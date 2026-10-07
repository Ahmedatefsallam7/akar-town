<?php

namespace Modules\Careers\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Forms\SeoFields;
use App\Filament\Helpers\TableHelper;
use App\Filament\Schemas\Tab;
use App\Filament\Traits\HasResourceTranslations;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Careers\Filament\Resources\CareerResource\Pages;
use Modules\Careers\Models\Career;
use Modules\Lookups\Filament\Forms\LocationFields;
use Modules\Lookups\Filament\Forms\LookupFields;

/**
 * Job openings for the website's careers page.
 */
class CareerResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    private const MAX_SALARY = 100_000_000;

    protected static ?string $permissionGroup = 'inquiries_crm';

    protected static ?string $model = Career::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title_en';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.inquiries_crm');
    }

    public static function getRecordTitleAttribute(): string
    {
        return getLocalizedFieldName('title');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title_en', 'title_ar'];
    }

    public static function getModelLabel(): string
    {
        return __('admin.careers.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.careers.plural');
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
                                        SeoFields::slugFrom(TextInput::make('title_en')
                                            ->label(__('admin.careers.title_en'))
                                            ->required()
                                            ->maxLength(191), Career::class),
                                        TextInput::make('title_ar')
                                            ->label(__('admin.careers.title_ar'))
                                            ->required()
                                            ->maxLength(191),
                                        Select::make('type')
                                            ->label(__('admin.careers.type'))
                                            ->options(Career::typeOptions())
                                            ->required()
                                            ->native(false),
                                        TextInput::make('vacancies')
                                            ->label(__('admin.careers.vacancies'))
                                            ->required()
                                            ->integer()
                                            ->minValue(1)
                                            ->maxValue(1000),
                                        LookupFields::select('department_id', 'department')
                                            ->label(__('admin.careers.department')),
                                        DatePicker::make('closing_date')
                                            ->label(__('admin.careers.closing_date'))
                                            ->helperText(__('admin.careers.closing_date_help'))
                                            ->rule(static::notInThePast()),
                                        Toggle::make('is_active')
                                            ->label(__('admin.careers.published'))
                                            ->helperText(__('admin.careers.published_help'))
                                            ->default(true),
                                    ])
                                    ->columns(2),
                                Section::make(__('admin.careers.location'))
                                    ->schema(LocationFields::make())
                                    ->columns(2),
                            ]),
                        Tab::make(__('admin.tabs.description'))
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                static::richEditor('description_en', __('admin.table.description_en')),
                                static::richEditor('description_ar', __('admin.table.description_ar')),
                                static::bulletList('responsibilities', 'responsibility'),
                                static::bulletList('requirements', 'requirement'),
                                Section::make(__('admin.careers.benefits'))
                                    ->schema([LookupFields::checkboxList('benefits', 'benefits')->label(__('admin.careers.benefits'))->hiddenLabel()])
                                    ->compact(),
                            ]),
                        Tab::make(__('admin.tabs.salary'))
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Toggle::make('show_salary')
                                    ->label(__('admin.careers.show_salary'))
                                    ->helperText(__('admin.careers.show_salary_help'))
                                    ->columnSpanFull(),
                                TextInput::make('salary_from')
                                    ->label(__('admin.careers.salary_from'))
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(self::MAX_SALARY),
                                TextInput::make('salary_to')
                                    ->label(__('admin.careers.salary_to'))
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(self::MAX_SALARY)
                                    ->rule(static::notBelow('salary_from')),
                                Select::make('salary_currency')
                                    ->label(__('admin.careers.salary_currency'))
                                    ->options(Career::currencyOptions())
                                    // A salary needs its currency.
                                    ->requiredWith('salary_from,salary_to')
                                    ->native(false),
                            ])
                            ->columns(3),
                        SeoFields::tab([SeoFields::slug()]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableHelper::emptyState($table, static::class, 'heroicon-o-briefcase')
            ->striped()
            ->columns([
                TableHelper::indexColumn(),
                TextColumn::make(getLocalizedFieldName('title'))
                    ->label(__('admin.table.title'))
                    ->searchable(['title_en', 'title_ar'])
                    ->sortable()
                    ->weight('medium')
                    ->url(fn (Career $record) => static::getUrl('view', ['record' => $record]))
                    ->color('primary')
                    // Long titles wrap instead of widening the table.
                    ->wrap(),
                TextColumn::make('type')
                    ->label(__('admin.careers.type'))
                    ->formatStateUsing(fn (string $state) => Career::typeOptions()[$state] ?? $state)
                    ->badge()
                    ->color('gray')
                    ->alignment('center'),
                TextColumn::make('vacancies')
                    ->label(__('admin.careers.vacancies'))
                    ->sortable()
                    ->alignment('center'),
                IconColumn::make('is_active')
                    ->label(__('admin.careers.published'))
                    ->boolean()
                    ->sortable()
                    ->alignment('center'),
                // Published but past its closing date = hidden from the website.
                IconColumn::make('open')
                    ->label(__('admin.careers.on_website'))
                    ->state(fn (Career $record) => $record->isOpen())
                    ->boolean()
                    ->alignment('center'),
                TextColumn::make('closing_date')
                    ->label(__('admin.careers.closing_date'))
                    ->date()
                    ->placeholder('—')
                    ->sortable()
                    ->color(fn (Career $record) => $record->closing_date?->lt(today()) ? 'danger' : null)
                    ->toggleable(),
                TableHelper::createdAtColumn(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('admin.careers.type'))
                    ->options(Career::typeOptions()),
                QueryBuilder::make()
                    ->constraints([
                        TextConstraint::make('title_en')->label(__('admin.careers.title_en')),
                        TextConstraint::make('title_ar')->label(__('admin.careers.title_ar')),
                        NumberConstraint::make('vacancies')->label(__('admin.careers.vacancies'))->integer(),
                        DateConstraint::make('closing_date')->label(__('admin.careers.closing_date')),
                    ]),
                TableHelper::dateRangeFilter('created_at', 'created_from', 'created_until'),
                TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions(TableHelper::recordActions(
                fn (Career $record) => __('admin.careers.notifications.deleted', ['name' => $record->getLocalized('title')]),
            ))
            ->recordActionsColumnLabel(__('admin.table.actions'))
            ->recordActionsAlignment('center')
            ->toolbarActions(TableHelper::standardBulkActions([
                TableHelper::statusBulkAction(static::class, 'activate', 'heroicon-o-check-circle', true),
                TableHelper::statusBulkAction(static::class, 'deactivate', 'heroicon-o-x-circle', false),
            ]));
    }

    /**
     * A bullet list (responsibilities, requirements): each item in English and Arabic, both required.
     */
    private static function bulletList(string $name, string $item): Repeater
    {
        return Repeater::make($name)
            ->label(__("admin.careers.{$name}"))
            ->schema([
                TextInput::make('en')->label(__("admin.careers.{$item}_en"))->required()->maxLength(500),
                TextInput::make('ar')->label(__("admin.careers.{$item}_ar"))->required()->maxLength(500),
            ])
            ->columns(2)
            ->defaultItems(0)
            ->addActionLabel(__('admin.careers.add_item'))
            ->reorderableWithButtons()
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => $state[app()->getLocale() === 'ar' ? 'ar' : 'en'] ?? $state['en'] ?? null)
            ->columnSpanFull();
    }

    private static function richEditor(string $name, string $label): RichEditor
    {
        return RichEditor::make($name)
            ->label($label)
            ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'undo', 'redo'])
            ->columnSpanFull();
    }

    /**
     * A new closing date can't be in the past (the job would close the moment it's saved). An expired job can still
     * be edited without changing its date.
     */
    private static function notInThePast(): Closure
    {
        return fn (?Career $record) => function (string $attribute, mixed $value, Closure $fail) use ($record) {
            if (blank($value) || $record?->closing_date?->toDateString() === Carbon::parse($value)->toDateString()) {
                return;
            }

            if (Carbon::parse($value)->lt(today())) {
                $fail(__('admin.careers.closing_date_past'));
            }
        };
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
            'index' => Pages\ListCareers::route('/'),
            'create' => Pages\CreateCareer::route('/create'),
            'view' => Pages\ViewCareer::route('/{record}'),
            'edit' => Pages\EditCareer::route('/{record}/edit'),
        ];
    }
}
