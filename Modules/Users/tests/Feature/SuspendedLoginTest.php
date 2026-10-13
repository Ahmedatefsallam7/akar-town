<?php

namespace Modules\Users\Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Users\Filament\Auth\Login;
use Tests\TestCase;

class SuspendedLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_logged_in_user_is_logged_out_when_suspended(): void
    {
        $user = $this->createAdmin(['password' => 'secret-pass-1']);
        $this->actingAs($user)->get('/admin')->assertOk();

        $user->update(['is_suspended' => true]);

        $this->get('/admin')->assertRedirect(Filament::getLoginUrl());
        $this->assertGuest();
        $this->assertTrue(session()->has(Login::SUSPENDED_FLASH));
    }

    public function test_login_page_shows_the_suspended_alert_after_that_logout(): void
    {
        session()->put(Login::SUSPENDED_FLASH, true);

        Livewire::test(Login::class)->assertNotified(__('admin.auth.suspended_title'));

        $this->assertFalse(session()->has(Login::SUSPENDED_FLASH));
    }

    public function test_suspended_user_sees_the_suspended_message_when_logging_in(): void
    {
        $this->createAdmin(['email' => 'sara@example.com', 'password' => 'secret-pass-1', 'is_suspended' => true]);

        Livewire::test(Login::class)
            ->fillForm(['email' => 'sara@example.com', 'password' => 'secret-pass-1'])
            ->call('authenticate')
            ->assertHasFormErrors(['email'])
            ->assertSee(__('admin.auth.suspended'))
            ->assertNotified(__('admin.auth.suspended_title'));

        $this->assertGuest();
    }

    public function test_wrong_password_on_a_suspended_account_does_not_reveal_it(): void
    {
        $this->createAdmin(['email' => 'sara@example.com', 'password' => 'secret-pass-1', 'is_suspended' => true]);

        Livewire::test(Login::class)
            ->fillForm(['email' => 'sara@example.com', 'password' => 'wrong-password'])
            ->call('authenticate')
            ->assertHasFormErrors(['email'])
            ->assertDontSee(__('admin.auth.suspended'));

        $this->assertGuest();
    }

    public function test_active_user_logs_in_normally(): void
    {
        $this->createAdmin(['email' => 'sara@example.com', 'password' => 'secret-pass-1']);

        Livewire::test(Login::class)
            ->fillForm(['email' => 'sara@example.com', 'password' => 'secret-pass-1'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticated();
    }

    public function test_login_is_locked_after_too_many_wrong_passwords(): void
    {
        $this->createAdmin(['email' => 'sara@example.com', 'password' => 'secret-pass-1']);
        $login = Livewire::test(Login::class);

        foreach (range(1, 6) as $try) {
            $login->fillForm(['email' => 'sara@example.com', 'password' => "wrong-{$try}"])->call('authenticate');
        }

        $login->fillForm(['email' => 'sara@example.com', 'password' => 'secret-pass-1'])->call('authenticate');

        $this->assertGuest();
    }
}
