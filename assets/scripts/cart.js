// Met à jour le panier en AJAX et estime les frais de livraison à partir de l'adresse saisie.
document.addEventListener('DOMContentLoaded', () => {
  const cartForm = document.querySelector('[data-cart-form]');

  if (!cartForm) {
    return;
  }

  const updateButtons = Array.from(cartForm.querySelectorAll('[data-cart-update-button]'));
  const updateMessage = cartForm.querySelector('[data-cart-update-message]');
  const recapCard = cartForm.querySelector('[data-cart-recap]');
  const addressInput = cartForm.querySelector('input[name="adresse_livraison"]');
  const postalCodeInput = cartForm.querySelector('input[name="code_postal_livraison"]');
  const cityInput = cartForm.querySelector('input[name="ville_livraison"]');
  const currencyFormatter = new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  });
  const BORDEAUX_LATITUDE = 44.837789;
  const BORDEAUX_LONGITUDE = -0.57918;
  const DELIVERY_BASE_PRICE = 5;
  const DELIVERY_PRICE_PER_KM = 0.59;
  const knownPostalDistances = {
    // Distances de repli utilisées lorsque les services de géocodage ou de routage sont indisponibles.
    33100: 5,
    33200: 4,
    33300: 4,
    33800: 3,
    33110: 4,
    33130: 4,
    33140: 7,
    33150: 5,
    33160: 13,
    33170: 8,
    33185: 8,
    33270: 5,
    33290: 10,
    33310: 6,
    33320: 8,
    33360: 9,
    33370: 8,
    33400: 4,
    33450: 14,
    33500: 35,
    33520: 6,
    33530: 8,
    33560: 9,
    33600: 7,
    33610: 15,
    33700: 7,
    33710: 30,
    33720: 35,
    33850: 15,
    33950: 55,
    37000: 300,
  };

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
    // Synchronise les quantités, remises et totaux avec la réponse calculée côté serveur.
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

    if (recapCard) {
      recapCard.dataset.cartMenuTotal = String(data.prixMenu || 0);
      recapCard.dataset.cartReduction = String(data.reduction || 0);
    }

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

  const calculateDistanceKm = (startLat, startLon, endLat, endLon) => {
    // Formule de Haversine : distance à vol d'oiseau entre deux coordonnées géographiques.
    const earthRadiusKm = 6371;
    const latDistance = ((endLat - startLat) * Math.PI) / 180;
    const lonDistance = ((endLon - startLon) * Math.PI) / 180;
    const startLatRad = (startLat * Math.PI) / 180;
    const endLatRad = (endLat * Math.PI) / 180;
    const a = Math.sin(latDistance / 2) ** 2
      + Math.cos(startLatRad) * Math.cos(endLatRad) * Math.sin(lonDistance / 2) ** 2;

    return earthRadiusKm * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
  };

  const estimateDistanceFromPostalCode = (postalCode) => knownPostalDistances[postalCode] || 25;

  const findCoordinates = async (query) => {
    // L'API Adresse renvoie les coordonnées dans l'ordre longitude, latitude.
    const response = await fetch(`https://api-adresse.data.gouv.fr/search/?limit=1&q=${encodeURIComponent(query)}`);

    if (!response.ok) {
      throw new Error('geocode_failed');
    }

    const payload = await response.json();
    const coordinates = payload.features?.[0]?.geometry?.coordinates;

    if (!coordinates) {
      throw new Error('geocode_empty');
    }

    return {
      latitude: Number(coordinates[1]),
      longitude: Number(coordinates[0]),
    };
  };

  const findDrivingDistance = async (latitude, longitude) => {
    // Demande à OSRM une distance routière depuis le point de référence situé à Bordeaux.
    const url = `https://router.project-osrm.org/route/v1/driving/${BORDEAUX_LONGITUDE},${BORDEAUX_LATITUDE};${longitude},${latitude}?overview=false`;
    const response = await fetch(url);

    if (!response.ok) {
      throw new Error('route_failed');
    }

    const payload = await response.json();
    const distanceMeters = payload.routes?.[0]?.distance;

    if (!distanceMeters) {
      throw new Error('route_empty');
    }

    return Number(distanceMeters) / 1000;
  };

  const calculateDeliveryPrice = async () => {
    const postalCode = (postalCodeInput?.value || '').replace(/\D/g, '').slice(0, 5);

    if (!postalCode || postalCode === '33000') {
      return 0;
    }

    const query = `${addressInput?.value || ''} ${postalCode} ${cityInput?.value || ''}`.trim();
    let distanceKm = estimateDistanceFromPostalCode(postalCode);

    if (query.length > 7) {
      // Privilégie la distance routière, puis la distance directe, avant le barème postal de repli.
      try {
        const coordinates = await findCoordinates(query);
        distanceKm = await findDrivingDistance(coordinates.latitude, coordinates.longitude);
      } catch (error) {
        try {
          const coordinates = await findCoordinates(query);
          distanceKm = calculateDistanceKm(BORDEAUX_LATITUDE, BORDEAUX_LONGITUDE, coordinates.latitude, coordinates.longitude);
        } catch (fallbackError) {
          distanceKm = estimateDistanceFromPostalCode(postalCode);
        }
      }
    }

    return Math.round((DELIVERY_BASE_PRICE + (distanceKm * DELIVERY_PRICE_PER_KM)) * 100) / 100;
  };

  const updateDeliveryDisplay = async () => {
    if (!recapCard) {
      return;
    }

    const delivery = cartForm.querySelector('[data-cart-delivery]');
    const total = cartForm.querySelector('[data-cart-total]');
    const menuTotal = Number(recapCard.dataset.cartMenuTotal || 0);
    const reduction = Number(recapCard.dataset.cartReduction || 0);
    const deliveryPrice = await calculateDeliveryPrice();

    if (delivery) {
      delivery.textContent = deliveryPrice === 0 ? 'Offerte' : formatPrice(deliveryPrice);
    }

    if (total) {
      total.textContent = formatPrice(menuTotal - reduction + deliveryPrice);
    }
  };

  const debounce = (callback, delay = 500) => {
    // Regroupe les saisies rapprochées afin de limiter les appels aux services géographiques.
    let timeoutId;

    return () => {
      window.clearTimeout(timeoutId);
      timeoutId = window.setTimeout(callback, delay);
    };
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
    // Intercepte uniquement le bouton de recalcul ; la validation finale conserve son envoi normal.
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
    // Chaque bouton cible son propre formulaire grâce à l'attribut HTML « form ».
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

  const updateDeliveryAfterAddressChange = debounce(updateDeliveryDisplay, 500);

  [addressInput, postalCodeInput, cityInput].forEach((input) => {
    if (!input) {
      return;
    }

    input.addEventListener('input', updateDeliveryAfterAddressChange);
    input.addEventListener('change', updateDeliveryAfterAddressChange);
    input.addEventListener('blur', updateDeliveryAfterAddressChange);
  });
});
