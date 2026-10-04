<?php

namespace Modules\Lookups\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Helpers\TableHelper;
use App\Filament\Traits\HasResourceTranslations;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Modules\Lookups\Filament\Resources\LookupResource\Pages;
use Modules\Lookups\Models\Lookup;

class LookupResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    protected static ?string $permissionGroup = 'system_configuration';

    protected static ?string $model = Lookup::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.system_configuration');
    }

    public static function getRecordTitleAttribute(): string
    {
        return getLocalizedFieldName('name');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name_en', 'name_ar', 'slug'];
    }

    public static function getNavigationBadgeCount(): int
    {
        return Lookup::withoutRoles()->count();
    }

    public static function getModelLabel(): string
    {
        return __('admin.lookups.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.lookups.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.lookups.heading'))
                    ->description(__('admin.lookups.heading_desc'))
                    ->icon('heroicon-m-list-bullet')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name_en')
                            ->label(__('admin.lookups.name_en'))
                            ->required()
                            ->maxLength(191)
                            ->prefixIcon('heroicon-m-language')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                if (blank($get('slug'))) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('name_ar')
                            ->label(__('admin.lookups.name_ar'))
                            ->required()
                            ->maxLength(191)
                            ->prefixIcon('heroicon-m-language'),
                        TextInput::make('slug')
                            ->label(__('admin.table.slug'))
                            ->required()
                            // Slugs repeat across categories (e.g. "studio"), so they are unique per parent only.
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $get('parent_id')
                                ? $rule->where('parent_id', $get('parent_id'))
                                : $rule->whereNull('parent_id'))
                            ->maxLength(191)
                            ->prefixIcon('heroicon-m-link')
                            ->helperText(__('admin.lookups.slug_helper')),
                        Select::make('parent_id')
                            ->label(__('admin.lookups.parent'))
                            ->options(fn (?Lookup $record = null) => Lookup::query()
                                ->withoutRoles()
                                // Not itself or one of its own items (that would loop the tree): outside its nested-set range.
                                ->when($record?->id, fn ($q) => $q->whereNotBetween('_lft', [$record->_lft, $record->_rgt]))
                                ->orderBy(getLocalizedFieldName('name'))
                                ->pluck(getLocalizedFieldName('name'), 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->prefixIcon('heroicon-m-folder')
                            ->placeholder(__('admin.lookups.parent_placeholder')),
                        TextInput::make('order')
                            ->label(__('admin.table.order'))
                            ->integer()
                            ->default(0)
                            ->prefixIcon('heroicon-m-arrows-up-down')
                            ->helperText(__('admin.lookups.order_helper')),
                        Grid::make()
                            ->schema([
                                Toggle::make('is_active')
                                    ->label(__('admin.lookups.active'))
                                    ->default(true)
                                    ->onIcon('heroicon-m-check-circle')
                                    ->offIcon('heroicon-m-x-circle')
                                    ->onColor('success')
                                    ->offColor('danger'),
                                Toggle::make('is_default')
                                    ->label(__('admin.lookups.default'))
                                    ->onIcon('heroicon-m-star')
                                    ->offIcon('heroicon-m-star')
                                    ->onColor('warning'),
                            ])->columns(2),
                        Textarea::make('description_en')
                            ->label(__('admin.lookups.description_en'))
                            ->columnSpanFull(),
                        Textarea::make('description_ar')
                            ->label(__('admin.lookups.description_ar'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableHelper::emptyState($table, static::class, 'heroicon-o-list-bullet')
            ->reorderable('order')
            ->columns([
                TableHelper::indexColumn(),
                TextColumn::make(getLocalizedFieldName('name'))
                    ->label(__('admin.table.title'))
                    ->sortable()
                    ->searchable(['name_en', 'name_ar'])
                    ->alignment('center')
                    ->url(fn (Lookup $record) => static::getUrl('view', ['record' => $record]))
                    ->color('primary')
                    ->description(fn (Lookup $record): string => $record->parent_id === null
                        ? $record->children_count.' '.__('admin.lookups.children_count')
                        : ''
                    ),
                TextColumn::make('parent.'.getLocalizedFieldName('name'))
                    ->label(__('admin.lookups.parent'))
                    ->sortable()
                    ->searchable()
                    ->alignment('center')
                    ->placeholder(__('admin.lookups.root_category')),
                TextColumn::make('slug')
                    ->label(__('admin.table.slug'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignment('center')
                    ->copyable()
                    ->copyMessage(__('admin.actions.copied')),
                IconColumn::make('is_active')
                    ->label(__('admin.lookups.active'))
                    ->boolean()
                    ->sortable()
                    ->alignment('center'),
                IconColumn::make('is_default')
                    ->label(__('admin.lookups.default'))
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignment('center'),
                TextColumn::make('order')
                    ->label(__('admin.table.order'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignment('center'),
                TableHelper::createdAtColumn(),
                TextColumn::make('updated_at')
                    ->label(__('admin.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignment('center'),
            ])
            ->striped()
            ->defaultSort('order')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin.table.status'))
                    ->trueLabel(__('admin.lookups.active'))
                    ->falseLabel(__('admin.lookups.deactive'))
                    ->indicator(__('admin.table.status')),
                TernaryFilter::make('is_default')
                    ->label(__('admin.lookups.is_default'))
                    ->trueLabel(__('admin.lookups.default'))
                    ->falseLabel(__('admin.lookups.not_default'))
                    ->indicator(__('admin.lookups.default')),
                Filter::make('taxonomy')
                    ->schema([
                        Select::make('category_id')
                            ->label(__('admin.lookups.category'))
                            ->options(fn () => Lookup::whereNull('parent_id')->withoutRoles()->pluck(getLocalizedFieldName('name'), 'id'))
                            ->live()
                            ->searchable()
                            ->preload()
                            ->placeholder(__('admin.lookups.select_category'))
                            ->prefixIcon('heroicon-m-folder')
                            ->afterStateUpdated(fn (Set $set) => $set('subcategory_id', null)),
                        Select::make('subcategory_id')
                            ->label(__('admin.lookups.subcategory'))
                            ->options(fn (Get $get) => $get('category_id')
                                ? Lookup::where('parent_id', $get('category_id'))->pluck(getLocalizedFieldName('name'), 'id')
                                : []
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->placeholder(__('admin.lookups.select_subcategories'))
                            ->prefixIcon('heroicon-m-list-bullet')
                            ->disabled(fn (Get $get) => ! $get('category_id'))
                            ->hint(fn (Get $get) => ! $get('category_id') ? __('admin.lookups.choose_category_first') : ''),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->query(function (Builder $query, array $data): Builder {
                        $subcategoryIds = array_filter((array) ($data['subcategory_id'] ?? []));

                        if (! empty($subcategoryIds)) {
                            return $query->whereIn('id', $subcategoryIds);
                        }

                        if ($categoryId = $data['category_id'] ?? null) {
                            return $query->where(fn (Builder $q) => $q
                                ->where('parent_id', $categoryId)
                                ->orWhereHas('parent', fn (Builder $pq) => $pq->where('parent_id', $categoryId)));
                        }

                        return $query;
                    }),
                Filter::make('root_only')
                    ->label(__('admin.lookups.root_only'))
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNull('parent_id'))
                    ->indicator(__('admin.lookups.root_only')),
                TableHelper::dateRangeFilter('created_at', 'created_from', 'created_until'),
                TableHelper::dateRangeFilter('updated_at', 'updated_from', 'updated_until'),
                TrashedFilter::make(),
            ])
            ->filtersFormColumns(2)
            ->filtersFormWidth('2xl')
            ->recordActions(TableHelper::recordActions(
                fn (Lookup $record) => __('admin.lookups.notifications.deleted', ['name' => $record->getLocalized('name')]),
            ))
            ->recordActionsColumnLabel(__('admin.table.actions'))
            ->recordActionsAlignment('center')
            ->toolbarActions(TableHelper::standardBulkActions([
                TableHelper::statusBulkAction(static::class, 'activate', 'heroicon-o-check-circle', true),
                TableHelper::statusBulkAction(static::class, 'deactivate', 'heroicon-o-x-circle', false),
            ]))
            ->paginated([10, 25, 50, 100]);
    }

    /**
     * Protected lookups can't be deleted, from the row menu or with bulk delete.
     */
    protected static function canPerformOnRecord(string $ability, Model $record): bool
    {
        return match ($ability) {
            // Deleting a category also deletes its items, so it must not contain protected ones.
            'delete', 'forceDelete' => $record->is_deletable && ! $record->hasProtectedDescendants(),
            default => true,
        };
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            // Roles are managed only from the Roles resource.
            ->withoutRoles()
            ->with('parent')
            ->withCount('children')
            ->withProtectedDescendantsFlag()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLookups::route('/'),
            'create' => Pages\CreateLookup::route('/create'),
            'view' => Pages\ViewLookup::route('/{record}'),
            'edit' => Pages\EditLookup::route('/{record}/edit'),
        ];
    }
}
