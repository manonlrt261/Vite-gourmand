document.addEventListener('DOMContentLoaded', () => {
  const page = document.querySelector('[data-employee-orders-page]');

  if (!page) {
    return;
  }

  const filters = page.querySelector('[data-order-filters]');
  const cards = Array.from(page.querySelectorAll('[data-order-card]'));
  const emptyMessage = page.querySelector('[data-order-empty]');
  const countElement = page.querySelector('[data-order-count]');

  if (!filters || cards.length === 0) {
    return;
  }

  const fields = {
    search: filters.querySelector('[data-order-search]'),
    status: filters.querySelector('[data-order-status]'),
    period: filters.querySelector('[data-order-period]'),
    people: filters.querySelector('[data-order-people]'),
    deliveryDate: filters.querySelector('[data-order-delivery-date]'),
    postal: filters.querySelector('[data-order-postal]'),
  };

  const applyButton = filters.querySelector('[data-order-apply]');
  const resetButton = filters.querySelector('[data-order-reset]');

  const getPeriodRange = (period) => {
    const now = new Date();
    const startOfDay = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const endOfDay = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate(), 23, 59, 59, 999);

    switch (period) {
      case '10days': {
        const start = startOfDay(now);
        start.setDate(start.getDate() - 9);
        return { start, end: endOfDay(now) };
      }
      case 'previous_month':
        return {
          start: new Date(now.getFullYear(), now.getMonth() - 1, 1),
          end: new Date(now.getFullYear(), now.getMonth(), 0, 23, 59, 59, 999),
        };
      case '6months': {
        const start = startOfDay(now);
        start.setMonth(start.getMonth() - 6);
        return { start, end: endOfDay(now) };
      }
      case '2025':
        return { start: new Date(2025, 0, 1), end: new Date(2025, 11, 31, 23, 59, 59, 999) };
      case '2024':
        return { start: new Date(2024, 0, 1), end: new Date(2024, 11, 31, 23, 59, 59, 999) };
      case 'other':
        return { start: new Date(1900, 0, 1), end: new Date(2023, 11, 31, 23, 59, 59, 999) };
      default:
        return null;
    }
  };

  const isInPeopleRange = (people, range) => {
    if (!range) {
      return true;
    }

    if (range === '20-plus') {
      return people >= 20;
    }

    const [min, max] = range.split('-').map((value) => Number.parseInt(value, 10));
    return people >= min && people <= max;
  };

  const normalize = (value) => String(value || '').trim().toLowerCase();

  const applyFilters = () => {
    const search = normalize(fields.search.value);
    const status = String(fields.status.value || '');
    const periodRange = getPeriodRange(String(fields.period.value || ''));
    const peopleRange = String(fields.people.value || '');
    const deliveryDate = String(fields.deliveryDate.value || '');
    const postal = normalize(fields.postal.value);
    let visibleCount = 0;

    cards.forEach((card) => {
      const orderDate = card.dataset.orderDate ? new Date(`${card.dataset.orderDate}T12:00:00`) : null;
      const people = Number.parseInt(card.dataset.orderPeople || '0', 10);
      let isVisible = true;

      if (search && !normalize(card.dataset.orderSearch).includes(search)) {
        isVisible = false;
      }

      if (isVisible && status && card.dataset.orderStatus !== status) {
        isVisible = false;
      }

      if (isVisible && periodRange) {
        isVisible = orderDate && orderDate >= periodRange.start && orderDate <= periodRange.end;
      }

      if (isVisible && !isInPeopleRange(people, peopleRange)) {
        isVisible = false;
      }

      if (isVisible && deliveryDate && card.dataset.orderDeliveryDate !== deliveryDate) {
        isVisible = false;
      }

      if (isVisible && postal && !normalize(card.dataset.orderPostal).includes(postal)) {
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

    if (countElement) {
      countElement.textContent = String(visibleCount);
    }
  };

  const resetFilters = () => {
    Object.values(fields).forEach((field) => {
      if (field.tagName === 'SELECT') {
        field.selectedIndex = 0;
      } else {
        field.value = '';
      }
    });

    applyFilters();
  };

  const setOrderContactActionsState = (card, status) => {
    const actions = card.querySelector('[data-order-contact-actions]');
    const contactLogs = card.querySelectorAll('[data-order-contact-log]');

    if (!actions) {
      return;
    }

    const isPending = status === 'en_attente';
    actions.classList.toggle('is-disabled', !isPending);
    actions.setAttribute('aria-disabled', String(!isPending));

    actions.querySelectorAll('details').forEach((details) => {
      if (!isPending) {
        details.open = false;
      }
    });

    actions.querySelectorAll('input, select, textarea, button').forEach((field) => {
      field.disabled = !isPending;
    });

    // Quand une commande revient en attente, les actions reapparaissent
    // et le resume du dernier contact se masque pour laisser place aux formulaires.
    contactLogs.forEach((log) => {
      log.hidden = isPending;
    });
  };

  page.querySelectorAll('[data-order-status-form]').forEach((form) => {
    const select = form.querySelector('[data-order-status-select]');

    if (!select) {
      return;
    }

    let previousStatus = select.value;

    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      const card = form.closest('[data-order-card]');

      if (!card) {
        return;
      }

      // On prepare les donnees avant de desactiver le select :
      // un champ disabled n'est pas inclus dans FormData.
      const formData = new FormData(form);
      select.disabled = true;

      try {
        // Le formulaire contient le token CSRF cache : on l envoie aussi en AJAX.
        const response = await fetch(form.action, {
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
          setOrderContactActionsState(card, data.status);
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

    // Le changement de statut se fait des que l'employe choisit une valeur dans le menu.
    select.addEventListener('change', () => {
      form.requestSubmit();
    });
  });

  cards.forEach((card) => {
    setOrderContactActionsState(card, card.dataset.orderStatus || 'en_attente');
  });

  filters.addEventListener('keydown', (event) => {
    // La touche Entree applique les filtres comme le bouton principal.
    if (event.key === 'Enter' && event.target.matches('input, select')) {
      event.preventDefault();
      applyFilters();
    }
  });

  applyButton.addEventListener('click', applyFilters);
  resetButton.addEventListener('click', resetFilters);
});
