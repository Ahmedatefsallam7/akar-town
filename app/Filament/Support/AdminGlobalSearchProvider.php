<?php

namespace App\Filament\Support;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResults;
use Filament\GlobalSearch\Providers\DefaultGlobalSearchProvider;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

/**
 * The admin's global search: Filament's own (one group per resource), except that a resource with
 * getGlobalSearchCategories() gives its own groups (lookups: the types, then each type's values under its name).
 * It also keeps each group's resource icon for the results window (resources/views/vendor/filament-panels/livewire/
 * global-search.blade.php) and marks the searched text in the titles.
 */
class AdminGlobalSearchProvider extends DefaultGlobalSearchProvider
{
    /** @var array<string, string|BackedEnum|Htmlable|null> */
    protected static array $icons = [];

    public function getResults(string $query): ?GlobalSearchResults
    {
        $builder = GlobalSearchResults::make();
        static::$icons = [];

        $resources = Filament::getResources();
        usort($resources, fn (string $a, string $b): int => ($a::getGlobalSearchSort() ?? 0) <=> ($b::getGlobalSearchSort() ?? 0));

        foreach ($resources as $resource) {
            if (! $resource::canGloballySearch()) {
                continue;
            }

            $categories = method_exists($resource, 'getGlobalSearchCategories')
                ? $resource::getGlobalSearchCategories($query)
                : [$resource::getPluralModelLabel() => $resource::getGlobalSearchResults($query)];

            foreach ($categories as $name => $results) {
                if ($results->count()) {
                    $builder->category($name, $results);
                    static::$icons[$name] = $resource::getNavigationIcon();
                }
            }
        }

        return $builder;
    }

    /**
     * The icon of the resource a group of results comes from.
     */
    public static function icon(string $group): string|BackedEnum|Htmlable
    {
        return static::$icons[$group] ?? 'heroicon-o-magnifying-glass';
    }

    /**
     * The title with each place the searched text appears wrapped in <mark> (the text is escaped first).
     */
    public static function highlight(string|Htmlable $title, ?string $search): Htmlable
    {
        if ($title instanceof Htmlable) {
            return $title;
        }

        $words = collect(preg_split('/\s+/u', trim((string) $search)))->filter()->map(fn (string $word) => preg_quote($word, '/'))->implode('|');

        if ($words === '') {
            return new HtmlString(e($title));
        }

        // Split on the matches (kept), then escape every piece: the matches are the odd pieces.
        $pieces = preg_split("/({$words})/iu", $title, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$title];

        return new HtmlString(collect($pieces)->map(fn (string $piece, int $index) => $index % 2 ? '<mark>'.e($piece).'</mark>' : e($piece))->implode(''));
    }
}
