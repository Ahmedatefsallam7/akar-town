<?php

namespace Modules\WebsiteUsers\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Modules\WebsiteUsers\Http\Middleware\EnsureWebsiteUserIsActive;
use Modules\WebsiteUsers\Support\WhatsApp\CodeSender;
use Modules\WebsiteUsers\Support\WhatsApp\LogCodeSender;
use Modules\WebsiteUsers\Support\WhatsApp\MetaCodeSender;
use Nwidart\Modules\Support\ModuleServiceProvider;

class WebsiteUsersServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'WebsiteUsers';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'websiteusers';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // WhatsApp sign-in codes: logged locally, sent with the WhatsApp Cloud API once configured (AKAR-49).
        $this->app->bind(CodeSender::class, fn () => config('services.whatsapp.driver') === 'meta'
            ? new MetaCodeSender(config('services.whatsapp'))
            : new LogCodeSender);
    }

    public function boot(): void
    {
        parent::boot();

        // For the website's logged-in routes: Route::middleware(['auth:website', 'website.active']).
        $this->app->make(Router::class)->aliasMiddleware('website.active', EnsureWebsiteUserIsActive::class);
    }

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
