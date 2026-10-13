<?php

namespace Modules\Lookups\Support;

use Illuminate\Support\Collection;
use Modules\Lookups\Models\Lookup;

/**
 * The location chain (Country › City › Region › Area › Sub-Area): the lookup tree under "locations". The whole
 * tree is small, so it's read in ONE query once per request and every select, filter and check works from it.
 */
class LocationTree
{
    public const ROOT_SLUG = 'locations';

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
     * @return array{root: ?int, nodes: Collection<int, Lookup>, children: array<int, Collection<int, Lookup>>, levels: array<int, int>}
     */
    private static function tree(): array
    {
        return once(function () {
            $root = fn (string $column) => Lookup::query()->select($column)->whereNull('parent_id')->where('slug', self::ROOT_SLUG)->limit(1);

            $nodes = Lookup::query()
                ->where('_lft', '>=', $root('_lft'))
                ->where('_rgt', '<=', $root('_rgt'))
                ->orderBy('order')
                ->orderBy('name_en')
                ->get(['id', 'parent_id', 'name_en', 'name_ar', 'is_active'])
                ->keyBy('id');

            $rootId = $nodes->firstWhere('parent_id', null)?->id;
            $children = $nodes->groupBy('parent_id')->all();

            $levels = [];
            $walk = function (?int $parentId, int $level) use (&$walk, &$levels, $children) {
                foreach ($children[$parentId] ?? [] as $node) {
                    $levels[$node->id] = $level;
                    $walk($node->id, $level + 1);
                }
            };
            $walk($rootId, 1);

            return ['root' => $rootId, 'nodes' => $nodes, 'children' => $children, 'levels' => $levels];
        });
    }
}
