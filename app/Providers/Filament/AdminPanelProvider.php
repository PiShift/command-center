<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
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
            ->brandName('PiShift')
            ->brandLogo(new \Illuminate\Support\HtmlString(
                '<img src="' . asset('images/logo.svg') . '" alt="PiShift" class="wr-logo-full" style="height:26px;width:auto;">'
                . '<img src="' . asset('images/icon-wb-round.webp') . '" alt="PiShift" class="wr-logo-icon" style="height:30px;width:30px;">'
            ))
            ->favicon(asset('images/icon-wb-round.webp'))
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('13.5rem')
            ->collapsedSidebarWidth('3.75rem')
            ->navigationGroups(['Workspace'])
            ->renderHook('panels::sidebar.nav.end', fn () => view('filament.sidebar.projects'))
            ->renderHook('panels::sidebar.footer', fn () => view('filament.sidebar.footer'))
            ->renderHook('panels::head.end', fn () => view('filament.sidebar.styles'))
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
                Dashboard::class,
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
