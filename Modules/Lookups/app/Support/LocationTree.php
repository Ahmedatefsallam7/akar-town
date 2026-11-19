<?php

namespace Modules\Lookups\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Lookups\Models\Lookup;

/**
 * The location chain (Country › City › Region › Area › Compound; the last level was the sub-area, AKAR-46, and keeps
 * its sub_area_id column): the lookup tree under "locations". The whole
 * tree is small, so it's read in ONE query once per request and every select, filter and check works from it.
 */
class LocationTree
{
    public const ROOT_SLUG = 'locations';

    private const CACHE_KEY = 'lookups.location_tree';

    /** Goes up on every change, so this request reads the tree again (see tree()). */
    private static int $version = 0;

    /** The places of the tree this request read (id => true), to tell a place from any other lookup. */
    private static ?array $ids = null;

    /**
     * Chain columns, top level first (level 1 = country).
     */
    public const LEVELS = ['country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id'];

    /**
     * Active values under a parent (null = countries), plus $keep so a saved value that was later switched off
     * still shows. Sorted by display order, then name.
     *
     * @return array<int, string>
     */
    public static function options(?int $parentId, ?int $keep = null): array
    {
        $tree = static::tree();

        return ($tree['children'][$parentId ?? $tree['root']] ?? collect())
            ->filter(fn (Lookup $node) => $node->is_active || $node->id === $keep)
            ->mapWithKeys(fn (Lookup $node) => [$node->id => $node->getLocalized('name')])
            ->all();
    }

    /**
     * Every value at a level (1-5), for filters. Below the country level the parent is added, because two
     * areas in different cities can share a name.
     *
     * @return array<int, string>
     */
    public static function levelOptions(int $level): array
    {
        $tree = static::tree();

        return collect(array_keys($tree['levels'], $level, true))
            ->mapWithKeys(function (int $id) use ($tree, $level) {
                $node = $tree['nodes'][$id];
                $parent = $level > 1 ? $tree['nodes'][$node->parent_id] ?? null : null;

                return [$id => $parent ? $node->getLocalized('name').' — '.$parent->getLocalized('name') : $node->getLocalized('name')];
            })
            ->all();
    }

    /**
     * A saved chain as text, top level first, e.g. "Egypt › Cairo › New Cairo". Empty when nothing is chosen.
     *
     * @param  array<int|null>  $ids  country_id … sub_area_id
     */
    public static function path(array $ids): string
    {
        $nodes = static::tree()['nodes'];

        return collect($ids)
            ->filter()
            ->map(fn (int $id) => $nodes->get($id)?->getLocalized('name'))
            ->filter()
            ->implode(' › ');
    }

    /**
     * A saved chain as a short place, the most specific level and the country, e.g. "New Cairo, Egypt" (the
     * website's cards). Empty when nothing is chosen.
     *
     * @param  array<int|null>  $ids  country_id … sub_area_id
     */
    public static function place(array $ids): string
    {
        $ids = array_values(array_filter($ids));
        $nodes = static::tree()['nodes'];
        $chosen = count($ids) > 1 ? [end($ids), $ids[0]] : $ids;

        return collect($chosen)
            ->map(fn (int $id) => $nodes->get($id)?->getLocalized('name'))
            ->filter()
            ->implode(app()->getLocale() === 'ar' ? '، ' : ', ');
    }

    /**
     * The places whose English or Arabic name starts with the text, as their chain column and id (from the tree read
     * once per request), e.g. "new" → ['column' => 'region_id', 'id' => <New Cairo>].
     *
     * @return list<array{column: string, id: int}>
     */
    public static function startingWith(string $text): array
    {
        $text = mb_strtolower(trim($text));
        $tree = static::tree();

        if ($text === '') {
            return [];
        }

        return $tree['nodes']
            ->filter(fn (Lookup $node) => isset($tree['levels'][$node->id])
                && (str_starts_with(mb_strtolower((string) $node->name_en), $text) || str_starts_with(mb_strtolower((string) $node->name_ar), $text)))
            ->map(fn (Lookup $node) => ['column' => static::LEVELS[$tree['levels'][$node->id] - 1], 'id' => $node->id])
            ->values()
            ->all();
    }

    /**
     * The compounds (level 5) whose English or Arabic name holds the text anywhere, compared as SearchText does (case,
     * Arabic letter variants), from the tree read once per request: no query of its own (AKAR-47). Switched-off
     * compounds only when asked (the admin).
     *
     * @return list<int>
     */
    public static function compoundsMatching(string $text, bool $withInactive = false): array
    {
        $text = SearchText::normalize($text);
        if (mb_strlen($text) < 2) {
            return [];
        }
        $tree = static::tree();

        return $tree['nodes']
            ->filter(fn (Lookup $node) => ($tree['levels'][$node->id] ?? null) === 5
                && ($withInactive || $node->is_active)
                && (SearchText::contains($node->name_en, $text) || SearchText::contains($node->name_ar, $text)))
            ->keys()
            ->all();
    }

