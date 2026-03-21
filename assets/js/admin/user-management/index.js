import {
  bindTableFilters,
  initAdminShellInteractions,
  initModalSystem,
  initStatusChangeConfirmations,
  openModal,
} from '/hris-system/assets/js/shared/admin-core.js';

let flatpickrPromise = null;

const ensureFlatpickr = async () => {
  if (typeof window.flatpickr === 'function') {
    return window.flatpickr;
  }

  if (!flatpickrPromise) {
    flatpickrPromise = new Promise((resolve, reject) => {
      const existing = document.querySelector('script[data-flatpickr="true"]');
      if (existing) {
        existing.addEventListener('load', () => resolve(window.flatpickr), { once: true });
        existing.addEventListener('error', () => reject(new Error('Failed to load Flatpickr.')), { once: true });
        return;
      }

      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
      script.defer = true;
      script.dataset.flatpickr = 'true';
      script.onload = () => resolve(window.flatpickr);
      script.onerror = () => reject(new Error('Failed to load Flatpickr.'));
      document.head.appendChild(script);
    });
  }

  return flatpickrPromise;
};

const fetchHtml = async (url) => {
  const response = await fetch(url, {
    credentials: 'same-origin',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
    },
  });

  if (!response.ok) {
    throw new Error(`Request failed with status ${response.status}`);
  }

  return response.text();
};

const buildPartialUrl = (baseUrl, partial) => {
  const url = new URL(baseUrl, window.location.href);
  url.searchParams.set('partial', partial);
  return url.toString();
};

const initDateInputs = async (root = document) => {
  const dateInputs = Array.from(root.querySelectorAll('input[type="date"]:not([data-flatpickr="off"])'));
  if (!dateInputs.length) {
    return;
  }

  const flatpickr = await ensureFlatpickr();
  dateInputs.forEach((input) => {
    if (input.dataset.flatpickrInitialized === 'true') {
      return;
    }

    flatpickr(input, {
      dateFormat: 'Y-m-d',
      allowInput: true,
    });
    input.dataset.flatpickrInitialized = 'true';
  });
};

const initUserManagementFilters = (root = document) => {
  const table = root.querySelector('#usersTable');
  const searchInput = root.querySelector('#usersSearchInput');
  const statusFilter = root.querySelector('#usersStatusFilter');
  if (!table || !searchInput || !statusFilter || table.dataset.userFiltersInitialized === 'true') {
    return;
  }

  bindTableFilters({
    tableId: 'usersTable',
    searchInputId: 'usersSearchInput',
    searchDataAttr: 'data-user-search',
    statusFilterId: 'usersStatusFilter',
    statusDataAttr: 'data-user-status',
  });
  table.dataset.userFiltersInitialized = 'true';
};

