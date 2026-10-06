<?php

namespace Modules\Users\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\NoPendingMigrations;
use Illuminate\Support\Facades\Event;
use Modules\Users\Support\PermissionModules;
use Nwidart\Modules\Support\ModuleServiceProvider;

class UsersServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Users';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'users';

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

    public function boot(): void
    {
        parent::boot();

        // A module added in a new release is given to the Admin role once, when the release is migrated (also when
        // the release has no new migrations).
        Event::listen(function (MigrationsEnded|NoPendingMigrations $event) {
            if ($event->method === 'up') {
                PermissionModules::grantNewModulesToAdmin();
            }
        });
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
