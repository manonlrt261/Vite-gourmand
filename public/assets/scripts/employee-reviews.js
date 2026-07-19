document.addEventListener('DOMContentLoaded', () => {
  const filters = document.querySelector('[data-review-filters]');
  const getCards = () => Array.from(document.querySelectorAll('[data-review-card]'));
  const emptyMessage = document.querySelector('[data-review-empty]');
  const countLabel = document.querySelector('[data-review-count]');
  const isPendingReviewPage = Boolean(document.querySelector('.employee-reviews-layout')) && !document.querySelector('.employee-review-all-list');
  const allReviewsList = document.querySelector('.employee-review-all-list');
  const isAllReviewsPage = Boolean(allReviewsList);
  const deleteModal = document.querySelector('[data-review-delete-modal]');
  const deleteConfirmButton = document.querySelector('[data-review-delete-confirm]');
  const deleteCancelButtons = document.querySelectorAll('[data-review-delete-cancel]');
  const deleteError = document.querySelector('[data-review-delete-error]');
  let pendingDeleteForm = null;
  let lastFocusedElement = null;

  if (!filters || getCards().length === 0) {
    return;
  }

  const searchInput = filters.querySelector('[data-review-search]');
  const statusSelect = filters.querySelector('[data-review-status]');
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
    const status = statusSelect?.value || '';
    const note = noteSelect?.value || '';
    const period = periodSelect?.value || '';
    const menu = menuSelect?.value || '';
    const hasActiveFilters = Boolean(search || status || note || period || menu);
    let visibleCount = 0;

    getCards().forEach((card) => {
      const matchesSearch = !search || card.dataset.search.includes(search);
      const matchesStatus = !status || card.dataset.status === status;
      const matchesNote = !note || card.dataset.note === note;
      const matchesPeriod = getPeriodMatch(period, card.dataset.date);
      const matchesMenu = !menu || card.dataset.menuId === menu;
      const isVisible = matchesSearch && matchesStatus && matchesNote && matchesPeriod && matchesMenu;

      card.hidden = !isVisible;

      if (isVisible) {
        visibleCount += 1;
      }
    });

    if (isAllReviewsPage) {
      document.querySelectorAll('[data-review-group]').forEach((group) => {
        group.hidden = Boolean(status) && group.dataset.reviewGroup !== status;
      });
    }

    if (emptyMessage) {
      emptyMessage.hidden = visibleCount !== 0 || !hasActiveFilters;
    }

    if (countLabel) {
      countLabel.textContent = visibleCount.toString();
    }
  };

  const resetFilters = () => {
    if (searchInput) {
      searchInput.value = '';
    }

    [statusSelect, noteSelect, periodSelect, menuSelect].forEach((select) => {
      if (select) {
        select.value = '';
      }
    });

    applyFilters();
  };

  const sendReviewAction = async (form) => {
    // Le formulaire contient un champ cache "action" pour le statut choisi.
    // getAttribute('action') evite de confondre ce champ avec l'URL du formulaire.
    const response = await fetch(form.getAttribute('action'), {
      method: form.method || 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      body: new FormData(form),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(data.message || 'Action impossible.');
    }

    return data;
  };

  const updateGroupEmptyMessages = () => {
    document.querySelectorAll('[data-review-group]').forEach((group) => {
      let empty = group.querySelector('[data-review-group-empty]');
      const hasReviews = Boolean(group.querySelector('[data-review-card]'));

      if (!empty && !hasReviews) {
        const labels = {
          en_attente: 'Aucun avis en attente.',
          valide: 'Aucun avis accepté.',
          refuse: 'Aucun avis refusé.',
        };
        empty = document.createElement('p');
        empty.className = 'employee-empty';
        empty.dataset.reviewGroupEmpty = '';
        empty.textContent = labels[group.dataset.reviewGroup] || 'Aucun avis.';
        group.appendChild(empty);
      }

      if (empty) {
        empty.hidden = hasReviews;
      }
    });
  };

  const createReviewActionForm = ({ action, label, className }, actionUrl, csrfToken) => {
    const form = document.createElement('form');
    form.method = 'post';
    form.action = actionUrl;
    form.dataset.reviewAction = '';

    form.innerHTML = `
      <input type="hidden" name="_csrf_token" value="${csrfToken}">
      <input type="hidden" name="action" value="${action}">
      <button type="submit" class="${className}">${label}</button>
    `;

    return form;
  };

  const refreshReviewActions = (card, status, actionUrl, csrfToken) => {
    const actions = card.querySelector('.employee-review-card__actions');
    if (!actions) {
      return;
    }

    const deleteForm = actions.querySelector('[data-review-delete]');
    const nextActions = [
      { status: 'valide', action: 'accept', label: "Valider l'avis", className: 'buttonaccept' },
      { status: 'refuse', action: 'refuse', label: "Refuser l'avis", className: 'buttonrefuse' },
      { status: 'en_attente', action: 'pending', label: 'Remettre en attente', className: 'buttonwhite' },
    ];

    actions.innerHTML = '';
    nextActions
      .filter((item) => item.status !== status)
      .forEach((item) => {
        actions.appendChild(createReviewActionForm(item, actionUrl, csrfToken));
      });

    if (deleteForm) {
      actions.appendChild(deleteForm);
    }
  };

  const closeDeleteModal = () => {
    pendingDeleteForm = null;
    deleteModal?.setAttribute('hidden', '');
    document.body.classList.remove('employee-modal-is-open');

    if (deleteError) {
      deleteError.hidden = true;
      deleteError.textContent = '';
    }

    lastFocusedElement?.focus();
    lastFocusedElement = null;
  };

  const openDeleteModal = (form) => {
    pendingDeleteForm = form;
    lastFocusedElement = document.activeElement;

    if (deleteModal) {
      deleteModal.hidden = false;
      document.body.classList.add('employee-modal-is-open');
      deleteConfirmButton?.focus();
    }
  };

  const moveReviewCardToGroup = (card, status) => {
    const targetGroup = document.querySelector(`[data-review-group="${status}"]`);
    if (!targetGroup) {
      return;
    }

    const empty = targetGroup.querySelector('[data-review-group-empty]');
    targetGroup.insertBefore(card, empty || null);
    updateGroupEmptyMessages();
  };

  document.addEventListener('submit', async (event) => {
    const deleteForm = event.target.matches('[data-review-delete]') ? event.target : null;
    if (deleteForm) {
      event.preventDefault();
      openDeleteModal(deleteForm);
      return;
    }

    const form = event.target.matches('[data-review-action]') ? event.target : null;
    if (!form) {
      return;
    }

    event.preventDefault();

      const button = form.querySelector('button');
      const card = form.closest('[data-review-card]');
      const formData = new FormData(form);
      const actionUrl = form.getAttribute('action');
      const csrfToken = formData.get('_csrf_token') || '';

      if (button) {
        button.disabled = true;
      }

      try {
        const data = await sendReviewAction(form);

        if (data.status && card) {
          card.dataset.status = data.status;
          card.querySelector('[data-review-status-label]').textContent = data.label;

          // Sur la page "Gestion des avis", seuls les avis en attente doivent rester visibles.
          // Un avis valide ou refuse disparait donc aussitot et se retrouve dans "Tous les avis".
          if (isPendingReviewPage && data.status !== 'en_attente') {
            card.remove();
            applyFilters();
          }

          // Sur la page "Tous les avis", la carte rejoint automatiquement la bonne section.
          if (isAllReviewsPage) {
            moveReviewCardToGroup(card, data.status);
            refreshReviewActions(card, data.status, actionUrl, csrfToken);
            applyFilters();
          }
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

  deleteConfirmButton?.addEventListener('click', async () => {
    if (!pendingDeleteForm) {
      closeDeleteModal();
      return;
    }

    deleteConfirmButton.disabled = true;

    try {
      const form = pendingDeleteForm;
      const card = form.closest('[data-review-card]');
      const data = await sendReviewAction(form);

      if (data.success) {
        card?.remove();
        closeDeleteModal();
        updateGroupEmptyMessages();
        applyFilters();
      }
    } catch (error) {
      if (deleteError) {
        deleteError.textContent = error.message;
        deleteError.hidden = false;
      }
    } finally {
      deleteConfirmButton.disabled = false;
    }
  });

  deleteCancelButtons.forEach((button) => {
    button.addEventListener('click', closeDeleteModal);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && deleteModal && !deleteModal.hidden) {
      closeDeleteModal();
    }
  });

  filters.addEventListener('keydown', (event) => {
    // La touche Entree applique les filtres comme le bouton principal.
    if (event.key === 'Enter' && event.target.matches('input, select')) {
      event.preventDefault();
      applyFilters();
    }
  });

  updateGroupEmptyMessages();
  filters.addEventListener('input', applyFilters);
  filters.addEventListener('change', applyFilters);
  applyButton?.addEventListener('click', applyFilters);
  resetButton?.addEventListener('click', resetFilters);
});
