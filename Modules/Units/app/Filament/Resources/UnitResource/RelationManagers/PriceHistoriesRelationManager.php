<?php

namespace Modules\Units\Filament\Resources\UnitResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Units\Filament\Resources\UnitResource;
use Modules\Units\Filament\Resources\UnitResource\Pages\ViewUnit;
use Modules\Units\Models\UnitPriceHistory;

/**
 * Every price the unit had (newest first) and who set it, on its View page. Written automatically; read-only here.
 */
class PriceHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'priceHistories';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.units.price_history');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $pageClass === ViewUnit::class;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('changedBy:id,first_name,last_name'))
            ->columns([
                TextColumn::make('price')
                    ->label(__('admin.units.price'))
                    ->formatStateUsing(fn (UnitPriceHistory $record) => UnitResource::money($this->getOwnerRecord(), $record->price)),
                TextColumn::make('changedBy.first_name')
                    ->label(__('admin.units.changed_by'))
                    ->formatStateUsing(fn (UnitPriceHistory $record) => trim($record->changedBy?->first_name.' '.$record->changedBy?->last_name) ?: null)
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label(__('admin.units.changed_at'))
                    ->dateTime(),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50]);
    }
}