    /**
     * The compounds whose name holds the text with a small typo (SearchText::nearly, AKAR-48): "madinsty" finds
     * Madinaty. From the tree, no query; active compounds only.
     *
     * @return list<int>
     */
    public static function compoundsNearly(string $text): array
    {
        $text = SearchText::normalize($text);
        $tree = static::tree();

        return $tree['nodes']
            ->filter(fn (Lookup $node) => ($tree['levels'][$node->id] ?? null) === 5 && $node->is_active
                && (SearchText::nearly($node->name_en, $text) || SearchText::nearly($node->name_ar, $text)))
            ->keys()
            ->all();
    }

    /**
     * A place and every place above it (its chain), e.g. a compound with its area, region, city and country.
     *
     * @return list<int>
     */
    public static function withAncestors(int $id): array
    {
        $nodes = static::tree()['nodes'];
        $chain = [];
        for ($node = $nodes->get($id); $node; $node = $nodes->get($node->parent_id)) {
            if (static::levelOf($node->id)) {
                $chain[] = $node->id;
            }
        }

        return $chain;
    }

    /**
     * The level of a value in the tree, 1 (country) … 5 (compound); null when it isn't a place.
     */
    public static function levelOf(int $id): ?int
    {
        return static::tree()['levels'][$id] ?? null;
    }

    /**
     * @return array{root: ?int, nodes: Collection<int, Lookup>, children: array<int, Collection<int, Lookup>>, levels: array<int, int>}
     */
    private static function tree(): array
    {
        // Kept for every request (performance review: one query of the whole tree on most pages), cleared when a
        // lookup changes (see forget()); an hour at most as a safety net. Cached as plain rows (the cache never holds
        // objects), made into models once per request. once() keys on the closure's variables, so a new version
        // means a fresh read without flushing the request's other once() values.
        $version = static::$version;

        return once(function () use ($version) {
            $cached = Cache::remember(self::CACHE_KEY, now()->addHour(), static fn () => static::build());
            $nodes = Lookup::hydrate($cached['rows'])->keyBy('id');
            static::$ids = $version === static::$version ? array_flip($nodes->keys()->all()) : null;

            return ['root' => $cached['root'], 'nodes' => $nodes, 'children' => $nodes->groupBy('parent_id')->all(), 'levels' => $cached['levels']];
        });
    }

    /**
     * After a location is added, changed, moved or re-ordered: the tree is read again.
     */
    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        static::$version++;
        static::$ids = null;
    }

    /**
     * After a lookup is saved, deleted or restored: the tree is read again only when the lookup is (or was, or goes
     * under) a place. A role, an amenity or a department leaves it as it is. When the tree isn't read yet in this
     * request, it's cleared anyway (no query just to decide).
     */
    public static function forgetFor(Lookup $lookup): void
    {
        $ids = static::$ids;
        $inTree = fn (mixed $id) => $id !== null && isset($ids[$id]);

        if ($ids === null || ($lookup->slug === self::ROOT_SLUG && $lookup->parent_id === null)
            || $inTree($lookup->getKey()) || $inTree($lookup->parent_id) || $inTree($lookup->getOriginal('parent_id'))) {
            static::forget();
        }
    }

    /**
     * The tree as plain rows (for the cache): every location in the admin's order, the root, and each one's level.
     *
     * @return array{root: ?int, rows: list<array<string, mixed>>, levels: array<int, int>}
     */
    private static function build(): array
    {
        $root = fn (string $column) => Lookup::query()->select($column)->whereNull('parent_id')->where('slug', self::ROOT_SLUG)->limit(1);

        $rows = Lookup::query()
            ->where('_lft', '>=', $root('_lft'))
            ->where('_rgt', '<=', $root('_rgt'))
            ->orderBy('order')
            ->orderBy('name_en')
            ->toBase()
            ->get(['id', 'parent_id', 'name_en', 'name_ar', 'is_active'])
            ->map(fn (object $row) => (array) $row)
            ->all();

        $rootId = collect($rows)->firstWhere('parent_id', null)['id'] ?? null;
        $children = collect($rows)->groupBy('parent_id')->all();

        $levels = [];
        $walk = function (?int $parentId, int $level) use (&$walk, &$levels, $children) {
            foreach ($children[$parentId] ?? [] as $node) {
                $levels[$node['id']] = $level;
                $walk($node['id'], $level + 1);
            }
        };
        $walk($rootId, 1);

        return ['root' => $rootId, 'rows' => $rows, 'levels' => $levels];
    }
}
