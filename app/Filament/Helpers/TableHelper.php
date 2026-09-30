<?php

namespace App\Filament\Helpers;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;

class TableHelper
{
    public static function indexColumn(): TextColumn
    {
        return TextColumn::make('index')
            ->label(__('admin.table.hash'))
            ->rowIndex()
            ->alignment('center')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function createdAtColumn(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('admin.table.created_at'))
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function standardActions(): ActionGroup
    {
        return ActionGroup::make([
            ViewAction::make()->label(__('admin.actions.view')),
            EditAction::make()->label(__('admin.actions.edit')),
            DeleteAction::make()->label(__('admin.actions.delete')),
            RestoreAction::make()->label(__('admin.actions.restore')),
            ForceDeleteAction::make()->label(__('admin.actions.force_delete')),
        ]);
    }

    public static function viewAction(): ViewAction
    {
        return ViewAction::make()->label(__('admin.actions.view'));
    }

    public static function standardBulkActions(array $additionalActions = []): array
    {
        return [
            BulkActionGroup::make([
                ...$additionalActions,
                DeleteBulkAction::make()->label(__('admin.actions.delete')),
                RestoreBulkAction::make()->label(__('admin.actions.restore')),
                ForceDeleteBulkAction::make()->label(__('admin.actions.force_delete')),
            ]),
        ];
    }
}
