<?php

namespace App\Support\Website;

use Illuminate\Support\Collection;
use Modules\Lookups\Support\LocationTree;
use Modules\Projects\Models\Project;
use Modules\Sliders\Models\Slider;

/**
 * The home hero's slides: the projects on the active hero slider (in its order), published ones only; with no
 * hero slider or no slides, the featured projects (as in Dastan).
 */
class HomeHero
{
    /**
     * @return list<array{title: string, tagline: ?string, location: string, image: ?string, thumb: ?string, url: string, announce: string}>
     */
    public static function slides(): array
    {
        $projects = static::projects();

        return $projects->values()->map(fn (Project $project, int $i) => [
            'title' => $project->getLocalized('title'),
            'tagline' => $project->getLocalized('short_description'),
            'location' => LocationTree::place([$project->country_id, $project->city_id, $project->region_id, $project->area_id, $project->sub_area_id]),
            'image' => Image::url($project->getFirstMedia(Project::FEATURED_IMAGE)),
            'thumb' => Image::url($project->getFirstMedia(Project::FEATURED_IMAGE), 'thumb'),
            'url' => route('projects'),
            'announce' => __('Showing :title, property :number of :total', ['title' => $project->getLocalized('title'), 'number' => $i + 1, 'total' => $projects->count()]),
        ])->all();
    }

    /**
     * @return Collection<int, Project>
     */
    private static function projects(): Collection
    {
        $withImage = ['media' => fn ($query) => $query->where('collection_name', Project::FEATURED_IMAGE)];

        $hero = Slider::query()->where('placement', Slider::HOME_HERO)->where('is_active', true)->orderBy('order')->first();

        $projects = $hero
            ? $hero->items()
                ->where('is_active', true)
                ->orderBy('order')
                ->with(['project' => fn ($query) => $query->where('is_published', true)->with($withImage)])
                ->get()
                ->pluck('project')
                ->filter()
            : collect();

        if ($projects->isNotEmpty()) {
            return $projects;
        }

        return Project::query()->where('is_published', true)->where('is_featured', true)->with($withImage)->latest()->get();
    }
}
