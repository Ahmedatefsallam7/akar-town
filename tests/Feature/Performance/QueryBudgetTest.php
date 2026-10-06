<?php

namespace Tests\Feature\Performance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Once;
use Modules\Developers\Filament\Resources\DeveloperResource;
use Modules\Developers\Models\Developer;
use Modules\Locations\Filament\Resources\LocationResource;
use Modules\Locations\Models\Location;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Lookups\Models\Lookup;
use Modules\Projects\Filament\Resources\ProjectResource;
use Modules\Projects\Models\Project;
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
        'projects.edit' => 11,    // project, picked values, media per image field (5: the plugin reloads it per field), developers (options + current), location chain, lookup values
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
            'projects.index' => ProjectResource::getUrl('index'),
            'projects.edit' => ProjectResource::getUrl('edit', ['record' => $project]),
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
