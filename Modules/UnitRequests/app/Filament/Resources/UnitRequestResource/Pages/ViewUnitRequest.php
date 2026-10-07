<?php

namespace Modules\UnitRequests\Filament\Resources\UnitRequestResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource;
use Modules\UnitRequests\Models\UnitRequest;
use Modules\Units\Filament\Resources\UnitResource;
use Modules\WebsiteUsers\Filament\Resources\WebsiteUserResource;

class ViewUnitRequest extends ViewRecord
{
    protected static string $resource = UnitRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            WebsiteUserResource::leadLinkAction(),
            UnitRequestResource::convertAction(),
            Action::make('viewUnit')
                ->label(__('admin.unit_requests.view_unit'))
                ->icon('heroicon-o-home-modern')
                ->color('gray')
                ->visible(fn (UnitRequest $record) => $record->unit !== null && UnitResource::canView($record->unit))
                ->url(fn (UnitRequest $record) => UnitResource::getUrl('view', ['record' => $record->unit])),
            EditAction::make(),
            DeleteAction::make()
                ->successNotification(fn (UnitRequest $record) => Notification::make()
                    ->success()
                    ->title(__('admin.unit_requests.notifications.deleted', ['name' => $record->getLocalized('name')]))),
        ];
    }

    public function getTitle(): string
    {
        return __('admin.actions.view_title', ['name' => $this->getResource()::getModelLabel()]);
    }
}
