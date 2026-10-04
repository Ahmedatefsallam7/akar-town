<?php

namespace App\Filament\Widgets;

use App\Filament\Support\PanelResources;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * One number per section the user can open (the same cached counts as the sidebar), each linking to its list.
 */
class QuickStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected ?string $pollingInterval = null;

    /** Rendered with the page: the counts are already cached, and a lazy widget costs a whole extra request. */
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return collect(PanelResources::visible())
            ->map(fn (string $resource) => Stat::make($resource::getPluralModelLabel(), $resource::getNavigationBadge() ?? '—')
                ->icon($resource::getNavigationIcon())
                ->description(__('admin.dashboard.open_list'))
                // Points toward the reading direction (left in Arabic).
                ->descriptionIcon(__('filament-panels::layout.direction') === 'rtl' ? 'heroicon-m-arrow-small-left' : 'heroicon-m-arrow-small-right')
                ->url($resource::getUrl('index')))
            ->all();
    }
}
