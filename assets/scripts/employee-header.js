document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.querySelector('[data-employee-menu-overlay]');
  const openButton = document.querySelector('[data-employee-menu-open]');
  const closeButton = document.querySelector('[data-employee-menu-close]');

  if (!overlay || !openButton || !closeButton) {
    return;
  }

  const openMenu = () => {
    overlay.hidden = false;
    openButton.setAttribute('aria-expanded', 'true');
    document.body.classList.add('employee-menu-is-open');
    closeButton.focus();
  };

  const closeMenu = () => {
    overlay.hidden = true;
    openButton.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('employee-menu-is-open');
    openButton.focus();
  };

  openButton.addEventListener('click', openMenu);
  closeButton.addEventListener('click', closeMenu);

  overlay.addEventListener('click', (event) => {
    if (event.target === overlay) {
      closeMenu();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !overlay.hidden) {
      closeMenu();
    }
  });
});
