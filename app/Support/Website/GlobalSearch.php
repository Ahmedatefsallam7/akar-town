<?php

namespace App\Support\Website;

use App\Support\SmartSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\Developers\Models\Developer;
use Modules\Locations\Models\Location;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Lookups\Support\SearchText;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;

/**
 * The website's search (the navbar window and the /search page, AKAR-47/48): what a typed text finds, grouped by
 * type and published only. Names match in any part, any case, Arabic letters written either way, the best matches
 * first (SmartSearch). A compound's name also finds the compound (it opens the properties filtered by it), its
 * properties, the projects and developers with a property in it and the location it belongs to. When nothing
 * matches, close names with a small typo are tried (compounds, developers, locations: "madinsty" → Madinaty, and
 * what is in it). A fixed number of small queries whatever the data.
 */
class GlobalSearch
{
    public const PER_GROUP = 5;

    public const GROUPS = ['compounds', 'properties', 'projects', 'developers', 'locations'];

    /**
     * @return array<string, list<array{title: string, sub: ?string, image: ?string, url: string, badge?: ?string}>> compounds, properties, projects, developers, locations
     */
    public static function for(string $q, int $limit = self::PER_GROUP): array
    {
        return static::search($q, $limit)['groups'];
    }

    /**
     * The groups, and whether they are close matches (a typo) rather than matches.
     *
     * @return array{groups: array<string, list<array<string, mixed>>>, near: bool}
     */
    public static function search(string $q, int $limit = self::PER_GROUP): array
    {
        $q = trim($q);
        $empty = array_fill_keys(self::GROUPS, []);
        if (mb_strlen($q) < 2) {
            return ['groups' => $empty, 'near' => false];
        }

        $groups = static::find($q, $limit);
        if (collect($groups)->flatten(1)->isEmpty() && mb_strlen(SearchText::normalize($q)) >= 4) {
            return ['groups' => static::find($q, $limit, static::near($q)), 'near' => true];
        }

        return ['groups' => $groups, 'near' => false];
    }

    /**
     * @param  array{compounds: list<int>, developers: list<int>, locations: list<int>}|null  $near  close matches instead of the text
     */
    private static function find(string $q, int $limit, ?array $near = null): array
    {
        // Only compounds with published properties: one with nothing to open isn't offered.
        $compoundIds = $near ? SearchOptions::compoundsUsedOf('units', $near['compounds']) : PropertySearch::compoundsWithProperties($q);
        $compounds = Lookup::query()->whereIn('id', $compoundIds ?: [0])->orderBy('order')->orderBy('name_en')->limit($limit)->get(['id', 'parent_id', 'slug', 'name_en', 'name_ar']);
        $counts = $compounds->isEmpty() ? collect() : Unit::query()->where('is_published', true)->whereIn('sub_area_id', $compounds->modelKeys())
            ->groupBy('sub_area_id')->selectRaw('sub_area_id, count(*) as total')->pluck('total', 'sub_area_id');

        $photo = fn (string $collection) => ['media' => fn ($query) => $query->where('collection_name', $collection)];
        $byName = fn (Builder $query) => $near ? $query : SmartSearch::rank($query, $q);

        $units = $byName(Unit::query()->where('is_published', true)
            ->where(fn (Builder $query) => $query->whereIn('sub_area_id', $compoundIds ?: [0])
                ->when(! $near, fn (Builder $query) => $query->orWhere(fn (Builder $query) => SmartSearch::match($query, $q)))))
            ->with($photo(Unit::FEATURED_IMAGE))
            ->orderByDesc('is_featured')->latest('id');
        $units = static::firstOf($units, $limit, ['id', 'slug', 'name_en', 'name_ar', 'price', 'country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id']);

        $projects = $byName(Project::query()->where('is_published', true)
            ->where(fn (Builder $query) => $query->whereIn('sub_area_id', $compoundIds ?: [0])
                ->orWhereIn('id', PropertySearch::withPropertiesIn($compoundIds, 'project_id'))
                ->when($near, fn (Builder $query) => $query->orWhereIn('developer_id', $near['developers'] ?: [0]),
                    fn (Builder $query) => $query->orWhere(fn (Builder $query) => SmartSearch::match($query, $q)))))
            ->with($photo(Project::FEATURED_IMAGE))
            ->orderByDesc('is_featured')->latest('id');
        $projects = static::firstOf($projects, $limit, ['id', 'slug', 'title_en', 'title_ar', 'country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id']);

        $developers = $byName($near
            ? Developer::query()->where('is_published', true)->whereIn('id', $near['developers'] ?: [0])
            : DeveloperCards::search(Developer::query()->where('is_published', true), $q))
            ->with($photo(Developer::LOGO))->withCount(['projects' => fn (Builder $query) => $query->where('is_published', true)])
            ->orderByDesc('is_featured')->orderBy('name_en')->limit($limit)->get(['id', 'slug', 'name_en', 'name_ar']);

        $locations = $byName($near
            ? Location::query()->where('is_active', true)->whereIn('id', $near['locations'] ?: [0])
            : LocationCards::search(Location::query()->where('is_active', true), $q))
            ->with($photo(Location::FEATURED_IMAGE))
            ->orderByDesc('is_featured')->orderBy('order')->limit($limit)->get(['id', 'slug', 'name_en', 'name_ar', 'country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id']);

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
     * The first records of a search, in two steps: their ids, found inside the search index (no row reads over the
     * whole table), then only those rows (performance review: with 50,000 properties a search read every row).
     *
     * @param  list<string>  $columns
     */
    private static function firstOf(Builder $query, int $limit, array $columns): Collection
    {
        $ids = (clone $query)->limit($limit)->pluck($query->qualifyColumn('id'))->all();

        return $ids === [] ? $query->getModel()->newCollection() : $query->getModel()->newQuery()
            ->with($query->getEagerLoads())->whereKey($ids)->get($columns)
            ->sortBy(fn (Model $model) => array_search($model->getKey(), $ids))->values();
    }

    /**
     * Names close to the text (a small typo): compounds from the tree, developers and locations from their search
     * names (two small lists: published developers, active locations).
     *
     * @return array{compounds: list<int>, developers: list<int>, locations: list<int>}
     */
    private static function near(string $q): array
    {
        $text = SearchText::normalize($q);
        $close = fn (Builder $query) => $query->pluck('search_name', 'id')->filter(fn (?string $name) => SearchText::nearly($name, $text))->keys()->take(20)->all();

        return [
            'compounds' => LocationTree::compoundsNearly($text),
            'developers' => $close(Developer::query()->where('is_published', true)),
            'locations' => $close(Location::query()->where('is_active', true)),
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
