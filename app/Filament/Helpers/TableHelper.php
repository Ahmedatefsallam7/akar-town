<?php

namespace App\Filament\Helpers;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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

    /**
     * Row actions: View and Edit as direct icon buttons (the everyday ones), the rest in a "more" menu so a
     * delete is never one misclick away. Each action still hides itself when the user isn't allowed to use it.
     *
     * @param  Closure(Model): string  $deletedTitle  notification title after a delete
     * @return array<Action|ActionGroup>
     */
    public static function recordActions(Closure $deletedTitle): array
    {
        return [
            ViewAction::make()->label(__('admin.actions.view'))->iconButton()->tooltip(__('admin.actions.view')),
            EditAction::make()->label(__('admin.actions.edit'))->iconButton()->tooltip(__('admin.actions.edit')),
            ActionGroup::make([
                DeleteAction::make()
                    ->label(__('admin.actions.delete'))
                    ->successNotification(fn (Model $record) => Notification::make()->success()->title($deletedTitle($record))),
                RestoreAction::make()->label(__('admin.actions.restore')),
                ForceDeleteAction::make()->label(__('admin.actions.force_delete')),
            ])->tooltip(__('admin.actions.more')),
        ];
    }

    /**
     * Friendly empty table: a clear message and an "Add" button (shown only to users who may create).
     *
     * @param  class-string<resource>  $resource
     */
    public static function emptyState(Table $table, string $resource, string $icon): Table
    {
        return $table
            ->emptyStateIcon($icon)
            ->emptyStateHeading(__('admin.table.empty_heading', ['name' => $resource::getPluralModelLabel()]))
            ->emptyStateDescription(__('admin.table.empty_description'))
            ->emptyStateActions([
                CreateAction::make()
                    ->label(__('admin.actions.create_title', ['name' => $resource::getModelLabel()]))
                    ->icon('heroicon-o-plus')
                    ->url($resource::getUrl('create')),
            ]);
    }

    public static function standardBulkActions(array $additionalActions = []): array
    {
        return [
            BulkActionGroup::make([
                ...$additionalActions,
                // Check each selected record too, so per-record rules (e.g. protected rows) also apply in bulk.
                DeleteBulkAction::make()->label(__('admin.actions.delete'))->authorizeIndividualRecords(),
                RestoreBulkAction::make()->label(__('admin.actions.restore'))->authorizeIndividualRecords(),
                ForceDeleteBulkAction::make()->label(__('admin.actions.force_delete'))->authorizeIndividualRecords(),
            ]),
        ];
    }

    /**
     * Filter on a date column between two optional dates, with removable indicators.
     */
    public static function dateRangeFilter(string $column, string $from, string $until): Filter
    {
        return Filter::make($column)
            ->schema([
                DatePicker::make($from)
                    ->label(__("admin.filter.{$from}"))
                    ->prefixIcon('heroicon-m-calendar-days'),
                DatePicker::make($until)
                    ->label(__("admin.filter.{$until}"))
                    ->prefixIcon('heroicon-m-calendar-days'),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data[$from] ?? null, fn (Builder $query, $date): Builder => $query->whereDate($column, '>=', $date))
                ->when($data[$until] ?? null, fn (Builder $query, $date): Builder => $query->whereDate($column, '<=', $date)))
            ->indicateUsing(function (array $data) use ($from, $until): array {
                $indicators = [];

                foreach ([$from, $until] as $field) {
                    if ($data[$field] ?? null) {
                        $indicators[] = Indicator::make(__("admin.filter.{$field}").': '.$data[$field])->removeField($field);
                    }
                }

                return $indicators;
            });
    }

    /**
     * Bulk action that sets a yes/no column (is_active by default, or e.g. is_featured) on the selected records.
     * Needs edit + bulk; records you can't edit are skipped.
     *
     * @param  class-string<resource>  $resource
     */
    public static function statusBulkAction(string $resource, string $name, string $icon, bool $value, string $column = 'is_active'): BulkAction
    {
        return BulkAction::make($name)
            ->label(__("admin.actions.{$name}"))
            ->icon($icon)
            ->requiresConfirmation()
            ->authorize(fn (): bool => $resource::can('updateAny'))
            ->action(fn (Collection $records) => $records
                ->filter(fn (Model $record) => $resource::canEdit($record))
                ->each->update([$column => $value]))
            ->successNotificationTitle(__('admin.notifications.updated'))
            ->deselectRecordsAfterCompletion();
    }
}
