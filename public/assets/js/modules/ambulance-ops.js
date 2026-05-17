document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('ambulanceOpsList');
  if (!root) return;

  const masterMapNode = document.getElementById('ambulanceMasterMap');
  const navBanner = document.getElementById('ambulanceNavBanner');
  const navSummary = document.getElementById('ambulanceNavSummary');
  const navNote = document.getElementById('ambulanceNavNote');
  const navStep = document.querySelector('[data-nav-step]');
  const navStepDistance = document.querySelector('[data-nav-step-distance]');
  const navArrow = document.querySelector('[data-nav-arrow]');
  const navDuration = document.querySelector('[data-nav-duration]');
  const navDistanceTotal = document.querySelector('[data-nav-distance-total]');
  const navArrival = document.querySelector('[data-nav-arrival]');
  const navTarget = document.querySelector('[data-nav-target]');
  const exitButtons = [
    document.getElementById('ambulanceNavExitTop'),
    document.getElementById('ambulanceNavExitBottom'),
  ].filter(Boolean);

  const mapsApiKey = masterMapNode?.dataset.apiKey || '';
  const feedUrl = root.dataset.feedUrl || '';
  const locationUrlTemplate = root.dataset.locationUrlTemplate || '';
  const navigationUrlTemplate = root.dataset.navigationUrlTemplate || '';

  let maps = null;
  let masterMap = null;
  let ambulanceMarker = null;
  let destinationMarker = null;
  let masterRoutePolyline = null;
  let activeIncidentId = window.sessionStorage.getItem('ambulanceActiveIncident') || '';
  let lastPosition = null;

  function formatDistance(distanceMeters) {
    const value = Math.max(Number(distanceMeters || 0), 0);
    if (value >= 1000) {
      return `${(value / 1000).toFixed(1)} km`;
    }
    return `${Math.round(value)} m`;
  }

  function formatArrival(seconds) {
    const safe = Math.max(Number(seconds || 0), 0);
    const targetTime = new Date(Date.now() + (safe * 1000));
    return targetTime.toLocaleTimeString([], {
      hour: 'numeric',
      minute: '2-digit',
    });
  }

  function getCardByIncidentId(incidentId) {
    return root.querySelector(`[data-incident-id="${incidentId}"]`);
  }

  function setNavigationActive(card) {
    root.querySelectorAll('[data-incident-card]').forEach((node) => {
      const isActive = !!card && node.dataset.incidentId === card.dataset.incidentId;
      node.classList.toggle('is-nav-active', isActive);
      const button = node.querySelector('[data-start-nav-button]');
      if (button) {
        button.textContent = isActive ? 'Navigation Active' : 'Start Navigation';
      }
    });
  }

  async function ensureMaps() {
    if (maps) {
      return maps;
    }

    maps = await HealthFirst.loadGoogleMaps(mapsApiKey);
    if (!maps || !masterMapNode) {
      return null;
    }

    masterMap = new maps.Map(masterMapNode, {
      center: { lat: 6.9271, lng: 79.8612 },
      zoom: 12,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: false,
      clickableIcons: false,
    });

    ambulanceMarker = new maps.Marker({
      map: masterMap,
      title: 'Ambulance',
      zIndex: 999,
      icon: {
        path: maps.SymbolPath.FORWARD_CLOSED_ARROW,
        scale: 6,
        fillColor: '#1f71e7',
        fillOpacity: 1,
        strokeColor: '#ffffff',
        strokeWeight: 2,
      },
    });

    destinationMarker = new maps.Marker({
      map: masterMap,
      title: 'Destination',
      zIndex: 998,
      icon: {
        path: maps.SymbolPath.CIRCLE,
        scale: 8,
        fillColor: '#ff5d4d',
        fillOpacity: 1,
        strokeColor: '#ffffff',
        strokeWeight: 2,
      },
    });

    masterRoutePolyline = new maps.Polyline({
      map: masterMap,
      strokeColor: '#4b38ff',
      strokeOpacity: 0.95,
      strokeWeight: 7,
    });

    return maps;
  }

  function decodeRoutePath(encodedPolyline, origin, destination) {
    if (encodedPolyline && maps?.geometry?.encoding) {
      return maps.geometry.encoding.decodePath(encodedPolyline);
    }

    return [
      new maps.LatLng(Number(origin.lat), Number(origin.lng)),
      new maps.LatLng(Number(destination.lat), Number(destination.lng)),
    ];
  }

  function fitMapToRoute(path) {
    if (!masterMap || !maps) return;
    const bounds = new maps.LatLngBounds();
    path.forEach((point) => bounds.extend(point));
    masterMap.fitBounds(bounds, 56);
  }

  function setNavigationPanels(card, payload) {
    if (navBanner) navBanner.hidden = false;
    if (navSummary) navSummary.hidden = false;

    if (navArrow) navArrow.textContent = '\u2197';
    if (navStep) navStep.textContent = payload.instruction || `Drive to ${payload.destination_label || 'destination'}.`;
    if (navStepDistance) navStepDistance.textContent = formatDistance(payload.distance_meters || 0);
    if (navDuration) navDuration.textContent = HealthFirst.formatEta(payload.eta_seconds || 0);
    if (navDistanceTotal) navDistanceTotal.textContent = formatDistance(payload.distance_meters || 0);
    if (navArrival) navArrival.textContent = formatArrival(payload.eta_seconds || 0);
    if (navTarget) navTarget.textContent = `${payload.destination_label || card.dataset.navTargetLabel || 'Destination'} · ${payload.patient_name || card.dataset.patientName || 'Active case'}`;

    if (navNote) {
      navNote.textContent = `${payload.ambulance_number || card.dataset.ambulanceNumber || 'Assigned ambulance'} navigating to ${payload.destination_label || card.dataset.navTargetLabel || 'the destination'}.`;
    }
  }

  function updateIncidentCard(card, incident) {
    const etaSeconds = Math.max(Number(incident.display_eta_seconds || 0), 0);
    const etaLive = Boolean(incident.eta_live);
    const isHospitalRoute = ['patient_picked_up', 'en_route_hospital'].includes(incident.status || card.dataset.status || '');
    const etaNode = card.querySelector('[data-eta-seconds]');
    const etaSummary = card.querySelector('[data-eta-summary]');
    const mapNode = card.querySelector('[data-map-card]');

    card.dataset.displayEta = `${etaSeconds}`;
    card.dataset.displayDistance = `${incident.display_distance_meters || 0}`;
    if (Object.prototype.hasOwnProperty.call(incident, 'display_route_polyline')) {
      card.dataset.routePolyline = incident.display_route_polyline || '';
    }

    if (etaNode) {
      HealthFirst.setEta(etaNode, etaSeconds, etaLive);
    }

    if (etaSummary) {
      etaSummary.textContent = `${Math.max(Math.ceil(etaSeconds / 60), 0)} min ${isHospitalRoute ? 'to hospital' : 'to scene'}`;
    }

    if (!mapNode) {
      return;
    }

    let mapChanged = false;
    if (incident.display_origin_latitude !== null && incident.display_origin_latitude !== undefined
      && incident.display_origin_longitude !== null && incident.display_origin_longitude !== undefined) {
      const nextLat = `${incident.display_origin_latitude}`;
      const nextLng = `${incident.display_origin_longitude}`;
      mapChanged = mapChanged || mapNode.dataset.originLat !== nextLat || mapNode.dataset.originLng !== nextLng;
      mapNode.dataset.originLat = nextLat;
      mapNode.dataset.originLng = nextLng;
    }

    if (incident.display_destination_latitude !== null && incident.display_destination_latitude !== undefined
      && incident.display_destination_longitude !== null && incident.display_destination_longitude !== undefined) {
      const nextLat = `${incident.display_destination_latitude}`;
      const nextLng = `${incident.display_destination_longitude}`;
      mapChanged = mapChanged || mapNode.dataset.destLat !== nextLat || mapNode.dataset.destLng !== nextLng;
      mapNode.dataset.destLat = nextLat;
      mapNode.dataset.destLng = nextLng;
    }

    if (Object.prototype.hasOwnProperty.call(incident, 'display_route_polyline')) {
      const nextPolyline = incident.display_route_polyline || '';
      mapChanged = mapChanged || mapNode.dataset.routePolyline !== nextPolyline;
      mapNode.dataset.routePolyline = nextPolyline;
    }

    if (mapChanged) {
      delete mapNode.dataset.initialized;
      mapNode.replaceChildren();
    }
  }

  async function refreshIncidentFeed() {
    if (!feedUrl) return;

    const response = await fetch(feedUrl, {
      headers: {
        'Accept': 'application/json',
      },
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(payload.error || 'Unable to refresh ambulance incident ETA.');
    }

    const liveIds = [...root.querySelectorAll('[data-incident-card]')]
      .map((card) => `${card.dataset.incidentId}:${card.dataset.status || ''}`)
      .join(',');
    const nextIds = (payload.incidents || [])
      .map((incident) => `${incident.incident_id}:${incident.status || ''}`)
      .join(',');
    if (liveIds !== nextIds) {
      window.location.reload();
      return;
    }

    (payload.incidents || []).forEach((incident) => {
      const card = getCardByIncidentId(incident.incident_id);
      if (card) {
        updateIncidentCard(card, incident);
      }
    });

    await initMiniMaps();
  }

  async function renderMasterNavigation(card, payload) {
    const mapsApi = await ensureMaps();
    if (!mapsApi || !masterMap || !ambulanceMarker || !destinationMarker || !masterRoutePolyline) {
      return;
    }

    const origin = {
      lat: Number(payload.origin_latitude),
      lng: Number(payload.origin_longitude),
    };
    const destination = {
      lat: Number(payload.destination_latitude),
      lng: Number(payload.destination_longitude),
    };

    if (!origin.lat || !origin.lng || !destination.lat || !destination.lng) {
      return;
    }

    const path = decodeRoutePath(payload.encoded_polyline || card.dataset.routePolyline || '', origin, destination);
    ambulanceMarker.setMap(masterMap);
    ambulanceMarker.setPosition(origin);
    destinationMarker.setMap(masterMap);
    destinationMarker.setPosition(destination);
    masterRoutePolyline.setMap(masterMap);
    masterRoutePolyline.setPath(path);
    fitMapToRoute(path);
    setNavigationPanels(card, payload);
  }

  async function initMiniMaps() {
    const mapsApi = await ensureMaps();
    if (!mapsApi) return;

    root.querySelectorAll('[data-map-card]').forEach((node) => {
      if (node.dataset.initialized === '1') return;
      node.dataset.initialized = '1';

      const destination = {
        lat: Number(node.dataset.destLat),
        lng: Number(node.dataset.destLng),
      };
      const origin = {
        lat: Number(node.dataset.originLat || node.dataset.destLat),
        lng: Number(node.dataset.originLng || node.dataset.destLng),
      };

      const map = new mapsApi.Map(node, {
        center: destination,
        zoom: 13,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false,
        clickableIcons: false,
      });

      const path = decodeRoutePath(node.dataset.routePolyline || '', origin, destination);
      const bounds = new mapsApi.LatLngBounds();
      path.forEach((point) => bounds.extend(point));
      map.fitBounds(bounds, 28);

      new mapsApi.Marker({
        map,
        position: origin,
        title: 'Ambulance',
        icon: {
          path: mapsApi.SymbolPath.FORWARD_CLOSED_ARROW,
          scale: 5,
          fillColor: '#1f71e7',
          fillOpacity: 1,
          strokeColor: '#ffffff',
          strokeWeight: 2,
        },
      });

      new mapsApi.Marker({
        map,
        position: destination,
        title: 'Destination',
      });

      new mapsApi.Polyline({
        map,
        path,
        strokeColor: '#4b38ff',
        strokeOpacity: 0.92,
        strokeWeight: 5,
      });
    });
  }

  async function fetchNavigation(card, position) {
    const url = new URL(
      navigationUrlTemplate.replace('__ID__', card.dataset.incidentId),
      window.location.origin
    );

    if (position?.latitude && position?.longitude) {
      url.searchParams.set('latitude', position.latitude);
      url.searchParams.set('longitude', position.longitude);
    }

    const response = await fetch(url.toString(), {
      headers: {
        'Accept': 'application/json',
      },
    });

    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(payload.error || 'Unable to build the live navigation route.');
    }

    return payload;
  }

  async function syncLocation(position) {
    const latitude = position.coords.latitude;
    const longitude = position.coords.longitude;
    const speed = position.coords.speed || '';
    lastPosition = { latitude, longitude, speed };

    const cards = [...root.querySelectorAll('[data-incident-card]')];
    await Promise.all(cards.map(async (card) => {
      const url = locationUrlTemplate.replace('__ID__', card.dataset.incidentId);
      const formData = new FormData();
      formData.append('latitude', latitude);
      formData.append('longitude', longitude);
      formData.append('speed_kmh', speed);
      formData.append('_token', HealthFirst.csrf);
      try {
        await HealthFirst.postForm(url, formData);
      } catch (error) {
        console.error(error);
      }
    }));

    await refreshIncidentFeed();

    if (activeIncidentId) {
      const activeCard = getCardByIncidentId(activeIncidentId);
      if (activeCard) {
        try {
          const route = await fetchNavigation(activeCard, lastPosition);
          await renderMasterNavigation(activeCard, route);
        } catch (error) {
          if (navNote) {
            navNote.textContent = error.message;
          }
        }
      }
    }
  }

  async function refreshCurrentLocation() {
    try {
      const position = await HealthFirst.getCurrentPosition();
      await syncLocation(position);
    } catch (error) {
      console.warn(error);
      if (navNote && activeIncidentId) {
        navNote.textContent = error.message || 'Unable to access live ambulance location right now.';
      }
    }
  }

  async function startNavigation(card) {
    activeIncidentId = card.dataset.incidentId;
    window.sessionStorage.setItem('ambulanceActiveIncident', activeIncidentId);
    setNavigationActive(card);

    try {
      const position = await HealthFirst.getCurrentPosition();
      await syncLocation(position);
      const route = await fetchNavigation(card, lastPosition);
      await renderMasterNavigation(card, route);
    } catch (error) {
      if (navNote) {
        navNote.textContent = error.message || 'Unable to start navigation without location access.';
      }
    }
  }

  function exitNavigation() {
    activeIncidentId = '';
    window.sessionStorage.removeItem('ambulanceActiveIncident');
    setNavigationActive(null);

    if (navBanner) navBanner.hidden = true;
    if (navSummary) navSummary.hidden = true;
    if (navNote) {
      navNote.textContent = 'Choose a dispatch and press Start Navigation to open a live route from the current ambulance position.';
    }

    if (masterRoutePolyline) {
      masterRoutePolyline.setPath([]);
    }
    if (ambulanceMarker) {
      ambulanceMarker.setMap(null);
    }
    if (destinationMarker) {
      destinationMarker.setMap(null);
    }
  }

  root.querySelectorAll('[data-start-nav-button]').forEach((button) => {
    button.addEventListener('click', async () => {
      const card = button.closest('[data-incident-card]');
      if (!card) return;
      await startNavigation(card);
    });
  });

  exitButtons.forEach((button) => {
    button.addEventListener('click', exitNavigation);
  });

  root.querySelectorAll('[data-pickup-button]').forEach((button) => {
    button.addEventListener('click', async () => {
      try {
        const position = await HealthFirst.getCurrentPosition();
        const formData = new FormData();
        formData.append('latitude', position.coords.latitude);
        formData.append('longitude', position.coords.longitude);
        formData.append('_token', HealthFirst.csrf);
        await HealthFirst.postForm(button.dataset.url, formData);
        window.location.reload();
      } catch (error) {
        window.alert(error.message);
      }
    });
  });

  root.querySelectorAll('[data-arrive-hospital-button]').forEach((button) => {
    button.addEventListener('click', async () => {
      button.disabled = true;
      const originalText = button.textContent;
      button.textContent = 'Confirming arrival...';
      try {
        const formData = new FormData();
        formData.append('_token', HealthFirst.csrf);
        await HealthFirst.postForm(button.dataset.url, formData);
        window.location.reload();
      } catch (error) {
        button.disabled = false;
        button.textContent = originalText;
        window.alert(error.message);
      }
    });
  });

  initMiniMaps();
  refreshIncidentFeed().catch((error) => console.error(error));
  refreshCurrentLocation();
  window.setInterval(() => {
    refreshIncidentFeed().catch((error) => console.error(error));
  }, 20000);
  window.setInterval(refreshCurrentLocation, 15000);

  if (activeIncidentId) {
    const card = getCardByIncidentId(activeIncidentId);
    if (card) {
      startNavigation(card);
    } else {
      exitNavigation();
    }
  }
});
