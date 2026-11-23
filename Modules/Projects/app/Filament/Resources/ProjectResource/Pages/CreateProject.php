<?php

namespace Modules\Projects\Filament\Resources\ProjectResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Url;
use Modules\Projects\Filament\Resources\ProjectResource;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    public function getTitle(): string
    {
        return __('admin.actions.create_title', ['name' => $this->getResource()::getModelLabel()]);
    }

    /**
     * Opened from a developer's Projects tab (?developer=ID): start with that developer chosen.
     */
    #[Url]
    public ?int $developer = null;

    /** The form's "existing galleries", linked after the save; null when the form didn't send them (not allowed). */
    protected ?array $galleryIds = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (array_key_exists('gallery_ids', $data)) {
            $this->galleryIds = (array) $data['gallery_ids'];
        }
        unset($data['gallery_ids']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->galleryIds !== null) {
            ProjectResource::syncGalleries($this->record, $this->galleryIds);
        }
    }

    protected function afterFill(): void
    {
        if ($this->developer) {
            $this->data['developer_id'] = $this->developer;
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
            ->title(__('admin.projects.notifications.created', ['name' => $this->record->getLocalized('title')]));
    }
}
