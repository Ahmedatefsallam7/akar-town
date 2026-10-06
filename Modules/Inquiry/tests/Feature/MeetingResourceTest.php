<?php

namespace Modules\Inquiry\Tests\Feature;

use App\Jobs\SendLeadToCrm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Modules\Inquiry\Filament\Resources\MeetingResource;
use Modules\Inquiry\Filament\Resources\MeetingResource\Pages\CreateMeeting;
use Modules\Inquiry\Filament\Resources\MeetingResource\Pages\EditMeeting;
use Modules\Inquiry\Filament\Resources\MeetingResource\Pages\ListMeetings;
use Modules\Inquiry\Filament\Resources\MeetingResource\Pages\ViewMeeting;
use Modules\Inquiry\Models\Meeting;
use Modules\Users\Support\PermissionModules;
use Tests\TestCase;

class MeetingResourceTest extends TestCase
{
    use RefreshDatabase;

    private const CRM_URL = 'https://akartown.8xcrm.com/api/v1/lead_generation/web_form_routings/storeLead';

    public function test_users_without_the_permission_are_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['contact_us' => true]));

        $this->get(MeetingResource::getUrl('index'))->assertForbidden();
        $this->get(MeetingResource::getUrl('create'))->assertForbidden();
    }

    public function test_admin_can_open_every_page(): void
    {
        $this->actingAs($this->createAdmin());
        $meeting = Meeting::factory()->create();

        $this->get(MeetingResource::getUrl('index'))->assertOk()->assertSee($meeting->name);
        $this->get(MeetingResource::getUrl('create'))->assertOk();
        $this->get(MeetingResource::getUrl('view', ['record' => $meeting]))->assertOk();
        $this->get(MeetingResource::getUrl('edit', ['record' => $meeting]))->assertOk();
    }

    public function test_meetings_is_an_inquiries_and_crm_permission_module(): void
    {
        $this->assertSame('inquiries_crm', PermissionModules::all()['meetings']['group'] ?? null);
    }

    public function test_required_fields_and_known_choices(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateMeeting::class)
            ->fillForm(['name' => '', 'phone' => '', 'meeting_at' => null, 'type' => null, 'status' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'phone' => 'required', 'meeting_at' => 'required', 'type' => 'required', 'status' => 'required'])
            ->assertHasNoFormErrors(['email', 'source', 'url', 'notes_en', 'notes_ar', 'admin_notes_en', 'admin_notes_ar']);

        Livewire::test(CreateMeeting::class)
            ->fillForm(['name' => 'Sara', 'phone' => '0100', 'meeting_at' => now()->addDay(), 'type' => 'helicopter', 'status' => 'lost', 'url' => 'javascript:alert(1)'])
            ->call('create')
            ->assertHasFormErrors(['type', 'status', 'url']);
    }

    public function test_staff_add_a_booking_that_starts_pending(): void
    {
        Queue::fake();
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateMeeting::class)
            ->assertSchemaStateSet(['status' => Meeting::PENDING])
            ->fillForm([
                'name' => 'Sara Ahmed',
                'phone' => '+201001234567',
                'meeting_at' => '2026-11-02 14:30',
                'type' => 'site_visit',
                'notes_en' => 'Wants to see the 3-bedroom units.',
                'admin_notes_ar' => 'عميلة جادة',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $meeting = Meeting::sole();
        $this->assertSame(['site_visit', Meeting::PENDING, '2026-11-02 14:30'], [$meeting->type, $meeting->status, $meeting->meeting_at->format('Y-m-d H:i')]);
        Queue::assertPushed(SendLeadToCrm::class, fn (SendLeadToCrm $job) => $job->record->is($meeting));
    }

    public function test_a_new_booking_goes_to_the_crm_without_the_internal_notes(): void
    {
        config(['crm.token' => 't', 'crm.base_url' => 'https://akartown.8xcrm.com', 'crm.forms.schedule_viewing' => '33']);
        Http::fake([self::CRM_URL => Http::response(['data' => ['id' => 77]])]);

        $meeting = Meeting::create([
            'name' => 'Omar Ali',
            'phone' => '+201559998888',
            'meeting_at' => '2026-11-02 14:30',
            'type' => 'site_visit',
            'notes_en' => 'Interested in villas.',
            'admin_notes_en' => 'VIP, call before noon',
            'source' => 'Book a viewing: Mountain Park',
            'url' => 'https://akartown.com/projects/mountain-park',
        ]);

        Http::assertSent(fn (Request $request) => $request['form_id'] === '33'
            && str_contains($request['description'], 'Requested Property Site Visit at 2026-11-02 14:30')
            && str_contains($request['description'], 'Interested in villas.')
            && str_contains($request['description'], 'Source: Book a viewing: Mountain Park')
            && str_contains($request['description'], 'URL: https://akartown.com/projects/mountain-park')
            && ! str_contains($request['description'], 'VIP'));

        $this->assertSame('77', $meeting->fresh()->crm_lead_id);
    }

    public function test_a_booking_the_crm_did_not_take_can_be_sent_again(): void
    {
        $this->actingAs($this->createAdmin());
        Queue::fake();
        $meeting = Meeting::factory()->create();

        Livewire::test(ViewMeeting::class, ['record' => $meeting->getRouteKey()])->callAction('sendToCrm');

        Queue::assertPushed(SendLeadToCrm::class, 2);
    }

    public function test_bulk_confirm_and_complete_leave_closed_bookings_alone(): void
    {
        $this->actingAs($this->createAdmin());
        $pending = Meeting::factory()->create(['status' => Meeting::PENDING]);
        $rescheduled = Meeting::factory()->create(['status' => Meeting::RESCHEDULED]);
        $cancelled = Meeting::factory()->create(['status' => Meeting::CANCELLED]);
        $completed = Meeting::factory()->create(['status' => Meeting::COMPLETED]);
        $all = [$pending, $rescheduled, $cancelled, $completed];

        Livewire::test(ListMeetings::class)->callTableBulkAction('mark_confirmed', $all);
        $this->assertSame(
            [Meeting::CONFIRMED, Meeting::CONFIRMED, Meeting::CANCELLED, Meeting::COMPLETED],
            array_map(fn (Meeting $meeting) => $meeting->fresh()->status, $all),
        );

        Livewire::test(ListMeetings::class)->callTableBulkAction('mark_completed', $all);
        $this->assertSame(
            [Meeting::COMPLETED, Meeting::COMPLETED, Meeting::CANCELLED, Meeting::COMPLETED],
            array_map(fn (Meeting $meeting) => $meeting->fresh()->status, $all),
        );
    }

    public function test_bulk_status_needs_edit_and_bulk(): void
    {
        $this->actingAs($this->createUserWithPermissions(['meetings' => ['view', 'delete', 'bulk']]));
        Meeting::factory()->create();

        Livewire::test(ListMeetings::class)
            ->assertTableBulkActionHidden('mark_confirmed')
            ->assertTableBulkActionHidden('mark_completed');
    }

    public function test_filters_by_status_type_meeting_date_and_trash(): void
    {
        $this->actingAs($this->createAdmin());
        $visit = Meeting::factory()->create(['type' => 'site_visit', 'status' => Meeting::CONFIRMED, 'meeting_at' => now()->addDays(2)]);
        $call = Meeting::factory()->create(['type' => 'call', 'status' => Meeting::PENDING, 'meeting_at' => now()->addDays(20)]);

        Livewire::test(ListMeetings::class)
            ->filterTable('status', [Meeting::CONFIRMED])
            ->assertCanSeeTableRecords([$visit])->assertCanNotSeeTableRecords([$call]);
        Livewire::test(ListMeetings::class)
            ->filterTable('type', ['call'])
            ->assertCanSeeTableRecords([$call])->assertCanNotSeeTableRecords([$visit]);
        Livewire::test(ListMeetings::class)
            ->filterTable('meeting_at', ['meeting_until' => now()->addDays(5)->toDateString()])
            ->assertCanSeeTableRecords([$visit])->assertCanNotSeeTableRecords([$call]);

        $call->delete();
        Livewire::test(ListMeetings::class)
            ->assertCanNotSeeTableRecords([$call])
            ->filterTable('trashed', true)
            ->assertCanSeeTableRecords([$call]);
    }

    public function test_one_search_box_finds_by_how_the_name_email_or_phone_starts(): void
    {
        $this->actingAs($this->createAdmin());
        $sara = Meeting::factory()->create(['name' => 'Sara Ahmed', 'email' => 's.ahmed@example.com', 'phone' => '+201001112222']);
        $omar = Meeting::factory()->create(['name' => 'Omar Ali', 'email' => 'omar@example.com', 'phone' => '+201559998888']);

        foreach (['Sara Ahmed', 's.ahmed@', '+2010011'] as $term) {
            Livewire::test(ListMeetings::class)
                ->searchTable($term)
                ->assertCanSeeTableRecords([$sara])
                ->assertCanNotSeeTableRecords([$omar]);
        }
    }

    public function test_optional_columns_and_editing(): void
    {
        $this->actingAs($this->createAdmin());
        $meeting = Meeting::factory()->create();

        Livewire::test(ListMeetings::class)
            ->assertTableColumnExists('source')
            ->assertTableColumnExists('url')
            ->assertTableColumnExists('created_at');

        Livewire::test(EditMeeting::class, ['record' => $meeting->getRouteKey()])
            ->fillForm(['status' => Meeting::RESCHEDULED, 'meeting_at' => '2026-12-01 11:00', 'admin_notes_en' => 'Client asked to move it.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $meeting->refresh();
        $this->assertSame([Meeting::RESCHEDULED, '2026-12-01 11:00', 'Client asked to move it.'], [$meeting->status, $meeting->meeting_at->format('Y-m-d H:i'), $meeting->admin_notes_en]);
    }
}
