<?php

namespace App\Support\Website;

use Illuminate\Support\Facades\Cache;
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
     * Of these compounds, the ones that hold published records, from the kept list (no query).
     *
     * @param  'units'|'projects'  $table
     * @param  list<int>  $compounds
     * @return list<int>
     */
    public static function compoundsUsedOf(string $table, array $compounds): array
    {
        return $compounds === [] ? [] : array_values(array_intersect($compounds, array_merge([], ...array_column(static::compoundsIn($table), 'ids'))));
    }

    /** How long the compounds of a listing are kept (also cleared when a property or a project changes). */
    private const COMPOUNDS_MINUTES = 10;

    /**
     * The compounds used, kept a while for every visitor (one query, read from an index, then cached: it was ~90 ms
     * with 50,000 properties on every listing page), localized per request.
     *
     * @param  'units'|'projects'  $table
     * @return array<string, array{ids: list<int>, name: string}>
     */
    private static function compoundsIn(string $table): array
    {
        return once(function () use ($table) {
            $rows = Cache::remember(static::compoundsKey($table), now()->addMinutes(self::COMPOUNDS_MINUTES), fn () => Lookup::query()
                ->whereIn('id', DB::table($table)->select('sub_area_id')->where('is_published', true)->whereNull('deleted_at')->whereNotNull('sub_area_id')->distinct())
                ->where('is_active', true)
                ->orderBy('order')->orderBy('name_en')
                ->get(['id', 'slug', 'name_en', 'name_ar'])
                ->map(fn (Lookup $compound) => $compound->only(['id', 'slug', 'name_en', 'name_ar']))
                ->all());

            return collect($rows)
                ->groupBy('slug')
                ->map(fn ($same) => ['ids' => $same->pluck('id')->all(), 'name' => (new Lookup)->forceFill($same->first())->getLocalized('name')])
                ->all();
        });
    }

    /**
     * After a property or a project is saved or deleted: the Compound filters are read again.
     */
    public static function forgetCompounds(): void
    {
        Cache::forget(static::compoundsKey('units'));
        Cache::forget(static::compoundsKey('projects'));
    }

    private static function compoundsKey(string $table): string
    {
        return "website.compounds.{$table}";
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
