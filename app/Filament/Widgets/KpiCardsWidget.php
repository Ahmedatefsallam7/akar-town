<?php

namespace App\Filament\Widgets;

use App\Filament\Support\NavigationBadges;
use Filament\Resources\Resource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Careers\Filament\Resources\ApplicantResource;
use Modules\Developers\Filament\Resources\DeveloperResource;
use Modules\Inquiry\Filament\Resources\InquiryResource;
use Modules\Projects\Filament\Resources\ProjectResource;
use Modules\Units\Filament\Resources\UnitResource;
use Modules\WebsiteUsers\Filament\Widgets\NewRegistrationsWidget;

/**
 * The dashboard's KPI cards (team lead's list; "Blog Articles" joins when Blogs exists) and "New Registrations"
 * (Website Users), each linking to its list.
 * The numbers are the sidebar's cached counts, so the dashboard runs no count queries.
 */
class KpiCardsWidget extends StatsOverviewWidget
{
    /** @var array<class-string<resource>, array{label: string, color: string}> */
    private const CARDS = [
        ProjectResource::class => ['label' => 'total_projects', 'color' => 'success'],
        UnitResource::class => ['label' => 'total_properties', 'color' => 'warning'],
        InquiryResource::class => ['label' => 'contact_inquiries', 'color' => 'primary'],
        ApplicantResource::class => ['label' => 'job_applicants', 'color' => 'info'],
        DeveloperResource::class => ['label' => 'total_developers', 'color' => 'gray'],
    ];

    protected static ?int $sort = -1;

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return collect(array_keys(self::CARDS))->contains(fn (string $resource) => $resource::canViewAny()) || NewRegistrationsWidget::canView();
    }

    protected function getStats(): array
    {
        return collect(self::CARDS)
            ->filter(fn (array $card, string $resource) => $resource::canViewAny())
            ->map(fn (array $card, string $resource) => Stat::make(__("admin.dashboard.stats.{$card['label']}"), number_format((int) NavigationBadges::get($resource)))
                ->icon($resource::getNavigationIcon())
                ->color($card['color'])
                ->description(__('admin.dashboard.open_list'))
                // Points toward the reading direction (left in Arabic).
                ->descriptionIcon(__('filament-panels::layout.direction') === 'rtl' ? 'heroicon-m-arrow-small-left' : 'heroicon-m-arrow-small-right')
                ->url($resource::getUrl('index')))
            ->values()
            ->when(NewRegistrationsWidget::canView(), fn ($stats) => $stats->push(NewRegistrationsWidget::stat()))
            ->all();
    }
}
