<?php

namespace Modules\Inquiry\Filament\Resources\InquiryResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Modules\Inquiry\Filament\Resources\InquiryResource;

class CreateInquiry extends CreateRecord
{
    protected static string $resource = InquiryResource::class;

    public function getTitle(): string
    {
        return __('admin.actions.create_title', ['name' => $this->getResource()::getModelLabel()]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('admin.contact_us.notifications.created', ['name' => $this->record->full_name]));
    }
}
