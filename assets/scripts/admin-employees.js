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
  const deleteModal = page.querySelector('[data-employee-delete-modal]');
  const deleteConfirm = page.querySelector('[data-delete-confirm]');

  let editedCard = null;
  let deletedCard = null;
  let deleteUrl = '';

  const normalize = (value) => value.toString().trim().toLowerCase();

  const requestJson = async (url, options = {}) => {
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
    editForm.action = button.dataset.updateUrl || '';
    editForm.querySelector('[data-edit-id]').value = button.dataset.id || '';
    editForm.querySelector('[data-edit-prenom]').value = button.dataset.prenom || '';
    editForm.querySelector('[data-edit-nom]').value = button.dataset.nom || '';
    editForm.querySelector('[data-edit-email]').value = button.dataset.email || '';
    editForm.querySelector('[data-edit-telephone]').value = button.dataset.telephone || '';
    editForm.querySelector('[data-edit-adresse]').value = button.dataset.adresse || '';
    editForm.querySelector('[data-edit-ville]').value = button.dataset.ville || '';
    editForm.querySelector('[data-edit-code-postal]').value = button.dataset.codePostal || '';
    editForm.querySelector('[data-edit-poste]').value = button.dataset.poste || '';
  };

  const openEditModal = (button) => {
    editedCard = button.closest('[data-employee-card]');
    fillEditForm(button);

    if (editError) {
      editError.hidden = true;
      editError.textContent = '';
    }

    editModal.hidden = false;
    document.body.classList.add('employee-modal-is-open');
  };

  const closeEditModal = () => {
    editModal.hidden = true;
    document.body.classList.remove('employee-modal-is-open');
    editedCard = null;
  };

  const updateEmployeeCard = (employee) => {
    if (!editedCard) {
      return;
    }

    const fullName = `${employee.prenom || ''} ${employee.nom || ''}`.trim();
    const editButton = editedCard.querySelector('[data-employee-edit]');

    editedCard.dataset.search = normalize(`${employee.id} ${employee.nom || ''} ${employee.prenom || ''} ${employee.email || ''}`);
    editedCard.dataset.job = employee.poste || '';

    editedCard.querySelector('[data-employee-name]').textContent = fullName;
    editedCard.querySelector('[data-employee-identity]').textContent = `ID ${employee.id} · ${employee.email || ''}`;
    editedCard.querySelector('[data-employee-job-label]').textContent = employee.poste || '';

    if (editButton) {
      editButton.dataset.prenom = employee.prenom || '';
      editButton.dataset.nom = employee.nom || '';
      editButton.dataset.email = employee.email || '';
      editButton.dataset.telephone = employee.telephone || '';
      editButton.dataset.adresse = employee.adresse_postale || '';
      editButton.dataset.ville = employee.ville || '';
      editButton.dataset.codePostal = employee.code_postal || '';
      editButton.dataset.poste = employee.poste || '';
    }
  };

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
    deletedCard = button.closest('[data-employee-card]');
    deleteUrl = button.dataset.deleteUrl || '';
    deleteModal.hidden = false;
    document.body.classList.add('employee-modal-is-open');
  };

  const closeDeleteModal = () => {
    deletedCard = null;
    deleteUrl = '';
    deleteModal.hidden = true;
    document.body.classList.remove('employee-modal-is-open');
  };

  page.querySelectorAll('[data-employee-delete]').forEach((button) => {
    button.addEventListener('click', () => openDeleteModal(button));
  });

  deleteConfirm?.addEventListener('click', async () => {
    if (!deleteUrl || !deletedCard) {
      closeDeleteModal();
      return;
    }

    deleteConfirm.disabled = true;

    try {
      await requestJson(deleteUrl, { method: 'POST' });
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

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      if (editModal && !editModal.hidden) {
        closeEditModal();
      }

      if (deleteModal && !deleteModal.hidden) {
        closeDeleteModal();
      }
    }
  });

  applyButton?.addEventListener('click', applyFilters);
  resetButton?.addEventListener('click', resetFilters);
});
