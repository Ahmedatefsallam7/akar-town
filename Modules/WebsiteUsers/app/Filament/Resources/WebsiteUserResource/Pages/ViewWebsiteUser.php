<?php

namespace Modules\WebsiteUsers\Filament\Resources\WebsiteUserResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\WebsiteUsers\Filament\Resources\WebsiteUserResource;
use Modules\WebsiteUsers\Models\WebsiteUser;

class ViewWebsiteUser extends ViewRecord
{
    protected static string $resource = WebsiteUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            WebsiteUserResource::blockAction(),
            WebsiteUserResource::unblockAction(),
            EditAction::make(),
            DeleteAction::make()
                ->successNotification(fn (WebsiteUser $record) => Notification::make()
                    ->success()
                    ->title(__('admin.website_users.notifications.deleted', ['name' => $record->name]))),
        ];
    }

    public function getTitle(): string
    {
        return __('admin.actions.view_title', ['name' => $this->getResource()::getModelLabel()]);
    }
}
