<?php

namespace App\Livewire\Website;

use App\Livewire\Website\Concerns\SignsInWithEmail;
use App\Livewire\Website\Concerns\SignsInWithWhatsApp;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Modules\WebsiteUsers\Models\WebsiteUser;

/**
 * The website's "Sign in or Register" window (the design's auth-modal): one window for both, opened by the navbar's
 * account button (window event "open-auth"). Steps: WhatsApp number, code, e-mail and password, welcome.
 *
 * The two ways in live in their own concerns: SignsInWithWhatsApp (AKAR-49) and SignsInWithEmail. This class keeps
 * what they share: the name and password fields, signing in, and the tries allowed per visitor.
 */
class AuthModal extends Component
{
    use SignsInWithEmail;
    use SignsInWithWhatsApp;

    public const GUARD = 'website';

    public string $password = '';

    public string $name = '';

    public string $passwordConfirmation = '';

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
    private function allowed(string $action, int $max, int $seconds, string $field = 'email'): bool
    {
        $key = $action.':'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, $max)) {
            $this->addError($field, __('Too many attempts. Please try again in :seconds seconds.', ['seconds' => RateLimiter::availableIn($key)]));

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
