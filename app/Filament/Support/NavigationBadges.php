<?php

namespace App\Filament\Support;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;

/**
 * Sidebar (and dashboard) counts for every resource in ONE cache entry: one cache query per request instead of one
 * per resource, and remembered for the rest of the request. Each resource says how to count with
 * getNavigationBadgeCount(). Counts may be up to 2 minutes old; Utilities → Refresh clears them.
 */
class NavigationBadges
{
    private const TTL = 120;

    public static function get(string $resource): ?string
    {
        $count = static::counts(static::cacheKey())[$resource] ?? null;

        return $count === null ? null : (string) $count;
    }

    public static function forget(): void
    {
        Cache::deleteMultiple(['navigation_badges_all', 'navigation_badges_non_admin']);
    }

    /**
     * Admins and non-admins can see different users, so their counts are cached separately.
     */
    private static function cacheKey(): string
    {
        return 'navigation_badges_'.(auth()->user()?->isAdmin() ? 'all' : 'non_admin');
    }

    /**
     * @return array<class-string, int>
     */
    private static function counts(string $key): array
    {
        return once(fn () => Cache::remember($key, self::TTL, fn () => collect(Filament::getCurrentOrDefaultPanel()->getResources())
            ->filter(fn (string $resource) => method_exists($resource, 'getNavigationBadgeCount'))
            ->mapWithKeys(fn (string $resource) => [$resource => $resource::getNavigationBadgeCount()])
            ->all()));
    }
}
