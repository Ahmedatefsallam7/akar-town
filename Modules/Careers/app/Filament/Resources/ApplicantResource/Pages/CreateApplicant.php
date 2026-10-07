<?php

namespace Modules\Careers\Filament\Resources\ApplicantResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Modules\Careers\Filament\Resources\ApplicantResource;

class CreateApplicant extends CreateRecord
{
    protected static string $resource = ApplicantResource::class;

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
            ->title(__('admin.applicants.notifications.created', ['name' => $this->record->name]));
    }
}
