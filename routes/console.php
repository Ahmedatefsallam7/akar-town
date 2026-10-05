<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
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
