<?php

namespace App\Support\Website;

/**
 * The home page's "Prime Destinations": active, featured locations (in the admin's order) with how many published
 * properties each has.
 */
class FeaturedLocations
{
    private const LIMIT = 8;

    /**
     * @return list<array{slug: string, name: string, region: string, country: string, description: ?string, count: int, featured: bool, image: ?string, url: string}>
     */
    public static function get(): array
    {
        return LocationCards::from(LocationCards::query()->where('is_featured', true)->limit(self::LIMIT)->get());
    }
}
