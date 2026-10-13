<?php

namespace App\Filament\Schemas;

use Filament\Schemas\Components\Tabs\Tab as BaseTab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

/**
 * A form tab that ends with Previous / Next buttons (as in Dastan), so after filling a long tab the admin moves on
 * without scrolling back up to the tab bar. Use it instead of Filament's Tab in every tabbed form.
 */
class Tab extends BaseTab
{
    public function getDefaultChildComponents(): array|Schema
    {
        $components = parent::getDefaultChildComponents();

        if (! is_array($components)) {
            return $components;
        }

        return [
            ...$components,
            View::make('filament.components.tabs-navigation')->columnSpanFull(),
        ];
    }
}
