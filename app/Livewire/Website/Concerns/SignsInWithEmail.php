<?php

namespace App\Livewire\Website\Concerns;

use App\Support\Crm\LeadContact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Locked;
use Modules\WebsiteUsers\Models\WebsiteUser;

/**
 * The sign-in window's e-mail way in (App\Livewire\Website\AuthModal): an e-mail that has an account signs in with
 * its password; a new one shows the name and password confirmation fields in the same step and creates the account;
 * "forgot password" e-mails a reset link. Uses the window's login() and allowed() (tries per visitor).
 */
trait SignsInWithEmail
{
    /** Sign-in tries per minute per visitor (IP), as the admin panel. */
    private const MAX_SIGN_INS = 5;

    /** New accounts per hour per visitor (IP). */
    private const MAX_REGISTRATIONS = 5;

    /** "Forgot password" requests per hour per visitor (IP); the broker also waits a minute between links. */
    private const MAX_RESET_LINKS = 5;

    public string $email = '';

    /** The e-mail has no account yet: the step asks for the name too. Set by the server only. */
    #[Locked]
    public bool $registering = false;

    /** Registering because the e-mail typed to sign in has no account (not from "Create an account"). */
    public bool $newEmail = false;

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
            $this->newEmail = true;
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
     * "Create an account": the e-mail step with the name and the password twice (register() checks the e-mail is new).
     */
    public function startRegistration(): void
    {
        $this->registering = true;
        $this->newEmail = false;
        $this->resetErrorBag();
    }

    /**
     * Back to e-mail and password only (the "Back" link, "Sign in" or another e-mail).
     */
    public function cancelRegistration(): void
    {
        $this->registering = false;
        $this->newEmail = false;
        $this->reset('name', 'passwordConfirmation');
        $this->resetErrorBag();
    }
}
