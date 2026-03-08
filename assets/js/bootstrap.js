const loadLegacyAdminScript = () => new Promise((resolve, reject) => {
  const existing = document.querySelector('script[data-admin-legacy="true"]');
  if (existing) {
    resolve();
    return;
  }

  const script = document.createElement('script');
  script.src = '/hris-system/pages/admin/js/script.js';
  script.defer = true;
  script.dataset.adminLegacy = 'true';
  script.onload = () => resolve();
  script.onerror = () => reject(new Error('Failed to load legacy admin script.'));
  document.body.appendChild(script);
});

const adminModuleMap = {
  dashboard: () => import('/hris-system/assets/js/admin/dashboard/index.js'),
  recruitment: () => import('/hris-system/assets/js/admin/recruitment/index.js'),
  evaluation: () => import('/hris-system/assets/js/admin/evaluation/index.js'),
  'document-management': () => import('/hris-system/assets/js/admin/document-management/index.js'),
  'payroll-management': () => import('/hris-system/assets/js/admin/payroll-management/index.js'),
  'learning-and-development': () => import('/hris-system/assets/js/admin/learning-and-development/index.js'),
  'report-analytics': () => import('/hris-system/assets/js/admin/report-analytics/index.js'),
  'user-management': () => import('/hris-system/assets/js/admin/user-management/index.js'),
  support: () => import('/hris-system/assets/js/admin/support/index.js'),
};

const initAdminActionMenus = () => {
  if (document.body?.dataset?.adminActionMenusInitialized === 'true') {
    return;
  }

  const MENU_OFFSET = 8;
  const VIEWPORT_MARGIN = 12;

  const getMenus = () => Array.from(document.querySelectorAll('[data-admin-action-menu]'));

  const setExpanded = (scope, expanded) => {
    const toggle = scope?.querySelector('[data-admin-action-menu-toggle]');
    if (toggle instanceof HTMLElement) {
      toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }
  };

  const closeAllMenus = () => {
    getMenus().forEach((menu) => {
      menu.classList.add('hidden');
      menu.style.top = '';
      menu.style.left = '';
      menu.style.maxHeight = '';
      menu.dataset.menuPlacement = 'bottom';
      setExpanded(menu.closest('[data-admin-action-scope]'), false);
    });
  };

  const positionMenu = (scope, menu) => {
    const toggle = scope?.querySelector('[data-admin-action-menu-toggle]');
    if (!(toggle instanceof HTMLElement) || !(menu instanceof HTMLElement)) {
      return;
    }

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
    const boundedLeft = Math.max(
      VIEWPORT_MARGIN,
      Math.min(toggleRect.right - menuWidth, viewportWidth - menuWidth - VIEWPORT_MARGIN)
    );
    const boundedTop = placeBelow
      ? Math.max(
          VIEWPORT_MARGIN,
          Math.min(toggleRect.bottom + MENU_OFFSET, viewportHeight - Math.min(preferredMenuHeight, availableHeight) - VIEWPORT_MARGIN)
        )
      : Math.max(
          VIEWPORT_MARGIN,
          toggleRect.top - MENU_OFFSET - Math.min(preferredMenuHeight, availableHeight)
        );

    menu.dataset.menuPlacement = placeBelow ? 'bottom' : 'top';
    menu.style.left = `${Math.round(boundedLeft)}px`;
    menu.style.top = `${Math.round(boundedTop)}px`;
    menu.style.maxHeight = `${Math.max(0, Math.floor(availableHeight))}px`;
  };

  const refreshOpenMenus = () => {
    getMenus().forEach((menu) => {
      if (menu.classList.contains('hidden')) {
        return;
      }

      positionMenu(menu.closest('[data-admin-action-scope]'), menu);
    });
  };

  document.querySelectorAll('[data-admin-action-menu-toggle]').forEach((toggle) => {
    if (!(toggle instanceof HTMLElement)) {
      return;
    }

    toggle.setAttribute('aria-expanded', 'false');
    toggle.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();

      const scope = toggle.closest('[data-admin-action-scope]');
      const menu = scope?.querySelector('[data-admin-action-menu]');
      if (!(menu instanceof HTMLElement)) {
        return;
      }

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
    if (!(event.target instanceof Element) || event.target.closest('[data-admin-action-scope]')) {
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

    const menu = event.target.closest('[data-admin-action-menu]');
    if (!menu || event.target.closest('[data-admin-action-menu-toggle]')) {
      return;
    }

    const actionable = event.target.closest('a, button, [role="menuitem"]');
    if (actionable) {
      closeAllMenus();
    }
  });

  document.body.dataset.adminActionMenusInitialized = 'true';
};

const init = async () => {
  const role = document.body?.dataset?.role || '';
  const page = document.body?.dataset?.page || '';

  if (role !== 'admin') {
    return;
  }

  initAdminActionMenus();

  const loader = adminModuleMap[page];
  if (!loader) {
    await loadLegacyAdminScript();
    return;
  }

  try {
    const module = await loader();
    if (typeof module.default === 'function') {
      module.default();
      return;
    }
    await loadLegacyAdminScript();
  } catch (error) {
    console.error(error);
    await loadLegacyAdminScript();
  }
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    init().catch(console.error);
  });
} else {
  init().catch(console.error);
}
