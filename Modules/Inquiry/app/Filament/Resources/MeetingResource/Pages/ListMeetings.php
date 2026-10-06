<?php

namespace Modules\Inquiry\Filament\Resources\MeetingResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inquiry\Filament\Resources\MeetingResource;

class ListMeetings extends ListRecords
{
    protected static string $resource = MeetingResource::class;

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
        return __('admin.meetings.title');
    }
}
