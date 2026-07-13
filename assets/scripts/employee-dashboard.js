document.addEventListener('DOMContentLoaded', () => {
  const dashboard = document.querySelector('[data-employee-dashboard]');
  const form = document.querySelector('[data-employee-order-filters]');
  const orderCards = Array.from(document.querySelectorAll('[data-order-card]'));
  const emptyMessage = document.querySelector('[data-employee-orders-empty]');
  const resetButton = form ? form.querySelector('button[type="reset"]') : null;

  if (!dashboard || !form || orderCards.length === 0) {
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

  if (resetButton) {
    resetButton.addEventListener('click', () => {
      window.setTimeout(() => applyFilters(), 0);
    });
  }
});
