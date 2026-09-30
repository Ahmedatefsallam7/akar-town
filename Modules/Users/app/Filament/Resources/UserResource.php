<?php

namespace Modules\Users\Filament\Resources;

use App\Filament\Helpers\TableHelper;
use App\Filament\Traits\HasResourceTranslations;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Filament\Resources\UserResource\Pages;
use Modules\Users\Models\User;

class UserResource extends Resource
{
    use HasResourceTranslations;

    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'username';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.system_configuration');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'username', 'email', 'phone'];
    }

    public static function getModelLabel(): string
    {
        return __('admin.users.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.users.plural');
    }

    /**
     * Nobody deletes their own account from here, so an admin cannot lock themselves out.
     */
    public static function canDelete(Model $record): bool
    {
        return static::canAccess() && $record->isNot(auth()->user());
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::canDelete($record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.tabs.details'))
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('first_name')
                            ->label(__('admin.users.first_name'))
                            ->required()
                            ->maxLength(191),
                        TextInput::make('last_name')
                            ->label(__('admin.users.last_name'))
                            ->required()
                            ->maxLength(191),
                        TextInput::make('username')
                            ->label(__('admin.users.username'))
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(191),
                        TextInput::make('email')
                            ->label(__('admin.users.email'))
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(191),
                        TextInput::make('phone')
                            ->label(__('admin.users.phone'))
                            ->tel()
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(191)
                            ->regex('/^\+?[0-9]{7,15}$/')
                            ->helperText(__('admin.users.phone_format')),
                        Select::make('role_id')
                            ->label(__('admin.users.role'))
                            ->relationship(
                                'role',
                                getLocalizedFieldName('name'),
                                function (Builder $query) {
                                    $query->roles();

                                    // Only an admin may assign the Admin role (prevents privilege escalation).
                                    if (! auth()->user()?->isAdmin()) {
                                        $query->where('slug', '!=', User::ADMIN_ROLE);
                                    }
                                }
                            )
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('password')
                            ->label(__('admin.users.password'))
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->same('password_confirmation')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText(fn (string $operation): string => $operation === 'edit' ? __('admin.users.password_leave_blank') : ''),
                        TextInput::make('password_confirmation')
                            ->label(__('admin.users.password_confirmation'))
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->required(fn (string $operation): bool => $operation === 'create'),
                        Toggle::make('is_suspended')
                            ->label(__('admin.users.suspended'))
                            // You cannot suspend yourself.
                            ->disabled(fn (?User $record) => $record?->is(auth()->user())),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                TableHelper::indexColumn(),
                TextColumn::make('name')
                    ->label(__('admin.users.full_name'))
                    ->searchable(['first_name', 'last_name'])
                    ->alignment('center')
                    ->url(fn (User $record) => static::getUrl('view', ['record' => $record]))
                    ->color('primary'),
                TextColumn::make('username')
                    ->label(__('admin.users.username'))
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->alignment('center'),
                TextColumn::make('email')
                    ->label(__('admin.users.email'))
                    ->searchable()
                    ->alignment('center'),
                IconColumn::make('email_verified_at')
                    ->label(__('admin.users.email_verified'))
                    ->boolean()
                    ->trueIcon('heroicon-m-check-badge')
                    ->falseIcon('heroicon-m-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->sortable()
                    ->toggleable()
                    ->alignment('center')
                    ->getStateUsing(fn (User $record) => ! is_null($record->email_verified_at)),
                TextColumn::make('phone')
                    ->label(__('admin.users.phone'))
                    ->searchable()
                    ->toggleable()
                    ->alignment('center'),
                TextColumn::make('role.'.getLocalizedFieldName('name'))
                    ->label(__('admin.users.role'))
                    ->badge()
                    ->color(fn (User $record): string => $record->roleColor())
                    ->alignment('center'),
                IconColumn::make('is_suspended')
                    ->boolean()
                    ->label(__('admin.users.suspended'))
                    ->trueIcon('heroicon-m-check-circle')
                    ->falseIcon('heroicon-m-x-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->sortable()
                    ->alignment('center'),
                TableHelper::createdAtColumn(),
            ])
            ->filters([
                QueryBuilder::make()
                    ->constraints([
                        TextConstraint::make('first_name')->label(__('admin.users.first_name')),
                        TextConstraint::make('last_name')->label(__('admin.users.last_name')),
                        TextConstraint::make('username')->label(__('admin.users.username')),
                        TextConstraint::make('email')->label(__('admin.users.email')),
                        TextConstraint::make('phone')->label(__('admin.users.phone')),
                        DateConstraint::make('created_at')->label(__('admin.table.created_at')),
                        DateConstraint::make('updated_at')->label(__('admin.table.updated_at')),
                    ]),
                SelectFilter::make('role_id')
                    ->label(__('admin.users.role'))
                    ->relationship('role', getLocalizedFieldName('name'), fn (Builder $query) => $query->roles())
                    ->searchable()
                    ->preload()
                    ->indicator(__('admin.users.role')),
                TernaryFilter::make('is_suspended')
                    ->label(__('admin.users.suspended'))
                    ->indicator(__('admin.users.suspended')),
                TernaryFilter::make('email_verified_at')
                    ->label(__('admin.users.email_verified'))
                    ->nullable()
                    ->placeholder(__('admin.users.all_users'))
                    ->trueLabel(__('admin.users.verified'))
                    ->falseLabel(__('admin.users.unverified')),
                TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label(__('admin.actions.view')),
                    EditAction::make()->label(__('admin.actions.edit')),
                    DeleteAction::make()
                        ->label(__('admin.actions.delete'))
                        ->successNotification(fn (User $record) => Notification::make()
                            ->success()
                            ->title(__('admin.users.notifications.deleted', ['name' => $record->name ?: $record->username]))),
                    RestoreAction::make()->label(__('admin.actions.restore')),
                    ForceDeleteAction::make()->label(__('admin.actions.force_delete')),
                ]),
            ])
            ->recordActionsColumnLabel(__('admin.table.actions'))
            ->recordActionsAlignment('center')
            ->toolbarActions(TableHelper::standardBulkActions([
                static::suspendBulkAction('suspend', 'heroicon-o-pause-circle', true),
                static::suspendBulkAction('unsuspend', 'heroicon-o-play-circle', false),
            ]))
            ->checkIfRecordIsSelectableUsing(fn (User $record): bool => $record->isNot(auth()->user()))
            ->paginated([10, 25, 50, 100]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('role')
            // Only admins can see (and so edit) admin accounts.
            ->when(! auth()->user()?->isAdmin(), fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereNull('role_id')
                ->orWhereDoesntHave('role', fn (Builder $role) => $role->where('slug', User::ADMIN_ROLE))))
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    protected static function suspendBulkAction(string $name, string $icon, bool $isSuspended): BulkAction
    {
        return BulkAction::make($name)
            ->label(__("admin.actions.{$name}"))
            ->icon($icon)
            ->requiresConfirmation()
            ->action(fn (Collection $records) => $records
                ->reject(fn (User $user) => $user->is(auth()->user()))
                ->each->update(['is_suspended' => $isSuspended]))
            ->successNotificationTitle(__('admin.notifications.updated'))
            ->deselectRecordsAfterCompletion();
    }
}
