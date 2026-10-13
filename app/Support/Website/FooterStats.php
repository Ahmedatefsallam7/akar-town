<?php

namespace App\Support\Website;

use Illuminate\Support\Facades\Cache;
use Modules\GeneralSettings\Settings\AboutUsSettings;
use Modules\Locations\Models\Location;
use Modules\Units\Models\Unit;

/**
 * The footer's numbers, on every page: cached for 10 minutes (plain array: the database cache store can't restore
 * objects), so a page view runs no count queries.
 */
class FooterStats
{
    /**
     * @return array{listings: int, locations: int, years: int}
     */
    public static function get(): array
    {
        return Cache::remember('website.footer_stats', now()->addMinutes(10), fn () => [
            'listings' => Unit::query()->where('is_published', true)->count(),
            'locations' => Location::query()->where('is_active', true)->count(),
            'years' => app(AboutUsSettings::class)->years_of_excellence,
        ]);
    }
}
