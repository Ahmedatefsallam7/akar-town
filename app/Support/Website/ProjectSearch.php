<?php

namespace App\Support\Website;

use App\Filament\Support\PrefixSearch;
use Illuminate\Database\Eloquent\Builder;
use Modules\Developers\Models\Developer;
use Modules\Lookups\Support\LocationTree;
use Modules\Projects\Models\Project;

/**
 * The website's project search (the design's projects.html, and the home quick search's "New Projects" tab):
 * published projects filtered by name / developer / place, location, unit type, status and developer. Every filter
 * is optional; junk values are ignored.
 */
class ProjectSearch
{
    public const SORTS = ['featured', 'newest', 'price-asc', 'price-desc', 'completion'];

    /** The project status the quick search's "New Projects → Off-Plan" tab stands for. */
    public const OFF_PLAN = 'off-plan';

    /**
     * @param  array{q?: ?string, location?: ?string, type?: mixed, status?: mixed, developer?: mixed, sort?: ?string}  $filters
     */
    public static function query(array $filters): Builder
    {
        $query = Project::query()->where('is_published', true);

        if (filled($q = trim((string) ($filters['q'] ?? '')))) {
            $query->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => PrefixSearch::apply($query, $q, ['title_en', 'title_ar']))
                ->orWhereHas('developer', fn (Builder $query) => PrefixSearch::apply($query->where('is_published', true), $q, ['name_en', 'name_ar']))
                ->orWhere(fn (Builder $query) => PropertySearch::inPlaces($query, LocationTree::startingWith($q))));
        }

        PropertySearch::inLocation($query, $filters['location'] ?? null);

        // Lookups the project picked (its unit types, its status).
        foreach (['type' => 'unittype', 'status' => 'projectstatus'] as $filter => $type) {
            if ($id = SearchOptions::ids($type, static::first($filters[$filter] ?? null))[0] ?? null) {
                $query->whereHas('lookups', fn (Builder $query) => $query->where('lookups.id', $id)->where('lookupables.type', $type));
            }
        }

        // By slug; an unknown one is ignored, like the other filters.
        $developer = static::first($filters['developer'] ?? null);
        if (is_string($developer) && $id = Developer::query()->where('slug', $developer)->where('is_published', true)->value('id')) {
            $query->where('developer_id', $id);
        }

        return static::sort($query, $filters['sort'] ?? 'featured');
    }

    private static function sort(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'newest' => $query->latest()->latest('id'),
            'price-asc' => $query->orderByRaw('price_from IS NULL')->orderBy('price_from')->latest('id'),
            'price-desc' => $query->orderByRaw('price_from IS NULL')->orderByDesc('price_from')->latest('id'),
            'completion' => $query->orderByRaw('delivery_date IS NULL')->orderBy('delivery_date')->latest('id'),
            default => $query->orderByDesc('is_featured')->latest()->latest('id'),
        };
    }

    /**
     * A filter's value: one value, or the first of a list (the quick search sends lists).
     */
    private static function first(mixed $value): mixed
    {
        return is_array($value) ? (array_values($value)[0] ?? null) : $value;
    }
}
