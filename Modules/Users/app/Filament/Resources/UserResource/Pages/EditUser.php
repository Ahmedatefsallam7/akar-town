<?php

namespace Modules\Users\Filament\Resources\UserResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
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

        $data = UserResource::formDataToPermissions($data, $this->record);

        // What the user will really have after saving (their role again when custom permissions are turned off): never
        // more than the editor has, or turning the switch off would hand over a role's full access.
        $after = (clone $this->record)->forceFill(['role_id' => $data['role_id'] ?? $this->record->role_id, 'custom_permissions' => $data['custom_permissions']]);
        $after->unsetRelation('role');

        if (! auth()->user()->covers($after->effectivePermissions())) {
            throw ValidationException::withMessages(['data.use_custom_permissions' => __('admin.users.cannot_grant_more')]);
        }

        return $data;
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
