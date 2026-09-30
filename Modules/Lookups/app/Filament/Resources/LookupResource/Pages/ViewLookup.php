<?php

namespace Modules\Lookups\Filament\Resources\LookupResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Lookups\Filament\Resources\LookupResource;

class ViewLookup extends ViewRecord
{
    protected static string $resource = LookupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()->hidden(fn () => ! $this->record->is_deletable),
        ];
    }

    public function getTitle(): string
    {
        return __('admin.actions.view_title', ['name' => $this->getResource()::getModelLabel()]);
    }
}
