<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Str;
use Modules\Users\Enums\PermissionAction;

/**
 * Default HasPermissionModule implementation. A class can set, without declaring them here:
 * - protected static ?string $permissionKey   (default: from the class name, e.g. LookupResource => "lookups")
 * - protected static ?string $permissionGroup (default: "other")
 * - protected static array $permissionActions (default: every PermissionAction)
 */
trait InteractsWithPermissionModule
{
    public static function getPermissionKey(): string
    {
        return static::$permissionKey ?? static::$transKey ?? Str::of(class_basename(static::class))
            ->replace(['Resource', 'Page'], '')
            ->snake()
            ->plural()
            ->toString();
    }

    public static function getPermissionGroup(): string
    {
        return static::$permissionGroup ?? 'other';
    }

    /**
     * @return array<PermissionAction>
     */
    public static function getPermissionActions(): array
    {
        return static::$permissionActions ?? PermissionAction::cases();
    }

    public static function getPermissionLabel(): string
    {
        return static::getNavigationLabel();
    }
}
