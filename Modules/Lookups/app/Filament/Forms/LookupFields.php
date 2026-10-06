<?php

namespace Modules\Lookups\Filament\Forms;

use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Modules\Lookups\Support\LookupOptions;

/**
 * Form fields whose choices come from a lookup type (Lookups is the one place for dropdown and checkbox values).
 * Only a value from the field's options is accepted, so a value of another type is rejected.
 */
class LookupFields
{
    /**
     * A single value stored in a column, e.g. currency_id from "currencies".
     *
     * @param  (Closure(Get): ?array<int>)|null  $only  limits the choices to these ids (null = all), e.g. what the unit's
     *                                                  project picked
     */
    public static function select(string $column, string $type, ?Closure $only = null): Select
    {
        LookupOptions::need($type);

        return Select::make($column)
            ->options(fn (?Model $record, Get $get) => static::singleOptions($type, $record?->getAttribute($column), $only ? $only($get) : null))
            ->searchable();
    }

    /**
     * Like select(), as radio buttons (short lists such as finishing type).
     *
     * @param  (Closure(Get): ?array<int>)|null  $only
     */
    public static function radio(string $column, string $type, ?Closure $only = null): Radio
    {
        LookupOptions::need($type);

        return Radio::make($column)
            ->options(fn (?Model $record, Get $get) => static::singleOptions($type, $record?->getAttribute($column), $only ? $only($get) : null))
            ->inline();
    }

    /**
     * @param  array<int>|null  $only
     * @return array<int, string>
     */
    private static function singleOptions(string $type, mixed $saved, ?array $only): array
    {
        $saved = filled($saved) ? (int) $saved : null;
        $options = LookupOptions::for($type, array_filter([$saved]));

        return $only === null ? $options : array_intersect_key($options, array_flip(array_filter([...$only, $saved])));
    }

    /**
     * Several values saved in "lookupables" (the model uses HasLookups), e.g. amenities. All lists on a form share
     * one query for the saved values and one for the choices.
     */
    public static function checkboxList(string $name, string $type): CheckboxList
    {
        LookupOptions::need($type);

        return CheckboxList::make($name)
            ->options(fn (?Model $record) => LookupOptions::for($type, $record?->lookupIds($type) ?? []))
            ->columns(['default' => 1, 'sm' => 2, 'lg' => 3])
            ->bulkToggleable()
            ->afterStateHydrated(function (CheckboxList $component, ?Model $record) use ($type) {
                if ($record) {
                    $component->state($record->lookupIds($type));
                }
            })
            // Filament doesn't check checkbox values against the options, so do it: only values of this type.
            ->rule(fn (?Model $record) => function (string $attribute, mixed $value, Closure $fail) use ($type, $record) {
                $allowed = array_keys(LookupOptions::for($type, $record?->lookupIds($type) ?? []));

                if (array_diff(array_map('intval', (array) $value), $allowed) !== []) {
                    $fail(__('validation.in', ['attribute' => __('admin.table.value')]));
                }
            })
            ->saveRelationshipsUsing(fn (Model $record, ?array $state) => $record->lookupsOfType($type)->sync($state ?? []))
            ->dehydrated(false);
    }
}
