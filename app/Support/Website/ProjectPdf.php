<?php

namespace App\Support\Website;

use Illuminate\Support\Str;
use Modules\Projects\Models\Project;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A project's datasheet as a PDF, in English or Arabic (user asked; replaces the design's browser-made jsPDF sheet,
 * whose font had no Arabic). Built on the server (Datasheet, mPDF) from the view website/pdf/project: the page's data
 * (ProjectDetails) in the chosen language, the photos cropped to their boxes, the master plan, the unit types, every
 * Business Info number, the sales advisor and QR codes to the project page and the map.
 */
class ProjectPdf
{
    /**
     * @return array{name: string, content: string} the file name and the PDF
     */
    public static function make(Project $project, string $locale): array
    {
        $content = Datasheet::render($locale, function (Datasheet $sheet) use ($project) {
            $details = ProjectDetails::for($project);
            $media = collect([$project->getFirstMedia(Project::FEATURED_IMAGE)])->merge($project->getMedia(Project::GALLERY))->filter()->values();
            $plan = $sheet->plan($project->getFirstMedia(Project::MASTER_PLAN));

            return ['website.pdf.project', [
                'p' => $details,
                'cover' => $sheet->crop($media->first(), 'large', 1600, 840),
                'thumbs' => $media->slice(1, Datasheet::THUMBS)->map(fn (Media $photo) => $sheet->crop($photo, 'card', 600, 420))->filter()->values()->all(),
                'plans' => $plan ? [['label' => __('Master Plan'), 'image' => $plan]] : [],
                'agentPhoto' => $sheet->agentPhoto($project->agent),
                'url' => route('projects.show', $project->slug),
                'mapUrl' => Datasheet::mapUrl($details['latitude'], $details['longitude'], $details['address']),
            ], $details['title']];
        });

        return ['name' => (Str::slug($project->title_en) ?: 'project').'-'.$locale.'.pdf', 'content' => $content];
    }
}
