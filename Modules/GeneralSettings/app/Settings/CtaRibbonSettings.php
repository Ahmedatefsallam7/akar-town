<?php

namespace Modules\GeneralSettings\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * The call-to-action ribbon (the "CTA Ribbon" homepage block).
 */
class CtaRibbonSettings extends Settings
{
    public string $header_en;

    public string $header_ar;

    public string $body_en;

    public string $body_ar;

    public static function group(): string
    {
        return 'cta_ribbon';
    }
}
