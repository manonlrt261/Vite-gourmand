document.addEventListener('DOMContentLoaded', () => {
  const setupOverlayMenu = ({
    overlaySelector,
    openSelector,
    closeSelector,
    bodyClass,
  }) => {
    const overlay = document.querySelector(overlaySelector);
    const openButton = document.querySelector(openSelector);
    const closeButton = document.querySelector(closeSelector);

    if (!overlay || !openButton || !closeButton) {
      return;
    }

    const openMenu = () => {
      overlay.hidden = false;
      openButton.setAttribute('aria-expanded', 'true');
      document.body.classList.add(bodyClass);
      closeButton.focus();
    };

    const closeMenu = () => {
      overlay.hidden = true;
      openButton.setAttribute('aria-expanded', 'false');
      document.body.classList.remove(bodyClass);
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
  };

  // Menu superpose du header employe/administrateur.
  setupOverlayMenu({
    overlaySelector: '[data-employee-menu-overlay]',
    openSelector: '[data-employee-menu-open]',
    closeSelector: '[data-employee-menu-close]',
    bodyClass: 'employee-menu-is-open',
  });

  // Menu superpose du header public/client sur mobile.
  setupOverlayMenu({
    overlaySelector: '[data-public-menu-overlay]',
    openSelector: '[data-public-menu-open]',
    closeSelector: '[data-public-menu-close]',
    bodyClass: 'public-menu-is-open',
  });
});
