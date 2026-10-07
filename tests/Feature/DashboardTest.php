<?php

namespace Tests\Feature;

use App\Filament\Widgets\KpiCardsWidget;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\WelcomeWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Modules\Careers\Filament\Resources\CareerResource;
use Modules\Developers\Models\Developer;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Inquiry\Filament\Resources\InquiryResource;
use Modules\Inquiry\Filament\Resources\MeetingResource;
use Modules\Inquiry\Filament\Widgets\MeetingsCalendarWidget;
use Modules\Inquiry\Models\Meeting;
use Modules\Lookups\Models\Lookup;
use Modules\Projects\Filament\Resources\ProjectResource;
use Modules\Projects\Filament\Widgets\ProjectsByCityChart;
use Modules\Projects\Models\Project;
use Modules\Units\Filament\Resources\UnitResource;
use Modules\Units\Filament\Widgets\UnitsByTypeChart;
use Modules\Units\Models\Unit;
use Modules\Users\Models\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_greets_the_user_and_shows_the_site_status(): void
    {
        $this->actingAs($this->createAdmin(['name' => 'Sara Ali']));

        $this->get('/admin')
            ->assertOk()
            ->assertSee(__('admin.dashboard.welcome', ['name' => 'Sara Ali']))
            ->assertSee(__('admin.dashboard.widgets.platform_happening'))
            ->assertSee(__('admin.site_status.offline'))
            ->assertDontSee('filament-info-widget');
    }

    public function test_quick_actions_and_kpi_cards_link_to_the_lists_the_user_can_open(): void
    {
        $this->actingAs($this->createAdmin());
        Developer::factory()->count(3)->create();

        $this->assertSame(
            [ProjectResource::getUrl('index'), UnitResource::getUrl('index'), InquiryResource::getUrl('index'), CareerResource::getUrl('index'), MeetingResource::getUrl('index')],
            array_column(QuickActionsWidget::actions(), 'url'),
        );

        Livewire::test(KpiCardsWidget::class)
            ->assertSee(__('admin.dashboard.stats.total_projects'))
            ->assertSee(__('admin.dashboard.stats.total_properties'))
            ->assertSee(__('admin.dashboard.stats.contact_inquiries'))
            ->assertSee(__('admin.dashboard.stats.job_applicants'))
            ->assertSee(__('admin.dashboard.stats.total_developers'))
            ->assertSee(ProjectResource::getUrl('index'), false);

        $this->actingAs($this->createUserWithPermissions(['projects' => ['view']]));
        $this->assertSame([ProjectResource::getUrl('index')], array_column(QuickActionsWidget::actions(), 'url'));
        Livewire::test(KpiCardsWidget::class)
            ->assertSee(__('admin.dashboard.stats.total_projects'))
            ->assertDontSee(__('admin.dashboard.stats.total_developers'));
    }

    public function test_user_without_access_sees_a_helpful_message(): void
    {
        $this->seedRoles();
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertOk()->assertSee(__('admin.dashboard.no_access'));
    }

    public function test_settings_editors_switch_the_site_online_and_offline(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(WelcomeWidget::class)->call('toggleSite')->assertNotified();
        $this->assertTrue(app(SiteSettings::class)->is_live);

        $this->actingAs($this->createUserWithPermissions(['general_settings' => ['view'], 'projects' => true]));
        Livewire::test(WelcomeWidget::class)->assertDontSeeHtml('wire:click="toggleSite"')->call('toggleSite')->assertForbidden();
        $this->assertTrue(app(SiteSettings::class)->refresh()->is_live);
    }

    public function test_an_offline_site_shows_visitors_the_maintenance_page_but_the_admin_keeps_working(): void
    {
        Route::middleware('web')->get('/test-public-page', fn () => 'public page');
        $settings = app(SiteSettings::class);

        $settings->is_live = false;
        $settings->save();
        $this->get('/test-public-page')->assertStatus(503)->assertSee(__('admin.site_status.offline_message'));
        $this->get('/admin/login')->assertOk();

        $settings->is_live = true;
        $settings->save();
        $this->get('/test-public-page')->assertOk()->assertSee('public page');
    }

    public function test_charts_split_projects_by_city_and_properties_by_type(): void
    {
        $this->actingAs($this->createAdmin());
        $root = Lookup::create(['slug' => 'locations', 'name_en' => 'Locations']);
        $egypt = $root->children()->create(['slug' => 'egypt', 'name_en' => 'Egypt']);
        $cairo = $egypt->children()->create(['slug' => 'cairo', 'name_en' => 'Cairo']);
        Project::factory()->count(2)->create(['country_id' => $egypt->id, 'city_id' => $cairo->id]);
        Project::factory()->create();
        $types = Lookup::create(['slug' => 'unittype', 'name_en' => 'Unit Type']);
        $villa = $types->children()->create(['slug' => 'villa', 'name_en' => 'Villa']);
        Unit::factory()->count(3)->create(['property_type_id' => $villa->id]);

        $projects = (fn () => $this->getData())->call(new ProjectsByCityChart);
        $this->assertSame(['Cairo', __('admin.dashboard.charts.no_city')], $projects['labels']);
        $this->assertSame([2, 1], $projects['datasets'][0]['data']);

        $units = (fn () => $this->getData())->call(new UnitsByTypeChart);
        $this->assertSame(['Villa'], $units['labels']);
        $this->assertSame([3], $units['datasets'][0]['data']);
    }

    public function test_charts_and_settings_come_back_right_from_the_real_cache(): void
    {
        // Tests use an in-memory cache; the server's database cache doesn't restore objects
        // (cache.serializable_classes = false), so cached values must be plain.
        config(['cache.default' => 'database']);
        $this->actingAs($this->createAdmin());
        Project::factory()->create();

        $chart = fn () => (fn () => $this->getData())->call(new ProjectsByCityChart);
        $first = $chart();
        $this->assertSame($first, $chart());
        $this->assertSame([__('admin.dashboard.charts.no_city')], $chart()['labels']);

        app()->forgetInstance(SiteSettings::class);
        app(SiteSettings::class);
        app()->forgetInstance(SiteSettings::class);
        $this->assertFalse(app(SiteSettings::class)->is_live);
    }

    public function test_the_calendar_shows_the_bookings_of_the_dates_on_screen(): void
    {
        $this->actingAs($this->createAdmin());
        $inView = Meeting::factory()->create(['meeting_at' => '2026-10-15 11:00', 'status' => Meeting::CONFIRMED]);
        Meeting::factory()->create(['meeting_at' => '2026-12-01 11:00']);

        $events = Livewire::test(MeetingsCalendarWidget::class)->instance()->events('2026-09-28', '2026-11-09');

        $this->assertCount(1, $events);
        $this->assertSame([$inView->id, MeetingResource::getUrl('view', ['record' => $inView]), '#10b981'], [$events[0]['id'], $events[0]['url'], $events[0]['color']]);
        // A huge range (or a reversed one) returns nothing instead of loading every booking.
        $this->assertSame([], Livewire::test(MeetingsCalendarWidget::class)->instance()->events('2020-01-01', '2030-01-01'));

        $this->actingAs($this->createUserWithPermissions(['projects' => true]));
        $this->assertFalse(MeetingsCalendarWidget::canView());
    }
}
