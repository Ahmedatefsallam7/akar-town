<?php

namespace Modules\Users\Filament\Forms;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Set;
use Modules\Users\Enums\PermissionAction;
use Modules\Users\Support\PermissionModules;

/**
 * The module => actions checkbox grid, shared by the role form and the user form.
 * Fields are named "perm_<module>". You can only tick or untick actions you have yourself.
 */
class PermissionsGrid
{
    /**
     * @param  Closure(mixed $record, string $module): bool|null  $isLocked  locked modules are always fully ticked
     * @return array<Component>
     */
    public static function make(?Closure $isLocked = null): array
    {
        $locked = fn ($record, string $module): bool => $isLocked !== null && $isLocked($record, $module);

        $components = [
            Actions::make([
                Action::make('selectAllPermissions')
                    ->label(__('admin.roles.select_all'))
                    ->link()
                    ->action(fn (Set $set) => static::setAll($set, true)),
                Action::make('clearAllPermissions')
                    ->label(__('admin.roles.clear_all'))
                    ->link()
                    ->color('gray')
                    ->action(fn (Set $set) => static::setAll($set, false)),
            ])->key('permissionButtons'),
        ];

        foreach (PermissionModules::grouped() as $group => $modules) {
            $components[] = Fieldset::make(__("admin.navigation.{$group}"))
                ->schema(array_map(fn (array $module) => CheckboxList::make("perm_{$module['key']}")
                    ->label($module['label'])
                    ->options(collect($module['actions'])
                        ->mapWithKeys(fn (string $action) => [$action => PermissionAction::from($action)->label()])
                        ->all())
                    ->disableOptionWhen(fn (string $value) => ! in_array($value, static::grantable($module['key']), true))
                    ->columns(5)
                    ->disabled(fn ($record) => $locked($record, $module['key']))
                    ->helperText(fn ($record) => $locked($record, $module['key']) ? __('admin.roles.locked_for_admin') : null)
                    ->columnSpanFull(), array_values($modules)));
        }

        return $components;
    }

    /**
     * Stored permissions => form state.
     */
    public static function toFormData(array $permissions, array $data = []): array
    {
        foreach (PermissionModules::all() as $key => $module) {
            $data["perm_{$key}"] = array_values(array_intersect($module['actions'], (array) ($permissions[$key] ?? [])));
        }

        return $data;
    }

    /**
     * Form state => stored permissions. Removes the "perm_*" fields from $data.
     * - Locked modules always get every action.
     * - Actions you don't have yourself keep their saved value (you can't add or remove them).
     * - Modules not in the form (e.g. not built yet) keep their saved actions.
     *
     * @param  Closure(string $module): bool|null  $isLocked
     * @return array<string, array<string>>
     */
    public static function fromFormData(array &$data, array $existing = [], ?Closure $isLocked = null): array
    {
        $permissions = $existing;

        foreach (PermissionModules::all() as $key => $module) {
            $field = "perm_{$key}";

            if ($isLocked !== null && $isLocked($key)) {
                $permissions[$key] = $module['actions'];
            } elseif (array_key_exists($field, $data)) {
                $grantable = static::grantable($key);
                $chosen = array_intersect((array) $data[$field], $grantable);
                $kept = array_diff((array) ($existing[$key] ?? []), $grantable);

                $permissions[$key] = array_values(array_intersect($module['actions'], [...$chosen, ...$kept]));
            }

            unset($data[$field]);
        }

        return $permissions;
    }

    /**
     * Actions the logged-in user may give or take away on a module: the ones they have.
     *
     * @return array<string>
     */
    public static function grantable(string $module): array
    {
        $user = auth()->user();

        return array_values(array_filter(
            PermissionModules::actionsFor($module),
            fn (string $action) => (bool) $user?->hasPermission($module, PermissionAction::from($action)),
        ));
    }

    protected static function setAll(Set $set, bool $on): void
    {
        foreach (PermissionModules::all() as $key => $module) {
            $set("perm_{$key}", $on ? static::grantable($key) : []);
        }
    }
}
