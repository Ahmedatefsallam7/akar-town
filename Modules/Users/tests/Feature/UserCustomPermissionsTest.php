<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Filament\Auth\EditProfile;
use Modules\Users\Filament\Resources\UserResource;
use Modules\Users\Filament\Resources\UserResource\Pages\CreateUser;
use Modules\Users\Filament\Resources\UserResource\Pages\EditUser;
use Modules\Users\Models\User;
use Tests\TestCase;

/**
 * "Custom permissions for this user": one switch on the user form; when on, the user's own list replaces the role.
 */
class UserCustomPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function client(?array $custom = null): User
    {
        $this->seedRoles();

        return User::factory()->create([
            'username' => 'client'.uniqid(),
            'phone' => '+2010'.random_int(10000000, 99999999),
            'role_id' => $this->role('user_roles_client')->id,
            'custom_permissions' => $custom,
        ]);
    }

    public function test_switching_on_starts_from_the_role_permissions(): void
    {
        $this->actingAs($this->createAdmin());
        $this->role('user_roles_client')->update(['attributes' => ['permissions' => ['lookups' => ['view', 'edit']]]]);
        $user = $this->client();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertSchemaStateSet(['use_custom_permissions' => false])
            ->fillForm(['use_custom_permissions' => true])
            ->assertSchemaStateSet(['perm_lookups' => ['view', 'edit'], 'perm_users' => []]);
    }

    public function test_admin_gives_one_user_their_own_permissions_whatever_their_role(): void
    {
        $this->actingAs($this->createAdmin());
        $user = $this->client();
        $other = $this->client();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['use_custom_permissions' => true, 'perm_lookups' => ['view', 'edit']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['view', 'edit'], $user->fresh()->custom_permissions['lookups']);
        $this->assertFalse($other->fresh()->hasPermission('lookups'));
        $this->actingAs($user->fresh())
            ->get(LookupResource::getUrl('edit', ['record' => Lookup::create(['name_en' => 'Views', 'slug' => 'views'])]))
            ->assertOk();
    }

    public function test_switching_off_goes_back_to_the_role(): void
    {
        $this->actingAs($this->createAdmin());
        $user = $this->client(['lookups' => ['view']]);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertSchemaStateSet(['use_custom_permissions' => true, 'perm_lookups' => ['view']])
            ->fillForm(['use_custom_permissions' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($user->fresh()->custom_permissions);
    }

    public function test_custom_permissions_can_be_set_when_creating_a_user(): void
    {
        $this->actingAs($this->createAdmin());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'first_name' => 'Sara', 'last_name' => 'Ali', 'username' => 'sara', 'email' => 'sara@example.com',
                'phone' => '+201012345678', 'role_id' => $this->role('user_roles_client')->id,
                'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
                'use_custom_permissions' => true, 'perm_lookups' => ['view'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(User::where('email', 'sara@example.com')->sole()->hasPermission('lookups'));
    }

    public function test_nobody_can_give_more_than_they_have(): void
    {
        $this->actingAs($this->createUserWithPermissions(['users' => true, 'lookups' => ['view']]));
        $user = $this->client();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['use_custom_permissions' => true])
            ->set('data.perm_lookups', ['view', 'edit', 'delete'])
            ->call('save')
            ->assertHasFormErrors(['perm_lookups.1', 'perm_lookups.2']);

        $this->assertNull($user->fresh()->custom_permissions);

        // The save step drops them too, even if the form check were bypassed.
        $data = UserResource::formDataToPermissions(['use_custom_permissions' => true, 'perm_lookups' => ['view', 'edit', 'delete']], $user);
        $this->assertSame(['view'], $data['custom_permissions']['lookups']);
    }

    public function test_your_own_permissions_are_hidden_and_never_saved(): void
    {
        $admin = $this->createAdmin(['username' => 'boss', 'phone' => '+201099999999', 'custom_permissions' => ['lookups' => ['view']]]);
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->assertSchemaComponentHidden('use_custom_permissions')
            ->set('data.use_custom_permissions', false)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['lookups' => ['view']], $admin->fresh()->custom_permissions);
    }

    public function test_view_page_shows_effective_permissions(): void
    {
        $this->actingAs($this->createAdmin());
        $user = $this->client(['lookups' => ['view', 'edit']]);

        $this->get(UserResource::getUrl('view', ['record' => $user]))
            ->assertOk()
            ->assertSee(__('admin.users.effective_permissions'))
            ->assertSee(__('admin.roles.actions.edit'))
            ->assertSee(__('admin.users.no_access'));
    }

    public function test_profile_page_cannot_change_permissions(): void
    {
        $user = $this->client();
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->set('data.custom_permissions', ['lookups' => ['view', 'edit', 'delete']])
            ->call('save');

        $this->assertNull($user->fresh()->custom_permissions);
    }
}
