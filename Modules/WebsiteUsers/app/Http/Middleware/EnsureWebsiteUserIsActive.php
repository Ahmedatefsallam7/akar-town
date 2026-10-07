<?php

namespace Modules\WebsiteUsers\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * For the website's logged-in pages: a user blocked while logged in is signed out on their next request, whatever
 * device or session they use, with the neutral "account unavailable" message.
 */
class EnsureWebsiteUserIsActive
{
    public const GUARD = 'website';

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard(self::GUARD)->user();

        if ($user?->isBlocked()) {
            Auth::guard(self::GUARD)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, __('admin.website_users.account_unavailable'));
        }

        return $next($request);
    }
}
