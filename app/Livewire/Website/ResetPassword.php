<?php

namespace App\Livewire\Website;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\WebsiteUsers\Http\Middleware\EnsureWebsiteUserIsActive;
use Modules\WebsiteUsers\Models\WebsiteUser;

/**
 * The page the "reset your password" e-mail opens: a new password for the website account, then signed in. Uses the
 * website accounts' own reset broker (links valid 60 minutes, see config/auth.php). A blocked account can't reset.
 */
class ResetPassword extends Component
{
    #[Locked]
    public string $token = '';

    #[Locked]
    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = mb_strtolower(trim((string) request()->query('email', '')));
    }

    public function save(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'max:191', PasswordRule::min(8)],
            'passwordConfirmation' => ['required', 'same:password'],
        ], attributes: ['password' => __('Password'), 'passwordConfirmation' => __('Confirm password')]);

        $user = WebsiteUser::query()->where('email', $this->email)->first();
        if (! $user || $user->isBlocked()) {
            $this->addError('password', __('This link is invalid or has expired.'));

            return;
        }

        $status = Password::broker('website_users')->reset(
            ['email' => $this->email, 'token' => $this->token, 'password' => $this->password],
            function (WebsiteUser $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('password', __('This link is invalid or has expired.'));

            return;
        }

        Auth::guard('website')->login($user, remember: true);
        session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        session()->flash(EnsureWebsiteUserIsActive::NOTICE, __('Your password was changed. You are signed in.'));
        $this->redirectRoute('home');
    }

    public function render(): View
    {
        return view('livewire.website.reset-password');
    }
}
