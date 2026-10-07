<?php

namespace Modules\Projects\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Forms\SeoFields;
use App\Filament\Helpers\TableHelper;
use App\Filament\Schemas\Tab;
use App\Filament\Traits\HasResourceTranslations;
use App\Rules\VideoEmbed;
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
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Lookups\Filament\Forms\LocationFields;
use Modules\Lookups\Filament\Forms\LookupFields;
use Modules\Lookups\Support\LocationTree;
use Modules\Lookups\Support\LookupOptions;
use Modules\Projects\Filament\Resources\ProjectResource\Pages;
use Modules\Projects\Filament\Resources\ProjectResource\RelationManagers;
use Modules\Projects\Models\Project;

/**
 * Projects (compounds and developments): the core catalogue. Units, galleries and homepage sliders hang off them.
 */
class ProjectResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    /**
     * Upper limits that fit the database columns (bigint price, int area), so a typo can't cause a save error.
     */
    private const MAX_PRICE = 1_000_000_000_000;

    private const MAX_AREA = 10_000_000;

    protected static ?string $permissionGroup = 'property_management';

    protected static ?string $model = Project::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title_en';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.property_management');
    }

    public static function getRecordTitleAttribute(): string
    {
        return getLocalizedFieldName('title');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title_en', 'title_ar', 'project_number'];
    }

    public static function getModelLabel(): string
    {
        return __('admin.projects.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.projects.plural');
    }

    /**
     * A project that has units (also in the trash) can't be deleted: every unit needs its project's context.
     */
    protected static function canPerformOnRecord(string $ability, Model $record): bool
    {
        return static::recordDenialMessage($ability, $record) === null;
    }

    protected static function recordDenialMessage(string $ability, Model $record): ?string
    {
        return in_array($ability, ['delete', 'forceDelete'], true) && $record->hasUnits()
            ? __('admin.projects.cannot_delete_with_units')
            : null;
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
                                SeoFields::slugFrom(TextInput::make('title_en')
                                    ->label(__('admin.projects.title_en'))
                                    ->required()
                                    ->maxLength(191), Project::class),
                                TextInput::make('title_ar')
                                    ->label(__('admin.projects.title_ar'))
                                    ->required()
                                    ->maxLength(191),
                                TextInput::make('project_number')
                                    ->label(__('admin.projects.number'))
                                    ->helperText(__('admin.projects.number_help'))
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true),
                                Select::make('developer_id')
                                    ->label(__('admin.projects.developer'))
                                    ->relationship('developer', getLocalizedFieldName('name'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ])
                            ->columns(2),
                        SeoFields::tab([SeoFields::slug()]),
                        Tab::make(__('admin.tabs.descriptions'))
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Textarea::make('short_description_en')
                                    ->label(__('admin.projects.short_description_en'))
                                    ->helperText(__('admin.projects.short_description_help'))
                                    ->rows(3),
                                Textarea::make('short_description_ar')
                                    ->label(__('admin.projects.short_description_ar'))
                                    ->rows(3),
                                static::richEditor('description_en', __('admin.projects.description_en'))
                                    ->helperText(__('admin.projects.description_help')),
                                static::richEditor('description_ar', __('admin.projects.description_ar')),
                            ])
                            ->columns(2),
                        Tab::make(__('admin.tabs.location'))
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                ...LocationFields::make(required: 3),
                                TextInput::make('neighbourhood')
                                    ->label(__('admin.projects.neighbourhood'))
                                    ->maxLength(191),
                                TextInput::make('latitude')
                                    ->label(__('admin.filter.latitude'))
                                    ->helperText(__('admin.projects.map_help'))
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
                                TextInput::make('address_en')
                                    ->label(__('admin.projects.address_en'))
                                    ->maxLength(191),
                                TextInput::make('address_ar')
                                    ->label(__('admin.projects.address_ar'))
                                    ->maxLength(191),
                            ])
                            ->columns(2),
                        Tab::make(__('admin.tabs.pricing_finance'))
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                LookupFields::select('currency_id', 'currencies')
                                    ->label(__('admin.projects.currency'))
                                    // A price means nothing without its currency.
                                    ->requiredWith('price_from,price_to'),
                                DatePicker::make('delivery_date')
                                    ->label(__('admin.projects.delivery_date')),
                                TextInput::make('price_from')
                                    ->label(__('admin.projects.price_from'))
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(self::MAX_PRICE),
                                TextInput::make('price_to')
                                    ->label(__('admin.projects.price_to'))
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(self::MAX_PRICE)
                                    ->rule(static::notBelow('price_from')),
                                TextInput::make('area_from')
                                    ->label(__('admin.projects.area_from'))
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(self::MAX_AREA)
                                    ->suffix(__('admin.projects.square_meters')),
                                TextInput::make('area_to')
                                    ->label(__('admin.projects.area_to'))
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(self::MAX_AREA)
                                    ->suffix(__('admin.projects.square_meters'))
                                    ->rule(static::notBelow('area_from')),
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
                                Toggle::make('is_published')
                                    ->label(__('admin.projects.published'))
                                    ->helperText(__('admin.projects.published_help'))
                                    // New projects start unpublished (as in Dastan): publish when the data is complete.
                                    ->default(false),
                                Toggle::make('is_featured')
                                    ->label(__('admin.projects.featured'))
                                    ->helperText(__('admin.projects.featured_help'))
                                    ->default(false),
                            ])
                            ->columns(2),
                        Tab::make(__('admin.tabs.associations'))
                            ->icon('heroicon-o-squares-2x2')
                            ->schema(collect(Project::LOOKUP_TYPES)
                                ->map(fn (string $type, string $field) => Section::make(__("admin.projects.{$field}"))
                                    ->schema([LookupFields::checkboxList($field, $type)->label(__("admin.projects.{$field}"))->hiddenLabel()])
                                    ->collapsible()
                                    ->compact())
                                ->values()
                                ->all()),
                        Tab::make(__('admin.tabs.media'))
                            ->icon('heroicon-o-photo')
                            ->schema([
                                static::image('featured_image', Project::FEATURED_IMAGE)->required(),
                                static::image('logo', Project::LOGO)->required(),
                                static::image('gallery', Project::GALLERY)
                                    ->multiple()
                                    ->reorderable()
                                    ->maxFiles(30)
                                    ->panelLayout('grid')
                                    ->columnSpanFull(),
                                static::image('master_plan', Project::MASTER_PLAN),
                                static::image('about_image', Project::ABOUT_IMAGE),
                                Textarea::make('video_embed')
                                    ->label(__('admin.projects.video_embed'))
                                    ->helperText(__('admin.projects.video_embed_help'))
                                    ->rows(3)
                                    ->rule(new VideoEmbed)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableHelper::emptyState($table, static::class, 'heroicon-o-building-office')
            ->striped()
            ->columns([
                TableHelper::indexColumn(),
                TextColumn::make('id')
                    ->label(__('admin.projects.id'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                SpatieMediaLibraryImageColumn::make('featured_image')
                    ->label(__('admin.projects.featured_image'))
                    ->collection(Project::FEATURED_IMAGE)
                    ->conversion('thumb')
                    ->imageSize(48)
                    ->square()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                SpatieMediaLibraryImageColumn::make('logo')
                    ->label(__('admin.projects.logo'))
                    ->collection(Project::LOGO)
                    ->conversion('thumb')
                    ->imageSize(48)
                    ->alignment('center'),
                IconColumn::make('is_featured')
                    ->label(__('admin.table.featured'))
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->trueColor('warning')
                    ->falseIcon('heroicon-o-star')
                    ->falseColor('gray')
                    ->sortable()
                    ->alignment('center'),
                TextColumn::make(getLocalizedFieldName('title'))
                    ->label(__('admin.table.title'))
                    ->searchable(['title_en', 'title_ar', 'project_number'])
                    ->sortable()
                    ->weight('medium')
                    ->url(fn (Project $record) => static::getUrl('view', ['record' => $record]))
                    ->color('primary'),
                TextColumn::make('units_count')
                    ->label(__('admin.projects.units_count'))
                    ->counts('units')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->alignment('center'),
                TextColumn::make('price_from')
                    ->label(__('admin.projects.price_from'))
                    ->formatStateUsing(fn (Project $record, ?int $state) => static::money($state, $record))
                    ->sortable(),
                TextColumn::make('price_to')
                    ->label(__('admin.projects.price_to'))
                    ->formatStateUsing(fn (Project $record, ?int $state) => static::money($state, $record))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('area_from')
                    ->label(__('admin.projects.area_from'))
                    ->numeric()
                    ->suffix(' '.__('admin.projects.square_meters'))
                    ->sortable(),
                TextColumn::make('area_to')
                    ->label(__('admin.projects.area_to'))
                    ->numeric()
                    ->suffix(' '.__('admin.projects.square_meters'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_published')
                    ->label(__('admin.projects.published'))
                    ->boolean()
                    ->sortable()
                    ->alignment('center'),
                TextColumn::make('developer.'.getLocalizedFieldName('name'))
                    ->label(__('admin.projects.developer')),
                TextColumn::make('country_id')
                    ->label(__('admin.location_chain.country'))
                    ->formatStateUsing(fn (?int $state) => LocationTree::path([$state]))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('delivery_date')
                    ->label(__('admin.projects.delivery_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TableHelper::createdAtColumn(),
            ])
            // Units in the trash count too when deciding whether the project can be deleted.
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['units as all_units_count' => fn (Builder $query) => $query->withTrashed()]))
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->filters([
                TableHelper::rangeFilter('price', 'price_from', 'price_to', __('admin.projects.price')),
                TableHelper::rangeFilter('area', 'area_from', 'area_to', __('admin.projects.area')),
                ...LocationFields::filters(),
                SelectFilter::make('developer_id')
                    ->label(__('admin.projects.developer'))
                    ->relationship('developer', getLocalizedFieldName('name'))
                    ->searchable()
                    ->preload(),
                TableHelper::dateRangeFilter('delivery_date', 'delivery_from', 'delivery_until'),
                TableHelper::yesNoFilter('is_published', __('admin.projects.published')),
                TableHelper::yesNoFilter('is_featured', __('admin.projects.featured')),
                TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions(TableHelper::recordActions(
                fn (Project $record) => __('admin.projects.notifications.deleted', ['name' => $record->getLocalized('title')]),
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
     * Related-record tabs on the View page (Sliders comes with its module).
     */
    public static function getRelations(): array
    {
        return [
            RelationManagers\UnitsRelationManager::class,
            RelationManagers\GalleriesRelationManager::class,
            RelationManagers\SlidersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    /**
     * "1,500,000 EGP" (the project's currency), or nothing.
     */
    public static function money(?int $amount, Project $project): ?string
    {
        if ($amount === null) {
            return null;
        }

        $currency = LookupOptions::find('currencies', $project->currency_id);

        return number_format($amount).($currency ? ' '.strtoupper($currency->slug) : '');
    }

    private static function richEditor(string $name, string $label): RichEditor
    {
        return RichEditor::make($name)
            ->label($label)
            ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'undo', 'redo'])
            ->columnSpanFull();
    }

    /**
     * Images: JPG/PNG/WebP up to 5 MB, big photos scaled down in the browser before upload.
     */
    private static function image(string $name, string $collection): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make($name)
            ->label(__("admin.projects.{$name}"))
            ->collection($collection)
            ->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(5120)
            ->helperText(__('admin.projects.image_help'))
            ->imageResizeMode('contain')
            ->imageResizeTargetWidth('1920')
            ->imageResizeTargetHeight('1920')
            ->imageResizeUpscale(false);
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
