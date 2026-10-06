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
        'lookups.values' => 4, // the type, its ancestors (breadcrumbs, tree depth), the values (+ delete flag), the count
        'roles.index' => 2,    // count, page
        'roles.edit' => 1,     // role
        'locations.index' => 4,  // count, page, featured images, location chain (one query for the 5 level filters)
        'locations.create' => 1, // location chain
        'locations.edit' => 3,   // location, its image, location chain
        'developers.index' => 4,  // count, page, logos + featured images (one query), location chain (filters + column)
        'developers.edit' => 4,   // developer, media for each image field (the plugin reloads it per field), location chain
    ];

    public function test_every_admin_page_stays_within_its_query_budget(): void
    {
        $this->actingAs($this->createAdmin());
        $user = User::factory()->create(['role_id' => $this->role('user_roles_client')->id]);
        $lookup = Lookup::create(['name_en' => 'Views', 'slug' => 'views']);
        $lookup->children()->create(['name_en' => 'Sea', 'slug' => 'sea']);
        $location = Location::factory()->create();
        $developer = Developer::factory()->create();

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
