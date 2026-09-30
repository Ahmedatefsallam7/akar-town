<?php

namespace Modules\Lookups\Filament\Resources\LookupResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Modules\Lookups\Filament\Resources\LookupResource;

class CreateLookup extends CreateRecord
{
    protected static string $resource = LookupResource::class;

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
            ->title(__('admin.lookups.notifications.created', ['name' => $this->record->getLocalized('name')]));
    }
}
