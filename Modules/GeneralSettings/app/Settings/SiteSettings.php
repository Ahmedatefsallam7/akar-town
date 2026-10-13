<?php

namespace Modules\GeneralSettings\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Whether the public website is open (Online) or shows the maintenance page (Offline). Switched from the dashboard.
 */
class SiteSettings extends Settings
{
    public bool $is_live;

    public static function group(): string
    {
        return 'site';
    }
}
