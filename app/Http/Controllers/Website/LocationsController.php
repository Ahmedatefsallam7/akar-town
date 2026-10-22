<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\Website\Image;
use App\Support\Website\PropertySearch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Modules\Locations\Models\Location;

class LocationsController extends Controller
{
    /**
     * One active location's page: its photo, name and description on top, then the properties listing kept to it.
     * Its SEO title and description come from the admin (the name and description when empty).
     */
    public function show(string $slug): View
    {
        $location = Location::query()->where('slug', $slug)->where('is_active', true)->with('media')->firstOrFail();
        $description = $location->getLocalized('description');

        return view('website.location', [
            'slug' => $location->slug,
            'name' => $location->getLocalized('name'),
            'description' => $description,
            'image' => Image::url($location->getFirstMedia(Location::FEATURED_IMAGE)),
            'count' => PropertySearch::query(['location' => $location->slug])->reorder()->count(),
            'metaTitle' => $location->getLocalized('meta_title') ?: $location->getLocalized('name'),
            'metaDescription' => $location->getLocalized('meta_description') ?: Str::limit(strip_tags((string) $description), 160) ?: null,
        ]);
    }
}
