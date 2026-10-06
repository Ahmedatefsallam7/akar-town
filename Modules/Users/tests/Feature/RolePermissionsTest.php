<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Enums\PermissionAction;
use Modules\Users\Filament\Forms\PermissionsGrid;
use Modules\Users\Filament\Resources\RoleResource;
use Modules\Users\Models\User;
use Modules\Users\Support\PermissionModules;
use Tests\TestCase;

/**
 * Role form <-> stored permissions, through the shared PermissionsGrid.
 */
class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    private const ALL = ['view', 'create', 'edit', 'delete', 'bulk'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdmin());
    }

    public function test_modules_are_discovered_from_code_with_their_group_and_actions(): void
    {
        $modules = PermissionModules::all();

        $this->assertEqualsCanonicalizing(['developers', 'locations', 'lookups', 'projects', 'users', 'roles'], array_keys($modules));
        $this->assertSame('system_configuration', $modules['lookups']['group']);
        $this->assertSame(self::ALL, PermissionModules::actionsFor('lookups'));
        $this->assertSame([], PermissionModules::actionsFor('unknown_module'));
        $this->assertSame(self::ALL, PermissionModules::everything()['users']);
    }

    public function test_form_data_becomes_role_permissions_and_keeps_other_attributes(): void
    {
        $data = RoleResource::formDataToPermissions(
            ['name_en' => 'Editor', 'permission_matrix' => ['lookups' => ['view', 'edit'], 'users' => []]],
            ['color' => 'blue'],
        );

        $this->assertSame('Editor', $data['name_en']);
        $this->assertSame('blue', $data['attributes']['color']);
        $this->assertSame(['view', 'edit'], $data['attributes']['permissions']['lookups']);
        $this->assertSame([], $data['attributes']['permissions']['users']);
        $this->assertArrayNotHasKey('permission_matrix', $data);
    }

    public function test_unknown_actions_and_unknown_modules_are_dropped(): void
    {
        $data = RoleResource::formDataToPermissions(['permission_matrix' => ['lookups' => ['view', 'hack'], 'unknown_module' => ['view']]]);

        $this->assertSame(['view'], $data['attributes']['permissions']['lookups']);
        $this->assertArrayNotHasKey('unknown_module', $data['attributes']['permissions']);
    }

    public function test_modules_not_in_the_form_keep_their_saved_actions(): void
    {
        $data = RoleResource::formDataToPermissions(['permission_matrix' => ['users' => ['view']]], ['permissions' => ['projects' => ['view'], 'lookups' => ['view', 'edit']]]);

        $this->assertSame(['view'], $data['attributes']['permissions']['projects']);
        $this->assertSame(['view', 'edit'], $data['attributes']['permissions']['lookups']);
        $this->assertSame(['view'], $data['attributes']['permissions']['users']);
    }

    public function test_admin_role_keeps_every_action_on_the_locked_modules(): void
    {
        $admin = new Lookup(['slug' => User::ADMIN_ROLE]);
        $data = RoleResource::formDataToPermissions(['permission_matrix' => ['users' => [], 'roles' => ['view'], 'lookups' => []]], [], $admin);

        $this->assertSame(self::ALL, $data['attributes']['permissions']['users']);
        $this->assertSame(self::ALL, $data['attributes']['permissions']['roles']);
        $this->assertSame([], $data['attributes']['permissions']['lookups']);
    }

    public function test_other_roles_can_lose_the_locked_modules(): void
    {
        $editor = new Lookup(['slug' => 'user_roles_editor']);

        $this->assertFalse(RoleResource::isLockedPermission($editor, 'users'));
        $this->assertSame([], RoleResource::formDataToPermissions(['permission_matrix' => ['users' => []]], [], $editor)['attributes']['permissions']['users']);
    }

    public function test_you_can_only_change_actions_you_have_yourself(): void
    {
        $this->actingAs($this->createUserWithPermissions(['roles' => true, 'lookups' => ['view', 'edit']]));

        $this->assertSame(['view', 'edit'], PermissionsGrid::grantable('lookups'));

        // Can't add delete; can't remove the bulk action the role already had.
        $data = RoleResource::formDataToPermissions(['permission_matrix' => ['lookups' => ['view', 'delete']]], ['permissions' => ['lookups' => ['edit', 'bulk']]]);

        $this->assertSame(['view', 'bulk'], $data['attributes']['permissions']['lookups']);
    }

    public function test_stored_permissions_become_form_state(): void
    {
        $role = new Lookup(['slug' => 'user_roles_editor', 'attributes' => ['permissions' => ['lookups' => ['view', 'edit'], 'locations' => ['view']]]]);

        $data = RoleResource::permissionsToFormData($role, []);

        $this->assertSame(['view', 'edit'], $data['permission_matrix']['lookups']);
        $this->assertSame(['view'], $data['permission_matrix']['locations']);
        $this->assertSame([], $data['permission_matrix']['users']);
    }

    public function test_old_on_off_values_become_empty_form_state(): void
    {
        $role = new Lookup(['slug' => 'user_roles_editor', 'attributes' => ['permissions' => ['lookups' => true]]]);

        $this->assertSame([], RoleResource::permissionsToFormData($role, [])['permission_matrix']['lookups']);
    }

    public function test_filament_abilities_map_to_actions(): void
    {
        $this->assertSame([PermissionAction::View], PermissionAction::forAbility('viewAny'));
        $this->assertSame([PermissionAction::Edit], PermissionAction::forAbility('update'));
        $this->assertSame([PermissionAction::Delete], PermissionAction::forAbility('restore'));
        $this->assertSame([PermissionAction::Delete, PermissionAction::Bulk], PermissionAction::forAbility('deleteAny'));
        $this->assertSame([PermissionAction::Edit, PermissionAction::Bulk], PermissionAction::forAbility('updateAny'));
        $this->assertNull(PermissionAction::forAbility('somethingNew'));
    }

    public function test_role_slug_is_built_from_the_english_name(): void
    {
        $this->assertSame('user_roles_sales_team', RoleResource::makeSlug('Sales Team'));
        $this->assertSame('user_roles_sales_team', RoleResource::makeSlug('  Sales-Team! '));
    }

    public function test_only_the_admin_role_has_a_locked_slug(): void
    {
        $this->assertFalse(RoleResource::isSlugLocked(null));
        $this->assertTrue(RoleResource::isSlugLocked(new Lookup(['slug' => User::ADMIN_ROLE, 'is_deletable' => false])));
        $this->assertFalse(RoleResource::isSlugLocked(new Lookup(['slug' => 'user_roles_editor', 'is_deletable' => false])));
    }
}
