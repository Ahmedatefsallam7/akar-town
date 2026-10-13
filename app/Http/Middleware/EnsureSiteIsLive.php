<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\GeneralSettings\Settings\SiteSettings;
use Symfony\Component\HttpFoundation\Response;

/**
 * While the site is Offline (dashboard switch), visitors get the maintenance page. The admin panel, its language
 * switch and its Livewire requests keep working (as Dastan's CheckMaintenanceMode).
 */
class EnsureSiteIsLive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin', 'admin/*', 'locale/*', 'up') || $this->isAdminLivewireRequest($request) || app(SiteSettings::class)->is_live) {
            return $next($request);
        }

        return response()->view('errors.site-offline', status: 503);
    }

    /**
     * Livewire has one update URL for the whole site, so only let through requests sent from an admin page (a public
     * form left open in a browser can't keep submitting while the site is offline).
     */
    private function isAdminLivewireRequest(Request $request): bool
    {
        if (! $request->is('livewire*')) {
            return false;
        }

        $from = parse_url((string) $request->headers->get('referer'), PHP_URL_PATH) ?? '';

        return $from === '/admin' || str_starts_with($from, '/admin/');
    }
}
