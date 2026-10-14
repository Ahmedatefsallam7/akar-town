<?php

namespace Modules\Inquiry\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Helpers\CrmHelper;
use App\Filament\Helpers\TableHelper;
use App\Filament\Support\PrefixSearch;
use App\Filament\Traits\HasResourceTranslations;
use App\Support\Crm\LeadContact;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Modules\Inquiry\Filament\Resources\MeetingResource\Pages;
use Modules\Inquiry\Models\Meeting;

/**
 * "Meetings & Viewings": the agents' appointment book, from booking request to completion.
 */
class MeetingResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    protected static ?string $permissionGroup = 'inquiries_crm';

    protected static ?string $model = Meeting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    /** Searched by how they start (see PrefixSearch): fast with many bookings. */
    public const SEARCH_COLUMNS = ['name', 'email', 'phone'];

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.inquiries_crm');
    }

    public static function getModelLabel(): string
    {
        return __('admin.meetings.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.meetings.title');
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
                Section::make(__('admin.tabs.contact'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.meetings.name'))
                            ->required()
                            ->maxLength(191),
                        TextInput::make('phone')
                            ->label(__('admin.meetings.phone'))
                            ->tel()
                            ->rules(LeadContact::phoneRules())
                            ->required()
                            ->maxLength(30),
                        TextInput::make('email')
                            ->label(__('admin.meetings.email'))
                            ->email()
                            ->rules(LeadContact::emailRules())
                            ->maxLength(191),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make(__('admin.tabs.details'))
                    ->schema([
                        DateTimePicker::make('meeting_at')
                            ->label(__('admin.meetings.meeting_at'))
                            ->helperText(__('admin.meetings.meeting_at_help'))
                            ->seconds(false)
                            ->required(),
                        Select::make('type')
                            ->label(__('admin.meetings.type'))
                            ->options(Meeting::typeOptions())
                            ->required()
                            ->native(false),
                        Select::make('status')
                            ->label(__('admin.meetings.status'))
                            ->options(Meeting::statusOptions())
                            ->default(Meeting::PENDING)
                            ->required()
                            ->native(false),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make(__('admin.tabs.associations'))
                    ->schema([
                        TextInput::make('source')
                            ->label(__('admin.contact_us.source'))
                            ->helperText(__('admin.meetings.source_help'))
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
                Section::make(__('admin.tabs.description'))
                    ->schema([
                        Textarea::make('notes_en')
                            ->label(__('admin.meetings.notes_en'))
                            ->rows(3)
                            ->maxLength(5000),
                        Textarea::make('notes_ar')
                            ->label(__('admin.meetings.notes_ar'))
                            ->rows(3)
                            ->maxLength(5000),
                        Textarea::make('admin_notes_en')
                            ->label(__('admin.meetings.admin_notes_en'))
                            ->helperText(__('admin.meetings.admin_notes_help'))
                            ->rows(3)
                            ->maxLength(5000),
                        Textarea::make('admin_notes_ar')
                            ->label(__('admin.meetings.admin_notes_ar'))
                            ->helperText(__('admin.meetings.admin_notes_help'))
                            ->rows(3)
                            ->maxLength(5000),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableHelper::emptyState($table, static::class, 'heroicon-o-calendar-days')
            ->striped()
            // New bookings show up without reloading (as in Dastan).
            ->poll('30s')
            ->splitSearchTerms(false)
            ->columns([
                TableHelper::indexColumn(),
                TextColumn::make('name')
                    ->label(__('admin.meetings.name'))
                    // One search box for name, email and phone, by how they start.
                    ->searchable(query: PrefixSearch::using(static::SEARCH_COLUMNS))
                    ->sortable()
                    ->weight('medium')
                    ->url(fn (Meeting $record) => static::getUrl('view', ['record' => $record]))
                    ->color('primary'),
                TextColumn::make('phone')
                    ->label(__('admin.meetings.phone'))
                    ->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('email')
                    ->label(__('admin.meetings.email'))
                    ->placeholder('—'),
                TextColumn::make('meeting_at')
                    ->label(__('admin.meetings.meeting_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('admin.meetings.type'))
                    ->formatStateUsing(fn (string $state) => Meeting::typeOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'in_person' => 'primary',
                        'online' => 'info',
                        'site_visit' => 'success',
                        default => 'gray',
                    })
                    ->alignment('center'),
                TextColumn::make('status')
                    ->label(__('admin.meetings.status'))
                    ->formatStateUsing(fn (string $state) => Meeting::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => Meeting::statusColor($state))
                    ->sortable()
                    ->alignment('center'),
                TextColumn::make('source')
                    ->label(__('admin.contact_us.source'))
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('url')
                    ->label(__('admin.contact_us.url'))
                    ->url(fn (Meeting $record) => $record->url, shouldOpenInNewTab: true)
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
                SelectFilter::make('status')
                    ->label(__('admin.meetings.status'))
                    ->options(Meeting::statusOptions())
                    ->multiple(),
                SelectFilter::make('type')
                    ->label(__('admin.meetings.type'))
                    ->options(Meeting::typeOptions())
                    ->multiple(),
                TableHelper::dateRangeFilter('meeting_at', 'meeting_from', 'meeting_until'),
                TableHelper::dateRangeFilter('created_at', 'created_from', 'created_until'),
                CrmHelper::sentFilter(),
                TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions(TableHelper::recordActions(
                fn (Meeting $record) => __('admin.meetings.notifications.deleted', ['name' => $record->name]),
            ))
            ->recordActionsColumnLabel(__('admin.table.actions'))
            ->recordActionsAlignment('center')
            ->toolbarActions(TableHelper::standardBulkActions([
                static::markAsBulkAction(Meeting::CONFIRMED, 'heroicon-o-check-circle'),
                static::markAsBulkAction(Meeting::COMPLETED, 'heroicon-o-check-badge'),
            ]));
    }

    /**
     * Sets the status of the selected bookings that can move to it (a cancelled or completed one is left as is).
     * Needs edit + bulk; bookings you can't edit are skipped.
     */
    protected static function markAsBulkAction(string $status, string $icon): BulkAction
    {
        return BulkAction::make("mark_{$status}")
            ->label(__("admin.meetings.mark_{$status}"))
            ->icon($icon)
            ->requiresConfirmation()
            ->authorize(fn (): bool => static::can('updateAny'))
            ->action(fn (Collection $records) => $records
                ->filter(fn (Meeting $record) => in_array($record->status, Meeting::statusesBefore($status), true) && static::canEdit($record))
                ->each->update(['status' => $status]))
            ->successNotificationTitle(__('admin.notifications.updated'))
            ->deselectRecordsAfterCompletion();
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
            'index' => Pages\ListMeetings::route('/'),
            'create' => Pages\CreateMeeting::route('/create'),
            'view' => Pages\ViewMeeting::route('/{record}'),
            'edit' => Pages\EditMeeting::route('/{record}/edit'),
        ];
    }
}
