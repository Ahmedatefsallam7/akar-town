<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Filament\Resources\RoleResource;
use Modules\Users\Filament\Resources\RoleResource\Pages\EditRole;
use Modules\Users\Filament\Resources\RoleResource\Pages\ListRoles;
use Modules\Users\Filament\Resources\UserResource;
use Modules\Users\Filament\Resources\UserResource\Pages\CreateUser;
use Modules\Users\Filament\Resources\UserResource\Pages\EditUser;
use Modules\Users\Filament\Resources\UserResource\Pages\ListUsers;
use Modules\Users\Models\User;
use Modules\Users\Support\PermissionModules;
use Tests\TestCase;

/**
 * Nobody can give themselves (or anyone) more access than they have.
 */
class PrivilegeEscalationTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Lookup $superRole;

    private User $boss;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->createUserWithPermissions(['users' => true, 'roles' => true], ['username' => 'manager', 'phone' => '+201011111111']);
        $this->superRole = Lookup::create([
            'parent_id' => $this->role(Lookup::ROLES_SLUG)->id,
            'name_en' => 'Super', 'name_ar' => 'سوبر', 'slug' => 'user_roles_super', 'is_active' => true, 'is_deletable' => true,
            'attributes' => ['permissions' => PermissionModules::everything()],
        ]);
        $this->boss = User::factory()->create(['username' => 'boss', 'phone' => '+201022222222', 'role_id' => $this->superRole->id, 'password' => 'boss-pass-123']);
        $this->actingAs($this->manager);
    }

    public function test_nobody_can_change_their_own_role(): void
    {
        Livewire::test(EditUser::class, ['record' => $this->manager->getRouteKey()])
            ->assertFormFieldIsDisabled('role_id')
            ->set('data.role_id', $this->superRole->id)
            ->call('save');

        $this->assertNotSame($this->superRole->id, $this->manager->fresh()->role_id);
    }

    public function test_roles_with_more_access_cannot_be_given(): void
    {
        $this->assertNotContains($this->superRole->id, UserResource::assignableRoleIds());
        $this->assertNotContains($this->role(User::ADMIN_ROLE)->id, UserResource::assignableRoleIds());
        $this->assertContains($this->manager->role_id, UserResource::assignableRoleIds());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'first_name' => 'Sara', 'last_name' => 'Ali', 'username' => 'sara', 'email' => 'sara@example.com',
                'phone' => '+201012345678', 'role_id' => $this->superRole->id,
                'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
            ])
            ->call('create')
            ->assertHasFormErrors(['role_id']);
    }

    public function test_users_with_more_access_cannot_be_edited_or_taken_over(): void
    {
        $this->assertFalse(UserResource::canEdit($this->boss));
        $this->get(UserResource::getUrl('edit', ['record' => $this->boss]))->assertForbidden();
        $this->get(UserResource::getUrl('view', ['record' => $this->boss]))->assertOk();
        $this->assertTrue(Hash::check('boss-pass-123', $this->boss->fresh()->password));
    }

    public function test_users_with_more_access_cannot_be_deleted_or_suspended(): void
    {
        $peer = User::factory()->create(['role_id' => $this->manager->role_id]);

        // The real list page: edit link for the peer, none for the boss.
        $this->get(UserResource::getUrl('index'))
            ->assertOk()
            ->assertSee(UserResource::getUrl('edit', ['record' => $peer]), false)
            ->assertDontSee(UserResource::getUrl('edit', ['record' => $this->boss]), false);

        Livewire::test(ListUsers::class)
            ->callTableBulkAction('suspend', [$this->boss])
            ->callTableBulkAction('delete', [$this->boss]);

        $this->assertFalse($this->boss->fresh()->is_suspended);
        $this->assertNotSoftDeleted($this->boss);
    }

    public function test_users_with_less_or_equal_access_can_be_managed(): void
    {
        $peer = User::factory()->create(['role_id' => $this->manager->role_id]);
        $client = User::factory()->create(['role_id' => $this->role('user_roles_client')->id]);

        $this->assertTrue(UserResource::canEdit($peer));
        $this->assertTrue(UserResource::canEdit($client));
        $this->assertTrue(UserResource::canDelete($client));
    }

    public function test_roles_with_more_access_cannot_be_edited_deleted_or_deactivated(): void
    {
        $this->assertFalse(RoleResource::canEdit($this->superRole));
        $this->assertFalse(RoleResource::canDelete($this->superRole));
        $this->get(RoleResource::getUrl('edit', ['record' => $this->superRole]))->assertForbidden();

        Livewire::test(ListRoles::class)
            ->callTableBulkAction('deactivate', [$this->superRole])
            ->callTableBulkAction('delete', [$this->superRole]);

        $this->assertTrue($this->superRole->fresh()->is_active);
        $this->assertNotSoftDeleted($this->superRole);
    }

    public function test_admins_can_still_manage_everyone(): void
    {
        $this->actingAs($this->createAdmin());

        $this->assertTrue(UserResource::canEdit($this->boss));
        $this->assertTrue(RoleResource::canEdit($this->superRole));
        $this->assertContains($this->superRole->id, UserResource::assignableRoleIds());
    }

    public function test_the_admin_role_is_always_active(): void
    {
        $this->actingAs($this->createAdmin());
        $admin = $this->role(User::ADMIN_ROLE);

        Livewire::test(EditRole::class, ['record' => $admin->getRouteKey()])
            ->assertFormFieldIsDisabled('is_active');

        Livewire::test(ListRoles::class)->callTableBulkAction('deactivate', [$admin]);
        $admin->update(['is_active' => false]);

        $this->assertTrue($admin->fresh()->is_active);
    }
}
