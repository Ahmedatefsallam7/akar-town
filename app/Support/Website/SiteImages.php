<?php

namespace App\Support\Website;

use Illuminate\Support\Facades\Storage;
use Modules\GeneralSettings\Settings\WebsiteImagesSettings;
use Spatie\LaravelSettings\Exceptions\MissingSettings;

/**
 * The website's own photos: the one uploaded in Settings → Website Images, else the design's (in public/images/website).
 * Phones take the 900 px copy.
 */
class SiteImages
{
    /** The design's photos: [full, phone copy]. */
    private const DEFAULTS = [
        'page_header' => ['why-bg.webp', 'why-bg.webp'],
        'why_background' => ['why-bg.webp', 'why-bg.webp'],
        'contact_hero' => ['contact-hero.webp', 'contact-hero-small.webp'],
        'careers_hero' => ['careers-hero.webp', 'careers-hero-small.webp'],
        'about_hero' => ['about-hero.webp', 'about-hero-small.webp'],
        'footer_background' => ['footer-bg.webp', 'footer-bg-small.webp'],
    ];

    /**
     * @return array{0: string, 1: string} the photo's URL and its phone copy's URL
     */
    public static function get(string $key): array
    {
        $path = self::settings()?->{$key};

        if (! $path) {
            [$full, $small] = self::DEFAULTS[$key];

            return [asset('images/website/'.$full), asset('images/website/'.$small)];
        }

        $disk = Storage::disk('public');
        $small = static::smallPath($path);

        return [$disk->url($path), $disk->exists($small) ? $disk->url($small) : $disk->url($path)];
    }

    /** The uploaded logo's URL (empty: the built-in SVG logo is used). */
    public static function logo(): ?string
    {
        $path = self::settings()?->logo;

        return $path ? Storage::disk('public')->url($path) : null;
    }

    private static function settings(): ?WebsiteImagesSettings
    {
        try {
            return app(WebsiteImagesSettings::class);
        } catch (MissingSettings) {
            return null;
        }
    }

    /** "settings/images/x.jpg" → "settings/images/x-small.webp". */
    public static function smallPath(string $path): string
    {
        return preg_replace('/\.[^.\/]+$/', '', $path).'-small.webp';
    }
}
