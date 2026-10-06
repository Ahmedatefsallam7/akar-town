<?php

namespace Modules\Lookups\Filament\Forms;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Filters\SelectFilter;
use Modules\Lookups\Support\LocationTree;

/**
 * The location chain for any module (Locations, Projects, Units…): five cascading selects stored in
 * country_id … sub_area_id. Each lists the values under the one above and clears the ones below when it changes.
 * Filament only accepts a value from the select's options, so a city that isn't in the chosen country is rejected.
 */
class LocationFields
{
    /**
     * @param  int  $required  how many levels from the top must be chosen (e.g. 3 = country, city and region)
     * @return array<Select>
     */
    public static function make(int $required = 0): array
    {
        return collect(LocationTree::LEVELS)->map(function (string $column, int $index) use ($required) {
            $parent = LocationTree::LEVELS[$index - 1] ?? null;
            $below = array_slice(LocationTree::LEVELS, $index + 1);

            return Select::make($column)
                ->label(static::label($column))
                ->placeholder(__('admin.location_chain.select_'.static::level($column)))
                ->options(fn (Get $get) => $parent === null || filled($get($parent))
                    ? LocationTree::options($parent ? (int) $get($parent) : null, (int) $get($column) ?: null)
                    : [])
                ->searchable()
                ->required($index < $required)
                ->live()
                ->afterStateUpdated(fn (Set $set) => collect($below)->each(fn (string $column) => $set($column, null)));
        })->all();
    }

    /**
     * One filter per level.
     *
     * @return array<SelectFilter>
     */
    public static function filters(): array
    {
        return collect(LocationTree::LEVELS)->map(fn (string $column, int $index) => SelectFilter::make($column)
            ->label(static::label($column))
            ->options(fn () => LocationTree::levelOptions($index + 1))
            ->searchable()
            ->indicator(static::label($column)))
            ->all();
    }

    private static function label(string $column): string
    {
        return __('admin.location_chain.'.static::level($column));
    }

    /**
     * "sub_area_id" => "sub_area".
     */
    private static function level(string $column): string
    {
        return substr($column, 0, -3);
    }
}
