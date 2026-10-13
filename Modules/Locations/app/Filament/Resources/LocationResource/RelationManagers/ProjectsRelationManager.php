<?php

namespace Modules\Locations\Filament\Resources\LocationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Locations\Filament\Resources\LocationResource\Pages\ViewLocation;
use Modules\Projects\Filament\Resources\ProjectResource;
use Modules\Projects\Models\Project;

/**
 * Projects in this location (as in Dastan), on its View page. Read-only: a row opens the project.
 */
class ProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'projects';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.projects.plural');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $pageClass === ViewLocation::class && ProjectResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')->label(__('admin.projects.logo'))->collection(Project::LOGO)->conversion('thumb')->imageSize(40),
                TextColumn::make('title')->label(__('admin.table.title'))->state(fn (Project $record) => $record->getLocalized('title'))->weight('medium')->color('primary'),
                TextColumn::make('developer.'.getLocalizedFieldName('name'))->label(__('admin.projects.developer')),
                TextColumn::make('price_from')->label(__('admin.projects.price_from'))->formatStateUsing(fn (Project $record, ?int $state) => ProjectResource::money($state, $record)),
                IconColumn::make('is_published')->label(__('admin.projects.published'))->boolean()->alignment('center'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Project $record) => ProjectResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading(__('admin.locations.no_projects'));
    }
}
