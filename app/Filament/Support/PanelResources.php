<?php

namespace App\Filament\Support;

use App\Filament\Contracts\HasPermissionModule;
use Filament\Facades\Filament;
use Filament\Resources\Resource;

/**
 * Admin resources the logged-in user can open, in sidebar order. Used by the dashboard, so new modules
 * (e.g. Projects) show up there by themselves.
 */
class PanelResources
{
    /**
     * @return array<class-string<resource>>
     */
    public static function visible(): array
    {
        return collect(Filament::getCurrentOrDefaultPanel()->getResources())
            ->filter(fn (string $resource) => is_subclass_of($resource, HasPermissionModule::class) && $resource::canViewAny())
            ->sortBy(fn (string $resource) => [$resource::getNavigationGroup(), $resource::getNavigationSort()])
            ->values()
            ->all();
    }
}
