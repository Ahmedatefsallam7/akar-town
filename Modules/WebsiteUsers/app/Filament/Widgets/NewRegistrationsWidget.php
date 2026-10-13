<?php

namespace Modules\WebsiteUsers\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Modules\WebsiteUsers\Filament\Resources\WebsiteUserResource;
use Modules\WebsiteUsers\Models\WebsiteUser;

/**
 * Dashboard KPI: people who registered on the website this month, linking to them in Website Users. Shown as one of
 * the dashboard's KPI cards (KpiCardsWidget uses stat()), not as a widget of its own.
 */
class NewRegistrationsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return WebsiteUserResource::canViewAny();
    }

    protected function getStats(): array
    {
        return [static::stat()];
    }

    public static function stat(): Stat
    {
        $from = now()->startOfMonth();

        return
            // Cached for 2 minutes, like the other dashboard counts (the dashboard runs no count queries on each visit).
            Stat::make(__('admin.website_users.new_registrations'), number_format(Cache::remember(
                'website_users_registered_since_'.$from->toDateString(),
                120,
                fn () => WebsiteUser::query()->where('created_at', '>=', $from)->count(),
            )))
                ->description(__('admin.website_users.this_month'))
                ->icon('heroicon-o-user-plus')
                ->url(WebsiteUserResource::getUrl('index', ['tableFilters' => ['created_at' => ['registered_from' => $from->toDateString()]]]));
    }
}
