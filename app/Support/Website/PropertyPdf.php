<?php

namespace App\Support\Website;

use Illuminate\Support\Str;
use Modules\Units\Models\Unit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A property's datasheet as a PDF, in English or Arabic (user asked; replaces the design's browser-made jsPDF sheet,
 * whose font had no Arabic). Built on the server (Datasheet, mPDF) from the view website/pdf/property: the page's data
 * (PropertyDetails) in the chosen language, the photos cropped to their boxes, the plans, every Business Info number,
 * the agent and QR codes to the property page and the map.
 */
class PropertyPdf
{
    /**
     * @return array{name: string, content: string} the file name and the PDF
     */
    public static function make(Unit $unit, string $locale): array
    {
        $content = Datasheet::render($locale, function (Datasheet $sheet) use ($unit) {
            $property = PropertyDetails::for($unit);
            $media = $unit->photos(); // loaded by PropertyDetails::for() above

            return ['website.pdf.property', [
                'p' => $property,
                'cover' => $sheet->crop($media->first(), 'large', 1600, 840),
                'thumbs' => $media->slice(1, Datasheet::THUMBS)->map(fn (Media $photo) => $sheet->crop($photo, 'card', 600, 420))->filter()->values()->all(),
                'plans' => collect([
                    ['label' => __('Floor Plan'), 'image' => $sheet->plan($unit->floorPlans()->first())],
                    ['label' => __('Master Plan'), 'image' => $sheet->plan($unit->getFirstMedia(Unit::MASTER_PLAN))],
                ])->filter(fn (array $plan) => $plan['image'])->values()->all(),
                'agentPhoto' => $sheet->agentPhoto($unit->agent ?? $unit->project?->agent),
                'url' => route('properties.show', $unit->slug),
                'mapUrl' => Datasheet::mapUrl($property['latitude'], $property['longitude'], $property['place']),
            ], $property['title']];
        });

        return ['name' => (Str::slug($unit->name_en) ?: 'property').'-'.$locale.'.pdf', 'content' => $content];
    }
}
