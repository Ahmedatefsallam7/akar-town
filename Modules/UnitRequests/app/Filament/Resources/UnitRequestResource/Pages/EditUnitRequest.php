<?php

namespace Modules\UnitRequests\Filament\Resources\UnitRequestResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource;

class EditUnitRequest extends EditRecord
{
    protected static string $resource = UnitRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return __('admin.actions.edit_title', ['name' => $this->getResource()::getModelLabel()]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('admin.unit_requests.notifications.updated', ['name' => $this->record->getLocalized('name')]));
    }
}
