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
  const quickCreateForm = document.getElementById('quickCreateUserForm');
  const quickCreateNameInput = quickCreateForm?.querySelector('input[name="full_name"]');
  const quickCreateRoleSelect = document.getElementById('quickCreateRoleSelect');
  const quickCreateOfficeSelect = document.getElementById('quickCreateOfficeSelect');
  const quickCreatePositionSelect = document.getElementById('quickCreatePositionSelect');
  const accountForm = document.getElementById('accountForm');
  const accountEmailInput = document.getElementById('accountEmailInput');
  const accountFullNameInput = document.getElementById('accountFullNameInput');
  const deleteUserForm = document.getElementById('deleteUserForm');
  const deleteUserIdInput = document.getElementById('deleteUserIdInput');
  const deleteUserEmailInput = document.getElementById('deleteUserEmailInput');
  const deleteUserNameInput = document.getElementById('deleteUserNameInput');
  const roleForm = document.getElementById('roleForm');
  const roleUserSelect = document.getElementById('roleUserSelect');
  const roleSelect = document.getElementById('roleSelect');
  const roleOfficeSelect = document.getElementById('roleOfficeSelect');
  const roleAdminGuardHint = document.getElementById('roleAdminGuardHint');
  const credentialForm = document.getElementById('credentialForm');
  const credentialUserSelect = document.getElementById('credentialUserSelect');
  const credentialActionSelect = document.getElementById('credentialActionSelect');
  const credentialActionHelp = document.getElementById('credentialActionHelp');

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
      updateRoleConfirmation();
    });
  }

  if (roleSelect instanceof HTMLSelectElement) {
    roleSelect.addEventListener('change', updateRoleConfirmation);
  }

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
      updateCredentialConfirmation();
    });
  }

  if (credentialActionSelect instanceof HTMLSelectElement) {
    credentialActionSelect.addEventListener('change', updateCredentialConfirmation);
  }

  document.querySelectorAll('[data-prepare-archive]').forEach((button) => {
    button.addEventListener('click', () => {
      if (!(accountEmailInput instanceof HTMLInputElement)) {
        return;
      }

      accountEmailInput.value = button.getAttribute('data-email') || '';
      if (accountFullNameInput instanceof HTMLInputElement) {
        accountFullNameInput.value = button.getAttribute('data-display-name') || '';
      }

      openModal('accountModal');
      accountEmailInput.focus();
      updateArchiveConfirmation();
    });
  });

  document.querySelectorAll('[data-prepare-delete]').forEach((button) => {
    button.addEventListener('click', () => {
      if (deleteUserIdInput instanceof HTMLInputElement) {
        deleteUserIdInput.value = button.getAttribute('data-user-id') || '';
      }
      if (deleteUserEmailInput instanceof HTMLInputElement) {
        deleteUserEmailInput.value = button.getAttribute('data-email') || '';
      }
      if (deleteUserNameInput instanceof HTMLInputElement) {
        deleteUserNameInput.value = button.getAttribute('data-display-name') || '';
      }

      openModal('deleteUserModal');
      updateDeleteConfirmation();
    });
  });

  if (quickCreateNameInput instanceof HTMLInputElement) {
    quickCreateNameInput.addEventListener('input', updateQuickCreateConfirmation);
  }

  if (quickCreateRoleSelect instanceof HTMLSelectElement) {
    quickCreateRoleSelect.addEventListener('change', () => {
      updateQuickCreateAdminGuard();
      updateQuickCreateEmploymentRequirements();
      updateQuickCreateConfirmation();
    });
  }

  if (accountEmailInput instanceof HTMLInputElement) {
    accountEmailInput.addEventListener('input', updateArchiveConfirmation);
  }

  if (accountFullNameInput instanceof HTMLInputElement) {
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