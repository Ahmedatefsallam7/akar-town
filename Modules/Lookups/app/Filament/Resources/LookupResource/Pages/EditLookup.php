<?php

namespace Modules\Lookups\Filament\Resources\LookupResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Modules\Lookups\Filament\Resources\LookupResource;

class EditLookup extends EditRecord
{
    protected static string $resource = LookupResource::class;

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
            ->title(__('admin.lookups.notifications.updated', ['name' => $this->record->getLocalized('name')]));
    }
}
