<?php

namespace Modules\Developers\Filament\Resources\DeveloperResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Modules\Developers\Filament\Resources\DeveloperResource;

class CreateDeveloper extends CreateRecord
{
    protected static string $resource = DeveloperResource::class;

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
            ->title(__('admin.developers.notifications.created', ['name' => $this->record->getLocalized('name')]));
    }
}
