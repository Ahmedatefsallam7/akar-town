<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\Website\FeaturedLocations;
use App\Support\Website\FeaturedProjects;
use App\Support\Website\FeaturedUnits;
use App\Support\Website\HomeHero;
use App\Support\Website\HomeSections;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $sections = HomeSections::get();
        $types = array_column($sections, 'type');

        return view('website.home', [
            'slides' => HomeHero::slides(),
            'sections' => $sections,
            // Only what the active sections show.
            'featured' => in_array('properties', $types) ? FeaturedUnits::get() : [],
            'projects' => in_array('projects', $types) ? FeaturedProjects::get() : [],
            'locations' => in_array('locations', $types) ? FeaturedLocations::get() : [],
        ]);
    }
}
