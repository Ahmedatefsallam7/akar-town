<?php

namespace Modules\Inquiry\Filament\Resources\InquiryResource\Pages;

use App\Filament\Helpers\CrmHelper;
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
            CrmHelper::sendAction(InquiryResource::class),
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
