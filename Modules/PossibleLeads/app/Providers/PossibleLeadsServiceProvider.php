<?php

namespace Modules\PossibleLeads\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Inquiry\Models\Inquiry;
use Modules\Inquiry\Models\Meeting;
use Modules\PossibleLeads\Support\MarksPossibleLeadsConverted;
use Modules\UnitRequests\Models\UnitRequest;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PossibleLeadsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'PossibleLeads';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'possibleleads';

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

        // A sent lead converts the same visitor's earlier clicks.
        foreach ([Inquiry::class, Meeting::class, UnitRequest::class] as $lead) {
            $lead::created(fn ($record) => MarksPossibleLeadsConverted::handle($record));
        }
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
