<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\InteractsWithPermissionModule;
use App\Filament\Contracts\HasPermissionModule;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Modules\Users\Enums\PermissionAction;

class Utilities extends Page implements HasPermissionModule
{
    use InteractsWithPermissionModule;

    protected static ?string $permissionKey = 'utilities';

    protected static ?string $permissionGroup = 'system_configuration';

    /** The page only runs maintenance actions, so it offers view only. */
    protected static array $permissionActions = [PermissionAction::View];

    protected string $view = 'filament.pages.utilities';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.system_configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.utilities');
    }

    public function getTitle(): string
    {
        return __('admin.utilities.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission(static::getPermissionKey()) ?? false;
    }

    /**
     * Clear every cached value (sidebar counts, lists, stats) so they are rebuilt from the database.
     */
    public function refreshAppListsAction(): Action
    {
        return Action::make('refreshAppLists')
            ->requiresConfirmation()
            ->modalHeading(__('admin.utilities.refresh_lists'))
            ->modalDescription(__('admin.utilities.refresh_lists_confirm'))
            ->modalIcon('heroicon-o-sparkles')
            ->action(function () {
                Cache::flush();

                Notification::make()
                    ->success()
                    ->title(__('admin.utilities.notifications.lists_refreshed'))
                    ->send();

                $this->redirect(static::getUrl());
            });
    }

    /**
     * Permissions are read from the role on every request, so reloading rebuilds the sidebar with the latest ones.
     */
    public function refreshPermissionsAction(): Action
    {
        return Action::make('refreshPermissions')
            ->requiresConfirmation()
            ->modalHeading(__('admin.utilities.refresh_permissions'))
            ->modalDescription(__('admin.utilities.refresh_permissions_confirm'))
            ->modalIcon('heroicon-o-shield-check')
            ->action(function () {
                Cache::forget('badge_count_roles');
                Cache::forget('badge_count_user_all');
                Cache::forget('badge_count_user_non_admin');
                auth()->user()->load('role');

                Notification::make()
                    ->success()
                    ->title(__('admin.utilities.notifications.permissions_refreshed'))
                    ->send();

                $this->redirect(static::getUrl());
            });
    }
}
