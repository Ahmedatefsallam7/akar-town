<?php

namespace Modules\Projects\Filament\Widgets;

use App\Filament\Support\DistributionChart;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Modules\Lookups\Support\LocationTree;
use Modules\Projects\Filament\Resources\ProjectResource;
use Modules\Projects\Models\Project;

/**
 * Dashboard "Project Distribution": projects per city (as in Dastan; the team lead may switch it to developers).
 * Cached for 5 minutes, per language.
 */
class ProjectsByCityChart extends ChartWidget
{
    protected static ?int $sort = 1;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return ProjectResource::canViewAny();
    }

    public function getHeading(): string
    {
        return __('admin.dashboard.charts.projects');
    }

    public function getDescription(): ?string
    {
        return __('admin.dashboard.charts.projects_by_city');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = Cache::remember('dashboard_projects_by_city_'.app()->getLocale(), 300, fn () => Project::query()
            ->selectRaw('city_id, count(*) as total')
            ->groupBy('city_id')
            ->orderByDesc('total')
            ->pluck('total', 'city_id')
            ->mapWithKeys(fn (int $total, $city) => [($city ? LocationTree::path([(int) $city]) : null) ?: __('admin.dashboard.charts.no_city') => $total])
            // A plain array: the cache doesn't restore objects (cache.serializable_classes = false).
            ->all());

        return DistributionChart::data(collect($counts), __('admin.projects.plural'));
    }
}
