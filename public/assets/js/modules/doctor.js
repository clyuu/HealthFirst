document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.admit-form').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      try {
        await HealthFirst.postForm(form.action, form);
        window.location.href = `${HealthFirst.baseUrl}/doctor/patients`;
      } catch (error) {
        window.alert(error.message);
      }
    });
  });
});
