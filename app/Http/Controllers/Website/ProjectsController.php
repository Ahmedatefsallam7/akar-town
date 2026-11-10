<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\Website\ProjectDetails;
use App\Support\Website\ProjectPdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Modules\Projects\Models\Project;

class ProjectsController extends Controller
{
    /**
     * The project's datasheet as a PDF download, in English or Arabic (the visitor picks; see ProjectPdf).
     */
    public function pdf(string $slug, string $locale): Response
    {
        $project = Project::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();
        ['name' => $name, 'content' => $content] = ProjectPdf::make($project, $locale);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }

    /**
     * One published project: hero, overview, unit types, amenities, location and the enquiry form.
     */
    public function show(string $slug): View
    {
        $project = Project::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('website.project', ['project' => ProjectDetails::for($project)]);
    }
}
