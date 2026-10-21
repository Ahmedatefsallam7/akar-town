<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
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
     * Livewire has one update URL for the whole site, so only let through requests sent from an admin page by a
     * logged-in admin user, or from the admin's sign-in pages (login, password reset) before signing in. A public form
     * left open in a browser can't keep submitting while the site is offline, even with a faked Referer header.
     */
    private function isAdminLivewireRequest(Request $request): bool
    {
        if (! $request->is('livewire*')) {
            return false;
        }

        $from = parse_url((string) $request->headers->get('referer'), PHP_URL_PATH) ?? '';

        return ($from === '/admin' || str_starts_with($from, '/admin/')) && (auth('web')->check() || $this->onlyAdminSignIn($request));
    }

    /**
     * Every component in the request is one of the admin panel's sign-in pages (login, password reset). The name comes
     * from the component's snapshot, which Livewire signs: a changed name is refused by Livewire right after this.
     */
    private function onlyAdminSignIn(Request $request): bool
    {
        $components = $request->input('components');
        if (! is_array($components) || $components === []) {
            return false;
        }

        $panel = Filament::getPanel('admin');
        $pages = array_filter([$panel->getLoginRouteAction(), $panel->getRequestPasswordResetRouteAction(), $panel->getResetPasswordRouteAction()], 'is_string');

        foreach ($components as $component) {
            $name = json_decode((string) ($component['snapshot'] ?? ''), true)['memo']['name'] ?? null;
            if (! is_string($name) || ! in_array(rescue(fn () => app('livewire.factory')->resolveComponentClass($name), report: false), $pages, true)) {
                return false;
            }
        }

        return true;
    }
}
