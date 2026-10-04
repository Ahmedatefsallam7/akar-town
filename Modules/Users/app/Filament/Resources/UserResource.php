<?php

namespace Modules\Users\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Helpers\TableHelper;
use App\Filament\Support\PrefixSearch;
use App\Filament\Traits\HasResourceTranslations;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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

    /**
     * Searched by the table search and the global search; each has an index.
     */
    public const SEARCH_COLUMNS = ['first_name', 'last_name', 'username', 'email', 'phone'];

    public static function getGloballySearchableAttributes(): array
    {
        return static::SEARCH_COLUMNS;
    }

    /**
     * Global search uses the same fast prefix search as the table.
     */
    protected static function applyGlobalSearchAttributeConstraints(Builder $query, string $search): void
    {
        PrefixSearch::apply($query, $search, static::SEARCH_COLUMNS, ['first_name', 'last_name']);
    }

    /**
     * Counts only the users you can see (non-admins don't see admin accounts).
     */
    public static function getNavigationBadgeCount(): int
    {
        return static::getEloquentQuery()->withoutTrashed()->count();
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
                Tabs::make('user')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make(__('admin.tabs.details'))
                            ->icon('heroicon-o-user')
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
                                    // Only roles you may give (plus the user's current one); Filament validates against these.
                                    ->options(fn (?User $record) => static::roleOptions($record))
                                    // Nobody changes their own role.
                                    ->disabled(fn (?User $record) => $record?->is(auth()->user()))
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Toggle::make('is_suspended')
                                    ->label(__('admin.users.suspended'))
                                    // You cannot suspend yourself.
                                    ->disabled(fn (?User $record) => $record?->is(auth()->user())),
                            ])
                            ->columns(2),

                        Tab::make(__('admin.users.password'))
                            ->icon('heroicon-o-key')
                            ->visible(fn (string $operation, ?User $record): bool => static::canSetPassword($operation, $record))
                            ->schema([
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
                            ])
                            ->columns(2),

                        Tab::make(__('admin.users.permissions'))
                            ->icon('heroicon-o-shield-check')
                            // Nobody changes their own permissions; the view page shows the effective ones instead.
                            ->hidden(fn (string $operation, ?User $record) => $operation === 'view' || $record?->is(auth()->user()))
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

                        Tab::make(__('admin.users.effective_permissions'))
                            ->icon('heroicon-o-eye')
                            ->visibleOn('view')
                            ->schema(static::getEffectivePermissionEntries())
                            ->columns(2),
                    ]),
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
        return TableHelper::emptyState($table, static::class, 'heroicon-o-users')
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                TableHelper::indexColumn(),
                TextColumn::make('name')
                    ->label(__('admin.users.full_name'))
                    // One fast prefix search over name, username, email and phone (see PrefixSearch).
                    ->searchable(query: PrefixSearch::using(static::SEARCH_COLUMNS, ['first_name', 'last_name']))
                    ->alignment('center')
                    ->url(fn (User $record) => static::getUrl('view', ['record' => $record]))
                    ->color('primary'),
                TextColumn::make('username')
                    ->label(__('admin.users.username'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignment('center'),
                TextColumn::make('email')
                    ->label(__('admin.users.email'))
                    ->alignment('center'),
                IconColumn::make('email_verified_at')
                    ->label(__('admin.users.email_verified'))
                    ->boolean()
                    ->trueIcon('heroicon-m-check-badge')
                    ->falseIcon('heroicon-m-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignment('center')
                    ->getStateUsing(fn (User $record) => ! is_null($record->email_verified_at)),
                TextColumn::make('phone')
                    ->label(__('admin.users.phone'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignment('center'),
                // role_id (not "role.name") so Filament doesn't eager-load roles again; they're attached by getEloquentQuery().
                TextColumn::make('role_id')
                    ->label(__('admin.users.role'))
                    ->formatStateUsing(fn (User $record): ?string => $record->role?->getLocalized('name'))
                    ->badge()
                    ->color(fn (User $record): string => $record->roleColor())
                    ->alignment('center'),
                // A clear status badge: "Active" (green) or "Suspended" (red).
                TextColumn::make('is_suspended')
                    ->label(__('admin.table.status'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('admin.users.suspended') : __('admin.lookups.active'))
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-m-pause-circle' : 'heroicon-m-check-circle')
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
                    ->options(fn () => static::roles()->pluck(getLocalizedFieldName('name'), 'id')->all())
                    ->searchable()
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
            ->recordActions(TableHelper::recordActions(
                fn (User $record) => __('admin.users.notifications.deleted', ['name' => $record->name ?: $record->username]),
            ))
            ->recordActionsColumnLabel(__('admin.table.actions'))
            ->recordActionsAlignment('center')
            ->toolbarActions(TableHelper::standardBulkActions([
                static::suspendBulkAction('suspend', 'heroicon-o-pause-circle', true),
                static::suspendBulkAction('unsuspend', 'heroicon-o-play-circle', false),
            ]))
            ->paginated([10, 25, 50, 100]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            // Roles come from the per-request roles list instead of one more query.
            ->afterQuery(fn ($users) => static::attachRoles($users))
            // Only admins can see (and so edit) admin accounts. The admin role id is an uncorrelated subquery.
            ->when(! auth()->user()?->isAdmin(), fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereNull('role_id')
                ->orWhereNotIn('role_id', fn ($roles) => $roles->select('id')->from('lookups')->where('slug', User::ADMIN_ROLE))))
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

        return static::roles()
            ->filter(fn (Lookup $role) => $me?->isAdmin()
                || ($role->slug !== User::ADMIN_ROLE && (bool) $me?->covers($role->getAttribute('attributes')['permissions'] ?? [])))
            ->pluck('id')
            ->all();
    }

    /**
     * Role options for the form: the roles you may give, plus the user's current role so its name still shows.
     *
     * @return array<int, string>
     */
    public static function roleOptions(?User $record): array
    {
        $ids = [...static::assignableRoleIds(), $record?->role_id];

        return static::roles()->whereIn('id', $ids)->pluck(getLocalizedFieldName('name'), 'id')->all();
    }

    /**
     * Sets each user's role from roles() (already loaded for the form and the filter) instead of querying again.
     *
     * @param  iterable<User>  $users
     */
    public static function attachRoles(iterable $users): void
    {
        $roles = static::roles()->keyBy('id');

        foreach ($users as $user) {
            if ($user instanceof User) {
                $user->setRelation('role', $roles->get($user->role_id));
            }
        }
    }

    /**
     * Every role, fetched once per request and shared by the form, its validation and the table filter.
     *
     * @return Collection<int, Lookup>
     */
    public static function roles(): Collection
    {
        return once(fn () => Lookup::roles()->orderBy('order')->get());
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
