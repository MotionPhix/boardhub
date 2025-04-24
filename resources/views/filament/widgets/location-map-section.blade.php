<x-filament-widgets::widget>
  <x-filament::section>
    {{
        Dotswan\MapPicker\Infolists\MapEntry::make('locationsData')
            ->columnSpanFull()
            ->defaultLocation(-13.9626, 33.7741)
            ->draggable(false)
            ->clickable(false)
            ->zoom(7)
            ->minZoom(0)
            ->maxZoom(18)
            ->detectRetina(true)
            ->showMarker(true)
            ->markerColor('#3b82f6')
            ->showFullscreenControl(true)
            ->showZoomControl(true)
            ->extraStyles([
                'min-height: 400px',
                'border-radius: 0.75rem'
            ])
            ->render()
    }}
  </x-filament::section>
</x-filament-widgets::widget>
