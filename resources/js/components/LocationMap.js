export default class LocationMap {
  constructor(element, options = {}) {
    this.element = element;
    this.options = {
      defaultLat: parseFloat(options.defaultLat) || -13.9626,
      defaultLng: parseFloat(options.defaultLng) || 33.7741,
      zoom: options.zoom || 13,
    };

    this.map = null;
    this.marker = null;
    this.searchControl = null;

    // Ensure Leaflet is loaded
    if (typeof L === 'undefined') {
      console.error('Leaflet is not loaded');
      return;
    }

    this.init();
  }

  init() {
    try {
      this.initializeMap();
      this.addTileLayer();
      this.addMarker();
      this.setupEventListeners();
      this.initializeGeocoder();

      // Fix map display issues
      setTimeout(() => this.map.invalidateSize(), 250);
    } catch (error) {
      console.error('Error initializing map:', error);
    }
  }

  initializeMap() {
    this.map = L.map(this.element).setView(
      [this.options.defaultLat, this.options.defaultLng],
      this.options.zoom
    );
  }

  addTileLayer() {
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap contributors'
    }).addTo(this.map);
  }

  addMarker() {
    this.marker = L.marker(
      [this.options.defaultLat, this.options.defaultLng],
      { draggable: true }
    ).addTo(this.map);
  }

  setupEventListeners() {
    // Handle marker drag
    this.marker.on('dragend', (e) => {
      const position = e.target.getLatLng();
      this.updateInputs(position.lat, position.lng);
    });

    // Watch for input changes
    const latInput = document.querySelector('[name="latitude"]');
    const lngInput = document.querySelector('[name="longitude"]');

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

      // Set initial values if they exist
      if (latInput.value && lngInput.value) {
        updateMarker();
      }
    }
  }

  initializeGeocoder() {
    // Wait for Geocoder to be available
    const checkGeocoder = setInterval(() => {
      if (typeof L.Control.Geocoder !== 'undefined') {
        clearInterval(checkGeocoder);
        this.setupGeocoder();
      }
    }, 100);

    // Timeout after 5 seconds
    setTimeout(() => {
      clearInterval(checkGeocoder);
      if (!this.searchControl) {
        console.warn('Geocoder failed to load');
      }
    }, 5000);
  }

  setupGeocoder() {
    try {
      this.searchControl = new L.Control.Geocoder({
        defaultMarkGeocode: false
      });

      this.map.addControl(this.searchControl);

      this.searchControl.on('markgeocode', (e) => {
        const { center } = e.geocode;
        this.marker.setLatLng(center);
        this.map.setView(center, 16);
        this.updateInputs(center.lat, center.lng);
      });
    } catch (error) {
      console.error('Error setting up geocoder:', error);
    }
  }

  updateInputs(lat, lng) {
    const latInput = document.querySelector('[name="latitude"]');
    const lngInput = document.querySelector('[name="longitude"]');

    if (latInput && lngInput) {
      latInput.value = lat.toFixed(6);
      lngInput.value = lng.toFixed(6);

      latInput.dispatchEvent(new Event('change'));
      lngInput.dispatchEvent(new Event('change'));
    }
  }
}
