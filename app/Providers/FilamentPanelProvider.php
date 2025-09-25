<?php

namespace App\Providers;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

class FilamentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->resources([
                \App\Filament\Resources\UserResource::class,
                \App\Filament\Resources\BillboardResource::class,
            ])
            ->middleware([
                'web',
            ])
            ->authMiddleware([
                'auth',
            ])
            ->brandName('BoardHub')
            ->topNavigation()
            ->maxContentWidth('5xl');
    }
}
