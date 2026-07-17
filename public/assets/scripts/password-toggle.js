document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const control = button.closest('.auth-password-control');
    const input = control ? control.querySelector('[data-password-input]') : null;

    if (!input) {
      return;
    }

    button.addEventListener('click', () => {
      const isVisible = input.type === 'text';
      input.type = isVisible ? 'password' : 'text';
      button.textContent = isVisible ? 'Afficher' : 'Masquer';
      input.focus();
    });
  });
});
