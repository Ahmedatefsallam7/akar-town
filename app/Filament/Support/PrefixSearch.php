<?php

namespace App\Filament\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Fast search for big tables: matches values that START with the term ("ahm" finds "Ahmed"), so MySQL can use an
 * index. "%term%" (Filament's default) can't use one: with 300,000 users it took ~1 s per query and kept growing,
 * while "term%" takes under 1 ms. A term with a space ("Ahmed Atef") also matches first name + last name.
 *
 * Every searched column needs an index. Used by the Users table search and the global search.
 */
class PrefixSearch
{
    /**
     * @param  array<string>  $columns  e.g. ['first_name', 'last_name', 'email']
     * @param  array{0: string, 1: string}|null  $fullName  e.g. ['first_name', 'last_name']
     */
    public static function apply(Builder $query, string $search, array $columns, ?array $fullName = null): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $prefix = static::escape($search).'%';

        return $query->where(function (Builder $query) use ($columns, $prefix, $search, $fullName) {
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', $prefix);
            }

            if ($fullName !== null && str_contains($search, ' ')) {
                [$first, $last] = explode(' ', $search, 2);

                $query->orWhere(fn (Builder $query) => $query
                    ->where($fullName[0], 'like', static::escape($first).'%')
                    ->where($fullName[1], 'like', static::escape(trim($last)).'%'));
            }
        });
    }

    /**
     * For a Filament column: ->searchable(query: PrefixSearch::using([...])).
     */
    public static function using(array $columns, ?array $fullName = null): Closure
    {
        return fn (Builder $query, string $search): Builder => static::apply($query, $search, $columns, $fullName);
    }

    /**
     * A typed "%" or "_" is a wildcard in LIKE; escape it so the search stays a fast prefix match.
     */
    private static function escape(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
