document.addEventListener('DOMContentLoaded', () => {
  const filterPanel = document.querySelector('[data-menu-filters]');
  const cards = Array.from(document.querySelectorAll('[data-menu-card]'));
  const categorySections = Array.from(document.querySelectorAll('[data-menu-section]'));
  const resultsSection = document.querySelector('[data-results-section]');
  const resultsGrid = document.querySelector('[data-results-grid]');
  const emptyMessage = document.querySelector('[data-empty-message]');

  if (!filterPanel || cards.length === 0 || !resultsSection || !resultsGrid) {
    return;
  }

  const fields = {
    priceMin: filterPanel.querySelector('[data-filter-price-min]'),
    priceMax: filterPanel.querySelector('[data-filter-price-max]'),
    stock: filterPanel.querySelector('[data-filter-stock]'),
    theme: filterPanel.querySelector('[data-filter-theme]'),
    regime: filterPanel.querySelector('[data-filter-regime]'),
    minPerson: filterPanel.querySelector('[data-filter-min-person]'),
    sort: filterPanel.querySelector('[data-filter-sort]'),
  };

  const resetButton = filterPanel.querySelector('[data-filter-reset]');
  const submitButton = filterPanel.querySelector('[data-filter-submit]');
  const sectionToggles = Array.from(document.querySelectorAll('[data-menu-section-toggle]'));

  const originalCards = cards.map((card, index) => ({
    card,
    index,
    grid: card.closest('[data-menu-grid]'),
  }));

  const getNumber = (card, name) => Number.parseFloat(card.dataset[name] || '0');

  const getSelectedFilters = () => ({
    priceMin: fields.priceMin.value === '' ? null : Number.parseFloat(fields.priceMin.value),
    priceMax: fields.priceMax.value === '' ? null : Number.parseFloat(fields.priceMax.value),
    stock: fields.stock.value,
    theme: fields.theme.value,
    regime: fields.regime.value,
    minPerson: fields.minPerson.value,
    sort: fields.sort.value,
  });

  const isInPersonRange = (minimumPerson, range) => {
    if (!range) {
      return true;
    }

    if (range === '20-plus') {
      return minimumPerson >= 20;
    }

    const [min, max] = range.split('-').map((value) => Number.parseInt(value, 10));
    return minimumPerson >= min && minimumPerson <= max;
  };

  const cardMatchesFilters = (card, filters) => {
    const price = getNumber(card, 'price');
    const stock = getNumber(card, 'stock');
    const minimumPerson = getNumber(card, 'minimumPerson');

    if (filters.priceMin !== null && price < filters.priceMin) {
      return false;
    }

    if (filters.priceMax !== null && price > filters.priceMax) {
      return false;
    }

    if (filters.stock === 'available' && stock <= 0) {
      return false;
    }

    if (filters.theme && card.dataset.category !== filters.theme) {
      return false;
    }

    if (filters.regime && card.dataset.regime !== filters.regime) {
      return false;
    }

    if (!isInPersonRange(minimumPerson, filters.minPerson)) {
      return false;
    }

    return true;
  };

  const sortCards = (visibleCards, filters) => {
    const sortValue = filters.sort;

    return visibleCards.sort((first, second) => {
      const firstCard = first.card;
      const secondCard = second.card;

      if (sortValue === 'price-asc') {
        return getNumber(firstCard, 'price') - getNumber(secondCard, 'price');
      }

      if (sortValue === 'price-desc') {
        return getNumber(secondCard, 'price') - getNumber(firstCard, 'price');
      }

      if (sortValue === 'person-asc') {
        return getNumber(firstCard, 'minimumPerson') - getNumber(secondCard, 'minimumPerson');
      }

      if (sortValue === 'person-desc') {
        return getNumber(secondCard, 'minimumPerson') - getNumber(firstCard, 'minimumPerson');
      }

      if (sortValue === 'popularity') {
        return getNumber(secondCard, 'popularity') - getNumber(firstCard, 'popularity');
      }

      return first.index - second.index;
    });
  };

  const applyFilters = () => {
    const filters = getSelectedFilters();
    const visibleCards = originalCards.filter(({ card }) => cardMatchesFilters(card, filters));
    const sortedCards = sortCards(visibleCards, filters);

    categorySections.forEach((section) => {
      section.hidden = true;
    });

    cards.forEach((card) => {
      card.hidden = true;
    });

    sortedCards.forEach(({ card }) => {
      card.hidden = false;
      resultsGrid.appendChild(card);
    });

    resultsSection.hidden = sortedCards.length === 0;
    emptyMessage.hidden = sortedCards.length > 0;
  };

  const resetFilters = () => {
    Object.values(fields).forEach((field) => {
      field.value = '';
    });

    originalCards.forEach(({ card, grid }) => {
      card.hidden = false;
      grid.appendChild(card);
    });

    categorySections.forEach((section) => {
      section.hidden = false;
    });

    resultsSection.hidden = true;
    emptyMessage.hidden = true;
  };

  filterPanel.addEventListener('keydown', (event) => {
    // La touche Entree applique les filtres comme le bouton principal.
    if (event.key === 'Enter' && event.target.matches('input, select')) {
      event.preventDefault();

      if (event.target.matches('[data-filter-price-min], [data-filter-price-max]') && event.target.value === '') {
        resetFilters();
        return;
      }

      applyFilters();
    }
  });

  submitButton.addEventListener('click', applyFilters);
  resetButton.addEventListener('click', resetFilters);

  sectionToggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
      const section = toggle.closest('[data-menu-section]');
      const gridId = toggle.getAttribute('aria-controls');
      const grid = gridId ? document.getElementById(gridId) : section?.querySelector('[data-menu-grid]');

      if (!section || !grid) {
        return;
      }

      const isCollapsed = section.classList.toggle('is-collapsed');
      toggle.setAttribute('aria-expanded', String(!isCollapsed));
    });
  });
});
