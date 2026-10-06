<?php

namespace Modules\Sliders\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Helpers\TableHelper;
use App\Filament\Traits\HasResourceTranslations;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Sliders\Filament\Resources\SliderResource\Pages;
use Modules\Sliders\Filament\Resources\SliderResource\RelationManagers;
use Modules\Sliders\Models\Slider;
use Modules\Users\Enums\PermissionAction;

/**
 * The homepage blocks: which ones show, where, in what order and under what heading. The set is fixed, so there is
 * no create or delete; the hero slider's slides are managed on its View page.
 */
class SliderResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    protected static ?string $permissionGroup = 'marketing_content';

    /** @var array<PermissionAction> */
    protected static array $permissionActions = [PermissionAction::View, PermissionAction::Edit, PermissionAction::Bulk];

    protected static ?string $model = Slider::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title_en';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.marketing_content');
    }

    public static function getRecordTitleAttribute(): string
    {
        return getLocalizedFieldName('title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.sliders.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.sliders.plural');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.tabs.details'))
                    ->schema([
                        TextInput::make('title_en')
                            ->label(__('admin.sliders.title_en'))
                            ->helperText(__('admin.sliders.title_help'))
                            ->required()
                            ->maxLength(191),
                        TextInput::make('title_ar')
                            ->label(__('admin.sliders.title_ar'))
                            ->helperText(__('admin.sliders.title_help'))
                            ->required()
                            ->maxLength(191),
                        Select::make('placement')
                            ->label(__('admin.sliders.placement'))
                            ->helperText(__('admin.sliders.placement_help'))
                            ->options(Slider::placementOptions())
                            ->required()
                            ->native(false),
                        Select::make('content_type')
                            ->label(__('admin.sliders.content_type'))
                            ->helperText(__('admin.sliders.content_type_help'))
                            ->options(Slider::contentTypeOptions())
                            ->required()
                            ->native(false),
                        TextInput::make('order')
                            ->label(__('admin.sliders.order'))
                            ->helperText(__('admin.sliders.order_help'))
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(65_535),
                        Toggle::make('is_active')
                            ->label(__('admin.sliders.active'))
                            ->helperText(__('admin.sliders.active_help'))
                            ->default(true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TableHelper::indexColumn(),
                TextColumn::make(getLocalizedFieldName('title'))
                    ->label(__('admin.table.title'))
                    ->searchable(['title_en', 'title_ar'])
                    ->sortable()
                    ->weight('medium')
                    ->url(fn (Slider $record) => static::getUrl('view', ['record' => $record]))
                    ->color('primary'),
                TextColumn::make('placement')
                    ->label(__('admin.sliders.placement'))
                    ->formatStateUsing(fn (string $state) => Slider::placementOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Slider::HOME_HERO => 'primary',
                        Slider::HOME_BODY => 'info',
                        default => 'gray',
                    })
                    ->sortable()
                    ->alignment('center'),
                TextColumn::make('content_type')
                    ->label(__('admin.sliders.content_type'))
                    ->formatStateUsing(fn (string $state) => Slider::contentTypeOptions()[$state] ?? $state)
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->alignment('center'),
                TextColumn::make('order')
                    ->label(__('admin.sliders.order'))
                    ->sortable()
                    ->alignment('center'),
                IconColumn::make('is_active')
                    ->label(__('admin.sliders.active'))
                    ->boolean()
                    ->sortable()
                    ->alignment('center'),
                TableHelper::createdAtColumn()->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->reorderRecordsTriggerAction(fn (Action $action, bool $isReordering) => $action
                ->button()
                ->label($isReordering ? __('admin.sliders.reorder_done') : __('admin.sliders.reorder'))
                ->icon($isReordering ? 'heroicon-o-check' : 'heroicon-o-arrows-up-down'))
            ->paginated(false)
            ->emptyStateIcon('heroicon-o-rectangle-stack')
            ->emptyStateHeading(__('admin.table.empty_heading', ['name' => static::getPluralModelLabel()]))
            ->filters([
                TableHelper::dateRangeFilter('created_at', 'created_from', 'created_until'),
                SelectFilter::make('placement')
                    ->label(__('admin.sliders.placement'))
                    ->options(Slider::placementOptions()),
                SelectFilter::make('content_type')
                    ->label(__('admin.sliders.content_type'))
                    ->options(Slider::contentTypeOptions()),
                TableHelper::yesNoFilter('is_active', __('admin.sliders.active')),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions([
                ViewAction::make()->label(__('admin.actions.view'))->iconButton()->tooltip(__('admin.actions.view')),
                EditAction::make()->label(__('admin.actions.edit'))->iconButton()->tooltip(__('admin.actions.edit')),
            ])
            ->recordActionsColumnLabel(__('admin.table.actions'))
            ->recordActionsAlignment('center')
            ->toolbarActions([
                BulkActionGroup::make([
                    TableHelper::statusBulkAction(static::class, 'activate', 'heroicon-o-check-circle', true),
                    TableHelper::statusBulkAction(static::class, 'deactivate', 'heroicon-o-x-circle', false),
                ]),
            ]);
    }

    /**
     * The hero slider's slides, on its View page.
     */
    public static function getRelations(): array
    {
        return [
            RelationManagers\SlidesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSliders::route('/'),
            'view' => Pages\ViewSlider::route('/{record}'),
            'edit' => Pages\EditSlider::route('/{record}/edit'),
        ];
    }
}
