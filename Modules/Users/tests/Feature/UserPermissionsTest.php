<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Enums\PermissionAction;
use Modules\Users\Models\User;
use Tests\TestCase;

/**
 * User::hasPermission() rules. Feature tests, because the Permission Modules settings are read from the database.
 */
class UserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(?array $role): User
    {
        $user = new User;
        $user->setRelation('role', $role ? new Lookup($role) : null);

        return $user;
    }

    public function test_user_without_a_role_has_no_permissions(): void
    {
        $this->assertFalse($this->userWithRole(null)->hasPermission('lookups'));
    }

    public function test_permissions_follow_the_actions_on_the_role(): void
    {
        $user = $this->userWithRole([
            'slug' => 'user_roles_editor',
            'is_active' => true,
            'attributes' => ['permissions' => ['lookups' => ['view', 'edit'], 'users' => []]],
        ]);

        $this->assertTrue($user->hasPermission('lookups'));
        $this->assertTrue($user->hasPermission('lookups', PermissionAction::Edit));
        $this->assertFalse($user->hasPermission('lookups', PermissionAction::Create));
        $this->assertFalse($user->hasPermission('lookups', PermissionAction::Delete));
        $this->assertFalse($user->hasPermission('lookups', PermissionAction::Bulk));
        $this->assertFalse($user->hasPermission('users'));
        $this->assertFalse($user->hasPermission('blogs'));
    }

    public function test_every_action_also_needs_view(): void
    {
        $user = $this->userWithRole([
            'slug' => 'user_roles_editor',
            'is_active' => true,
            'attributes' => ['permissions' => ['lookups' => ['edit', 'delete']]],
        ]);

        $this->assertFalse($user->hasPermission('lookups', PermissionAction::Edit));
        $this->assertFalse($user->hasPermission('lookups', PermissionAction::Delete));
    }

    public function test_old_on_off_format_gives_no_access(): void
    {
        $user = $this->userWithRole([
            'slug' => 'user_roles_editor',
            'is_active' => true,
            'attributes' => ['permissions' => ['lookups' => true]],
        ]);

        $this->assertFalse($user->hasPermission('lookups'));
    }

    public function test_inactive_role_has_no_permissions(): void
    {
        $user = $this->userWithRole([
            'slug' => 'user_roles_editor',
            'is_active' => false,
            'attributes' => ['permissions' => ['lookups' => ['view']]],
        ]);

        $this->assertFalse($user->hasPermission('lookups'));
    }

    public function test_role_without_stored_permissions_has_no_access(): void
    {
        $this->assertFalse($this->userWithRole(['slug' => 'user_roles_editor', 'is_active' => true])->hasPermission('lookups'));
    }

    public function test_admin_follows_its_checkboxes_but_always_keeps_the_locked_modules(): void
    {
        $admin = $this->userWithRole([
            'slug' => User::ADMIN_ROLE,
            'is_active' => false,
            'attributes' => ['permissions' => ['lookups' => ['view']]],
        ]);

        foreach (User::ADMIN_LOCKED_MODULES as $module) {
            foreach (PermissionAction::cases() as $action) {
                $this->assertTrue($admin->hasPermission($module, $action));
            }
        }

        // Inactive admin role: unlocked modules follow the normal rules.
        $this->assertFalse($admin->hasPermission('lookups'));
    }

    private function editor(array $rolePermissions, ?array $custom, bool $active = true): User
    {
        $user = $this->userWithRole(['slug' => 'user_roles_editor', 'is_active' => $active, 'attributes' => ['permissions' => $rolePermissions]]);
        $user->custom_permissions = $custom;

        return $user;
    }

    public function test_user_follows_the_role_without_custom_permissions(): void
    {
        $user = $this->editor(['lookups' => ['view', 'edit']], null);

        $this->assertFalse($user->usesCustomPermissions());
        $this->assertSame(['view', 'edit'], $user->effectiveActions('lookups'));
    }

    public function test_custom_permissions_replace_the_role(): void
    {
        $user = $this->editor(['lookups' => ['view', 'edit', 'delete'], 'users' => ['view']], ['lookups' => ['view']]);

        $this->assertTrue($user->usesCustomPermissions());
        $this->assertTrue($user->hasPermission('lookups'));
        $this->assertFalse($user->hasPermission('lookups', PermissionAction::Edit));
        // Not in the custom list = no access, even if the role has it.
        $this->assertFalse($user->hasPermission('users'));
    }

    public function test_custom_permissions_can_give_more_than_the_role(): void
    {
        $user = $this->editor([], ['lookups' => ['view', 'edit']]);

        $this->assertTrue($user->hasPermission('lookups', PermissionAction::Edit));
    }

    public function test_empty_custom_permissions_mean_no_access(): void
    {
        $user = $this->editor(['lookups' => ['view']], []);

        $this->assertTrue($user->usesCustomPermissions());
        $this->assertFalse($user->hasPermission('lookups'));
    }

    public function test_custom_permissions_work_without_a_role_or_with_an_inactive_role(): void
    {
        $noRole = $this->userWithRole(null);
        $noRole->custom_permissions = ['lookups' => ['view']];

        $this->assertTrue($noRole->hasPermission('lookups'));
        $this->assertTrue($this->editor([], ['lookups' => ['view']], active: false)->hasPermission('lookups'));
    }

    public function test_custom_actions_still_need_view(): void
    {
        $this->assertFalse($this->editor([], ['lookups' => ['edit']])->hasPermission('lookups', PermissionAction::Edit));
    }

    public function test_admin_keeps_the_locked_modules_even_with_custom_permissions(): void
    {
        $admin = $this->userWithRole(['slug' => User::ADMIN_ROLE, 'is_active' => true, 'attributes' => ['permissions' => []]]);
        $admin->custom_permissions = [];

        $this->assertTrue($admin->hasPermission('users', PermissionAction::Delete));
        $this->assertFalse($admin->hasPermission('lookups'));
    }
}
