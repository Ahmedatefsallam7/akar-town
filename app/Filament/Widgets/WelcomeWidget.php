<?php

namespace App\Filament\Widgets;

use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Modules\GeneralSettings\Settings\SiteSettings;
use Modules\Users\Enums\PermissionAction;

/**
 * Dashboard header: "Welcome back, {name}", and the website's status (Online / Offline), which users who can edit
 * General Settings can switch (as in Dastan).
 */
class WelcomeWidget extends Widget
{
    protected string $view = 'filament.widgets.welcome';

    protected static ?int $sort = -3;

    protected int|string|array $columnSpan = 'full';

    /** Shown right away (it's small and the first thing on the page). */
    protected static bool $isLazy = false;

    public function isSiteLive(): bool
    {
        return app(SiteSettings::class)->is_live;
    }

    public function canSwitchSite(): bool
    {
        return (bool) filament()->auth()->user()?->hasPermission('general_settings', PermissionAction::Edit);
    }

    public function toggleSite(): void
    {
        abort_unless($this->canSwitchSite(), 403);

        $settings = app(SiteSettings::class);
        $settings->is_live = ! $settings->is_live;
        $settings->save();

        Notification::make()
            ->success()
            ->title($settings->is_live ? __('admin.site_status.now_online') : __('admin.site_status.now_offline'))
            ->send();
    }
}
