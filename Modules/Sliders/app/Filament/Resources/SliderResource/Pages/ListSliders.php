<?php

namespace Modules\Sliders\Filament\Resources\SliderResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Sliders\Filament\Resources\SliderResource;

class ListSliders extends ListRecords
{
    protected static string $resource = SliderResource::class;

    public function getTitle(): string
    {
        return __('admin.sliders.title');
    }

    public function getSubheading(): ?string
    {
        return __('admin.sliders.subheading');
    }
}
