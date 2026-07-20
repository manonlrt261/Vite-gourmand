// Pilote la liste des employés : filtres, changement de statut et modales de consultation, modification ou suppression.
document.addEventListener('DOMContentLoaded', () => {
  const page = document.querySelector('[data-admin-employees-page]');

  if (!page) {
    return;
  }

  const searchInput = page.querySelector('[data-employee-search]');
  const statusSelect = page.querySelector('[data-employee-status]');
  const jobSelect = page.querySelector('[data-employee-job]');
  const applyButton = page.querySelector('[data-employee-apply]');
  const resetButton = page.querySelector('[data-employee-reset]');
  const cards = () => Array.from(page.querySelectorAll('[data-employee-card]'));
  const emptyMessage = page.querySelector('[data-employee-empty]');

  const editModal = page.querySelector('[data-employee-edit-modal]');
  const editForm = page.querySelector('[data-employee-edit-form]');
  const editError = page.querySelector('[data-edit-error]');
  const viewModal = page.querySelector('[data-employee-view-modal]');
  const deleteModal = page.querySelector('[data-employee-delete-modal]');
  const deleteConfirm = page.querySelector('[data-delete-confirm]');
  // Le jeton CSRF fourni par Twig protège la suppression envoyée en AJAX.
  const csrfToken = page.dataset.adminCsrfToken || '';

  let editedCard = null;
  let deletedCard = null;
  let deleteUrl = '';
  let lastFocusedElement = null;

  const normalize = (value) => value.toString().trim().toLowerCase();

  // Convertit une date ISO en JJ/MM/AAAA sans modifier une valeur au format inattendu.
  const formatFrenchDate = (value) => {
    if (!value) {
      return '';
    }

    const parts = value.split('-');

    if (parts.length !== 3) {
      return value;
    }

    return `${parts[2]}/${parts[1]}/${parts[0]}`;
  };

  const requestJson = async (url, options = {}) => {
    // Centralise les en-têtes AJAX et transforme les erreurs HTTP en exceptions affichables.
    const response = await fetch(url, {
      ...options,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
        ...(options.headers || {}),
      },
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(data.message || 'Une erreur est survenue.');
    }

    return data;
  };

  const applyFilters = () => {
    // Une carte reste visible uniquement si elle satisfait simultanément la recherche, le statut et le poste.
    const search = normalize(searchInput?.value || '');
    const status = statusSelect?.value || '';
    const job = jobSelect?.value || '';
    let visibleCount = 0;

    cards().forEach((card) => {
      const matchesSearch = !search || normalize(card.dataset.search || '').includes(search);
      const matchesStatus = !status || card.dataset.status === status;
      const matchesJob = !job || card.dataset.job === job;
      const isVisible = matchesSearch && matchesStatus && matchesJob;

      card.hidden = !isVisible;
      card.classList.toggle('is-hidden-by-filter', !isVisible);

      if (isVisible) {
        visibleCount += 1;
      }
    });

    if (emptyMessage) {
      emptyMessage.hidden = visibleCount !== 0;
    }
  };

  const resetFilters = () => {
    if (searchInput) {
      searchInput.value = '';
    }

    if (statusSelect) {
      statusSelect.value = '';
    }

    if (jobSelect) {
      jobSelect.value = '';
    }

    applyFilters();
  };

  const setStatusButton = (button, active) => {
    button.textContent = active ? 'Actif' : 'Inactif';
    button.classList.toggle('badgeactif', active);
    button.classList.toggle('badgeinactif', !active);
  };

  page.querySelectorAll('[data-employee-toggle-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      const button = form.querySelector('[data-employee-status-button]');
      const card = form.closest('[data-employee-card]');

      if (!button || !card) {
        return;
      }

      button.disabled = true;

      try {
        const data = await requestJson(form.action, {
          method: form.method || 'POST',
          body: new FormData(form),
        });

        card.dataset.status = data.active ? '1' : '0';
        setStatusButton(button, data.active);
        applyFilters();
      } catch (error) {
        window.alert(error.message);
      } finally {
        button.disabled = false;
      }
    });
  });

  const fillEditForm = (button) => {
    // Les données embarquées sur le bouton évitent une requête supplémentaire à l'ouverture de la modale.
    editForm.action = button.dataset.updateUrl || '';
    editForm.querySelector('[data-edit-id]').value = button.dataset.id || '';
    editForm.querySelector('[data-edit-prenom]').value = button.dataset.prenom || '';
    editForm.querySelector('[data-edit-nom]').value = button.dataset.nom || '';
    editForm.querySelector('[data-edit-date-naissance]').value = button.dataset.dateNaissance || '';
    editForm.querySelector('[data-edit-lieu-naissance]').value = button.dataset.lieuNaissance || '';
    editForm.querySelector('[data-edit-email]').value = button.dataset.email || '';
    editForm.querySelector('[data-edit-email-personnel]').value = button.dataset.emailPersonnel || '';
    editForm.querySelector('[data-edit-telephone]').value = button.dataset.telephone || '';
    editForm.querySelector('[data-edit-adresse]').value = button.dataset.adresse || '';
    editForm.querySelector('[data-edit-ville]').value = button.dataset.ville || '';
    editForm.querySelector('[data-edit-code-postal]').value = button.dataset.codePostal || '';
    editForm.querySelector('[data-edit-poste]').value = button.dataset.poste || '';
  };

  const openEditModal = (button) => {
    lastFocusedElement = button;
    editedCard = button.closest('[data-employee-card]');
    fillEditForm(button);

    if (editError) {
      editError.hidden = true;
      editError.textContent = '';
    }

    editModal.hidden = false;
    document.body.classList.add('employee-modal-is-open');
    // Le premier champ reçoit le point de focalisation pour permettre la modification sans souris.
    editForm.querySelector('[data-edit-prenom]')?.focus();
  };

  const closeEditModal = () => {
    editModal.hidden = true;
    document.body.classList.remove('employee-modal-is-open');
    editedCard = null;
    lastFocusedElement?.focus();
    lastFocusedElement = null;
  };

  const fillViewModal = (button) => {
    viewModal.querySelector('[data-view-nom]').textContent = button.dataset.nom || 'Non renseigné';
    viewModal.querySelector('[data-view-prenom]').textContent = button.dataset.prenom || 'Non renseigné';
    viewModal.querySelector('[data-view-date-naissance]').textContent = formatFrenchDate(button.dataset.dateNaissance || '') || 'Non renseignée';
    viewModal.querySelector('[data-view-lieu-naissance]').textContent = button.dataset.lieuNaissance || 'Non renseigné';
    viewModal.querySelector('[data-view-adresse]').textContent = button.dataset.adresse || 'Non renseignée';
    viewModal.querySelector('[data-view-code-postal]').textContent = button.dataset.codePostal || 'Non renseigné';
    viewModal.querySelector('[data-view-ville]').textContent = button.dataset.ville || 'Non renseignée';
    viewModal.querySelector('[data-view-email]').textContent = button.dataset.email || 'Non renseigné';
    viewModal.querySelector('[data-view-email-personnel]').textContent = button.dataset.emailPersonnel || 'Non renseigné';
    viewModal.querySelector('[data-view-telephone]').textContent = button.dataset.telephone || 'Non renseigné';
    viewModal.querySelector('[data-view-poste]').textContent = button.dataset.poste || 'Non renseigné';
  };

  const openViewModal = (button) => {
    lastFocusedElement = button;
    fillViewModal(button);
    viewModal.hidden = false;
    document.body.classList.add('employee-modal-is-open');
    viewModal.querySelector('[data-view-modal-close]')?.focus();
  };

  const closeViewModal = () => {
    viewModal.hidden = true;
    document.body.classList.remove('employee-modal-is-open');
    lastFocusedElement?.focus();
    lastFocusedElement = null;
  };

  const updateEmployeeCard = (employee) => {
    // Répercute la réponse du serveur dans la carte et dans les attributs utilisés lors des prochaines ouvertures.
    if (!editedCard) {
      return;
    }

    const fullName = `${employee.prenom || ''} ${employee.nom || ''}`.trim();
    const editButton = editedCard.querySelector('[data-employee-edit]');
    const viewButton = editedCard.querySelector('[data-employee-view]');

    editedCard.dataset.search = normalize(`${employee.id} ${employee.nom || ''} ${employee.prenom || ''} ${employee.email || ''} ${employee.email_personnel || ''} ${employee.telephone || ''} ${employee.poste || ''}`);
    editedCard.dataset.job = employee.poste || '';

    editedCard.querySelector('[data-employee-name]').textContent = fullName;
    editedCard.querySelector('[data-employee-identity]').textContent = `ID ${employee.id} · ${employee.email || ''}`;
    editedCard.querySelector('[data-employee-job-label]').textContent = employee.poste || '';

    if (editButton) {
      editButton.dataset.prenom = employee.prenom || '';
      editButton.dataset.nom = employee.nom || '';
      editButton.dataset.dateNaissance = employee.date_naissance || '';
      editButton.dataset.lieuNaissance = employee.lieu_naissance || '';
      editButton.dataset.email = employee.email || '';
      editButton.dataset.emailPersonnel = employee.email_personnel || '';
      editButton.dataset.telephone = employee.telephone || '';
      editButton.dataset.adresse = employee.adresse_postale || '';
      editButton.dataset.ville = employee.ville || '';
      editButton.dataset.codePostal = employee.code_postal || '';
      editButton.dataset.poste = employee.poste || '';
    }

    if (viewButton) {
      viewButton.dataset.prenom = employee.prenom || '';
      viewButton.dataset.nom = employee.nom || '';
      viewButton.dataset.dateNaissance = employee.date_naissance || '';
      viewButton.dataset.lieuNaissance = employee.lieu_naissance || '';
      viewButton.dataset.email = employee.email || '';
      viewButton.dataset.emailPersonnel = employee.email_personnel || '';
      viewButton.dataset.telephone = employee.telephone || '';
      viewButton.dataset.adresse = employee.adresse_postale || '';
      viewButton.dataset.ville = employee.ville || '';
      viewButton.dataset.codePostal = employee.code_postal || '';
      viewButton.dataset.poste = employee.poste || '';
    }
  };

  page.querySelectorAll('[data-employee-view]').forEach((button) => {
    button.addEventListener('click', () => openViewModal(button));
  });

  page.querySelectorAll('[data-employee-edit]').forEach((button) => {
    button.addEventListener('click', () => openEditModal(button));
  });

  editForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const submitButton = editForm.querySelector('button[type="submit"]');
    submitButton.disabled = true;

    try {
      const data = await requestJson(editForm.action, {
        method: 'POST',
        body: new FormData(editForm),
      });

      updateEmployeeCard(data.employee);
      closeEditModal();
      applyFilters();
    } catch (error) {
      if (editError) {
        editError.textContent = error.message;
        editError.hidden = false;
      }
    } finally {
      submitButton.disabled = false;
    }
  });

  const openDeleteModal = (button) => {
    lastFocusedElement = button;
    deletedCard = button.closest('[data-employee-card]');
    deleteUrl = button.dataset.deleteUrl || '';
    deleteModal.hidden = false;
    document.body.classList.add('employee-modal-is-open');
    deleteConfirm?.focus();
  };

  const closeDeleteModal = () => {
    deletedCard = null;
    deleteUrl = '';
    deleteModal.hidden = true;
    document.body.classList.remove('employee-modal-is-open');
    lastFocusedElement?.focus();
    lastFocusedElement = null;
  };

  page.querySelectorAll('[data-employee-delete]').forEach((button) => {
    button.addEventListener('click', () => openDeleteModal(button));
  });

  deleteConfirm?.addEventListener('click', async () => {
    // La carte n'est retirée du DOM qu'après confirmation de la suppression par le serveur.
    if (!deleteUrl || !deletedCard) {
      closeDeleteModal();
      return;
    }

    deleteConfirm.disabled = true;

    try {
      const formData = new FormData();
      formData.append('_csrf_token', csrfToken);

      await requestJson(deleteUrl, {
        method: 'POST',
        body: formData,
      });
      deletedCard.remove();
      closeDeleteModal();
      applyFilters();
    } catch (error) {
      window.alert(error.message);
    } finally {
      deleteConfirm.disabled = false;
    }
  });

  page.querySelectorAll('[data-employee-modal-close]').forEach((button) => {
    button.addEventListener('click', closeEditModal);
  });

  page.querySelectorAll('[data-delete-modal-close]').forEach((button) => {
    button.addEventListener('click', closeDeleteModal);
  });

  page.querySelectorAll('[data-view-modal-close]').forEach((button) => {
    button.addEventListener('click', closeViewModal);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      if (editModal && !editModal.hidden) {
        closeEditModal();
      }

      if (deleteModal && !deleteModal.hidden) {
        closeDeleteModal();
      }

      if (viewModal && !viewModal.hidden) {
        closeViewModal();
      }
    }
  });

  [searchInput, statusSelect, jobSelect].forEach((field) => {
    field?.addEventListener('keydown', (event) => {
      // La touche Entrée applique les filtres comme le bouton principal.
      if (event.key === 'Enter') {
        event.preventDefault();
        applyFilters();
      }
    });
  });

  applyButton?.addEventListener('click', applyFilters);
  resetButton?.addEventListener('click', resetFilters);
});
