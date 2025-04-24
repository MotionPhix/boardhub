<div class="rounded-lg border border-gray-300 p-4">
  <div
    wire:ignore
    x-data="{
            lat: {{ $getRecord()?->latitude ?? -13.9626 }},
            lng: {{ $getRecord()?->longitude ?? 33.7741 }},
            zoom: 13,
            map: null,
            marker: null,
            searchControl: null,

            updateInputs(lat, lng) {
                const latInput = document.querySelector('[name=\'latitude\']');
                const lngInput = document.querySelector('[name=\'longitude\']');

                if (latInput && lngInput) {
                    latInput.value = lat.toFixed(6);
                    lngInput.value = lng.toFixed(6);

                    latInput.dispatchEvent(new Event('change'));
                    lngInput.dispatchEvent(new Event('change'));
                }
            },

            initMap() {
                // Initialize the map
                this.map = L.map($refs.map).setView([this.lat, this.lng], this.zoom);

                // Add tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(this.map);

                // Add marker
                this.marker = L.marker([this.lat, this.lng], {
                    draggable: true
                }).addTo(this.map);

                // Handle marker drag
                this.marker.on('dragend', (e) => {
                    const position = e.target.getLatLng();
                    this.updateInputs(position.lat, position.lng);
                });

                // Watch for input changes
                const latInput = document.querySelector('[name=\'latitude\']');
                const lngInput = document.querySelector('[name=\'longitude\']');

                if (latInput && lngInput) {
                    const updateMarker = () => {
                        const lat = parseFloat(latInput.value);
                        const lng = parseFloat(lngInput.value);

                        if (!isNaN(lat) && !isNaN(lng)) {
                            this.marker.setLatLng([lat, lng]);
                            this.map.setView([lat, lng]);
                        }
                    };

                    latInput.addEventListener('change', updateMarker);
                    lngInput.addEventListener('change', updateMarker);
                }

                // Add search control
                if (typeof L.Control.Geocoder === 'function') {
                    this.searchControl = L.Control.Geocoder.nominatim({
                        defaultMarkGeocode: false
                    });

                    this.searchControl.addTo(this.map);

                    this.searchControl.on('markgeocode', (e) => {
                        const { center } = e.geocode;
                        this.marker.setLatLng(center);
                        this.map.setView(center, 16);
                        this.updateInputs(center.lat, center.lng);
                    });
                }

                // Fix map display issues
                setTimeout(() => {
                    this.map.invalidateSize();
                }, 250);
            }
        }"
    x-init="initMap"
  >
    <div
      x-ref="map"
      class="h-[400px] w-full rounded-lg"
    ></div>
  </div>
</div>

@pushOnce('styles')
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
  <style>
    .leaflet-control-geocoder {
      @apply rounded-lg border border-gray-300 bg-white shadow-sm;
    }
    .leaflet-control-geocoder-form input {
      @apply rounded-lg border-gray-300 text-sm;
    }
    .leaflet-control-geocoder-alternatives {
      @apply rounded-lg border border-gray-300 bg-white shadow-sm;
    }
    .leaflet-control-geocoder-alternatives li:hover {
      @apply bg-gray-100;
    }
    .leaflet-control-attribution {
      @apply text-xs;
    }
  </style>
@endPushOnce

@pushOnce('scripts')
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
@endPushOnce
