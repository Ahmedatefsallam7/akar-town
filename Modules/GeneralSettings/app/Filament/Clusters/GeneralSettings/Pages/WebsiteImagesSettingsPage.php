<?php

namespace Modules\GeneralSettings\Filament\Clusters\GeneralSettings\Pages;

use App\Filament\Forms\ImageRules;
use App\Support\Images\ImageSizes;
use App\Support\Images\ProtectedImages;
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
 * The website's own photos (heroes, headers, backgrounds, logo, the image shared on social media). Empty fields keep
 * the design's photos. Each photo is cropped to its place's shape and size (Settings → Image Sizes), and gets a 900 px
 * WebP copy for phones; a replaced or removed photo is deleted with its copy.
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
        'sharing' => ['share_image'],
    ];

    /** A field's sizes on Settings → Image Sizes (the logo's key there is "site_logo"). */
    private const SIZES_KEY = ['logo' => 'site_logo'];

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
                // A logo needs its transparent background: PNG or WebP only (no SVG: it can carry scripts).
                ->schema([static::photo('logo', __('admin.settings.fields.logo_help'))])
                ->columnSpanFull(),
            ...collect(self::PHOTOS)->map(fn (array $keys, string $section) => Section::make(__("admin.settings.sections.{$section}"))
                ->description($section === 'sharing' ? __('admin.settings.fields.share_image_help') : null)
                ->schema(array_map(fn (string $key) => static::photo($key), $keys))
                ->columns(2)
                ->columnSpanFull())->values()->all(),
        ]);
    }

    /**
     * A photo field: its sizes, formats, hint and crop from Settings → Image Sizes (AKAR-50).
     */
    private static function photo(string $key, ?string $note = null): FileUpload
    {
        return ImageRules::apply(FileUpload::make($key)
            ->label(__("admin.settings.fields.{$key}"))
            ->disk(ProtectedImages::DISK)
            ->directory(self::IMAGES_DIRECTORY)
            ->visibility('private'), self::SIZES_KEY[$key] ?? $key, $note);
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
        $disk = Storage::disk(ProtectedImages::DISK);

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
                [$width, $height] = ImageSizes::field($key)['recommended'];
                [$w, $h] = getimagesize($disk->path($path)) ?: [0, 0];
                if ($w && $h && abs($w / $h - $width / $height) > 0.02) {
                    Image::load($disk->path($path))->fit(Fit::Crop, min($width, $w), (int) round(min($width, $w) * $height / $width))->save();
                    $disk->delete(SiteImages::smallPath($path));
                }

                // A 900 px copy for phones (the share image isn't shown on the site: no copy).
                $small = SiteImages::smallPath($path);
                if ($key !== 'share_image' && ! $disk->exists($small)) {
                    Image::load($disk->path($path))->width(900)->quality(75)->format('webp')->save($disk->path($small));
                }
            } catch (Throwable $e) {
                report($e); // the page then uses the photo as it is
            }
        }
    }
}
