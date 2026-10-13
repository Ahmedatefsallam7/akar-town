<?php

namespace Modules\GeneralSettings\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * The "Why List with Us" block on the Submit Your Property page.
 */
class SubmitPropertySettings extends Settings
{
    public string $why_list_mini_description_en;

    public string $why_list_mini_description_ar;

    // Each point: icon (image path), header_en, header_ar, body_en, body_ar.
    public array $points;

    public static function group(): string
    {
        return 'submit_property';
    }
}
