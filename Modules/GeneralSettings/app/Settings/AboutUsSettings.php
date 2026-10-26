<?php

namespace Modules\GeneralSettings\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * The About Us page.
 */
class AboutUsSettings extends Settings
{
    public string $hero_header_en;

    public string $hero_header_ar;

    public string $hero_body_en;

    public string $hero_body_ar;

    public string $story_header_en;

    public string $story_header_ar;

    public string $story_body_en;

    public string $story_body_ar;

    // The round photo beside the story (public disk path); empty: the design's photo.
    public ?string $story_image;

    public int $years_of_excellence;

    public int $stats_happy_clients;

    public int $stats_properties_sold;

    public int $stats_awards_won;

    public int $stats_commitment;

    // Each value: icon (image path), header_en, header_ar, body_en, body_ar.
    public array $core_values;

    public static function group(): string
    {
        return 'about_us';
    }
}
