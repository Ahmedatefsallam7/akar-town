<?php

namespace Modules\Users\Filament\Resources\RoleResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Users\Filament\Resources\RoleResource;

class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->successNotification(fn ($record) => Notification::make()
                    ->success()
                    ->title(__('admin.roles.notifications.deleted', ['name' => $record->getLocalized('name')]))),
        ];
    }

    public function getTitle(): string
    {
        return __('admin.actions.view_title', ['name' => $this->getResource()::getModelLabel()]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return RoleResource::permissionsToFormData($this->record, $data);
    }
}
