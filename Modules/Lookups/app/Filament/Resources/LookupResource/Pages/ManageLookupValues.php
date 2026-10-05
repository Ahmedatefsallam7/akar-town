<?php

namespace Modules\Lookups\Filament\Resources\LookupResource\Pages;

use App\Filament\Helpers\TableHelper;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Modules\Lookups\Filament\Resources\LookupResource;
use Modules\Lookups\Models\Lookup;

/**
 * The values of one lookup type (or one level of the Locations tree): add and edit in a modal, re-order by drag and
 * drop. In a tree, a value with a level below it opens that level. Every action goes through LookupResource's
 * permissions (view / create / edit / delete / bulk; re-order = edit).
 */
class ManageLookupValues extends ManageRelatedRecords
{
    protected static string $resource = LookupResource::class;

    protected static ?string $relatedResource = LookupResource::class;

    protected static string $relationship = 'children';

    /** Per-request memo of the record's ancestors (for the breadcrumbs, the title and the tree depth). */
    private ?Collection $ancestors = null;

    public function getTitle(): string|Htmlable
    {
        return $this->getOwnerRecord()->getLocalized('name');
    }

    public function getBreadcrumbs(): array
    {
        $crumbs = [LookupResource::getUrl('index') => __('admin.lookups.title')];

        foreach ($this->ancestors() as $ancestor) {
            $crumbs[LookupResource::getUrl('values', ['record' => $ancestor])] = $ancestor->getLocalized('name');
        }

        $crumbs[] = $this->getOwnerRecord()->getLocalized('name');

        return $crumbs;
    }

    public function form(Schema $schema): Schema
    {
        return LookupResource::form($schema);
    }

    public function table(Table $table): Table
    {
        $opensLevel = $this->valuesHaveALevelBelow();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([SoftDeletingScope::class])
                ->withProtectedDescendantsFlag()
                ->withUsageFlag()
                ->when($opensLevel, fn (Builder $query) => $query->withCount('children')))
            ->reorderable('order')
            ->defaultSort('order')
            ->paginated(false)
            ->emptyStateHeading(__('admin.table.empty_heading', ['name' => $this->getOwnerRecord()->getLocalized('name')]))
            ->emptyStateDescription(__('admin.table.empty_description'))
            ->emptyStateIcon('heroicon-o-list-bullet')
            ->columns([
                TextColumn::make('name_en')
                    ->label(__('admin.lookups.name_en'))
                    ->searchable()
                    ->weight('medium')
                    ->url(fn (Lookup $record) => $opensLevel ? LookupResource::getUrl('values', ['record' => $record]) : null)
                    ->color(fn () => $opensLevel ? 'primary' : null),
                TextColumn::make('name_ar')
                    ->label(__('admin.lookups.name_ar'))
                    ->searchable(),
                TextColumn::make('children_count')
                    ->label(__('admin.lookups.children_count'))
                    ->badge()
                    ->alignment('center')
                    ->visible($opensLevel),
                ToggleColumn::make('is_active')
                    ->label(__('admin.lookups.active'))
                    ->disabled(fn (Lookup $record) => ! LookupResource::canEdit($record))
                    ->alignment('center'),
            ])
            ->filters([TrashedFilter::make()])
            ->headerActions([
                CreateAction::make()
                    ->label(__('admin.lookups.add_value'))
                    ->modalHeading(__('admin.lookups.add_value'))
                    ->icon('heroicon-o-plus'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('admin.actions.edit'))
                    ->modalHeading(__('admin.lookups.edit_value'))
                    ->iconButton()
                    ->tooltip(__('admin.actions.edit')),
                DeleteAction::make()
                    ->label(__('admin.actions.delete'))
                    ->iconButton()
                    ->tooltip(__('admin.actions.delete'))
                    // A value with items inside (e.g. a city) takes them with it: say how many.
                    ->modalDescription(fn (Lookup $record) => $this->deleteWarning($record))
                    // Protected or in use: shown disabled with the reason instead of hidden.
                    ->authorizationTooltip(),
            ])
            ->toolbarActions(TableHelper::standardBulkActions());
    }

    /**
     * In a tree type (Locations), values above the last level open the level below them.
     */
    public function valuesHaveALevelBelow(): bool
    {
        $type = $this->ancestors()->first() ?? $this->getOwnerRecord();
        $levelOfValues = $this->ancestors()->count() + 1;

        return $levelOfValues < LookupResource::depthOf($type);
    }

    private function deleteWarning(Lookup $record): string
    {
        $inside = $record->descendants()->count();

        return $inside > 0
            ? trans_choice('admin.lookups.delete_with_items', $inside, ['count' => $inside])
            : __('filament-actions::modal.confirmation');
    }

    /**
     * The record's ancestors, top first (the lookup type comes first). One query, once per request.
     *
     * @return Collection<int, Lookup>
     */
    private function ancestors(): Collection
    {
        return $this->ancestors ??= $this->getOwnerRecord()->ancestors()->defaultOrder()->get(['id', 'slug', 'name_en', 'name_ar', '_lft', '_rgt', 'parent_id']);
    }
}
