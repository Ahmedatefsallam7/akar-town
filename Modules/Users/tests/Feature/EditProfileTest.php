<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Modules\Users\Filament\Auth\EditProfile;
use Modules\Users\Models\User;
use Tests\TestCase;

class EditProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_opens_for_any_logged_in_user(): void
    {
        $this->seedRoles();

        $this->actingAs(User::factory()->create())->get('/admin/profile')->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/profile')->assertRedirect('/admin/login');
    }

    public function test_user_can_update_their_details_without_a_password(): void
    {
        $user = $this->createAdmin(['name' => 'Ahmed Atef']);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->assertSchemaStateSet(['first_name' => 'Ahmed', 'last_name' => 'Atef'])
            ->fillForm(['last_name' => 'Sallam', 'username' => 'ahmed', 'phone' => '+201000000000'])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame('Ahmed Sallam', $user->name);
        $this->assertSame('ahmed', $user->username);
        $this->assertSame('+201000000000', $user->phone);
    }

    public function test_invalid_phone_and_taken_username_are_rejected(): void
    {
        User::factory()->create(['username' => 'taken']);
        $this->actingAs($this->createAdmin());

        Livewire::test(EditProfile::class)
            ->fillForm(['phone' => 'abc', 'username' => 'taken'])
            ->call('save')
            ->assertHasFormErrors(['phone' => 'regex', 'username' => 'unique']);
    }

    public function test_changing_the_password_needs_the_current_password(): void
    {
        $user = $this->createAdmin(['password' => 'old-pass-123']);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm(['password' => 'NewPass12345!', 'passwordConfirmation' => 'NewPass12345!'])
            ->call('save')
            ->assertHasFormErrors(['currentPassword' => 'required']);

        Livewire::test(EditProfile::class)
            ->fillForm(['password' => 'NewPass12345!', 'passwordConfirmation' => 'NewPass12345!', 'currentPassword' => 'wrong'])
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);

        $this->assertTrue(Hash::check('old-pass-123', $user->fresh()->password));

        Livewire::test(EditProfile::class)
            ->fillForm(['password' => 'NewPass12345!', 'passwordConfirmation' => 'NewPass12345!', 'currentPassword' => 'old-pass-123'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('NewPass12345!', $user->fresh()->password));
    }

    public function test_role_and_suspension_cannot_be_changed_from_the_profile(): void
    {
        $user = $this->createAdmin();
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->set('data.role_id', $this->role('user_roles_client')->id)
            ->set('data.is_suspended', true)
            ->call('save');

        $this->assertTrue($user->fresh()->isAdmin());
        $this->assertFalse($user->fresh()->is_suspended);
    }
}
