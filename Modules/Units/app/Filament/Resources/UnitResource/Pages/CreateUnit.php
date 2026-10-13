<?php

namespace Modules\Units\Filament\Resources\UnitResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Url;
use Modules\Lookups\Support\LocationTree;
use Modules\Projects\Models\Project;
use Modules\Units\Filament\Resources\UnitResource;

class CreateUnit extends CreateRecord
{
    protected static string $resource = UnitResource::class;

    public function getTitle(): string
    {
        return __('admin.actions.create_title', ['name' => $this->getResource()::getModelLabel()]);
    }

    /**
     * Opened from a project's Properties tab (?project=ID): start with that project chosen.
     */
    #[Url]
    public ?int $project = null;

    protected function afterFill(): void
    {
        $project = $this->project ? Project::find($this->project) : null;

        if ($project) {
            $this->data['project_id'] = $project->id;

            // Start with the project's location, as when picking it in the form.
            foreach (LocationTree::LEVELS as $column) {
                $this->data[$column] = $project->{$column};
            }
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
            ->title(__('admin.units.notifications.created', ['name' => $this->record->getLocalized('name')]));
    }
}
