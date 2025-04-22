<div class="rounded-lg border border-gray-300 p-4">
  <div
    x-data="{
            lat: {{ $getRecord()?->latitude ?? -13.9626 }},
            lng: {{ $getRecord()?->longitude ?? 33.7741 }},
            zoom: 13,
            initMap() {
                const map = L.map($refs.map).setView([this.lat, this.lng], this.zoom);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                const marker = L.marker([this.lat, this.lng], {
                    draggable: true
                }).addTo(map);

                marker.on('dragend', function(e) {
                    const position = e.target.getLatLng();
                    document.querySelector('[name="latitude"]').value = position.lat.toFixed(6);
  document.querySelector('[name="longitude"]').value = position.lng.toFixed(6);
  });

  // Update marker position when inputs change
  const latInput = document.querySelector('[name="latitude"]');
  const lngInput = document.querySelector('[name="longitude"]');

  const updateMarker = () => {
  const lat = parseFloat(latInput.value);
  const lng = parseFloat(lngInput.value);
  if (!isNaN(lat) && !isNaN(lng)) {
  marker.setLatLng([lat, lng]);
  map.setView([lat, lng]);
  }
  };

  latInput.addEventListener('change', updateMarker);
  lngInput.addEventListener('change', updateMarker);

  // Add search control
  const searchControl = L.Control.geocoder({
  defaultMarkGeocode: false
  }).addTo(map);

  searchControl.on('markgeocode', function(e) {
  const { center, name } = e.geocode;
  marker.setLatLng(center);
  map.setView(center, 16);
  latInput.value = center.lat.toFixed(6);
  lngInput.value = center.lng.toFixed(6);
  });
  }
  }"
  x-init="initMap()"
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
  </style>
@endPushOnce

@pushOnce('scripts')
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
@endPushOnce
