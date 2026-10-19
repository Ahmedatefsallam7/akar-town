<?php

namespace App\Support\Website;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Projects\Models\Project;

/**
 * Projects shaped for the website's project card (the design's .proj-card): photo, status, developer, price,
 * place, units, delivery and amenities, read in a fixed number of queries whatever the number of cards.
 * The design's "Phases" isn't in the admin, so it isn't shown.
 */
class ProjectCards
{
    /** Amenity tags shown before "+N more". */
    private const TAGS = 2;

    /** The card's status colour by project status slug (the design's off-plan / construction / ready). */
    public const STATUS_STYLES = ['off-plan' => 'offplan', 'under-construction' => 'construction', 'ready-to-move' => 'ready'];

    public static function eagerLoad(Builder $query): Builder
    {
        return $query
            ->with([
                // Only a published developer is named.
                'developer' => fn ($query) => $query->where('is_published', true)->select('id', 'name_en', 'name_ar'),
                'media' => fn ($query) => $query->where('collection_name', Project::FEATURED_IMAGE),
                'lookups' => fn ($query) => $query->wherePivotIn('type', ['projectstatus', 'amenities'])->select('lookups.id', 'lookups.slug', 'name_en', 'name_ar')->orderBy('lookups.order'),
            ])
            ->withCount(['units' => fn ($query) => $query->where('is_published', true)]);
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return list<array<string, mixed>>
     */
    public static function from(Collection $projects): array
    {
        return $projects->map(function (Project $project) {
            $status = $project->lookups->firstWhere('pivot.type', 'projectstatus');
            $amenities = $project->lookups->where('pivot.type', 'amenities')->map(fn (Lookup $lookup) => $lookup->getLocalized('name'))->values();
            $delivery = $project->delivery_date ? Carbon::parse($project->delivery_date) : null;

            return [
                'id' => $project->id,
                'title' => $project->getLocalized('title'),
                'description' => $project->getLocalized('short_description'),
                'featured' => $project->is_featured,
                'status' => $status?->getLocalized('name'),
                'statusStyle' => self::STATUS_STYLES[$status?->slug] ?? 'offplan',
                'developer' => $project->developer?->getLocalized('name'),
                'price' => $project->price_from ? __('EGP :price', ['price' => Number::abbreviate($project->price_from, maxPrecision: 1)]) : null,
                'location' => LocationTree::place([$project->country_id, $project->city_id, $project->region_id, $project->area_id, $project->sub_area_id]),
                'units' => $project->units_count,
                'delivery' => $delivery ? 'Q'.$delivery->quarter.' '.$delivery->year : null,
                'tags' => $amenities->take(self::TAGS)->all(),
                'more' => max(0, $amenities->count() - self::TAGS),
                'image' => Image::orPlaceholder($project->getFirstMedia(Project::FEATURED_IMAGE), 'card'),
                'url' => route('projects.show', $project->slug),
                'unitsUrl' => route('properties', ['project' => $project->slug]),
            ];
        })->all();
    }
}
