<?php

namespace App\Support\Website;

use Illuminate\Support\Facades\Cache;
use Modules\Developers\Models\Developer;
use Modules\GeneralSettings\Settings\AboutUsSettings;
use Modules\Projects\Models\Project;
use Modules\Units\Models\Unit;

/**
 * The footer's numbers, on every page: cached for 10 minutes (plain array: the database cache store can't restore
 * objects), so a page view runs no count queries.
 */
class FooterStats
{
    private const CACHE_KEY = 'website.footer_counts';

    /**
     * The footer shows projects, developers and years; "listings" is the properties page header's count.
     *
     * @return array{listings: int, projects: int, developers: int, years: int}
     */
    public static function get(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), fn () => [
            'listings' => Unit::query()->where('is_published', true)->count(),
            'projects' => Project::query()->where('is_published', true)->count(),
            'developers' => Developer::query()->where('is_published', true)->count(),
            'years' => app(AboutUsSettings::class)->years_of_excellence,
        ]);
    }

    /**
     * Read again on the next page (the admin's Refresh button).
     */
    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
