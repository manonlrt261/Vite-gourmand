// Active ou désactive les champs horaires de chaque journée selon son interrupteur d'ouverture.
document.addEventListener('DOMContentLoaded', () => {
  const showSavedMessage = () => {
    const intro = document.querySelector('[data-hours-intro]');
    if (!intro) return;

    intro.querySelectorAll('.employee-alert').forEach((message) => message.remove());
    const message = document.createElement('p');
    message.className = 'employee-alert employee-alert-success';
    message.setAttribute('role', 'status');
    message.textContent = 'Vos modifications ont bien été enregistrées.';
    intro.append(message);
  };

  document.querySelectorAll('[data-hours-row]').forEach((row) => {
    const toggle = row.querySelector('[data-hours-toggle]');
    const inputs = row.querySelectorAll('[data-hours-input]');

    if (!toggle) {
      return;
    }

    const syncInputs = () => {
      // Les champs désactivés ne seront pas soumis pour une journée déclarée fermée.
      inputs.forEach((input) => {
        input.disabled = !toggle.checked;
      });

      if (toggle.checked && inputs.length >= 2) {
        if (!inputs[0].value) inputs[0].value = '09:00';
        if (!inputs[1].value) inputs[1].value = '18:00';
      }
    };

    toggle.addEventListener('change', syncInputs);
    syncInputs();
  });

  const closureList = document.querySelector('[data-closure-list]');

  const bindClosureDeletion = (form) => {
    if (form.dataset.deleteBound === 'true') return;
    form.dataset.deleteBound = 'true';

    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      const button = form.querySelector('button[type="submit"]');
      button?.setAttribute('disabled', 'disabled');

      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
          throw new Error(result.message || 'La fermeture n’a pas pu être supprimée.');
        }

        form.closest('[data-closure-card]')?.remove();

        if (!closureList.querySelector('[data-closure-card]')) {
          const emptyMessage = document.createElement('p');
          emptyMessage.className = 'employee-hours-empty';
          emptyMessage.textContent = 'Aucune fermeture exceptionnelle enregistrée.';
          closureList.append(emptyMessage);
        }
        showSavedMessage();
      } catch (error) {
        window.alert(error instanceof Error ? error.message : 'La fermeture n’a pas pu être supprimée.');
        button?.removeAttribute('disabled');
      }
    });
  };

  closureList?.querySelectorAll('[data-closure-delete-form]').forEach(bindClosureDeletion);

  const addForm = document.querySelector('[data-closure-add-form]');
  addForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = addForm.querySelector('button[type="submit"]');
    button?.setAttribute('disabled', 'disabled');

    try {
      const response = await fetch(addForm.getAttribute('action') || window.location.href, {
        method: 'POST',
        body: new FormData(addForm),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const contentType = response.headers.get('content-type') || '';
      if (!contentType.includes('application/json')) {
        throw new Error('Le serveur n’a pas renvoyé une réponse valide.');
      }
      const result = await response.json();
      if (!response.ok || !result.success) {
        throw new Error(result.message || 'La fermeture n’a pas pu être ajoutée.');
      }

      const closure = result.closure;
      const formatDate = (value) => value.split('-').reverse().join('/');
      const title = closure.endDate && closure.endDate !== closure.date
        ? `Du ${formatDate(closure.date)} au ${formatDate(closure.endDate)}`
        : formatDate(closure.date);
      const card = document.createElement('article');
      card.className = 'employee-closure-card';
      card.dataset.closureCard = '';
      card.innerHTML = '<div><h3></h3><p></p></div><form method="post" data-closure-delete-form><input type="hidden" name="_csrf_token"><button type="submit" class="employee-icon-button" aria-label="Supprimer cette fermeture">Suppr.</button></form>';
      card.querySelector('h3').textContent = title;
      card.querySelector('p').textContent = closure.reason;
      const deleteForm = card.querySelector('[data-closure-delete-form]');
      deleteForm.action = closure.deleteUrl;
      deleteForm.querySelector('[name="_csrf_token"]').value = closure.csrfToken;

      closureList?.querySelector('.employee-hours-empty')?.remove();
      closureList?.append(card);
      bindClosureDeletion(deleteForm);
      addForm.reset();
      addForm.querySelector('[name="motif"]').value = 'Fermeture exceptionnelle';
      showSavedMessage();
    } catch (error) {
      window.alert(error instanceof Error ? error.message : 'La fermeture n’a pas pu être ajoutée.');
    } finally {
      button?.removeAttribute('disabled');
    }
  });
});
