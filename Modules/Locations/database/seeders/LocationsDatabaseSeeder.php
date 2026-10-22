<?php

namespace Modules\Locations\Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Modules\Locations\Models\Location;
use Modules\Lookups\Database\Seeders\LookupsDatabaseSeeder;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;

/**
 * Location landing pages (from Dastan's seeder, only the places that exist in our Lookups locations tree), each tied
 * to the place with the same slug, with every level above it filled in. A page whose place is missing is skipped.
 * Safe to run again: creates what's missing and never overwrites pages edited in the admin. No images.
 * Locally it then adds the demo data (LocationsDemoSeeder: photos, texts, SEO…).
 */
class LocationsDatabaseSeeder extends Seeder
{
    use SeedsDemoData;

    private const PAGES = [
        // ─── Cities ─────────────────────────────────────────────────────
        ['slug' => 'cairo', 'name_en' => 'Cairo', 'name_ar' => 'القاهرة', 'order' => 1, 'is_featured' => true],
        ['slug' => 'giza', 'name_en' => 'Giza', 'name_ar' => 'الجيزة', 'order' => 2, 'is_featured' => true],
        [
            'slug' => 'north-coast', 'name_en' => 'North Coast', 'name_ar' => 'الساحل الشمالي', 'order' => 3, 'is_featured' => true,
            'description_en' => 'Egypt\'s finest summer destination with turquoise waters.',
            'description_ar' => 'أفضل وجهة صيفية في مصر بمياهها الفيروزية.',
        ],
        ['slug' => 'new-administrative-capital', 'name_en' => 'New Administrative Capital', 'name_ar' => 'العاصمة الإدارية الجديدة', 'order' => 4],

        // ─── Regions ────────────────────────────────────────────────────
        [
            'slug' => 'new-cairo', 'name_en' => 'New Cairo', 'name_ar' => 'القاهرة الجديدة', 'order' => 1, 'is_featured' => true,
            'description_en' => 'A world-class residential and commercial hub in Egypt.',
            'description_ar' => 'مركز سكني وتجاري عالمي المستوى في مصر.',
        ],
        ['slug' => 'nasr-city', 'name_en' => 'Nasr City', 'name_ar' => 'مدينة نصر', 'order' => 2, 'is_featured' => true],
        ['slug' => 'heliopolis', 'name_en' => 'Heliopolis', 'name_ar' => 'مصر الجديدة', 'order' => 3, 'is_featured' => true],
        ['slug' => 'maadi', 'name_en' => 'Maadi', 'name_ar' => 'المعادي', 'order' => 4],
        ['slug' => '6th-of-october', 'name_en' => '6th of October', 'name_ar' => '6 أكتوبر', 'order' => 1, 'is_featured' => true],
        ['slug' => 'ras-el-hekma', 'name_en' => 'Ras El Hekma', 'name_ar' => 'رأس الحكمة', 'order' => 1, 'is_featured' => true],
        ['slug' => 'marina', 'name_en' => 'Marina', 'name_ar' => 'مارينا', 'order' => 2],
        ['slug' => 'r1-residential-district', 'name_en' => 'R1 Residential District', 'name_ar' => 'R1 الحي السكني', 'order' => 1],

        // ─── Areas under New Cairo ──────────────────────────────────────
        ['slug' => 'fifth-settlement', 'name_en' => 'Fifth Settlement', 'name_ar' => 'التجمع الخامس', 'is_featured' => true],
        ['slug' => 'golden-square', 'name_en' => 'Golden Square', 'name_ar' => 'الجولدن سكوير', 'is_featured' => true],
        ['slug' => 'first-settlement', 'name_en' => 'First Settlement', 'name_ar' => 'التجمع الأول'],

        // ─── Areas under Nasr City ──────────────────────────────────────
        ['slug' => 'abbas-el-akkad', 'name_en' => 'Abbas El Akkad', 'name_ar' => 'عباس العقاد'],
        ['slug' => 'el-hegaz-square', 'name_en' => 'El Hegaz Square', 'name_ar' => 'ميدان الحجاز'],

        // ─── Areas under 6th of October ─────────────────────────────────
        ['slug' => 'sheikh-zayed', 'name_en' => 'Sheikh Zayed', 'name_ar' => 'الشيخ زايد', 'is_featured' => true],
        ['slug' => 'hadayek-october', 'name_en' => 'Hadayek October', 'name_ar' => 'حدائق أكتوبر'],

        // ─── Areas under Marina ─────────────────────────────────────────
        ['slug' => 'marina-1', 'name_en' => 'Marina 1', 'name_ar' => 'مارينا 1'],
        ['slug' => 'marina-5', 'name_en' => 'Marina 5', 'name_ar' => 'مارينا 5'],

        // ─── Areas under Ras El Hekma ───────────────────────────────────
        ['slug' => 'fouka-bay', 'name_en' => 'Fouka Bay', 'name_ar' => 'فوكة باي', 'is_featured' => true],
        ['slug' => 'marassi', 'name_en' => 'Marassi', 'name_ar' => 'مراسي'],

        // ─── Areas under Heliopolis ─────────────────────────────────────
        ['slug' => 'roxy', 'name_en' => 'Roxy', 'name_ar' => 'روكسي'],
        ['slug' => 'korba', 'name_en' => 'Korba', 'name_ar' => 'الكوربة'],

        // ─── Areas under Maadi ──────────────────────────────────────────
        ['slug' => 'old-maadi', 'name_en' => 'Old Maadi', 'name_ar' => 'المعادي القديمة'],
        ['slug' => 'digla', 'name_en' => 'Digla', 'name_ar' => 'دجلة'],

        // ─── Areas under R1 Residential District ────────────────────────
        ['slug' => 'r1-villas-district', 'name_en' => 'R1 Villas District', 'name_ar' => 'R1 - منطقة الفيلات'],
    ];

    public function run(): void
    {
        // Locally (demo) the places come first, so this seeder also works alone.
        if (app()->isLocal()) {
            $this->needs(LookupsDatabaseSeeder::class);
        }

        $root = Lookup::whereNull('parent_id')->where('slug', LocationTree::ROOT_SLUG)->first();

        if (! $root) {
            $this->command?->warn('No locations tree in Lookups: run the Lookups seeder first.');

            return;
        }

        $places = Lookup::whereDescendantOf($root)->get(['id', 'parent_id', 'slug', '_lft', '_rgt'])->keyBy('slug');

        foreach (self::PAGES as $page) {
            $place = $places->get($page['slug']);

            if (! $place) {
                $this->command?->warn("Place '{$page['slug']}' isn't in the locations tree - skipped '{$page['name_en']}'.");

                continue;
            }

            Location::firstOrCreate(['slug' => $page['slug']], [
                ...$this->chain($place, $places->keyBy('id')),
                'name_en' => $page['name_en'],
                'name_ar' => $page['name_ar'],
                'description_en' => $page['description_en'] ?? null,
                'description_ar' => $page['description_ar'] ?? null,
                'order' => $page['order'] ?? 0,
                'is_featured' => $page['is_featured'] ?? false,
                'is_active' => true,
            ]);
        }

        if (app()->isLocal()) {
            $this->call(LocationsDemoSeeder::class);
        }
    }

    /**
     * The place and every level above it as country_id … sub_area_id.
     *
     * @return array<string, int>
     */
    private function chain(Lookup $place, $byId): array
    {
        $path = [];

        for ($node = $place; $node && $byId->has($node->id); $node = $byId->get($node->parent_id)) {
            array_unshift($path, $node->id);
        }

        return array_combine(array_slice(LocationTree::LEVELS, 0, count($path)), $path);
    }
}
