<?php

namespace Modules\Users\Filament\Resources;

use App\Filament\Contracts\HasPermissionModule;
use App\Filament\Helpers\TableHelper;
use App\Filament\Traits\HasResourceTranslations;
use Closure;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Filament\Forms\PermissionsGrid;
use Modules\Users\Filament\Resources\RoleResource\Pages;
use Modules\Users\Models\User;

class RoleResource extends Resource implements HasPermissionModule
{
    use HasResourceTranslations;

    protected static ?string $permissionGroup = 'system_configuration';

    protected static ?string $model = Lookup::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.system_configuration');
    }

    public static function getRecordTitleAttribute(): string
    {
        return getLocalizedFieldName('name');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name_en', 'name_ar', 'slug'];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Cache::remember('badge_count_roles', 120, fn () => Lookup::roles()->count());
    }

    public static function getModelLabel(): string
    {
        return __('admin.roles.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.roles.plural');
    }

    /**
     * Only an admin may change the Admin role, seeded roles can't be deleted, and nobody manages a role with more
     * access than they have (also applies to bulk actions).
     */
    protected static function canPerformOnRecord(string $ability, Model $record): bool
    {
        return match ($ability) {
            'update' => ($record->slug !== User::ADMIN_ROLE || (bool) auth()->user()?->isAdmin()) && static::canManageRole($record),
            'delete', 'forceDelete' => (bool) $record->is_deletable && static::canManageRole($record),
            'restore' => static::canManageRole($record),
            default => true,
        };
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.tabs.details'))
                    ->icon('heroicon-o-information-circle')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name_en')
                            ->label(__('admin.roles.name_en'))
                            ->required()
                            ->maxLength(191)
                            // Role slugs are built from this name, so it must be unique among roles.
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule
                                ->where('parent_id', static::rolesParentId())
                                ->whereNull('deleted_at'))
                            // Different names can give the same slug (e.g. "Sales Team" and "Sales-Team").
                            ->rule(fn (?Lookup $record) => function (string $attribute, $value, Closure $fail) use ($record) {
                                if (! static::isSlugLocked($record) && static::slugIsTaken(static::makeSlug($value), $record)) {
                                    $fail(__('admin.roles.slug_taken'));
                                }
                            })
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, ?Lookup $record) {
                                if (! static::isSlugLocked($record)) {
                                    $set('slug', static::makeSlug($state));
                                }
                            })
                            ->prefixIcon('heroicon-m-language'),
                        TextInput::make('name_ar')
                            ->label(__('admin.roles.name_ar'))
                            ->required()
                            ->maxLength(191)
                            ->prefixIcon('heroicon-m-language'),
                        TextInput::make('slug')
                            ->label(__('admin.table.slug'))
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText(fn (?Lookup $record) => static::isSlugLocked($record) ? __('admin.roles.slug_locked') : null)
                            ->prefixIcon('heroicon-m-link'),
                        Toggle::make('is_active')
                            ->label(__('admin.lookups.active'))
                            // The Admin role is always active.
                            ->disabled(fn (?Lookup $record) => $record?->slug === User::ADMIN_ROLE)
                            ->default(true)
                            ->inline(false),
                        Textarea::make('description_en')
                            ->label(__('admin.roles.description_en'))
                            ->columnSpanFull(),
                        Textarea::make('description_ar')
                            ->label(__('admin.roles.description_ar'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('admin.roles.permissions'))
                    ->icon('heroicon-o-shield-check')
                    ->description(__('admin.roles.permissions_description'))
                    ->columnSpanFull()
                    ->schema(PermissionsGrid::make(fn ($record, string $module) => static::isLockedPermission($record, $module))),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TableHelper::indexColumn(),
                TextColumn::make(getLocalizedFieldName('name'))
                    ->label(__('admin.table.title'))
                    ->sortable()
                    ->searchable(['name_en', 'name_ar'])
                    ->alignment('center')
                    ->url(fn (Lookup $record) => static::getUrl('view', ['record' => $record]))
                    ->color('primary'),
                TextColumn::make('slug')
                    ->label(__('admin.table.slug'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignment('center')
                    ->copyable()
                    ->copyMessage(__('admin.actions.copied')),
                IconColumn::make('is_active')
                    ->label(__('admin.lookups.active'))
                    ->boolean()
                    ->sortable()
                    ->alignment('center'),
                TableHelper::createdAtColumn(),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin.table.status'))
                    ->trueLabel(__('admin.lookups.active'))
                    ->falseLabel(__('admin.lookups.deactive'))
                    ->indicator(__('admin.table.status')),
                TableHelper::dateRangeFilter('created_at', 'created_from', 'created_until'),
                TableHelper::dateRangeFilter('updated_at', 'updated_from', 'updated_until'),
                TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label(__('admin.actions.view')),
                    EditAction::make()->label(__('admin.actions.edit')),
                    DeleteAction::make()
                        ->label(__('admin.actions.delete'))
                        ->successNotification(fn (Lookup $record) => Notification::make()
                            ->success()
                            ->title(__('admin.roles.notifications.deleted', ['name' => $record->getLocalized('name')]))),
                    RestoreAction::make()->label(__('admin.actions.restore')),
                    ForceDeleteAction::make()->label(__('admin.actions.force_delete')),
                ]),
            ])
            ->recordActionsColumnLabel(__('admin.table.actions'))
            ->recordActionsAlignment('center')
            ->toolbarActions(TableHelper::standardBulkActions([
                TableHelper::statusBulkAction(static::class, 'activate', 'heroicon-o-check-circle', true),
                TableHelper::statusBulkAction(static::class, 'deactivate', 'heroicon-o-x-circle', false),
            ]));
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->roles()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function rolesParentId(): ?int
    {
        return Lookup::where('slug', Lookup::ROLES_SLUG)->whereNull('parent_id')->value('id');
    }

    /**
     * Role slug built from the English name, e.g. "Sales Team" => "user_roles_sales_team".
     */
    public static function makeSlug(?string $name): string
    {
        return 'user_roles_'.Str::slug((string) $name, '_');
    }

    /**
     * Only the Admin role keeps its slug, because the code checks it by slug. Other roles follow their name.
     */
    public static function isSlugLocked(?Lookup $role): bool
    {
        return $role !== null && ! $role->is_deletable && in_array($role->slug, [User::ADMIN_ROLE], true);
    }

    public static function slugIsTaken(string $slug, ?Lookup $ignore = null): bool
    {
        return Lookup::withTrashed()
            ->where('slug', $slug)
            ->when($ignore, fn (Builder $query) => $query->whereKeyNot($ignore->getKey()))
            ->exists();
    }

    /**
     * Nobody edits, deletes or restores a role with more access than they have themselves.
     */
    protected static function canManageRole(Model $role): bool
    {
        return (bool) auth()->user()?->covers($role->getAttribute('attributes')['permissions'] ?? []);
    }

    /**
     * Stored role permissions => form state ("perm_<module>").
     */
    public static function permissionsToFormData(Lookup $role, array $data): array
    {
        return PermissionsGrid::toFormData($role->getAttribute('attributes')['permissions'] ?? [], $data);
    }

    /**
     * Form state => attributes.permissions, keeping the role's other attribute keys (e.g. color).
     */
    public static function formDataToPermissions(array $data, array $existingAttributes = [], ?Lookup $role = null): array
    {
        $permissions = PermissionsGrid::fromFormData(
            $data,
            $existingAttributes['permissions'] ?? [],
            fn (string $module) => static::isLockedPermission($role, $module),
        );

        $data['attributes'] = array_merge($existingAttributes, ['permissions' => $permissions]);

        return $data;
    }

    /**
     * The Admin role always keeps every action on the locked modules (users, roles).
     */
    public static function isLockedPermission(?Lookup $role, string $module): bool
    {
        return $role?->slug === User::ADMIN_ROLE && in_array($module, User::ADMIN_LOCKED_MODULES, true);
    }
}
