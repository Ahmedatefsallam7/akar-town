<?php

namespace App\Support\Website;

use Modules\Lookups\Support\LocationTree;

/**
 * Everything the website keeps in the cache, cleared at once by the admin's Refresh button: a safety net, since each
 * one is also cleared by itself when what it shows changes, or after 10 minutes at most.
 */
class WebsiteCaches
{
    public static function forget(): void
    {
        SearchOptions::forgetCompounds();   // the Compound filters
        LocationTree::forget();             // the locations tree (names and paths of places)
        FooterStats::forget();              // the footer's numbers and the listing's total
        GlobalSearch::forgetPopular();      // the search window's popular compounds
        LocationCards::forgetCounts();      // the locations' property counts
    }
}
