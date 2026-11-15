<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Developers\Models\Developer;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;
use Modules\Users\Models\User;

/**
 * Lots of data to check speed locally: 20,000 users, 10,000 lookups (50 categories × 200 items), 200 compounds,
 * 2,000 location pages, 300 developers, 10,000 projects (6 picked lookup values each) and 50,000 units (3 picks each),
 * spread over the real locations tree. Local only; meant for a copy of the local database (akar_town_perf). Rows are
 * marked ("perf-" slugs and e-mails, "perf_" lookup slugs).
 *
 *   DB_DATABASE=akar_town_perf php artisan db:seed --class=PerformanceSeeder
 */
class PerformanceSeeder extends Seeder
{
    private const USERS = 20_000;

    private const CATEGORIES = 50;

    private const ITEMS_PER_CATEGORY = 200;

    private const COMPOUNDS = 200;

    private const LOCATIONS = 2_000;

    private const DEVELOPERS = 300;

    private const PROJECTS = 10_000;

    private const UNITS = 50_000;

    private const CHUNK = 1_000;

    /** @var list<array<string, int|null>> full place chains (country … compound) the catalogue is spread over */
    private array $chains = [];

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('PerformanceSeeder only runs in the local environment.');

            return;
        }

        $this->seedUsers();
        $this->seedLookups();
        $this->seedCompounds();
        $this->seedCatalogue();
    }

    /**
     * 200 compounds under the tree's areas (level 4), then every chain down to a compound.
     */
    private function seedCompounds(): void
    {
        $root = Lookup::where('slug', LocationTree::ROOT_SLUG)->whereNull('parent_id')->first();
        if (! $root) {
            return;
        }

        $areas = Lookup::whereDescendantOf($root)->get(['id', 'parent_id', '_lft', '_rgt'])->filter(fn (Lookup $node) => LocationTree::levelOf($node->id) === 4)->values();
        $now = now();
        DB::table('lookups')->insert(array_map(fn (int $n) => [
            'parent_id' => $areas[$n % max(1, $areas->count())]->id ?? $root->id,
            'name_en' => "Perf Compound {$n}", 'name_ar' => "كمبوند تجريبي {$n}", 'slug' => "perf-compound-{$n}",
            'order' => $n, 'is_active' => true, 'is_deletable' => true, 'is_default' => false,
            '_lft' => 0, '_rgt' => 0, 'created_at' => $now, 'updated_at' => $now,
        ], range(1, self::COMPOUNDS)));
        Lookup::fixTree();

        // Chains: each compound with its four parents.
        $nodes = Lookup::whereDescendantOf($root->fresh())->get(['id', 'parent_id'])->keyBy('id');
        foreach ($nodes as $node) {
            $chain = [];
            for ($n = $node; $n && $n->id !== $root->id; $n = $nodes->get($n->parent_id)) {
                array_unshift($chain, $n->id);
            }
            if (count($chain) === 5) {
                $this->chains[] = array_combine(LocationTree::LEVELS, $chain);
            }
        }
    }

    /**
     * Location pages, developers, projects and units, spread over the chains, tagged with real amenities and views.
     */
    private function seedCatalogue(): void
    {
        $now = now();
        $chains = $this->chains ?: [array_fill_keys(LocationTree::LEVELS, null)];
        $chain = fn (int $n) => $chains[$n % count($chains)];
        $picks = Lookup::whereIn('parent_id', Lookup::whereNull('parent_id')->whereIn('slug', ['amenities', 'views'])->pluck('id'))
            ->get(['id', 'parent_id'])
            ->map(fn (Lookup $value) => ['id' => $value->id, 'type' => $value->parent()->value('slug')])
            ->all();

        foreach (array_chunk(range(1, self::LOCATIONS), self::CHUNK) as $numbers) {
            DB::table('locations')->insert(array_map(fn (int $n) => $chain($n) + [
                'name_en' => "Perf Location {$n}", 'name_ar' => "موقع تجريبي {$n}", 'slug' => "perf-loc-{$n}", 'search_name' => Location::searchNameFor(["Perf Location {$n}", "موقع تجريبي {$n}"]),
                'neighbourhoods' => json_encode(['A', 'B', 'C']), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ], $numbers));
        }

        DB::table('developers')->insert(array_map(fn (int $n) => $chain($n) + [
            'name_en' => "Perf Developer {$n}", 'name_ar' => "مطور تجريبي {$n}", 'slug' => "perf-dev-{$n}", 'search_name' => Developer::searchNameFor(["Perf Developer {$n}", "مطور تجريبي {$n}"]),
            'is_published' => true, 'created_at' => $now, 'updated_at' => $now,
        ], range(1, self::DEVELOPERS)));
        $developerIds = DB::table('developers')->where('slug', 'like', 'perf-dev-%')->pluck('id')->all();

        foreach (array_chunk(range(1, self::PROJECTS), self::CHUNK) as $numbers) {
            DB::table('projects')->insert(array_map(fn (int $n) => $chain($n) + [
                'title_en' => "Perf Project {$n}", 'title_ar' => "مشروع تجريبي {$n}", 'slug' => "perf-project-{$n}", 'search_name' => Project::searchNameFor(["Perf Project {$n}", "مشروع تجريبي {$n}"]),
                'developer_id' => $developerIds[$n % count($developerIds)],
                'price_from' => 1_000_000 + ($n % 200) * 50_000, 'price_to' => 3_000_000 + ($n % 200) * 80_000,
                'area_from' => 60 + $n % 300, 'area_to' => 200 + $n % 400,
                'down_payment' => 5 + $n % 20, 'installment_years_from' => 3, 'installment_years_to' => 6 + $n % 5,
                'delivery_date' => $now->copy()->addDays($n % 1500)->toDateString(),
                'is_published' => $n % 10 !== 0, 'is_featured' => $n % 20 === 0, 'created_at' => $now, 'updated_at' => $now,
            ], $numbers));
        }

        $this->seedUnits($chain, $picks);
        $this->pick('projects', 'perf-project-%', Project::class, 6, 7, $picks);
    }

    private function seedUsers(): void
    {
        $roleIds = Lookup::roles()->where('slug', '!=', User::ADMIN_ROLE)->pluck('id')->all();
        $password = Hash::make('password');
        $now = now();

        foreach (array_chunk(range(1, self::USERS), self::CHUNK) as $numbers) {
            DB::table('users')->insert(array_map(fn (int $n) => [
                'first_name' => "Perf{$n}", 'last_name' => 'User', 'username' => "perf-user-{$n}", 'email' => "perf-user-{$n}@example.test",
                'phone' => '+2010'.str_pad((string) $n, 8, '0', STR_PAD_LEFT), 'password' => $password,
                'role_id' => $roleIds[$n % count($roleIds)] ?? null, 'is_suspended' => $n % 25 === 0,
                'created_at' => $now, 'updated_at' => $now,
            ], $numbers));
        }
    }

    /**
     * Units: 4 in 5 inside a perf project (with its place), the rest on their own; real offering, unit type, finishing
     * and status values; 3 picked amenities/views each.
     *
     * @param  callable(int): array<string, int|null>  $chain
     * @param  array<array{id: int, type: string}>  $picks
     */
    private function seedUnits(callable $chain, array $picks): void
    {
        $now = now();
        $projects = DB::table('projects')->where('slug', 'like', 'perf-project-%')->get(['id', ...LocationTree::LEVELS])->all();
        $valuesOf = fn (string $type) => Lookup::where('parent_id', Lookup::whereNull('parent_id')->where('slug', $type)->value('id'))->pluck('id')->all() ?: [null];
        $offering = $valuesOf('offeringtype');
        $unitType = $valuesOf('unittype');
        $finishing = $valuesOf('finishingtype');
        $status = $valuesOf('unitstatus');

        foreach (array_chunk(range(1, self::UNITS), self::CHUNK) as $numbers) {
            DB::table('units')->insert(array_map(function (int $n) use ($chain, $projects, $offering, $unitType, $finishing, $status, $now) {
                $project = $n % 5 === 0 ? null : $projects[$n % count($projects)];
                $place = $project ? array_intersect_key((array) $project, array_flip(LocationTree::LEVELS)) : $chain($n);

                return $place + [
                    'name_en' => "Perf Unit {$n}", 'name_ar' => "وحدة تجريبية {$n}", 'slug' => "perf-unit-{$n}", 'search_name' => Unit::searchNameFor(["Perf Unit {$n}", "وحدة تجريبية {$n}"]),
                    'project_id' => $project?->id,
                    'offering_type_id' => $offering[$n % count($offering)], 'property_type_id' => $unitType[$n % count($unitType)],
                    'finishing_type_id' => $finishing[$n % count($finishing)], 'unit_status_id' => $status[$n % count($status)],
                    'price' => 800_000 + ($n % 400) * 25_000, 'area' => 50 + $n % 350, 'bedrooms' => $n % 6, 'bathrooms' => 1 + $n % 4,
                    'floor' => $n % 20, 'down_payment' => 10 + $n % 15, 'installment_years_from' => 3, 'installment_years_to' => 7,
                    'delivery_date' => $now->copy()->addDays($n % 1200)->toDateString(),
                    'is_published' => $n % 10 !== 0, 'is_featured' => $n % 25 === 0, 'created_at' => $now, 'updated_at' => $now,
                ];
            }, $numbers));
        }

        $this->pick('units', 'perf-unit-%', Unit::class, 3, 5, $picks);
    }

    /**
     * Picked lookup values (amenities, views) for the perf rows of a table.
     *
     * @param  array<array{id: int, type: string}>  $picks
     */
    private function pick(string $table, string $slugs, string $model, int $count, int $step, array $picks): void
    {
        if ($picks === []) {
            return;
        }

        DB::table($table)->where('slug', 'like', $slugs)->select('id')->orderBy('id')->chunk(self::CHUNK, function ($rows) use ($model, $count, $step, $picks) {
            $insert = [];
            foreach ($rows as $row) {
                foreach (range(0, $count - 1) as $i) {
                    $pick = $picks[($row->id + $i * $step) % count($picks)];
                    $insert["{$row->id}-{$pick['id']}"] = ['lookup_id' => $pick['id'], 'lookupable_type' => $model, 'lookupable_id' => $row->id, 'type' => $pick['type']];
                }
            }
            DB::table('lookupables')->insert(array_values($insert));
        });
    }

    private function seedLookups(): void
    {
        $now = now();

        foreach (range(1, self::CATEGORIES) as $c) {
            $category = Lookup::create(['name_en' => "Perf Category {$c}", 'name_ar' => "تصنيف تجريبي {$c}", 'slug' => "perf_category_{$c}"]);

            // Bulk insert the items, then rebuild the nested-set columns once.
            DB::table('lookups')->insert(array_map(fn (int $i) => [
                'parent_id' => $category->id, 'name_en' => "Perf Item {$c}-{$i}", 'name_ar' => "عنصر تجريبي {$c}-{$i}",
                'slug' => "perf_item_{$c}_{$i}", 'order' => $i, 'is_active' => true, 'is_deletable' => true, 'is_default' => false,
                '_lft' => 0, '_rgt' => 0, 'created_at' => $now, 'updated_at' => $now,
            ], range(1, self::ITEMS_PER_CATEGORY)));
        }

        Lookup::fixTree();
    }
}
