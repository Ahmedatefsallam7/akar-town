<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Modules\Users\Filament\Resources\UserResource;
use Modules\Users\Filament\Resources\UserResource\Pages\CreateUser;
use Modules\Users\Filament\Resources\UserResource\Pages\EditUser;
use Modules\Users\Filament\Resources\UserResource\Pages\ListUsers;
use Modules\Users\Models\User;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return $overrides + [
            'first_name' => 'Sara',
            'last_name' => 'Ali',
            'username' => 'sara',
            'email' => 'sara@example.com',
            'phone' => '+201012345678',
            'role_id' => $this->role('user_roles_editor')->id,
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ];
    }

    public function test_user_without_the_users_permission_is_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['lookups' => true]))
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_admin_can_open_every_page(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $this->get(UserResource::getUrl('index'))->assertOk();
        $this->get(UserResource::getUrl('create'))->assertOk();
        $this->get(UserResource::getUrl('view', ['record' => $admin]))->assertOk();
        $this->get(UserResource::getUrl('edit', ['record' => $admin]))->assertOk();
    }

    public function test_admin_can_create_a_user_with_a_hashed_password(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateUser::class)
            ->fillForm($this->validData())
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'sara@example.com')->sole();
        $this->assertSame('Sara Ali', $user->name);
        $this->assertSame('user_roles_editor', $user->role->slug);
        $this->assertTrue(Hash::check('secret-pass-1', $user->password));
    }

    public function test_create_form_validates_fields(): void
    {
        $admin = $this->createAdmin(['username' => 'taken']);
        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm($this->validData([
                'username' => 'taken',
                'email' => $admin->email,
                'phone' => 'abc',
                'password_confirmation' => 'different',
            ]))
            ->call('create')
            ->assertHasFormErrors(['username' => 'unique', 'email' => 'unique', 'phone' => 'regex', 'password' => 'same']);
    }

    public function test_editing_a_user_with_a_blank_password_keeps_the_old_one(): void
    {
        $this->actingAs($this->createAdmin());
        $user = User::factory()->create(['username' => 'sara', 'phone' => '+201012345678', 'password' => 'old-pass-123', 'role_id' => $this->role('user_roles_editor')->id]);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['first_name' => 'Sally', 'password' => '', 'password_confirmation' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Sally', $user->fresh()->first_name);
        $this->assertTrue(Hash::check('old-pass-123', $user->fresh()->password));
    }

    public function test_non_admin_cannot_see_or_open_admin_accounts(): void
    {
        $admin = $this->createAdmin();
        $manager = $this->createUserWithPermissions(['users' => true]);
        $this->actingAs($manager);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$manager])
            ->assertCanNotSeeTableRecords([$admin]);

        $this->get(UserResource::getUrl('edit', ['record' => $admin]))->assertNotFound();
    }

    public function test_non_admin_cannot_give_the_admin_role(): void
    {
        $this->actingAs($this->createUserWithPermissions(['users' => true]));

        Livewire::test(CreateUser::class)
            ->fillForm($this->validData(['role_id' => $this->role(User::ADMIN_ROLE)->id]))
            ->call('create')
            ->assertHasFormErrors(['role_id']);

        $this->assertDatabaseMissing('users', ['email' => 'sara@example.com']);
    }

    public function test_user_cannot_delete_or_suspend_themselves(): void
    {
        $admin = $this->createAdmin();
        $other = User::factory()->create();
        $this->actingAs($admin);

        $this->assertFalse(UserResource::canDelete($admin));
        $this->assertTrue(UserResource::canDelete($other));

        Livewire::test(ListUsers::class)
            ->callTableBulkAction('suspend', [$admin, $other]);

        $this->assertFalse($admin->fresh()->is_suspended);
        $this->assertTrue($other->fresh()->is_suspended);
    }

    public function test_non_admin_cannot_suspend_themselves(): void
    {
        $manager = $this->createUserWithPermissions(['users' => true], ['username' => 'manager', 'phone' => '+201011111111']);
        $this->actingAs($manager);

        Livewire::test(EditUser::class, ['record' => $manager->getRouteKey()])
            ->assertFormFieldIsDisabled('is_suspended')
            ->set('data.is_suspended', true)
            ->call('save');

        Livewire::test(ListUsers::class)->callTableBulkAction('suspend', [$manager]);

        $this->assertFalse($manager->fresh()->is_suspended);
    }

    public function test_non_admin_cannot_suspend_an_admin(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($this->createUserWithPermissions(['users' => true]));

        Livewire::test(ListUsers::class)->assertCanNotSeeTableRecords([$admin]);
        $this->get(UserResource::getUrl('edit', ['record' => $admin]))->assertNotFound();
        $this->assertFalse($admin->fresh()->is_suspended);
    }

    public function test_users_badge_counts_only_the_users_you_can_see(): void
    {
        $this->createAdmin();
        $this->createAdmin();
        $manager = $this->createUserWithPermissions(['users' => true]);

        $this->actingAs($manager);
        $this->assertSame('1', UserResource::getNavigationBadge());

        $this->actingAs(User::where('id', '!=', $manager->id)->first());
        $this->assertSame('3', UserResource::getNavigationBadge());
    }
}
