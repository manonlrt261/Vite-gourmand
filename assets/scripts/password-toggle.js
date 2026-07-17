// Bascule l'affichage des mots de passe sans retirer le focus du champ concerné.
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const control = button.closest('.auth-password-control');
    const input = control ? control.querySelector('[data-password-input]') : null;

    if (!input) {
      return;
    }

    button.addEventListener('click', () => {
      // Le libellé décrit toujours l'action suivante disponible, et non l'état courant.
      const isVisible = input.type === 'text';
      input.type = isVisible ? 'password' : 'text';
      button.textContent = isVisible ? 'Afficher' : 'Masquer';
      input.focus();
    });
  });
});
