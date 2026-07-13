document.addEventListener('DOMContentLoaded', () => {
  const filters = document.querySelector('[data-review-filters]');
  const cards = Array.from(document.querySelectorAll('[data-review-card]'));
  const emptyMessage = document.querySelector('[data-review-empty]');
  const countLabel = document.querySelector('[data-review-count]');

  if (!filters || cards.length === 0) {
    return;
  }

  const searchInput = filters.querySelector('[data-review-search]');
  const noteSelect = filters.querySelector('[data-review-note]');
  const periodSelect = filters.querySelector('[data-review-period]');
  const menuSelect = filters.querySelector('[data-review-menu]');
  const applyButton = filters.querySelector('[data-review-apply]');
  const resetButton = filters.querySelector('[data-review-reset]');

  const getPeriodMatch = (value, dateText) => {
    if (!value) {
      return true;
    }

    const reviewDate = new Date(`${dateText}T00:00:00`);
    const today = new Date();
    const startOfToday = new Date(today.getFullYear(), today.getMonth(), today.getDate());

    if (value === '10days') {
      const limit = new Date(startOfToday);
      limit.setDate(limit.getDate() - 10);
      return reviewDate >= limit;
    }

    if (value === 'previous_month') {
      const firstPreviousMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
      const firstCurrentMonth = new Date(today.getFullYear(), today.getMonth(), 1);
      return reviewDate >= firstPreviousMonth && reviewDate < firstCurrentMonth;
    }

    if (value === '6months') {
      const limit = new Date(startOfToday);
      limit.setMonth(limit.getMonth() - 6);
      return reviewDate >= limit;
    }

    if (value === '2025' || value === '2024') {
      return reviewDate.getFullYear().toString() === value;
    }

    if (value === 'other') {
      return !['2024', '2025'].includes(reviewDate.getFullYear().toString());
    }

    return true;
  };

  const applyFilters = () => {
    const search = (searchInput?.value || '').trim().toLowerCase();
    const note = noteSelect?.value || '';
    const period = periodSelect?.value || '';
    const menu = menuSelect?.value || '';
    let visibleCount = 0;

    cards.forEach((card) => {
      const matchesSearch = !search || card.dataset.search.includes(search);
      const matchesNote = !note || card.dataset.note === note;
      const matchesPeriod = getPeriodMatch(period, card.dataset.date);
      const matchesMenu = !menu || card.dataset.menuId === menu;
      const isVisible = matchesSearch && matchesNote && matchesPeriod && matchesMenu;

      card.hidden = !isVisible;

      if (isVisible) {
        visibleCount += 1;
      }
    });

    if (emptyMessage) {
      emptyMessage.hidden = visibleCount !== 0;
    }

    if (countLabel) {
      countLabel.textContent = visibleCount.toString();
    }
  };

  const resetFilters = () => {
    if (searchInput) {
      searchInput.value = '';
    }

    [noteSelect, periodSelect, menuSelect].forEach((select) => {
      if (select) {
        select.value = '';
      }
    });

    applyFilters();
  };

  const sendReviewAction = async (form) => {
    const response = await fetch(form.action, {
      method: form.method || 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      body: new FormData(form),
    });

    if (!response.ok) {
      throw new Error('Action impossible.');
    }

    return response.json();
  };

  document.querySelectorAll('[data-review-action]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      const button = form.querySelector('button');
      const card = form.closest('[data-review-card]');

      if (button) {
        button.disabled = true;
      }

      try {
        const data = await sendReviewAction(form);

        if (data.status && card) {
          card.dataset.status = data.status;
          card.querySelector('[data-review-status-label]').textContent = data.label;
        }

        if (typeof data.home === 'boolean') {
          const homeButton = form.querySelector('[data-home-button]');
          if (homeButton) {
            homeButton.textContent = data.label;
          }

          if (card) {
            card.dataset.home = data.home ? '1' : '0';
          }
        }
      } finally {
        if (button) {
          button.disabled = false;
        }
      }
    });
  });

  applyButton?.addEventListener('click', applyFilters);
  resetButton?.addEventListener('click', resetFilters);
});
