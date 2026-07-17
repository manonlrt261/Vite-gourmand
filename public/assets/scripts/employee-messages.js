document.addEventListener('DOMContentLoaded', () => {
  const tabs = document.querySelector('[data-message-tabs]');
  const list = document.querySelector('[data-message-list]');
  const feedback = document.querySelector('[data-message-feedback]');
  const deleteModal = document.querySelector('[data-message-delete-modal]');
  const deleteConfirmButton = document.querySelector('[data-message-delete-confirm]');
  const deleteCancelButtons = document.querySelectorAll('[data-message-delete-cancel]');
  let pendingDeleteForm = null;

  if (!tabs || !list) {
    return;
  }

  const showFeedback = (message, isError = false) => {
    if (!feedback) {
      return;
    }

    feedback.hidden = false;
    feedback.textContent = message;
    feedback.className = `employee-message-feedback ${isError ? 'employee-message-feedback--error' : 'employee-message-feedback--success'}`;
  };

  const updateTabs = (newTabs) => {
    if (!newTabs) {
      return;
    }

    tabs.innerHTML = newTabs.innerHTML;
  };

  const updateList = (newList) => {
    if (!newList) {
      return;
    }

    list.innerHTML = newList.innerHTML;
  };

  const loadTab = async (url, pushHistory = true) => {
    const response = await fetch(url, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (!response.ok) {
      throw new Error('Impossible de charger les messages.');
    }

    const html = await response.text();
    const page = new DOMParser().parseFromString(html, 'text/html');
    updateTabs(page.querySelector('[data-message-tabs]'));
    updateList(page.querySelector('[data-message-list]'));

    if (pushHistory) {
      window.history.pushState({}, '', url);
    }
  };

  const currentTabUrl = () => {
    const activeTab = tabs.querySelector('.is-active');
    return activeTab ? activeTab.href : window.location.href;
  };

  const submitAction = async (form) => {
    const response = await fetch(form.getAttribute('action'), {
      method: form.method || 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
      },
      body: new FormData(form),
    });

    const data = await response.json();
    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Action impossible pour le moment.');
    }

    return data;
  };

  const openDeleteModal = (form) => {
    pendingDeleteForm = form;
    if (deleteModal) {
      deleteModal.hidden = false;
    }
  };

  const closeDeleteModal = () => {
    pendingDeleteForm = null;
    if (deleteModal) {
      deleteModal.hidden = true;
    }
  };

  tabs.addEventListener('click', async (event) => {
    const link = event.target.closest('[data-message-tab]');
    if (!link) {
      return;
    }

    event.preventDefault();

    try {
      await loadTab(link.href);
    } catch (error) {
      showFeedback(error.message, true);
    }
  });

  document.addEventListener('submit', async (event) => {
    const form = event.target.matches('[data-message-action]') ? event.target : null;
    if (!form) {
      return;
    }

    event.preventDefault();

    if (form.dataset.messageAction === 'delete') {
      openDeleteModal(form);
      return;
    }

    const button = form.querySelector('button');
    if (button) {
      button.disabled = true;
    }

    try {
      const data = await submitAction(form);
      showFeedback(data.message || 'Action réalisée.');
      await loadTab(currentTabUrl(), false);
    } catch (error) {
      showFeedback(error.message, true);
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

    const form = pendingDeleteForm;
    const button = form.querySelector('button');
    deleteConfirmButton.disabled = true;

    if (button) {
      button.disabled = true;
    }

    try {
      const data = await submitAction(form);
      closeDeleteModal();
      showFeedback(data.message || 'Le message a bien été supprimé.');
      await loadTab(currentTabUrl(), false);
    } catch (error) {
      showFeedback(error.message, true);
    } finally {
      deleteConfirmButton.disabled = false;
      if (button) {
        button.disabled = false;
      }
    }
  });

  deleteCancelButtons.forEach((button) => {
    button.addEventListener('click', closeDeleteModal);
  });

  window.addEventListener('popstate', () => {
    loadTab(window.location.href, false).catch((error) => {
      showFeedback(error.message, true);
    });
  });
});
