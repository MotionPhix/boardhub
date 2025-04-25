<x-filament-widgets::widget>
  <div x-data="{
        billboards: @js($this->getBillboardsData()),
        map: null,
        markers: [],

        init() {
            this.map = L.map($refs.map).setView([-13.9626, 33.7741], 7);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.map);

            this.billboards.forEach(billboard => {
                const marker = L.marker([billboard.lat, billboard.lng])
                    .addTo(this.map)
                    .bindPopup(`
                        <div class='p-2'>
                            <h3 class='font-bold'>${billboard.name}</h3>
                            <p class='text-sm'>${billboard.location}</p>
                            <p class='text-sm'>Status:
                                <span class='${billboard.status === 'Available' ? 'text-green-600' : 'text-red-600'}'>
                                    ${billboard.status}
                                </span>
                            </p>
                            <p class='text-sm'>Price: MK ${billboard.price.toLocaleString()}</p>
                        </div>
                    `);

                this.markers.push(marker);
            });
        }
    }" wire:ignore>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
      <h3 class="text-lg font-bold mb-4">Billboard Locations</h3>
      <div x-ref="map" style="height: 400px;" class="rounded-lg"></div>
    </div>
  </div>
</x-filament-widgets::widget>
