<?php

namespace Modules\Projects\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Projects\Filament\Resources\ProjectResource\Pages\ViewProject;
use Modules\Units\Filament\Resources\UnitResource;
use Modules\Units\Models\Unit;

/**
 * The project's units (properties), on its View page. Units are managed on the Properties pages; this tab lists them,
 * opens one, or adds a new one in this project (no edit/delete here).
 */
class UnitsRelationManager extends RelationManager
{
    protected static string $relationship = 'units';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.units.plural');
    }

    /**
     * Only on the View page, and only for users who can see properties.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $pageClass === ViewProject::class && UnitResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('featured_image')
                    ->label(__('admin.units.image'))
                    ->collection(Unit::FEATURED_IMAGE)
                    ->conversion('thumb')
                    ->imageSize(40)
                    ->square(),
                TextColumn::make('name')
                    ->label(__('admin.units.name'))
                    ->state(fn (Unit $record) => $record->getLocalized('name'))
                    ->weight('medium')
                    ->color('primary'),
                TextColumn::make('price')
                    ->label(__('admin.units.price'))
                    ->formatStateUsing(fn (Unit $record) => UnitResource::money($record)),
                TextColumn::make('area')
                    ->label(__('admin.units.area'))
                    ->numeric()
                    ->suffix(' '.__('admin.projects.square_meters')),
                TextColumn::make('bedrooms')
                    ->label(__('admin.units.bedrooms'))
                    ->alignment('center'),
                IconColumn::make('is_published')
                    ->label(__('admin.units.published'))
                    ->boolean()
                    ->alignment('center'),
            ])
            ->defaultSort('order')
            ->recordUrl(fn (Unit $record) => UnitResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading(__('admin.units.none_for_project'))
            ->headerActions([
                Action::make('addUnit')
                    ->label(__('admin.actions.create_title', ['name' => UnitResource::getModelLabel()]))
                    ->icon('heroicon-o-plus')
                    ->url(fn () => UnitResource::getUrl('create', ['project' => $this->getOwnerRecord()->getKey()]))
                    ->visible(fn () => UnitResource::canCreate()),
            ]);
    }
}
