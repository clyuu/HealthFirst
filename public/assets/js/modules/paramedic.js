document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('paramedicCareList');
  if (!root) return;

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
});
