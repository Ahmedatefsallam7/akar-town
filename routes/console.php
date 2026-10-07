<?php

use App\Jobs\SendLeadToCrm;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Modules\Inquiry\Models\Inquiry;
use Modules\Inquiry\Models\Meeting;
use Modules\UnitRequests\Models\UnitRequest;
use Modules\Users\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The database cache store removes an expired entry only when it's read again, so entries that are never read
// (e.g. per-visitor rate-limit keys) would pile up forever. Clear them daily (uses the "expiration" index).
Artisan::command('cache:prune-expired', function () {
    $deleted = 0;

    foreach (['cache', 'cache_locks'] as $table) {
        $deleted += DB::table($table)->where('expiration', '<=', now()->getTimestamp())->delete();
    }

    $this->info("Removed {$deleted} expired cache entries.");
})->purpose('Delete expired rows from the database cache tables');

Schedule::command('cache:prune-expired')->daily();

// An avatar is written to disk when the form is submitted, before the record is saved; if the save then fails
// (another field invalid, page left), the file has no owner. Delete avatar files no user points to, once they're
// older than the given hours (so an upload in progress is never touched).
Artisan::command('users:prune-orphan-avatars {--hours=24}', function () {
    $disk = Storage::disk(User::AVATAR_DISK);
    $used = User::withTrashed()->whereNotNull('avatar')->pluck('avatar')->flip();
    $cutoff = now()->subHours((int) $this->option('hours'))->getTimestamp();
    $deleted = 0;

    foreach ($disk->files(User::AVATAR_DIRECTORY) as $path) {
        if (! $used->has($path) && $disk->lastModified($path) <= $cutoff) {
            $disk->delete($path);
            $deleted++;
        }
    }

    $this->info("Removed {$deleted} unused avatar files.");
})->purpose('Delete avatar files that no user uses');

Schedule::command('users:prune-orphan-avatars')->daily();

// No long-running worker on cPanel: the scheduler (cron every minute) runs the queue (e.g. CRM leads) until it's empty.
// The overlap lock expires after 5 minutes: if the host kills a worker, the queue restarts soon (the default is 24 h).
Schedule::command('queue:work --stop-when-empty --max-time=55')->everyMinute()->withoutOverlapping(5);

// Failed jobs (e.g. hourly CRM resends while the CRM is down) are kept a week for checking, then removed.
Schedule::command('queue:prune-failed --hours=168')->daily();

// Website leads the CRM never got (it was down for longer than the job's own retries): sent again every hour, for a
// few days, so no lead needs re-entering by hand. Starts an hour after the lead, once the job's retries are over.
Artisan::command('crm:send-unsent {--days=3}', function () {
    if (! SendLeadToCrm::isConfigured()) {
        $this->warn('CRM settings are missing: nothing sent.');

        return;
    }

    $queued = 0;

    foreach ([Inquiry::class, Meeting::class, UnitRequest::class] as $leads) {
        $leads::query()
            ->whereNull('crm_lead_id')
            ->whereBetween('created_at', [now()->subDays((int) $this->option('days')), now()->subHour()])
            ->chunkById(200, function ($records) use (&$queued) {
                foreach ($records as $record) {
                    // One attempt: the next try is the next hour's run.
                    $job = new SendLeadToCrm($record);
                    $job->tries = 1;
                    dispatch($job);
                    $queued++;
                }
            });
    }

    $this->info("Queued {$queued} unsent leads for the CRM.");
})->purpose('Send again the website leads the CRM never got');

Schedule::command('crm:send-unsent')->hourly();
