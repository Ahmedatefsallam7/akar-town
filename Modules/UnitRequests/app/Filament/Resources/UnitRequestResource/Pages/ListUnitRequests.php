<?php

namespace Modules\UnitRequests\Filament\Resources\UnitRequestResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource;

class ListUnitRequests extends ListRecords
{
    protected static string $resource = UnitRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.actions.create_title', ['name' => static::getResource()::getModelLabel()]))
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return __('admin.unit_requests.title');
    }
}
