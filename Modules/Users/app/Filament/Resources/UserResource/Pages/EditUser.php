<?php

namespace Modules\Users\Filament\Resources\UserResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Modules\Users\Filament\Resources\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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
        return UserResource::permissionsToFormData($this->record, $data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Your own permissions are hidden and never saved from here.
        if ($this->record->is(auth()->user())) {
            return collect($data)->reject(fn ($value, string $key) => in_array($key, ['use_custom_permissions', 'permission_matrix'], true))->all();
        }

        return UserResource::formDataToPermissions($data, $this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('admin.users.notifications.updated', ['name' => $this->record->name ?: $this->record->username]));
    }
}
