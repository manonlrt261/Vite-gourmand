// Ajoute un menu au panier en AJAX et affiche brièvement le résultat sur le bouton déclencheur.
document.addEventListener('DOMContentLoaded', () => {
  document.addEventListener('click', async (event) => {
    // La délégation permet aux cartes de menus déplacées par les filtres de conserver cette action.
    const button = event.target.closest('[data-add-to-cart]');

    if (!button) {
      return;
    }

    const cartUrl = button.dataset.cartUrl;
    // Le jeton CSRF fourni par Twig protège la requête d'ajout au panier.
    const cartToken = button.dataset.cartToken || '';

    if (!cartUrl || button.disabled) {
      return;
    }

    const initialText = button.textContent.trim();
    // Le verrouillage empêche un double ajout pendant le traitement de la requête.
    button.disabled = true;
    button.textContent = 'Ajout...';

    try {
      const formData = new FormData();
      formData.append('_csrf_token', cartToken);

      const response = await fetch(cartUrl, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        body: formData,
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
