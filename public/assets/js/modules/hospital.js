document.addEventListener('DOMContentLoaded', () => {
  const grid = document.querySelector('[data-hospital-dashboard]');
  if (!grid) return;

  const feedUrl = grid.dataset.feedUrl;
  const mapsApiKey = grid.dataset.mapsApiKey || '';
  const notice = document.getElementById('hospitalAssignNotice');

  function showNotice(message, type = 'success') {
    if (!notice) return;
    const text = document.createElement('span');
    text.textContent = message;
    const dismiss = document.createElement('button');
    dismiss.className = 'notice-dismiss';
    dismiss.type = 'button';
    dismiss.setAttribute('aria-label', 'Remove notification');
    dismiss.textContent = 'x';
    dismiss.addEventListener('click', () => {
      notice.hidden = true;
    });
    notice.hidden = false;
    notice.replaceChildren(text, dismiss);
    notice.classList.add('notice-with-dismiss');
    notice.classList.remove('success', 'error');
    notice.classList.add(type);
  }

  function restoreNotice() {
    try {
      const raw = window.sessionStorage.getItem('hospitalAssignNotice');
      if (!raw) return;
      window.sessionStorage.removeItem('hospitalAssignNotice');
      const payload = JSON.parse(raw);
      if (payload?.message) {
        showNotice(payload.message, payload.type || 'success');
      }
    } catch (error) {
      console.error(error);
    }
  }

  function statusClass(status) {
    if (status === 'verified_unassigned') return 'tile-red';
    if (status === 'ambulance_assigned' || status === 'en_route_scene') return 'tile-yellow';
    if (status === 'patient_picked_up' || status === 'en_route_hospital') return 'tile-green';
    return 'tile-neutral';
  }

  function applyStatus(card, status) {
    if (!card || !status) return;
    card.dataset.status = status;
    card.classList.remove('tile-red', 'tile-yellow', 'tile-green', 'tile-neutral');
    card.classList.add(statusClass(status));
    const badge = card.querySelector('.badge');
    if (badge) {
      badge.textContent = status;
    }
  }

  function updateIncidentCard(card, incident) {
    const etaLabel = card.querySelector('[data-eta-label]');
    const etaBadge = card.querySelector('[data-eta-badge]');
    const distanceLabel = card.querySelector('[data-distance-label]');
    const etaSeconds = Number(incident.display_eta_seconds || 0);
    const etaLive = Boolean(incident.eta_live);

    if (etaLabel) {
      HealthFirst.setEta(etaLabel, etaSeconds, etaLive);
    }

    if (etaBadge) {
      etaBadge.textContent = `${Math.max(Math.ceil(etaSeconds / 60), 0)} min ETA`;
    }

    if (distanceLabel && incident.display_distance_meters !== null && incident.display_distance_meters !== undefined) {
      distanceLabel.textContent = `${(Number(incident.display_distance_meters) / 1000).toFixed(1)} km`;
    }

    if (Object.prototype.hasOwnProperty.call(incident, 'display_route_polyline')) {
      card.dataset.routePolyline = incident.display_route_polyline || '';
    }

    if (incident.display_origin_latitude && incident.display_origin_longitude) {
      card.dataset.originLat = incident.display_origin_latitude;
      card.dataset.originLng = incident.display_origin_longitude;
    }

    if (incident.display_destination_latitude && incident.display_destination_longitude) {
      card.dataset.destLat = incident.display_destination_latitude;
      card.dataset.destLng = incident.display_destination_longitude;
    }

    if (incident.display_destination_label) {
      card.dataset.destLabel = incident.display_destination_label;
    }

    if (incident.status) {
      applyStatus(card, incident.status);
    }
  }

  async function initMiniMaps() {
    const maps = await HealthFirst.loadGoogleMaps(mapsApiKey);
    if (!maps) return;

    grid.querySelectorAll('[data-map-card]').forEach((card) => {
      if (card.dataset.mapInitialized === '1') return;

      const canvas = card.querySelector('[data-map-canvas]');
      const originLat = Number(card.dataset.originLat || 0);
      const originLng = Number(card.dataset.originLng || 0);
      const destLat = Number(card.dataset.destLat || 0);
      const destLng = Number(card.dataset.destLng || 0);
      if (!canvas || !originLat || !originLng || !destLat || !destLng) {
        return;
      }

      card.dataset.mapInitialized = '1';
      const origin = { lat: originLat, lng: originLng };
      const destination = { lat: destLat, lng: destLng };
      const map = new maps.Map(canvas, {
        center: destination,
        zoom: 13,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false,
      });

      new maps.Marker({
        map,
        position: origin,
        title: 'Hospital',
        icon: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png',
      });
      new maps.Marker({
        map,
        position: destination,
        title: card.dataset.destLabel || 'Destination',
        icon: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
      });

      const encodedPolyline = card.dataset.routePolyline || '';
      let bounds = new maps.LatLngBounds();
      bounds.extend(origin);
      bounds.extend(destination);

      if (encodedPolyline && maps.geometry?.encoding) {
        const path = maps.geometry.encoding.decodePath(encodedPolyline);
        new maps.Polyline({
          map,
          path,
          strokeColor: '#1f71e7',
          strokeOpacity: 0.92,
          strokeWeight: 5,
        });
        path.forEach((point) => bounds.extend(point));
      } else {
        new maps.Polyline({
          map,
          path: [origin, destination],
          strokeColor: '#1f71e7',
          strokeOpacity: 0.85,
          strokeWeight: 3,
        });
      }

      map.fitBounds(bounds, 24);
    });
  }

  grid.querySelectorAll('[data-assign-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const select = form.querySelector('select[name="ambulance_id"]');
      const submitButton = form.querySelector('button[type="submit"]');
      const card = form.closest('[data-incident-id]');
      if (!select || !select.value) {
        showNotice('Select an ambulance before assigning the incident.', 'error');
        return;
      }

      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Assigning...';
      }

      try {
        const payload = await HealthFirst.postForm(form.action, form);
        const incident = payload.incident || {};
        const message = payload.message || 'Ambulance assigned successfully.';

        applyStatus(card, incident.status || 'ambulance_assigned');
        form.closest('.ops-form-card')?.remove();
        showNotice(message, 'success');
        window.sessionStorage.setItem('hospitalAssignNotice', JSON.stringify({
          type: 'success',
          message,
        }));
        window.setTimeout(() => {
          const incidentId = incident.incident_id || card?.dataset.incidentId || '';
          const anchor = incidentId ? `#incident-${incidentId}` : '';
          window.location.assign(`${window.location.pathname}?refresh=${Date.now()}${anchor}`);
        }, 350);
      } catch (error) {
        showNotice(error.message || 'Ambulance assignment failed.', 'error');
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.textContent = 'Assign Ambulance';
        }
      }
    });
  });

  restoreNotice();
  initMiniMaps();

  window.setInterval(async () => {
    try {
      const response = await fetch(feedUrl, { headers: { Accept: 'application/json' } });
      const payload = await response.json();
      const liveIds = [...grid.querySelectorAll('[data-incident-id]')].map((node) => `${node.dataset.incidentId}:${node.dataset.status || ''}`).join(',');
      const nextIds = (payload.incidents || []).map((incident) => `${incident.incident_id}:${incident.status}`).join(',');
      if (liveIds !== nextIds) {
        window.location.reload();
        return;
      }

      (payload.incidents || []).forEach((incident) => {
        const card = grid.querySelector(`[data-incident-id="${incident.incident_id}"]`);
        if (card) {
          updateIncidentCard(card, incident);
        }
      });

      initMiniMaps();
    } catch (error) {
      console.error(error);
    }
  }, 20000);
});
