<?php

namespace Modules\PossibleLeads\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Modules\PossibleLeads\Models\PossibleLead;

/**
 * This month on the Possible Leads page: clicks, unique visitors (one person clicking many times counts once) and
 * how many of those visitors went on to send a form. Cached for 2 minutes.
 */
class PossibleLeadsStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $from = now()->startOfMonth();

        [$clicks, $visitors, $converted] = Cache::remember('possible_leads_stats_'.$from->toDateString(), 120, function () use ($from) {
            $month = PossibleLead::query()->where('created_at', '>=', $from);

            return [
                (clone $month)->count(),
                (clone $month)->distinct()->count('visitor_id'),
                (clone $month)->converted()->distinct()->count('visitor_id'),
            ];
        });

        return [
            Stat::make(__('admin.possible_leads.stats.clicks'), number_format($clicks))
                ->description(__('admin.website_users.this_month'))
                ->icon('heroicon-o-cursor-arrow-rays'),
            Stat::make(__('admin.possible_leads.stats.visitors'), number_format($visitors))
                ->description(__('admin.possible_leads.stats.visitors_help'))
                ->icon('heroicon-o-user'),
            Stat::make(__('admin.possible_leads.stats.converted'), number_format($converted))
                ->description($visitors ? __('admin.possible_leads.stats.rate', ['rate' => round($converted / $visitors * 100, 1)]) : '—')
                ->icon('heroicon-o-check-badge'),
        ];
    }
}
