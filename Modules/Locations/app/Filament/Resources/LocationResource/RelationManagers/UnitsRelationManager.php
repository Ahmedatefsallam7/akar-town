<?php

namespace Modules\Locations\Filament\Resources\LocationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Locations\Filament\Resources\LocationResource\Pages\ViewLocation;
use Modules\Units\Filament\Resources\UnitResource;
use Modules\Units\Models\Unit;

/**
 * Properties in this location (as in Dastan), on its View page. Read-only: a row opens the property.
 */
class UnitsRelationManager extends RelationManager
{
    protected static string $relationship = 'units';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.units.plural');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $pageClass === ViewLocation::class && UnitResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('project:id,currency_id,title_en,title_ar'))
            ->columns([
                SpatieMediaLibraryImageColumn::make('featured_image')->label(__('admin.units.image'))->collection(Unit::FEATURED_IMAGE)->conversion('thumb')->imageSize(40)->square(),
                TextColumn::make('name')->label(__('admin.units.name'))->state(fn (Unit $record) => $record->getLocalized('name'))->weight('medium')->color('primary'),
                TextColumn::make('project.'.getLocalizedFieldName('title'))->label(__('admin.units.project'))->placeholder(__('admin.units.standalone')),
                TextColumn::make('price')->label(__('admin.units.price'))->formatStateUsing(fn (Unit $record) => UnitResource::money($record)),
                IconColumn::make('is_published')->label(__('admin.units.published'))->boolean()->alignment('center'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Unit $record) => UnitResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading(__('admin.locations.no_units'));
    }
}
