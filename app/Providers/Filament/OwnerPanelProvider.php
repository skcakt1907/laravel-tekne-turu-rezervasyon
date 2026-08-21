<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Enums\GlobalSearchPosition;
use Filament\Enums\ThemeMode;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class OwnerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('owner')
            ->path('yat-sahibi')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->registration(\App\Filament\Owner\Pages\Auth\Register::class)
            ->passwordReset()
            ->profile(\App\Filament\Owner\Pages\Auth\EditProfile::class)
            ->brandName('Yat Sahibi Paneli')
            ->globalSearch(position: GlobalSearchPosition::Sidebar)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldKeyBindingSuffix()
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): string => view('filament.partials.sidebar-brand')->render(),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): string => view('filament.partials.sidebar-footer')->render(),
            )
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                // Marka: pirinç vurgu + deniz tonlu nötrler (tema CSS'i ile birlikte)
                'primary' => [
                    50 => '#fbf7ef', 100 => '#f4e8d2', 200 => '#e8d1a4', 300 => '#dab472',
                    400 => '#cd9749', 500 => '#c07f31', 600 => '#96661a', 700 => '#7a5314',
                    800 => '#634315', 900 => '#543a16', 950 => '#2f1f0a',
                ],
                'gray' => [
                    50 => '#f6f7f8', 100 => '#eceef0', 200 => '#d8dce0', 300 => '#b3babf',
                    400 => '#8b949b', 500 => '#6b757d', 600 => '#525b62', 700 => '#3d454b',
                    800 => '#262d32', 900 => '#171d21', 950 => '#0f1417',
                ],
                'danger' => Color::Red,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'info' => Color::Sky,
            ])
            ->defaultThemeMode(ThemeMode::Dark)
            ->discoverResources(in: app_path('Filament/Owner/Resources'), for: 'App\Filament\Owner\Resources')
            ->discoverPages(in: app_path('Filament/Owner/Pages'), for: 'App\Filament\Owner\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Owner/Widgets'), for: 'App\Filament\Owner\Widgets')
            ->widgets([
                \App\Filament\Owner\Widgets\OwnerOverview::class,
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
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
    }
}
