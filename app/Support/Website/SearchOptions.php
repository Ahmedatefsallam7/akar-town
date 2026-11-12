<?php

namespace App\Support\Website;

use Illuminate\Support\Facades\DB;
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
     * The compounds (the locations tree's last level, AKAR-46) that hold published properties or projects, for the
     * listings' Compound filter: a compound with nothing in it isn't offered. Keyed by slug like the other filters;
     * the same slug under two areas is one choice that covers both.
     *
     * @param  'units'|'projects'  $table
     * @return array<string, string> slug => localized name
     */
    public static function compounds(string $table): array
    {
        return array_map(fn (array $compound) => $compound['name'], static::compoundsIn($table));
    }

    /**
     * The ids of the given compound slugs (unknown ones are ignored), for the search queries.
     *
     * @param  'units'|'projects'  $table
     * @param  array<mixed>|string|null  $slugs
     * @return list<int>
     */
    public static function compoundIds(string $table, array|string|null $slugs): array
    {
        $compounds = static::compoundsIn($table);

        return array_values(array_merge([], ...array_map(fn ($slug) => $compounds[$slug]['ids'], array_filter((array) $slugs, fn ($slug) => is_string($slug) && isset($compounds[$slug])))));
    }

    /**
     * Read once per request: one query for the compounds used, one for their names.
     *
     * @return array<string, array{ids: list<int>, name: string}>
     */
    private static function compoundsIn(string $table): array
    {
        return once(function () use ($table) {
            $used = DB::table($table)->where('is_published', true)->whereNull('deleted_at')->whereNotNull('sub_area_id')->distinct()->pluck('sub_area_id');

            return Lookup::query()
                ->whereIn('id', $used)
                ->where('is_active', true)
                ->orderBy('order')->orderBy('name_en')
                ->get(['id', 'slug', 'name_en', 'name_ar'])
                ->groupBy('slug')
                ->map(fn ($same) => ['ids' => $same->pluck('id')->all(), 'name' => $same->first()->getLocalized('name')])
                ->all();
        });
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
