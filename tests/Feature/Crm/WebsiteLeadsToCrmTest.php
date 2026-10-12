<?php

namespace Tests\Feature\Crm;

use App\Jobs\SendLeadToCrm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Modules\Inquiry\Filament\Resources\InquiryResource\Pages\ListInquiries;
use Modules\Inquiry\Models\Inquiry;
use Modules\Inquiry\Models\Meeting;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource\Pages\ViewUnitRequest;
use Modules\UnitRequests\Models\UnitRequest;
use Tests\TestCase;

/**
 * Every website form (contact, property / project enquiry, viewing booking, sell request) becomes a CRM lead by
 * itself, with no export / import step, and a lead the CRM missed is sent again.
 */
class WebsiteLeadsToCrmTest extends TestCase
{
    use RefreshDatabase;

    private const CRM_URL = 'https://akartown.8xcrm.com/api/v1/lead_generation/web_form_routings/storeLead';

    private function configureCrm(array $forms = []): void
    {
        config([
            'crm.token' => 'secret',
            'crm.base_url' => 'https://akartown.8xcrm.com',
            'crm.forms' => $forms + ['contact_us' => '1', 'property_enquiry' => null, 'project_enquiry' => null, 'schedule_viewing' => null, 'sell_property' => null],
        ]);
        Http::fake([self::CRM_URL => Http::sequence()->whenEmpty(Http::response(['data' => ['id' => 500]]))]);
    }

    private function sentFormIds(): array
    {
        return Http::recorded()->map(fn (array $pair) => $pair[0]['form_id'])->all();
    }

    public function test_every_kind_of_website_form_becomes_a_crm_lead_on_its_own_form(): void
    {
        $this->configureCrm(['property_enquiry' => '2', 'project_enquiry' => '3', 'schedule_viewing' => '4', 'sell_property' => '5']);

        Inquiry::factory()->create(['form' => 'contact_us']);
        Inquiry::factory()->create(['form' => 'property_enquiry', 'source' => 'Property: Garden Villa']);
        Inquiry::factory()->create(['form' => 'project_enquiry', 'source' => 'Project: Mountain Park']);
        Meeting::factory()->create();
        UnitRequest::factory()->create();

        $this->assertSame(['1', '2', '3', '4', '5'], $this->sentFormIds());
        $this->assertSame(0, Inquiry::whereNull('crm_lead_id')->count() + Meeting::whereNull('crm_lead_id')->count() + UnitRequest::whereNull('crm_lead_id')->count());
    }

    public function test_a_form_without_its_own_crm_form_goes_to_the_contact_us_one(): void
    {
        $this->configureCrm();

        Inquiry::factory()->create(['form' => 'property_enquiry']);
        UnitRequest::factory()->create();

        $this->assertSame(['1', '1'], $this->sentFormIds());
    }

    public function test_the_lead_says_which_form_and_page_it_came_from(): void
    {
        $this->configureCrm();

        Inquiry::factory()->create(['form' => 'project_enquiry', 'source' => 'Project: Mountain Park', 'url' => 'https://akartown.com/projects/mountain-park']);

        Http::assertSent(fn (Request $request) => str_contains($request['description'], 'Form: Project Enquiry')
            && str_contains($request['description'], 'Source: Project: Mountain Park')
            && str_contains($request['description'], 'URL: https://akartown.com/projects/mountain-park'));
    }

    public function test_a_sell_request_lead_has_the_owner_and_the_unit_but_not_the_internal_notes(): void
    {
        $this->configureCrm();

        UnitRequest::factory()->create([
            'seller_name' => 'Omar Ali', 'phone' => '+201559998888', 'name_en' => 'Garden Villa', 'price' => 9000000,
            'internal_notes' => 'Owner is difficult',
        ]);

        Http::assertSent(fn (Request $request) => $request['full_name'] === 'Omar Ali'
            && str_contains($request['description'], 'Sell request: Garden Villa')
            && str_contains($request['description'], 'Price: 9,000,000 EGP')
            && ! str_contains($request['description'], 'difficult'));
    }

    public function test_a_web_page_answer_is_not_taken_as_sent(): void
    {
        // A wrong address (e.g. the CRM's website instead of its API) answers 200 with an HTML page.
        config(['crm.token' => 'secret', 'crm.base_url' => 'https://akartown.8xcrm.com', 'crm.forms.contact_us' => '1']);
        Http::fake([self::CRM_URL => Http::response('<!doctype html><title>8X CRM</title>', 200, ['Content-Type' => 'text/html'])]);
        Queue::fake();
        $inquiry = Inquiry::factory()->create();

        try {
            (new SendLeadToCrm($inquiry))->handle();
            $this->fail('An HTML answer should count as a failure.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('JSON', $exception->getMessage());
        }

        $this->assertNull($inquiry->fresh()->crm_lead_id);
    }

    public function test_leads_the_crm_missed_are_sent_again_every_hour(): void
    {
        $this->configureCrm();
        Queue::fake();
        $this->travelTo('2026-10-07 10:00');
        $missed = Inquiry::factory()->create(['created_at' => now()->subHours(3)]);
        $missedRequest = UnitRequest::factory()->create(['created_at' => now()->subHours(5)]);
        $tooNew = Meeting::factory()->create(['created_at' => now()->subMinutes(20)]);
        $tooOld = Inquiry::factory()->create(['created_at' => now()->subDays(4)]);
        $sent = Inquiry::factory()->create(['created_at' => now()->subHours(3)]);
        $sent->forceFill(['crm_lead_id' => '9'])->saveQuietly();
        Queue::fake(); // forget the jobs queued on create

        $this->artisan('crm:send-unsent')->expectsOutputToContain('Queued 2 unsent leads');

        Queue::assertPushed(SendLeadToCrm::class, 2);
        Queue::assertPushed(SendLeadToCrm::class, fn (SendLeadToCrm $job) => $job->record->is($missed) && $job->tries === 1);
        Queue::assertPushed(SendLeadToCrm::class, fn (SendLeadToCrm $job) => $job->record->is($missedRequest));
        $this->assertNotNull($tooNew);
        $this->assertNotNull($tooOld);
        $this->artisan('schedule:list')->expectsOutputToContain('crm:send-unsent');
    }

    public function test_without_crm_settings_the_resend_does_nothing(): void
    {
        Queue::fake();
        Inquiry::factory()->create(['created_at' => now()->subHours(3)]);
        Queue::fake();

        $this->artisan('crm:send-unsent')->expectsOutputToContain('CRM settings are missing');
        Queue::assertNothingPushed();
    }

    public function test_staff_see_and_filter_leads_the_crm_did_not_get_and_can_send_one_at_once(): void
    {
        Queue::fake();
        $this->actingAs($this->createAdmin());
        $unsent = Inquiry::factory()->create();
        $sent = Inquiry::factory()->create();
        $sent->forceFill(['crm_lead_id' => '7'])->saveQuietly();
        $request = UnitRequest::factory()->create();

        Livewire::test(ListInquiries::class)
            ->filterTable('sent_to_crm', false)
            ->assertCanSeeTableRecords([$unsent])
            ->assertCanNotSeeTableRecords([$sent]);

        Livewire::test(ViewUnitRequest::class, ['record' => $request->getRouteKey()])->callAction('sendToCrm');
        Queue::assertPushed(SendLeadToCrm::class, fn (SendLeadToCrm $job) => $job->record->is($request));
    }
}
