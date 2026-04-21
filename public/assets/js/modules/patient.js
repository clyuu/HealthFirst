document.addEventListener('DOMContentLoaded', () => {
  const listNode = document.getElementById('patientHospitalList');
  const mapNode = document.getElementById('patientHospitalMap');
  if (!listNode || !mapNode) return;

  const apiKey = mapNode.dataset.apiKey || '';
  const nearbyCount = document.getElementById('nearbyHospitalCount');

  async function renderHospitals(latitude, longitude) {
    const feed = await fetch(`${HealthFirst.baseUrl}/api/patient/nearby-hospitals?latitude=${encodeURIComponent(latitude)}&longitude=${encodeURIComponent(longitude)}`);
    const payload = await feed.json();
    const hospitals = payload.hospitals || [];
    nearbyCount.textContent = hospitals.length.toString();

    listNode.innerHTML = hospitals.map((hospital) => `
      <article class="doc-card">
        <div>
          <h3>${hospital.hospital_name}</h3>
          <p class="muted">${hospital.address}</p>
        </div>
        <div>
          <strong>${HealthFirst.formatEta(hospital.eta_seconds || 0)}</strong>
          <p>${((hospital.route_distance_meters || 0) / 1000).toFixed(1)} km</p>
        </div>
      </article>
    `).join('');

    const maps = await HealthFirst.loadGoogleMaps(apiKey);
    if (!maps) return;
    const map = new maps.Map(mapNode, {
      center: { lat: latitude, lng: longitude },
      zoom: 11,
      mapTypeControl: false,
      streetViewControl: false,
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
  }

  HealthFirst.getCurrentPosition()
    .then((position) => renderHospitals(position.coords.latitude, position.coords.longitude))
    .catch(async () => {
      const fallback = await fetch(`${HealthFirst.baseUrl}/api/patient/nearby-hospitals`);
      const payload = await fallback.json();
      const hospitals = payload.hospitals || [];
      nearbyCount.textContent = hospitals.length.toString();
      listNode.innerHTML = hospitals.map((hospital) => `
        <article class="doc-card">
          <div>
            <h3>${hospital.hospital_name}</h3>
            <p class="muted">${hospital.address}</p>
          </div>
          <div>
            <strong>${HealthFirst.formatEta(hospital.eta_seconds || 0)}</strong>
            <p>${((hospital.route_distance_meters || 0) / 1000).toFixed(1)} km</p>
          </div>
        </article>
      `).join('');
    });
});
