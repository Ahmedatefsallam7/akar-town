<?php

namespace Modules\GeneralSettings\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;

/**
 * "General Settings" in the sidebar: the eight settings pages, opened from tabs at the top.
 */
class GeneralSettingsCluster extends Cluster
{
    protected static ?string $slug = 'general-settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 0;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.system_configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.settings');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('admin.navigation.settings');
    }
}
