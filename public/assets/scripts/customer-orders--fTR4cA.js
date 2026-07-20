// Gère la fenêtre permettant à un client de déposer un avis depuis l'historique de ses commandes.
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.querySelector('[data-review-modal]');

  if (!modal) {
    return;
  }

  const orderIdInput = modal.querySelector('[data-review-order-id]');
  const orderLabel = modal.querySelector('[data-review-order-label]');
  const form = modal.querySelector('form');
  let lastFocusedButton = null;

  const openModal = (button) => {
    // Recopie l'identifiant après la réinitialisation afin de conserver la commande ciblée dans le formulaire.
    lastFocusedButton = button;
    orderIdInput.value = button.dataset.orderId || '';
    orderLabel.textContent = button.dataset.orderLabel || '';
    form.reset();
    orderIdInput.value = button.dataset.orderId || '';
    modal.hidden = false;
    document.body.classList.add('has-open-modal');

    // Le point de focalisation entre directement dans la fenêtre pour faciliter l'utilisation au clavier.
    modal.querySelector('[data-review-close]')?.focus();
  };

  const closeModal = () => {
    modal.hidden = true;
    document.body.classList.remove('has-open-modal');

    // Après la fermeture, le point de focalisation revient au bouton qui a ouvert la fenêtre.
    lastFocusedButton?.focus();
    lastFocusedButton = null;
  };

  document.addEventListener('click', (event) => {
    // La délégation d'événement couvre aussi les boutons ajoutés ultérieurement au document.
    const openButton = event.target.closest('[data-review-open]');

    if (openButton) {
      openModal(openButton);
      return;
    }

    if (event.target.closest('[data-review-close]')) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });
});
