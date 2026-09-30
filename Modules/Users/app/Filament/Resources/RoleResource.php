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
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\Unique;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Filament\Resources\RoleResource\Pages;
use Modules\Users\Models\User;

class RoleResource extends Resource
{
    use HasResourceTranslations;

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
     * Only an admin may change the Admin role.
     */
    public static function canEdit(Model $record): bool
    {
        return static::canAccess()
            && ($record->slug !== User::ADMIN_ROLE || auth()->user()?->isAdmin());
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccess() && $record->is_deletable;
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
                            ->prefixIcon('heroicon-m-link'),
                        Toggle::make('is_active')
                            ->label(__('admin.lookups.active'))
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
                    ->schema(static::getPermissionFields()),
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
                static::dateRangeFilter('created_at', 'created_from', 'created_until'),
                static::dateRangeFilter('updated_at', 'updated_from', 'updated_until'),
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
                static::statusBulkAction('activate', 'heroicon-o-check-circle', true),
                static::statusBulkAction('deactivate', 'heroicon-o-x-circle', false),
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

    /**
     * Permission keys grouped by sidebar section. Each key matches a resource's permission key.
     *
     * @return array<string, array<string>>
     */
    public static function getAvailableModules(): array
    {
        return [
            'property_management' => [
                'projects',
                'unit_types',
                'locations',
                'developers',
                'galleries',
                'import_sheet_history',
            ],
            'marketing_content' => [
                'blogs',
                'events',
                'sliders',
                'careers',
                'applicants',
            ],
            'inquiries_crm' => [
                'contact_us',
                'meetings',
                'unit_requests',
            ],
            'system_configuration' => [
                'users',
                'roles',
                'lookups',
                'settings',
                'utilities',
            ],
        ];
    }

    public static function rolesParentId(): ?int
    {
        return Lookup::where('slug', Lookup::ROLES_SLUG)->whereNull('parent_id')->value('id');
    }

    /**
     * Turn stored permissions into form checkbox state ("perm_<module>" and "perm_<group>_all").
     */
    public static function permissionsToFormData(Lookup $role, array $data): array
    {
        $permissions = $role->getAttribute('attributes')['permissions'] ?? [];

        foreach (static::getAvailableModules() as $group => $modules) {
            foreach ($modules as $module) {
                $data["perm_{$module}"] = static::isLockedPermission($role, $module) || (bool) ($permissions[$module] ?? false);
            }

            $data["perm_{$group}_all"] = collect($modules)->every(fn (string $module) => $data["perm_{$module}"]);
        }

        return $data;
    }

    /**
     * Move the permission checkboxes out of the form data into attributes.permissions, keeping other attribute keys.
     */
    public static function formDataToPermissions(array $data, array $existingAttributes = [], ?Lookup $role = null): array
    {
        $permissions = [];

        foreach (static::getAvailableModules() as $group => $modules) {
            unset($data["perm_{$group}_all"]);

            foreach ($modules as $module) {
                // Locked checkboxes are disabled, so they are not sent with the form.
                $permissions[$module] = static::isLockedPermission($role, $module) || (bool) ($data["perm_{$module}"] ?? false);
                unset($data["perm_{$module}"]);
            }
        }

        $data['attributes'] = array_merge($existingAttributes, ['permissions' => $permissions]);

        return $data;
    }

    /**
     * The Admin role always keeps the locked modules (users, roles).
     */
    public static function isLockedPermission(?Lookup $role, string $module): bool
    {
        return $role?->slug === User::ADMIN_ROLE && in_array($module, User::ADMIN_LOCKED_MODULES, true);
    }

    protected static function getPermissionFields(): array
    {
        $fields = [];

        foreach (static::getAvailableModules() as $group => $modules) {
            $fields[] = Fieldset::make(__("admin.navigation.{$group}"))
                ->schema([
                    Checkbox::make("perm_{$group}_all")
                        ->label(__('admin.roles.select_all'))
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state, ?Lookup $record) use ($modules) {
                            foreach ($modules as $module) {
                                $set("perm_{$module}", $state || static::isLockedPermission($record, $module));
                            }
                        }),
                    ...array_map(fn (string $module) => Checkbox::make("perm_{$module}")
                        ->label(__("admin.navigation.{$module}"))
                        ->disabled(fn (?Lookup $record) => static::isLockedPermission($record, $module))
                        ->helperText(fn (?Lookup $record) => static::isLockedPermission($record, $module)
                            ? __('admin.roles.locked_for_admin')
                            : null), $modules),
                ])
                ->columns(2);
        }

        return $fields;
    }

    protected static function dateRangeFilter(string $column, string $from, string $until): Filter
    {
        return Filter::make($column)
            ->schema([
                DatePicker::make($from)
                    ->label(__("admin.filter.{$from}"))
                    ->prefixIcon('heroicon-m-calendar'),
                DatePicker::make($until)
                    ->label(__("admin.filter.{$until}"))
                    ->prefixIcon('heroicon-m-calendar'),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data[$from] ?? null, fn (Builder $query, $date): Builder => $query->whereDate($column, '>=', $date))
                ->when($data[$until] ?? null, fn (Builder $query, $date): Builder => $query->whereDate($column, '<=', $date)))
            ->indicateUsing(function (array $data) use ($from, $until): array {
                $indicators = [];

                foreach ([$from, $until] as $field) {
                    if ($data[$field] ?? null) {
                        $indicators[] = Indicator::make(__("admin.filter.{$field}").': '.$data[$field])->removeField($field);
                    }
                }

                return $indicators;
            });
    }

    protected static function statusBulkAction(string $name, string $icon, bool $isActive): BulkAction
    {
        return BulkAction::make($name)
            ->label(__("admin.actions.{$name}"))
            ->icon($icon)
            ->requiresConfirmation()
            // The Admin role is left out unless an admin runs the action.
            ->action(fn (Collection $records) => $records
                ->filter(fn (Lookup $role) => static::canEdit($role))
                ->each->update(['is_active' => $isActive]))
            ->successNotificationTitle(__('admin.notifications.updated'))
            ->deselectRecordsAfterCompletion();
    }
}
