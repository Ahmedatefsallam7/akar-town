<?php

namespace Modules\Lookups\Support;

use Illuminate\Support\Facades\DB;
use Modules\Lookups\Models\Lookup;

/**
 * Columns in other modules that point at lookups (e.g. locations.city_id, users.role_id). A lookup that is used
 * there, or holds a used value inside it, can't be deleted, so no record loses its value. Trashed records count
 * too (they can come back). Each module registers its columns in its service provider.
 */
class LookupUsage
{
    /**
     * @var array<string, array<string>> table => columns
     */
    private static array $columns = [];

    /**
     * @param  array<string>  $columns
     */
    public static function register(string $table, array $columns): void
    {
        static::$columns[$table] = array_values(array_unique([...static::$columns[$table] ?? [], ...$columns]));
    }

    /**
     * Whether the lookup or anything inside it (its nested-set range) is used.
     */
    public static function isUsed(Lookup $lookup): bool
    {
        $lft = $lookup->getLft();
        $rgt = $lookup->getRgt();

        foreach (static::usedPositions() as $position) {
            if ($position >= $lft && $position <= $rgt) {
                return true;
            }
        }

        return false;
    }

    /**
     * How many rows of a registered table (trashed ones too) use this exact lookup in one of its columns, e.g. the
     * properties linked to a compound. All the counts of the table are read once per request (one grouped query per
     * column), whatever the number of rows on the page.
     */
    public static function countIn(string $table, Lookup $lookup): int
    {
        return static::countsIn($table)[$lookup->getKey()] ?? 0;
    }

    /**
     * @return array<int, int> lookup id => rows
     */
    private static function countsIn(string $table): array
    {
        return once(function () use ($table) {
            $counts = [];
            foreach (static::$columns[$table] ?? [] as $column) {
                DB::table($table)->selectRaw("{$column} as id, count(*) as total")->whereNotNull($column)->groupBy($column)
                    ->pluck('total', 'id')
                    ->each(function ($total, $id) use (&$counts) {
                        $counts[$id] = ($counts[$id] ?? 0) + (int) $total;
                    });
            }

            return $counts;
        });
    }

    /**
     * The tree position (_lft) of every used lookup, read once per request whatever the number of rows on the page:
     * the distinct used ids (each column read from its index), then their positions by primary key.
     *
     * @return array<int>
     */
    private static function usedPositions(): array
    {
        return once(function () {
            $used = null;

            foreach (static::$columns as $table => $columns) {
                foreach ($columns as $column) {
                    $values = DB::table($table)->select("{$column} as id")->whereNotNull($column)->distinct();
                    $used = $used ? $used->union($values) : $values;
                }
            }

            $ids = $used?->pluck('id')->all() ?? [];

            return $ids === [] ? [] : DB::table('lookups')->whereIn('id', $ids)->pluck('_lft')->map(fn ($lft) => (int) $lft)->all();
        });
    }
}
