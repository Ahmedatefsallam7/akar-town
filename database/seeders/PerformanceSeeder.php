<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;
use Modules\Users\Models\User;

/**
 * Lots of data to check speed locally: 20,000 users, 10,000 lookups (50 categories x 200 items), 2,000 location
 * pages, 300 developers, 10,000 projects (each with 6 picked lookup values) and 50,000 units (3 picks each).
 * Local only. Rows are marked ("perf-" emails and slugs, "perf_" lookup slugs) so PerformanceCleanupSeeder can remove them.
 *
 *   php artisan db:seed --class=PerformanceSeeder
 *   php artisan db:seed --class=PerformanceCleanupSeeder
 */
class PerformanceSeeder extends Seeder
{
    private const USERS = 20_000;

    private const CATEGORIES = 50;

    private const ITEMS_PER_CATEGORY = 200;

    private const LOCATIONS = 2_000;

    private const DEVELOPERS = 300;

    private const PROJECTS = 10_000;

    private const UNITS = 50_000;

    private const CHUNK = 1_000;

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('PerformanceSeeder only runs in the local environment.');

            return;
        }

        $this->seedUsers();
        $this->seedLookups();
        $this->seedCatalogue();
    }

    /**
     * Location pages, developers and projects, placed in the first city/region of the real locations tree and
     * tagged with real amenities and views.
     */
    private function seedCatalogue(): void
    {
        $now = now();
        $country = Lookup::where('slug', LocationTree::ROOT_SLUG)->whereNull('parent_id')->first()?->children()->first();
        $city = $country?->children()->first();
        $region = $city?->children()->first();
        $chain = ['country_id' => $country?->id, 'city_id' => $city?->id, 'region_id' => $region?->id];
        $picks = Lookup::whereIn('parent_id', Lookup::whereNull('parent_id')->whereIn('slug', ['amenities', 'views'])->pluck('id'))
            ->get(['id', 'parent_id'])
            ->map(fn (Lookup $value) => ['id' => $value->id, 'type' => $value->parent()->value('slug')])
            ->all();

        foreach (array_chunk(range(1, self::LOCATIONS), self::CHUNK) as $numbers) {
            DB::table('locations')->insert(array_map(fn (int $n) => $chain + [
                'name_en' => "Perf Location {$n}", 'name_ar' => "موقع تجريبي {$n}", 'slug' => "perf-loc-{$n}",
                'neighbourhoods' => json_encode(['A', 'B', 'C']), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ], $numbers));
        }

        DB::table('developers')->insert(array_map(fn (int $n) => $chain + [
            'name_en' => "Perf Developer {$n}", 'name_ar' => "مطور تجريبي {$n}", 'slug' => "perf-dev-{$n}",
            'is_published' => true, 'created_at' => $now, 'updated_at' => $now,
        ], range(1, self::DEVELOPERS)));
        $developerIds = DB::table('developers')->where('slug', 'like', 'perf-dev-%')->pluck('id')->all();

        foreach (array_chunk(range(1, self::PROJECTS), self::CHUNK) as $numbers) {
            DB::table('projects')->insert(array_map(fn (int $n) => $chain + [
                'title_en' => "Perf Project {$n}", 'title_ar' => "مشروع تجريبي {$n}", 'slug' => "perf-project-{$n}",
                'developer_id' => $developerIds[$n % count($developerIds)],
                'price_from' => 1_000_000 + ($n % 200) * 50_000, 'price_to' => 3_000_000 + ($n % 200) * 80_000,
                'area_from' => 60 + $n % 300, 'area_to' => 200 + $n % 400,
                'delivery_date' => $now->copy()->addDays($n % 1500)->toDateString(),
                'is_published' => $n % 10 !== 0, 'is_featured' => $n % 20 === 0, 'created_at' => $now, 'updated_at' => $now,
            ], $numbers));
        }

        $this->seedUnits($chain, $picks);

        if ($picks === []) {
            return;
        }

        DB::table('projects')->where('slug', 'like', 'perf-project-%')->select('id')->orderBy('id')->chunk(self::CHUNK, function ($projects) use ($picks) {
            $rows = [];
            foreach ($projects as $project) {
                foreach (range(0, 5) as $i) {
                    $pick = $picks[($project->id + $i * 7) % count($picks)];
                    $rows["{$project->id}-{$pick['id']}"] = ['lookup_id' => $pick['id'], 'lookupable_type' => Project::class, 'lookupable_id' => $project->id, 'type' => $pick['type']];
                }
            }
            DB::table('lookupables')->insert(array_values($rows));
        });
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

    /**
     * Units: 4 in 5 inside a perf project, with real offering/property types; 3 picked amenities/views each.
     *
     * @param  array<string, int|null>  $chain
     * @param  array<array{id: int, type: string}>  $picks
     */
    private function seedUnits(array $chain, array $picks): void
    {
        $now = now();
        $projectIds = DB::table('projects')->where('slug', 'like', 'perf-project-%')->pluck('id')->all();
        $valuesOf = fn (string $type) => Lookup::where('parent_id', Lookup::whereNull('parent_id')->where('slug', $type)->value('id'))->pluck('id')->all() ?: [null];
        $offering = $valuesOf('offeringtype');
        $propertyType = $valuesOf('unittype');

        foreach (array_chunk(range(1, self::UNITS), self::CHUNK) as $numbers) {
            DB::table('units')->insert(array_map(fn (int $n) => $chain + [
                'name_en' => "Perf Unit {$n}", 'name_ar' => "وحدة تجريبية {$n}", 'slug' => "perf-unit-{$n}",
                'project_id' => $n % 5 === 0 ? null : $projectIds[$n % count($projectIds)],
                'offering_type_id' => $offering[$n % count($offering)], 'property_type_id' => $propertyType[$n % count($propertyType)],
                'price' => 800_000 + ($n % 400) * 25_000, 'area' => 50 + $n % 350, 'bedrooms' => $n % 6, 'bathrooms' => 1 + $n % 4,
                'is_published' => $n % 10 !== 0, 'is_featured' => $n % 25 === 0, 'created_at' => $now, 'updated_at' => $now,
            ], $numbers));
        }

        if ($picks === []) {
            return;
        }

        DB::table('units')->where('slug', 'like', 'perf-unit-%')->select('id')->orderBy('id')->chunk(self::CHUNK, function ($units) use ($picks) {
            $rows = [];
            foreach ($units as $unit) {
                foreach (range(0, 2) as $i) {
                    $pick = $picks[($unit->id + $i * 5) % count($picks)];
                    $rows["{$unit->id}-{$pick['id']}"] = ['lookup_id' => $pick['id'], 'lookupable_type' => Unit::class, 'lookupable_id' => $unit->id, 'type' => $pick['type']];
                }
            }
            DB::table('lookupables')->insert(array_values($rows));
        });
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
