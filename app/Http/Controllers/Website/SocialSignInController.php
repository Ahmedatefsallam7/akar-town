<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\AbstractUser as ProviderUser;
use Laravel\Socialite\Facades\Socialite;
use Modules\WebsiteUsers\Http\Middleware\EnsureWebsiteUserIsActive;
use Modules\WebsiteUsers\Models\WebsiteUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * Website sign-in with Google / Facebook (AKAR-55), the sign-in window's buttons. The provider's e-mail is the account:
 * an e-mail that has an account signs in to it (the provider proved the e-mail is theirs, so it counts as verified),
 * a new one gets an account made from the name and e-mail. Blocked or deleted accounts, and a provider account with no
 * (verified) e-mail, are refused with a message. The visitor comes back to the page they were on.
 */
class SocialSignInController extends Controller
{
    public const PROVIDERS = ['google' => 'Google', 'facebook' => 'Facebook'];

    /** Session key of the page to come back to. */
    private const RETURN_TO = 'social_sign_in_return';

    /**
     * Is this way in set up (its client id in .env)? If not, the window's button says "coming soon".
     */
    public static function enabled(string $provider): bool
    {
        return filled(config("services.{$provider}.client_id")) && filled(config("services.{$provider}.client_secret"));
    }

    public function redirect(Request $request, string $provider): SymfonyRedirect
    {
        abort_unless(self::enabled($provider), 404);

        // Back to the page the button was on, only within this site (the Referer header can point anywhere).
        $previous = url()->previous();
        $request->session()->put(self::RETURN_TO, parse_url($previous, PHP_URL_HOST) === $request->getHost() ? $previous : url('/'));

        $driver = Socialite::driver($provider);

        return ($provider === 'facebook' ? $driver->scopes(['email']) : $driver)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless(self::enabled($provider), 404);

        $back = redirect()->to($request->session()->pull(self::RETURN_TO, url('/')));
        $name = self::PROVIDERS[$provider];

        // The visitor pressed "Cancel" on the provider's page.
        if ($request->has('error')) {
            return $back->with(EnsureWebsiteUserIsActive::NOTICE, __('Signing in with :provider was cancelled.', ['provider' => $name]));
        }

        try {
            $found = Socialite::driver($provider)->user();
        } catch (Throwable $e) {
            // An old or reused link (state), or the provider refused the code: say so and let them try again.
            report($e);

            return $back->with(EnsureWebsiteUserIsActive::NOTICE, __('We couldn\'t sign you in with :provider. Please try again.', ['provider' => $name]));
        }

        $email = $this->verifiedEmail($provider, $found);
        if (! $email) {
            return $back->with(EnsureWebsiteUserIsActive::NOTICE, __('Your :provider account has no e-mail. Please use another way to sign in.', ['provider' => $name]));
        }

        $user = WebsiteUser::withTrashed()->where('email', $email)->first();

        if ($user?->trashed() || $user?->isBlocked()) {
            return $back->with(EnsureWebsiteUserIsActive::NOTICE, __('admin.website_users.account_unavailable'));
        }

        $user ??= WebsiteUser::query()->create([
            'name' => mb_substr(trim((string) $found->getName()) ?: strstr($email, '@', true), 0, 191),
            'email' => $email,
            'registration_method' => $provider,
            'preferred_language' => app()->getLocale(),
        ]);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        Auth::guard(EnsureWebsiteUserIsActive::GUARD)->login($user, remember: true);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return $back->with(EnsureWebsiteUserIsActive::NOTICE, __('Welcome, :name!', ['name' => $user->name]));
    }

    /**
     * The provider's e-mail, lower-cased, only when the provider says it's verified (Google tells; Facebook shares only
     * confirmed e-mails). Null when there's none (e.g. a Facebook account made with a phone number).
     */
    private function verifiedEmail(string $provider, ProviderUser $found): ?string
    {
        $email = mb_strtolower(trim((string) $found->getEmail()));

        if ($email === '' || mb_strlen($email) > 191 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        if ($provider === 'google' && ! filter_var($found->getRaw()['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        return $email;
    }
}
