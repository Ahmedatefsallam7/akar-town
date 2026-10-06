<?php

namespace Modules\Projects\Filament\Resources\ProjectResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Modules\Projects\Filament\Resources\ProjectResource;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

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
            ->title(__('admin.projects.notifications.created', ['name' => $this->record->getLocalized('title')]));
    }
}
