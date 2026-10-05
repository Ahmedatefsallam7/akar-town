<?php

namespace Modules\Lookups\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Lookups\Models\Lookup;

/**
 * Columns in other modules that point at lookups (e.g. locations.city_id). A lookup that is used there, or holds a
 * used value inside it, can't be deleted, so no record loses its value. Trashed records count too (they can come
 * back). Each module registers its columns in its service provider.
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
     * Adds "is_used" to a lookups query: one EXISTS per registered column on the nested-set range, so a list
     * needs no query per row.
     */
    public static function addFlag(Builder $query): void
    {
        $table = $query->getModel()->getTable();
        $checks = static::checks($table);

        if ($checks === []) {
            return;
        }

        $query
            ->when($query->getQuery()->columns === null, fn (Builder $query) => $query->select("{$table}.*"))
            ->selectRaw('('.implode(' or ', $checks).') as is_used');
    }

    public static function isUsed(Lookup $lookup): bool
    {
        $checks = static::checks('outer_lookup');

        return $checks !== [] && (bool) DB::table($lookup->getTable(), 'outer_lookup')
            ->where('outer_lookup.id', $lookup->getKey())
            ->selectRaw('('.implode(' or ', $checks).') as is_used')
            ->value('is_used');
    }

    /**
     * "exists (…)" for every registered column, against the lookup row named $outer and everything inside it.
     *
     * @return array<string>
     */
    private static function checks(string $outer): array
    {
        $checks = [];

        foreach (static::$columns as $table => $columns) {
            foreach ($columns as $column) {
                $checks[] = 'exists ('.DB::table($table)
                    ->join('lookups as used_lookup', 'used_lookup.id', '=', "{$table}.{$column}")
                    ->whereColumn('used_lookup._lft', '>=', "{$outer}._lft")
                    ->whereColumn('used_lookup._rgt', '<=', "{$outer}._rgt")
                    ->selectRaw('1')
                    ->toSql().')';
            }
        }

        return $checks;
    }
}
