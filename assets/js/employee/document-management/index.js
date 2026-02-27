import { runPageStatePass } from '../../shared/ui/page-state.js';

const PER_PAGE = 10;

const toggleModal = (modal, open) => {
  if (!modal) return;
  modal.classList.toggle('hidden', !open);
  modal.setAttribute('aria-hidden', open ? 'false' : 'true');
};

const wireModal = ({ openSelector, closeSelector, modalId, onOpen }) => {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  document.querySelectorAll(openSelector).forEach((button) => {
    button.addEventListener('click', () => {
      if (typeof onOpen === 'function') {
        onOpen(button);
      }
      toggleModal(modal, true);
    });
  });

  document.querySelectorAll(closeSelector).forEach((button) => {
    button.addEventListener('click', () => toggleModal(modal, false));
  });

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      toggleModal(modal, false);
    }
  });
};

const wireDetailsModals = () => {
  document.querySelectorAll('[data-open-details]').forEach((button) => {
    button.addEventListener('click', () => {
      const modalId = button.getAttribute('data-open-details');
      if (!modalId) return;
      const modal = document.getElementById(modalId);
      toggleModal(modal, true);
    });
  });

  document.querySelectorAll('[data-close-details]').forEach((button) => {
    button.addEventListener('click', () => {
      const modal = button.closest('[id^="document-details-"]');
      if (!modal) return;
      toggleModal(modal, false);
    });
  });

  document.querySelectorAll('[id^="document-details-"]').forEach((modal) => {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        toggleModal(modal, false);
      }
    });
  });
};

