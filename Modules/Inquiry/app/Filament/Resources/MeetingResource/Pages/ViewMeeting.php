<?php

namespace Modules\Inquiry\Filament\Resources\MeetingResource\Pages;

use App\Jobs\SendLeadToCrm;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Inquiry\Filament\Resources\MeetingResource;
use Modules\Inquiry\Models\Meeting;

class ViewMeeting extends ViewRecord
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sending happens by itself; this is for a message the CRM didn't take (e.g. the CRM was down for hours).
            Action::make('sendToCrm')
                ->label(__('admin.contact_us.send_to_crm'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn (Meeting $record) => ! $record->isSentToCrm() && MeetingResource::canEdit($record))
                ->action(function (Meeting $record) {
                    SendLeadToCrm::dispatch($record);

                    Notification::make()->success()->title(__('admin.contact_us.notifications.queued_for_crm'))->send();
                }),
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
