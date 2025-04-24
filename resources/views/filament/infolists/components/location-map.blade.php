<div class="rounded-lg border border-gray-300 p-4">
  <div
    wire:ignore
    data-map-component
    class="h-[400px] w-full rounded-lg"
    data-latitude="{{ $getRecord()?->latitude ?? -13.9626 }}"
    data-longitude="{{ $getRecord()?->longitude ?? 33.7741 }}"
  ></div>
</div>

@pushOnce('styles')
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.css" />
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
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin></script>
  <script src="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const initializeMaps = () => {
        document.querySelectorAll('[data-map-component]').forEach(element => {
          try {
            new LocationMap(element, {
              defaultLat: element.dataset.latitude,
              defaultLng: element.dataset.longitude
            });
          } catch (error) {
            console.error('Error initializing map:', error);
          }
        });
      };

      // Initialize on page load
      initializeMaps();

      // Re-initialize when Livewire updates the DOM
      document.addEventListener('livewire:navigated', initializeMaps);
      document.addEventListener('livewire:initialized', initializeMaps);
    });
  </script>
@endPushOnce
