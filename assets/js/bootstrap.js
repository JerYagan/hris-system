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

let floatingActionMenuInitializerPromise = null;

const loadFloatingActionMenuInitializer = async () => {
  if (floatingActionMenuInitializerPromise) {
    return floatingActionMenuInitializerPromise;
  }

  floatingActionMenuInitializerPromise = import('/hris-system/assets/js/shared/action-menu.js')
    .then((module) => {
      if (typeof module.initFloatingActionMenus === 'function') {
        return module.initFloatingActionMenus;
      }

      if (typeof module.default === 'function') {
        return module.default;
      }

      return null;
    })
    .catch((error) => {
      console.warn('Shared action menu module could not be loaded.', error);
      return null;
    });

  return floatingActionMenuInitializerPromise;
};

const adminModuleMap = {
  dashboard: () => import('/hris-system/assets/js/admin/dashboard/index.js'),
  recruitment: () => import('/hris-system/assets/js/admin/recruitment/index.js'),
  evaluation: () => import('/hris-system/assets/js/admin/evaluation/index.js'),
  'document-management': () => import('/hris-system/assets/js/admin/document-management/index.js'),
  'payroll-management': () => import('/hris-system/assets/js/admin/payroll-management/index.js'),
  'personal-information': () => import('/hris-system/assets/js/admin/personal-information/index.js'),
  'learning-and-development': () => import('/hris-system/assets/js/admin/learning-and-development/index.js'),
  'report-analytics': () => import('/hris-system/assets/js/admin/report-analytics/index.js'),
  'user-management': () => import('/hris-system/assets/js/admin/user-management/index.js'),
  support: () => import('/hris-system/assets/js/admin/support/index.js'),
};

const initAdminActionMenus = async () => {
  if (document.body?.dataset?.adminActionMenusInitialized === 'true') {
    return;
  }

  if (!document.querySelector('[data-admin-action-scope]')) {
    if (document.body?.dataset) {
      document.body.dataset.adminActionMenusInitialized = 'true';
    }
    return;
  }

  const initFloatingActionMenus = await loadFloatingActionMenuInitializer();
  if (typeof initFloatingActionMenus !== 'function') {
    if (document.body?.dataset) {
      document.body.dataset.adminActionMenusInitialized = 'true';
    }
    return;
  }

  initFloatingActionMenus({
    scopeSelector: '[data-admin-action-scope]',
    toggleSelector: '[data-admin-action-menu-toggle]',
    menuSelector: '[data-admin-action-menu]',
    initFlag: 'adminActionMenusInitialized',
  });
};

const init = async () => {
  const role = document.body?.dataset?.role || '';
  const page = document.body?.dataset?.page || '';

  if (role !== 'admin') {
    return;
  }

  await initAdminActionMenus();

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
