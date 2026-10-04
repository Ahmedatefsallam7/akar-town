<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Lookups\Models\Lookup;

/**
 * Removes everything PerformanceSeeder added.
 */
class PerformanceCleanupSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->where('email', 'like', 'perf-user-%@example.test')->delete();
        DB::table('lookups')->where('slug', 'like', 'perf\_%')->delete();

        Lookup::fixTree();
        Cache::flush();
    }
}