const wireFilters = () => {
  const searchInput = document.getElementById('documentSearchInput');
  const categoryFilter = document.getElementById('documentCategoryFilter');
  const rows = Array.from(document.querySelectorAll('[data-document-row]'));
  const filterEmpty = document.getElementById('documentFilterEmpty');
  const viewTabButtons = Array.from(document.querySelectorAll('[data-document-view-tab]'));
  const statusTabButtons = Array.from(document.querySelectorAll('[data-document-status-tab]'));

  if (!rows.length || !searchInput || !categoryFilter) {
    return;
  }

  const paginationStateByTable = new Map();
  Array.from(document.querySelectorAll('[data-pagination-controls]')).forEach((container) => {
    const tableId = container.getAttribute('data-pagination-controls');
    if (!tableId) {
      return;
    }

    paginationStateByTable.set(tableId, {
      page: 1,
      prevButton: container.querySelector('[data-pagination-prev]'),
      nextButton: container.querySelector('[data-pagination-next]'),
      pageLabel: container.querySelector('[data-pagination-page]'),
      countLabel: container.querySelector('[data-pagination-label]'),
    });
  });

  const setPage = (tableId, page) => {
    const state = paginationStateByTable.get(tableId);
    if (!state) {
      return;
    }
    state.page = Math.max(1, page);
  };

  const renderPaginatedRows = (tableId, allRows, filteredRows, emptyElement, isFiltered) => {
    const state = paginationStateByTable.get(tableId);
    if (!state) {
      allRows.forEach((row) => row.classList.toggle('hidden', !filteredRows.includes(row)));
      if (emptyElement) {
        emptyElement.classList.toggle('hidden', filteredRows.length > 0 || !isFiltered);
      }
      return;
    }

    const totalRows = filteredRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / PER_PAGE));
    if (state.page > totalPages) {
      state.page = totalPages;
    }

    const start = (state.page - 1) * PER_PAGE;
    const end = start + PER_PAGE;
    const pageRows = filteredRows.slice(start, end);

    allRows.forEach((row) => {
      row.classList.toggle('hidden', !pageRows.includes(row));
    });

    if (emptyElement) {
      emptyElement.classList.toggle('hidden', totalRows > 0 || !isFiltered);
    }

    if (state.pageLabel) {
      state.pageLabel.textContent = `Page ${state.page} of ${totalPages}`;
    }
    if (state.countLabel) {
      const shown = totalRows === 0 ? 0 : Math.min(end, totalRows);
      state.countLabel.textContent = `Showing ${start + (totalRows === 0 ? 0 : 1)}-${shown} of ${totalRows}`;
    }
    if (state.prevButton) {
      state.prevButton.disabled = state.page <= 1;
      state.prevButton.classList.toggle('opacity-60', state.page <= 1);
      state.prevButton.classList.toggle('cursor-not-allowed', state.page <= 1);
    }
    if (state.nextButton) {
      state.nextButton.disabled = state.page >= totalPages;
      state.nextButton.classList.toggle('opacity-60', state.page >= totalPages);
      state.nextButton.classList.toggle('cursor-not-allowed', state.page >= totalPages);
    }
  };

  paginationStateByTable.forEach((state, tableId) => {
    if (state.prevButton) {
      state.prevButton.addEventListener('click', () => {
        if (state.page <= 1) {
          return;
        }
        state.page -= 1;
        render();
      });
    }

    if (state.nextButton) {
      state.nextButton.addEventListener('click', () => {
        state.page += 1;
        render();
      });
    }
  });

  let activeViewTab = 'active';
  let activeStatusTab = 'all';

  const applyToggleButtonState = (buttons, activeValue, attribute, activeClasses, inactiveClasses) => {
    buttons.forEach((button) => {
      const buttonValue = (button.getAttribute(attribute) || '').toLowerCase();
      const isActive = buttonValue === activeValue;
      activeClasses.forEach((className) => button.classList.toggle(className, isActive));
      inactiveClasses.forEach((className) => button.classList.toggle(className, !isActive));
    });
  };

  const render = () => {
    const term = (searchInput.value || '').toLowerCase().trim();
    const category = (categoryFilter.value || '').toLowerCase().trim();

    const filteredRows = rows.filter((row) => {
      const haystack = (row.getAttribute('data-search') || '').toLowerCase();
      const rowCategory = (row.getAttribute('data-category') || '').toLowerCase();
      const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
      const rowView = (row.getAttribute('data-view') || 'active').toLowerCase();

      const passTerm = term === '' || haystack.includes(term);
      const passCategory = category === '' || rowCategory === category;
      const passView = rowView === activeViewTab;
      const passStatus = activeViewTab === 'archived' || activeStatusTab === 'all' || rowStatus === activeStatusTab;
      return passTerm && passCategory && passView && passStatus;
    });

    const isFiltered = term !== '' || category !== '' || activeStatusTab !== 'all' || activeViewTab !== 'active';
    renderPaginatedRows('employeeDocumentTable', rows, filteredRows, filterEmpty, isFiltered);
  };

  [searchInput, categoryFilter].forEach((input) => {
    input.addEventListener('input', render);
    input.addEventListener('change', render);
  });

  viewTabButtons.forEach((button) => {
    button.addEventListener('click', () => {
      activeViewTab = (button.getAttribute('data-document-view-tab') || 'active').toLowerCase();
      setPage('employeeDocumentTable', 1);

      const statusTabsContainer = document.getElementById('documentStatusTabs');
      if (statusTabsContainer) {
        statusTabsContainer.classList.toggle('opacity-60', activeViewTab === 'archived');
        statusTabsContainer.classList.toggle('pointer-events-none', activeViewTab === 'archived');
      }

      applyToggleButtonState(
        viewTabButtons,
        activeViewTab,
        'data-document-view-tab',
        ['bg-slate-700', 'text-white'],
        ['bg-white', 'text-gray-700']
      );

      render();
    });
  });

  statusTabButtons.forEach((button) => {
    button.addEventListener('click', () => {
      activeStatusTab = (button.getAttribute('data-document-status-tab') || 'all').toLowerCase();
      setPage('employeeDocumentTable', 1);

      applyToggleButtonState(
        statusTabButtons,
        activeStatusTab,
        'data-document-status-tab',
        ['bg-slate-700', 'text-white'],
        ['bg-white', 'text-gray-700']
      );

      render();
    });
  });

  document.querySelectorAll('[data-clear-filters]').forEach((button) => {
    button.addEventListener('click', () => {
      searchInput.value = '';
      categoryFilter.value = '';
      activeViewTab = 'active';
      activeStatusTab = 'all';
      setPage('employeeDocumentTable', 1);

      applyToggleButtonState(
        viewTabButtons,
        activeViewTab,
        'data-document-view-tab',
        ['bg-slate-700', 'text-white'],
        ['bg-white', 'text-gray-700']
      );

      applyToggleButtonState(
        statusTabButtons,
        activeStatusTab,
        'data-document-status-tab',
        ['bg-slate-700', 'text-white'],
        ['bg-white', 'text-gray-700']
      );

      const statusTabsContainer = document.getElementById('documentStatusTabs');
      if (statusTabsContainer) {
        statusTabsContainer.classList.remove('opacity-60', 'pointer-events-none');
      }

      render();
    });
  });

  render();
};

const wireVersionModalData = () => {
  const documentIdInput = document.getElementById('versionDocumentId');
  const titleElement = document.getElementById('versionDocumentTitle');

  return (button) => {
    const documentId = button.getAttribute('data-document-id') || '';
    const title = button.getAttribute('data-document-title') || 'Document';

    if (documentIdInput) {
      documentIdInput.value = documentId;
    }

    if (titleElement) {
      titleElement.textContent = `Target document: ${title}`;
    }
  };
};

