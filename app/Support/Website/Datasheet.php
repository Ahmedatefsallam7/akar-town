<?php

namespace App\Support\Website;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\GeneralSettings\Settings\BusinessInfoSettings;
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
 * What the property and project PDF datasheets share (PropertyPdf, ProjectPdf): mPDF with the site's fonts (Archivo,
 * and mPDF's DejaVu Sans for Arabic), the chosen language for the build, photos prepared as temporary files (deleted
 * after), the white logo and the contact details from Business Info. The views share website/pdf/partials.
 */
class Datasheet
{
    public const LOCALES = ['en', 'ar'];

    /** Gallery photos in a sheet (each one is cropped on the request: a big gallery would be slow and heavy). */
    public const THUMBS = 6;

    /** @var list<string> temporary files of this build */
    private array $files = [];

    private function __construct(public readonly string $locale) {}

    /**
     * Builds a PDF in the given language: $build gets the Datasheet (for its photos) and returns [view, data, title].
     *
     * @param  Closure(self): array{0: string, 1: array<string, mixed>, 2: string}  $build
     */
    public static function render(string $locale, Closure $build): string
    {
        $previous = App::getLocale();
        App::setLocale($locale);
        $sheet = new self($locale);

        try {
            [$view, $data, $title] = $build($sheet);

            $html = view($view, $data + [
                'rtl' => $locale === 'ar',
                'logo' => $sheet->logo(),
                ...$sheet->contact(),
            ])->render();

            $pdf = new Mpdf(static::config($locale));
            $pdf->SetTitle($title);
            $pdf->SetAuthor(__('Akar Town'));
            $pdf->WriteHTML($html);

            return $pdf->Output('', Destination::STRING_RETURN);
        } finally {
            File::delete($sheet->files);
            App::setLocale($previous);
        }
    }

    /**
     * A photo cropped to its box (null when missing or unreadable).
     */
    public function crop(?Media $photo, string $size, int $width, int $height): ?string
    {
        return $this->prepare($photo, $size, fn (SpatieImage $image) => $image->fit(Fit::Crop, $width, $height)->quality(80));
    }

    /**
     * A plan drawing in its own shape (never cropped), with its size in mm to fit a 82 × 54 mm box.
     *
     * @return array{path: string, width: float, height: float}|null
     */
    public function plan(?Media $plan): ?array
    {
        $path = $this->prepare($plan, 'large', fn (SpatieImage $image) => $image->fit(Fit::Max, 1200, 900)->quality(88));
        if (! $path) {
            return null;
        }
        [$width, $height] = getimagesize($path);
        $scale = min(82 / $width, 54 / $height);

        return ['path' => $path, 'width' => round($width * $scale, 2), 'height' => round($height * $scale, 2)];
    }

    /**
     * The agent's photo file, when there is one on disk.
     */
    public function agentPhoto(?User $agent): ?string
    {
        $path = $agent?->avatar ? Storage::disk(User::AVATAR_DISK)->path($agent->avatar) : null;

        return $path && is_file($path) ? $path : null;
    }

    public static function mapUrl(int|float|string|null $latitude, int|float|string|null $longitude, ?string $place): string
    {
        $query = $latitude !== null && $longitude !== null ? $latitude.','.$longitude : (string) $place;

        return 'https://maps.google.com/?q='.rawurlencode($query);
    }

    /**
     * @param  Closure(SpatieImage): SpatieImage  $edit
     */
    private function prepare(?Media $media, string $size, Closure $edit): ?string
    {
        $source = Image::path($media, $size);
        if (! $source || ! is_file($source)) {
            return null;
        }

        try {
            $target = static::tempDir().'/'.Str::random(16).'.jpg';
            $edit(SpatieImage::load($source))->format('jpg')->save($target);

            return $this->files[] = $target;
        } catch (Throwable $e) {
            report($e); // the sheet goes on without this photo

            return null;
        }
    }

    /**
     * Every Business Info number (no repeats), WhatsApp, e-mail and the office address in the sheet's language.
     *
     * @return array{phones: list<string>, whatsapp: ?string, email: ?string, office: ?string}
     */
    private function contact(): array
    {
        $business = app(BusinessInfoSettings::class);

        return [
            'phones' => array_values(array_unique(array_filter([$business->hotline, $business->phone_primary, $business->phone_secondary, $business->phone_tertiary]))),
            'whatsapp' => $business->whatsapp,
            'email' => $business->email,
            'office' => $business->{'headquarters_'.$this->locale},
        ];
    }

    /**
     * The site's logo in white (the SVG component) as a file for the dark header.
     */
    private function logo(): string
    {
        $file = static::tempDir().'/'.Str::random(16).'.svg';
        File::put($file, Blade::render('<x-website.logo color="#ffffff" />'));

        return $this->files[] = $file;
    }

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

    private static function tempDir(): string
    {
        $dir = storage_path('app/mpdf');
        File::ensureDirectoryExists($dir);

        return $dir;
    }
}
