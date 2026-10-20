<?php

namespace Tests\Feature\Website;

use App\Livewire\Website\AuthModal;
use App\Livewire\Website\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Modules\WebsiteUsers\Notifications\ResetWebsitePassword;
use Modules\WebsiteUsers\Notifications\VerifyWebsiteEmail;
use Tests\TestCase;

/**
 * The website's sign-in / register window (the design's auth-modal).
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();
        RateLimiter::clear('website-sign-in:127.0.0.1');
        RateLimiter::clear('website-register:127.0.0.1');
        RateLimiter::clear('website-reset-link:127.0.0.1');
    }

    private function user(array $attributes = []): WebsiteUser
    {
        return WebsiteUser::factory()->create($attributes + ['email' => 'sara@example.com', 'password' => 'secret-pass-1', 'name' => 'Sara Ali']);
    }

    public function test_every_page_has_the_window_and_the_navbar_button_opens_it(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSeeLivewire(AuthModal::class)
            ->assertSee(['Sign in or Register', 'WhatsApp number', 'Continue with Email', 'Continue with Google', 'Continue with Facebook'])
            ->assertSee('@click="$dispatch(\'open-auth\')"', false)
            ->assertSee('@open-auth.window="show()"', false)
            // In its own form: a saved e-mail is filled here, not into the page's search box.
            ->assertSee('<form x-show="step === \'email\'" x-cloak @submit.prevent', false);
    }

    public function test_the_window_in_arabic(): void
    {
        $this->withCookie('locale', 'ar')
            ->get(route('home'))
            ->assertOk()
            ->assertSee(['تسجيل الدخول أو إنشاء حساب', 'رقم الواتساب', 'المتابعة بجوجل']);
    }

    public function test_an_existing_account_signs_in_with_its_password(): void
    {
        $user = $this->user();

        Livewire::test(AuthModal::class)
            ->set(['email' => ' Sara@Example.com ', 'password' => 'secret-pass-1'])
            ->call('signIn')
            ->assertHasNoErrors()
            ->assertDispatched('signed-in', name: 'Sara Ali')
            ->assertSet('password', '');

        $this->assertTrue(Auth::guard('website')->user()->is($user));
        $this->assertNotNull($user->fresh()->last_login_at);
        // Never the admin panel.
        $this->assertFalse(Auth::guard('web')->check());
    }

    public function test_a_wrong_password_says_only_that_the_details_are_incorrect(): void
    {
        $this->user();

        Livewire::test(AuthModal::class)
            ->set(['email' => 'sara@example.com', 'password' => 'wrong-password'])
            ->call('signIn')
            ->assertHasErrors('password')
            ->assertSee('These details are incorrect.')
            ->assertNotDispatched('signed-in');

        $this->assertFalse(Auth::guard('website')->check());
    }

    public function test_a_new_email_creates_the_account_in_the_same_step(): void
    {
        app()->setLocale('ar');

        Livewire::test(AuthModal::class)
            ->set(['email' => 'omar@example.com', 'password' => 'new-pass-123'])
            ->call('signIn')
            ->assertSet('registering', true)
            ->assertSee('auth-name')
            ->set(['name' => 'Omar Adel', 'passwordConfirmation' => 'new-pass-123'])
            ->call('register')
            ->assertHasNoErrors()
            ->assertDispatched('signed-in', name: 'Omar Adel');

        $user = WebsiteUser::sole();
        $this->assertSame(['Omar Adel', 'omar@example.com', 'email', 'ar', WebsiteUser::ACTIVE], [$user->name, $user->email, $user->registration_method, $user->preferred_language, $user->status]);
        $this->assertTrue(Auth::guard('website')->user()->is($user));
        $this->assertNotSame('new-pass-123', $user->password); // stored hashed
    }

    public function test_registration_checks_name_password_length_and_confirmation(): void
    {
        Livewire::test(AuthModal::class)
            ->set(['email' => 'omar@example.com', 'password' => 'short'])
            ->call('signIn')
            ->set(['name' => '', 'passwordConfirmation' => 'other'])
            ->call('register')
            ->assertHasErrors(['name' => 'required', 'password', 'passwordConfirmation' => 'same']);

        $this->assertSame(0, WebsiteUser::count());
    }

    public function test_the_registering_switch_cant_be_set_from_the_browser(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(AuthModal::class)->set('registering', true);
    }

    public function test_a_blocked_account_cant_sign_in(): void
    {
        $this->user()->block();

        Livewire::test(AuthModal::class)
            ->set(['email' => 'sara@example.com', 'password' => 'secret-pass-1'])
            ->call('signIn')
            ->assertHasErrors('email')
            ->assertSee(__('admin.website_users.account_unavailable'))
            ->assertNotDispatched('signed-in');

        $this->assertFalse(Auth::guard('website')->check());
    }

    public function test_sign_in_tries_are_limited(): void
    {
        $this->user();
        $try = fn () => Livewire::test(AuthModal::class)->set(['email' => 'sara@example.com', 'password' => 'wrong-password'])->call('signIn');

        foreach (range(1, 5) as $i) {
            $try()->assertHasErrors('password');
        }
        $try()->assertHasErrors('email')->assertSee('Too many attempts.');
    }

    public function test_signed_in_the_navbar_shows_the_name_and_a_sign_out(): void
    {
        $this->get(route('home'))->assertSee('Sign in or register')->assertDontSee('My account');

        $this->actingAs($this->user(), 'website')
            ->get(route('home'))
            ->assertOk()
            ->assertSee(['My account', 'Sara Ali', 'sara@example.com', 'Sign out', route('account.logout')])
            ->assertDontSee('aria-label="Sign in or register"', false);
    }

    public function test_sign_out_ends_the_website_account_only_and_stays_on_this_site(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'web')->actingAs($this->user(), 'website');

        $this->from(route('properties'))->post(route('account.logout'))->assertRedirect(route('properties'));
        $this->assertFalse(Auth::guard('website')->check());
        $this->assertTrue(Auth::guard('web')->check()); // the admin stays signed in

        // Never back to another site.
        $this->actingAs($this->user(['email' => 'omar@example.com']), 'website')
            ->withHeader('Referer', 'https://evil.example/page')
            ->post(route('account.logout'))
            ->assertRedirect(url('/'));
    }

    public function test_a_blocked_account_sees_the_notice_once(): void
    {
        $user = $this->user();
        $this->actingAs($user, 'website');
        $user->block();

        $this->get(route('home'))->assertOk()->assertSee(__('admin.website_users.account_unavailable'));
        $this->get(route('properties'))->assertOk()->assertDontSee(__('admin.website_users.account_unavailable'));
    }

    public function test_forgot_password_emails_a_link_to_the_website_page_and_says_the_same_for_any_email(): void
    {
        Notification::fake();
        $user = $this->user(['preferred_language' => 'ar']);
        $blocked = $this->user(['email' => 'blocked@example.com']);
        $blocked->block();

        foreach (['sara@example.com', 'nobody@example.com', 'blocked@example.com'] as $email) {
            Livewire::test(AuthModal::class)->set('email', $email)->call('sendResetLink')->assertHasNoErrors()->assertSet('resetSent', true);
        }

        Notification::assertSentTo($user, ResetWebsitePassword::class, fn (ResetWebsitePassword $mail) => str_starts_with($mail->toMail($user)->actionUrl, route('account.password.reset', $mail->token)));
        Notification::assertSentTimes(ResetWebsitePassword::class, 1); // not to unknown or blocked accounts
        $this->assertSame('ar', $user->preferredLocale()); // the e-mail is in the account's language
    }

    public function test_the_link_sets_a_new_password_and_signs_in(): void
    {
        $user = $this->user();
        $token = Password::broker('website_users')->createToken($user);

        $this->get(route('account.password.reset', ['token' => $token, 'email' => 'sara@example.com']))->assertOk()->assertSee(['Reset Password', 'sara@example.com']);

        Livewire::withQueryParams(['email' => 'sara@example.com'])
            ->test(ResetPassword::class, ['token' => $token])
            ->set(['password' => 'brand-new-pass', 'passwordConfirmation' => 'brand-new-pass'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertTrue(Auth::guard('website')->user()->is($user));
        $this->assertTrue(Auth::guard('website')->validate(['email' => 'sara@example.com', 'password' => 'brand-new-pass']));
    }

    public function test_a_wrong_or_used_link_or_a_blocked_account_cant_reset(): void
    {
        $user = $this->user();
        $reset = fn (string $token) => Livewire::withQueryParams(['email' => 'sara@example.com'])
            ->test(ResetPassword::class, ['token' => $token])
            ->set(['password' => 'brand-new-pass', 'passwordConfirmation' => 'brand-new-pass'])
            ->call('save');

        $reset('made-up-token')->assertHasErrors('password')->assertSee('This link is invalid or has expired.');

        $token = Password::broker('website_users')->createToken($user);
        $user->block();
        $reset($token)->assertHasErrors('password');
        $this->assertFalse(Auth::guard('website')->check());
    }

    public function test_a_new_account_gets_a_verification_link_that_verifies_the_email(): void
    {
        Notification::fake();

        Livewire::test(AuthModal::class)
            ->set(['email' => 'omar@example.com', 'password' => 'new-pass-123'])
            ->call('signIn')
            ->set(['name' => 'Omar Adel', 'passwordConfirmation' => 'new-pass-123'])
            ->call('register');

        $user = WebsiteUser::sole();
        Notification::assertSentTo($user, VerifyWebsiteEmail::class);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyWebsiteEmail::class, function (VerifyWebsiteEmail $mail) use ($user, &$link) {
            $link = $mail->toMail($user)->actionUrl;

            return ! str_contains($link, '/'.$user->id); // no database id in the link
        });
        $this->get($link)->assertRedirect(route('home'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get(route('home'))->assertSee('Your e-mail is verified. Thank you!');
    }

    public function test_a_changed_or_expired_verification_link_does_nothing(): void
    {
        $user = $this->user(['email_verified_at' => null]);

        $link = URL::temporarySignedRoute('account.verify', now()->addHour(), ['email' => 'other@example.com']);
        $this->get(str_replace(urlencode('other@example.com'), urlencode($user->email), $link)); // e-mail changed
        $this->get(route('account.verify', ['email' => $user->email])); // no signature
        $this->get(URL::temporarySignedRoute('account.verify', now()->subMinute(), ['email' => $user->email]))
            ->assertRedirect(route('home'));

        $this->assertNull($user->fresh()->email_verified_at);
        $this->get(route('home'))->assertSee('This link is invalid or has expired.');
    }
}
