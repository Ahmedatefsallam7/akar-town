<?php

namespace Modules\GeneralSettings\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * The Terms of Use page.
 */
class TermsOfUseSettings extends Settings
{
    public string $description_en;

    public string $description_ar;

    // Each entry: header_en, header_ar, body_en, body_ar.
    public array $entries;

    public static function group(): string
    {
        return 'terms_of_use';
    }
}
