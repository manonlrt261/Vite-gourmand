document.addEventListener('DOMContentLoaded', () => {
  const modal = document.querySelector('[data-review-modal]');

  if (!modal) {
    return;
  }

  const orderIdInput = modal.querySelector('[data-review-order-id]');
  const orderLabel = modal.querySelector('[data-review-order-label]');
  const form = modal.querySelector('form');

  const openModal = (button) => {
    orderIdInput.value = button.dataset.orderId || '';
    orderLabel.textContent = button.dataset.orderLabel || '';
    form.reset();
    orderIdInput.value = button.dataset.orderId || '';
    modal.hidden = false;
    document.body.classList.add('has-open-modal');
  };

  const closeModal = () => {
    modal.hidden = true;
    document.body.classList.remove('has-open-modal');
  };

  document.addEventListener('click', (event) => {
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
