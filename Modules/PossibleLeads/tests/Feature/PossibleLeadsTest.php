<?php

namespace Modules\PossibleLeads\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Modules\Inquiry\Models\Inquiry;
use Modules\Inquiry\Models\Meeting;
use Modules\PossibleLeads\Filament\Resources\PossibleLeadResource;
use Modules\PossibleLeads\Filament\Resources\PossibleLeadResource\Pages\ListPossibleLeads;
use Modules\PossibleLeads\Filament\Widgets\PossibleLeadsStats;
use Modules\PossibleLeads\Models\PossibleLead;
use Modules\PossibleLeads\Support\Visitor;
use Modules\Projects\Models\Project;
use Modules\UnitRequests\Models\UnitRequest;
use Modules\Units\Models\Unit;
use Modules\Users\Support\PermissionModules;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Tests\TestCase;

class PossibleLeadsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake(); // leads go to the CRM in the background; not under test here
    }

    public function test_the_website_records_form_whatsapp_and_phone_clicks_with_the_page_and_listing(): void
    {
        $unit = Unit::factory()->create();
        $project = Project::factory()->create();

        $first = $this->postJson(route('api.possible-leads.track'), ['click_type' => 'whatsapp', 'page_url' => 'https://akartown.com/properties/garden-villa', 'unit_id' => $unit->id])
            ->assertCreated()
            ->assertCookie(Visitor::COOKIE);
        $visitor = $first->getCookie(Visitor::COOKIE, false)->getValue();

        $this->withCredentials()->withUnencryptedCookie(Visitor::COOKIE, $visitor)
            ->postJson(route('api.possible-leads.track'), ['click_type' => 'phone', 'project_id' => $project->id])
            ->assertCreated();
        $this->withCredentials()->withUnencryptedCookie(Visitor::COOKIE, $visitor)
            ->postJson(route('api.possible-leads.track'), ['click_type' => 'form', 'page_url' => 'https://akartown.com/contact'])
            ->assertCreated();

        $this->assertSame(['whatsapp', 'phone', 'form'], PossibleLead::orderBy('id')->pluck('click_type')->all());
        // The same browser keeps one visitor id, so it counts once.
        $this->assertSame([$visitor], PossibleLead::distinct()->pluck('visitor_id')->all());
        $this->assertSame([$unit->id, null, null], PossibleLead::orderBy('id')->pluck('unit_id')->all());
        $this->assertSame([null, $project->id, null], PossibleLead::orderBy('id')->pluck('project_id')->all());
    }

    public function test_bad_clicks_are_refused_and_the_endpoint_is_rate_limited(): void
    {
        $this->postJson(route('api.possible-leads.track'), ['click_type' => 'email'])->assertUnprocessable();
        $this->postJson(route('api.possible-leads.track'), ['click_type' => 'phone', 'page_url' => 'javascript:alert(1)'])->assertUnprocessable();
        $this->postJson(route('api.possible-leads.track'), ['click_type' => 'phone', 'unit_id' => 999])->assertUnprocessable();
        // A made-up visitor cookie isn't trusted: a fresh id is given.
        $this->withCredentials()->withUnencryptedCookie(Visitor::COOKIE, 'not-a-uuid')->postJson(route('api.possible-leads.track'), ['click_type' => 'phone'])->assertCreated();
        $this->assertTrue(str($ids = PossibleLead::sole()->visitor_id)->isUuid(), $ids);

        foreach (range(1, 59) as $i) {
            $this->postJson(route('api.possible-leads.track'), ['click_type' => 'phone']);
        }
        $this->postJson(route('api.possible-leads.track'), ['click_type' => 'phone'])->assertTooManyRequests();
    }

    public function test_recording_a_click_starts_no_session(): void
    {
        $this->postJson(route('api.possible-leads.track'), ['click_type' => 'phone'])->assertCreated();

        $this->assertSame(0, DB::table('sessions')->count());
    }

    public function test_a_form_sent_later_by_the_same_visitor_converts_their_clicks(): void
    {
        $visitor = Visitor::newId();
        $mine = PossibleLead::factory()->count(2)->create(['visitor_id' => $visitor, 'created_at' => now()->subHour()]);
        $someoneElse = PossibleLead::factory()->create(['created_at' => now()->subHour()]);

        $inquiry = Inquiry::factory()->create(['visitor_id' => $visitor]);

        foreach ($mine as $click) {
            $click->refresh();
            $this->assertTrue($click->isConverted());
            $this->assertTrue($click->convertedLead->is($inquiry));
        }
        $this->assertFalse($someoneElse->fresh()->isConverted());
    }

    public function test_a_logged_in_users_clicks_convert_by_account_and_meetings_and_sell_requests_count_too(): void
    {
        $user = WebsiteUser::factory()->create();
        $byAccount = PossibleLead::factory()->create(['website_user_id' => $user->id, 'created_at' => now()->subHour()]);
        $visitor = Visitor::newId();
        $byVisitor = PossibleLead::factory()->create(['visitor_id' => $visitor, 'created_at' => now()->subHour()]);

        $meeting = Meeting::factory()->create(['website_user_id' => $user->id]);
        $request = UnitRequest::factory()->create(['visitor_id' => $visitor]);

        $this->assertTrue($byAccount->fresh()->convertedLead->is($meeting));
        $this->assertTrue($byVisitor->fresh()->convertedLead->is($request));
    }

    public function test_a_form_sent_logged_out_keeps_the_account_of_clicks_made_logged_in(): void
    {
        Queue::fake();
        $user = WebsiteUser::factory()->create();
        $visitor = Visitor::newId();
        $click = PossibleLead::factory()->create(['visitor_id' => $visitor, 'website_user_id' => $user->id, 'created_at' => now()->subHour()]);

        $inquiry = Inquiry::factory()->create(['visitor_id' => $visitor, 'website_user_id' => null]);

        $this->assertSame($user->id, $click->fresh()->website_user_id);
        $this->assertTrue($click->fresh()->convertedLead->is($inquiry));
    }

    public function test_a_lead_added_by_staff_converts_nothing(): void
    {
        PossibleLead::factory()->create();

        Inquiry::factory()->create();

        $this->assertSame(0, PossibleLead::converted()->count());
    }

    public function test_the_admin_page_lists_filters_and_is_read_only(): void
    {
        $this->actingAs($this->createAdmin());
        $whatsapp = PossibleLead::factory()->create(['click_type' => 'whatsapp', 'created_at' => now()->subDays(10)]);
        $phone = PossibleLead::factory()->create(['click_type' => 'phone', 'created_at' => now()->subMinute()]);
        $converted = PossibleLead::factory()->create(['click_type' => 'form', 'created_at' => now()->subMinutes(2)]);
        $converted->forceFill(['converted_at' => now()])->save();

        $this->get(PossibleLeadResource::getUrl('index'))->assertOk();
        $this->assertFalse(PossibleLeadResource::canCreate());
        $this->assertSame(['index'], array_keys(PossibleLeadResource::getPages()));

        Livewire::test(ListPossibleLeads::class)
            ->assertCanSeeTableRecords([$phone, $converted, $whatsapp], inOrder: true)
            ->filterTable('click_type', ['whatsapp'])
            ->assertCanSeeTableRecords([$whatsapp])->assertCanNotSeeTableRecords([$phone, $converted]);

        Livewire::test(ListPossibleLeads::class)
            ->filterTable('created_at', ['created_from' => now()->subDay()->toDateString()])
            ->assertCanSeeTableRecords([$phone, $converted])->assertCanNotSeeTableRecords([$whatsapp]);

        Livewire::test(ListPossibleLeads::class)
            ->filterTable('converted', true)
            ->assertCanSeeTableRecords([$converted])->assertCanNotSeeTableRecords([$phone, $whatsapp]);

        Livewire::test(ListPossibleLeads::class)->callTableBulkAction('delete', [$whatsapp]);
        $this->assertNull($whatsapp->fresh());
    }

    public function test_this_months_stats_count_each_visitor_once(): void
    {
        $this->actingAs($this->createAdmin());
        $visitor = Visitor::newId();
        PossibleLead::factory()->count(3)->create(['visitor_id' => $visitor]);
        PossibleLead::factory()->create();
        PossibleLead::factory()->create(['visitor_id' => $visitor])->forceFill(['converted_at' => now()])->save();

        $stats = (fn () => $this->getStats())->call(new PossibleLeadsStats);

        $this->assertSame(['5', '2', '1'], array_map(fn ($stat) => (string) $stat->getValue(), $stats));
    }

    public function test_permission_is_view_delete_and_bulk_in_customers_and_crm(): void
    {
        $module = PermissionModules::all()['possible_leads'];

        $this->assertSame('inquiries_crm', $module['group']);
        $this->assertSame(['view', 'delete', 'bulk'], $module['actions']);

        $this->actingAs($this->createUserWithPermissions(['contact_us' => true]));
        $this->get(PossibleLeadResource::getUrl('index'))->assertForbidden();
    }
}
