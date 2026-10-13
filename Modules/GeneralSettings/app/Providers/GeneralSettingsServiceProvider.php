<?php

namespace Modules\GeneralSettings\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Spatie\LaravelSettings\Support\SettingsCacheFactory;

class GeneralSettingsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'GeneralSettings';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'generalsettings';

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

        // Settings are cached; a migration that adds or changes one would leave the old copy in the cache (a missing
        // property breaks the page). So every migrate run clears it.
        Event::listen(function (MigrationsEnded $event) {
            foreach (app(SettingsCacheFactory::class)->all() as $cache) {
                $cache->clear();
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
