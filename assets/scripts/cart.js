document.addEventListener('DOMContentLoaded', () => {
  const cartForm = document.querySelector('[data-cart-form]');

  if (!cartForm) {
    return;
  }

  const updateButtons = Array.from(cartForm.querySelectorAll('[data-cart-update-button]'));
  const updateMessage = cartForm.querySelector('[data-cart-update-message]');
  const currencyFormatter = new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  });

  const formatPrice = (amount) => currencyFormatter.format(Number(amount || 0));

  const setMessage = (text, isError = false) => {
    if (!updateMessage) {
      return;
    }

    updateMessage.hidden = false;
    updateMessage.textContent = text;
    updateMessage.classList.toggle('is-error', isError);
  };

  const updateCartDisplay = (data) => {
    if (data.isEmpty) {
      renderEmptyCart();
      return;
    }

    data.items.forEach((item) => {
      const quantityInput = cartForm.querySelector(`[data-cart-quantity][data-menu-id="${item.menuId}"]`);
      const orderQuantity = cartForm.querySelector(`[data-cart-order-quantity="${item.menuId}"]`);
      const recapLine = cartForm.querySelector(`[data-cart-line="${item.menuId}"]`);
      const discountLine = cartForm.querySelector(`[data-cart-discount-line="${item.menuId}"]`);

      if (quantityInput) {
        quantityInput.value = item.quantity;
      }

      if (orderQuantity) {
        orderQuantity.textContent = `Minimum ${item.minimum} personnes`;
      }

      if (recapLine) {
        const quantityLabel = recapLine.querySelector('[data-cart-quantity-label]');
        const lineTotal = recapLine.querySelector('[data-cart-line-total]');

        if (quantityLabel) {
          quantityLabel.textContent = `${item.quantity} personnes x ${formatPrice(item.unitPrice)} / pers.`;
        }

        if (lineTotal) {
          lineTotal.textContent = formatPrice(item.lineTotal);
        }
      }

      if (discountLine) {
        const lineDiscount = discountLine.querySelector('[data-cart-line-discount]');
        discountLine.hidden = item.lineDiscount <= 0;

        if (lineDiscount) {
          lineDiscount.textContent = `-${formatPrice(item.lineDiscount)}`;
        }
      }
    });

    const totalDiscount = cartForm.querySelector('[data-cart-total-discount]');
    const delivery = cartForm.querySelector('[data-cart-delivery]');
    const total = cartForm.querySelector('[data-cart-total]');

    if (totalDiscount) {
      totalDiscount.textContent = `-${formatPrice(data.reduction)}`;
    }

    if (delivery) {
      delivery.textContent = data.prixLivraison === 0 ? 'Offerte' : formatPrice(data.prixLivraison);
    }

    if (total) {
      total.textContent = formatPrice(data.prixTotal);
    }
  };

  const renderEmptyCart = () => {
    const cartPage = document.querySelector('.cart-page');

    if (!cartPage) {
      return;
    }

    cartPage.innerHTML = `
      <section class="cart-card cart-empty-card">
        <h2>Votre panier est vide</h2>
        <div class="cart-card__line"></div>
        <p>Choisissez un menu gourmand pour commencer votre commande.</p>
        <a href="/menus" class="buttonprimary">Voir les menus</a>
      </section>
    `;
  };

  cartForm.addEventListener('submit', async (event) => {
    if (!event.submitter || !event.submitter.matches('[data-cart-update-button]')) {
      return;
    }

    event.preventDefault();

    const clickedButton = event.submitter;
    updateButtons.forEach((button) => {
      button.disabled = true;
    });
    clickedButton.textContent = 'Mise a jour...';

    try {
      const response = await fetch(cartForm.action, {
        method: 'POST',
        body: new FormData(cartForm),
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) {
        throw new Error('update_failed');
      }

      const data = await response.json();
      updateCartDisplay(data);
      setMessage('Panier mis a jour.');
    } catch (error) {
      setMessage('Impossible de mettre a jour le panier pour le moment.', true);
    } finally {
      updateButtons.forEach((button) => {
        button.disabled = false;
        button.textContent = 'Mettre a jour';
      });
    }
  });

  document.querySelectorAll('[data-cart-remove-button]').forEach((button) => {
    button.addEventListener('click', async (event) => {
      event.preventDefault();

      const formId = button.getAttribute('form');
      const removeForm = formId ? document.getElementById(formId) : null;
      const menuId = button.dataset.menuId;

      if (!removeForm || !menuId) {
        return;
      }

      button.disabled = true;
      button.textContent = 'Suppression...';

      try {
        const response = await fetch(removeForm.action, {
          method: 'POST',
          body: new FormData(removeForm),
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
          },
        });

        if (!response.ok) {
          throw new Error('remove_failed');
        }

        const data = await response.json();
        const cartItem = document.querySelector(`[data-cart-item="${menuId}"]`);
        const recapLine = document.querySelector(`[data-cart-line="${menuId}"]`);
        const discountLine = document.querySelector(`[data-cart-discount-line="${menuId}"]`);

        if (cartItem) {
          cartItem.remove();
        }

        if (recapLine) {
          recapLine.remove();
        }

        if (discountLine) {
          discountLine.remove();
        }

        updateCartDisplay(data);
        setMessage(data.isEmpty ? 'Votre panier est vide.' : 'Menu supprime du panier.');
      } catch (error) {
        button.disabled = false;
        button.textContent = 'Supprimer';
        setMessage('Impossible de supprimer ce menu pour le moment.', true);
      }
    });
  });
});
