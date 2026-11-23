<?php

namespace Tests\Feature\Website;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as ProviderUser;
use Mockery;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\WebsiteUsers\Http\Middleware\EnsureWebsiteUserIsActive;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Tests\TestCase;

/**
 * Website sign-in with Google / Facebook (AKAR-55, SocialSignInController).
 */
class SocialSignInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $site = app(SiteSettings::class);
        $site->is_live = true;
        $site->save();

        config([
            'services.google.client_id' => 'google-id', 'services.google.client_secret' => 'google-secret',
            'services.facebook.client_id' => 'facebook-id', 'services.facebook.client_secret' => 'facebook-secret',
        ]);
    }

    /**
     * The provider sends back this person.
     */
    private function returns(string $provider, array $person, array $raw = []): void
    {
        $user = (new ProviderUser)->setRaw($raw)->map($person + ['id' => '123']);
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->once()->with($provider)->andReturn($driver);
    }

    private function comeBack(string $provider, array $query = []): TestResponse
    {
        return $this->withSession(['social_sign_in_return' => url('/properties')])
            ->get(route('auth.social.callback', ['provider' => $provider] + $query));
    }

    public function test_the_windows_buttons_link_to_the_provider_only_when_its_keys_are_set(): void
    {
        $this->get(route('home'))
            ->assertSee('href="'.route('auth.social', 'google').'"', false)
            ->assertSee('href="'.route('auth.social', 'facebook').'"', false)
            ->assertDontSee('comingSoon(&quot;Google&quot;)', false);

        config(['services.facebook.client_id' => null]);

        $this->get(route('home'))
            ->assertSee('href="'.route('auth.social', 'google').'"', false)
            ->assertDontSee('href="'.route('auth.social', 'facebook').'"', false)
            ->assertSee('comingSoon(', false);

        $this->get(route('auth.social', 'facebook'))->assertNotFound();
        $this->get(route('auth.social.callback', 'facebook'))->assertNotFound();
        $this->get('/auth/apple/redirect')->assertNotFound();
    }

    public function test_the_button_goes_to_the_provider_and_remembers_the_page_within_this_site_only(): void
    {
        $this->from(url('/projects'))->get(route('auth.social', 'google'))
            ->assertRedirectContains('accounts.google.com')
            ->assertRedirectContains(urlencode(url('/auth/google/callback')))
            ->assertSessionHas('social_sign_in_return', url('/projects'));

        $this->from('https://evil.example/page')->get(route('auth.social', 'facebook'))
            ->assertRedirectContains('facebook.com')
            ->assertRedirectContains('scope=email')
            ->assertSessionHas('social_sign_in_return', url('/'));
    }

    public function test_a_new_person_gets_an_account_signed_in_and_verified(): void
    {
        $this->returns('google', ['name' => 'Sara Ali', 'email' => 'Sara@Example.com'], ['email_verified' => true]);

        $this->withCookie('locale', 'ar')->comeBack('google')
            ->assertRedirect(url('/properties'))
            ->assertSessionHas(EnsureWebsiteUserIsActive::NOTICE);

        $user = WebsiteUser::query()->sole();
        $this->assertSame(['Sara Ali', 'sara@example.com', 'google', 'ar'], [$user->name, $user->email, $user->registration_method, $user->preferred_language]);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNotNull($user->last_login_at);
        $this->assertNull($user->password);
        $this->assertAuthenticatedAs($user, 'website');
    }

    public function test_an_e_mail_that_has_an_account_signs_in_to_it(): void
    {
        $user = WebsiteUser::factory()->create(['email' => 'sara@example.com', 'registration_method' => 'email', 'email_verified_at' => null]);
        $this->returns('facebook', ['name' => 'Another Name', 'email' => 'sara@example.com']);

        $this->comeBack('facebook')->assertRedirect(url('/properties'));

        $this->assertSame(1, WebsiteUser::query()->count());
        $user->refresh();
        $this->assertSame('email', $user->registration_method); // how it was made stays
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user, 'website');
    }

    public function test_blocked_and_deleted_accounts_are_refused(): void
    {
        WebsiteUser::factory()->create(['email' => 'blocked@example.com', 'status' => WebsiteUser::BLOCKED]);
        WebsiteUser::factory()->create(['email' => 'gone@example.com'])->delete();

        foreach (['blocked@example.com', 'gone@example.com'] as $email) {
            $this->returns('facebook', ['name' => 'X', 'email' => $email]);

            $this->comeBack('facebook')->assertSessionHas(EnsureWebsiteUserIsActive::NOTICE, __('admin.website_users.account_unavailable'));
            $this->assertGuest('website');
        }

        $this->assertSame(1, WebsiteUser::query()->count());
    }

    public function test_no_or_unverified_e_mail_is_refused(): void
    {
        $this->returns('facebook', ['name' => 'Phone Only', 'email' => null]);
        $this->comeBack('facebook')->assertSessionHas(EnsureWebsiteUserIsActive::NOTICE, 'Your Facebook account has no e-mail. Please use another way to sign in.');

        $this->returns('google', ['name' => 'Sara', 'email' => 'sara@example.com'], ['email_verified' => false]);
        $this->comeBack('google')->assertSessionHas(EnsureWebsiteUserIsActive::NOTICE, 'Your Google account has no e-mail. Please use another way to sign in.');

        $this->assertSame(0, WebsiteUser::query()->count());
        $this->assertGuest('website');
    }

    public function test_a_cancel_or_a_failed_answer_goes_back_with_a_message(): void
    {
        $this->comeBack('google', ['error' => 'access_denied'])
            ->assertRedirect(url('/properties'))
            ->assertSessionHas(EnsureWebsiteUserIsActive::NOTICE, 'Signing in with Google was cancelled.');

        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andThrow(new InvalidStateException);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->comeBack('google')
            ->assertRedirect(url('/properties'))
            ->assertSessionHas(EnsureWebsiteUserIsActive::NOTICE, "We couldn't sign you in with Google. Please try again.");

        $this->assertGuest('website');
    }
}
