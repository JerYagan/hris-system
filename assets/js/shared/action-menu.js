export const initFloatingActionMenus = ({
  scopeSelector,
  toggleSelector,
  menuSelector,
  itemSelector = 'a, button, [role="menuitem"]',
  initFlag = 'floatingActionMenusInitialized',
}) => {
  if (!scopeSelector || !toggleSelector || !menuSelector) {
    return;
  }

  if (document.body?.dataset?.[initFlag] === 'true') {
    return;
  }

  const MENU_OFFSET = 8;
  const VIEWPORT_MARGIN = 12;

  const getScopes = () => Array.from(document.querySelectorAll(scopeSelector));
  const getMenus = () => Array.from(document.querySelectorAll(menuSelector));

  const setExpanded = (scope, expanded) => {
    const toggle = scope?.querySelector(toggleSelector);
    if (toggle instanceof HTMLElement) {
      toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }
  };

  const closeMenu = (menu) => {
    if (!(menu instanceof HTMLElement)) {
      return;
    }

    menu.classList.add('hidden');
    menu.style.position = '';
    menu.style.top = '';
    menu.style.left = '';
    menu.style.right = '';
    menu.style.maxHeight = '';
    menu.dataset.menuPlacement = 'bottom';
    setExpanded(menu.closest(scopeSelector), false);
  };

  const closeAllMenus = () => {
    getMenus().forEach((menu) => closeMenu(menu));
  };

  const positionMenu = (scope, menu) => {
    const toggle = scope?.querySelector(toggleSelector);
    if (!(toggle instanceof HTMLElement) || !(menu instanceof HTMLElement)) {
      return;
    }

    menu.style.position = 'fixed';

    const toggleRect = toggle.getBoundingClientRect();
    const menuRect = menu.getBoundingClientRect();
    const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
    const menuWidth = menuRect.width;
    const preferredMenuHeight = menuRect.height;
    const availableBelow = Math.max(0, viewportHeight - toggleRect.bottom - MENU_OFFSET - VIEWPORT_MARGIN);
    const availableAbove = Math.max(0, toggleRect.top - MENU_OFFSET - VIEWPORT_MARGIN);
    const placeBelow = availableBelow >= preferredMenuHeight || availableBelow >= availableAbove;
    const availableHeight = placeBelow ? availableBelow : availableAbove;
    const targetHeight = Math.min(preferredMenuHeight, Math.max(availableHeight, 0));
    const boundedLeft = Math.max(
      VIEWPORT_MARGIN,
      Math.min(toggleRect.right - menuWidth, viewportWidth - menuWidth - VIEWPORT_MARGIN)
    );
    const boundedTop = placeBelow
      ? Math.max(
          VIEWPORT_MARGIN,
          Math.min(toggleRect.bottom + MENU_OFFSET, viewportHeight - targetHeight - VIEWPORT_MARGIN)
        )
      : Math.max(VIEWPORT_MARGIN, toggleRect.top - MENU_OFFSET - targetHeight);

    menu.dataset.menuPlacement = placeBelow ? 'bottom' : 'top';
    menu.style.left = `${Math.round(boundedLeft)}px`;
    menu.style.top = `${Math.round(boundedTop)}px`;
    menu.style.right = 'auto';
    menu.style.maxHeight = `${Math.max(0, Math.floor(availableHeight))}px`;
  };

  const refreshOpenMenus = () => {
    getMenus().forEach((menu) => {
      if (!(menu instanceof HTMLElement) || menu.classList.contains('hidden')) {
        return;
      }

      positionMenu(menu.closest(scopeSelector), menu);
    });
  };

  getScopes().forEach((scope) => {
    const toggle = scope.querySelector(toggleSelector);
    const menu = scope.querySelector(menuSelector);

    if (!(toggle instanceof HTMLElement) || !(menu instanceof HTMLElement)) {
      return;
    }

    toggle.setAttribute('aria-expanded', 'false');
    toggle.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();

      const willOpen = menu.classList.contains('hidden');
      closeAllMenus();
      if (willOpen) {
        menu.classList.remove('hidden');
        positionMenu(scope, menu);
        setExpanded(scope, true);
      }
    });
  });

  window.addEventListener('resize', refreshOpenMenus);
  window.addEventListener('scroll', refreshOpenMenus, true);

  document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element) || event.target.closest(scopeSelector)) {
      return;
    }

    closeAllMenus();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeAllMenus();
    }
  });

  document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
      return;
    }

    const menu = event.target.closest(menuSelector);
    if (!menu || event.target.closest(toggleSelector)) {
      return;
    }

    if (event.target.closest(itemSelector)) {
      closeAllMenus();
    }
  });

  if (document.body?.dataset) {
    document.body.dataset[initFlag] = 'true';
  }
};

export default initFloatingActionMenus;