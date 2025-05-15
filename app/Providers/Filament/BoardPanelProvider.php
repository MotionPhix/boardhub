<?php

namespace App\Providers\Filament;

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
use App\Filament\Pages\Dashboard;

class BoardPanelProvider extends PanelProvider
{
  public function panel(Panel $panel): Panel
  {
    return $panel
      ->default()
      ->id('admin')
      ->path('')
      ->login()
      ->colors([
        'primary' => '#6366f1',
        'danger' => Color::Rose,
        'success' => Color::Emerald,
        'warning' => Color::Orange,
        'info' => Color::Blue,
      ])
      ->brandName('BoardHub')
      ->sidebarCollapsibleOnDesktop()
      ->favicon(asset('images/favicon.png'))
      ->font('Geist Mono', provider: GoogleFontProvider::class)
      ->assets([
        Css::make('custom-stylesheet', resource_path('css/custom.css')),
      ])
      ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
      ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
      ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
      ->pages([
        Dashboard::class,
      ])
      ->spa()
      ->maxContentWidth(MaxWidth::FiveExtraLarge)
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
      ])
      ->authMiddleware([
        Authenticate::class,
      ])
      ->authGuard('web')
//      ->registration()
      ->passwordReset()
      ->emailVerification()
      ->profile();
  }
}
