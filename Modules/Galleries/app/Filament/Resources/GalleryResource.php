<?php

namespace Modules\Galleries\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Helpers\TableHelper;
use App\Filament\Traits\HasResourceTranslations;
use App\Rules\VideoUrl;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Galleries\Filament\Resources\GalleryResource\Pages;
use Modules\Galleries\Models\Gallery;
use Modules\Projects\Filament\Resources\ProjectResource;

/**
 * Reusable media packs for a project or a unit type.
 */
class GalleryResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    protected static ?string $permissionGroup = 'property_management';

    protected static ?string $model = Gallery::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 4;

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
        return ['name_en', 'name_ar'];
    }

    public static function getModelLabel(): string
    {
        return __('admin.galleries.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.galleries.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.galleries.identity'))
                    ->icon('heroicon-o-identification')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextInput::make('name_en')
                            ->label(__('admin.galleries.name_en'))
                            ->placeholder(__('admin.galleries.name_example'))
                            ->required()
                            ->maxLength(191),
                        TextInput::make('name_ar')
                            ->label(__('admin.galleries.name_ar'))
                            ->maxLength(191),
                        Select::make('project_id')
                            ->label(__('admin.galleries.project'))
                            ->helperText(__('admin.galleries.project_help'))
                            ->relationship('project', getLocalizedFieldName('title'))
                            ->searchable()
                            ->preload(),
                    ]),
                static::mediaSection('featured_image', 'heroicon-o-star', static::images(Gallery::FEATURED_IMAGE, 5120, 1920)),
                static::mediaSection('images_360', 'heroicon-o-globe-alt', static::images(Gallery::IMAGES_360, 20480)
                    ->multiple()
                    ->reorderable()
                    ->helperText(__('admin.galleries.images_360_help', ['size' => 20]))),
                static::mediaSection('photos', 'heroicon-o-photo', static::images(Gallery::PHOTOS, 10240, 1920)
                    ->multiple()
                    ->reorderable()
                    ->panelLayout('grid')),
                static::mediaSection('floor_plans', 'heroicon-o-square-3-stack-3d', static::images(Gallery::FLOOR_PLANS, 10240, 3000)
                    ->multiple()
                    ->reorderable()
                    ->panelLayout('grid')),
                static::mediaSection('attachments', 'heroicon-o-paper-clip', SpatieMediaLibraryFileUpload::make('attachments')
                    ->collection(Gallery::ATTACHMENTS)
                    ->multiple()
                    ->reorderable()
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->maxSize(20480)
                    ->downloadable()
                    ->helperText(__('admin.galleries.attachments_help'))),
                static::mediaSection('video_url', 'heroicon-o-video-camera', TextInput::make('video_url')
                    ->url()
                    ->maxLength(500)
                    ->rule(new VideoUrl)
                    ->placeholder('https://www.youtube.com/watch?v=…')
                    ->helperText(__('admin.galleries.video_url_help'))),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableHelper::emptyState($table, static::class, 'heroicon-o-photo')
            ->striped()
            ->columns([
                TableHelper::indexColumn(),
                SpatieMediaLibraryImageColumn::make('featured_image')
                    ->label(__('admin.galleries.featured_image'))
                    ->collection(Gallery::FEATURED_IMAGE)
                    ->conversion('thumb')
                    ->imageSize(48)
                    ->square()
                    ->alignment('center'),
                TextColumn::make(getLocalizedFieldName('name'))
                    ->label(__('admin.galleries.name'))
                    ->state(fn (Gallery $record) => $record->getLocalized('name'))
                    ->searchable(['name_en', 'name_ar'])
                    ->sortable(['name_en'])
                    ->weight('medium')
                    ->url(fn (Gallery $record) => static::getUrl('view', ['record' => $record]))
                    ->color('primary'),
                TextColumn::make('project.'.getLocalizedFieldName('title'))
                    ->label(__('admin.galleries.project'))
                    ->placeholder(__('admin.galleries.no_project'))
                    ->url(fn (Gallery $record) => $record->project_id && ProjectResource::canViewAny() ? ProjectResource::getUrl('view', ['record' => $record->project_id]) : null),
                TableHelper::createdAtColumn(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->filters([
                TableHelper::dateRangeFilter('created_at', 'created_from', 'created_until'),
                SelectFilter::make('project_id')
                    ->label(__('admin.galleries.project'))
                    ->relationship('project', getLocalizedFieldName('title'))
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions(TableHelper::recordActions(
                fn (Gallery $record) => __('admin.galleries.notifications.deleted', ['name' => $record->getLocalized('name')]),
            ))
            ->recordActionsColumnLabel(__('admin.table.actions'))
            ->recordActionsAlignment('center')
            ->toolbarActions(TableHelper::standardBulkActions());
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
            'index' => Pages\ListGalleries::route('/'),
            'create' => Pages\CreateGallery::route('/create'),
            'view' => Pages\ViewGallery::route('/{record}'),
            'edit' => Pages\EditGallery::route('/{record}/edit'),
        ];
    }

    private static function mediaSection(string $name, string $icon, mixed $field): Section
    {
        return Section::make(__("admin.galleries.{$name}"))
            ->icon($icon)
            ->columnSpanFull()
            ->collapsible()
            ->schema([$field->hiddenLabel()->label(__("admin.galleries.{$name}"))]);
    }

    /**
     * JPG/PNG/WebP. With $resizeTo, big images are scaled down in the browser before upload (360° images keep their
     * full size: the panorama viewer needs it).
     */
    private static function images(string $collection, int $maxKb, ?int $resizeTo = null): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make($collection)
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
}
