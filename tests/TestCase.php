<?php

namespace Tests;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Database\Seeders\RolesTableSeeder;
use Modules\Users\Models\User;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
     * A user on a new role that only allows the given modules, e.g. ['lookups' => true].
     */
    protected function createUserWithPermissions(array $permissions, array $attributes = []): User
    {
        $this->seedRoles();

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
}
