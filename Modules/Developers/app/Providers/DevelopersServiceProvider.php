<?php

namespace Modules\Developers\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Lookups\Support\LocationTree;
use Modules\Lookups\Support\LookupUsage;
use Nwidart\Modules\Support\ModuleServiceProvider;

class DevelopersServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Developers';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'developers';

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

        // A city, area… that a developer's head office points at can't be deleted from Lookups.
        LookupUsage::register('developers', LocationTree::LEVELS);
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
