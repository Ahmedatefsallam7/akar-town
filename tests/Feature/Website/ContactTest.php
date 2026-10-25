<?php

namespace Tests\Feature\Website;

use App\Jobs\SendLeadToCrm;
use App\Livewire\Website\ContactForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Modules\GeneralSettings\Settings\BusinessInfoSettings;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Inquiry\Models\Inquiry;
use Modules\PossibleLeads\Models\PossibleLead;
use Modules\PossibleLeads\Support\Visitor;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Tests\TestCase;

/**
 * The contact page (the design's contact.html): the business details from Settings and the "Send Us a Message" form,
 * saved as a Contact Us message and sent to the CRM.
 */
class ContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        RateLimiter::clear('contact-form:127.0.0.1');

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
    }

    private function business(array $values): void
    {
        $business = app(BusinessInfoSettings::class);
        foreach ($values as $key => $value) {
            $business->{$key} = $value;
        }
        $business->save();
    }

    public function test_the_page_shows_the_form_and_the_business_details(): void
    {
        $this->business(['hotline' => '19000', 'email' => 'info@akartown.test', 'whatsapp' => '+20 115 000 0727', 'headquarters_en' => '90th Street, New Cairo', 'working_hours_from' => '09:00', 'working_hours_to' => '21:00']);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSeeLivewire(ContactForm::class)
            ->assertSee(["Let's Start a <em>Conversation</em>", 'Send Us a <em>Message</em>'], false)
            ->assertSee(["I'm Interested In", 'Investment Opportunities'])
            ->assertSee(['href="tel:19000"', 'href="mailto:info@akartown.test"', 'href="https://wa.me/201150000727"', '90th Street, New Cairo', 'Available 9 AM – 9 PM'], false)
            ->assertSee('maps.google.com/maps?q=90th+Street%2C+New+Cairo', false)
            ->assertSee(['Call Us Directly', '19000 · Available 9 AM – 9 PM', 'Browse All Properties', 'href="'.route('properties').'"'], false);
    }

    public function test_every_phone_and_branch_is_listed(): void
    {
        $this->business(['hotline' => '19000', 'phone_primary' => '01001234567', 'phone_secondary' => '01112345678', 'phone_tertiary' => null, 'branch_1_en' => 'Mall of Arabia', 'branch_2_en' => null, 'branch_3_en' => 'Marassi']);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee(['href="tel:19000"', 'href="tel:01001234567"', 'href="tel:01112345678"'], false)
            ->assertSee(['Branches', 'Mall of Arabia', 'Marassi']);
    }

    public function test_empty_settings_hide_their_cards_and_the_map(): void
    {
        $this->business(['hotline' => null, 'email' => null, 'whatsapp' => null, 'headquarters_en' => '']);

        $this->get(route('contact'))
            ->assertOk()
            ->assertDontSee(['href="mailto:', 'maps.google.com', 'Akar Town HQ'], false);
    }

    public function test_a_message_is_saved_as_a_contact_message_and_sent_to_the_crm(): void
    {
        PossibleLead::create(['click_type' => 'phone', 'visitor_id' => $visitor = Visitor::newId()]);

        Livewire::withCookies([Visitor::COOKIE => $visitor])
            ->test(ContactForm::class)
            ->set([
                'fullName' => 'Sara Ali',
                'phone' => '+20 100 123 4567',
                'email' => 'sara@example.com',
                'interest' => 'invest',
                'message' => 'A villa in New Cairo.',
            ])
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSee('Message sent');

        $inquiry = Inquiry::sole();
        $this->assertSame(['Sara Ali', '+201001234567', 'sara@example.com', 'contact_us', 'Contact page', $visitor], [$inquiry->full_name, $inquiry->phone, $inquiry->email, $inquiry->form, $inquiry->source, $inquiry->visitor_id]);
        $this->assertSame("Interested in: Investment Opportunities\n\nA villa in New Cairo.", $inquiry->message);
        Queue::assertPushed(SendLeadToCrm::class, fn (SendLeadToCrm $job) => $job->record->is($inquiry));
        $this->assertSame($inquiry->id, PossibleLead::sole()->converted_lead_id);
    }

    public function test_the_interest_is_optional(): void
    {
        Livewire::test(ContactForm::class)
            ->set(['fullName' => 'Sara', 'phone' => '01001234567', 'email' => 'sara@example.com', 'message' => 'Hello'])
            ->call('send')
            ->assertHasNoErrors();

        $this->assertSame('Hello', Inquiry::sole()->message);
    }

    public function test_name_phone_email_and_message_are_required_and_the_interest_must_be_known(): void
    {
        Livewire::test(ContactForm::class)
            ->set(['phone' => '0100000000', 'email' => 'kfjds@fm', 'interest' => 'lots'])
            ->call('send')
            ->assertHasErrors(['fullName' => 'required', 'phone', 'email', 'interest', 'message' => 'required'])
            ->assertSet('sent', false);

        $this->assertSame(0, Inquiry::count());
    }

    public function test_a_signed_in_visitor_gets_their_details_filled_and_the_message_linked(): void
    {
        $user = WebsiteUser::factory()->create(['name' => 'Omar', 'email' => 'omar@example.com', 'phone' => '01001234567']);
        $this->actingAs($user, 'website');

        Livewire::test(ContactForm::class)
            ->assertSet('fullName', 'Omar')
            ->assertSet('email', 'omar@example.com')
            ->set('message', 'Call me')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertSame($user->id, Inquiry::sole()->website_user_id);
    }

    public function test_sends_are_limited_per_visitor(): void
    {
        foreach (range(1, 6) as $i) {
            $form = Livewire::test(ContactForm::class)->set(['fullName' => 'Sara', 'phone' => "0100123456{$i}", 'email' => "sara{$i}@example.com", 'message' => 'Hi'])->call('send');
            $i <= 5 ? $form->assertHasNoErrors() : $form->assertHasErrors('email');
        }

        $this->assertSame(5, Inquiry::count());
    }

    public function test_arabic(): void
    {
        $this->withCookie('locale', 'ar')->get(route('contact'))->assertOk()->assertSee(['لنبدأ <em>حديثنا</em>', 'أنا مهتم بـ', 'فرص استثمارية'], false);
    }
}
