<?php

namespace Modules\WebsiteUsers\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Helpers\TableHelper;
use App\Filament\Support\PrefixSearch;
use App\Filament\Traits\HasResourceTranslations;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Modules\Users\Enums\PermissionAction;
use Modules\WebsiteUsers\Filament\Resources\WebsiteUserResource\Pages;
use Modules\WebsiteUsers\Models\WebsiteUser;

/**
 * "Website Users": people who registered on the public website. Separate from admin users; there is no create
 * (they register themselves) and no password field (admins never see or set it).
 */
class WebsiteUserResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    /** Searched by how they start (see PrefixSearch): fast with many users. */
    public const SEARCH_COLUMNS = ['name', 'email', 'phone'];

    protected static ?string $permissionGroup = 'inquiries_crm';

    /** @var array<PermissionAction> */
    protected static array $permissionActions = [PermissionAction::View, PermissionAction::Edit, PermissionAction::Delete, PermissionAction::Bulk];

    protected static ?string $model = WebsiteUser::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 0;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.inquiries_crm');
    }

    public static function getModelLabel(): string
    {
        return __('admin.website_users.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.website_users.plural');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return static::SEARCH_COLUMNS;
    }

    protected static function applyGlobalSearchAttributeConstraints(Builder $query, string $search): void
    {
        PrefixSearch::apply($query, $search, static::SEARCH_COLUMNS);
    }

    /**
     * Admins correct the name, phone, language, status and notes. The e-mail is the user's login, so it's only shown.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.website_users.section_account'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.website_users.name'))
                            ->required()
                            ->maxLength(191),
                        TextInput::make('email')
                            ->label(__('admin.website_users.email'))
                            ->helperText(__('admin.website_users.email_help'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('phone')
                            ->label(__('admin.website_users.phone'))
                            ->tel()
                            ->maxLength(30)
                            ->unique(ignoreRecord: true),
                        Select::make('preferred_language')
                            ->label(__('admin.website_users.preferred_language'))
                            ->options(WebsiteUser::options('languages', WebsiteUser::LANGUAGES))
                            ->required()
                            ->native(false),
                        Select::make('status')
                            ->label(__('admin.website_users.status'))
                            ->helperText(__('admin.website_users.status_help'))
                            ->options(WebsiteUser::options('statuses', WebsiteUser::STATUSES))
                            ->required()
                            ->native(false),
                        Textarea::make('admin_notes')
                            ->label(__('admin.website_users.admin_notes'))
                            ->helperText(__('admin.website_users.admin_notes_help'))
                            ->rows(4)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.website_users.section_account'))
                    ->schema([
                        ImageEntry::make('avatar')
                            ->label(__('admin.website_users.avatar'))
                            ->disk(WebsiteUser::AVATAR_DISK)
                            ->circular()
                            ->imageSize(64)
                            ->placeholder('—'),
                        TextEntry::make('name')->label(__('admin.website_users.name')),
                        TextEntry::make('email')->label(__('admin.website_users.email'))->placeholder('—')->copyable(),
                        TextEntry::make('phone')->label(__('admin.website_users.phone'))->placeholder('—')->copyable()->extraAttributes(['dir' => 'ltr']),
                        TextEntry::make('registration_method')
                            ->label(__('admin.website_users.registration_method'))
                            ->formatStateUsing(fn (string $state) => WebsiteUser::options('registration_methods', WebsiteUser::REGISTRATION_METHODS)[$state] ?? $state)
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('preferred_language')
                            ->label(__('admin.website_users.preferred_language'))
                            ->formatStateUsing(fn (string $state) => WebsiteUser::options('languages', WebsiteUser::LANGUAGES)[$state] ?? $state),
                        TextEntry::make('email_verified_at')
                            ->label(__('admin.website_users.email_verified'))
                            ->dateTime()
                            ->placeholder(__('admin.website_users.not_verified')),
                        TextEntry::make('phone_verified_at')
                            ->label(__('admin.website_users.phone_verified'))
                            ->dateTime()
                            ->placeholder(__('admin.website_users.not_verified')),
                        TextEntry::make('status')
                            ->label(__('admin.website_users.status'))
                            ->formatStateUsing(fn (string $state) => WebsiteUser::options('statuses', WebsiteUser::STATUSES)[$state] ?? $state)
                            ->badge()
                            ->color(fn (string $state) => $state === WebsiteUser::BLOCKED ? 'danger' : 'success'),
                        TextEntry::make('created_at')->label(__('admin.website_users.registered_at'))->dateTime(),
                        TextEntry::make('last_login_at')->label(__('admin.website_users.last_login_at'))->dateTime()->placeholder('—'),
                        TextEntry::make('admin_notes')->label(__('admin.website_users.admin_notes'))->placeholder('—')->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->splitSearchTerms(false)
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading(__('admin.table.empty_heading', ['name' => static::getPluralModelLabel()]))
            ->columns([
                TableHelper::indexColumn(),
                ImageColumn::make('avatar')
                    ->label(__('admin.website_users.avatar'))
                    ->disk(WebsiteUser::AVATAR_DISK)
                    ->circular()
                    ->imageSize(36)
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('admin.website_users.name'))
                    // One search box for name, email and phone, by how they start.
                    ->searchable(query: PrefixSearch::using(static::SEARCH_COLUMNS))
                    ->sortable()
                    ->weight('medium')
                    ->url(fn (WebsiteUser $record) => static::getUrl('view', ['record' => $record]))
                    ->color('primary'),
                TextColumn::make('email')
                    ->label(__('admin.website_users.email'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label(__('admin.website_users.phone'))
                    ->extraAttributes(['dir' => 'ltr'])
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('registration_method')
                    ->label(__('admin.website_users.registration_method'))
                    ->formatStateUsing(fn (string $state) => WebsiteUser::options('registration_methods', WebsiteUser::REGISTRATION_METHODS)[$state] ?? $state)
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                IconColumn::make('verified')
                    ->label(__('admin.website_users.verified'))
                    ->state(fn (WebsiteUser $record) => $record->isVerified())
                    ->boolean()
                    ->alignment('center')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('admin.website_users.status'))
                    ->formatStateUsing(fn (string $state) => WebsiteUser::options('statuses', WebsiteUser::STATUSES)[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => $state === WebsiteUser::BLOCKED ? 'danger' : 'success')
                    ->alignment('center')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('admin.website_users.registered_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('last_login_at')
                    ->label(__('admin.website_users.last_login_at'))
                    ->since()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.website_users.status'))
                    ->options(WebsiteUser::options('statuses', WebsiteUser::STATUSES)),
                TableHelper::dateRangeFilter('created_at', 'registered_from', 'registered_until'),
                TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions([
                static::blockAction()->iconButton()->tooltip(__('admin.website_users.block')),
                static::unblockAction()->iconButton()->tooltip(__('admin.website_users.unblock')),
                ...TableHelper::recordActions(
                    fn (WebsiteUser $record) => __('admin.website_users.notifications.deleted', ['name' => $record->name]),
                ),
            ])
            ->recordActionsColumnLabel(__('admin.table.actions'))
            ->recordActionsAlignment('center')
            ->toolbarActions(TableHelper::standardBulkActions([
                static::statusBulkAction('block', 'heroicon-o-no-symbol', fn (WebsiteUser $user) => $user->block()),
                static::statusBulkAction('unblock', 'heroicon-o-check-circle', fn (WebsiteUser $user) => $user->unblock()),
            ]));
    }

    /**
     * "Website User" on a lead's View page (contact message, meeting, sell request) sent while logged in.
     */
    public static function leadLinkAction(): Action
    {
        return Action::make('websiteUser')
            ->label(__('admin.website_users.singular'))
            ->icon('heroicon-o-user-circle')
            ->color('gray')
            ->visible(fn ($record) => $record->websiteUser !== null && static::canView($record->websiteUser))
            ->url(fn ($record) => static::getUrl('view', ['record' => $record->websiteUser]));
    }

    public static function blockAction(): Action
    {
        return Action::make('block')
            ->label(__('admin.website_users.block'))
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription(__('admin.website_users.block_confirm'))
            ->visible(fn (WebsiteUser $record) => ! $record->isBlocked() && ! $record->trashed() && static::canEdit($record))
            ->action(fn (WebsiteUser $record) => $record->block())
            ->successNotificationTitle(__('admin.website_users.notifications.blocked'));
    }

    public static function unblockAction(): Action
    {
        return Action::make('unblock')
            ->label(__('admin.website_users.unblock'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (WebsiteUser $record) => $record->isBlocked() && ! $record->trashed() && static::canEdit($record))
            ->action(fn (WebsiteUser $record) => $record->unblock())
            ->successNotificationTitle(__('admin.website_users.notifications.unblocked'));
    }

    /**
     * Block / unblock the selected users. Needs edit + bulk; users you can't edit are skipped.
     *
     * @param  \Closure(WebsiteUser): void  $apply
     */
    protected static function statusBulkAction(string $name, string $icon, \Closure $apply): BulkAction
    {
        return BulkAction::make($name)
            ->label(__("admin.website_users.{$name}"))
            ->icon($icon)
            ->requiresConfirmation()
            ->modalDescription($name === 'block' ? __('admin.website_users.block_confirm') : null)
            ->authorize(fn (): bool => static::can('updateAny'))
            ->action(fn (Collection $records) => $records->filter(fn (WebsiteUser $record) => static::canEdit($record))->each($apply))
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
            'index' => Pages\ListWebsiteUsers::route('/'),
            'view' => Pages\ViewWebsiteUser::route('/{record}'),
            'edit' => Pages\EditWebsiteUser::route('/{record}/edit'),
        ];
    }
}
