<?php

namespace Tests\Feature\Website;

use App\Jobs\SendLeadToCrm;
use App\Livewire\Website\PropertyMatching;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Inquiry\Models\Inquiry;
use Modules\Lookups\Models\Lookup;
use Modules\PossibleLeads\Models\PossibleLead;
use Modules\PossibleLeads\Support\Visitor;
use Tests\TestCase;

/**
 * The footer's "Property Matching" form: saved as a Contact Us message and sent to the CRM.
 */
class PropertyMatchingTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, Lookup> */
    private array $types = [];

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        RateLimiter::clear('property-matching:127.0.0.1');

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();

        $root = Lookup::create(['slug' => 'unittype', 'name_en' => 'Unit Type']);
        foreach (['Villa', 'Penthouse', 'Chalet', 'Duplex', 'Studio'] as $i => $name) {
            $this->types[$name] = $root->children()->create(['slug' => strtolower($name), 'name_en' => $name, 'name_ar' => "ع {$name}", 'order' => $i + 1]);
        }
    }

    public function test_the_footer_shows_the_first_four_unit_types_on_every_page(): void
    {
        Livewire::test(PropertyMatching::class)->assertSeeInOrder(['Villa', 'Penthouse', 'Chalet', 'Duplex'])->assertDontSee('Studio');

        $this->get(route('home'))->assertOk()->assertSeeLivewire(PropertyMatching::class);
        $this->get(route('properties'))->assertOk()->assertSeeLivewire(PropertyMatching::class);
    }

    public function test_a_request_is_saved_as_a_contact_message_and_sent_to_the_crm(): void
    {
        PossibleLead::create(['click_type' => 'whatsapp', 'visitor_id' => $visitor = Visitor::newId()]);

        Livewire::withCookies([Visitor::COOKIE => $visitor])
            ->test(PropertyMatching::class)
            ->assertSet('type', 'villa')
            ->set('type', 'penthouse')
            ->set('budgetMin', '2000000')
            ->set('budgetMax', '5000000')
            ->set('fullName', 'Sara Ali')
            ->set('phone', '+20 100 123 4567')
            ->set('email', 'sara@example.com')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSee('our team will contact you soon');

        $inquiry = Inquiry::sole();
        $this->assertSame(['Sara Ali', '+201001234567', 'sara@example.com', 'property_matching', $visitor], [$inquiry->full_name, $inquiry->phone, $inquiry->email, $inquiry->form, $inquiry->visitor_id]);
        $this->assertStringContainsString('Unit type: Penthouse', $inquiry->message);
        $this->assertStringContainsString('Budget: EGP 2,000,000 – EGP 5,000,000', $inquiry->message);
        Queue::assertPushed(SendLeadToCrm::class, fn (SendLeadToCrm $job) => $job->record->is($inquiry));
        // The visitor's earlier click now points at this request.
        $this->assertTrue(PossibleLead::sole()->converted_lead_id === $inquiry->id);
    }

    public function test_name_phone_and_email_are_required_and_the_budget_must_make_sense(): void
    {
        Livewire::test(PropertyMatching::class)
            ->set('type', 'studio') // not one of the shown chips
            ->set('budgetMin', '5000000')
            ->set('budgetMax', '1000000')
            ->set('phone', 'call me')
            ->set('email', 'not-an-email')
            ->call('send')
            ->assertHasErrors(['type', 'budgetMax' => 'gte', 'fullName' => 'required', 'phone', 'email'])
            ->assertSet('sent', false);

        // What the CRM refuses: a number that isn't valid for its country, and an address without a real domain.
        Livewire::test(PropertyMatching::class)
            ->set(['fullName' => 'Sara', 'phone' => '0100000000', 'email' => 'kfjds@fm'])
            ->call('send')
            ->assertHasErrors(['phone', 'email'])
            ->assertSee('must be a valid phone number');

        $this->assertSame(0, Inquiry::count());
    }

    public function test_tries_are_limited_per_visitor_failed_ones_included(): void
    {
        $send = fn (int $i) => Livewire::test(PropertyMatching::class)
            ->set(['fullName' => 'Sara', 'phone' => "0100123456{$i}", 'email' => "sara{$i}@example.com"])
            ->call('send');

        foreach (range(1, 5) as $i) {
            $send($i)->assertHasNoErrors();
        }
        // Failed tries count too (so "already sent" can't be used to check many numbers).
        foreach (range(1, 5) as $i) {
            Livewire::test(PropertyMatching::class)->set(['fullName' => 'Sara', 'phone' => 'bad', 'email' => 'bad'])->call('send')->assertHasErrors('phone');
        }
        $send(6)->assertHasErrors('email')->assertSee('Too many requests.');

        $this->assertSame(5, Inquiry::count());
    }

    public function test_one_request_per_phone_and_per_email(): void
    {
        Inquiry::factory()->create(['form' => 'property_matching', 'phone' => '01001234567', 'email' => 'sara@example.com']);
        // Other Contact Us forms and deleted requests don't count.
        Inquiry::factory()->create(['form' => 'contact_us', 'phone' => '01110000000', 'email' => 'omar@example.com']);
        Inquiry::factory()->create(['form' => 'property_matching', 'phone' => '01220000000', 'email' => 'mona@example.com'])->delete();

        Livewire::test(PropertyMatching::class)
            // The same number typed with spaces and a dash.
            ->set(['fullName' => 'Sara', 'phone' => '0100 123-4567', 'email' => 'sara@example.com'])
            ->call('send')
            ->assertHasErrors(['phone' => 'unique', 'email' => 'unique'])
            ->assertSee('A request was already sent with this phone number.');

        foreach ([['01110000000', 'omar@example.com'], ['01220000000', 'mona@example.com']] as [$phone, $email]) {
            Livewire::test(PropertyMatching::class)
                ->set(['fullName' => 'Visitor', 'phone' => $phone, 'email' => $email])
                ->call('send')
                ->assertHasNoErrors();
        }
    }
}
