<?php

namespace Modules\Units\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Lookups\Support\LocationTree;
use Modules\Lookups\Support\LookupUsage;
use Modules\Units\Models\Unit;
use Nwidart\Modules\Support\ModuleServiceProvider;

class UnitsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Units';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'units';

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

        // Location levels and single choices a unit points at can't be deleted from Lookups.
        LookupUsage::register('units', [...LocationTree::LEVELS, ...array_keys(Unit::SINGLE_LOOKUPS)]);
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
