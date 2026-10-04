<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Filament\Resources\RoleResource;
use Modules\Users\Filament\Resources\RoleResource\Pages\CreateRole;
use Modules\Users\Filament\Resources\RoleResource\Pages\EditRole;
use Modules\Users\Filament\Resources\RoleResource\Pages\ListRoles;
use Modules\Users\Models\User;
use Tests\TestCase;

class RoleResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_the_roles_permission_is_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['users' => true]))
            ->get(RoleResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_admin_can_open_every_page(): void
    {
        $this->actingAs($this->createAdmin());
        $editor = $this->role('user_roles_editor');

        $this->get(RoleResource::getUrl('index'))->assertOk();
        $this->get(RoleResource::getUrl('create'))->assertOk();
        $this->get(RoleResource::getUrl('view', ['record' => $editor]))->assertOk();
        $this->get(RoleResource::getUrl('edit', ['record' => $editor]))->assertOk();
    }

    public function test_list_shows_only_roles(): void
    {
        $this->actingAs($this->createAdmin());
        $views = Lookup::create(['name_en' => 'Views', 'slug' => 'views']);

        Livewire::test(ListRoles::class)
            ->assertCanSeeTableRecords(Lookup::roles()->get())
            ->assertCanNotSeeTableRecords([$views, $this->role(Lookup::ROLES_SLUG)]);
    }

    public function test_admin_can_create_a_role_with_permissions(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateRole::class)
            ->fillForm([
                'name_en' => 'Sales Team',
                'name_ar' => 'فريق المبيعات',
                'is_active' => true,
                'perm_lookups' => true,
                'perm_blogs' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $role = Lookup::where('slug', 'user_roles_sales_team')->sole();
        $this->assertSame($this->role(Lookup::ROLES_SLUG)->id, $role->parent_id);
        $this->assertTrue($role->getAttribute('attributes')['permissions']['lookups']);
        $this->assertTrue($role->getAttribute('attributes')['permissions']['blogs']);
        $this->assertFalse($role->getAttribute('attributes')['permissions']['users']);
    }

    public function test_slug_fills_in_while_typing_the_english_name(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateRole::class)
            ->fillForm(['name_en' => 'Sales Team'])
            ->assertSchemaStateSet(['slug' => 'user_roles_sales_team']);
    }

    public function test_renaming_a_custom_role_updates_its_slug(): void
    {
        $this->actingAs($this->createAdmin());
        $role = $this->createCustomRole('Sales Team');

        Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
            ->fillForm(['name_en' => 'Sales Managers'])
            ->assertSchemaStateSet(['slug' => 'user_roles_sales_managers'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('user_roles_sales_managers', $role->fresh()->slug);
    }

    public function test_renaming_a_seeded_non_admin_role_updates_its_slug(): void
    {
        $this->actingAs($this->createAdmin());
        $editor = $this->role('user_roles_editor');

        Livewire::test(EditRole::class, ['record' => $editor->getRouteKey()])
            ->fillForm(['name_en' => 'Content Editor'])
            ->assertSchemaStateSet(['slug' => 'user_roles_content_editor'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('user_roles_content_editor', $editor->fresh()->slug);
        $this->assertSame('info', $editor->fresh()->getAttribute('attributes')['color']);
    }

    public function test_renaming_the_admin_role_keeps_its_slug(): void
    {
        $this->actingAs($this->createAdmin());
        $admin = $this->role(User::ADMIN_ROLE);

        Livewire::test(EditRole::class, ['record' => $admin->getRouteKey()])
            ->fillForm(['name_en' => 'Super Admin'])
            ->assertSchemaStateSet(['slug' => User::ADMIN_ROLE])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(User::ADMIN_ROLE, $admin->fresh()->slug);
        $this->assertSame('Super Admin', $admin->fresh()->name_en);
    }

    public function test_a_name_that_gives_a_used_slug_is_rejected(): void
    {
        $this->actingAs($this->createAdmin());
        $this->createCustomRole('Sales Team');
        $this->createCustomRole('Old Team')->delete();

        Livewire::test(CreateRole::class)
            ->fillForm(['name_en' => 'Sales-Team', 'name_ar' => 'المبيعات'])
            ->call('create')
            ->assertHasFormErrors(['name_en']);

        // A deleted role still holds its slug, so it can be restored.
        Livewire::test(CreateRole::class)
            ->fillForm(['name_en' => 'Old Team', 'name_ar' => 'قديم'])
            ->call('create')
            ->assertHasFormErrors(['name_en']);
    }

    public function test_renaming_a_role_to_a_used_slug_is_rejected(): void
    {
        $this->actingAs($this->createAdmin());
        $this->createCustomRole('Sales Team');
        $role = $this->createCustomRole('Support');

        Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
            ->fillForm(['name_en' => 'Sales-Team'])
            ->call('save')
            ->assertHasFormErrors(['name_en']);

        $this->assertSame('user_roles_support', $role->fresh()->slug);
    }

    public function test_role_name_must_be_unique_among_roles(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateRole::class)
            ->fillForm(['name_en' => 'Editor', 'name_ar' => 'المحرر'])
            ->call('create')
            ->assertHasFormErrors(['name_en' => 'unique']);
    }

    public function test_editing_a_role_saves_permissions_and_keeps_other_attributes(): void
    {
        $this->actingAs($this->createAdmin());
        $editor = $this->role('user_roles_editor');
        $editor->update(['attributes' => ['color' => 'blue', 'permissions' => ['lookups' => true]]]);

        Livewire::test(EditRole::class, ['record' => $editor->getRouteKey()])
            ->assertFormSet(['perm_lookups' => true, 'perm_blogs' => false])
            ->fillForm(['perm_lookups' => false, 'perm_blogs' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $attributes = $editor->fresh()->getAttribute('attributes');
        $this->assertSame('blue', $attributes['color']);
        $this->assertFalse($attributes['permissions']['lookups']);
        $this->assertTrue($attributes['permissions']['blogs']);
    }

    public function test_admin_role_keeps_users_and_roles_after_saving(): void
    {
        $this->actingAs($this->createAdmin());
        $admin = $this->role(User::ADMIN_ROLE);

        Livewire::test(EditRole::class, ['record' => $admin->getRouteKey()])
            ->fillForm(['perm_users' => false, 'perm_roles' => false, 'perm_blogs' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $permissions = $admin->fresh()->getAttribute('attributes')['permissions'];
        $this->assertTrue($permissions['users']);
        $this->assertTrue($permissions['roles']);
        $this->assertFalse($permissions['blogs']);
    }

    public function test_only_an_admin_can_edit_the_admin_role(): void
    {
        $this->actingAs($this->createUserWithPermissions(['roles' => true]));
        $admin = $this->role(User::ADMIN_ROLE);

        $this->assertFalse(RoleResource::canEdit($admin));
        $this->assertTrue(RoleResource::canEdit($this->role('user_roles_editor')));
        $this->get(RoleResource::getUrl('edit', ['record' => $admin]))->assertForbidden();
    }

    public function test_seeded_roles_cannot_be_deleted(): void
    {
        $this->actingAs($this->createAdmin());
        $custom = Lookup::create([
            'parent_id' => $this->role(Lookup::ROLES_SLUG)->id,
            'name_en' => 'Temp',
            'slug' => 'user_roles_temp',
            'is_deletable' => true,
        ]);

        $this->assertFalse(RoleResource::canDelete($this->role('user_roles_editor')));
        $this->assertTrue(RoleResource::canDelete($custom));
    }

    private function createCustomRole(string $name): Lookup
    {
        return Lookup::create([
            'parent_id' => $this->role(Lookup::ROLES_SLUG)->id,
            'name_en' => $name,
            'name_ar' => $name,
            'slug' => RoleResource::makeSlug($name),
            'is_deletable' => true,
        ]);
    }
}
