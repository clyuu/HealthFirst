document.addEventListener('DOMContentLoaded', () => {
  const listNode = document.getElementById('patientHospitalList');
  const mapNode = document.getElementById('patientHospitalMap');
  const noticeNode = document.getElementById('patientHospitalNotice');
  const nearbyCount = document.getElementById('nearbyHospitalCount');

  function selectedLocationText(latitude, longitude, label = '') {
    const lat = Number(latitude || 0);
    const lng = Number(longitude || 0);
    if (!Number.isFinite(lat) || !Number.isFinite(lng) || (lat === 0 && lng === 0)) {
      return 'No home location selected yet.';
    }
    const prefix = label ? `${label} - ` : '';
    return `${prefix}Selected location: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
  }

  function googleEmbedUrl(latitude, longitude, label = '') {
    const query = label || `${latitude},${longitude}`;
    return `https://www.google.com/maps?q=${encodeURIComponent(query)}&ll=${encodeURIComponent(`${latitude},${longitude}`)}&z=15&output=embed`;
  }

  function googleSearchEmbedUrl(query) {
    return `https://www.google.com/maps?q=${encodeURIComponent(query)}&z=15&output=embed`;
  }

  function isValidCoordinate(latitude, longitude) {
    const lat = Number(latitude);
    const lng = Number(longitude);
    return Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180;
  }

  function projectLatLng(latitude, longitude, zoom) {
    const scale = 256 * (2 ** zoom);
    const siny = Math.min(Math.max(Math.sin((latitude * Math.PI) / 180), -0.9999), 0.9999);
    return {
      x: ((longitude + 180) / 360) * scale,
      y: (0.5 - Math.log((1 + siny) / (1 - siny)) / (4 * Math.PI)) * scale,
    };
  }

  function unprojectLatLng(point, zoom) {
    const scale = 256 * (2 ** zoom);
    const longitude = (point.x / scale) * 360 - 180;
    const latitude = (Math.atan(Math.sinh(Math.PI * (1 - (2 * point.y) / scale))) * 180) / Math.PI;
    return { latitude, longitude };
  }

  function sriLankaLocationFallback(query) {
    const normalized = (query || '').trim().toLowerCase();
    if (!normalized) {
      return null;
    }

    const places = [
      { aliases: ['colombo'], latitude: 6.9271, longitude: 79.8612, address: 'Colombo, Sri Lanka' },
      { aliases: ['galle'], latitude: 6.0329, longitude: 80.2168, address: 'Galle, Sri Lanka' },
      { aliases: ['matara'], latitude: 5.9549, longitude: 80.5550, address: 'Matara, Sri Lanka' },
      { aliases: ['kandy'], latitude: 7.2906, longitude: 80.6337, address: 'Kandy, Sri Lanka' },
      { aliases: ['negombo'], latitude: 7.2083, longitude: 79.8358, address: 'Negombo, Sri Lanka' },
      { aliases: ['jaffna'], latitude: 9.6615, longitude: 80.0255, address: 'Jaffna, Sri Lanka' },
      { aliases: ['kurunegala'], latitude: 7.4863, longitude: 80.3647, address: 'Kurunegala, Sri Lanka' },
      { aliases: ['gampaha'], latitude: 7.0873, longitude: 79.9990, address: 'Gampaha, Sri Lanka' },
      { aliases: ['kalutara'], latitude: 6.5854, longitude: 79.9607, address: 'Kalutara, Sri Lanka' },
      { aliases: ['ratnapura'], latitude: 6.7056, longitude: 80.3847, address: 'Ratnapura, Sri Lanka' },
      { aliases: ['badulla'], latitude: 6.9934, longitude: 81.0550, address: 'Badulla, Sri Lanka' },
      { aliases: ['anuradhapura'], latitude: 8.3114, longitude: 80.4037, address: 'Anuradhapura, Sri Lanka' },
      { aliases: ['trincomalee'], latitude: 8.5874, longitude: 81.2152, address: 'Trincomalee, Sri Lanka' },
      { aliases: ['batticaloa'], latitude: 7.7102, longitude: 81.6924, address: 'Batticaloa, Sri Lanka' },
      { aliases: ['hambantota'], latitude: 6.1241, longitude: 81.1185, address: 'Hambantota, Sri Lanka' },
      { aliases: ['nuwara eliya', 'nuwaraeliya'], latitude: 6.9497, longitude: 80.7891, address: 'Nuwara Eliya, Sri Lanka' },
      { aliases: ['polonnaruwa'], latitude: 7.9403, longitude: 81.0188, address: 'Polonnaruwa, Sri Lanka' },
      { aliases: ['puttalam'], latitude: 8.0362, longitude: 79.8283, address: 'Puttalam, Sri Lanka' },
      { aliases: ['chilaw'], latitude: 7.5758, longitude: 79.7953, address: 'Chilaw, Sri Lanka' },
      { aliases: ['kegalle'], latitude: 7.2513, longitude: 80.3464, address: 'Kegalle, Sri Lanka' },
      { aliases: ['monaragala'], latitude: 6.8728, longitude: 81.3507, address: 'Monaragala, Sri Lanka' },
      { aliases: ['ampara'], latitude: 7.3018, longitude: 81.6747, address: 'Ampara, Sri Lanka' },
      { aliases: ['vavuniya'], latitude: 8.7514, longitude: 80.4971, address: 'Vavuniya, Sri Lanka' },
      { aliases: ['mannar'], latitude: 8.9810, longitude: 79.9044, address: 'Mannar, Sri Lanka' },
    ];

    const match = places.find((place) => place.aliases.some((alias) => normalized.includes(alias)));
    return match ? {
      latitude: match.latitude,
      longitude: match.longitude,
      formatted_address: match.address,
      approximate: true,
    } : null;
  }

  function syncDropControls(picker) {
    const active = picker._locationDropMode === true;
    const toggle = picker.querySelector('[data-location-drop-toggle]');
    picker.querySelectorAll('[data-location-fallback-drop]').forEach((layer) => {
      layer.hidden = !active;
      layer.disabled = !active;
    });
    picker.querySelector('[data-location-map]')?.classList.toggle('is-drop-active', active);

    if (toggle) {
      toggle.classList.toggle('is-active', active);
      toggle.setAttribute('aria-pressed', active ? 'true' : 'false');
      toggle.textContent = active ? 'Cancel drop' : 'Drop pin';
    }
  }

  function setDropMode(picker, enabled, showNotice = true) {
    const notice = picker.querySelector('[data-location-notice]');
    const canDrop = Boolean(picker.querySelector('[data-location-fallback-drop]'));
    if (enabled && !canDrop) {
      HealthFirst.setNotice(notice, 'Search result is preview only. Try another search or use current location first.', 'error');
      return;
    }

    picker._locationDropMode = enabled;
    syncDropControls(picker);
    if (showNotice && enabled) {
      HealthFirst.setNotice(notice, 'Drop pin mode is on. Click the exact place on the map to save it.', 'success');
    }
  }

  function renderFallbackMap(picker, latitude, longitude, label = '') {
    const previewNode = picker.querySelector('[data-location-preview]');
    if (!previewNode || !isValidCoordinate(latitude, longitude)) {
      return;
    }

    const lat = Number(latitude);
    const lng = Number(longitude);
    const zoom = 15;
    picker._locationFallbackState = { center: { latitude: lat, longitude: lng }, zoom };
    previewNode.innerHTML = `
      <div class="location-map-shell">
        <iframe title="Selected Google map location" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="${googleEmbedUrl(lat, lng, label)}"></iframe>
        <span class="location-map-pin" aria-hidden="true"></span>
        <button class="location-map-drop-layer" type="button" data-location-fallback-drop aria-label="Select this spot on the map" hidden disabled></button>
      </div>
    `;
    bindFallbackMap(picker);
    syncDropControls(picker);
  }

  function renderSearchPreviewOnly(picker, query) {
    const previewNode = picker.querySelector('[data-location-preview]');
    if (!previewNode) {
      return;
    }

    previewNode.innerHTML = `
      <div class="location-map-shell">
        <iframe title="Google map search preview" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="${googleSearchEmbedUrl(query)}"></iframe>
      </div>
    `;
    syncDropControls(picker);
  }

  function previewPickerLocation(picker, latitude, longitude, message, label = '') {
    const selectedNode = picker.querySelector('[data-location-selected]');
    if (!isValidCoordinate(latitude, longitude)) {
      return;
    }

    renderFallbackMap(picker, latitude, longitude, label);
    if (selectedNode) {
      const prefix = label ? `${label} - ` : '';
      selectedNode.textContent = `${prefix}Preview ready. Click Drop pin, then click the map to save.`;
    }
    if (message) {
      HealthFirst.setNotice(picker.querySelector('[data-location-notice]'), message, 'success');
    }
  }

  function fallbackDropCoordinates(picker, event) {
    const mapNode = picker.querySelector('[data-location-map]');
    const state = picker._locationFallbackState;
    if (!mapNode || !state?.center || !isValidCoordinate(state.center.latitude, state.center.longitude)) {
      return null;
    }

    const rect = mapNode.getBoundingClientRect();
    const centerPoint = projectLatLng(state.center.latitude, state.center.longitude, state.zoom);
    return unprojectLatLng({
      x: centerPoint.x + event.clientX - (rect.left + rect.width / 2),
      y: centerPoint.y + event.clientY - (rect.top + rect.height / 2),
    }, state.zoom);
  }

  function bindFallbackMap(picker) {
    const dropLayer = picker.querySelector('[data-location-fallback-drop]');
    if (!dropLayer || dropLayer.dataset.bound === '1') {
      return;
    }

    dropLayer.dataset.bound = '1';
    dropLayer.addEventListener('click', (event) => {
      if (picker._locationDropMode !== true) {
        return;
      }
      const coordinates = fallbackDropCoordinates(picker, event);
      if (!coordinates) {
        HealthFirst.setNotice(
          picker.querySelector('[data-location-notice]'),
          'Map is still loading. Please try again in a moment.',
          'error'
        );
        return;
      }
      setPickerLocation(picker, coordinates.latitude, coordinates.longitude, 'Dropped pin selected.');
      setDropMode(picker, false, false);
    });

    dropLayer.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter' && event.key !== ' ') {
        return;
      }
      event.preventDefault();
      if (picker._locationDropMode !== true) {
        return;
      }
      const state = picker._locationFallbackState;
      if (state?.center) {
        setPickerLocation(picker, state.center.latitude, state.center.longitude, 'Map center selected.');
        setDropMode(picker, false, false);
      }
    });
  }

  function updateInteractiveMap(picker, latitude, longitude) {
    const state = picker._locationMapState;
    if (!state?.map || !state?.marker || !isValidCoordinate(latitude, longitude)) {
      return false;
    }

    const position = { lat: Number(latitude), lng: Number(longitude) };
    state.marker.setPosition(position);
    state.map.panTo(position);
    if ((state.map.getZoom?.() || 0) < 14) {
      state.map.setZoom(14);
    }
    return true;
  }

  function setPickerLocation(picker, latitude, longitude, message, label = '') {
    const latField = picker.querySelector('[data-location-lat]');
    const lngField = picker.querySelector('[data-location-lng]');
    const selectedNode = picker.querySelector('[data-location-selected]');
    if (!isValidCoordinate(latitude, longitude)) {
      return;
    }

    const lat = Number(latitude);
    const lng = Number(longitude);

    latField.value = lat.toFixed(7);
    lngField.value = lng.toFixed(7);
    if (selectedNode) {
      selectedNode.textContent = selectedLocationText(lat, lng, label);
    }
    if (!updateInteractiveMap(picker, lat, lng)) {
      renderFallbackMap(picker, lat, lng, label);
    }
    if (message) {
      HealthFirst.setNotice(picker.querySelector('[data-location-notice]'), message, 'success');
    }
  }

  function showSearchPreviewOnly(picker, query, message) {
    const selectedNode = picker.querySelector('[data-location-selected]');

    picker._locationFallbackState = null;
    setDropMode(picker, false, false);
    if (selectedNode) {
      selectedNode.textContent = `Preview only: ${query}. Exact coordinates were not saved.`;
    }
    renderSearchPreviewOnly(picker, query);
    HealthFirst.setNotice(picker.querySelector('[data-location-notice]'), message, 'error');
  }

  async function searchGoogleLocation(query) {
    const response = await fetch(`${HealthFirst.baseUrl}/api/location/search?query=${encodeURIComponent(query)}`);
    const payload = await response.json();
    if (!response.ok) {
      throw new Error(payload.error || 'No matching Google Maps location found.');
    }
    return payload.location;
  }

  async function browserGeocode(query, apiKey) {
    const maps = await HealthFirst.loadGoogleMaps(apiKey);
    if (!maps?.Geocoder) {
      throw new Error('Google Maps geocoder is unavailable.');
    }

    return new Promise((resolve, reject) => {
      const geocoder = new maps.Geocoder();
      geocoder.geocode({ address: query, componentRestrictions: { country: 'LK' } }, (results, status) => {
        const result = results?.[0];
        if (status !== 'OK' || !result?.geometry?.location) {
          reject(new Error('No matching Google Maps location found.'));
          return;
        }

        resolve({
          latitude: result.geometry.location.lat(),
          longitude: result.geometry.location.lng(),
          formatted_address: result.formatted_address || query,
        });
      });
    });
  }

  function initLocationPicker(picker) {
    if (!picker || picker.dataset.locationPickerBound === '1') {
      return;
    }

    picker.dataset.locationPickerBound = '1';
    const apiKey = picker.dataset.apiKey || '';
    const searchInput = picker.querySelector('[data-location-search]');
    const searchButton = picker.querySelector('[data-location-search-button]');
    const dropToggle = picker.querySelector('[data-location-drop-toggle]');
    const currentButton = picker.querySelector('[data-location-current]');
    const notice = picker.querySelector('[data-location-notice]');
    const latField = picker.querySelector('[data-location-lat]');
    const lngField = picker.querySelector('[data-location-lng]');
    const selectedNode = picker.querySelector('[data-location-selected]');
    const defaultLat = Number(latField?.value || picker.dataset.defaultLat || 6.9271);
    const defaultLng = Number(lngField?.value || picker.dataset.defaultLng || 79.8612);

    if (selectedNode) {
      selectedNode.textContent = selectedLocationText(latField?.value, lngField?.value);
    }
    if (latField?.value && lngField?.value) {
      setPickerLocation(picker, latField.value, lngField.value);
    } else {
      renderFallbackMap(picker, defaultLat, defaultLng);
      latField.value = '';
      lngField.value = '';
      if (selectedNode) {
        selectedNode.textContent = selectedLocationText();
      }
    }

    const form = picker.closest('form');
    if (form && picker.dataset.requireLocation === '1' && picker.dataset.locationSubmitBound !== '1') {
      picker.dataset.locationSubmitBound = '1';
      form.addEventListener('submit', (event) => {
        if (!latField?.value || !lngField?.value) {
          event.preventDefault();
          HealthFirst.setNotice(notice, 'Please select your home location before creating the account.', 'error');
        }
      });
    }

    const runSearch = async () => {
      const query = searchInput?.value.trim() || '';
      if (!query) {
        HealthFirst.setNotice(notice, 'Type a location to search first.', 'error');
        return;
      }

      HealthFirst.setNotice(notice, 'Searching Google Maps...');
      try {
        let location;
        try {
          location = await searchGoogleLocation(query);
        } catch (_serverError) {
          location = await browserGeocode(query, apiKey);
        }
        previewPickerLocation(
          picker,
          location.latitude,
          location.longitude,
          'Google Maps preview is ready. Click Drop pin and choose the exact spot.',
          location.formatted_address || query
        );
      } catch (error) {
        const fallback = sriLankaLocationFallback(query);
        if (fallback) {
          previewPickerLocation(
            picker,
            fallback.latitude,
            fallback.longitude,
            'Approximate map preview is ready. Click Drop pin and choose the exact spot.',
            fallback.formatted_address || query
          );
          return;
        }
        showSearchPreviewOnly(
          picker,
          query,
          `${error.message || 'No matching location found.'} Google Maps preview is shown, but exact coordinates were not saved.`
        );
      }
    };

    searchButton?.addEventListener('click', runSearch);
    dropToggle?.addEventListener('click', () => {
      setDropMode(picker, picker._locationDropMode !== true);
    });
    searchInput?.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter') {
        return;
      }
      event.preventDefault();
      runSearch();
    });

    currentButton?.addEventListener('click', async () => {
      HealthFirst.setNotice(notice, 'Checking your current location...');
      try {
        const position = await HealthFirst.getCurrentPosition();
        setPickerLocation(
          picker,
          position.coords.latitude,
          position.coords.longitude,
          'Current location selected.',
          'Current location'
        );
      } catch (error) {
        HealthFirst.setNotice(notice, error.message || 'Current location could not be captured.', 'error');
      }
    });

    if (picker.dataset.autoCapture === '1' && (!latField?.value || !lngField?.value)) {
      HealthFirst.getCurrentPosition()
        .then((position) => {
          setPickerLocation(
            picker,
            position.coords.latitude,
            position.coords.longitude,
            'Current location detected. Search if this is not your home.',
            'Current location'
          );
        })
        .catch(() => {
          HealthFirst.setNotice(notice, 'Search your home location with Google Maps.', 'info');
        });
    }
  }

  function initVisibleLocationPickers(root = document) {
    root.querySelectorAll('[data-location-picker]').forEach((picker) => {
      if (picker.closest('.app-modal[hidden]')) {
        return;
      }
      initLocationPicker(picker);
    });
  }

  function renderHospitalCards(hospitals) {
    if (nearbyCount) {
      nearbyCount.textContent = hospitals.length.toString();
    }
    if (!listNode) {
      return;
    }
    if (!hospitals.length) {
      listNode.innerHTML = '<div class="patient-empty-card">No nearby hospitals found for the current location.</div>';
      return;
    }

    listNode.innerHTML = hospitals.map((hospital) => `
      <article class="hospital-mini-card">
        <div>
          <strong>${hospital.hospital_name}</strong>
          <p class="muted">${hospital.address}</p>
        </div>
        <div>
          <strong>${HealthFirst.formatEta(hospital.eta_seconds || 0)}</strong>
          <p class="muted">${((hospital.route_distance_meters || 0) / 1000).toFixed(1)} km</p>
        </div>
      </article>
    `).join('');
  }

  async function drawNearbyMap(latitude, longitude, hospitals) {
    if (!mapNode) {
      return;
    }
    const maps = await HealthFirst.loadGoogleMaps(mapNode.dataset.apiKey || '');
    if (!maps) {
      HealthFirst.setNotice(noticeNode, 'Google Maps API key missing. Showing ranked hospitals only.', 'info');
      return;
    }

    const map = new maps.Map(mapNode, {
      center: { lat: latitude, lng: longitude },
      zoom: 11,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: false,
    });

    new maps.Marker({
      map,
      position: { lat: latitude, lng: longitude },
      title: 'Your location',
    });

    hospitals.forEach((hospital) => {
      new maps.Marker({
        map,
        position: { lat: Number(hospital.latitude), lng: Number(hospital.longitude) },
        title: hospital.hospital_name,
      });
    });

    HealthFirst.setNotice(noticeNode, 'Nearest hospitals ranked using your current or saved location.', 'success');
  }

  async function loadHospitals(latitude, longitude) {
    const query = latitude && longitude
      ? `?latitude=${encodeURIComponent(latitude)}&longitude=${encodeURIComponent(longitude)}`
      : '';
    const response = await fetch(`${HealthFirst.baseUrl}/api/patient/nearby-hospitals${query}`);
    const payload = await response.json();
    if (!response.ok) {
      throw new Error(payload.error || 'Unable to load nearby hospitals.');
    }
    return payload.hospitals || [];
  }

  let nearbyLoaded = false;
  async function loadNearbyHospitals() {
    if (!listNode || !mapNode || nearbyLoaded) {
      return;
    }
    nearbyLoaded = true;
    HealthFirst.setNotice(noticeNode, 'Searching your nearest hospitals...', 'info');

    try {
      const position = await HealthFirst.getCurrentPosition();
      const latitude = position.coords.latitude;
      const longitude = position.coords.longitude;
      const hospitals = await loadHospitals(latitude, longitude);
      renderHospitalCards(hospitals);
      await drawNearbyMap(latitude, longitude, hospitals);
    } catch (_error) {
      try {
        const hospitals = await loadHospitals();
        renderHospitalCards(hospitals);
        HealthFirst.setNotice(noticeNode, 'Using the saved profile location because live GPS was not available.', 'info');
      } catch (error) {
        renderHospitalCards([]);
        HealthFirst.setNotice(noticeNode, error.message || 'Unable to load nearby hospitals.', 'error');
      }
    }
  }

  document.addEventListener('healthfirst:modal-open', (event) => {
    const modalId = event.detail?.modalId || '';
    const modal = document.getElementById(modalId);
    if (modal) {
      initVisibleLocationPickers(modal);
    }
    if (modalId === 'nearbyHospitalsModal') {
      loadNearbyHospitals();
    }
  });

  if (window.location.hash) {
    const hashTarget = window.location.hash.slice(1);
    window.setTimeout(() => {
      if (document.getElementById(hashTarget)?.classList.contains('app-modal')) {
        HealthFirst.openModal(hashTarget);
      }
    }, 50);
  }

  initVisibleLocationPickers(document);
});