const initAccountPrefill = (root = document) => {
  const quickCreateForm = document.getElementById('quickCreateUserForm');
  const quickCreateNameInput = quickCreateForm?.querySelector('input[name="full_name"]');
  const quickCreateRoleSelect = document.getElementById('quickCreateRoleSelect');
  const quickCreateOfficeSelect = document.getElementById('quickCreateOfficeSelect');
  const quickCreatePositionSelect = document.getElementById('quickCreatePositionSelect');

  const accountForm = root.querySelector('#accountForm');
  const accountEmailInput = root.querySelector('#accountEmailInput');
  const accountFullNameInput = root.querySelector('#accountFullNameInput');
  const deleteUserForm = root.querySelector('#deleteUserForm');
  const deleteUserIdInput = root.querySelector('#deleteUserIdInput');
  const deleteUserEmailInput = root.querySelector('#deleteUserEmailInput');
  const deleteUserNameInput = root.querySelector('#deleteUserNameInput');
  const roleForm = root.querySelector('#roleForm');
  const roleUserSelect = root.querySelector('#roleUserSelect');
  const roleSelect = root.querySelector('#roleSelect');
  const roleOfficeSelect = root.querySelector('#roleOfficeSelect');
  const roleAdminGuardHint = root.querySelector('#roleAdminGuardHint');
  const credentialForm = root.querySelector('#credentialForm');
  const credentialUserSelect = root.querySelector('#credentialUserSelect');
  const credentialActionSelect = root.querySelector('#credentialActionSelect');
  const credentialActionHelp = root.querySelector('#credentialActionHelp');

  const getSelectedLabel = (select) => {
    if (!(select instanceof HTMLSelectElement)) {
      return '';
    }

    const selectedOption = select.options[select.selectedIndex] || null;
    return String(selectedOption?.textContent || '').trim();
  };

  const updateQuickCreateConfirmation = () => {
    if (!(quickCreateForm instanceof HTMLFormElement)) {
      return;
    }

    const fullName = quickCreateNameInput instanceof HTMLInputElement ? quickCreateNameInput.value.trim() : '';
    const selectedOption = quickCreateRoleSelect instanceof HTMLSelectElement
      ? quickCreateRoleSelect.options[quickCreateRoleSelect.selectedIndex] || null
      : null;
    const roleLabel = String(selectedOption?.textContent || '').trim().toLowerCase() || 'selected role';
    const targetLabel = fullName !== '' ? fullName : 'this user';

    quickCreateForm.dataset.confirmTitle = 'Create this user account?';
    quickCreateForm.dataset.confirmText = `This will create an account for ${targetLabel} and provision ${roleLabel} records.`;
    quickCreateForm.dataset.confirmButtonText = 'Create user';
    quickCreateForm.dataset.confirmButtonColor = '#0f172a';
  };

  const updateQuickCreateEmploymentRequirements = () => {
    if (!(quickCreateRoleSelect instanceof HTMLSelectElement)) {
      return;
    }

    const selectedOption = quickCreateRoleSelect.options[quickCreateRoleSelect.selectedIndex] || null;
    const selectedRoleKey = String(selectedOption?.getAttribute('data-role-key') || '').trim().toLowerCase();
    const requiresEmployment = ['admin', 'staff', 'employee'].includes(selectedRoleKey);

    if (quickCreateOfficeSelect instanceof HTMLSelectElement) {
      quickCreateOfficeSelect.required = requiresEmployment;
    }

    if (quickCreatePositionSelect instanceof HTMLSelectElement) {
      quickCreatePositionSelect.required = requiresEmployment;
    }
  };

  const updateArchiveConfirmation = () => {
    if (!(accountForm instanceof HTMLFormElement)) {
      return;
    }

    const fullName = accountFullNameInput instanceof HTMLInputElement ? accountFullNameInput.value.trim() : '';
    const email = accountEmailInput instanceof HTMLInputElement ? accountEmailInput.value.trim() : '';
    const targetLabel = fullName !== '' ? fullName : (email !== '' ? email : 'this user');

    accountForm.dataset.confirmTitle = 'Archive this user account?';
    accountForm.dataset.confirmText = `${targetLabel} will be archived and removed from active sign-in access.`;
    accountForm.dataset.confirmButtonText = 'Archive account';
    accountForm.dataset.confirmButtonColor = '#dc2626';
  };

  const updateDeleteConfirmation = () => {
    if (!(deleteUserForm instanceof HTMLFormElement)) {
      return;
    }

    const targetLabel = deleteUserNameInput instanceof HTMLInputElement && deleteUserNameInput.value.trim() !== ''
      ? deleteUserNameInput.value.trim()
      : (deleteUserEmailInput instanceof HTMLInputElement ? deleteUserEmailInput.value.trim() : 'this user');

    deleteUserForm.dataset.confirmTitle = 'Delete this user?';
    deleteUserForm.dataset.confirmText = `${targetLabel} and any removable linked records will be deleted permanently.`;
    deleteUserForm.dataset.confirmButtonText = 'Delete user';
    deleteUserForm.dataset.confirmButtonColor = '#dc2626';
  };

  const updateRoleConfirmation = () => {
    if (!(roleForm instanceof HTMLFormElement)) {
      return;
    }

    const userLabel = getSelectedLabel(roleUserSelect) || 'the selected user';
    const roleLabel = getSelectedLabel(roleSelect) || 'the selected role';
    roleForm.dataset.confirmTitle = 'Assign this role?';
    roleForm.dataset.confirmText = `${roleLabel} will be assigned to ${userLabel}.`;
    roleForm.dataset.confirmButtonText = 'Assign role';
    roleForm.dataset.confirmButtonColor = '#0f172a';
  };

  const updateCredentialConfirmation = () => {
    if (!(credentialForm instanceof HTMLFormElement) || !(credentialActionSelect instanceof HTMLSelectElement)) {
      return;
    }

    const userLabel = getSelectedLabel(credentialUserSelect) || 'the selected user';
    const action = String(credentialActionSelect.value || '').trim().toLowerCase();

    if (action === 'unlock_account') {
      credentialForm.dataset.confirmTitle = 'Unlock this account?';
      credentialForm.dataset.confirmText = `${userLabel} will regain access if the account was locked.`;
      credentialForm.dataset.confirmButtonText = 'Unlock account';
      credentialForm.dataset.confirmButtonColor = '#0f172a';
      return;
    }

    if (action === 'disable_login') {
      credentialForm.dataset.confirmTitle = 'Disable login access?';
      credentialForm.dataset.confirmText = `${userLabel} will no longer be able to sign in until access is restored.`;
      credentialForm.dataset.confirmButtonText = 'Disable login';
      credentialForm.dataset.confirmButtonColor = '#dc2626';
      return;
    }

    credentialForm.dataset.confirmTitle = 'Reset this user\'s password?';
    credentialForm.dataset.confirmText = `${userLabel} will receive a temporary password with change-password instructions.`;
    credentialForm.dataset.confirmButtonText = 'Reset password';
    credentialForm.dataset.confirmButtonColor = '#0f172a';
  };

  const updateRoleAdminGuard = () => {
    if (!(roleUserSelect instanceof HTMLSelectElement) || !(roleSelect instanceof HTMLSelectElement)) {
      return;
    }

    const selectedUserOption = roleUserSelect.options[roleUserSelect.selectedIndex] || null;
    const selectedUserIsAdmin = selectedUserOption?.getAttribute('data-is-admin') === '1';
    const activeAdminCount = Number(roleSelect.dataset.activeAdminCount || '0');
    const activeAdminMax = Number(roleSelect.dataset.activeAdminMax || '3');
    const adminRoleOption = roleSelect.querySelector('option[data-role-key="admin"]');
    const capReached = activeAdminCount >= activeAdminMax && !selectedUserIsAdmin;

    if (adminRoleOption instanceof HTMLOptionElement) {
      adminRoleOption.disabled = capReached;
      if (capReached && roleSelect.value === adminRoleOption.value) {
        roleSelect.value = '';
      }
    }

    if (roleAdminGuardHint instanceof HTMLElement) {
      roleAdminGuardHint.textContent = capReached
        ? `Admin assignment is unavailable because ${activeAdminMax} active admin-role users already exist.`
        : `Assigning Admin is allowed only while there are fewer than ${activeAdminMax} active admin-role users.`;
      roleAdminGuardHint.classList.toggle('text-amber-600', capReached);
      roleAdminGuardHint.classList.toggle('text-slate-500', !capReached);
    }
  };

  const updateQuickCreateAdminGuard = () => {
    if (!(quickCreateRoleSelect instanceof HTMLSelectElement) || !(roleSelect instanceof HTMLSelectElement)) {
      return;
    }

    const activeAdminCount = Number(roleSelect.dataset.activeAdminCount || '0');
    const activeAdminMax = Number(roleSelect.dataset.activeAdminMax || '3');
    const adminRoleOption = quickCreateRoleSelect.querySelector('option[data-role-key="admin"]');
    const capReached = activeAdminCount >= activeAdminMax;

    if (adminRoleOption instanceof HTMLOptionElement) {
      adminRoleOption.disabled = capReached;
      if (capReached && quickCreateRoleSelect.value === adminRoleOption.value) {
        quickCreateRoleSelect.value = '';
      }
    }
  };

  const updateCredentialAdminGuard = () => {
    if (!(credentialUserSelect instanceof HTMLSelectElement) || !(credentialActionSelect instanceof HTMLSelectElement)) {
      return;
    }

    const selectedUserOption = credentialUserSelect.options[credentialUserSelect.selectedIndex] || null;
    const selectedUserIsAdmin = selectedUserOption?.getAttribute('data-is-admin') === '1';
    const disableLoginOption = credentialActionSelect.querySelector('option[value="disable_login"]');

    if (disableLoginOption instanceof HTMLOptionElement) {
      disableLoginOption.disabled = selectedUserIsAdmin;
      if (selectedUserIsAdmin && credentialActionSelect.value === 'disable_login') {
        credentialActionSelect.value = 'reset_password';
      }
    }

    if (credentialActionHelp instanceof HTMLElement) {
      credentialActionHelp.textContent = selectedUserIsAdmin
        ? 'Admin accounts cannot be disabled from User Management. Reset and unlock actions remain available where policy allows.'
        : 'Reset Password is limited to Employee and Staff accounts. The temporary password is emailed with change-password instructions.';
      credentialActionHelp.classList.toggle('text-amber-600', selectedUserIsAdmin);
      credentialActionHelp.classList.toggle('text-slate-500', !selectedUserIsAdmin);
    }
  };

  if (roleUserSelect instanceof HTMLSelectElement && roleOfficeSelect instanceof HTMLSelectElement && roleUserSelect.dataset.prefillBound !== 'true') {
    roleUserSelect.dataset.prefillBound = 'true';
    roleUserSelect.addEventListener('change', () => {
      const selectedOption = roleUserSelect.options[roleUserSelect.selectedIndex] || null;
      const officeId = selectedOption ? (selectedOption.getAttribute('data-office-id') || '').trim() : '';
      if (officeId !== '') {
        roleOfficeSelect.value = officeId;
        roleOfficeSelect.dispatchEvent(new Event('change'));
      }

      updateRoleAdminGuard();
      updateRoleConfirmation();
    });
  }

  if (roleSelect instanceof HTMLSelectElement && roleSelect.dataset.prefillBound !== 'true') {
    roleSelect.dataset.prefillBound = 'true';
    roleSelect.addEventListener('change', updateRoleConfirmation);
  }

  if (credentialUserSelect instanceof HTMLSelectElement && credentialUserSelect.dataset.prefillBound !== 'true') {
    credentialUserSelect.dataset.prefillBound = 'true';
    credentialUserSelect.addEventListener('change', () => {
      updateCredentialAdminGuard();
      updateCredentialConfirmation();
    });
  }

  if (credentialActionSelect instanceof HTMLSelectElement && credentialActionSelect.dataset.prefillBound !== 'true') {
    credentialActionSelect.dataset.prefillBound = 'true';
    credentialActionSelect.addEventListener('change', updateCredentialConfirmation);
  }

  if (quickCreateNameInput instanceof HTMLInputElement && quickCreateNameInput.dataset.prefillBound !== 'true') {
    quickCreateNameInput.dataset.prefillBound = 'true';
    quickCreateNameInput.addEventListener('input', updateQuickCreateConfirmation);
  }

  if (quickCreateRoleSelect instanceof HTMLSelectElement && quickCreateRoleSelect.dataset.prefillBound !== 'true') {
    quickCreateRoleSelect.dataset.prefillBound = 'true';
    quickCreateRoleSelect.addEventListener('change', () => {
      updateQuickCreateAdminGuard();
      updateQuickCreateEmploymentRequirements();
      updateQuickCreateConfirmation();
    });
  }

  if (accountEmailInput instanceof HTMLInputElement && accountEmailInput.dataset.prefillBound !== 'true') {
    accountEmailInput.dataset.prefillBound = 'true';
    accountEmailInput.addEventListener('input', updateArchiveConfirmation);
  }

  if (accountFullNameInput instanceof HTMLInputElement && accountFullNameInput.dataset.prefillBound !== 'true') {
    accountFullNameInput.dataset.prefillBound = 'true';
    accountFullNameInput.addEventListener('input', updateArchiveConfirmation);
  }

  updateQuickCreateAdminGuard();
  updateQuickCreateEmploymentRequirements();
  updateQuickCreateConfirmation();
  updateArchiveConfirmation();
  updateDeleteConfirmation();
  updateRoleAdminGuard();
  updateRoleConfirmation();
  updateCredentialAdminGuard();
  updateCredentialConfirmation();

  return {
    prepareRole(userId) {
      if (!(roleUserSelect instanceof HTMLSelectElement) || !userId) {
        return;
      }
      roleUserSelect.value = userId;
      roleUserSelect.dispatchEvent(new Event('change'));
    },
    prepareCredential(userId) {
      if (!(credentialUserSelect instanceof HTMLSelectElement) || !userId) {
        return;
      }
      credentialUserSelect.value = userId;
      credentialUserSelect.dispatchEvent(new Event('change'));
    },
    prepareArchive(name, email) {
      if (accountFullNameInput instanceof HTMLInputElement) {
        accountFullNameInput.value = name || '';
      }
      if (accountEmailInput instanceof HTMLInputElement) {
        accountEmailInput.value = email || '';
      }
      updateArchiveConfirmation();
    },
    prepareDelete(userId, name, email) {
      if (deleteUserIdInput instanceof HTMLInputElement) {
        deleteUserIdInput.value = userId || '';
      }
      if (deleteUserNameInput instanceof HTMLInputElement) {
        deleteUserNameInput.value = name || '';
      }
      if (deleteUserEmailInput instanceof HTMLInputElement) {
        deleteUserEmailInput.value = email || '';
      }
      updateDeleteConfirmation();
    },
  };
};

