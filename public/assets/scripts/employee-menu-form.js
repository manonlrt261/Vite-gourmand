document.addEventListener('DOMContentLoaded', () => {
  const page = document.querySelector('[data-linked-meals]');

  if (!page) {
    return;
  }

  const modals = document.querySelectorAll('[data-linked-meal-modal]');
  const menuThemeSelect = document.querySelector('[data-menu-theme-source]');
  const linkedThemeSelects = document.querySelectorAll('[data-linked-meal-theme]');

  const syncLinkedTheme = (select, force = false) => {
    if (!menuThemeSelect || !select || !menuThemeSelect.value) {
      return;
    }

    if (force || !select.value || select.dataset.syncedTheme === '1') {
      select.value = menuThemeSelect.value;
      select.dataset.syncedTheme = '1';
    }
  };

  const openModal = (type) => {
    const modal = document.querySelector(`[data-linked-meal-modal="${type}"]`);

    if (!modal) {
      return;
    }

    // Le theme choisi sur le menu est automatiquement repris dans la fenetre ouverte.
    syncLinkedTheme(modal.querySelector('[data-linked-meal-theme]'), true);

    modal.hidden = false;
    document.body.classList.add('employee-modal-is-open');
    modal.querySelector('input, select, textarea, button')?.focus();
  };

  const closeModal = (modal) => {
    modal.hidden = true;
    document.body.classList.remove('employee-modal-is-open');
  };

  document.querySelectorAll('[data-linked-meal-open]').forEach((button) => {
    button.addEventListener('click', () => {
      openModal(button.dataset.linkedMealOpen);
    });
  });

  if (menuThemeSelect) {
    menuThemeSelect.addEventListener('change', () => {
      linkedThemeSelects.forEach((select) => syncLinkedTheme(select));
    });
  }

  linkedThemeSelects.forEach((select) => {
    select.addEventListener('change', () => {
      select.dataset.syncedTheme = select.value === menuThemeSelect?.value ? '1' : '0';
    });
  });

  modals.forEach((modal) => {
    modal.querySelectorAll('[data-linked-meal-close]').forEach((button) => {
      button.addEventListener('click', () => {
        closeModal(modal);
      });
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
      return;
    }

    modals.forEach((modal) => {
      if (!modal.hidden) {
        closeModal(modal);
      }
    });
  });
});
