document.addEventListener('DOMContentLoaded', () => {
  const listNode = document.getElementById('patientHospitalList');
  const mapNode = document.getElementById('patientHospitalMap');
  const noticeNode = document.getElementById('patientHospitalNotice');
  const nearbyCount = document.getElementById('nearbyHospitalCount');
  const openButtons = document.querySelectorAll('[data-open-panel]');

  function openPanel(panelId) {
    const panel = document.getElementById(panelId);
    if (!panel) return;
    panel.classList.remove('hidden');
    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  openButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      const panelId = button.dataset.openPanel;
      if (!panelId) return;
      event.preventDefault();
      openPanel(panelId);
    });
  });

  if (window.location.hash) {
    const hashTarget = window.location.hash.slice(1);
    if (hashTarget) {
      window.setTimeout(() => openPanel(hashTarget), 50);
    }
  }

  if (!listNode || !mapNode) return;

  const apiKey = mapNode.dataset.apiKey || '';

  function renderHospitalCards(hospitals) {
    nearbyCount.textContent = hospitals.length.toString();
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

  async function drawMap(latitude, longitude, hospitals) {
    const maps = await HealthFirst.loadGoogleMaps(apiKey);
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

  HealthFirst.setNotice(noticeNode, 'Searching your nearest hospitals...', 'info');

  HealthFirst.getCurrentPosition()
    .then(async (position) => {
      const latitude = position.coords.latitude;
      const longitude = position.coords.longitude;
      const hospitals = await loadHospitals(latitude, longitude);
      renderHospitalCards(hospitals);
      await drawMap(latitude, longitude, hospitals);
    })
    .catch(async () => {
      try {
        const hospitals = await loadHospitals();
        renderHospitalCards(hospitals);
        HealthFirst.setNotice(noticeNode, 'Using the saved profile location because live GPS was not available.', 'info');
      } catch (error) {
        renderHospitalCards([]);
        HealthFirst.setNotice(noticeNode, error.message || 'Unable to load nearby hospitals.', 'error');
      }
    });
});
