<?php

namespace Modules\Careers\Filament\Resources\ApplicantResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Url;
use Modules\Careers\Filament\Resources\ApplicantResource;

class ListApplicants extends ListRecords
{
    protected static string $resource = ApplicantResource::class;

    /** Filters can come from the link, e.g. the applicants count in the jobs list opens one job's applicants. */
    #[Url]
    public ?array $tableFilters = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.actions.create_title', ['name' => static::getResource()::getModelLabel()]))
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return __('admin.applicants.title');
    }
}
