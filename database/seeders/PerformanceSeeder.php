<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Models\User;

/**
 * Lots of data to check speed locally: 20,000 users and 10,000 lookups (50 categories x 200 items).
 * Local only. Rows are marked ("perf-" emails, "perf_" slugs) so PerformanceCleanupSeeder can remove them.
 *
 *   php artisan db:seed --class=PerformanceSeeder
 *   php artisan db:seed --class=PerformanceCleanupSeeder
 */
class PerformanceSeeder extends Seeder
{
    private const USERS = 20_000;

    private const CATEGORIES = 50;

    private const ITEMS_PER_CATEGORY = 200;

    private const CHUNK = 1_000;

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('PerformanceSeeder only runs in the local environment.');

            return;
        }

        $this->seedUsers();
        $this->seedLookups();
    }

    private function seedUsers(): void
    {
        $roleIds = Lookup::roles()->where('slug', '!=', User::ADMIN_ROLE)->pluck('id')->all();
        $password = Hash::make('password');
        $now = now();

        foreach (array_chunk(range(1, self::USERS), self::CHUNK) as $numbers) {
            DB::table('users')->insert(array_map(fn (int $n) => [
                'first_name' => "Perf{$n}",
                'last_name' => 'User',
                'username' => "perf-user-{$n}",
                'email' => "perf-user-{$n}@example.test",
                'phone' => '+2010'.str_pad((string) $n, 8, '0', STR_PAD_LEFT),
                'password' => $password,
                'role_id' => $roleIds[$n % count($roleIds)] ?? null,
                'is_suspended' => $n % 25 === 0,
                'created_at' => $now,
                'updated_at' => $now,
            ], $numbers));
        }
    }

    private function seedLookups(): void
    {
        $now = now();

        foreach (range(1, self::CATEGORIES) as $c) {
            $category = Lookup::create([
                'name_en' => "Perf Category {$c}",
                'name_ar' => "تصنيف تجريبي {$c}",
                'slug' => "perf_category_{$c}",
            ]);

            // Bulk insert the items, then rebuild the nested-set columns once.
            DB::table('lookups')->insert(array_map(fn (int $i) => [
                'parent_id' => $category->id,
                'name_en' => "Perf Item {$c}-{$i}",
                'name_ar' => "عنصر تجريبي {$c}-{$i}",
                'slug' => "perf_item_{$c}_{$i}",
                'order' => $i,
                'is_active' => true,
                'is_deletable' => true,
                'is_default' => false,
                '_lft' => 0,
                '_rgt' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ], range(1, self::ITEMS_PER_CATEGORY)));
        }

        Lookup::fixTree();
    }
}
