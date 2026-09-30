<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class Utilities extends Page
{
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
        return auth()->user()?->hasModulePermission('utilities') ?? false;
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
                Cache::forget('badge_count_user');
                auth()->user()->load('role');

                Notification::make()
                    ->success()
                    ->title(__('admin.utilities.notifications.permissions_refreshed'))
                    ->send();

                $this->redirect(static::getUrl());
            });
    }
}
