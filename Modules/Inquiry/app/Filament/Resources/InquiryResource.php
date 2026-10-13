<?php

namespace Modules\Inquiry\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Helpers\CrmHelper;
use App\Filament\Helpers\TableHelper;
use App\Filament\Support\PrefixSearch;
use App\Filament\Traits\HasResourceTranslations;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Inquiry\Filament\Resources\InquiryResource\Pages;
use Modules\Inquiry\Models\Inquiry;

/**
 * "Contact Us": the inbox of the website's contact forms, so the sales team follows up on every general inquiry.
 */
class InquiryResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    protected static ?string $transKey = 'contact_us';

    protected static ?string $permissionGroup = 'inquiries_crm';

    protected static ?string $model = Inquiry::class;

    protected static ?string $slug = 'contact-us';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.inquiries_crm');
    }

    public static function getModelLabel(): string
    {
        return __('admin.contact_us.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.contact_us.plural');
    }

    /** Searched by how they start (see PrefixSearch): fast with many messages. */
    public const SEARCH_COLUMNS = ['full_name', 'email', 'phone'];

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
                Section::make(__('admin.contact_us.section_message'))
                    ->schema([
                        TextInput::make('full_name')
                            ->label(__('admin.contact_us.full_name'))
                            ->required()
                            ->maxLength(191),
                        TextInput::make('email')
                            ->label(__('admin.contact_us.email'))
                            ->email()
                            ->maxLength(191),
                        TextInput::make('phone')
                            ->label(__('admin.contact_us.phone'))
                            ->tel()
                            ->required()
                            ->maxLength(30),
                        Textarea::make('message')
                            ->label(__('admin.contact_us.message'))
                            ->required()
                            ->rows(5)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('admin.contact_us.section_source'))
                    ->schema([
                        TextInput::make('source')
                            ->label(__('admin.contact_us.source'))
                            ->helperText(__('admin.contact_us.source_help'))
                            ->maxLength(255),
                        TextInput::make('url')
                            ->label(__('admin.contact_us.url'))
                            ->helperText(__('admin.contact_us.url_help'))
                            ->url()
                            ->rules(['url:http,https'])
                            ->maxLength(2048),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableHelper::emptyState($table, static::class, 'heroicon-o-envelope')
            ->striped()
            // New messages show up without reloading (as in Dastan).
            ->poll('60s')
            // "Sara Ahmed" is one name, not two separate searches.
            ->splitSearchTerms(false)
            ->columns([
                TableHelper::indexColumn(),
                TextColumn::make('full_name')
                    ->label(__('admin.contact_us.full_name'))
                    // One search box for name, email and phone, by how they start ("Sara", "sara@", "+2010").
                    ->searchable(query: PrefixSearch::using(static::SEARCH_COLUMNS))
                    ->sortable()
                    ->weight('medium')
                    ->url(fn (Inquiry $record) => static::getUrl('view', ['record' => $record]))
                    ->color('primary'),
                TextColumn::make('email')
                    ->label(__('admin.contact_us.email'))
                    ->placeholder('—'),
                TextColumn::make('phone')
                    ->label(__('admin.contact_us.phone'))
                    // Keeps "+20…" the right way round in Arabic.
                    ->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('message')
                    ->label(__('admin.contact_us.message'))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('form')
                    ->label(__('admin.contact_us.form'))
                    ->formatStateUsing(fn (string $state) => __("admin.contact_us.forms.{$state}"))
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('source')
                    ->label(__('admin.contact_us.source'))
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('url')
                    ->label(__('admin.contact_us.url'))
                    ->url(fn (Inquiry $record) => $record->url, shouldOpenInNewTab: true)
                    ->limit(40)
                    ->color('primary')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                CrmHelper::sentColumn(),
                TableHelper::createdAtColumn(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->filters([
                QueryBuilder::make()
                    ->constraints([
                        TextConstraint::make('full_name')->label(__('admin.contact_us.full_name')),
                        TextConstraint::make('email')->label(__('admin.contact_us.email')),
                        TextConstraint::make('phone')->label(__('admin.contact_us.phone')),
                        TextConstraint::make('message')->label(__('admin.contact_us.message')),
                    ]),
                TableHelper::dateRangeFilter('created_at', 'created_from', 'created_until'),
                CrmHelper::sentFilter(),
                TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions(TableHelper::recordActions(
                fn (Inquiry $record) => __('admin.contact_us.notifications.deleted', ['name' => $record->full_name]),
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
            'index' => Pages\ListInquiries::route('/'),
            'create' => Pages\CreateInquiry::route('/create'),
            'view' => Pages\ViewInquiry::route('/{record}'),
            'edit' => Pages\EditInquiry::route('/{record}/edit'),
        ];
    }
}
