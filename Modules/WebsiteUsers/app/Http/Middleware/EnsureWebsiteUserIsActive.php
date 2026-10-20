<?php

namespace Modules\WebsiteUsers\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * On every website page: a user blocked while logged in is signed out on their next request (whatever device or
 * session they use) and keeps browsing as a guest, with the neutral "account unavailable" message. Only a logged-in
 * visitor costs a query.
 */
class EnsureWebsiteUserIsActive
{
    public const GUARD = 'website';

    /** Session key of the one-time message the website layout shows. */
    public const NOTICE = 'website_notice';

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard(self::GUARD)->user();

        if ($user?->isBlocked()) {
            Auth::guard(self::GUARD)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->now(self::NOTICE, __('admin.website_users.account_unavailable'));
        }

        return $next($request);
    }
}
