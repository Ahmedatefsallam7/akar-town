<?php

namespace Modules\Developers\Filament\Resources\DeveloperResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Developers\Filament\Resources\DeveloperResource;

class ViewDeveloper extends ViewRecord
{
    protected static string $resource = DeveloperResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->successNotification(fn ($record) => Notification::make()
                    ->success()
                    ->title(__('admin.developers.notifications.deleted', ['name' => $record->getLocalized('name')]))),
        ];
    }

    public function getTitle(): string
    {
        return __('admin.actions.view_title', ['name' => $this->getResource()::getModelLabel()]);
    }
}
