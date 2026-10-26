<?php

namespace Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages;

use App\Support\Website\SiteImages;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\GeneralSettings\Filament\Support\GeneralSettingsPage;
use Modules\GeneralSettings\Settings\WebsiteImagesSettings;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Throwable;

/**
 * The website's own photos (heroes, headers, backgrounds, logo). Empty fields keep the design's photos. Each photo is
 * cropped to its place's shape and size, and gets a 900 px WebP copy for phones; a replaced or removed photo is deleted
 * with its copy.
 */
class WebsiteImagesSettingsPage extends GeneralSettingsPage
{
    protected static string $settings = WebsiteImagesSettings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 9;

    /** Photos (not the logo) and their sections. */
    private const PHOTOS = [
        'pages' => ['page_header', 'contact_hero', 'careers_hero', 'about_hero'],
        'backgrounds' => ['why_background', 'footer_background'],
    ];

    /**
     * The shape each photo is cropped to and its saved size, for the place it fills: the listing header is a wide strip,
     * the heroes and backgrounds fill the screen. A photo of another shape would otherwise be zoomed in to fill it.
     */
    private const SIZES = [
        'page_header' => ['3:1', 2400, 800],
        'contact_hero' => ['16:9', 2400, 1350],
        'careers_hero' => ['16:9', 2400, 1350],
        'about_hero' => ['16:9', 2400, 1350],
        'why_background' => ['16:9', 2400, 1350],
        'footer_background' => ['16:9', 2400, 1350],
    ];

    /** The narrowest photo accepted (they span the screen's width). */
    private const MIN_WIDTH = 1200;

    /** Photos in use before the save. */
    private array $photosBeforeSave = [];

    protected static function getTranslationKey(): string
    {
        return 'website_images';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.settings.sections.logo'))
                ->schema([
                    static::photo('logo', 1024)
                        // A logo needs its transparent background: PNG or WebP only (no SVG: it can carry scripts).
                        ->acceptedFileTypes(['image/png', 'image/webp'])
                        ->helperText(__('admin.settings.fields.logo_help')),
                ])
                ->columnSpanFull(),
            ...collect(self::PHOTOS)->map(fn (array $keys, string $section) => Section::make(__("admin.settings.sections.{$section}"))
                ->schema(array_map(fn (string $key) => static::photo($key), $keys))
                ->columns(2)
                ->columnSpanFull())->values()->all(),
        ]);
    }

    private static function photo(string $key, int $maxKb = 5120): FileUpload
    {
        $field = FileUpload::make($key)
            ->label(__("admin.settings.fields.{$key}"))
            ->image()
            ->disk('public')
            ->directory(self::IMAGES_DIRECTORY)
            ->visibility('public')
            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
            ->maxSize($maxKb);

        if (! isset(self::SIZES[$key])) {
            // The logo keeps its own shape, at most 800 px wide.
            return $field->imageResizeMode('contain')->imageResizeTargetWidth('800')->imageResizeTargetHeight('800')->imageResizeUpscale(false);
        }

        [$ratio, $width, $height] = self::SIZES[$key];

        return $field
            ->helperText(__('admin.settings.fields.website_image_help', ['size' => "{$width} × {$height}"]))
            // A smaller photo would be stretched across the screen and look blurry.
            ->rules(['dimensions:min_width='.self::MIN_WIDTH])
            ->validationMessages(['dimensions' => __('admin.settings.fields.website_image_too_small', ['width' => self::MIN_WIDTH])])
            // Cropped to the place's shape (the editor lets the admin pick the part to keep), then saved at its size.
            ->imageCropAspectRatio($ratio)
            ->imageEditor()
            ->imageEditorAspectRatioOptions([$ratio])
            ->imageResizeMode('cover')
            ->imageResizeTargetWidth((string) $width)
            ->imageResizeTargetHeight((string) $height);
    }

    protected function beforeSave(): void
    {
        parent::beforeSave();
        $this->photosBeforeSave = array_filter(app(WebsiteImagesSettings::class)->toArray());
    }

    protected function afterSave(): void
    {
        parent::afterSave(); // deletes the replaced / removed photos

        $now = array_filter(app(WebsiteImagesSettings::class)->toArray());
        $disk = Storage::disk('public');

        foreach (array_diff($this->photosBeforeSave, $now) as $removed) {
            $disk->delete(SiteImages::smallPath($removed));
        }

        foreach ($now as $key => $path) {
            if ($key === 'logo' || ! $disk->exists($path)) {
                continue;
            }
            try {
                // A photo not in its place's shape (e.g. saved before the crop, or the browser couldn't crop it) is
                // cropped from the centre to it, so the page doesn't zoom into a corner of it.
                [$ratio, $width, $height] = self::SIZES[$key];
                [$w, $h] = getimagesize($disk->path($path)) ?: [0, 0];
                if ($w && $h && abs($w / $h - $width / $height) > 0.02) {
                    Image::load($disk->path($path))->fit(Fit::Crop, min($width, $w), (int) round(min($width, $w) * $height / $width))->save();
                    $disk->delete(SiteImages::smallPath($path));
                }

                $small = SiteImages::smallPath($path);
                if (! $disk->exists($small)) {
                    Image::load($disk->path($path))->width(900)->quality(75)->format('webp')->save($disk->path($small));
                }
            } catch (Throwable $e) {
                report($e); // the page then uses the photo as it is
            }
        }
    }
}
