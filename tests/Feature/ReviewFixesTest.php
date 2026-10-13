<?php

namespace Tests\Feature;

use App\Jobs\SendLeadToCrm;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Modules\Careers\Mail\ApplicationStatusChanged;
use Modules\Careers\Models\Applicant;
use Modules\Developers\Models\Developer;
use Modules\Galleries\Filament\Resources\GalleryResource\Pages\EditGallery;
use Modules\Galleries\Models\Gallery;
use Modules\GeneralSettings\Settings\HomeSettings;
use Modules\Inquiry\Filament\Widgets\MeetingsCalendarWidget;
use Modules\Inquiry\Models\Inquiry;
use Modules\Inquiry\Models\Meeting;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Lookups\Models\Lookup;
use Modules\PossibleLeads\Models\PossibleLead;
use Modules\PossibleLeads\Support\Visitor;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\ViewProject;
use Modules\Projects\Filament\Resources\ProjectResource\RelationManagers\SlidersRelationManager;
use Modules\Projects\Models\Project;
use Modules\Sliders\Filament\Resources\SliderResource\Pages\EditSlider;
use Modules\Sliders\Models\Slider;
use Modules\Units\Models\Unit;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Tests\TestCase;

/**
 * Problems found in the full review of 2026-10-07, each kept fixed by a test.
 */
class ReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_gallery_used_by_properties_keeps_its_project(): void
    {
        $this->actingAs($this->createAdmin());
        $project = Project::factory()->create();
        $used = Gallery::factory()->create(['project_id' => $project->id]);
        Unit::factory()->create(['project_id' => $project->id, 'gallery_id' => $used->id]);
        $free = Gallery::factory()->create(['project_id' => $project->id]);

        Livewire::test(EditGallery::class, ['record' => $used->getRouteKey()])
            ->assertFormFieldIsDisabled('project_id')
            ->fillForm(['project_id' => Project::factory()->create()->id])
            ->call('save');
        $this->assertSame($project->id, $used->fresh()->project_id);

        Livewire::test(EditGallery::class, ['record' => $free->getRouteKey()])->assertFormFieldIsEnabled('project_id');
    }

    public function test_long_names_get_a_slug_that_fits_with_its_number(): void
    {
        $name = str_repeat('a', 191);

        $first = Developer::factory()->create(['name_en' => $name, 'slug' => null]);
        $second = Developer::factory()->create(['name_en' => $name, 'slug' => null]);

        $this->assertLessThanOrEqual(191, strlen($second->slug));
        $this->assertNotSame($first->slug, $second->slug);
    }

    public function test_the_hero_slider_stays_the_only_hero(): void
    {
        $this->actingAs($this->createAdmin());
        $hero = Slider::factory()->hero()->create();
        $body = Slider::factory()->create();

        Livewire::test(EditSlider::class, ['record' => $hero->getRouteKey()])->assertFormFieldIsDisabled('placement');

        Livewire::test(EditSlider::class, ['record' => $body->getRouteKey()])
            ->fillForm(['placement' => Slider::HOME_HERO])
            ->call('save')
            ->assertHasFormErrors(['placement']);
        $this->assertSame(1, Slider::where('placement', Slider::HOME_HERO)->count());
    }

    public function test_a_trashed_project_cannot_be_added_to_the_hero(): void
    {
        $this->actingAs($this->createAdmin());
        Slider::factory()->hero()->create();
        $project = Project::factory()->create();
        $project->delete();

        Livewire::test(SlidersRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
            ->assertTableActionHidden('addToHero');
    }

    public function test_lookup_values_cannot_be_opened_below_their_types_depth(): void
    {
        $this->actingAs($this->createAdmin());
        $type = Lookup::create(['slug' => 'amenities', 'name_en' => 'Amenities']);
        $pool = $type->children()->create(['slug' => 'pool', 'name_en' => 'Pool']);

        $this->get(LookupResource::getUrl('values', ['record' => $type]))->assertOk();
        // A flat list has no values under a value.
        $this->get(LookupResource::getUrl('values', ['record' => $pool]))->assertNotFound();
    }

    public function test_the_status_email_says_the_status_it_was_sent_for(): void
    {
        $applicant = Applicant::factory()->create(['status' => Applicant::REJECTED]);

        // Accepted first, then corrected to Rejected before the queue ran: the first mail still says Accepted.
        (new ApplicationStatusChanged($applicant, Applicant::ACCEPTED))->assertSeeInHtml(__('admin.applicants.status_options.accepted', [], 'en'));
    }

    public function test_a_lead_deleted_before_its_crm_job_runs_is_not_sent(): void
    {
        config(['crm.token' => 't', 'crm.base_url' => 'https://akartown.8xcrm.com', 'crm.forms.contact_us' => '1']);
        Http::fake();
        Queue::fake();
        $inquiry = Inquiry::factory()->create();
        $inquiry->delete();

        (new SendLeadToCrm($inquiry))->handle();

        Http::assertNothingSent();
        $this->assertTrue((new SendLeadToCrm($inquiry))->deleteWhenMissingModels);
    }

    public function test_the_calendar_shows_meetings_at_their_booked_time_whatever_the_browser_timezone(): void
    {
        $this->actingAs($this->createAdmin());
        $meeting = Meeting::factory()->create(['meeting_at' => '2026-10-15 14:00:00']);

        // The browser sends its own offset; the booked time is what counts.
        $events = Livewire::test(MeetingsCalendarWidget::class)->instance()->events('2026-10-01T00:00:00+03:00', '2026-11-01T00:00:00+03:00');

        $this->assertSame('2026-10-15T14:00:00', $events[0]['start']);
        $this->assertSame([], Livewire::test(MeetingsCalendarWidget::class)->instance()->events('not a date', 'nope'));
        $this->assertNotNull($meeting);
    }

    public function test_a_logged_in_visitors_clicks_get_their_account_when_they_send_a_lead(): void
    {
        $visitor = Visitor::newId();
        $click = PossibleLead::factory()->create(['visitor_id' => $visitor, 'created_at' => now()->subHour()]);
        $user = WebsiteUser::factory()->create();
        Queue::fake();

        Inquiry::factory()->create(['visitor_id' => $visitor, 'website_user_id' => $user->id]);

        $this->assertSame($user->id, $click->fresh()->website_user_id);
        $this->assertTrue($click->fresh()->isConverted());
    }

    public function test_the_queue_lock_is_short_and_old_failed_jobs_are_pruned(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('queue:prune-failed --hours=168');

        $events = collect(app(Schedule::class)->events());
        $worker = $events->first(fn ($event) => str_contains($event->command, 'queue:work'));
        $this->assertSame(5, $worker->expiresAt);
    }

    public function test_tests_never_reach_the_real_crm(): void
    {
        // The real CRM settings in .env are blanked for tests (phpunit.xml)...
        $this->assertNull(config('crm.base_url'));
        $this->assertSame('', (string) config('crm.token'));

        // ...and even with settings, a call that isn't faked is refused.
        config(['crm.token' => 't', 'crm.base_url' => 'https://testing.8xcrm.com', 'crm.forms.contact_us' => '1']);
        $this->expectException(StrayRequestException::class);
        Inquiry::factory()->create();
    }

    public function test_dates_are_in_cairo_time(): void
    {
        $this->assertSame('Africa/Cairo', config('app.timezone'));
    }

    public function test_settings_cache_is_cleared_after_migrating(): void
    {
        config(['cache.default' => 'database']);
        app(HomeSettings::class)->why_choose_subtitle_en;
        $cached = fn () => DB::table('cache')->where('key', 'like', '%settings%')->count();
        $this->assertGreaterThan(0, $cached());

        event(new MigrationsEnded('up'));

        $this->assertSame(0, $cached());
    }
}
