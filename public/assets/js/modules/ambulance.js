document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('ambulanceIncidentList');
  if (!root) return;

  const mapsApiKey = document.getElementById('ambulanceMasterMap')?.dataset.apiKey || '';

  async function initMiniMaps() {
    const maps = await HealthFirst.loadGoogleMaps(mapsApiKey);
    if (!maps) return;

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
      const map = new maps.Map(node, {
        center: destination,
        zoom: 13,
        mapTypeControl: false,
        streetViewControl: false,
      });
      new maps.Marker({ map, position: origin, title: 'Ambulance' });
      new maps.Marker({ map, position: destination, title: 'Incident' });
    });
  }

  async function postCurrentLocation() {
    try {
      const position = await HealthFirst.getCurrentPosition();
      const latitude = position.coords.latitude;
      const longitude = position.coords.longitude;
      root.querySelectorAll('[data-incident-card]').forEach(async (card) => {
        const url = root.dataset.locationUrlTemplate.replace('__ID__', card.dataset.incidentId);
        const formData = new FormData();
        formData.append('latitude', latitude);
        formData.append('longitude', longitude);
        formData.append('speed_kmh', position.coords.speed || '');
        formData.append('_token', HealthFirst.csrf);
        try {
          await HealthFirst.postForm(url, formData);
        } catch (error) {
          console.error(error);
        }
      });
    } catch (error) {
      console.warn(error);
    }
  }

  root.querySelectorAll('[data-lookup-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      try {
        const result = await HealthFirst.postForm(form.action, form);
        window.alert(`Patient loaded: ${result.patient.patient_name}\nBlood: ${result.patient.blood_group || 'Unknown'}\nAllergies: ${result.patient.allergies || 'None'}`);
      } catch (error) {
        window.alert(error.message);
      }
    });
  });

  root.querySelectorAll('[data-vitals-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      try {
        await HealthFirst.postForm(form.action, form);
        window.alert('Vitals saved.');
      } catch (error) {
        window.alert(error.message);
      }
    });
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

  root.querySelectorAll('[data-injury-root]').forEach((injuryRoot) => {
    const startButton = injuryRoot.querySelector('[data-start-injury]');
    const activePanel = injuryRoot.querySelector('[data-injury-active]');
    const fileInput = injuryRoot.querySelector('[data-injury-file]');
    const attachButton = injuryRoot.querySelector('[data-attach-photo]');
    const finalizeButton = injuryRoot.querySelector('[data-finalize-session]');
    const statusNode = injuryRoot.querySelector('[data-injury-status]');
    let sessionId = null;

    startButton?.addEventListener('click', async () => {
      const formData = new FormData();
      formData.append('special_note', injuryRoot.querySelector('[name="special_note"]').value || '');
      formData.append('_token', HealthFirst.csrf);
      try {
        const result = await HealthFirst.postForm(injuryRoot.dataset.startUrl, formData);
        sessionId = result.session_id;
        activePanel.classList.remove('hidden');
        HealthFirst.setNotice(statusNode, 'Injury session started. Attach one photo at a time.');
      } catch (error) {
        HealthFirst.setNotice(statusNode, error.message, 'error');
      }
    });

    attachButton?.addEventListener('click', async () => {
      if (!sessionId || !fileInput.files.length) {
        HealthFirst.setNotice(statusNode, 'Choose an injury photo first.', 'error');
        return;
      }
      const formData = new FormData();
      formData.append('injury_photo', fileInput.files[0]);
      formData.append('_token', HealthFirst.csrf);
      try {
        const result = await HealthFirst.postForm(
          `${HealthFirst.baseUrl}/ambulance/injury-sessions/${sessionId}/images`,
          formData
        );
        HealthFirst.setNotice(statusNode, `Attached: ${result.analysis.predicted_label} (${result.analysis.confidence}%)`, 'success');
        fileInput.value = '';
      } catch (error) {
        HealthFirst.setNotice(statusNode, error.message, 'error');
      }
    });

    finalizeButton?.addEventListener('click', async () => {
      if (!sessionId) {
        HealthFirst.setNotice(statusNode, 'Start a session first.', 'error');
        return;
      }
      const formData = new FormData();
      formData.append('special_note', injuryRoot.querySelector('[name="special_note"]').value || '');
      formData.append('_token', HealthFirst.csrf);
      try {
        const result = await HealthFirst.postForm(
          `${HealthFirst.baseUrl}/ambulance/injury-sessions/${sessionId}/finalize`,
          formData
        );
        HealthFirst.setNotice(statusNode, `Injury report ready. Severity: ${result.overall_severity}`, 'success');
      } catch (error) {
        HealthFirst.setNotice(statusNode, error.message, 'error');
      }
    });
  });

  initMiniMaps();
  postCurrentLocation();
  window.setInterval(postCurrentLocation, 15000);
});
