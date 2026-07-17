// Gère en AJAX l'activation et la suppression des menus, plats et formules depuis l'espace employé.
document.addEventListener('DOMContentLoaded', () => {
  const toggleForms = document.querySelectorAll('[data-toggle-active-form]');
  const deleteForms = document.querySelectorAll('[data-confirm-delete]');
  const modal = document.querySelector('[data-delete-modal]');
  const confirmButton = document.querySelector('[data-delete-confirm]');
  const cancelButtons = document.querySelectorAll('[data-delete-cancel]');
  let pendingDeleteForm = null;
  let lastFocusedElement = null;

  const setStatusButton = (button, active) => {
    button.textContent = active ? 'Actif' : 'Inactif';
    button.classList.toggle('badgeactif', active);
    button.classList.toggle('badgeinactif', !active);
  };

  const syncChildrenStatus = (menuId, active) => {
    // Répercute visuellement l'état d'un menu sur les éléments qui lui sont rattachés.
    if (!menuId) {
      return;
    }

    document.querySelectorAll(`[data-parent-menu-id="${menuId}"] [data-status-button], [data-toggle-active-form][data-parent-menu-id="${menuId}"] [data-status-button]`).forEach((button) => {
      setStatusButton(button, active);
      button.title = active ? '' : 'Le menu associé est inactif.';
    });
  };

  const sendForm = async (form) => {
    // Uniformise l'envoi des actions et restitue le message métier renvoyé en cas d'échec HTTP.
    const response = await fetch(form.action, {
      method: form.method || 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      body: new FormData(form),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(data.message || 'Request failed');
    }

    return data;
  };

  toggleForms.forEach((form) => {
    // Le bouton reste verrouillé pendant la requête pour prévenir les changements de statut concurrents.
    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      const button = form.querySelector('[data-status-button]');
      if (!button) {
        return;
      }

      button.disabled = true;

      try {
        const data = await sendForm(form);

        if (data.success) {
          setStatusButton(button, data.active);

          if (data.type === 'menu') {
            syncChildrenStatus(String(data.affectedMenuId), data.active);
          }
        }
      } catch (error) {
        window.alert(error.message);
      } finally {
        button.disabled = false;
      }
    });
  });

  const openDeleteModal = (form) => {
    pendingDeleteForm = form;
    lastFocusedElement = document.activeElement;

    if (modal) {
      modal.hidden = false;
      document.body.classList.add('employee-modal-is-open');
      // Le point de focalisation arrive sur l'action principale de la fenêtre de confirmation.
      confirmButton?.focus();
    }
  };

  const closeDeleteModal = () => {
    pendingDeleteForm = null;

    if (modal) {
      modal.hidden = true;
      document.body.classList.remove('employee-modal-is-open');
      lastFocusedElement?.focus();
      lastFocusedElement = null;
    }
  };

  deleteForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      openDeleteModal(form);
    });
  });

  confirmButton?.addEventListener('click', async () => {
    // Conserve le formulaire en attente jusqu'à la confirmation explicite dans la modale.
    if (!pendingDeleteForm) {
      closeDeleteModal();
      return;
    }

    confirmButton.disabled = true;

    try {
      const form = pendingDeleteForm;
      const data = await sendForm(form);

      if (data.success) {
        form.closest('[data-management-card]')?.remove();
      }

      closeDeleteModal();
    } finally {
      confirmButton.disabled = false;
    }
  });

  cancelButtons.forEach((button) => {
    button.addEventListener('click', closeDeleteModal);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal && !modal.hidden) {
      closeDeleteModal();
    }
  });
});
