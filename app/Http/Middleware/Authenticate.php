<?php

namespace App\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Modules\Users\Filament\Auth\Login;

/**
 * Filament's Authenticate, plus: a user suspended while logged in is logged out on their next request and sent to
 * the login page, which explains why (instead of Filament's 403 page).
 */
class Authenticate extends FilamentAuthenticate
{
    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        if (Filament::auth()->user()?->is_suspended) {
            Filament::auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put(Login::SUSPENDED_FLASH, true);

            $this->unauthenticated($request, $guards);
        }

        parent::authenticate($request, $guards);
    }
}
