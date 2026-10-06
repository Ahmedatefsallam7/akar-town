<?php

namespace Modules\Developers\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Forms\SeoFields;
use App\Filament\Helpers\TableHelper;
use App\Filament\Traits\HasResourceTranslations;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Developers\Filament\Resources\DeveloperResource\Pages;
use Modules\Developers\Models\Developer;
use Modules\Lookups\Filament\Forms\LocationFields;
use Modules\Lookups\Support\LocationTree;

/**
 * Real-estate developer profiles: developer pages and the "Our Partners" strip on the website.
 */
class DeveloperResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    protected static ?string $permissionGroup = 'property_management';

    protected static ?string $model = Developer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 2;

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
        return ['name_en', 'name_ar', 'email'];
    }

    public static function getModelLabel(): string
    {
        return __('admin.developers.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.developers.plural');
    }

    /**
     * A developer that has projects (also in the trash) can't be deleted: every project needs its developer.
     */
    protected static function canPerformOnRecord(string $ability, Model $record): bool
    {
        return static::recordDenialMessage($ability, $record) === null;
    }

    protected static function recordDenialMessage(string $ability, Model $record): ?string
    {
        return in_array($ability, ['delete', 'forceDelete'], true) && $record->hasProjects()
            ? __('admin.developers.cannot_delete_with_projects')
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
                        Tab::make(__('admin.developers.identity'))
                            ->icon('heroicon-o-identification')
                            ->schema([
                                SeoFields::slugFrom(TextInput::make('name_en')
                                    ->label(__('admin.developers.name_en'))
                                    ->required()
                                    ->maxLength(191), Developer::class),
                                TextInput::make('name_ar')
                                    ->label(__('admin.developers.name_ar'))
                                    ->required()
                                    ->maxLength(191),
                                Toggle::make('is_published')
                                    ->label(__('admin.developers.published'))
                                    ->helperText(__('admin.developers.published_help'))
                                    ->default(true),
                                Toggle::make('is_featured')
                                    ->label(__('admin.developers.featured'))
                                    ->helperText(__('admin.developers.featured_help'))
                                    ->default(false),
                            ])
                            ->columns(2),
                        Tab::make(__('admin.developers.tagline'))
                            ->icon('heroicon-o-chat-bubble-bottom-center-text')
                            ->schema([
                                TextInput::make('tagline_en')
                                    ->label(__('admin.developers.tagline_en'))
                                    ->helperText(__('admin.developers.tagline_help'))
                                    ->maxLength(191),
                                TextInput::make('tagline_ar')
                                    ->label(__('admin.developers.tagline_ar'))
                                    ->maxLength(191),
                            ])
                            ->columns(2),
                        Tab::make(__('admin.developers.description'))
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Textarea::make('short_description_en')
                                    ->label(__('admin.developers.short_description_en'))
                                    ->helperText(__('admin.developers.short_description_help'))
                                    ->rows(3),
                                Textarea::make('short_description_ar')
                                    ->label(__('admin.developers.short_description_ar'))
                                    ->rows(3),
                                Textarea::make('description_en')
                                    ->label(__('admin.developers.description_en'))
                                    ->helperText(__('admin.developers.description_help'))
                                    ->rows(6),
                                Textarea::make('description_ar')
                                    ->label(__('admin.developers.description_ar'))
                                    ->rows(6),
                            ])
                            ->columns(2),
                        Tab::make(__('admin.developers.contact'))
                            ->icon('heroicon-o-phone')
                            ->schema([
                                TextInput::make('email')
                                    ->label(__('admin.developers.email'))
                                    ->email()
                                    ->maxLength(191)
                                    ->prefixIcon('heroicon-m-envelope'),
                                TextInput::make('phone')
                                    ->label(__('admin.developers.phone'))
                                    ->tel()
                                    ->maxLength(30)
                                    ->prefixIcon('heroicon-m-phone'),
                                ...LocationFields::make(),
                                TextInput::make('neighbourhood')
                                    ->label(__('admin.developers.neighbourhood'))
                                    ->helperText(__('admin.developers.head_office_help'))
                                    ->maxLength(191),
                            ])
                            ->columns(2),
                        Tab::make(__('admin.developers.media'))
                            ->icon('heroicon-o-photo')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('logo')
                                    ->label(__('admin.developers.logo'))
                                    ->collection(Developer::LOGO)
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(2048)
                                    ->helperText(__('admin.developers.logo_dimensions'))
                                    // Big files are scaled down in the browser before upload; small ones are kept.
                                    ->imageResizeMode('contain')
                                    ->imageResizeTargetWidth('800')
                                    ->imageResizeTargetHeight('800')
                                    ->imageResizeUpscale(false),
                                SpatieMediaLibraryFileUpload::make('featured_image')
                                    ->label(__('admin.developers.featured_image'))
                                    ->collection(Developer::FEATURED_IMAGE)
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->helperText(__('admin.developers.featured_image_dimensions'))
                                    ->imageResizeMode('contain')
                                    ->imageResizeTargetWidth('1920')
                                    ->imageResizeTargetHeight('1920')
                                    ->imageResizeUpscale(false),
                            ])
                            ->columns(2),
                        SeoFields::tab([SeoFields::slug()]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableHelper::emptyState($table, static::class, 'heroicon-o-building-office-2')
            ->striped()
            ->columns([
                TableHelper::indexColumn(),
                SpatieMediaLibraryImageColumn::make('featured_image')
                    ->label(__('admin.developers.featured_image'))
                    ->collection(Developer::FEATURED_IMAGE)
                    ->conversion('thumb')
                    ->imageSize(48)
                    ->square()
                    ->alignment('center'),
                SpatieMediaLibraryImageColumn::make('logo')
                    ->label(__('admin.developers.logo'))
                    ->collection(Developer::LOGO)
                    ->conversion('thumb')
                    ->imageSize(48)
                    ->alignment('center')
                    ->url(fn (Developer $record) => static::getUrl('view', ['record' => $record])),
                TextColumn::make('projects_count')
                    ->label(__('admin.developers.projects_count'))
                    ->counts('projects')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->alignment('center'),
                TextColumn::make('name_en')
                    ->label(__('admin.developers.name_en'))
                    ->searchable(['name_en', 'name_ar', 'email', 'phone'])
                    ->sortable()
                    ->weight('medium')
                    ->url(fn (Developer $record) => static::getUrl('view', ['record' => $record]))
                    ->color('primary')
                    ->toggleable(),
                TextColumn::make('name_ar')
                    ->label(__('admin.developers.name_ar'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make(getLocalizedFieldName('tagline'))
                    ->label(__('admin.developers.tagline'))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(getLocalizedFieldName('short_description'))
                    ->label(__('admin.developers.short_description'))
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->label(__('admin.developers.email'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')
                    ->label(__('admin.developers.phone'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location')
                    ->label(__('admin.developers.location'))
                    ->state(fn (Developer $record) => LocationTree::path([$record->country_id, $record->city_id, $record->region_id, $record->area_id, $record->sub_area_id]) ?: null)
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
                IconColumn::make('is_published')
                    ->label(__('admin.developers.published'))
                    ->boolean()
                    ->sortable()
                    ->alignment('center'),
                TableHelper::createdAtColumn(),
            ])
            // Projects in the trash count too when deciding whether the developer can be deleted.
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['projects as all_projects_count' => fn (Builder $query) => $query->withTrashed()]))
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->filters([
                TableHelper::dateRangeFilter('created_at', 'created_from', 'created_until'),
                ...LocationFields::filters(),
                TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions(TableHelper::recordActions(
                fn (Developer $record) => __('admin.developers.notifications.deleted', ['name' => $record->getLocalized('name')]),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDevelopers::route('/'),
            'create' => Pages\CreateDeveloper::route('/create'),
            'view' => Pages\ViewDeveloper::route('/{record}'),
            'edit' => Pages\EditDeveloper::route('/{record}/edit'),
        ];
    }
}
