<?php

namespace App\Filament\Contracts;

use Modules\Users\Enums\PermissionAction;

/**
 * A resource or page whose access is controlled by role permissions. Every class that implements it is
 * discovered automatically and shown in the role form and on the Permission Modules page.
 */
interface HasPermissionModule
{
    /**
     * Key stored on roles, e.g. "lookups".
     */
    public static function getPermissionKey(): string;

    /**
     * Group key in the role form, e.g. "system_configuration" (label: admin.navigation.<group>).
     */
    public static function getPermissionGroup(): string;

    /**
     * @return array<PermissionAction>
     */
    public static function getPermissionActions(): array;

    public static function getPermissionLabel(): string;
}
