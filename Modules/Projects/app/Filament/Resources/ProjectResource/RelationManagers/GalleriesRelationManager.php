<?php

namespace Modules\Projects\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Galleries\Filament\Resources\GalleryResource;
use Modules\Galleries\Models\Gallery;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\ViewProject;

/**
 * The project's galleries, on its View page. Galleries are managed on the Galleries pages; this tab lists them,
 * opens one, or adds a new one for this project (no edit/delete here).
 */
class GalleriesRelationManager extends RelationManager
{
    protected static string $relationship = 'galleries';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.galleries.plural');
    }

    /**
     * Only on the View page, and only for users who can see galleries.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $pageClass === ViewProject::class && GalleryResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('featured_image')
                    ->label(__('admin.galleries.featured_image'))
                    ->collection(Gallery::FEATURED_IMAGE)
                    ->conversion('thumb')
                    ->imageSize(40)
                    ->square(),
                TextColumn::make('name')
                    ->label(__('admin.galleries.name'))
                    ->state(fn (Gallery $record) => $record->getLocalized('name'))
                    ->weight('medium')
                    ->color('primary'),
                TextColumn::make('created_at')
                    ->label(__('admin.table.created_at'))
                    ->date(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Gallery $record) => GalleryResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading(__('admin.galleries.none_for_project'))
            ->headerActions([
                Action::make('addGallery')
                    ->label(__('admin.actions.create_title', ['name' => GalleryResource::getModelLabel()]))
                    ->icon('heroicon-o-plus')
                    ->url(fn () => GalleryResource::getUrl('create', ['project' => $this->getOwnerRecord()->getKey()]))
                    ->visible(fn () => GalleryResource::canCreate()),
            ]);
    }
}
