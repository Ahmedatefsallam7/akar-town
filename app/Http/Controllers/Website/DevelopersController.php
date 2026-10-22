<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\Website\DeveloperCards;
use App\Support\Website\Image;
use App\Support\Website\ProjectCards;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Modules\Developers\Models\Developer;
use Modules\Lookups\Support\LocationTree;
use Modules\Projects\Models\Project;

class DevelopersController extends Controller
{
    /** Projects shown on the page; "View All" opens the projects listing filtered by the developer. */
    private const PROJECTS = 6;

    /**
     * One published developer's page (the design's developer-details.html): logo, name, tagline and contacts on its
     * photo, then its story (a project photo, social links, description), then its published projects.
     */
    public function show(string $slug): View
    {
        $developer = Developer::query()->where('slug', $slug)->where('is_published', true)->with('media')->firstOrFail();

        $projects = Project::query()->where('developer_id', $developer->id)->where('is_published', true);
        $total = (clone $projects)->count();
        $cards = $total ? ProjectCards::eagerLoad($projects)->orderByDesc('is_featured')->latest('id')->limit(self::PROJECTS)->get() : collect();
        // The story photo: one of its projects (its work), else its own photo.
        $storyPhoto = $cards->map(fn (Project $project) => $project->getFirstMedia(Project::FEATURED_IMAGE))->filter()->first()
            ?? $developer->getFirstMedia(Developer::FEATURED_IMAGE);

        $name = $developer->getLocalized('name');
        $place = LocationTree::place($developer->only(LocationTree::LEVELS));

        return view('website.developer', [
            'name' => $name,
            'initials' => DeveloperCards::initials($developer->name_en ?: $name),
            'tagline' => $developer->getLocalized('tagline') ?: $developer->getLocalized('short_description'),
            'logo' => Image::url($developer->getFirstMedia(Developer::LOGO), 'thumb'),
            'cover' => Image::url($developer->getFirstMedia(Developer::FEATURED_IMAGE)),
            // Phones take the card size.
            'coverSmall' => Image::url($developer->getFirstMedia(Developer::FEATURED_IMAGE), 'card'),
            'phone' => $developer->phone,
            'email' => $developer->email,
            'address' => collect([$developer->neighbourhood, $place])->filter()->implode(app()->getLocale() === 'ar' ? '، ' : ', ') ?: null,
            'socials' => collect(Developer::NETWORKS)->mapWithKeys(fn (string $network) => [$network => $developer->{"social_{$network}"}])->filter()->all(),
            'storyPhoto' => Image::url($storyPhoto, 'card'),
            'description' => (string) str((string) $developer->getLocalized('description'))->sanitizeHtml(),
            'projects' => ProjectCards::from($cards),
            'total' => $total,
            'slug' => $developer->slug,
            'metaTitle' => $developer->getLocalized('meta_title') ?: $name,
            'metaDescription' => $developer->getLocalized('meta_description') ?: (Str::limit(strip_tags((string) $developer->getLocalized('short_description')), 160) ?: null),
        ]);
    }
}
