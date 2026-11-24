<?php

namespace App\Filament\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Fast search for big tables: matches values that START with the term ("ahm" finds "Ahmed"), so MySQL can use an
 * index. "%term%" (Filament's default) can't use one: with 300,000 users it took ~1 s per query and kept growing,
 * while "term%" takes under 1 ms. A term with a space ("Ahmed Atef") also matches first name + last name.
 *
 * Every searched column needs an index. Used by the Users table search and the global search. Name columns can also
 * match from any word ($anyWord: "Ali" finds "Sara Ali"), through a full-text index on MySQL.
 */
class PrefixSearch
{
    /**
     * @param  array<string>  $columns  e.g. ['first_name', 'last_name', 'email']
     * @param  array{0: string, 1: string}|null  $fullName  e.g. ['first_name', 'last_name']
     * @param  array<string>  $anyWord  name columns where the words also match from any word, not only the start
     *                                  ("Ali" finds "Sara Ali"); each needs a full-text index on MySQL (migration
     *                                  2026_11_24_100000_add_name_word_search_indexes)
     */
    public static function apply(Builder $query, string $search, array $columns, ?array $fullName = null, array $anyWord = []): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $prefix = static::escape($search).'%';

        return $query->where(function (Builder $query) use ($columns, $prefix, $search, $fullName, $anyWord) {
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', $prefix);
            }

            if ($fullName !== null && str_contains($search, ' ')) {
                [$first, $last] = explode(' ', $search, 2);

                $query->orWhere(fn (Builder $query) => $query
                    ->where($fullName[0], 'like', static::escape($first).'%')
                    ->where($fullName[1], 'like', static::escape(trim($last)).'%'));
            }

            foreach ($anyWord as $column) {
                static::anyWord($query, $column, $search);
            }
        });
    }

    /**
     * For a Filament column: ->searchable(query: PrefixSearch::using([...])).
     */
    public static function using(array $columns, ?array $fullName = null, array $anyWord = []): Closure
    {
        return fn (Builder $query, string $search): Builder => static::apply($query, $search, $columns, $fullName, $anyWord);
    }

    /**
     * Every typed word starts a word of the column, in any order. MySQL: the full-text index in boolean mode
     * ("+sara* +ali*"; words under its minimum length, 3 letters, are left to the prefix match). Other databases
     * (the tests' SQLite): each word at the start or after a space.
     */
    private static function anyWord(Builder $query, string $column, string $search): void
    {
        $words = collect(preg_split('/\s+/u', $search))->filter()->values();

        if (in_array($query->getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            // The full-text operators are taken out of the words, so a typed "-" or "*" can't change the search.
            $terms = $words->map(fn (string $word) => preg_replace('/[+\-<>()~*"@]+/u', '', $word))
                ->filter(fn (string $word) => mb_strlen($word) >= 3);

            if ($terms->isNotEmpty()) {
                $query->orWhereFullText($column, $terms->map(fn (string $word) => "+{$word}*")->implode(' '), ['mode' => 'boolean']);
            }

            return;
        }

        $query->orWhere(fn (Builder $query) => $words->each(fn (string $word) => $query->where(fn (Builder $query) => $query
            ->where($column, 'like', static::escape($word).'%')
            ->orWhere($column, 'like', '% '.static::escape($word).'%'))));
    }

    /**
     * A typed "%" or "_" is a wildcard in LIKE; escape it so the search stays a fast prefix match.
     */
    private static function escape(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
