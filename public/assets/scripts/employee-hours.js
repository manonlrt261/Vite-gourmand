document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-hours-row]').forEach((row) => {
    const toggle = row.querySelector('[data-hours-toggle]');
    const inputs = row.querySelectorAll('[data-hours-input]');

    if (!toggle) {
      return;
    }

    const syncInputs = () => {
      inputs.forEach((input) => {
        input.disabled = !toggle.checked;
      });
    };

    toggle.addEventListener('change', syncInputs);
    syncInputs();
  });
});
