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
        // The same read as the listing's location filter (once per request).
        abort_unless($location = PropertySearch::activeLocation($slug), 404);
        $location->loadMissing('media');
        $description = $location->getLocalized('description');

        return view('website.location', [
            'slug' => $location->slug,
            'name' => $location->getLocalized('name'),
            'description' => $description,
            'image' => Image::url($location->getFirstMedia(Location::FEATURED_IMAGE)),
            'imageSmall' => Image::url($location->getFirstMedia(Location::FEATURED_IMAGE), 'card'),
            'count' => PropertySearch::countIn($location->slug),
            'metaTitle' => $location->getLocalized('meta_title') ?: $location->getLocalized('name'),
            'metaDescription' => $location->getLocalized('meta_description') ?: Str::limit(strip_tags((string) $description), 160) ?: null,
        ]);
    }
}
