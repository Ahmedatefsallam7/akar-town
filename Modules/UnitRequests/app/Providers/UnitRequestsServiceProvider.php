<?php

namespace Modules\UnitRequests\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Lookups\Support\LocationTree;
use Modules\Lookups\Support\LookupUsage;
use Nwidart\Modules\Support\ModuleServiceProvider;

class UnitRequestsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'UnitRequests';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'unitrequests';

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

        // Location levels a request points at can't be deleted from Lookups (its picked values are in "lookupables").
        LookupUsage::register('unit_requests', LocationTree::LEVELS);
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
