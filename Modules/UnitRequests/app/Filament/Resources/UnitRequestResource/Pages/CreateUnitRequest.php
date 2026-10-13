<?php

namespace Modules\UnitRequests\Filament\Resources\UnitRequestResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource;

class CreateUnitRequest extends CreateRecord
{
    protected static string $resource = UnitRequestResource::class;

    public function getTitle(): string
    {
        return __('admin.actions.create_title', ['name' => $this->getResource()::getModelLabel()]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('admin.unit_requests.notifications.created', ['name' => $this->record->getLocalized('name')]));
    }
}
