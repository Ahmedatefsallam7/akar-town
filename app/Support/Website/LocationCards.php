<?php

namespace App\Support\Website;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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

        return Cache::remember('website.location_counts.'.md5($levels->toJson()), now()->addMinutes(10), fn () => $levels
            ->map(fn (array $ids, string $level) => Unit::query()
                ->where('is_published', true)
                ->whereIn($level, $ids)
                ->groupBy($level)
                ->selectRaw("{$level}, count(*) as total")
                ->pluck('total', $level)
                ->all())
            ->all());
    }

    private static function deepestLevel(Location $location): string
    {
        return collect(LocationTree::LEVELS)->reverse()->first(fn (string $column) => filled($location->{$column})) ?? '';
    }
}
