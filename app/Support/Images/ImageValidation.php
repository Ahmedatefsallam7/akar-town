<?php

namespace App\Support\Images;

use App\Rules\NotTooBigImage;

/**
 * The checks an uploaded image goes through (AKAR-50), from its field's sizes on Settings → Image Sizes: the formats
 * and file size it takes, the smallest image it accepts, and no image too big to open. Used by the admin's upload
 * fields (App\Filament\Forms\ImageRules) and the website's forms.
 */
class ImageValidation
{
    /**
     * Every rule for one uploaded image of a field, for a Laravel / Livewire validator.
     *
     * @return list<string|NotTooBigImage>
     */
    public static function rules(string $field): array
    {
        $image = ImageSizes::field($field);
        $extensions = array_merge(...array_map(fn (string $format) => $format === 'jpg' ? ['jpg', 'jpeg'] : [$format], $image['formats']));

        return array_values(array_filter([
            'image',
            'mimes:'.implode(',', $extensions),
            'max:'.($image['mb'] * 1024),
            static::minimumRule($field),
            static::checksPixels($field) ? new NotTooBigImage : null,
        ]));
    }

    /**
     * The file types a field takes, e.g. ["image/jpeg", "image/webp"] (also for a file input's accept attribute).
     *
     * @return list<string>
     */
    public static function mimeTypes(string $field): array
    {
        return array_map(fn (string $format) => ImageSizes::FORMATS[$format], ImageSizes::field($field)['formats']);
    }

    /**
     * Laravel's rule for the smallest image accepted, or null when any size goes.
     */
    public static function minimumRule(string $field): ?string
    {
        [$width, $height] = ImageSizes::field($field)['min'];
        $limits = array_filter(['min_width' => $width, 'min_height' => $height]);

        return $limits ? 'dimensions:'.implode(',', array_map(fn ($value, $name) => "{$name}={$value}", $limits, array_keys($limits))) : null;
    }

    /**
     * Whether a field's images are checked for too many pixels: not the ones kept as uploaded (360°), which the server
     * never opens and 360° cameras make huge.
     */
    public static function checksPixels(string $field): bool
    {
        return ImageSizes::field($field)['fit'] !== 'none';
    }
}
