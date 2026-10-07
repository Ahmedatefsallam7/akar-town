<?php

namespace App\Filament\Widgets;

use Filament\Resources\Resource;
use Filament\Widgets\Widget;
use Modules\Careers\Filament\Resources\CareerResource;
use Modules\Inquiry\Filament\Resources\InquiryResource;
use Modules\Inquiry\Filament\Resources\MeetingResource;
use Modules\Projects\Filament\Resources\ProjectResource;
use Modules\Units\Filament\Resources\UnitResource;

/**
 * Shortcuts to the lists admins open most (team lead's list; Blogs joins when that module exists). Only the ones the
 * user can open.
 */
class QuickActionsWidget extends Widget
{
    /** @var array<class-string<resource>> */
    public const RESOURCES = [ProjectResource::class, UnitResource::class, InquiryResource::class, CareerResource::class, MeetingResource::class];

    protected string $view = 'filament.widgets.quick-actions';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return static::actions() !== [];
    }

    /**
     * @return array<array{label: string, url: string, icon: string|null}>
     */
    public static function actions(): array
    {
        return collect(self::RESOURCES)
            ->filter(fn (string $resource) => $resource::canViewAny())
            ->map(fn (string $resource) => [
                'label' => $resource::getPluralModelLabel(),
                'url' => $resource::getUrl('index'),
                'icon' => $resource::getNavigationIcon(),
            ])
            ->values()
            ->all();
    }
}
