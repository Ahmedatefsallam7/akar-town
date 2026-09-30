<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetLocale;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Nwidart\Modules\Facades\Module;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName(fn (): string => __(config('app.name')))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->sidebarCollapsibleOnDesktop()
            ->login()
            ->registration()
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.tab-title')->render(),
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => view('filament.language-switcher')->render(),
            )
            ->userMenuItems([
                'profile' => fn (Action $action) => $action
                    ->label(fn (): HtmlString => new HtmlString(view('filament.user-menu-profile')->render()))
                    ->icon(null),
            ])
            ->colors([
                'primary' => Color::Violet,
                'gray' => Color::Slate,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
            ])
            ->font('Cairo')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        $this->discoverModules($panel);

        return $panel;
    }

    /**
     * Register the Filament resources, pages and widgets of every enabled module.
     */
    protected function discoverModules(Panel $panel): void
    {
        foreach (Module::allEnabled() as $module) {
            $namespace = 'Modules\\'.$module->getStudlyName().'\\Filament';

            $panel
                ->discoverResources(in: $module->getExtraPath('app/Filament/Resources'), for: $namespace.'\\Resources')
                ->discoverPages(in: $module->getExtraPath('app/Filament/Pages'), for: $namespace.'\\Pages')
                ->discoverWidgets(in: $module->getExtraPath('app/Filament/Widgets'), for: $namespace.'\\Widgets');
        }
    }
}
