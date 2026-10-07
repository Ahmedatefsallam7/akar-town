<?php

namespace Modules\Inquiry\Filament\Resources\InquiryResource\Pages;

use App\Jobs\SendLeadToCrm;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Inquiry\Filament\Resources\InquiryResource;
use Modules\Inquiry\Models\Inquiry;
use Modules\WebsiteUsers\Filament\Resources\WebsiteUserResource;

class ViewInquiry extends ViewRecord
{
    protected static string $resource = InquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            WebsiteUserResource::leadLinkAction(),
            // Sending happens by itself; this is for a message the CRM didn't take (e.g. the CRM was down for hours).
            Action::make('sendToCrm')
                ->label(__('admin.contact_us.send_to_crm'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn (Inquiry $record) => ! $record->isSentToCrm() && InquiryResource::canEdit($record))
                ->action(function (Inquiry $record) {
                    SendLeadToCrm::dispatch($record);

                    Notification::make()->success()->title(__('admin.contact_us.notifications.queued_for_crm'))->send();
                }),
            EditAction::make(),
            DeleteAction::make()
                ->successNotification(fn (Inquiry $record) => Notification::make()
                    ->success()
                    ->title(__('admin.contact_us.notifications.deleted', ['name' => $record->full_name]))),
        ];
    }

    public function getTitle(): string
    {
        return __('admin.actions.view_title', ['name' => $this->getResource()::getModelLabel()]);
    }
}
