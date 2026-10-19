<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\Website\ProjectDetails;
use Illuminate\Contracts\View\View;
use Modules\Projects\Models\Project;

class ProjectsController extends Controller
{
    /**
     * One published project: hero, overview, unit types, amenities, location and the enquiry form.
     */
    public function show(string $slug): View
    {
        $project = Project::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('website.project', ['project' => ProjectDetails::for($project)]);
    }
}
