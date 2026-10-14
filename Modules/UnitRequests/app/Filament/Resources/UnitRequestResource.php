<?php

namespace Modules\UnitRequests\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Forms\MapPicker;
use App\Filament\Helpers\CrmHelper;
use App\Filament\Helpers\TableHelper;
use App\Filament\Schemas\Tab;
use App\Filament\Support\PrefixSearch;
use App\Filament\Traits\HasResourceTranslations;
use App\Support\Crm\LeadContact;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Modules\Lookups\Filament\Forms\LocationFields;
use Modules\Lookups\Filament\Forms\LookupFields;
use Modules\UnitRequests\Actions\ConvertUnitRequestToUnit;
use Modules\UnitRequests\Filament\Resources\UnitRequestResource\Pages;
use Modules\UnitRequests\Models\UnitRequest;
use Modules\Units\Filament\Resources\UnitResource;

/**
 * "Sell Property Requests": units owners submit for sale on the website. Staff review them, contact the owner and
 * may turn one into a property.
 */
class UnitRequestResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    private const MAX_PRICE = 1_000_000_000_000;

    private const MAX_AREA = 10_000_000;

    /** Searched by how they start (see PrefixSearch): fast with many requests. */
    public const SEARCH_COLUMNS = ['seller_name', 'name_en', 'phone', 'email'];

    protected static ?string $permissionGroup = 'inquiries_crm';

    protected static ?string $model = UnitRequest::class;

    protected static ?string $slug = 'sell-property-requests';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.inquiries_crm');
    }

    public static function getRecordTitleAttribute(): string
    {
        return getLocalizedFieldName('name');
    }

    public static function getModelLabel(): string
    {
        return __('admin.unit_requests.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.unit_requests.plural');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return static::SEARCH_COLUMNS;
    }

    protected static function applyGlobalSearchAttributeConstraints(Builder $query, string $search): void
    {
        PrefixSearch::apply($query, $search, static::SEARCH_COLUMNS);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make(__('admin.tabs.contact'))
                            ->icon('heroicon-o-user')
                            ->schema([
                                TextInput::make('seller_name')
                                    ->label(__('admin.unit_requests.seller_name'))
                                    ->required()
                                    ->maxLength(191),
                                TextInput::make('email')
                                    ->label(__('admin.unit_requests.email'))
                                    ->email()
                                    ->rules(LeadContact::emailRules())
                                    ->maxLength(191),
                                TextInput::make('phone')
                                    ->label(__('admin.unit_requests.phone'))
                                    ->tel()
                                    ->rules(LeadContact::phoneRules())
                                    ->required()
                                    ->maxLength(30),
                            ])
                            ->columns(3),
                        Tab::make(__('admin.tabs.general'))
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                TextInput::make('name_en')
                                    ->label(__('admin.unit_requests.name_en'))
                                    ->helperText(__('admin.unit_requests.name_help'))
                                    ->required()
                                    ->maxLength(191),
                                TextInput::make('name_ar')
                                    ->label(__('admin.unit_requests.name_ar'))
                                    ->maxLength(191),
                                ...LocationFields::make(),
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
                            ])
                            ->columns(2),
                        Tab::make(__('admin.tabs.pricing_space'))
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                TextInput::make('price')
                                    ->label(__('admin.units.price'))
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(self::MAX_PRICE)
                                    ->suffix('EGP'),
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
                            ])
                            ->columns(2),
                        Tab::make(__('admin.tabs.associations'))
                            ->icon('heroicon-o-squares-2x2')
                            ->schema(collect(UnitRequest::LOOKUPS)
                                ->map(fn (string $type, string $field) => Section::make(__("admin.unit_requests.{$field}"))
                                    ->schema([LookupFields::checkboxList($field, $type)->label(__("admin.unit_requests.{$field}"))->hiddenLabel()])
                                    ->collapsible()
                                    ->compact())
                                ->values()
                                ->all()),
                        Tab::make(__('admin.tabs.description'))
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Textarea::make('short_description_en')
                                    ->label(__('admin.units.short_description_en'))
                                    ->rows(3)
                                    ->maxLength(5000),
                                Textarea::make('short_description_ar')
                                    ->label(__('admin.units.short_description_ar'))
                                    ->rows(3)
                                    ->maxLength(5000),
                                static::richEditor('description_en', __('admin.units.description_en')),
                                static::richEditor('description_ar', __('admin.units.description_ar')),
                            ])
                            ->columns(2),
                        Tab::make(__('admin.tabs.media'))
                            ->icon('heroicon-o-photo')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('featured_image')
                                    ->label(__('admin.table.featured_image'))
                                    ->collection(UnitRequest::FEATURED_IMAGE)
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->helperText(__('admin.galleries.image_help', ['size' => 5]))
                                    ->imageResizeMode('contain')
                                    ->imageResizeTargetWidth('1920')
                                    ->imageResizeTargetHeight('1920')
                                    ->imageResizeUpscale(false),
                            ]),
                        Tab::make(__('admin.tabs.admin'))
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                Select::make('status')
                                    ->label(__('admin.unit_requests.status'))
                                    ->options(UnitRequest::statusOptions())
                                    ->default(UnitRequest::PENDING)
                                    ->required()
                                    ->native(false),
                                Textarea::make('internal_notes')
                                    ->label(__('admin.unit_requests.notes'))
                                    ->helperText(__('admin.unit_requests.notes_help'))
                                    ->rows(4)
                                    ->maxLength(5000)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableHelper::emptyState($table, static::class, 'heroicon-o-inbox-arrow-down')
            ->striped()
            ->splitSearchTerms(false)
            ->columns([
                TableHelper::indexColumn(),
                SpatieMediaLibraryImageColumn::make('featured_image')
                    ->label(__('admin.table.featured_image'))
                    ->collection(UnitRequest::FEATURED_IMAGE)
                    ->conversion('thumb')
                    ->imageSize(40)
                    ->square()
                    ->toggleable(),
                TextColumn::make(getLocalizedFieldName('name'))
                    ->label(__('admin.unit_requests.name'))
                    // One search box for the seller, the unit name, phone and email, by how they start.
                    ->searchable(query: PrefixSearch::using(static::SEARCH_COLUMNS))
                    ->state(fn (UnitRequest $record) => $record->getLocalized('name'))
                    ->weight('medium')
                    ->url(fn (UnitRequest $record) => static::getUrl('view', ['record' => $record]))
                    ->color('primary'),
                TextColumn::make('seller_name')
                    ->label(__('admin.unit_requests.seller_name'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label(__('admin.unit_requests.phone'))
                    ->extraAttributes(['dir' => 'ltr'])
                    ->toggleable(),
                TextColumn::make('email')
                    ->label(__('admin.unit_requests.email'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('price')
                    ->label(__('admin.units.price'))
                    ->formatStateUsing(fn (?int $state) => $state === null ? null : number_format($state).' EGP')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('admin.unit_requests.status'))
                    ->formatStateUsing(fn (string $state) => UnitRequest::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => UnitRequest::statusColor($state))
                    ->sortable()
                    ->alignment('center'),
                CrmHelper::sentColumn(),
                IconColumn::make('unit_id')
                    ->label(__('admin.unit_requests.converted'))
                    ->state(fn (UnitRequest $record) => $record->isConverted())
                    ->boolean()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('area')
                    ->label(__('admin.units.area'))
                    ->numeric()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bedrooms')
                    ->label(__('admin.units.bedrooms'))
                    ->placeholder('—')
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bathrooms')
                    ->label(__('admin.units.bathrooms'))
                    ->placeholder('—')
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                TableHelper::createdAtColumn(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.unit_requests.status'))
                    ->options(UnitRequest::statusOptions())
                    ->multiple(),
                QueryBuilder::make()
                    ->constraints([
                        TextConstraint::make('name_en')->label(__('admin.unit_requests.name')),
                        TextConstraint::make('seller_name')->label(__('admin.unit_requests.seller_name')),
                        TextConstraint::make('phone')->label(__('admin.unit_requests.phone')),
                        TextConstraint::make('email')->label(__('admin.unit_requests.email')),
                        NumberConstraint::make('price')->label(__('admin.units.price'))->integer(),
                    ]),
                TableHelper::dateRangeFilter('created_at', 'created_from', 'created_until'),
                CrmHelper::sentFilter(),
                TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions([
                static::convertAction()->iconButton()->tooltip(__('admin.unit_requests.convert_to_unit')),
                ...TableHelper::recordActions(
                    fn (UnitRequest $record) => __('admin.unit_requests.notifications.deleted', ['name' => $record->getLocalized('name')]),
                ),
            ])
            ->recordActionsColumnLabel(__('admin.table.actions'))
            ->recordActionsAlignment('center')
            ->toolbarActions(TableHelper::standardBulkActions([
                static::markAsBulkAction(UnitRequest::REVIEWED, 'heroicon-o-eye'),
                static::markAsBulkAction(UnitRequest::CONTACTED, 'heroicon-o-phone'),
            ]));
    }

    /**
     * Turns the request into a property in one click (see ConvertUnitRequestToUnit) and opens it to finish.
     * Needs edit here and create on Properties; only for a request not converted yet and not cancelled.
     */
    public static function convertAction(): Action
    {
        return Action::make('convertToUnit')
            ->label(__('admin.unit_requests.convert_to_unit'))
            ->icon('heroicon-o-arrow-right-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription(__('admin.unit_requests.convert_confirm'))
            ->visible(fn (UnitRequest $record) => $record->canBeConverted() && static::canEdit($record) && UnitResource::canCreate())
            ->action(function (UnitRequest $record, Action $action) {
                $unit = app(ConvertUnitRequestToUnit::class)($record);

                Notification::make()->success()->title(__('admin.unit_requests.convert_success'))->send();

                $action->redirect(UnitResource::getUrl('edit', ['record' => $unit]));
            });
    }

    /**
     * Sets the status of the selected requests that can move to it (a closed or cancelled one is left as is).
     * Needs edit + bulk; requests you can't edit are skipped.
     */
    protected static function markAsBulkAction(string $status, string $icon): BulkAction
    {
        return BulkAction::make("mark_{$status}")
            ->label(__("admin.unit_requests.mark_{$status}"))
            ->icon($icon)
            ->requiresConfirmation()
            ->authorize(fn (): bool => static::can('updateAny'))
            ->action(fn (Collection $records) => $records
                ->filter(fn (UnitRequest $record) => in_array($record->status, UnitRequest::statusesBefore($status), true) && static::canEdit($record))
                ->each->update(['status' => $status]))
            ->successNotificationTitle(__('admin.notifications.updated'))
            ->deselectRecordsAfterCompletion();
    }

    private static function richEditor(string $name, string $label): RichEditor
    {
        return RichEditor::make($name)
            ->label($label)
            ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'undo', 'redo'])
            ->columnSpanFull();
    }

    /**
     * "To" can't be lower than "From".
     */
    private static function notBelow(string $fromField): Closure
    {
        return fn (Get $get) => function (string $attribute, mixed $value, Closure $fail) use ($get, $fromField) {
            if (filled($value) && filled($get($fromField)) && (float) $value < (float) $get($fromField)) {
                $fail(__('admin.projects.range_invalid'));
            }
        };
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
            'index' => Pages\ListUnitRequests::route('/'),
            'create' => Pages\CreateUnitRequest::route('/create'),
            'view' => Pages\ViewUnitRequest::route('/{record}'),
            'edit' => Pages\EditUnitRequest::route('/{record}/edit'),
        ];
    }
}
