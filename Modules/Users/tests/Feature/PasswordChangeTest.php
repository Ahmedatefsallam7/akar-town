<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Modules\Users\Filament\Resources\UserResource\Pages\EditUser;
use Modules\Users\Models\User;
use Tests\TestCase;

/**
 * Only an admin changes someone else's password; everyone changes their own on the profile page.
 */
class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seedRoles();

        return User::factory()->create([
            'username' => 'client'.uniqid(), 'phone' => '+2010'.random_int(10000000, 99999999),
            'role_id' => $this->role('user_roles_client')->id, 'password' => 'old-pass-123',
        ]);
    }

    public function test_admin_can_set_another_users_password(): void
    {
        $this->actingAs($this->createAdmin());
        $user = $this->client();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertFormFieldIsVisible('password')
            ->fillForm(['password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('new-pass-123', $user->fresh()->password));
    }

    public function test_non_admin_cannot_change_another_users_password(): void
    {
        $this->actingAs($this->createUserWithPermissions(['users' => true]));
        $user = $this->client();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertFormFieldIsHidden('password')
            ->set('data.password', 'hacked-pass-1')
            ->set('data.password_confirmation', 'hacked-pass-1')
            ->call('save');

        $this->assertTrue(Hash::check('old-pass-123', $user->fresh()->password));
    }

    public function test_nobody_changes_their_own_password_from_the_users_page(): void
    {
        $admin = $this->createAdmin(['username' => 'boss', 'phone' => '+201099999999', 'password' => 'old-pass-123']);
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->assertFormFieldIsHidden('password')
            ->set('data.password', 'new-pass-123')
            ->call('save');

        $this->assertTrue(Hash::check('old-pass-123', $admin->fresh()->password));
    }
}