let modalControllers = null;

const ensureModalHub = async () => {
  const host = document.getElementById('adminUserManagementModalHost');
  if (!host) {
    return null;
  }

  if (host.dataset.loaded === 'true') {
    return host;
  }

  const baseUrl = host.getAttribute('data-base-url') || 'user-management.php';
  const html = await fetchHtml(buildPartialUrl(baseUrl, 'modals'));
  host.innerHTML = html;
  host.dataset.loaded = 'true';
  initModalSystem();
  initStatusChangeConfirmations();
  await initDateInputs(host);
  modalControllers = initAccountPrefill(host);
  return host;
};

const initAsyncWorkspace = () => {
  const region = document.querySelector('[data-admin-user-async-region]');
  if (!region) {
    return;
  }

  const baseUrl = region.getAttribute('data-base-url') || 'user-management.php';
  const loadingState = region.querySelector('[data-user-async-loading]');
  const errorState = region.querySelector('[data-user-async-error]');
  const emptyState = region.querySelector('[data-user-async-empty]');
  const contentState = region.querySelector('[data-user-async-content]');
  const trigger = region.querySelector('[data-user-async-trigger="reference"]');
  let cachedReferenceHtml = '';

  const showLoading = () => {
    loadingState?.classList.remove('hidden');
    errorState?.classList.add('hidden');
    emptyState?.classList.add('hidden');
    contentState?.classList.add('hidden');
  };

  const showContent = (html) => {
    if (!contentState) {
      return;
    }
    contentState.innerHTML = html;
    loadingState?.classList.add('hidden');
    errorState?.classList.add('hidden');
    emptyState?.classList.add('hidden');
    contentState.classList.remove('hidden');
  };

  const showError = () => {
    loadingState?.classList.add('hidden');
    contentState?.classList.add('hidden');
    emptyState?.classList.add('hidden');
    errorState?.classList.remove('hidden');
  };

  const loadReference = async (forceRefresh = false) => {
    if (!forceRefresh && cachedReferenceHtml !== '') {
      showContent(cachedReferenceHtml);
      return;
    }

    showLoading();
    try {
      const html = await fetchHtml(buildPartialUrl(baseUrl, 'reference'));
      cachedReferenceHtml = html;
      showContent(html);
    } catch (_error) {
      showError();
    }
  };

  trigger?.addEventListener('click', () => {
    loadReference(false);
  });

  region.querySelector('[data-user-async-retry]')?.addEventListener('click', () => {
    loadReference(true);
  });
};

