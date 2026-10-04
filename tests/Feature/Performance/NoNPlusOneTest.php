<?php

namespace Tests\Feature\Performance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Filament\Resources\RoleResource;
use Modules\Users\Filament\Resources\UserResource;
use Modules\Users\Models\User;
use Tests\TestCase;

/**
 * List pages must not run a query per row (N+1): the same number of queries for 1 row and for a full page.
 */
class NoNPlusOneTest extends TestCase
{
    use RefreshDatabase;

    private function queriesFor(string $url): int
    {
        Cache::flush();

        return $this->countQueries(fn () => $this->get($url)->assertOk());
    }

    private function assertNoNPlusOne(string $url, callable $addRows): void
    {
        $this->get($url); // warm up (session, translations)
        $few = $this->queriesFor($url);

        $addRows();
        $many = $this->queriesFor($url);

        $this->assertSame($few, $many, "{$url}: {$few} queries with few rows, {$many} with a full page (N+1).");
    }

    public function test_users_list(): void
    {
        $this->actingAs($this->createAdmin());

        $this->assertNoNPlusOne(UserResource::getUrl('index'), fn () => User::factory()->count(9)->create([
            'role_id' => $this->role('user_roles_client')->id,
        ]));
    }

    public function test_users_list_for_a_non_admin_manager(): void
    {
        $this->actingAs($this->createUserWithPermissions(['users' => true]));

        $this->assertNoNPlusOne(UserResource::getUrl('index'), fn () => User::factory()->count(9)->create([
            'role_id' => $this->role('user_roles_client')->id,
        ]));
    }

    public function test_lookups_list(): void
    {
        $this->actingAs($this->createAdmin());
        $category = Lookup::create(['name_en' => 'Views', 'slug' => 'views']);
        $category->children()->create(['name_en' => 'View 0', 'slug' => 'view_0']);

        $this->assertNoNPlusOne(LookupResource::getUrl('index'), function () use ($category) {
            foreach (range(1, 8) as $i) {
                $category->children()->create(['name_en' => "View {$i}", 'slug' => "view_{$i}"]);
            }
        });
    }

    public function test_roles_list(): void
    {
        $this->actingAs($this->createAdmin());

        $this->assertNoNPlusOne(RoleResource::getUrl('index'), function () {
            foreach (range(1, 7) as $i) {
                Lookup::create(['parent_id' => $this->role(Lookup::ROLES_SLUG)->id, 'name_en' => "Role {$i}", 'slug' => "user_roles_r{$i}"]);
            }
        });
    }

    public function test_users_list_never_loads_every_user(): void
    {
        $this->actingAs($this->createAdmin());
        User::factory()->count(30)->create();

        DB::enableQueryLog();
        $this->get(UserResource::getUrl('index'))->assertOk();

        // Only a page of users (limit) or a count, never "select * from users" for the whole table.
        $unbounded = collect(DB::getQueryLog())->pluck('query')
            ->filter(fn (string $sql) => str_starts_with($sql, 'select * from "users"') && ! str_contains($sql, 'limit'));

        $this->assertEmpty($unbounded->all(), 'The users list loaded the whole table: '.$unbounded->first());
    }
}
