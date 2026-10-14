<?php

namespace App\Support\Website;

use Modules\Lookups\Models\Lookup;

/**
 * Choices for the website's search filters, from the admin's lookups (active values, in their display order).
 */
class SearchOptions
{
    /**
     * @return array<int, string> id => localized name
     */
    public static function values(string $type): array
    {
        return once(fn () => Lookup::query()
            ->whereNull('parent_id')
            ->whereIn('slug', ['unittype', 'finishingtype', 'amenities', 'projectstatus'])
            ->with(['children' => fn ($query) => $query->where('is_active', true)->orderBy('order')->orderBy('name_en')])
            ->get()
            ->mapWithKeys(fn (Lookup $root) => [$root->slug => $root->children->mapWithKeys(fn (Lookup $value) => [$value->id => $value->getLocalized('name')])->all()])
            ->all())[$type] ?? [];
    }
}
