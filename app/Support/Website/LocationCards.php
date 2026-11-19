<?php

namespace App\Support\Website;

use App\Support\SmartSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Locations\Models\Location;
use Modules\Lookups\Support\LocationTree;
use Modules\Units\Models\Unit;

/**
 * Website location cards (the locations page and the home page's "Prime Destinations"): active locations, featured
 * first then the admin's order, with how many published properties each has.
 */
class LocationCards
{
    /**
     * @return Builder<Location>
     */
    public static function query(): Builder
    {
        return Location::query()
            ->where('is_active', true)
            ->with('media')
            ->orderByDesc('is_featured')
            ->orderByRaw('`order` is null')
            ->orderBy('order')
            ->orderBy('id');
    }

    /**
     * The locations page's search: the location's name (starts with), or a compound's name: the location that is the
     * compound or its area (its most precise place), when the compound has published properties (AKAR-47).
     */
    public static function search(Builder $query, string $q): Builder
    {
        $q = trim($q);
        if ($q === '') {
            return $query;
        }
        $places = collect(PropertySearch::compoundsWithProperties($q))->flatMap(fn (int $compound) => array_slice(LocationTree::withAncestors($compound), 0, 2))->unique()->values()->all();

        return $query->where(fn (Builder $query) => $query
            ->where(fn (Builder $query) => SmartSearch::match($query, $q))
            ->orWhereIn(DB::raw('coalesce(sub_area_id, area_id, region_id, city_id, country_id)'), $places ?: [0]));
    }

    /**
     * @param  Collection<int, Location>  $locations
     * @return list<array{slug: string, name: string, region: string, country: string, description: ?string, count: int, featured: bool, image: ?string, url: string}>
     */
    public static function from(Collection $locations): array
    {
        $counts = static::unitCounts($locations);

        return $locations->map(function (Location $location) use ($counts) {
            $level = static::deepestLevel($location);
            $chain = array_values(array_filter(array_map(fn (string $column) => $location->{$column}, LocationTree::LEVELS)));

            return [
                'slug' => $location->slug,
                'name' => $location->getLocalized('name'),
                // The place above it, e.g. "Cairo" for New Cairo (the country for a city).
                'region' => count($chain) > 1 ? LocationTree::path([$chain[count($chain) - 2]]) : '',
                'country' => LocationTree::path([$location->country_id]),
                'description' => $location->getLocalized('description'),
                'count' => $level ? ($counts[$level][$location->{$level}] ?? 0) : 0,
                'featured' => $location->is_featured,
                'image' => Image::orPlaceholder($location->getFirstMedia(Location::FEATURED_IMAGE), 'card'),
                'url' => route('locations.show', $location->slug),
            ];
        })->values()->all();
    }

    /**
     * Published properties per location: one grouped count per chain level the locations use (usually one or two),
     * kept 10 minutes like the footer's numbers (a big city counts thousands of rows: 72 ms each on 50,000 units).
     *
     * @param  Collection<int, Location>  $locations
     * @return array<string, array<int, int>> level column => id => count
     */
    private static function unitCounts(Collection $locations): array
    {
        $levels = $locations
            ->groupBy(fn (Location $location) => static::deepestLevel($location))
            ->filter(fn ($group, $level) => $level !== '')
            ->map(fn (Collection $group) => $group->pluck(static::deepestLevel($group->first()))->sort()->values()->all());

        $key = 'website.location_counts.'.md5($levels->toJson());

        return Cache::remember($key, now()->addMinutes(10), function () use ($key, $levels) {
            static::noteCountKey($key);

            return $levels
                ->map(fn (array $ids, string $level) => Unit::query()
                    ->where('is_published', true)
                    ->whereIn($level, $ids)
                    ->groupBy($level)
                    ->selectRaw("{$level}, count(*) as total")
                    ->pluck('total', $level)
                    ->all())
                ->all();
        });
    }

    private static function deepestLevel(Location $location): string
    {
        return collect(LocationTree::LEVELS)->reverse()->first(fn (string $column) => filled($location->{$column})) ?? '';
    }

    /** The list of the location-count keys made (their names depend on the locations shown). */
    private const COUNT_KEYS = 'website.location_counts.keys';

    /**
     * Notes a location-count key, so the Refresh button can find it (only when the counts are built: a page view reads
     * nothing more).
     */
    private static function noteCountKey(string $key): void
    {
        Cache::put(self::COUNT_KEYS, array_values(array_unique([...Cache::get(self::COUNT_KEYS, []), $key])), now()->addDay());
    }

    /**
     * Every location card's count is read again (the admin's Refresh button).
     */
    public static function forgetCounts(): void
    {
        foreach (Cache::pull(self::COUNT_KEYS, []) as $key) {
            Cache::forget($key);
        }
    }
}
