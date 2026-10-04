<?php

namespace Modules\Users\Enums;

/**
 * Actions a role can be allowed per module. Stored on the role as a list, e.g. ["view", "edit"].
 *
 * To add an action (e.g. restore): add a case, give it a label in lang/{en,ar}/admin.php
 * (admin.roles.actions.<value>), and point the matching Filament abilities to it in forAbility().
 */
enum PermissionAction: string
{
    case View = 'view';
    case Create = 'create';
    case Edit = 'edit';
    case Delete = 'delete';
    case Bulk = 'bulk';

    public function label(): string
    {
        return __("admin.roles.actions.{$this->value}");
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Actions needed for a Filament ability (viewAny, update, deleteAny...). An unknown ability needs an action
     * nobody has, so it is denied. "updateAny" is ours, for custom bulk actions such as activate or suspend.
     *
     * @return array<self>|null
     */
    public static function forAbility(string $ability): ?array
    {
        return match ($ability) {
            'viewAny', 'view' => [self::View],
            'create', 'replicate' => [self::Create],
            'update', 'reorder' => [self::Edit],
            'delete', 'forceDelete', 'restore' => [self::Delete],
            'deleteAny', 'forceDeleteAny', 'restoreAny' => [self::Delete, self::Bulk],
            'updateAny' => [self::Edit, self::Bulk],
            default => null,
        };
    }
}
