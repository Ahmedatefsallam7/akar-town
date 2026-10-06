<?php

namespace Modules\Users\Support;

use App\Filament\Contracts\HasPermissionModule;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Schema;
use Modules\Lookups\Models\Lookup;
use Modules\Users\Enums\PermissionAction;
use Modules\Users\Models\User;

/**
 * The permission modules, discovered from the admin panel: every resource or page that implements
 * HasPermissionModule. Code is the only source, so a new module appears in the role and user forms by itself.
 */
class PermissionModules
{
    /**
     * @return array<string, array{key: string, group: string, label: string, actions: array<string>}>
     */
    public static function all(): array
    {
        return once(function () {
            $panel = Filament::getPanel('admin');
            $modules = [];

            foreach ([...$panel->getResources(), ...$panel->getPages()] as $class) {
                if (is_subclass_of($class, HasPermissionModule::class)) {
                    $modules[$class::getPermissionKey()] = [
                        'key' => $class::getPermissionKey(),
                        'group' => $class::getPermissionGroup(),
                        'label' => $class::getPermissionLabel(),
                        'actions' => array_map(fn (PermissionAction $action) => $action->value, $class::getPermissionActions()),
                    ];
                }
            }

            return $modules;
        });
    }

    /**
     * Modules by group, groups in the sidebar's order.
     *
     * @return array<string, array<string, array{key: string, group: string, label: string, actions: array<string>}>>
     */
    public static function grouped(): array
    {
        $order = array_flip(AdminPanelProvider::NAVIGATION_GROUPS);

        return collect(static::all())
            ->groupBy('group', preserveKeys: true)
            ->sortBy(fn ($modules, string $group) => $order[$group] ?? PHP_INT_MAX)
            ->map->all()
            ->all();
    }

    /**
     * Actions a module offers, e.g. ["view", "create", "edit", "delete", "bulk"]. Unknown module = none.
     *
     * @return array<string>
     */
    public static function actionsFor(string $key): array
    {
        return static::all()[$key]['actions'] ?? [];
    }

    /**
     * Every module with every action: what a new Admin role gets.
     *
     * @return array<string, array<string>>
     */
    public static function everything(): array
    {
        return array_map(fn (array $module) => $module['actions'], static::all());
    }

    /**
     * Gives the Admin role, once, every module it has never been offered (a module added in a new release), with
     * every action. The role remembers the modules it was offered ("offered_modules"), so a module the admin
     * switched off stays off. Runs after every `migrate` (see UsersServiceProvider).
     */
    public static function grantNewModulesToAdmin(): void
    {
        if (! Schema::hasTable('lookups')) {
            return;
        }

        $admin = Lookup::roles()->where('slug', User::ADMIN_ROLE)->first();

        if (! $admin) {
            return;
        }

        $attributes = $admin->getAttribute('attributes') ?? [];
        // Before this list existed, the modules already on the role count as offered.
        $offered = $attributes['offered_modules'] ?? array_keys($attributes['permissions'] ?? []);
        $new = array_diff(array_keys(static::all()), $offered);

        if ($new === [] && isset($attributes['offered_modules'])) {
            return;
        }

        foreach ($new as $module) {
            $attributes['permissions'][$module] = static::actionsFor($module);
        }

        $attributes['offered_modules'] = array_values(array_unique([...$offered, ...$new]));
        $admin->update(['attributes' => $attributes]);
    }
}
