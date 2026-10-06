<?php

namespace Modules\Galleries\Filament\Resources\GalleryResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Galleries\Filament\Resources\GalleryResource;

class ListGalleries extends ListRecords
{
    protected static string $resource = GalleryResource::class;

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
        return __('admin.galleries.title');
    }
}
