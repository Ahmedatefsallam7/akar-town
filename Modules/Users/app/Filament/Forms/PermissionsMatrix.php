<?php

namespace Modules\Users\Filament\Forms;

use Closure;
use Filament\Forms\Components\Field;
use Modules\Users\Enums\PermissionAction;
use Modules\Users\Support\PermissionModules;

/**
 * Permissions as one table: a row per module, a column per action, with "all" toggles per row and for the table.
 * State: {"<module>": ["view", "edit"]}. The save step (PermissionsGrid::fromFormData) re-checks everything.
 */
class PermissionsMatrix extends Field
{
    protected string $view = 'users::filament.forms.permissions-matrix';

    /**
     * @var Closure(mixed, string): bool|null
     */
    protected ?Closure $isLockedUsing = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('admin.roles.permissions'));
        $this->hiddenLabel();
        $this->default(fn () => PermissionsGrid::toFormData([])['permission_matrix']);
    }

    /**
     * @param  Closure(mixed $record, string $module): bool  $callback  locked modules always have every action
     */
    public function lockedUsing(Closure $callback): static
    {
        $this->isLockedUsing = $callback;

        return $this;
    }

    public function isModuleLocked(string $module): bool
    {
        return $this->isLockedUsing !== null && ($this->isLockedUsing)($this->getRecord(), $module);
    }

    /**
     * @return array<array{value: string, label: string}>
     */
    public function getActionColumns(): array
    {
        return array_map(
            fn (PermissionAction $action) => ['value' => $action->value, 'label' => $action->label()],
            PermissionAction::cases(),
        );
    }

    /**
     * Groups of modules, each with the actions it offers and the ones this user may change.
     *
     * @return array<array{label: string, modules: array<array{key: string, label: string, offered: array<string>, editable: array<string>, locked: bool}>}>
     */
    public function getGroups(): array
    {
        $groups = [];

        foreach (PermissionModules::grouped() as $group => $modules) {
            $groups[] = [
                'label' => __("admin.navigation.{$group}"),
                'modules' => array_values(array_map(function (array $module) {
                    $locked = $this->isModuleLocked($module['key']);

                    return [
                        'key' => $module['key'],
                        'label' => $module['label'],
                        'offered' => $module['actions'],
                        'editable' => $locked || $this->isDisabled() ? [] : PermissionsGrid::grantable($module['key']),
                        'locked' => $locked,
                    ];
                }, $modules)),
            ];
        }

        return $groups;
    }
}
