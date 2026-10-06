<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Lookups\Models\Lookup;
use Modules\Projects\Models\Project;

/**
 * Removes everything PerformanceSeeder added.
 */
class PerformanceCleanupSeeder extends Seeder
{
    public function run(): void
    {
        $projects = DB::table('projects')->where('slug', 'like', 'perf-project-%')->select('id');
        DB::table('lookupables')->where('lookupable_type', Project::class)->whereIn('lookupable_id', $projects)->delete();
        DB::table('projects')->where('slug', 'like', 'perf-project-%')->delete();
        DB::table('developers')->where('slug', 'like', 'perf-dev-%')->delete();
        DB::table('locations')->where('slug', 'like', 'perf-loc-%')->delete();
        DB::table('users')->where('email', 'like', 'perf-user-%@example.test')->delete();
        DB::table('lookups')->where('slug', 'like', 'perf\_%')->delete();

        Lookup::fixTree();
        Cache::flush();
    }
}
