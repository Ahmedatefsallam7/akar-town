<?php

namespace Modules\Users\Filament\Resources\RoleResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Modules\Users\Filament\Resources\RoleResource;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return __('admin.actions.edit_title', ['name' => $this->getResource()::getModelLabel()]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return RoleResource::permissionsToFormData($this->record, $data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return RoleResource::formDataToPermissions($data, $this->record->getAttribute('attributes') ?? []);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('admin.roles.notifications.updated', ['name' => $this->record->getLocalized('name')]));
    }
}
