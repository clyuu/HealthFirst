document.addEventListener('DOMContentLoaded', () => {
  const grid = document.querySelector('[data-hospital-dashboard]');
  if (!grid) return;

  const feedUrl = grid.dataset.feedUrl;

  grid.querySelectorAll('[data-assign-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      try {
        await HealthFirst.postForm(form.action, form);
        window.location.reload();
      } catch (error) {
        window.alert(error.message);
      }
    });
  });

  window.setInterval(async () => {
    try {
      const response = await fetch(feedUrl, { headers: { Accept: 'application/json' } });
      const payload = await response.json();
      const liveIds = [...grid.querySelectorAll('[data-incident-id]')].map((node) => `${node.dataset.incidentId}:${node.dataset.status || ''}`).join(',');
      const nextIds = (payload.incidents || []).map((incident) => `${incident.incident_id}:${incident.status}`).join(',');
      if (liveIds !== nextIds) {
        window.location.reload();
      }
    } catch (error) {
      console.error(error);
    }
  }, 20000);
});

