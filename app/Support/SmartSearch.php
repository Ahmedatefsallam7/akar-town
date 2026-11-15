<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Modules\Lookups\Support\SearchText;

/**
 * Name search on the "search_name" column (AKAR-48, see App\Models\Concerns\HasSearchName): any part of the English
 * or Arabic name, any case, Arabic letters written either way; the best matches first (a name starting with the
 * text, then a word starting with it, then the rest). Used by the website search, its listings and the admin lists.
 */
class SmartSearch
{
    /**
     * Records whose name holds the text.
     */
    public static function match(Builder $query, string $text, string $column = 'search_name'): Builder
    {
        $text = SearchText::normalize($text);

        return $text === ''
            ? $query
            : $query->whereRaw("{$query->qualifyColumn($column)} like ? escape '!'", ['%'.static::escape($text).'%']);
    }

    /**
     * Best matches first: the name starts with the text, then one of its words does, then the rest.
     */
    public static function rank(Builder $query, string $text, string $column = 'search_name'): Builder
    {
        $text = static::escape(SearchText::normalize($text));
        if ($text === '') {
            return $query;
        }
        $column = $query->qualifyColumn($column);

        return $query->orderByRaw(
            "case when {$column} like ? escape '!' or {$column} like ? escape '!' then 0 when {$column} like ? escape '!' then 1 else 2 end",
            [$text.'%', "%\n".$text.'%', '% '.$text.'%'],
        );
    }

    /**
     * A typed "%", "_" or "!" is just a letter in LIKE.
     */
    private static function escape(string $text): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $text);
    }
}
