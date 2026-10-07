<?php

namespace Modules\Units\Filament\Widgets;

use App\Filament\Support\DistributionChart;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Modules\Lookups\Support\LookupOptions;
use Modules\Units\Filament\Resources\UnitResource;
use Modules\Units\Models\Unit;

/**
 * Dashboard "Property Category Distribution": properties per property type. Cached for 5 minutes, per language.
 */
class UnitsByTypeChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return UnitResource::canViewAny();
    }

    public function getHeading(): string
    {
        return __('admin.dashboard.charts.inventory');
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $counts = Cache::remember('dashboard_units_by_type_'.app()->getLocale(), 300, fn () => Unit::query()
            ->selectRaw('property_type_id, count(*) as total')
            ->groupBy('property_type_id')
            ->orderByDesc('total')
            ->pluck('total', 'property_type_id')
            ->mapWithKeys(fn (int $total, $type) => [($type ? LookupOptions::find('unittype', (int) $type)?->getLocalized('name') : null) ?: __('admin.dashboard.charts.no_type') => $total])
            // A plain array: the cache doesn't restore objects (cache.serializable_classes = false).
            ->all());

        return DistributionChart::data(collect($counts), __('admin.units.plural'));
    }
}
