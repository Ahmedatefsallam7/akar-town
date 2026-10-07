<?php

namespace Modules\Units\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Forms\MapPicker;
use App\Filament\Forms\SeoFields;
use App\Filament\Helpers\TableHelper;
use App\Filament\Schemas\Tab;
use App\Filament\Support\PrefixSearch;
use App\Filament\Traits\HasResourceTranslations;
use App\Rules\VideoUrl;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Galleries\Models\Gallery;
use Modules\Lookups\Filament\Forms\LocationFields;
use Modules\Lookups\Filament\Forms\LookupFields;
use Modules\Lookups\Support\LocationTree;
use Modules\Lookups\Support\LookupOptions;
use Modules\Projects\Models\Project;
use Modules\Units\Filament\Resources\UnitResource\Pages;
use Modules\Units\Filament\Resources\UnitResource\RelationManagers;
use Modules\Units\Models\Unit;

/**
 * Properties (units): individual listings for primary, resale or rent.
 */
class UnitResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    /**
     * Upper limits that fit the database columns, so a typo can't cause a save error.
     */
    private const MAX_PRICE = 1_000_000_000_000;

    private const MAX_AREA = 10_000_000;

    protected static ?string $permissionGroup = 'property_management';

    protected static ?string $model = Unit::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.property_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.units.plural');
    }

    public static function getRecordTitleAttribute(): string
    {
        return getLocalizedFieldName('name');
    }

    /**
     * Searched by the table search and the global search; each has an index (units can be many).
     */
    public const SEARCH_COLUMNS = ['name_en', 'name_ar', 'slug'];

    public static function getGloballySearchableAttributes(): array
    {
        return static::SEARCH_COLUMNS;
    }

    /**
     * Global search uses the same fast prefix search as the table (see PrefixSearch).
     */
    protected static function applyGlobalSearchAttributeConstraints(Builder $query, string $search): void
    {
        PrefixSearch::apply($query, $search, static::SEARCH_COLUMNS);
    }

    public static function getModelLabel(): string
    {
        return __('admin.units.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.units.plural');
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
                                SeoFields::slugFrom(TextInput::make('name_en')
                                    ->label(__('admin.units.name_en'))
                                    ->required()
                                    ->maxLength(191), Unit::class),
                                TextInput::make('name_ar')
                                    ->label(__('admin.units.name_ar'))
                                    ->required()
                                    ->maxLength(191),
                                Select::make('project_id')
                                    ->label(__('admin.units.project'))
                                    ->helperText(__('admin.units.project_help'))
                                    ->relationship('project', getLocalizedFieldName('title'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get, ?int $state) => static::takeProjectContext($set, $get, $state)),
                                LookupFields::select('offering_type_id', 'offeringtype', static::projectScope('offeringtype'))
                                    ->label(__('admin.units.offering_type'))
                                    ->helperText(__('admin.units.offering_type_help')),
                                ...LocationFields::make(),
                                TextInput::make('neighbourhood')
                                    ->label(__('admin.units.neighbourhood'))
                                    ->maxLength(191),
                                TextInput::make('address')
                                    ->label(__('admin.units.address'))
                                    ->maxLength(191),
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
                                Toggle::make('is_published')
                                    ->label(__('admin.units.published'))
                                    ->helperText(__('admin.units.published_help'))
                                    ->default(false),
                                Toggle::make('is_featured')
                                    ->label(__('admin.units.featured'))
                                    ->helperText(__('admin.units.featured_help'))
                                    ->default(false),
                                TextInput::make('order')
                                    ->label(__('admin.table.order'))
                                    ->helperText(__('admin.units.order_help'))
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(999_999)
                                    ->default(0),
                            ])
                            ->columns(2),
                        SeoFields::tab([SeoFields::slug()]),
                        Tab::make(__('admin.tabs.pricing_space'))
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                TextInput::make('price')
                                    ->label(__('admin.units.price'))
                                    ->helperText(__('admin.units.price_help'))
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(self::MAX_PRICE),
                                TextInput::make('area')
                                    ->label(__('admin.units.area'))
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(self::MAX_AREA)
                                    ->suffix(__('admin.projects.square_meters')),
                                TextInput::make('bedrooms')
                                    ->label(__('admin.units.bedrooms'))
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(50),
                                TextInput::make('bathrooms')
                                    ->label(__('admin.units.bathrooms'))
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(50),
                                TextInput::make('floor')
                                    ->label(__('admin.units.floor'))
                                    ->helperText(__('admin.units.floor_help'))
                                    ->integer()
                                    ->minValue(-10)
                                    ->maxValue(300),
                                TextInput::make('down_payment')
                                    ->label(__('admin.projects.down_payment'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%'),
                                TextInput::make('installment_years_from')
                                    ->label(__('admin.projects.installments_from'))
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(30)
                                    ->suffix(__('admin.projects.years')),
                                TextInput::make('installment_years_to')
                                    ->label(__('admin.projects.installments_to'))
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(30)
                                    ->suffix(__('admin.projects.years'))
                                    ->rule(static::notBelow('installment_years_from')),
                                DatePicker::make('delivery_date')
                                    ->label(__('admin.projects.delivery_date')),
                            ])
                            ->columns(2),
                        Tab::make(__('admin.tabs.associations'))
                            ->icon('heroicon-o-squares-2x2')
                            ->schema([
                                ...collect(Unit::MULTI_LOOKUPS)
                                    ->map(fn (string $type, string $field) => Section::make(__("admin.units.{$field}"))
                                        ->schema([LookupFields::checkboxList($field, $type)->label(__("admin.units.{$field}"))->hiddenLabel()])
                                        ->collapsible()
                                        ->compact())
                                    ->values()
                                    ->all(),
                                Section::make(__('admin.units.single_choices'))
                                    ->compact()
                                    ->schema([
                                        LookupFields::radio('finishing_type_id', 'finishingtype')->label(__('admin.units.finishing_type')),
                                        LookupFields::radio('furnishing_status_id', 'furnishingstatus')->label(__('admin.units.furnishing_status')),
                                        LookupFields::radio('unit_status_id', 'unitstatus')->label(__('admin.units.unit_status')),
                                        LookupFields::radio('property_usage_id', 'propertyusage', static::projectScope('propertyusage'))->label(__('admin.units.property_usage')),
                                        LookupFields::radio('property_type_id', 'unittype', static::projectScope('unittype'))->label(__('admin.units.property_type')),
                                    ]),
                            ]),
                        Tab::make(__('admin.tabs.description'))
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Textarea::make('short_description_en')
                                    ->label(__('admin.units.short_description_en'))
                                    ->maxLength(20000)
                                    ->rows(3),
                                Textarea::make('short_description_ar')
                                    ->label(__('admin.units.short_description_ar'))
                                    ->maxLength(20000)
                                    ->rows(3),
                                static::richEditor('description_en', __('admin.units.description_en')),
                                static::richEditor('description_ar', __('admin.units.description_ar')),
                            ])
                            ->columns(2),
                        Tab::make(__('admin.tabs.media'))
                            ->icon('heroicon-o-photo')
                            ->schema([
                                static::image('featured_image', Unit::FEATURED_IMAGE, 5120, 1920),
                                Select::make('gallery_id')
                                    ->label(__('admin.units.gallery'))
                                    ->helperText(__('admin.units.gallery_help'))
                                    // Its project's galleries, or galleries with no project for a unit without one.
                                    ->relationship('gallery', 'name_en', fn (Builder $query, Get $get) => filled($get('project_id'))
                                        ? $query->where('project_id', $get('project_id'))
                                        : $query->whereNull('project_id'))
                                    ->getOptionLabelFromRecordUsing(fn (Gallery $record) => $record->getLocalized('name'))
                                    ->searchable()
                                    ->preload(),
                                static::image('images_360', Unit::IMAGES_360, 20480)
                                    ->multiple()
                                    ->reorderable()
                                    ->helperText(__('admin.galleries.images_360_help', ['size' => 20])),
                                static::image('floor_plan', Unit::FLOOR_PLAN, 10240, 3000),
                                TextInput::make('video_url')
                                    ->label(__('admin.units.video_url'))
                                    ->url()
                                    ->maxLength(500)
                                    ->rule(new VideoUrl)
                                    ->placeholder('https://www.youtube.com/watch?v=…'),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableHelper::emptyState($table, static::class, 'heroicon-o-home-modern')
            ->striped()
            ->columns([
                TableHelper::indexColumn(),
                SpatieMediaLibraryImageColumn::make('featured_image')
                    ->label(__('admin.units.image'))
                    ->collection(Unit::FEATURED_IMAGE)
                    ->conversion('thumb')
                    ->imageSize(48)
                    ->square()
                    ->alignment('center'),
                TextColumn::make(getLocalizedFieldName('name'))
                    ->label(__('admin.table.title'))
                    // Fast "starts with" search (see PrefixSearch): "Villa 1" finds "Villa 12", not "Garden Villa".
                    ->searchable(query: PrefixSearch::using(static::SEARCH_COLUMNS))
                    ->sortable()
                    ->weight('medium')
                    ->url(fn (Unit $record) => static::getUrl('view', ['record' => $record]))
                    ->color('primary'),
                IconColumn::make('is_featured')
                    ->label(__('admin.table.featured'))
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->trueColor('warning')
                    ->falseIcon('heroicon-o-star')
                    ->falseColor('gray')
                    ->sortable()
                    ->alignment('center'),
                IconColumn::make('is_published')
                    ->label(__('admin.units.published'))
                    ->boolean()
                    ->sortable()
                    ->alignment('center'),
                TextColumn::make('offering_type_id')
                    ->label(__('admin.units.offering_type'))
                    ->formatStateUsing(fn (?int $state) => LookupOptions::find('offeringtype', $state)?->getLocalized('name'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('project.'.getLocalizedFieldName('title'))
                    ->label(__('admin.units.project'))
                    ->placeholder(__('admin.units.standalone')),
                TextColumn::make('price')
                    ->label(__('admin.units.price'))
                    ->formatStateUsing(fn (Unit $record) => static::money($record))
                    ->sortable(),
                TextColumn::make('area')
                    ->label(__('admin.units.area'))
                    ->numeric()
                    ->suffix(' '.__('admin.projects.square_meters'))
                    ->sortable(),
                TextColumn::make('bedrooms')
                    ->label(__('admin.units.beds'))
                    ->sortable()
                    ->alignment('center'),
                TextColumn::make('bathrooms')
                    ->label(__('admin.units.baths'))
                    ->sortable()
                    ->alignment('center'),
                TextColumn::make('location')
                    ->label(__('admin.units.location'))
                    ->state(fn (Unit $record) => LocationTree::path([$record->country_id, $record->city_id, $record->region_id, $record->area_id, $record->sub_area_id]) ?: null)
                    ->limit(40),
                TextColumn::make('slug')
                    ->label(__('admin.seo.slug'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TableHelper::createdAtColumn(),
            ])
            // "Villa 1" is one name, not two words (each would have to start a field).
            ->splitSearchTerms(false)
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->filters([
                TableHelper::rangeFilter('price', 'price', 'price', __('admin.units.price')),
                TableHelper::rangeFilter('area', 'area', 'area', __('admin.units.area')),
                SelectFilter::make('bedrooms')
                    ->label(__('admin.units.bedrooms'))
                    ->options(static::countOptions()),
                SelectFilter::make('bathrooms')
                    ->label(__('admin.units.bathrooms'))
                    ->options(static::countOptions()),
                SelectFilter::make('project_id')
                    ->label(__('admin.units.project'))
                    ->relationship('project', getLocalizedFieldName('title'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('offering_type_id')
                    ->label(__('admin.units.offering_type'))
                    ->options(fn () => LookupOptions::for('offeringtype')),
                TableHelper::yesNoFilter('is_published', __('admin.units.published')),
                TableHelper::yesNoFilter('is_featured', __('admin.units.featured')),
                TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions(TableHelper::recordActions(
                fn (Unit $record) => __('admin.units.notifications.deleted', ['name' => $record->getLocalized('name')]),
            ))
            ->recordActionsColumnLabel(__('admin.table.actions'))
            ->recordActionsAlignment('center')
            ->toolbarActions(TableHelper::standardBulkActions([
                TableHelper::statusBulkAction(static::class, 'publish', 'heroicon-o-eye', true, 'is_published'),
                TableHelper::statusBulkAction(static::class, 'unpublish', 'heroicon-o-eye-slash', false, 'is_published'),
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
     * Price history on the View page.
     */
    public static function getRelations(): array
    {
        return [
            RelationManagers\PriceHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'view' => Pages\ViewUnit::route('/{record}'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
        ];
    }

    /**
     * "2,500,000 EGP": the project's currency, or EGP for a unit on its own (as in Dastan).
     */
    public static function money(Unit $unit, ?int $amount = null): ?string
    {
        $amount ??= $unit->price;

        if ($amount === null) {
            return null;
        }

        $currency = $unit->project?->currency_id ? LookupOptions::find('currencies', $unit->project->currency_id) : null;

        return number_format($amount).' '.($currency ? strtoupper($currency->slug) : 'EGP');
    }

    /**
     * Picking a project: take its location (as in Dastan) and drop choices the project doesn't offer.
     */
    public static function takeProjectContext(Set $set, Get $get, ?int $projectId): void
    {
        $project = static::project($projectId);

        if ($project) {
            foreach (LocationTree::LEVELS as $column) {
                $set($column, $project->{$column});
            }
        }

        foreach (Unit::PROJECT_SCOPED as $column => $type) {
            $allowed = static::projectChoices($projectId, $type);
            if ($allowed !== null && ! in_array((int) $get($column), $allowed, true)) {
                $set($column, null);
            }
        }

        // The gallery must belong to the new project (or to none).
        $gallery = filled($get('gallery_id')) ? Gallery::find($get('gallery_id')) : null;
        if ($gallery && $gallery->project_id !== $projectId) {
            $set('gallery_id', null);
        }
    }

    /**
     * Limits a choice to what the unit's project picked (as in Dastan). A unit without a project, or a project that
     * picked nothing of this type, gets every value.
     */
    private static function projectScope(string $type): Closure
    {
        return fn (Get $get) => static::projectChoices(filled($get('project_id')) ? (int) $get('project_id') : null, $type);
    }

    /**
     * @return array<int>|null
     */
    private static function projectChoices(?int $projectId, string $type): ?array
    {
        $ids = static::project($projectId)?->lookupIds($type) ?? [];

        return $ids === [] ? null : $ids;
    }

    private static function project(?int $projectId): ?Project
    {
        return $projectId ? once(fn () => Project::with('lookups:id')->find($projectId)) : null;
    }

    /**
     * @return array<int, string> 0 = studio … 6+
     */
    private static function countOptions(): array
    {
        return [0 => '0', 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6+'];
    }

    private static function richEditor(string $name, string $label): RichEditor
    {
        return RichEditor::make($name)
            ->label($label)
            ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'undo', 'redo'])
            ->columnSpanFull();
    }

    /**
     * JPG/PNG/WebP. With $resizeTo, big images are scaled down in the browser before upload.
     */
    private static function image(string $name, string $collection, int $maxKb, ?int $resizeTo = null): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make($name)
            ->label(__("admin.units.{$name}"))
            ->collection($collection)
            ->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize($maxKb)
            ->helperText(__('admin.galleries.image_help', ['size' => $maxKb / 1024]))
            ->when($resizeTo, fn (SpatieMediaLibraryFileUpload $field) => $field
                ->imageResizeMode('contain')
                ->imageResizeTargetWidth((string) $resizeTo)
                ->imageResizeTargetHeight((string) $resizeTo)
                ->imageResizeUpscale(false));
    }

    /**
     * "To" can't be below "From" (when both are filled).
     */
    private static function notBelow(string $fromField): Closure
    {
        return fn (Get $get) => function (string $attribute, mixed $value, Closure $fail) use ($get, $fromField) {
            if (filled($value) && filled($get($fromField)) && (float) $value < (float) $get($fromField)) {
                $fail(__('admin.projects.range_invalid'));
            }
        };
    }
}
