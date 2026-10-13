<?php

namespace Modules\Lookups\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Lookups\Models\Lookup;

/**
 * Seeds every lookup type and its values from data/lookups.php (Locations is a nested tree).
 * Safe to run again: creates what's missing and never overwrites values edited in the admin.
 * Lookup types (the top level) are protected from deletion; their values aren't.
 */
class LookupsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require __DIR__.'/data/lookups.php' as $index => $type) {
            $this->seed($type, null, $index + 1);
        }
    }

    /**
     * @param  array{slug: string, name_en: string, name_ar: string, order?: int, attributes?: array, description_en?: string, description_ar?: string, items?: array}  $node
     */
    private function seed(array $node, ?Lookup $parent, int $position): void
    {
        $lookup = Lookup::firstOrCreate(
            ['slug' => $node['slug'], 'parent_id' => $parent?->id],
            [
                'name_en' => $node['name_en'],
                'name_ar' => $node['name_ar'],
                'order' => $node['order'] ?? $position,
                'attributes' => $node['attributes'] ?? null,
                'description_en' => $node['description_en'] ?? null,
                'description_ar' => $node['description_ar'] ?? null,
                'is_active' => true,
                'is_deletable' => $parent !== null,
            ],
        );

        foreach ($node['items'] ?? [] as $index => $item) {
            $this->seed($item, $lookup, $index + 1);
        }
    }
}
