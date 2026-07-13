document.addEventListener('DOMContentLoaded', () => {
  document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-add-to-cart]');

    if (!button) {
      return;
    }

    const cartUrl = button.dataset.cartUrl;

    if (!cartUrl || button.disabled) {
      return;
    }

    const initialText = button.textContent.trim();
    button.disabled = true;
    button.textContent = 'Ajout...';

    try {
      const response = await fetch(cartUrl, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Impossible d’ajouter ce menu.');
      }

      button.textContent = 'Ajouté';
      button.classList.add('is-added');

      window.setTimeout(() => {
        button.textContent = initialText;
        button.classList.remove('is-added');
        button.disabled = false;
      }, 1400);
    } catch (error) {
      button.textContent = 'Erreur';
      button.classList.add('is-error');

      window.setTimeout(() => {
        button.textContent = initialText;
        button.classList.remove('is-error');
        button.disabled = false;
      }, 1800);
    }
  });
});
