document.addEventListener('DOMContentLoaded', () => {
  const root = document.querySelector('[data-emergency-root]');
  if (!root) return;

  const startButton = root.querySelector('[data-start-emergency]');
  const formPanel = root.querySelector('[data-emergency-form]');
  const form = root.querySelector('#emergencyIncidentForm');
  const statusNode = root.querySelector('[data-emergency-status]');
  const submitUrl = root.dataset.submitUrl;
  const latitudeField = form?.querySelector('[name="incident_latitude"]');
  const longitudeField = form?.querySelector('[name="incident_longitude"]');
  const fallbackPanel = root.querySelector('[data-location-fallback]');
  const fallbackMapNode = root.querySelector('[data-location-map]');
  const fallbackSelectedNode = root.querySelector('[data-location-selected]');

  let leafletLoader = null;
  let fallbackMap = null;
  let fallbackMarker = null;

  const hasLocation = () => {
    const latitude = Number(latitudeField?.value || 0);
    const longitude = Number(longitudeField?.value || 0);
    return Number.isFinite(latitude) && Number.isFinite(longitude) && !(latitude === 0 && longitude === 0);
  };

  const setLocation = (latitude, longitude, message) => {
    latitudeField.value = latitude;
    longitudeField.value = longitude;
    if (fallbackSelectedNode) {
      fallbackSelectedNode.textContent = `Selected location: ${Number(latitude).toFixed(6)}, ${Number(longitude).toFixed(6)}`;
    }
    if (message) {
      HealthFirst.setNotice(statusNode, message, 'success');
    }
  };

  const loadLeaflet = async () => {
    if (window.L) {
      return window.L;
    }
    if (leafletLoader) {
      return leafletLoader;
    }

    leafletLoader = new Promise((resolve, reject) => {
      if (!document.querySelector('link[data-leaflet]')) {
        const css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        css.dataset.leaflet = '1';
        document.head.appendChild(css);
      }

      const script = document.createElement('script');
      script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
      script.onload = () => resolve(window.L);
      script.onerror = () => reject(new Error('Map could not be loaded.'));
      document.head.appendChild(script);
    });

    return leafletLoader;
  };

  const ensureFallbackMap = async () => {
    const L = await loadLeaflet();
    if (!fallbackMapNode) {
      throw new Error('Location map container not found.');
    }

    if (!fallbackMap) {
      fallbackMap = L.map(fallbackMapNode).setView([6.9271, 79.8612], 12);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
      }).addTo(fallbackMap);

      fallbackMap.on('click', (event) => {
        const { lat, lng } = event.latlng;
        if (fallbackMarker) {
          fallbackMarker.setLatLng(event.latlng);
        } else {
          fallbackMarker = L.marker(event.latlng).addTo(fallbackMap);
        }
        setLocation(lat, lng, 'Map location selected. You can now submit the emergency report.');
      });
    }

    window.setTimeout(() => fallbackMap.invalidateSize(), 50);
  };

  const showFallbackMap = async (message) => {
    fallbackPanel?.classList.remove('hidden');
    if (fallbackSelectedNode && !hasLocation()) {
      fallbackSelectedNode.textContent = 'No map location selected yet.';
    }
    HealthFirst.setNotice(statusNode, message || 'Tap the accident spot on the map to capture location.', 'error');
    try {
      await ensureFallbackMap();
    } catch (error) {
      HealthFirst.setNotice(statusNode, error.message || 'Map could not be loaded.', 'error');
    }
  };

  const captureLocation = async ({ showReadyNotice = true } = {}) => {
    const position = await HealthFirst.getCurrentPosition();
    setLocation(position.coords.latitude, position.coords.longitude);
    fallbackPanel?.classList.add('hidden');
    if (showReadyNotice) {
      HealthFirst.setNotice(statusNode, 'Location captured. Please attach a clear accident photo and submit.', 'success');
    }
    return position;
  };

  const requestLocationFromUser = async () => {
    HealthFirst.setNotice(statusNode, 'Allow location access so the nearest registered hospital can be selected.');
    try {
      await captureLocation();
      return true;
    } catch (error) {
      await showFallbackMap(error.message || 'Location could not be captured.');
      return false;
    }
  };

  startButton?.addEventListener('click', async () => {
    formPanel?.classList.remove('hidden');
    startButton.classList.add('hidden');
    await requestLocationFromUser();
  });

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!hasLocation()) {
      const granted = await requestLocationFromUser();
      if (!granted || !hasLocation()) {
        await showFallbackMap('Location is required before submitting the emergency report.');
        return;
      }
    }

    HealthFirst.setNotice(statusNode, 'Submitting emergency report...');

    try {
      const payload = await HealthFirst.postForm(submitUrl, form);
      const message = payload.message || 'Emergency report submitted successfully.';
      HealthFirst.setNotice(statusNode, message, payload.status === 'rejected' ? 'error' : 'success');
      form.reset();
    } catch (error) {
      HealthFirst.setNotice(statusNode, error.message, 'error');
    }
  });
});
