<?php

namespace App\Support\Website;

use Modules\Developers\Models\Developer;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;

/**
 * Choices for the website's search filters, from the admin's lookups (active values, in their display order).
 * Filters are keyed by slug, so URLs show names (?type[]=villa), never database ids.
 */
class SearchOptions
{
    /**
     * @return array<string, string> slug => localized name
     */
    public static function values(string $type): array
    {
        return array_map(fn (array $value) => $value['name'], static::lookups()[$type] ?? []);
    }

    /**
     * The ids of the given slugs (unknown slugs are ignored), for the search queries.
     *
     * @param  array<mixed>|string|null  $slugs
     * @return list<int>
     */
    public static function ids(string $type, array|string|null $slugs): array
    {
        $values = static::lookups()[$type] ?? [];

        return array_values(array_map(fn ($slug) => $values[$slug]['id'], array_filter((array) $slugs, fn ($slug) => is_string($slug) && isset($values[$slug]))));
    }

    /**
     * Read once per request.
     *
     * @return array<string, array<string, array{id: int, name: string}>> type => slug => value
     */
    private static function lookups(): array
    {
        return once(fn () => Lookup::query()
            ->whereNull('parent_id')
            ->whereIn('slug', ['unittype', 'finishingtype', 'amenities', 'projectstatus'])
            ->with(['children' => fn ($query) => $query->where('is_active', true)->orderBy('order')->orderBy('name_en')])
            ->get()
            ->mapWithKeys(fn (Lookup $root) => [$root->slug => $root->children->mapWithKeys(fn (Lookup $value) => [$value->slug => ['id' => $value->id, 'name' => $value->getLocalized('name')]])->all()])
            ->all());
    }

    /**
     * Active locations (the admin's order). Plain rows, not models: there can be thousands.
     *
     * @return array<string, string> slug => localized name
     */
    public static function locations(): array
    {
        return static::names(Location::query()->where('is_active', true)->orderBy('order')->orderBy('name_en'), 'slug');
    }

    /**
     * @return array<string, string> slug => localized name
     */
    public static function developers(): array
    {
        return static::names(Developer::query()->where('is_published', true)->orderBy('name_en'), 'slug');
    }

    /**
     * @return array<string, string> key => the name in the current language (English when it has none)
     */
    private static function names($query, string $key): array
    {
        $column = 'name_'.app()->getLocale();

        return $query->toBase()->get([$key, 'name_en', 'name_ar'])
            ->mapWithKeys(fn (object $row) => [$row->{$key} => $row->{$column} ?: ($row->name_en ?: $row->name_ar)])
            ->all();
    }
}
