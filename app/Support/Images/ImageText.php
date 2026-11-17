<?php

namespace App\Support\Images;

/**
 * How image sizes are written (AKAR-50), the same in the admin, the website and the Excel sheet: "1920 × 1440 px",
 * "4:3", "JPG, PNG, WebP", the smallest image accepted, and the messages for an image too small or too big. Sizes stay
 * left to right inside Arabic text.
 */
class ImageText
{
    /**
     * "1920 × 1440", kept left to right inside Arabic text (a page or an Arabic sheet would show "1440 × 1920").
     *
     * @param  array{int, int}  $size
     */
    public static function dimensions(array $size): string
    {
        return "\u{2066}{$size[0]} × {$size[1]}\u{2069}";
    }

    /**
     * "1920 × 1440 px".
     *
     * @param  array{int, int}  $size
     */
    public static function size(array $size): string
    {
        return static::dimensions($size).' px';
    }

    /**
     * The shape of a size, e.g. 1920 × 1440 → "4:3" (1200 × 630 → "40:21").
     *
     * @param  array{int, int}  $size
     */
    public static function ratio(array $size): string
    {
        [$width, $height] = $size;
        for ($a = $width, $b = $height; $b !== 0;) {
            [$a, $b] = [$b, $a % $b];
        }

        return $a > 0 ? ($width / $a).':'.($height / $a) : '';
    }

    /**
     * "JPG, PNG, WebP".
     *
     * @param  list<string>  $formats
     */
    public static function formats(array $formats): string
    {
        return implode(', ', array_map(fn (string $format) => $format === 'webp' ? 'WebP' : strtoupper($format), $formats));
    }

    /**
     * The smallest image accepted, short: "1200 × 800", "1200 px wide", "800 px high" or "Any size".
     *
     * @param  array{int, int}  $min
     */
    public static function minimum(array $min, ?string $locale = null): string
    {
        [$width, $height] = $min;

        return match (true) {
            $width > 0 && $height > 0 => static::dimensions($min),
            $width > 0 => __(':width px wide', ['width' => $width], $locale),
            $height > 0 => __(':height px high', ['height' => $height], $locale),
            default => __('Any size', [], $locale),
        };
    }

    /**
     * The message for an image smaller than a field accepts.
     */
    public static function tooSmall(string $field): string
    {
        return __('This image is too small: it must be at least :size, or it would look blurry. Please upload a larger one.', ['size' => static::minimum(ImageSizes::field($field)['min'])]);
    }

    /**
     * The message for an image with too many pixels.
     */
    public static function tooBig(): string
    {
        return __('This image is too big: at most :max megapixels (e.g. 8000 × 6000). Please make it smaller.', ['max' => ImageSizes::MAX_PIXELS / 1_000_000]);
    }
}
