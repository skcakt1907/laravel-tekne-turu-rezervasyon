<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Enums\ThemeMode;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('yonetim')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->brandName('Yat Kiralama Yönetim')
            ->colors([
                // Marka: pirinç vurgu + deniz tonlu nötrler (tema CSS'i ile birlikte)
                'primary' => [
                    50 => '#fbf7ef', 100 => '#f4e8d2', 200 => '#e8d1a4', 300 => '#dab472',
                    400 => '#cd9749', 500 => '#c07f31', 600 => '#96661a', 700 => '#7a5314',
                    800 => '#634315', 900 => '#543a16', 950 => '#2f1f0a',
                ],
                'gray' => [
                    50 => '#f2f7f8', 100 => '#e2ecef', 200 => '#c6d9de', 300 => '#9cbcc5',
                    400 => '#6b98a5', 500 => '#4d7c8a', 600 => '#3f6674', 700 => '#375561',
                    800 => '#223a44', 900 => '#16303a', 950 => '#0b1f27',
                ],
                'danger' => Color::Red,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'info' => Color::Sky,
            ])
            ->defaultThemeMode(ThemeMode::Dark)
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
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
