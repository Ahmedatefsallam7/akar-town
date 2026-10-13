<?php

namespace Modules\Users\Filament\Forms;

use Closure;
use Filament\Schemas\Components\Component;
use Modules\Users\Enums\PermissionAction;
use Modules\Users\Support\PermissionModules;

/**
 * The module => actions permissions table (PermissionsMatrix), shared by the role form and the user form,
 * and the mapping between its state and what is stored. You can only tick or untick actions you have yourself.
 */
class PermissionsGrid
{
    /**
     * @param  Closure(mixed $record, string $module): bool|null  $isLocked  locked modules are always fully ticked
     * @return array<Component>
     */
    public static function make(?Closure $isLocked = null): array
    {
        $field = PermissionsMatrix::make('permission_matrix');

        if ($isLocked !== null) {
            $field->lockedUsing($isLocked);
        }

        return [$field];
    }

    /**
     * Stored permissions => form state: every module, with only the actions it offers.
     */
    public static function toFormData(array $permissions, array $data = []): array
    {
        $matrix = [];

        foreach (PermissionModules::all() as $key => $module) {
            $matrix[$key] = array_values(array_intersect($module['actions'], (array) ($permissions[$key] ?? [])));
        }

        $data['permission_matrix'] = $matrix;

        return $data;
    }

    /**
     * Form state => stored permissions. Removes "permission_matrix" from $data.
     * - Locked modules always get every action.
     * - Actions you don't have yourself keep their saved value (you can't add or remove them).
     * - Modules not in the form (e.g. not built yet) keep their saved actions.
     *
     * @param  Closure(string $module): bool|null  $isLocked
     * @return array<string, array<string>>
     */
    public static function fromFormData(array &$data, array $existing = [], ?Closure $isLocked = null): array
    {
        $matrix = (array) ($data['permission_matrix'] ?? []);
        $permissions = $existing;

        foreach (PermissionModules::all() as $key => $module) {
            if ($isLocked !== null && $isLocked($key)) {
                $permissions[$key] = $module['actions'];
            } elseif (array_key_exists($key, $matrix)) {
                $grantable = static::grantable($key);
                $chosen = array_intersect((array) $matrix[$key], $grantable);
                $kept = array_diff((array) ($existing[$key] ?? []), $grantable);

                $permissions[$key] = array_values(array_intersect($module['actions'], [...$chosen, ...$kept]));
            }
        }

        unset($data['permission_matrix']);

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
}
