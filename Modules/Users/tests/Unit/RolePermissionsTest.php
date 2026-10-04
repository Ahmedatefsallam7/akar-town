<?php

namespace Modules\Users\Tests\Unit;

use Modules\Lookups\Models\Lookup;
use Modules\Users\Filament\Resources\RoleResource;
use Modules\Users\Models\User;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    public function test_form_data_becomes_role_permissions_and_keeps_other_attributes(): void
    {
        $data = RoleResource::formDataToPermissions(
            ['name_en' => 'Editor', 'perm_lookups' => true, 'perm_blogs' => false, 'perm_system_configuration_all' => false],
            ['color' => 'blue'],
        );

        $this->assertSame('Editor', $data['name_en']);
        $this->assertSame('blue', $data['attributes']['color']);
        $this->assertTrue($data['attributes']['permissions']['lookups']);
        $this->assertFalse($data['attributes']['permissions']['blogs']);
        $this->assertFalse($data['attributes']['permissions']['users']);
        $this->assertArrayNotHasKey('perm_lookups', $data);
        $this->assertArrayNotHasKey('perm_system_configuration_all', $data);
    }

    public function test_every_available_module_gets_a_permission_key(): void
    {
        $data = RoleResource::formDataToPermissions([]);
        $modules = array_merge(...array_values(RoleResource::getAvailableModules()));

        $this->assertEqualsCanonicalizing($modules, array_keys($data['attributes']['permissions']));
    }

    public function test_admin_role_keeps_the_locked_modules_even_when_unchecked(): void
    {
        $admin = new Lookup(['slug' => User::ADMIN_ROLE]);
        $data = RoleResource::formDataToPermissions(['perm_users' => false, 'perm_roles' => false], [], $admin);

        $this->assertTrue($data['attributes']['permissions']['users']);
        $this->assertTrue($data['attributes']['permissions']['roles']);
        $this->assertFalse($data['attributes']['permissions']['lookups']);
    }

    public function test_other_roles_can_lose_the_locked_modules(): void
    {
        $editor = new Lookup(['slug' => 'user_roles_editor']);

        $this->assertFalse(RoleResource::isLockedPermission($editor, 'users'));
        $this->assertFalse(RoleResource::formDataToPermissions(['perm_users' => false], [], $editor)['attributes']['permissions']['users']);
    }

    public function test_stored_permissions_become_checkbox_state(): void
    {
        $role = new Lookup([
            'slug' => 'user_roles_editor',
            'attributes' => ['permissions' => ['users' => true, 'roles' => true, 'lookups' => true, 'settings' => true, 'utilities' => true, 'blogs' => true]],
        ]);

        $data = RoleResource::permissionsToFormData($role, []);

        $this->assertTrue($data['perm_blogs']);
        $this->assertFalse($data['perm_events']);
        $this->assertTrue($data['perm_system_configuration_all']);
        $this->assertFalse($data['perm_marketing_content_all']);
    }
}
