<?php

namespace App\Support\Website;

use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\Projects\Models\Project;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The home page's "Our Projects" grid: four published, featured projects (newest first), shaped for the cards.
 */
class FeaturedProjects
{
    private const LIMIT = 4;

    /**
     * @return list<array{id: int, title: string, badge: string, location: string, price: ?string, image: ?string, images: list<string>, url: string}>
     */
    public static function get(): array
    {
        $projects = Project::query()
            ->where('is_published', true)
            ->where('is_featured', true)
            ->with([
                'media' => fn ($query) => $query->whereIn('collection_name', [Project::FEATURED_IMAGE, Project::GALLERY])->orderBy('order_column'),
                'lookups' => fn ($query) => $query->wherePivot('type', 'projectstatus')->select('lookups.id', 'name_en', 'name_ar'),
            ])
            ->latest('id')
            ->limit(self::LIMIT)
            ->get();

        return $projects->map(function (Project $project) {
            $photos = collect([$project->getFirstMedia(Project::FEATURED_IMAGE)])
                ->merge($project->getMedia(Project::GALLERY))
                ->filter();
            $status = $project->lookups->first();

            return [
                'id' => $project->id,
                'title' => $project->getLocalized('title'),
                'badge' => $status instanceof Lookup ? $status->getLocalized('name') : __('Featured'),
                'location' => LocationTree::place([$project->country_id, $project->city_id, $project->region_id, $project->area_id, $project->sub_area_id]),
                'price' => $project->price_from ? __('Starting from EGP :price', ['price' => number_format($project->price_from)]) : null,
                'image' => Image::url($photos->first()),
                'images' => $photos->map(fn (Media $media) => Image::url($media))->values()->all(),
                'url' => route('projects'),
            ];
        })->all();
    }
}
