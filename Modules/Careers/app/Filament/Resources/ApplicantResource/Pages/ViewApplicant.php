<?php

namespace Modules\Careers\Filament\Resources\ApplicantResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Careers\Filament\Resources\ApplicantResource;
use Modules\Careers\Models\Applicant;

class ViewApplicant extends ViewRecord
{
    protected static string $resource = ApplicantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ApplicantResource::downloadResumeAction(),
            EditAction::make(),
            DeleteAction::make()
                ->successNotification(fn (Applicant $record) => Notification::make()
                    ->success()
                    ->title(__('admin.applicants.notifications.deleted', ['name' => $record->name]))),
        ];
    }

    public function getTitle(): string
    {
        return __('admin.actions.view_title', ['name' => $this->getResource()::getModelLabel()]);
    }
}
