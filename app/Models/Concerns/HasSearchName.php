<?php

namespace App\Models\Concerns;

use Modules\Lookups\Support\SearchText;

/**
 * The record's names kept in "search_name" as searches compare them (AKAR-48, see SearchText): English and Arabic,
 * one per line, lower case, Arabic letter variants made one. Searches then match any part of a name in one column
 * (App\Support\SmartSearch). Filled on every save; rows written straight to the table (seeders) fill it
 * with searchNameFor().
 */
trait HasSearchName
{
    /**
     * The columns the search name is made from.
     *
     * @return list<string>
     */
    public static function searchNameSources(): array
    {
        return ['name_en', 'name_ar'];
    }

    /**
     * @param  array<?string>  $names
     */
    public static function searchNameFor(array $names): string
    {
        return implode("\n", array_filter(array_map(SearchText::normalize(...), $names), fn (string $name) => $name !== ''));
    }

    public static function bootHasSearchName(): void
    {
        static::saving(function (self $record) {
            $record->search_name = static::searchNameFor(array_map(fn (string $column) => $record->getAttribute($column), static::searchNameSources()));
        });
    }
}
