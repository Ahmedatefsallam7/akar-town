<?php

namespace Modules\Projects\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Lookups\Support\LocationTree;
use Modules\Lookups\Support\LookupUsage;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ProjectsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Projects';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'projects';

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

        // A city, currency… that a project points at can't be deleted from Lookups.
        LookupUsage::register('projects', [...LocationTree::LEVELS, 'currency_id']);
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
