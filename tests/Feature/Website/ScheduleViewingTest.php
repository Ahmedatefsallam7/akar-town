<?php

namespace Tests\Feature\Website;

use App\Jobs\SendLeadToCrm;
use App\Livewire\Website\ScheduleViewing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Inquiry\Models\Meeting;
use Modules\PossibleLeads\Models\PossibleLead;
use Modules\PossibleLeads\Support\Visitor;
use Modules\Units\Models\Unit;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Tests\TestCase;

/**
 * The property page's booking window (Dastan's schedule-viewing form): saved as a pending Meeting for that property and
 * sent to the CRM.
 */
class ScheduleViewingTest extends TestCase
{
    use RefreshDatabase;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        RateLimiter::clear('schedule-viewing:127.0.0.1');

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();

        $this->unit = Unit::factory()->create(['name_en' => 'Sky Penthouse', 'slug' => 'sky-penthouse']);
    }

    private function form(): mixed
    {
        return Livewire::test(ScheduleViewing::class, ['unitId' => $this->unit->id]);
    }

    public function test_the_property_page_buttons_open_the_window(): void
    {
        $html = $this->get(route('properties.show', 'sky-penthouse'))
            ->assertOk()
            ->assertSeeLivewire(ScheduleViewing::class)
            ->getContent();

        // The header's "Schedule a Visit", the sticky bar's "Schedule Visit" and the side card's "Schedule Viewing".
        $this->assertSame(3, substr_count($html, "\$dispatch('schedule-viewing')"));
    }

    public function test_a_booking_is_saved_as_a_pending_meeting_for_the_property_and_sent_to_the_crm(): void
    {
        PossibleLead::create(['click_type' => 'phone', 'visitor_id' => $visitor = Visitor::newId(), 'unit_id' => $this->unit->id]);
        $when = now()->addDays(3)->setTime(11, 30);

        Livewire::withCookies([Visitor::COOKIE => $visitor])
            ->test(ScheduleViewing::class, ['unitId' => $this->unit->id])
            ->set([
                'fullName' => 'Sara Ali',
                'phone' => '+20 100 123 4567',
                'email' => 'sara@example.com',
                'type' => 'online',
                'meetingAt' => $when->format('Y-m-d\TH:i'),
                'notes' => 'Is parking included?',
            ])
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSee('Viewing Requested!');

        $meeting = Meeting::sole();
        $this->assertSame(
            ['Sara Ali', '+201001234567', 'sara@example.com', 'online', $when->toDateTimeString(), 'Is parking included?', 'Property page: Sky Penthouse', Meeting::PENDING, $visitor],
            [$meeting->name, $meeting->phone, $meeting->email, $meeting->type, $meeting->meeting_at->toDateTimeString(), $meeting->notes_en, $meeting->source, $meeting->status, $meeting->visitor_id],
        );
        Queue::assertPushed(SendLeadToCrm::class, fn (SendLeadToCrm $job) => $job->record->is($meeting));
        $this->assertSame($meeting->id, PossibleLead::sole()->converted_lead_id);
    }

    public function test_name_phone_a_known_type_and_a_future_time_are_required(): void
    {
        $this->form()
            ->set(['phone' => '0100', 'email' => 'x@y', 'type' => 'dinner', 'meetingAt' => now()->subDay()->format('Y-m-d\TH:i')])
            ->call('send')
            ->assertHasErrors(['fullName' => 'required', 'phone', 'email', 'type', 'meetingAt' => 'after']);

        $this->form()
            ->set(['fullName' => 'Sara', 'phone' => '01001234567', 'meetingAt' => now()->addYears(2)->format('Y-m-d\TH:i')])
            ->call('send')
            ->assertHasErrors(['meetingAt' => 'before']);

        $this->assertSame(0, Meeting::count());
    }

    public function test_book_another_keeps_the_contact(): void
    {
        $this->form()
            ->set(['fullName' => 'Sara', 'phone' => '01001234567', 'meetingAt' => now()->addDay()->format('Y-m-d\TH:i')])
            ->call('send')
            ->call('again')
            ->assertSet('sent', false)
            ->assertSet('fullName', 'Sara')
            ->assertSet('meetingAt', '');
    }

    public function test_a_signed_in_visitor_gets_their_details_and_the_booking_is_linked(): void
    {
        $user = WebsiteUser::factory()->create(['name' => 'Omar', 'email' => 'omar@example.com', 'phone' => '01001234567']);
        $this->actingAs($user, 'website');

        $this->form()
            ->assertSet('fullName', 'Omar')
            ->set('meetingAt', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('send')
            ->assertHasNoErrors();

        $this->assertSame($user->id, Meeting::sole()->website_user_id);
    }

    public function test_bookings_are_limited_per_visitor(): void
    {
        foreach (range(1, 6) as $i) {
            $form = $this->form()->set(['fullName' => 'Sara', 'phone' => "0100123456{$i}", 'meetingAt' => now()->addDays($i)->format('Y-m-d\TH:i')])->call('send');
            $i <= 5 ? $form->assertHasNoErrors() : $form->assertHasErrors('meetingAt');
        }

        $this->assertSame(5, Meeting::count());
    }

    public function test_arabic(): void
    {
        $this->withCookie('locale', 'ar')->get(route('properties.show', 'sky-penthouse'))
            ->assertOk()
            ->assertSee(['احجز معاينة', 'زيارة الموقع', 'تأكيد طلب المعاينة']);
    }
}
