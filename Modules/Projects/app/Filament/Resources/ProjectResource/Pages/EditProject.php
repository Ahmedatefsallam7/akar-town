<?php

namespace Modules\Projects\Filament\Resources\ProjectResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Modules\Projects\Filament\Resources\ProjectResource;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    /** The form's "existing galleries", linked after the save; null when the form didn't send them (not allowed). */
    protected ?array $galleryIds = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('gallery_ids', $data)) {
            $this->galleryIds = (array) $data['gallery_ids'];
        }
        unset($data['gallery_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->galleryIds !== null) {
            ProjectResource::syncGalleries($this->record, $this->galleryIds);
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return __('admin.actions.edit_title', ['name' => $this->getResource()::getModelLabel()]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('admin.projects.notifications.updated', ['name' => $this->record->getLocalized('title')]));
    }
}
