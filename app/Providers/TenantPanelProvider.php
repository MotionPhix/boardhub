<?php

namespace App\Providers;

use App\Filament\Pages\Dashboard;
use App\Http\Middleware\EnsureUserIsActive;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

class TenantPanelProvider extends PanelProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tenant')
            ->path('t/{tenant:uuid}/admin')
            ->login()
            ->colors([
                'primary' => '#6366f1',
                'danger' => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'info' => Color::Blue,
            ])
            ->brandName(fn() => app()->bound('tenant') ? app('tenant')->name : 'AdPro')
            ->sidebarCollapsibleOnDesktop()
            ->favicon(asset('images/favicon.png'))
            ->font('Lekton', provider: GoogleFontProvider::class)
            ->assets([
                Css::make('custom-stylesheet', resource_path('css/custom.css')),
            ])
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\\Filament\\Tenant\\Resources')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\\Filament\\Tenant\\Pages')
            ->discoverWidgets(in: app_path('Filament/Tenant/Widgets'), for: 'App\\Filament\\Tenant\\Widgets')
            ->pages([
                Dashboard::class,
            ])
            ->spa()
            ->maxContentWidth(MaxWidth::FiveExtraLarge)
            ->databaseNotifications()
            ->broadcasting()
            ->unsavedChangesAlerts()
            ->plugins([
                FilamentApexChartsPlugin::make(),
                FilamentShieldPlugin::make()
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
                'resolve.tenant', // Add tenant resolution
                EnsureUserIsActive::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->passwordReset()
            ->emailVerification()
            ->profile();
    }
}
