<?php

namespace Modules\Users\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use Modules\Users\Models\User;

/**
 * Filament's login page, plus a clear message for suspended accounts. The message only appears after a correct
 * password, so it never tells a stranger which emails have accounts.
 */
class Login extends BaseLogin
{
    public const SUSPENDED_FLASH = 'account_suspended';

    protected bool $isSuspended = false;

    public function mount(): void
    {
        parent::mount();

        // Sent here by App\Http\Middleware\Authenticate after being suspended while logged in.
        if (session()->pull(self::SUSPENDED_FLASH)) {
            $this->notifySuspended();
        }
    }

    protected function isUserAllowedToAccessPanel(Authenticatable $user): bool
    {
        $this->isSuspended = $user instanceof User && $user->is_suspended;

        return parent::isUserAllowedToAccessPanel($user);
    }

    protected function throwFailureValidationException(): never
    {
        if ($this->isSuspended) {
            $this->notifySuspended();

            throw ValidationException::withMessages(['data.email' => __('admin.auth.suspended')]);
        }

        parent::throwFailureValidationException();
    }

    protected function notifySuspended(): void
    {
        Notification::make()
            ->danger()
            ->title(__('admin.auth.suspended_title'))
            ->body(__('admin.auth.suspended'))
            ->persistent()
            ->send();
    }
}
