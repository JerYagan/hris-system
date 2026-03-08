import { bindTableFilters, initAdminShellInteractions, initModalSystem, initStatusChangeConfirmations, openModal } from '/hris-system/assets/js/shared/admin-core.js';

const initActionMenus = () => {
  const closeMenus = () => {
    document.querySelectorAll('[data-person-action-menu]').forEach((menu) => {
      menu.classList.add('hidden');
    });
  };

  document.querySelectorAll('[data-person-action-menu-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();

      const scope = toggle.closest('[data-person-action-scope]');
      const menu = scope?.querySelector('[data-person-action-menu]');
      if (!menu) {
        return;
      }

      const willOpen = menu.classList.contains('hidden');
      closeMenus();
      if (willOpen) {
        menu.classList.remove('hidden');
      }
    });
  });

  document.querySelectorAll('[data-action-menu-item]').forEach((menuItem) => {
    menuItem.addEventListener('click', (event) => {
      event.preventDefault();
      const action = menuItem.getAttribute('data-action-target') || '';
      const scope = menuItem.closest('[data-person-action-scope]');
      const trigger = scope?.querySelector(`[data-action-trigger="${action}"]`);
      closeMenus();
      if (trigger instanceof HTMLElement) {
        trigger.click();
      }
    });
  });

  document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-person-action-scope]')) {
      closeMenus();
    }
  });
};

const initDateInputs = () => {
  if (typeof window.flatpickr !== 'function') {
    return;
  }

  document.querySelectorAll('input[type="date"]:not([data-flatpickr="off"])').forEach((input) => {
    if (input.dataset.flatpickrInitialized === 'true') {
      return;
    }

    window.flatpickr(input, {
      dateFormat: 'Y-m-d',
      allowInput: true,
    });
    input.dataset.flatpickrInitialized = 'true';
  });
};

const initAccountPrefill = () => {
  const accountActionSelect = document.getElementById('accountActionSelect');
  const accountEmailInput = document.getElementById('accountEmailInput');
  const accountFullNameInput = document.getElementById('accountFullNameInput');
  const roleUserSelect = document.getElementById('roleUserSelect');
  const roleSelect = document.getElementById('roleSelect');
  const roleOfficeSelect = document.getElementById('roleOfficeSelect');
  const roleAdminGuardHint = document.getElementById('roleAdminGuardHint');
  const credentialUserSelect = document.getElementById('credentialUserSelect');
  const credentialActionSelect = document.getElementById('credentialActionSelect');
  const credentialActionHelp = document.getElementById('credentialActionHelp');

  const updateRoleAdminGuard = () => {
    if (!(roleUserSelect instanceof HTMLSelectElement) || !(roleSelect instanceof HTMLSelectElement)) {
      return;
    }

    const selectedUserOption = roleUserSelect.options[roleUserSelect.selectedIndex] || null;
    const selectedUserIsAdmin = selectedUserOption?.getAttribute('data-is-admin') === '1';
    const activeAdminCount = Number(roleSelect.dataset.activeAdminCount || '0');
    const adminRoleOption = roleSelect.querySelector('option[data-role-key="admin"]');
    const capReached = activeAdminCount >= 2 && !selectedUserIsAdmin;

    if (adminRoleOption instanceof HTMLOptionElement) {
      adminRoleOption.disabled = capReached;
      if (capReached && roleSelect.value === adminRoleOption.value) {
        roleSelect.value = '';
      }
    }

    if (roleAdminGuardHint instanceof HTMLElement) {
      roleAdminGuardHint.textContent = capReached
        ? 'Admin assignment is unavailable because 2 active admin-role users already exist.'
        : 'Assigning Admin is allowed only while there are fewer than 2 active admin-role users.';
      roleAdminGuardHint.classList.toggle('text-amber-600', capReached);
      roleAdminGuardHint.classList.toggle('text-slate-500', !capReached);
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

  document.querySelectorAll('[data-fill-role]').forEach((button) => {
    button.addEventListener('click', () => {
      const userId = button.getAttribute('data-user-id') || '';
      if (!(roleUserSelect instanceof HTMLSelectElement) || userId === '') {
        return;
      }

      roleUserSelect.value = userId;
      roleUserSelect.dispatchEvent(new Event('change'));
      openModal('roleModal');
    });
  });

  if (roleUserSelect instanceof HTMLSelectElement && roleOfficeSelect instanceof HTMLSelectElement) {
    roleUserSelect.addEventListener('change', () => {
      const selectedOption = roleUserSelect.options[roleUserSelect.selectedIndex] || null;
      const officeId = selectedOption ? (selectedOption.getAttribute('data-office-id') || '').trim() : '';
      if (officeId !== '') {
        roleOfficeSelect.value = officeId;
        roleOfficeSelect.dispatchEvent(new Event('change'));
      }

      updateRoleAdminGuard();
    });
  }

  updateRoleAdminGuard();

  document.querySelectorAll('[data-fill-credential]').forEach((button) => {
    button.addEventListener('click', () => {
      const userId = button.getAttribute('data-user-id') || '';
      if (!(credentialUserSelect instanceof HTMLSelectElement) || userId === '') {
        return;
      }

      credentialUserSelect.value = userId;
      credentialUserSelect.dispatchEvent(new Event('change'));
      openModal('credentialModal');
    });
  });

  if (credentialUserSelect instanceof HTMLSelectElement) {
    credentialUserSelect.addEventListener('change', () => {
      updateCredentialAdminGuard();
    });
  }

  updateCredentialAdminGuard();

  document.querySelectorAll('[data-prepare-archive]').forEach((button) => {
    button.addEventListener('click', () => {
      if (!(accountActionSelect instanceof HTMLSelectElement) || !(accountEmailInput instanceof HTMLInputElement)) {
        return;
      }

      accountActionSelect.value = 'archive';
      accountEmailInput.value = button.getAttribute('data-email') || '';
      if (accountFullNameInput instanceof HTMLInputElement) {
        accountFullNameInput.value = button.getAttribute('data-display-name') || '';
      }

      openModal('accountModal');
      accountEmailInput.focus();
    });
  });
};

const initUserManagementFilters = () => {
  bindTableFilters({
    tableId: 'usersTable',
    searchInputId: 'usersSearchInput',
    searchDataAttr: 'data-user-search',
    statusFilterId: 'usersStatusFilter',
    statusDataAttr: 'data-user-status',
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
  initActionMenus();
  initUserManagementFilters();
  initAccountPrefill();
  initDateInputs();
}