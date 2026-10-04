<?php

namespace App\Filament\Widgets;

use App\Filament\Support\PanelResources;
use Filament\Widgets\Widget;

/**
 * Dashboard greeting with the user's role and one-click "Add" shortcuts for what they may create.
 */
class WelcomeWidget extends Widget
{
    protected string $view = 'filament.widgets.welcome';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    /** Shown right away (it's small and the first thing on the page). */
    protected static bool $isLazy = false;

    /**
     * @return array<array{label: string, url: string, icon: string|null}>
     */
    public function getShortcuts(): array
    {
        return collect(PanelResources::visible())
            ->filter(fn (string $resource) => $resource::hasPage('create') && $resource::canCreate())
            ->map(fn (string $resource) => [
                'label' => __('admin.actions.create_title', ['name' => $resource::getModelLabel()]),
                'url' => $resource::getUrl('create'),
                'icon' => $resource::getNavigationIcon(),
            ])
            ->values()
            ->all();
    }
}
