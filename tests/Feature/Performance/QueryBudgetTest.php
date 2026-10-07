<?php

namespace Tests\Feature\Performance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Once;
use Modules\Careers\Filament\Resources\ApplicantResource;
use Modules\Careers\Filament\Resources\CareerResource;
use Modules\Careers\Models\Applicant;
use Modules\Careers\Models\Career;
use Modules\Developers\Filament\Resources\DeveloperResource;
use Modules\Developers\Models\Developer;
use Modules\Galleries\Filament\Resources\GalleryResource;
use Modules\Galleries\Models\Gallery;
use Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages\AboutUsSettingsPage;
use Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages\HomeSettingsPage;
use Modules\Inquiry\Filament\Resources\InquiryResource;
use Modules\Inquiry\Filament\Resources\MeetingResource;
use Modules\Inquiry\Models\Inquiry;
use Modules\Inquiry\Models\Meeting;
use Modules\Locations\Filament\Resources\LocationResource;
use Modules\Locations\Models\Location;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Lookups\Models\Lookup;
use Modules\Projects\Filament\Resources\ProjectResource;
use Modules\Projects\Models\Project;
use Modules\Sliders\Filament\Resources\SliderResource;
use Modules\Sliders\Models\Slider;
use Modules\Sliders\Models\SliderItem;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource;
use Modules\UnitRequests\Models\UnitRequest;
use Modules\Units\Filament\Resources\UnitResource;
use Modules\Units\Models\Unit;
use Modules\Users\Filament\Resources\RoleResource;
use Modules\Users\Filament\Resources\UserResource;
use Modules\Users\Models\User;
use Tests\TestCase;

/**
 * A fixed maximum number of queries per admin page (page work only: the session, the logged-in user and the
 * sidebar are the same on every page). If a change adds queries, this fails; raise a budget only on purpose.
 */
class QueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    private const BUDGETS = [
        'dashboard' => 0,
        'users.index' => 3,    // count, page, roles (shared by the role column, filter and form)
        'users.view' => 2,     // user, roles
        'users.edit' => 2,     // user, roles
        'users.create' => 1,   // roles
        'lookups.index' => 1,  // every type card with its number of values, in one query
        'lookups.values' => 6, // the type, its ancestors (breadcrumbs, tree depth), the values (+ protected flag), the count, values in use (2: used ids, their tree positions)
        'roles.index' => 4,    // count, page, roles given to users (2: used ids, their tree positions)
        'roles.edit' => 1,     // role
        'locations.index' => 4,  // count, page, featured images, location chain (one query for the 5 level filters)
        'locations.create' => 1, // location chain
        'locations.edit' => 3,   // location, its image, location chain
        'developers.index' => 4,  // count, page, logos + featured images (one query), location chain (filters + column)
        'developers.edit' => 4,   // developer, media for each image field (the plugin reloads it per field), location chain
        'projects.index' => 6,    // count, page, logos, developers, location chain (filters + country), lookup values (currency)
        'galleries.index' => 5,   // count, page, featured images, projects (column), projects (filter options)
        'galleries.edit' => 8,    // gallery, media per upload field (5), projects (options + current)
        'units.index' => 6,       // count, page, images, projects (column), location chain, lookup values
        'units.edit' => 11,       // unit, picks, media per upload field (3), projects (options + current), the project's choices, galleries, location chain, lookup values
        'projects.edit' => 11,    // project, picked values, media per image field (5: the plugin reloads it per field), developers (options + current), location chain, lookup values
        'sliders.index' => 2,     // page (not paginated: a fixed handful of blocks), count
        'settings.home' => 1,     // the page's settings (one query per group)
        'settings.about_us' => 1, // the page's settings
        'sliders.view' => 1,      // slider (the slides tab loads on its own)
        'contact_us.index' => 2,  // count, page
        'contact_us.view' => 1,   // message
        'meetings.index' => 2,    // count, page
        'meetings.view' => 1,     // booking
        'unit_requests.index' => 3,   // count, page, images
        'unit_requests.edit' => 4,
        'careers.index' => 2,         // count, page
        'careers.edit' => 3,
        'applicants.index' => 5,      // count, page, their jobs, CVs, jobs (filter options)
        'applicants.edit' => 4,       // application, its CV, jobs (options + current)          // job, its benefits, choices (location chain + lookup values)    // request, its image, picked values, choices (location chain + lookup values)
    ];

    public function test_every_admin_page_stays_within_its_query_budget(): void
    {
        $this->actingAs($this->createAdmin());
        $user = User::factory()->create(['role_id' => $this->role('user_roles_client')->id]);
        $lookup = Lookup::create(['name_en' => 'Views', 'slug' => 'views']);
        $lookup->children()->create(['name_en' => 'Sea', 'slug' => 'sea']);
        $location = Location::factory()->create();
        Lookup::create(['parent_id' => $this->role(Lookup::ROLES_SLUG)->id, 'name_en' => 'Sales', 'slug' => 'user_roles_sales']);
        $developer = Developer::factory()->create();
        $project = Project::factory()->create(['developer_id' => $developer->id]);
        $gallery = Gallery::factory()->create(['project_id' => $project->id]);
        $unit = Unit::factory()->create(['project_id' => $project->id, 'price' => 1000000]);
        $hero = Slider::factory()->hero()->create();
        $inquiry = Inquiry::factory()->create();
        $meeting = Meeting::factory()->create();
        $unitRequest = UnitRequest::factory()->create();
        $career = Career::factory()->create();
        $applicant = Applicant::factory()->create(['career_id' => $career->id]);
        SliderItem::factory()->create(['slider_id' => $hero->id, 'project_id' => $project->id]);

        $pages = [
            'dashboard' => '/admin',
            'users.index' => UserResource::getUrl('index'),
            'users.view' => UserResource::getUrl('view', ['record' => $user]),
            'users.edit' => UserResource::getUrl('edit', ['record' => $user]),
            'users.create' => UserResource::getUrl('create'),
            'lookups.index' => LookupResource::getUrl('index'),
            'lookups.values' => LookupResource::getUrl('values', ['record' => $lookup]),
            'roles.index' => RoleResource::getUrl('index'),
            'roles.edit' => RoleResource::getUrl('edit', ['record' => $this->role('user_roles_editor')]),
            'locations.index' => LocationResource::getUrl('index'),
            'locations.create' => LocationResource::getUrl('create'),
            'locations.edit' => LocationResource::getUrl('edit', ['record' => $location]),
            'developers.index' => DeveloperResource::getUrl('index'),
            'developers.edit' => DeveloperResource::getUrl('edit', ['record' => $developer]),
            'galleries.index' => GalleryResource::getUrl('index'),
            'galleries.edit' => GalleryResource::getUrl('edit', ['record' => $gallery]),
            'units.index' => UnitResource::getUrl('index'),
            'units.edit' => UnitResource::getUrl('edit', ['record' => $unit]),
            'projects.index' => ProjectResource::getUrl('index'),
            'projects.edit' => ProjectResource::getUrl('edit', ['record' => $project]),
            'sliders.index' => SliderResource::getUrl('index'),
            'sliders.view' => SliderResource::getUrl('view', ['record' => $hero]),
            'settings.home' => HomeSettingsPage::getUrl(),
            'contact_us.index' => InquiryResource::getUrl('index'),
            'contact_us.view' => InquiryResource::getUrl('view', ['record' => $inquiry]),
            'meetings.index' => MeetingResource::getUrl('index'),
            'meetings.view' => MeetingResource::getUrl('view', ['record' => $meeting]),
            'unit_requests.index' => UnitRequestResource::getUrl('index'),
            'unit_requests.edit' => UnitRequestResource::getUrl('edit', ['record' => $unitRequest]),
            'careers.index' => CareerResource::getUrl('index'),
            'careers.edit' => CareerResource::getUrl('edit', ['record' => $career]),
            'applicants.index' => ApplicantResource::getUrl('index'),
            'applicants.edit' => ApplicantResource::getUrl('edit', ['record' => $applicant]),
            'settings.about_us' => AboutUsSettingsPage::getUrl(),
        ];

        $actual = [];

        foreach ($pages as $name => $url) {
            // Warm up (cached sidebar counts, translations), then measure a normal request: cache filled,
            // per-request memos (e.g. the roles list) empty.
            Cache::flush();
            Once::flush();
            $this->get($url)->assertOk();
            Once::flush();
            $actual[$name] = $this->countQueries(fn () => $this->get($url)->assertOk());
        }

        foreach (self::BUDGETS as $name => $budget) {
            $this->assertLessThanOrEqual($budget, $actual[$name], "{$name} runs {$actual[$name]} queries (budget {$budget}). All: ".json_encode($actual));
        }
    }
}
