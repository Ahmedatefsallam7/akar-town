<?php

namespace App\Providers\Filament;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\SetLocale;
use Filament\Actions\Action;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\BasePage;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Users\Filament\Auth\EditProfile;
use Modules\Users\Filament\Auth\Login;
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
            // Warn before leaving a form with unsaved changes.
            ->unsavedChangesAlerts()
            // Move between pages without full reloads (a link starts loading on hover, so pages open almost instantly),
            // and open search with Ctrl/Cmd + K. Only Filament's own links are prefetched, never plain links like the
            // language switch, and all actions (delete, logout...) are buttons, not links.
            ->spa(hasPrefetching: true)
            // Sidebar sections in a fixed order (each appears once it has pages). Labels are translated per request.
            ->navigationGroups(collect(['property_management', 'marketing_content', 'inquiries_crm', 'system_configuration'])
                ->map(fn (string $group) => NavigationGroup::make(fn () => __("admin.navigation.{$group}")))
                ->all())
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->login(Login::class)
            // ->registration()
            ->profile(EditProfile::class, isSimple: false)
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
            ], isPersistent: true);

        $this->discoverModules($panel);

        return $panel;
    }

    public function boot(): void
    {
        // Save / Cancel stay visible at the bottom of long forms.
        BasePage::stickyFormActions();
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
