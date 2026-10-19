<?php

namespace App\Support\Website;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Locations\Models\Location;
use Modules\Lookups\Support\LocationTree;
use Modules\Units\Models\Unit;

/**
 * The home page's "Prime Destinations": active, featured locations (in the admin's order) with how many published
 * properties each has.
 */
class FeaturedLocations
{
    private const LIMIT = 8;

    /**
     * @return list<array{name: string, country: string, description: ?string, count: int, image: ?string, url: string}>
     */
    public static function get(): array
    {
        $locations = Location::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->with('media')
            ->orderByRaw('`order` is null')
            ->orderBy('order')
            ->limit(self::LIMIT)
            ->get();

        $counts = static::unitCounts($locations);

        return $locations->map(function (Location $location) use ($counts) {
            $level = static::deepestLevel($location);

            return [
                'name' => $location->getLocalized('name'),
                'country' => LocationTree::path([$location->country_id]),
                'description' => $location->getLocalized('description'),
                'count' => $level ? ($counts[$level][$location->{$level}] ?? 0) : 0,
                'image' => Image::orPlaceholder($location->getFirstMedia(Location::FEATURED_IMAGE), 'card'),
                'url' => route('properties', ['location' => $location->slug]),
            ];
        })->all();
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
