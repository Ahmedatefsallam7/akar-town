<?php

namespace App\Support\Website;

use Modules\Lookups\Models\Lookup;
use Modules\Units\Models\Unit;

/**
 * The compare page (the design's compare.html): up to MAX published units side by side, in the order picked, with
 * the "best" cell of each figure (lowest price; most beds, baths, area; highest floor) and every amenity any of them
 * has. Read in a fixed number of queries.
 */
class CompareUnits
{
    public const MAX = 5;

    /**
     * @param  list<string>  $slugs
     * @return array{units: list<array<string, mixed>>, best: array<string, list<bool>>, amenities: list<string>, maxArea: int, maxFloor: int}
     */
    public static function for(array $slugs): array
    {
        $slugs = array_slice(array_values(array_unique($slugs)), 0, self::MAX);
        $models = PropertyCards::eagerLoad(Unit::query())
            ->with([
                'agent' => Agent::scope(),
                'project' => fn ($query) => $query->where('is_published', true),
                'project.agent' => Agent::scope(),
                'lookups' => fn ($query) => $query->wherePivot('type', 'amenities')->select('lookups.id', 'name_en', 'name_ar')->orderBy('lookups.order'),
            ])
            ->where('is_published', true)
            ->whereIn('slug', $slugs)
            ->get()
            ->sortBy(fn (Unit $unit) => array_search($unit->slug, $slugs, true))
            ->values();

        $units = collect(PropertyCards::from($models))->map(function (array $card, int $i) use ($models) {
            $unit = $models[$i];

            return [
                ...$card,
                'priceValue' => $unit->price,
                'agent' => Agent::from($unit->agent ?? $unit->project?->agent),
                'amenities' => $unit->lookups->map(fn (Lookup $lookup) => $lookup->getLocalized('name'))->all(),
            ];
        })->all();

        return [
            'units' => $units,
            'best' => [
                'price' => static::best($units, 'priceValue', 'min'),
                'beds' => static::best($units, 'beds', 'max'),
                'baths' => static::best($units, 'baths', 'max'),
                'area' => static::best($units, 'area', 'max'),
                'floor' => static::best($units, 'floor', 'max'),
            ],
            'amenities' => collect($units)->pluck('amenities')->flatten()->unique()->values()->all(),
            'maxArea' => (int) collect($units)->max('area'),
            'maxFloor' => (int) collect($units)->max('floor'),
        ];
    }

    /**
     * Which units hold the best value (as the design: none when they all share it). Unknown values never win.
     *
     * @param  list<array<string, mixed>>  $units
     * @return list<bool>
     */
    private static function best(array $units, string $key, string $mode): array
    {
        $values = array_filter(array_column($units, $key), fn ($value) => $value !== null);
        if (! $values) {
            return array_fill(0, count($units), false);
        }
        $target = $mode === 'max' ? max($values) : min($values);
        $all = count(array_filter($values, fn ($value) => $value == $target)) === count($units);

        return array_map(fn (array $unit) => ! $all && $unit[$key] !== null && $unit[$key] == $target, $units);
    }
}
