<?php

namespace App\Support\Website;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\GeneralSettings\Settings\BusinessInfoSettings;
use Modules\Units\Models\Unit;
use Modules\Users\Models\User;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image as SpatieImage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * A property's datasheet as a PDF, in English or Arabic (user asked; replaces the design's browser-made jsPDF sheet,
 * whose font had no Arabic). Built on the server with mPDF from the view website/pdf/property: the page's data
 * (PropertyDetails) in the chosen language, the photos cropped to their boxes, every Business Info number, the agent and
 * a QR code to the property page. Fonts: the site's Archivo and mPDF's built-in DejaVu Sans for Arabic compatibility.
 */
class PropertyPdf
{
    public const LOCALES = ['en', 'ar'];

    /**
     * @return array{name: string, content: string} the file name and the PDF
     */
    public static function make(Unit $unit, string $locale): array
    {
        $previous = App::getLocale();
        App::setLocale($locale);
        $files = [];

        try {
            $property = PropertyDetails::for($unit);
            $business = app(BusinessInfoSettings::class);
            $media = collect([$unit->getFirstMedia(Unit::FEATURED_IMAGE)])->merge($unit->gallery?->media ?? [])->filter()->values();

            $crop = function (?Media $photo, string $size, int $width, int $height) use (&$files): ?string {
                $source = Image::path($photo, $size);
                if (! $source || ! is_file($source)) {
                    return null;
                }
                try {
                    $target = static::tempDir().'/'.Str::random(16).'.jpg';
                    SpatieImage::load($source)->fit(Fit::Crop, $width, $height)->quality(80)->format('jpg')->save($target);

                    return $files[] = $target;
                } catch (Throwable $e) {
                    report($e);

                    return null;
                }
            };

            $preparePlan = function (?Media $plan) use (&$files): ?array {
                $source = Image::path($plan, 'large');
                if (! $source || ! is_file($source)) {
                    return null;
                }
                try {
                    $target = static::tempDir().'/'.Str::random(16).'.jpg';
                    SpatieImage::load($source)->fit(Fit::Max, 1200, 900)->quality(88)->format('jpg')->save($target);
                    $files[] = $target;
                    [$width, $height] = getimagesize($target);
                    $scale = min(82 / $width, 54 / $height);

                    return ['path' => $target, 'width' => round($width * $scale, 2), 'height' => round($height * $scale, 2)];
                } catch (Throwable $e) {
                    report($e);

                    return null;
                }
            };

            $agentUser = $unit->agent ?? $unit->project?->agent;
            $agentPhoto = $agentUser?->avatar ? Storage::disk(User::AVATAR_DISK)->path($agentUser->avatar) : null;
            $mapQuery = $property['latitude'] !== null && $property['longitude'] !== null
                ? $property['latitude'].','.$property['longitude']
                : $property['place'];

            $html = view('website.pdf.property', [
                'p' => $property,
                'rtl' => $locale === 'ar',
                'logo' => $files[] = static::logo(),
                'cover' => $crop($media->first(), 'large', 1600, 840),
                'thumbs' => $media->slice(1)->map(fn (Media $photo) => $crop($photo, 'card', 600, 420))->filter()->values()->all(),
                'plans' => collect([
                    ['label' => __('Floor Plan'), 'media' => $unit->getFirstMedia(Unit::FLOOR_PLAN)],
                    ['label' => __('Master Plan'), 'media' => $unit->getFirstMedia(Unit::MASTER_PLAN)],
                ])->map(fn (array $plan) => ['label' => $plan['label'], 'image' => $preparePlan($plan['media'])])->filter(fn (array $plan) => $plan['image'])->values()->all(),
                'agentPhoto' => $agentPhoto && is_file($agentPhoto) ? $agentPhoto : null,
                'phones' => array_values(array_unique(array_filter([$business->hotline, $business->phone_primary, $business->phone_secondary, $business->phone_tertiary]))),
                'whatsapp' => $business->whatsapp,
                'email' => $business->email,
                'office' => $business->{'headquarters_'.$locale},
                'url' => request()->getSchemeAndHttpHost().route('properties.show', $unit->slug, absolute: false),
                'mapUrl' => 'https://maps.google.com/?q='.rawurlencode((string) $mapQuery),
            ])->render();

            $pdf = new Mpdf(static::config($locale));
            $pdf->SetTitle($property['title']);
            $pdf->SetAuthor(__('Akar Town'));
            $pdf->WriteHTML($html);

            return [
                'name' => (Str::slug($unit->name_en) ?: 'property').'-'.$locale.'.pdf',
                'content' => $pdf->Output('', Destination::STRING_RETURN),
            ];
        } finally {
            File::delete(array_filter($files));
            App::setLocale($previous);
        }
    }

    /**
    * mPDF with Archivo for English and its bundled DejaVu Sans for reliable Arabic glyph shaping.
     */
    private static function config(string $locale): array
    {
        $fontDirs = (new ConfigVariables)->getDefaults()['fontDir'];
        $fontData = (new FontVariables)->getDefaults()['fontdata'];

        return [
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 16,
            'margin_footer' => 6,
            'tempDir' => static::tempDir(),
            'fontDir' => [...$fontDirs, resource_path('fonts/pdf')],
            'fontdata' => $fontData + [
                'archivo' => ['R' => 'Archivo-Regular.ttf', 'B' => 'Archivo-Bold.ttf'],
            ],
            'default_font' => $locale === 'ar' ? 'dejavusans' : 'archivo',
            'directionality' => $locale === 'ar' ? 'rtl' : 'ltr',
        ];
    }

    /**
     * The site's logo in white (the SVG component) as a file for the dark header.
     */
    private static function logo(): string
    {
        $file = static::tempDir().'/'.Str::random(16).'.svg';
        File::put($file, Blade::render('<x-website.logo color="#ffffff" />'));

        return $file;
    }

    private static function tempDir(): string
    {
        $dir = storage_path('app/mpdf');
        File::ensureDirectoryExists($dir);

        return $dir;
    }
}
