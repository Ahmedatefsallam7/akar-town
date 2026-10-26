<?php

namespace App\Livewire\Website;

use App\Support\Crm\LeadContact;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Modules\WebsiteUsers\Models\WebsiteUser;

/**
 * The account page's "My details" and "Password" forms for the signed-in website user. A new e-mail is unverified
 * until its link is opened (the link is sent to it); e-mail and phone stay unique (team lead).
 */
class AccountSettings extends Component
{
    /** Password checks allowed per account per minute (guessing the current password). */
    private const MAX_PASSWORD_TRIES = 5;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    /** Which form was just saved ("details" / "password"), for its message. */
    public string $saved = '';

    public function mount(): void
    {
        $user = $this->user();
        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->phone = (string) $user->phone;
    }

    private function user(): WebsiteUser
    {
        // Every call runs signed in only (the page and Livewire's requests check the website guard).
        return auth('website')->user() ?? abort(403);
    }

    public function saveDetails(): void
    {
        $user = $this->user();
        // "0100 123-4567" and "01001234567" are one number: separators go before the checks and the save.
        $this->phone = preg_replace('/[\s\-.\/()]+/', '', $this->phone);
        $this->email = Str::lower(trim($this->email));

        $this->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'max:191', ...LeadContact::emailRules(), Rule::unique('website_users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', ...LeadContact::phoneRules(), Rule::unique('website_users', 'phone')->ignore($user->id)],
        ], attributes: ['name' => __('Full name'), 'email' => __('Email'), 'phone' => __('Phone')]);

        $emailChanged = $this->email !== Str::lower((string) $user->email);
        $user->fill(['name' => trim($this->name), 'email' => $this->email, 'phone' => $this->phone ?: null]);
        if ($emailChanged) {
            // The new address is checked like at sign-up: unverified until its link is opened.
            $user->email_verified_at = null;
        }
        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        $this->saved = $emailChanged ? 'email' : 'details';
    }

    public function savePassword(): void
    {
        $user = $this->user();

        $this->validate([
            'currentPassword' => ['required', 'string', 'max:191'],
            'password' => ['required', 'string', 'max:191', Password::min(8), 'different:currentPassword'],
            'passwordConfirmation' => ['required', 'same:password'],
        ], attributes: [
            'currentPassword' => __('Current password'),
            'password' => __('New password'),
            'passwordConfirmation' => __('Confirm password'),
        ]);

        $key = 'account-password:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, self::MAX_PASSWORD_TRIES)) {
            $this->addError('currentPassword', __('Too many requests. Please try again later.'));

            return;
        }

        if (! Hash::check($this->currentPassword, (string) $user->password)) {
            RateLimiter::hit($key, 60);
            $this->addError('currentPassword', __('The current password is incorrect.'));

            return;
        }
        RateLimiter::clear($key);

        // A new "remember me" token signs out the other browsers that were remembered.
        $user->forceFill(['password' => $this->password, 'remember_token' => Str::random(60)])->save();

        $this->reset('currentPassword', 'password', 'passwordConfirmation');
        $this->saved = 'password';
    }

    public function render(): View
    {
        return view('livewire.website.account-settings', ['verified' => $this->user()->hasVerifiedEmail()]);
    }
}
