<?php

namespace Tests;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Database\Seeders\RolesTableSeeder;
use Modules\Users\Models\User;
use Modules\Users\Support\PermissionModules;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // No test may reach a real outside service (e.g. the CRM): an HTTP call that isn't faked fails the test.
        Http::preventStrayRequests();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /**
     * Seed the user_roles category with the Admin, Editor and Client roles.
     */
    protected function seedRoles(): void
    {
        $this->seed(RolesTableSeeder::class);
    }

    protected function role(string $slug): Lookup
    {
        return Lookup::where('slug', $slug)->firstOrFail();
    }

    /**
     * A user on the seeded Admin role (every module allowed).
     */
    protected function createAdmin(array $attributes = []): User
    {
        $this->seedRoles();

        return User::factory()->create($attributes + ['role_id' => $this->role(User::ADMIN_ROLE)->id]);
    }

    /**
     * A user on a new role with the given permissions: a list of actions per module, or true for every action.
     * e.g. ['lookups' => true, 'users' => ['view', 'edit']].
     */
    protected function createUserWithPermissions(array $permissions, array $attributes = []): User
    {
        $this->seedRoles();

        $permissions = collect($permissions)
            ->map(fn (array|bool $actions, string $module) => $actions === true ? PermissionModules::actionsFor($module) : ($actions ?: []))
            ->all();

        $role = Lookup::create([
            'parent_id' => $this->role(Lookup::ROLES_SLUG)->id,
            'name_en' => 'Limited',
            'name_ar' => 'محدود',
            'slug' => 'user_roles_limited_'.uniqid(),
            'is_active' => true,
            'attributes' => ['permissions' => $permissions],
        ]);

        return User::factory()->create($attributes + ['role_id' => $role->id]);
    }

    /**
     * Number of database queries the callback runs.
     */
    protected function countQueries(callable $callback): int
    {
        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });

        $callback();

        return $count;
    }
}
