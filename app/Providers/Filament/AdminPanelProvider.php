<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use App\Filament\Widgets\MyTasksWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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
            ->path('admin')
            ->login()
            ->brandName('PiShift Command Center')
            ->favicon(null)
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('15rem')
            ->navigationGroups(['Projects', 'People', 'Settings'])
            ->renderHook('panels::head.end', fn () => "
                <style>
                    /* Full-width — override any remaining max-width */
                    .fi-main { max-width: 100% !important; padding-left: 1rem !important; padding-right: 1rem !important; }
                    /* Tighter dashboard grid */
                    .fi-wi { gap: 0.75rem !important; }
                    /* Tight section header and body */
                    .fi-section-header-ctn { padding: 0.5rem 1rem !important; }
                    /* Compact stats widget */
                    .fi-wi-stats-overview-stat { padding: 0.625rem 0.875rem !important; }
                    .fi-wi-stats-overview-stat-label { font-size: 0.7rem !important; }
                    .fi-wi-stats-overview-stat-value { font-size: 1.1rem !important; }
                </style>
            ")
            ->colors([
                'primary' => Color::hex('#D97757'),
                'gray'    => Color::hex('#5c5c5a'),
            ])
            ->font('Inter')
            ->theme(asset('css/filament/admin/theme.css'))
            ->maxContentWidth(MaxWidth::Full)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
                StatsOverviewWidget::class,
                MyTasksWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\RequirePanelAccess::class,
            ])
            ->authGuard('web');
    }
}
