<?php

namespace Modules\Inquiry\Filament\Resources\MeetingResource\Pages;

use App\Filament\Helpers\CrmHelper;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Inquiry\Filament\Resources\MeetingResource;
use Modules\Inquiry\Models\Meeting;
use Modules\WebsiteUsers\Filament\Resources\WebsiteUserResource;

class ViewMeeting extends ViewRecord
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            WebsiteUserResource::leadLinkAction(),
            CrmHelper::sendAction(MeetingResource::class),
            EditAction::make(),
            DeleteAction::make()
                ->successNotification(fn (Meeting $record) => Notification::make()
                    ->success()
                    ->title(__('admin.meetings.notifications.deleted', ['name' => $record->name]))),
        ];
    }

    public function getTitle(): string
    {
        return __('admin.actions.view_title', ['name' => $this->getResource()::getModelLabel()]);
    }
}
