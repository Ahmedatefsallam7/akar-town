<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

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
