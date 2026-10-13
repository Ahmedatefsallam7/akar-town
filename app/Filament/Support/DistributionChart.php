<?php

namespace App\Filament\Support;

use Illuminate\Support\Collection;

/**
 * Shared look for the dashboard's distribution charts: the biggest slices, the rest as "Other", one palette.
 */
class DistributionChart
{
    public const PALETTE = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#94a3b8'];

    private const SLICES = 6;

    /**
     * @param  Collection<string, int>  $counts  label => count, biggest first
     */
    public static function data(Collection $counts, string $label): array
    {
        $top = $counts->take(self::SLICES);
        $rest = $counts->skip(self::SLICES)->sum();

        if ($rest > 0) {
            $top->put(__('admin.dashboard.charts.other'), $rest);
        }

        return [
            'datasets' => [[
                'label' => $label,
                'data' => $top->values()->all(),
                'backgroundColor' => array_slice(self::PALETTE, 0, max(1, $top->count())),
                'borderWidth' => 0,
            ]],
            'labels' => $top->keys()->all(),
        ];
    }
}
