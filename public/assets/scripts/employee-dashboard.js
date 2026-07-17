document.addEventListener('DOMContentLoaded', () => {
  const dashboard = document.querySelector('[data-employee-dashboard]');
  const form = document.querySelector('[data-employee-order-filters]');
  const orderCards = Array.from(document.querySelectorAll('[data-order-card]'));
  const emptyMessage = document.querySelector('[data-employee-orders-empty]');
  const resetButton = form ? form.querySelector('button[type="reset"]') : null;

  if (!dashboard || !form) {
    return;
  }

  const getPeriodRange = (period) => {
    const now = new Date();
    const startOfDay = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const endOfDay = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate(), 23, 59, 59, 999);

    switch (period) {
      case 'last_10_days': {
        const start = startOfDay(now);
        start.setDate(start.getDate() - 9);
        return { start, end: endOfDay(now) };
      }
      case 'last_month': {
        const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const end = new Date(now.getFullYear(), now.getMonth(), 0, 23, 59, 59, 999);
        return { start, end };
      }
      case 'last_6_months': {
        const start = startOfDay(now);
        start.setMonth(start.getMonth() - 6);
        return { start, end: endOfDay(now) };
      }
      case 'current_year':
        return {
          start: new Date(now.getFullYear(), 0, 1),
          end: new Date(now.getFullYear(), 11, 31, 23, 59, 59, 999),
        };
      case 'previous_year':
        return {
          start: new Date(now.getFullYear() - 1, 0, 1),
          end: new Date(now.getFullYear() - 1, 11, 31, 23, 59, 59, 999),
        };
      case 'two_years_ago':
        return {
          start: new Date(now.getFullYear() - 2, 0, 1),
          end: new Date(now.getFullYear() - 2, 11, 31, 23, 59, 59, 999),
        };
      default:
        return null;
    }
  };

  const parseOrderIds = (value) => value
    .split(/[\s,;]+/)
    .map((item) => item.trim())
    .filter(Boolean);

  const applyFilters = (event = null) => {
    if (event) {
      event.preventDefault();
    }

    const formData = new FormData(form);
    const searchedIds = parseOrderIds(String(formData.get('orders') || ''));
    const selectedStatus = String(formData.get('status') || '');
    const selectedPeriod = String(formData.get('period') || '');
    const periodRange = getPeriodRange(selectedPeriod);
    const hasFilters = searchedIds.length > 0 || selectedStatus !== '' || selectedPeriod !== '';
    let visibleCount = 0;

    orderCards.forEach((card, index) => {
      const orderId = card.dataset.orderId || '';
      const orderStatus = card.dataset.orderStatus || '';
      const orderDate = card.dataset.orderDate ? new Date(`${card.dataset.orderDate}T12:00:00`) : null;
      let isVisible = true;

      if (searchedIds.length > 0) {
        isVisible = searchedIds.includes(orderId);
      }

      if (isVisible && selectedStatus !== '') {
        isVisible = orderStatus === selectedStatus;
      }

      if (isVisible && periodRange) {
        isVisible = orderDate && orderDate >= periodRange.start && orderDate <= periodRange.end;
      }

      if (!hasFilters && index >= 4) {
        isVisible = false;
      }

      card.hidden = !isVisible;

      if (isVisible) {
        visibleCount += 1;
      }
    });

    if (emptyMessage) {
      emptyMessage.hidden = visibleCount > 0;
    }
  };

  form.addEventListener('submit', applyFilters);

  form.addEventListener('keydown', (event) => {
    // La touche Entree applique les filtres comme le bouton Valider.
    if (event.key === 'Enter' && event.target.matches('input, select')) {
      event.preventDefault();
      applyFilters();
    }
  });

  if (resetButton) {
    resetButton.addEventListener('click', () => {
      window.setTimeout(() => applyFilters(), 0);
    });
  }

  dashboard.querySelectorAll('[data-dashboard-order-status-form]').forEach((statusForm) => {
    const select = statusForm.querySelector('[data-dashboard-order-status-select]');

    if (!select) {
      return;
    }

    let previousStatus = select.value;

    statusForm.addEventListener('submit', async (event) => {
      event.preventDefault();

      const card = statusForm.closest('[data-order-card]');

      if (!card) {
        return;
      }

      // Le FormData doit etre cree avant de desactiver le select,
      // sinon la valeur choisie ne serait pas envoyee.
      const formData = new FormData(statusForm);
      select.disabled = true;

      try {
        const response = await fetch(statusForm.action, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
          },
        });

        const data = await response.json();

        if (data.success) {
          card.dataset.orderStatus = data.status;
          select.value = data.status;
          previousStatus = data.status;
          select.classList.remove('badgeattente', 'badgevalidee', 'badgeterminee');
          select.classList.add(data.className);
          applyFilters();
        } else {
          select.value = previousStatus;
        }
      } catch (error) {
        console.error(error);
        select.value = previousStatus;
      } finally {
        select.disabled = (card.dataset.orderStatus || '') === 'annulee';
      }
    });

    // Le statut se met a jour des que l'employe choisit une valeur.
    select.addEventListener('change', () => {
      statusForm.requestSubmit();
    });
  });

  dashboard.addEventListener('submit', async (event) => {
    const reviewForm = event.target.closest('[data-dashboard-review-action]');

    if (!reviewForm) {
      return;
    }

    event.preventDefault();

    const reviewCard = reviewForm.closest('.latestreview');
    const reviewList = reviewForm.closest('.employee-review-list');
    const buttons = reviewCard ? Array.from(reviewCard.querySelectorAll('button')) : [];
    const previousError = reviewCard ? reviewCard.querySelector('[data-dashboard-review-error]') : null;

    if (previousError) {
      previousError.remove();
    }

    buttons.forEach((button) => {
      button.disabled = true;
    });

    try {
      // Les boutons Valider et Refuser envoient le meme formulaire qu'avant,
      // mais en AJAX pour rester sur le tableau de bord.
      // Le champ cache s'appelle "action" pour indiquer accepter/refuser.
      // On lit donc l'attribut HTML du formulaire, sinon reviewForm.action
      // peut pointer vers le champ cache au lieu de l'URL.
      const response = await fetch(reviewForm.getAttribute('action'), {
        method: 'POST',
        body: new FormData(reviewForm),
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
        },
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Action impossible pour le moment.');
      }

      if (reviewCard) {
        reviewCard.remove();
      }

      if (reviewList && !reviewList.querySelector('.latestreview') && !reviewList.querySelector('.employee-empty')) {
        const empty = document.createElement('p');
        empty.className = 'employee-empty';
        empty.textContent = 'Aucun avis en attente.';
        reviewList.appendChild(empty);
      }
    } catch (error) {
      console.error(error);

      if (reviewCard) {
        const message = document.createElement('p');
        message.className = 'employee-empty';
        message.dataset.dashboardReviewError = 'true';
        message.textContent = 'Impossible de modifier cet avis pour le moment.';
        reviewCard.appendChild(message);
      }

      buttons.forEach((button) => {
        button.disabled = false;
      });
    }
  });
});
