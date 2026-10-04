<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Database\Seeders\AdminSeeder;
use Modules\Users\Filament\Resources\RoleResource;
use Modules\Users\Models\User;
use Tests\TestCase;

class UsersSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_seeder_creates_the_three_roles_under_one_category(): void
    {
        $this->seedRoles();
        $this->seedRoles();

        $this->assertSame(1, Lookup::where('slug', Lookup::ROLES_SLUG)->count());
        $this->assertSame(3, Lookup::roles()->count());
        $this->assertFalse($this->role(User::ADMIN_ROLE)->is_deletable);
    }

    public function test_new_admin_role_gets_every_module(): void
    {
        $this->seedRoles();
        $permissions = $this->role(User::ADMIN_ROLE)->getAttribute('attributes')['permissions'];

        foreach (array_merge(...array_values(RoleResource::getAvailableModules())) as $module) {
            $this->assertTrue($permissions[$module], "Admin is missing {$module}");
        }
    }

    public function test_roles_seeder_never_overwrites_saved_admin_permissions(): void
    {
        $this->seedRoles();
        $this->role(User::ADMIN_ROLE)->update(['attributes' => ['permissions' => ['users' => true, 'roles' => true, 'blogs' => false]]]);

        $this->seedRoles();

        $this->assertFalse($this->role(User::ADMIN_ROLE)->getAttribute('attributes')['permissions']['blogs']);
    }

    public function test_admin_seeder_creates_the_local_admin_login(): void
    {
        $this->app['env'] = 'local';
        $this->seedRoles();

        $this->seed(AdminSeeder::class);
        $this->seed(AdminSeeder::class);

        $admin = User::where('email', 'ahmed@gmail.com')->sole();
        $this->assertSame('Ahmed Atef', $admin->name);
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(Hash::check('123456789', $admin->password));
    }

    public function test_admin_seeder_is_skipped_outside_local(): void
    {
        $this->seedRoles();
        $this->app['env'] = 'production';

        // Production asks for confirmation, so pass --force like a deploy would.
        $this->artisan('db:seed', ['--class' => AdminSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'ahmed@gmail.com']);
    }

    public function test_database_seeder_seeds_lookups_and_roles(): void
    {
        $this->seed();

        $this->assertTrue(Lookup::where('slug', 'amenities')->exists());
        $this->assertSame(3, Lookup::roles()->count());
    }
}
