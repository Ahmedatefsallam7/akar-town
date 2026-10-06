<?php

namespace Modules\Lookups\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Lookups\Support\LookupOptions;
use Modules\Lookups\Support\LookupUsage;
use Nwidart\Modules\Support\ModuleServiceProvider;

class LookupsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Lookups';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'lookups';

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

        // Lookup values for selects and checkbox lists: loaded once per request.
        $this->app->scoped(LookupOptions::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Values picked on records (a project's amenities…) can't be deleted from Lookups.
        LookupUsage::register('lookupables', ['lookup_id']);
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
