<?php

use App\Http\Middleware\EnsureSiteIsLive;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Modules\PossibleLeads\Support\Visitor;
use Modules\WebsiteUsers\Http\Middleware\EnsureWebsiteUserIsActive;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        $middleware->web(append: [
            SetLocale::class,
            EnsureSiteIsLive::class,
            // A website user blocked while logged in is signed out on their next page.
            EnsureWebsiteUserIsActive::class,
        ]);

        // Not encrypted: the anonymous visitor id (Possible Leads) is set by a stateless API route, and the website's
        // light / dark choice is set by the browser (Alpine) and only read to render the matching theme.
        $middleware->encryptCookies(except: [Visitor::COOKIE, 'theme']);

        // Behind a proxy or tunnel (e.g. ngrok for a client demo) its https address must be trusted: TRUSTED_PROXIES=*.
        // Empty by default, so nobody can fake their IP (rate limits) or https on a server without a proxy.
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : explode(',', $proxies));
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
