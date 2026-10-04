<?php

namespace Modules\Users\Tests\Unit;

use Filament\Facades\Filament;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    private function userWithRole(?array $role): User
    {
        $user = new User;
        $user->setRelation('role', $role ? new Lookup($role) : null);

        return $user;
    }

    public function test_full_name_is_split_into_first_and_last_name(): void
    {
        $user = new User(['name' => 'Ahmed Atef Sallam']);

        $this->assertSame('Ahmed', $user->first_name);
        $this->assertSame('Atef Sallam', $user->last_name);
        $this->assertSame('Ahmed Atef Sallam', $user->name);
    }

    public function test_single_word_name_leaves_last_name_empty(): void
    {
        $user = new User(['name' => 'Ahmed']);

        $this->assertSame('Ahmed', $user->first_name);
        $this->assertSame('', $user->last_name);
    }

    public function test_filament_name_falls_back_to_username_then_email(): void
    {
        $this->assertSame('Ahmed Atef', (new User(['name' => 'Ahmed Atef', 'email' => 'a@b.c']))->getFilamentName());
        $this->assertSame('ahmed', (new User(['username' => 'ahmed', 'email' => 'a@b.c']))->getFilamentName());
        $this->assertSame('a@b.c', (new User(['email' => 'a@b.c']))->getFilamentName());
    }

    public function test_role_color_and_admin_check(): void
    {
        $this->assertSame('danger', $this->userWithRole(['slug' => User::ADMIN_ROLE])->roleColor());
        $this->assertSame('info', $this->userWithRole(['slug' => 'user_roles_editor'])->roleColor());
        $this->assertSame('success', $this->userWithRole(['slug' => 'user_roles_client'])->roleColor());
        $this->assertSame('gray', $this->userWithRole(null)->roleColor());

        $this->assertTrue($this->userWithRole(['slug' => User::ADMIN_ROLE])->isAdmin());
        $this->assertFalse($this->userWithRole(['slug' => 'user_roles_editor'])->isAdmin());
        $this->assertFalse($this->userWithRole(null)->isAdmin());
    }

    public function test_user_without_a_role_has_no_permissions(): void
    {
        $this->assertFalse($this->userWithRole(null)->hasModulePermission('lookups'));
    }

    public function test_permissions_follow_the_role_checkboxes(): void
    {
        $user = $this->userWithRole([
            'slug' => 'user_roles_editor',
            'is_active' => true,
            'attributes' => ['permissions' => ['lookups' => true, 'users' => false]],
        ]);

        $this->assertTrue($user->hasModulePermission('lookups'));
        $this->assertFalse($user->hasModulePermission('users'));
        $this->assertFalse($user->hasModulePermission('blogs'));
    }

    public function test_inactive_role_has_no_permissions(): void
    {
        $user = $this->userWithRole([
            'slug' => 'user_roles_editor',
            'is_active' => false,
            'attributes' => ['permissions' => ['lookups' => true]],
        ]);

        $this->assertFalse($user->hasModulePermission('lookups'));
    }

    public function test_role_without_stored_permissions_has_no_access(): void
    {
        $this->assertFalse($this->userWithRole(['slug' => 'user_roles_editor', 'is_active' => true])->hasModulePermission('lookups'));
    }

    public function test_admin_follows_its_checkboxes_but_always_keeps_the_locked_modules(): void
    {
        $admin = $this->userWithRole([
            'slug' => User::ADMIN_ROLE,
            'is_active' => false,
            'attributes' => ['permissions' => ['lookups' => true]],
        ]);

        foreach (User::ADMIN_LOCKED_MODULES as $module) {
            $this->assertTrue($admin->hasModulePermission($module));
        }

        // Inactive admin role: unlocked modules follow the normal rules.
        $this->assertFalse($admin->hasModulePermission('lookups'));
    }

    public function test_suspended_user_cannot_access_the_panel(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue((new User(['is_suspended' => false]))->canAccessPanel($panel));
        $this->assertFalse((new User(['is_suspended' => true]))->canAccessPanel($panel));
    }
}
