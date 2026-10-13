<?php

namespace Modules\Galleries\Filament\Resources\GalleryResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Url;
use Modules\Galleries\Filament\Resources\GalleryResource;

class CreateGallery extends CreateRecord
{
    protected static string $resource = GalleryResource::class;

    public function getTitle(): string
    {
        return __('admin.actions.create_title', ['name' => $this->getResource()::getModelLabel()]);
    }

    /**
     * Opened from a project's Galleries tab (?project=ID): start with that project chosen.
     */
    #[Url]
    public ?int $project = null;

    protected function afterFill(): void
    {
        if ($this->project) {
            $this->data['project_id'] = $this->project;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('admin.galleries.notifications.created', ['name' => $this->record->getLocalized('name')]));
    }
}
