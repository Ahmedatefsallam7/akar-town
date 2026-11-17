<?php

namespace App\Support\Images;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Enums\AlignPosition;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Enums\Unit;
use Spatie\Image\Image;
use Spatie\MediaLibrary\HasMedia;
use Throwable;

/**
 * The sizes made of an uploaded image (AKAR-51), the same for every model. The website only ever shows these (never
 * the original, see ProtectedImages); "large" and "card" are made in the background after an upload.
 *
 * The photos' and plans' website sizes can carry the Akar Town watermark (a large logo in the middle), switched on
 * or off at once with IMAGES_WATERMARK (config images.watermark): it's added when the image is sent, from a copy
 * made once beside the size ("watermarked/") and made again when the size changes.
 */
class MediaSizes
{
    /** The watermark: the logo in white with a soft shadow, so it shows on light and dark photos. */
    public const WATERMARK = 'images/watermark.png';

    /** How much of the photo's width the watermark takes, and how strong it is (0–100). */
    private const WATERMARK_WIDTH = 60;

    private const WATERMARK_ALPHA = 60;

    /**
     * A photo: a 160 px square for the admin lists, then the cards' size and the full size.
     */
    public static function photo(HasMedia $model): void
    {
        $model->addMediaConversion('thumb')->fit(Fit::Crop, 160, 160)->format('webp')->nonQueued();
        $model->addMediaConversion('card')->fit(Fit::Max, 900, 900)->format('webp')->queued();
        $model->addMediaConversion('large')->fit(Fit::Max, 1920, 1920)->format('webp')->queued();
    }

    /**
     * A plan, shown whole (never cropped): a small one for the admin, and a large one (readable details).
     */
    public static function plan(HasMedia $model): void
    {
        $model->addMediaConversion('thumb')->fit(Fit::Max, 160, 160)->format('webp')->nonQueued();
        $model->addMediaConversion('large')->fit(Fit::Max, 2400, 2400)->format('webp')->queued();
    }

    /**
     * A logo, shown whole on its transparent background; "whole", not "large", so it never gets the watermark.
     */
    public static function logo(HasMedia $model): void
    {
        $model->addMediaConversion('thumb')->fit(Fit::Max, 160, 160)->format('webp')->nonQueued();
        $model->addMediaConversion('whole')->fit(Fit::Max, 800, 800)->format('webp')->queued();
    }

    public static function watermarkIsOn(): bool
    {
        return (bool) config('images.watermark');
    }

    /**
     * Whether a file gets the watermark when it's on: the "card" and "large" sizes of a media library image.
     */
    public static function takesWatermark(string $path): bool
    {
        return (bool) preg_match('#^\d+/conversions/[^/]+-(card|large)\.[a-z0-9]+$#i', $path);
    }

    /**
     * The file to send for a path on the media disk: with the watermark on, its watermarked copy (made now if it's
     * missing or older than the size); otherwise the file itself. Falls back to the file if the copy can't be made.
     */
    public static function fileToSend(string $path): string
    {
        $disk = Storage::disk(ProtectedImages::DISK);
        $source = $disk->path($path);

        if (! static::watermarkIsOn() || ! static::takesWatermark($path) || ! is_file($source)) {
            return $source;
        }

        $copy = $disk->path(dirname($path).'/watermarked/'.basename($path));
        if (is_file($copy) && filemtime($copy) >= filemtime($source)) {
            return $copy;
        }

        try {
            File::ensureDirectoryExists(dirname($copy));
            Image::load($source)
                ->watermark(resource_path(self::WATERMARK), AlignPosition::Center, width: self::WATERMARK_WIDTH, widthUnit: Unit::Percent, alpha: self::WATERMARK_ALPHA)
                ->save($copy);

            return $copy;
        } catch (Throwable $e) {
            report($e);

            return $source;
        }
    }
}
