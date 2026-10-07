<?php

namespace Modules\WebsiteUsers\Filament\Resources\WebsiteUserResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Modules\WebsiteUsers\Filament\Resources\WebsiteUserResource;

class EditWebsiteUser extends EditRecord
{
    protected static string $resource = WebsiteUserResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return __('admin.actions.edit_title', ['name' => $this->getResource()::getModelLabel()]);
    }

    /**
     * Blocking from the form also ends the user's "remember me" logins, like the Block button.
     */
    protected function afterSave(): void
    {
        if ($this->record->wasChanged('status') && $this->record->isBlocked()) {
            $this->record->block();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('admin.website_users.notifications.updated', ['name' => $this->record->name]));
    }
}