const initActionDelegation = () => {
  if (document.body.dataset.adminUserManagementDelegated === 'true') {
    return;
  }

  document.body.dataset.adminUserManagementDelegated = 'true';

  document.addEventListener('click', async (event) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target) {
      return;
    }

    const menuItem = target.closest('[data-action-menu-item]');
    if (menuItem) {
      event.preventDefault();
      const action = menuItem.getAttribute('data-action-target') || '';
      const scope = menuItem.closest('[data-admin-action-scope]');
      const trigger = scope?.querySelector(`[data-action-trigger="${action}"]`);
      if (trigger instanceof HTMLElement) {
        trigger.click();
      }
      return;
    }

    const modalTrigger = target.closest('[data-open-user-management-modal]');
    if (!modalTrigger) {
      return;
    }

    event.preventDefault();
    const modalKey = modalTrigger.getAttribute('data-open-user-management-modal') || '';
    if (!modalKey) {
      return;
    }

    await ensureModalHub();

    if (!modalControllers) {
      modalControllers = initAccountPrefill(document.getElementById('adminUserManagementModalHost') || document);
    }

    if (modalKey === 'role') {
      modalControllers?.prepareRole?.(modalTrigger.getAttribute('data-user-id') || '');
      openModal('roleModal');
      return;
    }

    if (modalKey === 'credential') {
      modalControllers?.prepareCredential?.(modalTrigger.getAttribute('data-user-id') || '');
      openModal('credentialModal');
      return;
    }

    if (modalKey === 'archive') {
      modalControllers?.prepareArchive?.(
        modalTrigger.getAttribute('data-display-name') || '',
        modalTrigger.getAttribute('data-email') || '',
      );
      openModal('accountModal');
      return;
    }

    if (modalKey === 'delete') {
      modalControllers?.prepareDelete?.(
        modalTrigger.getAttribute('data-user-id') || '',
        modalTrigger.getAttribute('data-display-name') || '',
        modalTrigger.getAttribute('data-email') || '',
      );
      openModal('deleteUserModal');
      return;
    }

    if (modalKey === 'position') {
      openModal('positionModal');
      return;
    }

    if (modalKey === 'department') {
      openModal('departmentModal');
      return;
    }
  });
};

export default function initAdminUserManagementPage() {
  if (document.body?.dataset?.adminUserManagementInitialized === 'true') {
    return;
  }

  if (document.body) {
    document.body.dataset.adminUserManagementInitialized = 'true';
  }

  initAdminShellInteractions();
  initModalSystem();
  initStatusChangeConfirmations();
  initUserManagementFilters(document);
  initAccountPrefill(document);
  initDateInputs(document).catch(console.error);
  initAsyncWorkspace();
  initActionDelegation();
}