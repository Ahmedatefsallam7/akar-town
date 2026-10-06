<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Developers\Models\Developer;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Database\Seeders\AdminSeeder;
use Modules\Users\Models\User;
use Modules\Users\Support\PermissionModules;
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

        foreach (array_keys(PermissionModules::all()) as $module) {
            $this->assertSame(PermissionModules::actionsFor($module), $permissions[$module], "Admin is missing actions on {$module}");
        }
    }

    public function test_roles_seeder_never_overwrites_saved_admin_permissions(): void
    {
        $this->seedRoles();
        $this->role(User::ADMIN_ROLE)->update(['attributes' => ['permissions' => ['users' => ['view'], 'roles' => ['view'], 'lookups' => []]]]);

        $this->seedRoles();

        $this->assertSame([], $this->role(User::ADMIN_ROLE)->getAttribute('attributes')['permissions']['lookups']);
    }

    public function test_editor_and_client_get_their_badge_colors(): void
    {
        $this->seedRoles();

        $this->assertSame('info', $this->role('user_roles_editor')->getAttribute('attributes')['color']);
        $this->assertSame('success', $this->role('user_roles_client')->getAttribute('attributes')['color']);
    }

    public function test_running_again_after_renaming_roles_creates_no_duplicates_and_keeps_the_edits(): void
    {
        $this->seedRoles();
        $this->role('user_roles_editor')->update(['name_en' => 'Content Editor', 'slug' => 'user_roles_content_editor']);
        $this->role(User::ADMIN_ROLE)->update(['name_en' => 'Super Admin']);

        $this->seedRoles();

        $this->assertSame(3, Lookup::roles()->count());
        $this->assertFalse(Lookup::where('slug', 'user_roles_editor')->exists());
        $this->assertSame('Super Admin', $this->role(User::ADMIN_ROLE)->name_en);
    }

    public function test_deleted_editor_is_not_brought_back(): void
    {
        $this->seedRoles();
        $this->role('user_roles_editor')->delete();

        $this->seedRoles();

        $this->assertSame(2, Lookup::roles()->count());
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

    public function test_database_seeder_seeds_lookups_roles_location_pages_and_developers(): void
    {
        $this->seed();

        $this->assertTrue(Lookup::where('slug', 'amenities')->exists());
        $this->assertSame(3, Lookup::roles()->count());
        $this->assertTrue(Location::where('slug', 'new-cairo')->exists());
        $this->assertTrue(Developer::where('slug', 'mountain-view')->exists());
    }
}
