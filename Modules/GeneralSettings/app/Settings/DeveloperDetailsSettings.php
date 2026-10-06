<?php

namespace Modules\GeneralSettings\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * The "What to Expect" cards on developer pages.
 */
class DeveloperDetailsSettings extends Settings
{
    // Each card: icon (image path), header_en, header_ar, body_en, body_ar.
    public array $what_to_expect_cards;

    public static function group(): string
    {
        return 'developer_details';
    }
}
