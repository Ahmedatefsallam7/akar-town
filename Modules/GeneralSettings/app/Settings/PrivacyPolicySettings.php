<?php

namespace Modules\GeneralSettings\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * The Privacy Policy page.
 */
class PrivacyPolicySettings extends Settings
{
    public string $description_en;

    public string $description_ar;

    // Each entry: header_en, header_ar, body_en, body_ar.
    public array $entries;

    public static function group(): string
    {
        return 'privacy_policy';
    }
}
