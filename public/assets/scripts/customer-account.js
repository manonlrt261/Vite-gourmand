document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('[data-customer-account-review-form]');

  if (!form) {
    return;
  }

  const messageBox = form.querySelector('[data-customer-account-review-message]');
  const orderSelect = form.querySelector('select[name="commande_id"]');
  const submitButton = form.querySelector('button[type="submit"]');

  const showMessage = (message, isSuccess) => {
    if (!messageBox) {
      return;
    }

    messageBox.innerHTML = '';

    const paragraph = document.createElement('p');
    paragraph.className = `customer-alert ${isSuccess ? 'customer-alert--success' : 'customer-alert--error'}`;
    paragraph.textContent = message;
    messageBox.appendChild(paragraph);
  };

  const removeReviewedOrder = (orderId) => {
    if (!orderSelect || !orderId) {
      return;
    }

    const option = orderSelect.querySelector(`option[value="${CSS.escape(String(orderId))}"]`);
    option?.remove();
    orderSelect.value = '';

    const remainingOrders = Array.from(orderSelect.options).filter((optionItem) => optionItem.value !== '');

    if (remainingOrders.length === 0) {
      form.querySelectorAll('.customer-review-field, .customer-rating, .customer-review-submit').forEach((element) => {
        element.remove();
      });

      const emptyMessage = document.createElement('p');
      emptyMessage.className = 'customer-empty';
      emptyMessage.textContent = 'Vous pourrez laisser un avis une fois votre commande réceptionnée.';
      form.appendChild(emptyMessage);
    }
  };

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (submitButton) {
      submitButton.disabled = true;
    }

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      const data = await response.json();

      showMessage(data.message || 'Une erreur est survenue.', Boolean(data.success));

      if (data.success) {
        const reviewedOrderId = data.commande_id || orderSelect?.value;
        form.reset();
        removeReviewedOrder(reviewedOrderId);
      }
    } catch (error) {
      showMessage("Impossible d'envoyer l'avis pour le moment.", false);
    } finally {
      if (submitButton && document.body.contains(submitButton)) {
        submitButton.disabled = false;
      }
    }
  });
});
