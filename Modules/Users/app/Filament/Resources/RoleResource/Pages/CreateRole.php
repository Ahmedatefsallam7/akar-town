<?php

namespace Modules\Users\Filament\Resources\RoleResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Modules\Users\Filament\Resources\RoleResource;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string
    {
        return __('admin.actions.create_title', ['name' => $this->getResource()::getModelLabel()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = RoleResource::formDataToPermissions($data);
        $data['parent_id'] = RoleResource::rolesParentId();
        $data['slug'] = RoleResource::makeSlug($data['name_en']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('admin.roles.notifications.created', ['name' => $this->record->getLocalized('name')]));
    }
}
