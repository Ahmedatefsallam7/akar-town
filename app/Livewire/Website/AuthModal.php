<?php

namespace App\Livewire\Website;

use App\Support\Crm\LeadContact;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\WebsiteUsers\Models\WebsiteUser;

/**
 * The website's "Sign in or Register" window (the design's auth-modal): one window for both, opened by the navbar's
 * account button (window event "open-auth"). Steps: WhatsApp number, code, e-mail and password, welcome.
 *
 * E-mail: an e-mail that has an account signs in with its password; a new one shows the name and password
 * confirmation fields in the same step and creates the account.
 */
class AuthModal extends Component
{
    /** The design's country codes for the WhatsApp number. */
    public const COUNTRIES = [
        ['EG', '🇪🇬', '+20', 'Egypt'],
        ['SA', '🇸🇦', '+966', 'Saudi Arabia'],
        ['AE', '🇦🇪', '+971', 'UAE'],
        ['KW', '🇰🇼', '+965', 'Kuwait'],
        ['QA', '🇶🇦', '+974', 'Qatar'],
        ['GB', '🇬🇧', '+44', 'United Kingdom'],
    ];

    public const GUARD = 'website';

    /** Sign-in tries per minute per visitor (IP), as the admin panel. */
    private const MAX_SIGN_INS = 5;

    /** New accounts per hour per visitor (IP). */
    private const MAX_REGISTRATIONS = 5;

    /** "Forgot password" requests per hour per visitor (IP); the broker also waits a minute between links. */
    private const MAX_RESET_LINKS = 5;

    public string $email = '';

    public string $password = '';

    public string $name = '';

    public string $passwordConfirmation = '';

    /** The e-mail has no account yet: the step asks for the name too. Set by the server only. */
    #[Locked]
    public bool $registering = false;

    /** "Forgot password": the link was asked for (the same message whether the e-mail has an account or not). */
    #[Locked]
    public bool $resetSent = false;

    /**
     * The e-mail step's button: signs in, or (new e-mail) switches the step to "create an account".
     */
    public function signIn(): void
    {
        $this->email = mb_strtolower(trim($this->email));

        $this->validate([
            'email' => ['required', 'max:191', ...LeadContact::emailRules()],
            'password' => ['required', 'string', 'max:191'],
        ], attributes: ['email' => __('Email'), 'password' => __('Password')]);

        if (! $this->allowed('website-sign-in', self::MAX_SIGN_INS, 60)) {
            return;
        }

        $user = WebsiteUser::query()->where('email', $this->email)->first();

        if (! $user) {
            $this->registering = true;
            $this->resetErrorBag();

            return;
        }

        if (! Auth::guard(self::GUARD)->validate(['email' => $this->email, 'password' => $this->password])) {
            $this->addError('password', __('These details are incorrect.'));

            return;
        }

        if ($user->isBlocked()) {
            $this->addError('email', __('admin.website_users.account_unavailable'));

            return;
        }

        $this->login($user);
    }

    /**
     * Creates the account of a new e-mail, then signs it in.
     */
    public function register(): void
    {
        $this->email = mb_strtolower(trim($this->email));

        $this->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'max:191', ...LeadContact::emailRules(), Rule::unique('website_users', 'email')],
            'password' => ['required', 'string', 'max:191', Password::min(8)],
            'passwordConfirmation' => ['required', 'same:password'],
        ], attributes: [
            'name' => __('Full name'),
            'email' => __('Email'),
            'password' => __('Password'),
            'passwordConfirmation' => __('Confirm password'),
        ]);

        if (! $this->allowed('website-register', self::MAX_REGISTRATIONS, 3600)) {
            return;
        }

        $user = WebsiteUser::query()->create([
            'name' => trim($this->name),
            'email' => $this->email,
            'password' => $this->password,
            'registration_method' => 'email',
            'preferred_language' => app()->getLocale(),
        ]);

        // "Verify your e-mail" link; signing in doesn't wait for it. A mail server problem never stops the sign-up.
        rescue(fn () => $user->sendEmailVerificationNotification());

        $this->login($user);
    }

    /**
     * "Forgot password": e-mails a reset link (valid 60 minutes) to an active account. The answer is the same for any
     * e-mail, so nobody can find out who has an account.
     */
    public function sendResetLink(): void
    {
        $this->email = mb_strtolower(trim($this->email));

        $this->validate(['email' => ['required', 'max:191', ...LeadContact::emailRules()]], attributes: ['email' => __('Email')]);

        if (! $this->allowed('website-reset-link', self::MAX_RESET_LINKS, 3600)) {
            return;
        }

        $user = WebsiteUser::query()->where('email', $this->email)->first();
        if ($user && ! $user->isBlocked()) {
            rescue(fn () => PasswordBroker::broker('website_users')->sendResetLink(['email' => $this->email]));
        }

        $this->resetSent = true;
    }

    /**
     * Leaves "forgot password" (back to e-mail and password).
     */
    public function cancelReset(): void
    {
        $this->resetSent = false;
        $this->resetErrorBag();
    }

    /**
     * Back to e-mail and password only (the "Back" link or another e-mail).
     */
    public function cancelRegistration(): void
    {
        $this->registering = false;
        $this->reset('name', 'passwordConfirmation');
        $this->resetErrorBag();
    }

    private function login(WebsiteUser $user): void
    {
        Auth::guard(self::GUARD)->login($user, remember: true);
        session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        $this->reset('password', 'passwordConfirmation', 'name');
        $this->registering = false;
        // The window shows the welcome step, then the page reloads (navbar, forms) signed in.
        $this->dispatch('signed-in', name: $user->name);
    }

    /**
     * Counts a try; false (with the "too many" message) once the visitor reached the limit.
     */
    private function allowed(string $action, int $max, int $seconds): bool
    {
        $key = $action.':'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, $max)) {
            $this->addError('email', __('Too many attempts. Please try again in :seconds seconds.', ['seconds' => RateLimiter::availableIn($key)]));

            return false;
        }
        RateLimiter::hit($key, $seconds);

        return true;
    }

    public function render(): View
    {
        return view('livewire.website.auth-modal');
    }
}
