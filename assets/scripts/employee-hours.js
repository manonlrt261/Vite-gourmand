// Active ou désactive les champs horaires de chaque journée selon son interrupteur d'ouverture.
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-hours-row]').forEach((row) => {
    const toggle = row.querySelector('[data-hours-toggle]');
    const inputs = row.querySelectorAll('[data-hours-input]');

    if (!toggle) {
      return;
    }

    const syncInputs = () => {
      // Les champs désactivés ne seront pas soumis pour une journée déclarée fermée.
      inputs.forEach((input) => {
        input.disabled = !toggle.checked;
      });
    };

    toggle.addEventListener('change', syncInputs);
    syncInputs();
  });
});
