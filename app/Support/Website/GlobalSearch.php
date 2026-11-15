<?php

namespace App\Support\Website;

use App\Filament\Support\PrefixSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\Developers\Models\Developer;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;

/**
 * The website's global search (navbar, AKAR-47): what a typed text finds, grouped by type and published only. A
 * compound's name (any part, any case, Arabic letters written either way) finds the compound itself (it opens the
 * properties listing filtered by it), its properties, the projects and developers with a property in it and the
 * location it belongs to; names find properties, projects, developers and locations too. A few rows a group, each
 * with a small photo and a second line, in a fixed number of small queries whatever the data.
 */
class GlobalSearch
{
    private const PER_GROUP = 5;

    /**
     * @return array<string, list<array{title: string, sub: ?string, image: ?string, url: string, badge?: ?string}>> compounds, properties, projects, developers, locations
     */
    public static function for(string $q): array
    {
        $q = trim($q);
        $empty = ['compounds' => [], 'properties' => [], 'projects' => [], 'developers' => [], 'locations' => []];
        if (mb_strlen($q) < 2) {
            return $empty;
        }

        // Only compounds with published properties: one with nothing to open isn't offered.
        $compoundIds = PropertySearch::compoundsWithProperties($q);
        $compounds = Lookup::query()->whereIn('id', $compoundIds)->orderBy('order')->orderBy('name_en')->limit(self::PER_GROUP)->get(['id', 'parent_id', 'slug', 'name_en', 'name_ar']);
        $counts = $compounds->isEmpty() ? collect() : Unit::query()->where('is_published', true)->whereIn('sub_area_id', $compounds->modelKeys())
            ->groupBy('sub_area_id')->selectRaw('sub_area_id, count(*) as total')->pluck('total', 'sub_area_id');

        $photo = fn (string $collection) => ['media' => fn ($query) => $query->where('collection_name', $collection)];

        $units = Unit::query()->where('is_published', true)
            ->where(fn (Builder $query) => $query->whereIn('sub_area_id', $compoundIds ?: [0])->orWhere(fn (Builder $query) => PrefixSearch::apply($query, $q, ['name_en', 'name_ar'])))
            ->with($photo(Unit::FEATURED_IMAGE))
            ->orderByDesc('is_featured')->latest('id')->limit(self::PER_GROUP)
            ->get(['id', 'slug', 'name_en', 'name_ar', 'price', 'country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id']);

        $projects = Project::query()->where('is_published', true)
            ->where(fn (Builder $query) => $query->whereIn('sub_area_id', $compoundIds ?: [0])
                ->orWhereIn('id', PropertySearch::withPropertiesIn($compoundIds, 'project_id'))
                ->orWhere(fn (Builder $query) => PrefixSearch::apply($query, $q, ['title_en', 'title_ar'])))
            ->with($photo(Project::FEATURED_IMAGE))
            ->orderByDesc('is_featured')->latest('id')->limit(self::PER_GROUP)
            ->get(['id', 'slug', 'title_en', 'title_ar', 'country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id']);

        $developers = DeveloperCards::search(Developer::query()->where('is_published', true), $q)
            ->with($photo(Developer::LOGO))->withCount(['projects' => fn (Builder $query) => $query->where('is_published', true)])
            ->orderByDesc('is_featured')->orderBy('name_en')->limit(self::PER_GROUP)->get(['id', 'slug', 'name_en', 'name_ar']);

        $locations = LocationCards::search(Location::query()->where('is_active', true), $q)
            ->with($photo(Location::FEATURED_IMAGE))
            ->orderByDesc('is_featured')->orderBy('order')->limit(self::PER_GROUP)->get(['id', 'slug', 'name_en', 'name_ar', 'country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id']);

        $place = fn (Model $model) => LocationTree::place([$model->country_id, $model->city_id, $model->region_id, $model->area_id, $model->sub_area_id]) ?: null;
        $thumb = fn (Model $model, string $collection) => Image::url($model->getFirstMedia($collection), 'thumb');

        return [
            'compounds' => $compounds->map(fn (Lookup $compound) => [
                'title' => $compound->getLocalized('name'),
                'sub' => LocationTree::path([$compound->parent_id]) ?: null,
                'image' => null,
                'badge' => trans_choice(':count property|:count properties', $total = (int) ($counts[$compound->id] ?? 0), ['count' => $total]),
                'url' => route('properties', ['compound' => [$compound->slug]]),
            ])->all(),
            'properties' => $units->map(fn (Unit $unit) => [
                'title' => $unit->getLocalized('name'),
                'sub' => $place($unit),
                'image' => $thumb($unit, Unit::FEATURED_IMAGE),
                'badge' => $unit->price ? __('EGP :price', ['price' => number_format((float) $unit->price)]) : null,
                'url' => route('properties.show', $unit->slug),
            ])->all(),
            'projects' => $projects->map(fn (Project $project) => [
                'title' => $project->getLocalized('title'),
                'sub' => $place($project),
                'image' => $thumb($project, Project::FEATURED_IMAGE),
                'url' => route('projects.show', $project->slug),
            ])->all(),
            'developers' => $developers->map(fn (Developer $developer) => [
                'title' => $developer->getLocalized('name'),
                'sub' => $developer->projects_count ? trans_choice(':count project|:count projects', $developer->projects_count, ['count' => $developer->projects_count]) : null,
                'image' => $thumb($developer, Developer::LOGO),
                'url' => route('developers.show', $developer->slug),
            ])->all(),
            'locations' => $locations->map(fn (Location $location) => [
                'title' => $location->getLocalized('name'),
                // Where it is, without the place it is (its own name): "Cairo, Egypt" for New Cairo.
                'sub' => LocationTree::place(array_slice($chain = array_values(array_filter([$location->country_id, $location->city_id, $location->region_id, $location->area_id, $location->sub_area_id])), 0, max(1, count($chain) - 1))) ?: null,
                'image' => $thumb($location, Location::FEATURED_IMAGE),
                'url' => route('locations.show', $location->slug),
            ])->all(),
        ];
    }

    /**
     * The compounds with the most published properties, for the window before anything is typed (cached a while:
     * a grouped count over every unit).
     *
     * @return list<array{title: string, url: string}>
     */
    public static function popularCompounds(int $limit = 8): array
    {
        $ids = Cache::remember('website.search.popular_compounds', now()->addMinutes(10), fn () => Unit::query()
            ->where('is_published', true)->whereNotNull('sub_area_id')
            ->groupBy('sub_area_id')->orderByRaw('count(*) desc')->limit($limit)->pluck('sub_area_id')->all());

        return $ids === [] ? [] : Lookup::query()->whereIn('id', $ids)->where('is_active', true)->get(['id', 'slug', 'name_en', 'name_ar'])
            ->sortBy(fn (Lookup $compound) => array_search($compound->id, $ids))
            ->map(fn (Lookup $compound) => ['title' => $compound->getLocalized('name'), 'url' => route('properties', ['compound' => [$compound->slug]])])
            ->values()->all();
    }
}
