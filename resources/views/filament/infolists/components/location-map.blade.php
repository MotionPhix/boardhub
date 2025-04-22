<div class="rounded-lg border border-gray-300">
  <div
    x-data="{
            lat: {{ $getRecord()?->latitude ?? -13.9626 }},
            lng: {{ $getRecord()?->longitude ?? 33.7741 }},
            zoom: 15,
            initMap() {
                const map = L.map($refs.map).setView([this.lat, this.lng], this.zoom);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                const marker = L.marker([this.lat, this.lng]).addTo(map);

                // Add all billboards for this location
                @foreach($getRecord()->billboards as $billboard)
                    L.marker([{{ $billboard->latitude }}, {{ $billboard->longitude }}], {
                        icon: L.divIcon({
                            className: 'custom-div-icon',
                            html: `<div class="marker-pin bg-{{ match($billboard->physical_status) {
                                'operational' => 'emerald',
                                'maintenance' => 'amber',
                                'damaged' => 'red',
                                default => 'gray'
                            } }}-500"></div>`,
iconSize: [30, 42],
iconAnchor: [15, 42]
})
}).addTo(map).bindPopup(`
<div class="text-sm">
  <p class="font-bold">{{ $billboard->name }}</p>
  <p>Type: {{ $billboard->type }}</p>
  <p>Size: {{ $billboard->size }}</p>
  <p>Status: {{ ucfirst($billboard->physical_status) }}</p>
  @if($billboard->current_contract)
    <p class="mt-2 text-xs text-gray-500">
      Contract: {{ $billboard->current_contract->contract_number }}
    </p>
  @endif
</div>
`);
@endforeach
}
}"
x-init="initMap()"
class="relative"
>
<div
  x-ref="map"
  class="h-[400px] w-full rounded-lg"
></div>
</div>
</div>

@pushOnce('styles')
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
  <style>
    .custom-div-icon {
      background: transparent;
      border: none;
    }
    .marker-pin {
      width: 30px;
      height: 30px;
      border-radius: 50% 50% 50% 0;
      position: absolute;
      transform: rotate(-45deg);
      left: 50%;
      top: 50%;
      margin: -15px 0 0 -15px;
    }
    .marker-pin::after {
      content: '';
      width: 24px;
      height: 24px;
      margin: 3px 0 0 3px;
      background: #fff;
      position: absolute;
      border-radius: 50%;
    }
  </style>
@endPushOnce

@pushOnce('scripts')
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endPushOnce
