<?php

namespace App\Filament\Traits;

use App\Filament\Concerns\InteractsWithPermissionModule;
use App\Filament\Support\NavigationBadges;
use BackedEnum;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Users\Enums\PermissionAction;
use UnitEnum;

trait HasResourceTranslations
{
    use InteractsWithPermissionModule;

    public static function getNavigationLabel(): string
    {
        return __(static::getTranslationKey('navigation'));
    }

    public static function getBreadcrumb(): string
    {
        return __(static::getTranslationKey('navigation'));
    }

    public static function getModelLabel(): string
    {
        return __(static::getTranslationKey('model_label'));
    }

    public static function getPluralModelLabel(): string
    {
        return __(static::getTranslationKey('navigation'));
    }

    public static function getNavigationBadge(): ?string
    {
        return NavigationBadges::get(static::class);
    }

    /**
     * The number shown next to the resource in the sidebar and on the dashboard. Override to count differently.
     */
    public static function getNavigationBadgeCount(): int
    {
        return static::getModel()::query()->count();
    }

    /**
     * Every Filament check (canViewAny, canEdit, the Delete button, bulk delete...) comes through here, so the
     * role's actions are enforced in one place. See PermissionAction::forAbility() for the mapping.
     */
    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $ability = match (true) {
            $action instanceof BackedEnum => (string) $action->value,
            $action instanceof UnitEnum => $action->name,
            default => $action,
        };
        $actions = PermissionAction::forAbility($ability);
        $user = auth()->user();

        $allowed = $actions !== null
            && $user !== null
            && collect($actions)->every(fn (PermissionAction $needed) => $user->hasPermission(static::getPermissionKey(), $needed))
            && ($record === null || static::canPerformOnRecord($ability, $record));

        return $allowed ? Response::allow() : Response::deny();
    }

    /**
     * Extra rules for one record, e.g. "seeded roles can't be deleted". Override in a resource.
     */
    protected static function canPerformOnRecord(string $ability, Model $record): bool
    {
        return true;
    }

    protected static function getTranslationKey(string $type): string
    {
        $key = static::$transKey ?? Str::of(class_basename(static::class))
            ->replace('Resource', '')
            ->snake()
            ->plural()
            ->toString();

        if ($type === 'model_label') {
            return "admin.{$key}.title";
        }

        return "admin.navigation.{$key}";
    }
}
