<?php

namespace Modules\Inquiry\Tests\Feature;

use App\Jobs\SendLeadToCrm;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint\Operators\ContainsOperator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Modules\Inquiry\Filament\Resources\InquiryResource;
use Modules\Inquiry\Filament\Resources\InquiryResource\Pages\CreateInquiry;
use Modules\Inquiry\Filament\Resources\InquiryResource\Pages\EditInquiry;
use Modules\Inquiry\Filament\Resources\InquiryResource\Pages\ListInquiries;
use Modules\Inquiry\Filament\Resources\InquiryResource\Pages\ViewInquiry;
use Modules\Inquiry\Models\Inquiry;
use Modules\Users\Support\PermissionModules;
use Tests\TestCase;

class InquiryResourceTest extends TestCase
{
    use RefreshDatabase;

    private const CRM_URL = 'https://akartown.8xcrm.com/api/v1/lead_generation/web_form_routings/storeLead';

    private function configureCrm(): void
    {
        config([
            'crm.token' => 'secret-token',
            'crm.base_url' => 'https://akartown.8xcrm.com',
            'crm.forms.contact_us' => '17',
        ]);
    }

    public function test_users_without_the_permission_are_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['projects' => true]));

        $this->get(InquiryResource::getUrl('index'))->assertForbidden();
        $this->get(InquiryResource::getUrl('create'))->assertForbidden();
    }

    public function test_admin_can_open_every_page(): void
    {
        $this->actingAs($this->createAdmin());
        $inquiry = Inquiry::factory()->create();

        $this->get(InquiryResource::getUrl('index'))->assertOk()->assertSee($inquiry->full_name);
        $this->get(InquiryResource::getUrl('create'))->assertOk();
        $this->get(InquiryResource::getUrl('view', ['record' => $inquiry]))->assertOk();
        $this->get(InquiryResource::getUrl('edit', ['record' => $inquiry]))->assertOk();
    }

    public function test_contact_us_is_an_inquiries_and_crm_permission_module(): void
    {
        $this->assertSame('inquiries_crm', PermissionModules::all()['contact_us']['group'] ?? null);
        $this->assertSame('/admin/contact-us', parse_url(InquiryResource::getUrl('index'), PHP_URL_PATH));
    }

    public function test_name_phone_and_message_are_required_email_and_origin_are_optional(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateInquiry::class)
            ->fillForm(['full_name' => '', 'phone' => '', 'message' => ''])
            ->call('create')
            ->assertHasFormErrors(['full_name' => 'required', 'phone' => 'required', 'message' => 'required'])
            ->assertHasNoFormErrors(['email', 'source', 'url']);
    }

    public function test_email_and_url_are_checked(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateInquiry::class)
            ->fillForm(['full_name' => 'Sara', 'phone' => '0100', 'message' => 'Hi', 'email' => 'nope', 'url' => 'javascript:alert(1)'])
            ->call('create')
            ->assertHasFormErrors(['email', 'url']);
    }

    public function test_an_admin_adds_a_message_by_hand_after_a_phone_call(): void
    {
        Queue::fake();
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateInquiry::class)
            ->fillForm([
                'full_name' => 'Sara Ahmed',
                'email' => 'sara@example.com',
                'phone' => '+201001234567',
                'message' => 'Looking for a 3-bedroom apartment.',
                'source' => 'Phone call',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $inquiry = Inquiry::sole();
        $this->assertSame(['Sara Ahmed', 'Phone call', null], [$inquiry->full_name, $inquiry->source, $inquiry->url]);
        Queue::assertPushed(SendLeadToCrm::class, fn (SendLeadToCrm $job) => $job->record->is($inquiry));
    }

    public function test_every_new_message_is_sent_to_the_crm_as_a_fresh_lead_with_its_origin(): void
    {
        $this->configureCrm();
        Http::fake([self::CRM_URL => Http::response(['data' => ['id' => 9001]])]);

        $inquiry = Inquiry::create([
            'full_name' => 'Sara Ahmed Ali',
            'email' => 'sara@example.com',
            'phone' => '+20 100 123 4567',
            'message' => 'Is the villa still available?',
            'source' => 'Project: Mountain Park',
            'url' => 'https://akartown.com/projects/mountain-park',
        ]);

        Http::assertSent(fn (Request $request) => $request->url() === self::CRM_URL
            && $request->hasHeader('Authorization', 'Bearer secret-token')
            && $request['form_id'] === '17'
            && $request['first_name'] === 'Sara'
            && $request['last_name'] === 'Ahmed Ali'
            && $request['phones'] === [['phone' => '+201001234567', 'country_code' => 'EG']]
            && $request['social_accounts'][0]['social_account'] === 'sara@example.com'
            && str_contains($request['description'], 'Is the villa still available?')
            && str_contains($request['description'], 'Source: Project: Mountain Park')
            && str_contains($request['description'], 'URL: https://akartown.com/projects/mountain-park'));

        $inquiry->refresh();
        $this->assertSame('9001', $inquiry->crm_lead_id);
        $this->assertNotNull($inquiry->crm_synced_at);
    }

    public function test_a_lead_is_sent_once_and_editing_does_not_send_it_again(): void
    {
        $this->configureCrm();
        Http::fake([self::CRM_URL => Http::response(['data' => ['id' => 1]])]);

        $inquiry = Inquiry::factory()->create();
        $inquiry->update(['message' => 'Changed']);
        (new SendLeadToCrm($inquiry->fresh()))->handle();

        Http::assertSentCount(1);
    }

    public function test_when_the_crm_fails_the_message_is_kept_and_retried(): void
    {
        $this->configureCrm();
        Http::fake([self::CRM_URL => Http::response('down', 503)]);

        try {
            Inquiry::factory()->create(['full_name' => 'Kept']);
        } catch (\Throwable) {
            // With the sync queue in tests the failure reaches us; on the server the queue retries it later.
        }

        $this->assertFalse(Inquiry::where('full_name', 'Kept')->sole()->isSentToCrm());
        $this->assertSame(4, (new SendLeadToCrm(Inquiry::sole()))->tries);
    }

    public function test_without_crm_settings_nothing_is_sent(): void
    {
        Http::fake();

        Inquiry::factory()->create();

        Http::assertNothingSent();
    }

    public function test_a_message_the_crm_did_not_take_can_be_sent_again_from_its_page(): void
    {
        $this->actingAs($this->createAdmin());
        Queue::fake();
        $inquiry = Inquiry::factory()->create();
        $sent = Inquiry::factory()->create();
        $sent->forceFill(['crm_lead_id' => '5'])->saveQuietly();

        Livewire::test(ViewInquiry::class, ['record' => $inquiry->getRouteKey()])
            ->callAction('sendToCrm');
        Queue::assertPushed(SendLeadToCrm::class, 3); // two on create, one by hand

        Livewire::test(ViewInquiry::class, ['record' => $sent->getRouteKey()])->assertActionHidden('sendToCrm');
    }

    public function test_list_columns_query_builder_filters_and_trash(): void
    {
        $this->actingAs($this->createAdmin());
        $sara = Inquiry::factory()->create(['full_name' => 'Sara Ahmed', 'message' => 'Villa in New Cairo']);
        $omar = Inquiry::factory()->create(['full_name' => 'Omar Ali', 'message' => 'Office space']);

        Livewire::test(ListInquiries::class)
            ->assertTableColumnExists('message')
            ->assertTableColumnExists('source')
            ->assertTableColumnExists('url')
            ->assertCanSeeTableRecords([$sara, $omar])
            ->searchTable('Sara')
            ->assertCanSeeTableRecords([$sara])
            ->assertCanNotSeeTableRecords([$omar]);

        Livewire::test(ListInquiries::class)
            ->filterTable('queryBuilder', [
                'rules' => [
                    ['type' => 'message', 'data' => ['operator' => ContainsOperator::make()->getName(), 'settings' => ['text' => 'Villa']]],
                ],
            ])
            ->assertCanSeeTableRecords([$sara])
            ->assertCanNotSeeTableRecords([$omar]);

        Livewire::test(ListInquiries::class)->callTableBulkAction('delete', [$omar]);
        $this->assertSoftDeleted($omar);
        Livewire::test(ListInquiries::class)
            ->assertCanNotSeeTableRecords([$omar])
            ->filterTable('trashed', true)
            ->assertCanSeeTableRecords([$omar]);
    }

    public function test_one_search_box_finds_by_how_the_name_email_or_phone_starts(): void
    {
        $this->actingAs($this->createAdmin());
        $sara = Inquiry::factory()->create(['full_name' => 'Sara Ahmed', 'email' => 's.ahmed@example.com', 'phone' => '+201001112222']);
        $omar = Inquiry::factory()->create(['full_name' => 'Omar Ali', 'email' => 'omar@example.com', 'phone' => '+201559998888']);

        foreach (['Sara Ahmed', 's.ahmed@', '+2010011'] as $term) {
            Livewire::test(ListInquiries::class)
                ->searchTable($term)
                ->assertCanSeeTableRecords([$sara])
                ->assertCanNotSeeTableRecords([$omar]);
        }
    }

    public function test_editing_updates_the_message(): void
    {
        $this->actingAs($this->createAdmin());
        $inquiry = Inquiry::factory()->create();

        Livewire::test(EditInquiry::class, ['record' => $inquiry->getRouteKey()])
            ->fillForm(['message' => 'Called back, wants a viewing.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Called back, wants a viewing.', $inquiry->fresh()->message);
    }

    public function test_the_count_shows_in_the_sidebar(): void
    {
        $this->actingAs($this->createAdmin());
        Inquiry::factory()->count(3)->create();

        $this->assertSame('3', InquiryResource::getNavigationBadge());
    }

    public function test_the_queue_runs_from_the_scheduler(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('queue:work --stop-when-empty');
        $this->assertSame(0, DB::table('jobs')->count());
    }
}
