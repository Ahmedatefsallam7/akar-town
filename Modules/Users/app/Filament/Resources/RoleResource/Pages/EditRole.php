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
        // Renaming a role renames its slug too. Only the Admin role keeps its slug.
        if (! RoleResource::isSlugLocked($this->record)) {
            $data['slug'] = RoleResource::makeSlug($data['name_en']);
        }

        return RoleResource::formDataToPermissions($data, $this->record->getAttribute('attributes') ?? [], $this->record);
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
