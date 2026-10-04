<?php

namespace Modules\Users\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
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
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Enums\PermissionAction;
use Modules\Users\Filament\Forms\PermissionsGrid;
use Modules\Users\Filament\Resources\UserResource\Pages;
use Modules\Users\Models\User;
use Modules\Users\Support\PermissionModules;

class UserResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    protected static ?string $permissionGroup = 'system_configuration';

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

    /**
     * Counts only the users you can see (non-admins don't see admin accounts), cached per group.
     */
    public static function getNavigationBadge(): ?string
    {
        $scope = auth()->user()?->isAdmin() ? 'all' : 'non_admin';

        return (string) Cache::remember("badge_count_user_{$scope}", 120, fn () => static::getEloquentQuery()->withoutTrashed()->count());
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
     * Nobody deletes their own account from here (also blocks bulk delete), so an admin cannot lock themselves out.
     */
    protected static function canPerformOnRecord(string $ability, Model $record): bool
    {
        $me = auth()->user();

        return match ($ability) {
            // Yourself: edit only (never delete).
            'update' => $record->is($me) || (bool) $me?->covers($record->effectivePermissions()),
            // Nobody manages (password, permissions, delete...) a user with more access than themselves.
            'delete', 'forceDelete', 'restore' => $record->isNot($me) && (bool) $me?->covers($record->effectivePermissions()),
            default => true,
        };
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
                                fn (Builder $query) => $query->whereIn('id', static::assignableRoleIds())
                            )
                            // Nobody changes their own role.
                            ->disabled(fn (?User $record) => $record?->is(auth()->user()))
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
                            ->helperText(fn (string $operation): string => $operation === 'edit' ? __('admin.users.password_leave_blank') : '')
                            ->visible(fn (string $operation, ?User $record): bool => static::canSetPassword($operation, $record)),
                        TextInput::make('password_confirmation')
                            ->label(__('admin.users.password_confirmation'))
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->visible(fn (string $operation, ?User $record): bool => static::canSetPassword($operation, $record)),
                        Toggle::make('is_suspended')
                            ->label(__('admin.users.suspended'))
                            // You cannot suspend yourself.
                            ->disabled(fn (?User $record) => $record?->is(auth()->user())),
                    ])
                    ->columns(2),

                Section::make(__('admin.users.permissions'))
                    ->icon('heroicon-o-shield-check')
                    ->columnSpanFull()
                    // Nobody changes their own permissions.
                    ->hidden(fn (?User $record) => $record?->is(auth()->user()))
                    ->schema([
                        Toggle::make('use_custom_permissions')
                            ->label(__('admin.users.custom_permissions'))
                            ->helperText(__('admin.users.custom_permissions_help'))
                            ->live()
                            // Start from the role's permissions, so you only change what differs.
                            ->afterStateUpdated(function (bool $state, Set $set, Get $get) {
                                if ($state) {
                                    $role = Lookup::find($get('role_id'));
                                    foreach (PermissionsGrid::toFormData($role?->getAttribute('attributes')['permissions'] ?? []) as $field => $actions) {
                                        $set($field, $actions);
                                    }
                                }
                            }),
                        Group::make(PermissionsGrid::make())
                            ->visible(fn (Get $get) => (bool) $get('use_custom_permissions'))
                            ->hiddenOn('view'),
                    ]),

                Section::make(__('admin.users.effective_permissions'))
                    ->icon('heroicon-o-eye')
                    ->columnSpanFull()
                    ->visibleOn('view')
                    ->schema(static::getEffectivePermissionEntries())
                    ->columns(2),
            ]);
    }

    /**
     * Stored custom permissions => form state.
     */
    public static function permissionsToFormData(?User $user, array $data): array
    {
        $data['use_custom_permissions'] = (bool) $user?->usesCustomPermissions();

        return PermissionsGrid::toFormData($user?->custom_permissions ?? [], $data);
    }

    /**
     * Form state => custom_permissions (null = follow the role).
     */
    public static function formDataToPermissions(array $data, ?User $user = null): array
    {
        $useCustom = (bool) ($data['use_custom_permissions'] ?? false);
        unset($data['use_custom_permissions']);

        $permissions = PermissionsGrid::fromFormData($data, $user?->custom_permissions ?? []);
        $data['custom_permissions'] = $useCustom ? $permissions : null;

        return $data;
    }

    protected static function getEffectivePermissionEntries(): array
    {
        return collect(PermissionModules::all())
            ->map(fn (array $module, string $key) => TextEntry::make("effective_{$key}")
                ->label($module['label'])
                ->state(fn (?User $record) => collect($record?->effectiveActions($key) ?? [])
                    ->map(fn (string $action) => PermissionAction::from($action)->label())
                    ->all())
                ->badge()
                ->placeholder(__('admin.users.no_access')))
            ->values()
            ->all();
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

    /**
     * Passwords: set when creating a user; afterwards only an admin may change someone else's (e.g. a forgotten
     * password, since there's no public registration or reset). Everyone changes their own on the profile page,
     * which asks for the current password.
     */
    public static function canSetPassword(string $operation, ?User $record): bool
    {
        return $operation === 'create'
            || ($operation === 'edit' && (bool) auth()->user()?->isAdmin() && $record?->isNot(auth()->user()));
    }

    /**
     * Roles the logged-in user may give: admins any role; others only non-Admin roles with no more access than
     * they have themselves (prevents privilege escalation).
     *
     * @return array<int>
     */
    public static function assignableRoleIds(): array
    {
        $me = auth()->user();

        return Lookup::roles()
            ->get(['id', 'slug', 'attributes'])
            ->filter(fn (Lookup $role) => $me?->isAdmin()
                || ($role->slug !== User::ADMIN_ROLE && (bool) $me?->covers($role->getAttribute('attributes')['permissions'] ?? [])))
            ->pluck('id')
            ->all();
    }

    protected static function suspendBulkAction(string $name, string $icon, bool $isSuspended): BulkAction
    {
        return BulkAction::make($name)
            ->label(__("admin.actions.{$name}"))
            ->icon($icon)
            ->requiresConfirmation()
            ->authorize(fn (): bool => static::can('updateAny'))
            ->action(fn (Collection $records) => $records
                ->reject(fn (User $user) => $user->is(auth()->user()))
                ->filter(fn (User $user) => static::canEdit($user))
                ->each->update(['is_suspended' => $isSuspended]))
            ->successNotificationTitle(__('admin.notifications.updated'))
            ->deselectRecordsAfterCompletion();
    }
}
