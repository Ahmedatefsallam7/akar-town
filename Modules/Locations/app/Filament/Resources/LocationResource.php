<?php

namespace Modules\Locations\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Forms\MapPicker;
use App\Filament\Forms\SeoFields;
use App\Filament\Helpers\TableHelper;
use App\Filament\Schemas\Tab;
use App\Filament\Traits\HasResourceTranslations;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Locations\Filament\Resources\LocationResource\Pages;
use Modules\Locations\Filament\Resources\LocationResource\RelationManagers;
use Modules\Locations\Models\Location;
use Modules\Lookups\Filament\Forms\LocationFields;
use Modules\Lookups\Support\LocationTree;

/**
 * Location landing pages for the website's "Explore by Location" section.
 */
class LocationResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    protected static ?string $permissionGroup = 'property_management';

    protected static ?string $model = Location::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.property_management');
    }

    public static function getRecordTitleAttribute(): string
    {
        return getLocalizedFieldName('name');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name_en', 'name_ar', 'code'];
    }

    public static function getModelLabel(): string
    {
        return __('admin.locations.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.locations.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make(__('admin.tabs.general'))
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make(__('admin.tabs.details'))
                                    ->schema([
                                        SeoFields::slugFrom(TextInput::make('name_en')
                                            ->label(__('admin.locations.name_en'))
                                            ->required()
                                            ->maxLength(191), Location::class),
                                        TextInput::make('name_ar')
                                            ->label(__('admin.locations.name_ar'))
                                            ->required()
                                            ->maxLength(191),
                                        TextInput::make('code')
                                            ->label(__('admin.locations.code'))
                                            ->helperText(__('admin.locations.code_help'))
                                            ->maxLength(10),
                                        TextInput::make('order')
                                            ->label(__('admin.table.order'))
                                            ->helperText(__('admin.locations.order_help'))
                                            ->integer()
                                            ->minValue(0)
                                            ->maxValue(999_999)
                                            ->default(0),
                                        Toggle::make('is_featured')
                                            ->label(__('admin.locations.featured'))
                                            ->helperText(__('admin.locations.featured_help'))
                                            ->default(false),
                                        Toggle::make('is_active')
                                            ->label(__('admin.locations.active'))
                                            ->helperText(__('admin.locations.active_help'))
                                            ->default(true),
                                    ])
                                    ->columns(2),
                                Section::make(__('admin.locations.chain'))
                                    ->schema([
                                        ...LocationFields::make(),
                                        TagsInput::make('neighbourhoods')
                                            ->label(__('admin.locations.neighbourhoods'))
                                            ->placeholder(__('admin.locations.neighbourhoods_placeholder'))
                                            ->nestedRecursiveRules(['max:100'])
                                            ->columnSpanFull(),
                                        TextInput::make('latitude')
                                            ->label(__('admin.filter.latitude'))
                                            ->numeric()
                                            ->minValue(-90)
                                            ->maxValue(90)
                                            ->requiredWith('longitude'),
                                        TextInput::make('longitude')
                                            ->label(__('admin.filter.longitude'))
                                            ->numeric()
                                            ->minValue(-180)
                                            ->maxValue(180)
                                            ->requiredWith('latitude'),
                                        MapPicker::make(),
                                    ])
                                    ->columns(2),
                                Section::make(__('admin.tabs.description'))
                                    ->schema([
                                        Textarea::make('description_en')
                                            ->label(__('admin.table.description_en'))
                                            ->maxLength(20000)
                                            ->rows(5),
                                        Textarea::make('description_ar')
                                            ->label(__('admin.table.description_ar'))
                                            ->maxLength(20000)
                                            ->rows(5),
                                    ]),
                            ]),
                        SeoFields::tab([SeoFields::slug()]),
                        Tab::make(__('admin.tabs.media'))
                            ->icon('heroicon-o-photo')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('featured_image')
                                    ->label(__('admin.table.featured_image'))
                                    ->collection(Location::FEATURED_IMAGE)
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->helperText(__('admin.locations.image_help'))
                                    // Big photos are scaled down in the browser before upload; small ones are kept.
                                    ->imageResizeMode('contain')
                                    ->imageResizeTargetWidth('1920')
                                    ->imageResizeTargetHeight('1920')
                                    ->imageResizeUpscale(false),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableHelper::emptyState($table, static::class, 'heroicon-o-map-pin')
            ->striped()
            ->columns([
                TableHelper::indexColumn(),
                SpatieMediaLibraryImageColumn::make('featured_image')
                    ->label(__('admin.table.featured_image'))
                    ->collection(Location::FEATURED_IMAGE)
                    ->conversion('thumb')
                    ->imageSize(48)
                    ->square()
                    ->alignment('center'),
                TextColumn::make(getLocalizedFieldName('name'))
                    ->label(__('admin.table.name'))
                    ->searchable(['name_en', 'name_ar'])
                    ->sortable()
                    ->weight('medium')
                    ->url(fn (Location $record) => static::getUrl('view', ['record' => $record]))
                    ->color('primary'),
                TextColumn::make('neighbourhoods')
                    ->label(__('admin.locations.neighbourhoods'))
                    ->badge()
                    ->color('gray')
                    ->limitList(3)
                    ->expandableLimitedList(),
                TextColumn::make('code')
                    ->label(__('admin.locations.code'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location')
                    ->label(__('admin.locations.chain'))
                    ->state(fn (Location $record) => LocationTree::path([$record->country_id, $record->city_id, $record->region_id, $record->area_id, $record->sub_area_id]) ?: null)
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_featured')
                    ->label(__('admin.table.featured'))
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->trueColor('warning')
                    ->falseIcon('heroicon-o-star')
                    ->falseColor('gray')
                    ->sortable()
                    ->alignment('center'),
                IconColumn::make('is_active')
                    ->label(__('admin.locations.active'))
                    ->boolean()
                    ->sortable()
                    ->alignment('center'),
                TableHelper::createdAtColumn()->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->filters([
                TableHelper::dateRangeFilter('created_at', 'created_from', 'created_until'),
                ...LocationFields::filters(),
                TableHelper::yesNoFilter('is_active', __('admin.locations.active')),
                TableHelper::yesNoFilter('is_featured', __('admin.locations.featured')),
                TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions(TableHelper::recordActions(
                fn (Location $record) => __('admin.locations.notifications.deleted', ['name' => $record->getLocalized('name')]),
            ))
            ->recordActionsColumnLabel(__('admin.table.actions'))
            ->recordActionsAlignment('center')
            ->toolbarActions(TableHelper::standardBulkActions([
                TableHelper::statusBulkAction(static::class, 'activate', 'heroicon-o-check-circle', true),
                TableHelper::statusBulkAction(static::class, 'deactivate', 'heroicon-o-x-circle', false),
                TableHelper::statusBulkAction(static::class, 'feature', 'heroicon-o-star', true, 'is_featured'),
                TableHelper::statusBulkAction(static::class, 'unfeature', 'heroicon-o-no-symbol', false, 'is_featured'),
            ]));
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Related-record tabs on the View page (as in Dastan).
     */
    public static function getRelations(): array
    {
        return [
            RelationManagers\ProjectsRelationManager::class,
            RelationManagers\UnitsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'view' => Pages\ViewLocation::route('/{record}'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