const wireArchiveRestoreActions = () => {
  const archiveForms = Array.from(document.querySelectorAll('[data-archive-form]'));
  const restoreForms = Array.from(document.querySelectorAll('[data-restore-form]'));

  archiveForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (form.dataset.confirmed === '1') {
        form.dataset.confirmed = '0';
        return;
      }

      event.preventDefault();
      if (!window.Swal || typeof window.Swal.fire !== 'function') {
        return;
      }

      const title = (form.querySelector('[data-archive-title]')?.value || 'Document').trim();

      window.Swal.fire({
        title: 'Archive document?',
        text: `"${title}" will move to Archived Documents. Provide reason to continue.`,
        icon: 'warning',
        input: 'textarea',
        inputPlaceholder: 'Enter archive reason',
        inputAttributes: {
          maxlength: 500,
          'aria-label': 'Archive reason',
        },
        inputValidator: (value) => {
          if (!value || !value.trim()) {
            return 'Archive reason is required.';
          }
          return null;
        },
        showCancelButton: true,
        confirmButtonText: 'Yes, archive',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#b91c1c',
      }).then((result) => {
        if (!result.isConfirmed) {
          return;
        }

        const reasonInput = form.querySelector('[data-archive-reason]');
        if (reasonInput) {
          reasonInput.value = (result.value || '').toString().trim();
        }
        form.dataset.confirmed = '1';
        form.requestSubmit();
      });
    });
  });

  restoreForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (form.dataset.confirmed === '1') {
        form.dataset.confirmed = '0';
        return;
      }

      event.preventDefault();
      if (!window.Swal || typeof window.Swal.fire !== 'function') {
        return;
      }

      const title = (form.querySelector('[data-restore-title]')?.value || 'Document').trim();

      window.Swal.fire({
        title: 'Restore document?',
        text: `"${title}" will be restored to Submitted status. Provide reason to continue.`,
        icon: 'warning',
        input: 'textarea',
        inputPlaceholder: 'Enter restore reason',
        inputAttributes: {
          maxlength: 500,
          'aria-label': 'Restore reason',
        },
        inputValidator: (value) => {
          if (!value || !value.trim()) {
            return 'Restore reason is required.';
          }
          return null;
        },
        showCancelButton: true,
        confirmButtonText: 'Yes, restore',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#1d4ed8',
      }).then((result) => {
        if (!result.isConfirmed) {
          return;
        }

        const reasonInput = form.querySelector('[data-restore-reason]');
        if (reasonInput) {
          reasonInput.value = (result.value || '').toString().trim();
        }
        form.dataset.confirmed = '1';
        form.requestSubmit();
      });
    });
  });
};

const wireActionDropdowns = () => {
  const rows = Array.from(document.querySelectorAll('[data-document-row]'));
  if (!rows.length) {
    return;
  }

  const closeAllMenus = () => {
    document.querySelectorAll('[data-action-menu]').forEach((menu) => {
      menu.classList.add('hidden');
    });
  };

  const runAction = (row, action) => {
    const normalizedAction = (action || '').toLowerCase().trim();
    if (normalizedAction === '') {
      return;
    }

    if (normalizedAction === 'view') {
      row.querySelector('[data-action-view]')?.click();
      return;
    }

    if (normalizedAction === 'download') {
      row.querySelector('[data-action-download]')?.click();
      return;
    }

    if (normalizedAction === 'details') {
      row.querySelector('[data-action-details]')?.click();
      return;
    }

    if (normalizedAction === 'upload_version') {
      row.querySelector('[data-action-upload]')?.click();
      return;
    }

    if (normalizedAction === 'archive') {
      row.querySelector('[data-action-archive]')?.click();
      return;
    }

    if (normalizedAction === 'restore') {
      row.querySelector('[data-action-restore]')?.click();
    }
  };

  rows.forEach((row) => {
    const trigger = row.querySelector('[data-action-trigger]');
    const menu = row.querySelector('[data-action-menu]');
    const items = Array.from(row.querySelectorAll('[data-action-item]'));

    if (!trigger || !menu || !items.length) {
      return;
    }

    trigger.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      const isHidden = menu.classList.contains('hidden');
      closeAllMenus();
      if (isHidden) {
        menu.classList.remove('hidden');
      }
    });

    menu.addEventListener('click', (event) => {
      event.stopPropagation();
    });

    items.forEach((item) => {
      item.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const action = item.getAttribute('data-action-item') || '';
        closeAllMenus();
        runAction(row, action);
      });
    });
  });

  document.addEventListener('click', () => {
    closeAllMenus();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeAllMenus();
    }
  });
};

const initEmployeeDocumentManagementPage = () => {
  runPageStatePass({ pageKey: 'employee-document-management' });

  wireModal({
    openSelector: '[data-open-upload]',
    closeSelector: '[data-close-upload]',
    modalId: 'uploadModal',
  });

  wireModal({
    openSelector: '[data-open-version]',
    closeSelector: '[data-close-version]',
    modalId: 'versionModal',
    onOpen: wireVersionModalData(),
  });

  wireDetailsModals();
  wireFilters();
  wireArchiveRestoreActions();
  wireActionDropdowns();

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    ['uploadModal', 'versionModal'].forEach((modalId) => {
      const modal = document.getElementById(modalId);
      if (modal && !modal.classList.contains('hidden')) {
        toggleModal(modal, false);
      }
    });

    document.querySelectorAll('[id^="document-details-"]').forEach((modal) => {
      if (!modal.classList.contains('hidden')) {
        toggleModal(modal, false);
      }
    });
  });

  const quickAction = new URLSearchParams(window.location.search).get('quick_action');
  if (quickAction === 'upload-document') {
    const uploadModal = document.getElementById('uploadModal');
    toggleModal(uploadModal, true);
  }
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEmployeeDocumentManagementPage);
} else {
  initEmployeeDocumentManagementPage();
}

export default initEmployeeDocumentManagementPage;
