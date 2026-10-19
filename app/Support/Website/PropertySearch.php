<?php

namespace App\Support\Website;

use App\Filament\Support\PrefixSearch;
use Illuminate\Database\Eloquent\Builder;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;

/**
 * The website's property search: published units filtered by what the listings page (and the home quick search)
 * send. Every filter is optional; lists are OR-ed within a filter and AND-ed between filters.
 */
class PropertySearch
{
    /** Offering types (lookup slugs) that mean "for sale" and "for rent". */
    public const SALE = ['primary', 'resale'];

    public const RENT = ['rent'];

    /** The unit status (lookup slug) that means "off-plan". */
    public const OFF_PLAN = 'us-off-plan';

    /**
     * @param  array{q?: ?string, type?: array, price?: array, beds?: array, baths?: array, area?: array, area_min?: mixed, area_max?: mixed, floor_min?: mixed, floor_max?: mixed, status?: array, amenities?: array, finishing?: array, intent?: ?string, category?: ?string, location?: ?string, project?: mixed, sort?: ?string}  $filters
     */
    public static function query(array $filters): Builder
    {
        $query = Unit::query()->where('is_published', true);

        if (filled($q = trim((string) ($filters['q'] ?? '')))) {
            $query->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => PrefixSearch::apply($query, $q, ['name_en', 'name_ar']))
                ->orWhere(fn (Builder $query) => static::inPlaces($query, LocationTree::startingWith($q))));
        }

        static::whereIn($query, 'property_type_id', SearchOptions::ids('unittype', $filters['type'] ?? []));
        static::whereIn($query, 'finishing_type_id', SearchOptions::ids('finishingtype', $filters['finishing'] ?? []));
        static::counts($query, 'bedrooms', static::strings($filters['beds'] ?? []));
        static::counts($query, 'bathrooms', static::strings($filters['baths'] ?? []));
        static::ranges($query, 'price', static::strings($filters['price'] ?? []));
        static::ranges($query, 'area', static::strings($filters['area'] ?? []));
        static::between($query, 'area', $filters['area_min'] ?? null, $filters['area_max'] ?? null);
        static::between($query, 'floor', $filters['floor_min'] ?? null, $filters['floor_max'] ?? null);
        static::statuses($query, static::strings($filters['status'] ?? []), $filters['intent'] ?? null, $filters['category'] ?? null);

        foreach (SearchOptions::ids('amenities', $filters['amenities'] ?? []) as $amenity) {
            $query->whereHas('lookups', fn (Builder $query) => $query->where('lookups.id', $amenity));
        }

        static::inLocation($query, $filters['location'] ?? null);

        // By slug; an unknown one is ignored, like the other filters.
        $project = $filters['project'] ?? null;
        if (is_string($project) && $id = Project::query()->where('slug', $project)->where('is_published', true)->value('id')) {
            $query->where('project_id', $id);
        }

        return match ($filters['sort'] ?? null) {
            'price-asc' => $query->orderByRaw('price is null')->orderBy('price'),
            'price-desc' => $query->orderByDesc('price'),
            'area-desc' => $query->orderByDesc('area'),
            default => $query->latest(),
        };
    }

    /**
     * @param  list<int>  $ids
     */
    private static function whereIn(Builder $query, string $column, array $ids): void
    {
        if ($ids) {
            $query->whereIn($column, $ids);
        }
    }

    /**
     * A list filter's plain values: anything else in the URL (e.g. "beds[0][]=1") is ignored.
     *
     * @return list<string>
     */
    private static function strings(mixed $values): array
    {
        return array_values(array_map('strval', array_filter((array) $values, 'is_scalar')));
    }

    /**
     * Counts like "3" (exactly 3), "0" (studio) or "6+" (6 or more).
     *
     * @param  array<string>  $values
     */
    private static function counts(Builder $query, string $column, array $values): void
    {
        $values = array_filter($values, fn ($value) => preg_match('/^\d+\+?$/', (string) $value));

        if ($values) {
            $query->where(function (Builder $query) use ($column, $values) {
                foreach ($values as $value) {
                    str_ends_with($value, '+') ? $query->orWhere($column, '>=', (int) $value) : $query->orWhere($column, (int) $value);
                }
            });
        }
    }

    /**
     * Ranges like "2000000-5000000" or "20000000-" (open-ended).
     *
     * @param  array<string>  $values
     */
    private static function ranges(Builder $query, string $column, array $values): void
    {
        $values = array_filter($values, fn ($value) => preg_match('/^\d+-\d*$/', (string) $value));

        if ($values) {
            $query->where(function (Builder $query) use ($column, $values) {
                foreach ($values as $value) {
                    [$from, $to] = explode('-', $value);
                    $query->orWhere(fn (Builder $query) => static::between($query, $column, $from, $to));
                }
            });
        }
    }

    private static function between(Builder $query, string $column, mixed $from, mixed $to): void
    {
        if (is_numeric($from)) {
            $query->where($column, '>=', (int) $from);
        }

        if (is_numeric($to)) {
            $query->where($column, '<=', (int) $to);
        }
    }

    /**
     * Status chips (for-sale / for-rent / off-plan) and the home search's intent (buy → primary / resale, rent).
     *
     * @param  array<string>  $statuses
     */
    private static function statuses(Builder $query, array $statuses, ?string $intent, ?string $category): void
    {
        $offerings = static::lookupIds('offeringtype');
        $sale = array_values(array_intersect_key($offerings, array_flip(self::SALE)));
        $rent = array_values(array_intersect_key($offerings, array_flip(self::RENT)));

        if ($intent === 'buy') {
            $query->whereIn('offering_type_id', in_array($category, self::SALE, true) ? [$offerings[$category] ?? 0] : ($sale ?: [0]));
        } elseif ($intent === 'rent') {
            $query->whereIn('offering_type_id', $rent ?: [0]);
        }

        $statuses = array_intersect($statuses, ['for-sale', 'for-rent', 'off-plan']);

        if ($statuses) {
            $offPlan = static::lookupIds('unitstatus')[self::OFF_PLAN] ?? 0;
            $query->where(function (Builder $query) use ($statuses, $sale, $rent, $offPlan) {
                foreach ($statuses as $status) {
                    match ($status) {
                        'for-sale' => $query->orWhereIn('offering_type_id', $sale ?: [0]),
                        'for-rent' => $query->orWhereIn('offering_type_id', $rent ?: [0]),
                        'off-plan' => $query->orWhere('unit_status_id', $offPlan),
                    };
                }
            });
        }
    }

    /**
     * @return array<string, int> slug => id
     */
    private static function lookupIds(string $type): array
    {
        return once(fn () => Lookup::query()
            ->whereIn('parent_id', Lookup::query()->select('id')->whereNull('parent_id')->where('slug', $type))
            ->pluck('id', 'slug')
            ->all());
    }

    /**
     * Records (units, projects: anything with the location chain columns) in a website location (Locations, by slug):
     * its most specific level. An unknown slug filters nothing.
     */
    public static function inLocation(Builder $query, ?string $slug): void
    {
        if (blank($slug) || ! $location = Location::query()->where('slug', $slug)->where('is_active', true)->first()) {
            return;
        }

        $level = collect(LocationTree::LEVELS)->reverse()->first(fn (string $column) => filled($location->{$column}));
        static::inPlaces($query, $level ? [['column' => $level, 'id' => $location->{$level}]] : []);
    }

    /**
     * Records inside any of the places (a chain column and its value each, e.g. LocationTree::startingWith()); none
     * matches nothing.
     *
     * @param  list<array{column: string, id: int}>  $places
     */
    public static function inPlaces(Builder $query, array $places): void
    {
        $query->where(function (Builder $query) use ($places) {
            $query->whereRaw('1 = 0');
            foreach ($places as $place) {
                $query->orWhere($place['column'], $place['id']);
            }
        });
    }
}
