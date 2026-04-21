document.addEventListener('DOMContentLoaded', () => {
  const root = document.querySelector('[data-emergency-root]');
  if (!root) return;

  const startButton = root.querySelector('[data-start-emergency]');
  const formPanel = root.querySelector('[data-emergency-form]');
  const form = root.querySelector('#emergencyIncidentForm');
  const statusNode = root.querySelector('[data-emergency-status]');
  const submitUrl = root.dataset.submitUrl;

  startButton?.addEventListener('click', async () => {
    formPanel?.classList.remove('hidden');
    startButton.classList.add('hidden');
    HealthFirst.setNotice(statusNode, 'Allow location access so the nearest registered hospital can be selected.');

    try {
      const position = await HealthFirst.getCurrentPosition();
      form.querySelector('[name="incident_latitude"]').value = position.coords.latitude;
      form.querySelector('[name="incident_longitude"]').value = position.coords.longitude;
      HealthFirst.setNotice(statusNode, 'Location captured. Please attach a clear accident photo and submit.');
    } catch (error) {
      HealthFirst.setNotice(statusNode, error.message || 'Location could not be captured.', 'error');
    }
  });

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
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

