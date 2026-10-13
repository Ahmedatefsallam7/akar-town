<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\ViewField;

/**
 * "Set location on map" (team lead, 2026-10-07): drop or drag a pin to fill the form's latitude / longitude fields
 * (same form level), and typing them moves the pin. Add it next to those two fields.
 */
class MapPicker
{
    public static function make(): ViewField
    {
        return ViewField::make('map_picker')
            ->label(__('admin.map.set_location'))
            ->helperText(__('admin.map.help'))
            ->view('filament.forms.map-picker')
            ->dehydrated(false)
            ->columnSpanFull();
    }
}
