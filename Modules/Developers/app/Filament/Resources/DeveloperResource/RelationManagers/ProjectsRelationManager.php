<?php

namespace Modules\Developers\Filament\Resources\DeveloperResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Developers\Filament\Resources\DeveloperResource\Pages\ViewDeveloper;
use Modules\Projects\Filament\Resources\ProjectResource;
use Modules\Projects\Models\Project;

/**
 * The developer's projects (as in Dastan), on its View page. Lists them, opens one, or adds a project for this
 * developer.
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
        return $pageClass === ViewDeveloper::class && ProjectResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')->label(__('admin.projects.logo'))->collection(Project::LOGO)->conversion('thumb')->imageSize(40),
                TextColumn::make('title')->label(__('admin.table.title'))->state(fn (Project $record) => $record->getLocalized('title'))->weight('medium')->color('primary'),
                TextColumn::make('price_from')->label(__('admin.projects.price_from'))->formatStateUsing(fn (Project $record, ?int $state) => ProjectResource::money($state, $record)),
                TextColumn::make('units_count')->label(__('admin.projects.units_count'))->counts('units')->badge()->color('info')->alignment('center'),
                IconColumn::make('is_published')->label(__('admin.projects.published'))->boolean()->alignment('center'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Project $record) => ProjectResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading(__('admin.developers.no_projects'))
            ->headerActions([
                Action::make('addProject')
                    ->label(__('admin.actions.create_title', ['name' => ProjectResource::getModelLabel()]))
                    ->icon('heroicon-o-plus')
                    ->url(fn () => ProjectResource::getUrl('create', ['developer' => $this->getOwnerRecord()->getKey()]))
                    ->visible(fn () => ProjectResource::canCreate()),
            ]);
    }
}
