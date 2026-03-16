import { runPageStatePass } from '../../shared/ui/page-state.js';
import { initFloatingActionMenus } from '../../shared/action-menu.js';

const PER_PAGE = 10;

const toggleModal = (modal, open) => {
  if (!modal) return;
  modal.classList.toggle('hidden', !open);
  modal.setAttribute('aria-hidden', open ? 'false' : 'true');
};

const formatFileSize = (bytes) => {
  const value = Number(bytes || 0);
  if (!Number.isFinite(value) || value <= 0) {
    return '0 B';
  }

  const units = ['B', 'KB', 'MB', 'GB'];
  const exponent = Math.min(Math.floor(Math.log(value) / Math.log(1024)), units.length - 1);
  const sized = value / 1024 ** exponent;
  return `${sized.toFixed(exponent === 0 ? 0 : 2)} ${units[exponent]}`;
};

const parseDate = (value) => {
  const timestamp = Date.parse(String(value || '').trim());
  return Number.isNaN(timestamp) ? null : timestamp;
};

const buildDocumentTitleFromFile = (fileName) => {
  const raw = (fileName || '').replace(/\.[^/.]+$/, '');
  return raw.replace(/[_-]+/g, ' ').replace(/\s+/g, ' ').trim();
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
  const archivedFromInput = document.getElementById('documentArchivedFromFilter');
  const archivedToInput = document.getElementById('documentArchivedToFilter');
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
    const archivedFrom = parseDate(archivedFromInput?.value || '');
    const archivedToRaw = parseDate(archivedToInput?.value || '');
    const archivedTo = archivedToRaw === null ? null : (archivedToRaw + (24 * 60 * 60 * 1000) - 1);

    const filteredRows = rows.filter((row) => {
      const haystack = (row.getAttribute('data-search') || '').toLowerCase();
      const rowCategory = (row.getAttribute('data-category') || '').toLowerCase();
      const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
      const rowView = (row.getAttribute('data-view') || 'active').toLowerCase();
      const rowArchivedDate = parseDate(row.getAttribute('data-archived-date') || '');

      const passTerm = term === '' || haystack.includes(term);
      const passCategory = category === '' || rowCategory === category;
      const passView = rowView === activeViewTab;
      const passStatus = activeViewTab === 'archived' || activeStatusTab === 'all' || rowStatus === activeStatusTab;
      const passArchivedFrom = activeViewTab !== 'archived' || archivedFrom === null || (rowArchivedDate !== null && rowArchivedDate >= archivedFrom);
      const passArchivedTo = activeViewTab !== 'archived' || archivedTo === null || (rowArchivedDate !== null && rowArchivedDate <= archivedTo);

      return passTerm && passCategory && passView && passStatus && passArchivedFrom && passArchivedTo;
    });

    const isFiltered = term !== ''
      || category !== ''
      || activeStatusTab !== 'all'
      || activeViewTab !== 'active'
      || (archivedFromInput?.value || '') !== ''
      || (archivedToInput?.value || '') !== '';
    renderPaginatedRows('employeeDocumentTable', rows, filteredRows, filterEmpty, isFiltered);
  };

  [searchInput, categoryFilter, archivedFromInput, archivedToInput].forEach((input) => {
    if (!input) {
      return;
    }
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
        ['employee-doc-tab-active'],
        ['employee-doc-tab-muted']
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
        ['employee-doc-status-pill-active'],
        ['employee-doc-status-pill-muted']
      );

      render();
    });
  });

  document.querySelectorAll('[data-clear-filters]').forEach((button) => {
    button.addEventListener('click', () => {
      searchInput.value = '';
      categoryFilter.value = '';
      if (archivedFromInput) {
        archivedFromInput.value = '';
      }
      if (archivedToInput) {
        archivedToInput.value = '';
      }
      activeViewTab = 'active';
      activeStatusTab = 'all';
      setPage('employeeDocumentTable', 1);

      applyToggleButtonState(
        viewTabButtons,
        activeViewTab,
        'data-document-view-tab',
        ['employee-doc-tab-active'],
        ['employee-doc-tab-muted']
      );

      applyToggleButtonState(
        statusTabButtons,
        activeStatusTab,
        'data-document-status-tab',
        ['employee-doc-status-pill-active'],
        ['employee-doc-status-pill-muted']
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

const wireHrRequestForm = () => {
  const requestTypeSelect = document.getElementById('hrRequestType');
  const requestTypeOtherWrap = document.getElementById('hrRequestTypeOtherWrap');
  const requestTypeOtherInput = document.getElementById('hrRequestTypeOther');
  const purposeSelect = document.getElementById('hrRequestPurpose');
  const purposeOtherWrap = document.getElementById('hrRequestPurposeOtherWrap');
  const purposeOtherInput = document.getElementById('hrRequestPurposeOther');

  const syncConditionalFields = () => {
    const requiresOtherRequestType = (requestTypeSelect?.value || '') === 'other_hr_document';
    const requiresOtherPurpose = (purposeSelect?.value || '') === 'other';

    if (requestTypeOtherWrap) {
      requestTypeOtherWrap.classList.toggle('hidden', !requiresOtherRequestType);
    }
    if (requestTypeOtherInput) {
      requestTypeOtherInput.required = requiresOtherRequestType;
      if (!requiresOtherRequestType) {
        requestTypeOtherInput.value = '';
      }
    }

    if (purposeOtherWrap) {
      purposeOtherWrap.classList.toggle('hidden', !requiresOtherPurpose);
    }
    if (purposeOtherInput) {
      purposeOtherInput.required = requiresOtherPurpose;
      if (!requiresOtherPurpose) {
        purposeOtherInput.value = '';
      }
    }
  };

  requestTypeSelect?.addEventListener('change', syncConditionalFields);
  purposeSelect?.addEventListener('change', syncConditionalFields);
  syncConditionalFields();
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

const wireUploadModal = () => {
  const form = document.querySelector('[data-upload-form]');
  const titleInput = document.getElementById('uploadDocumentTitle');
  const categorySelect = document.getElementById('uploadDocumentCategory');
  const fileInput = document.getElementById('uploadDocumentFile');
  const dropzone = document.getElementById('uploadDocumentDropzone');
  const preview = document.getElementById('uploadDocumentPreview');
  const previewName = document.getElementById('uploadDocumentPreviewName');
  const previewMeta = document.getElementById('uploadDocumentPreviewMeta');
  const clearButton = document.getElementById('uploadDocumentClear');

  if (!form || !titleInput || !categorySelect || !fileInput || !dropzone) {
    return;
  }

  const renderFilePreview = (file) => {
    if (!preview || !previewName || !previewMeta) {
      return;
    }

    if (!file) {
      preview.classList.add('hidden');
      previewName.textContent = 'No file selected';
      previewMeta.textContent = 'Select a file to preview it here before uploading.';
      return;
    }

    preview.classList.remove('hidden');
    previewName.textContent = file.name;
    previewMeta.textContent = `${file.type || 'Unknown type'} • ${formatFileSize(file.size)}`;
  };

  const updateTitleFromFile = (file) => {
    if (!file) {
      return;
    }

    const nextTitle = buildDocumentTitleFromFile(file.name);
    const currentAutoTitle = titleInput.dataset.autoTitle || '';
    const shouldAutofill = titleInput.value.trim() === '' || titleInput.dataset.manuallyEdited !== '1' || titleInput.value.trim() === currentAutoTitle;

    if (shouldAutofill) {
      titleInput.value = nextTitle;
      titleInput.dataset.manuallyEdited = '0';
    }

    titleInput.dataset.autoTitle = nextTitle;
  };

  const setSelectedFile = (file) => {
    if (!file) {
      fileInput.value = '';
      renderFilePreview(null);
      return;
    }

    if (typeof window.DataTransfer === 'function') {
      const transfer = new window.DataTransfer();
      transfer.items.add(file);
      fileInput.files = transfer.files;
    }

    updateTitleFromFile(file);
    renderFilePreview(file);
  };

  titleInput.addEventListener('input', () => {
    const currentAutoTitle = titleInput.dataset.autoTitle || '';
    titleInput.dataset.manuallyEdited = titleInput.value.trim() !== '' && titleInput.value.trim() !== currentAutoTitle ? '1' : '0';
  });

  fileInput.addEventListener('change', () => {
    const [file] = Array.from(fileInput.files || []);
    updateTitleFromFile(file);
    renderFilePreview(file);
  });

  dropzone.addEventListener('click', () => {
    fileInput.click();
  });

  dropzone.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      fileInput.click();
    }
  });

  ['dragenter', 'dragover'].forEach((eventName) => {
    dropzone.addEventListener(eventName, (event) => {
      event.preventDefault();
      dropzone.classList.add('is-dragover');
    });
  });

  ['dragleave', 'dragend', 'drop'].forEach((eventName) => {
    dropzone.addEventListener(eventName, (event) => {
      event.preventDefault();
      dropzone.classList.remove('is-dragover');
    });
  });

  dropzone.addEventListener('drop', (event) => {
    const [file] = Array.from(event.dataTransfer?.files || []);
    setSelectedFile(file);
  });

  clearButton?.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    titleInput.dataset.autoTitle = '';
    fileInput.value = '';
    renderFilePreview(null);
  });

  form.addEventListener('submit', (event) => {
    if (form.dataset.confirmed === '1') {
      form.dataset.confirmed = '0';
      return;
    }

    event.preventDefault();
    const [file] = Array.from(fileInput.files || []);
    const title = titleInput.value.trim();
    const categoryText = categorySelect.options[categorySelect.selectedIndex]?.text?.trim() || 'Unspecified category';

    if (!file || !title || !categorySelect.value) {
      form.requestSubmit();
      return;
    }

    if (!window.Swal || typeof window.Swal.fire !== 'function') {
      form.dataset.confirmed = '1';
      form.requestSubmit();
      return;
    }

    window.Swal.fire({
      title: 'Confirm document upload?',
      html: `
        <div style="text-align:left">
          <p><strong>Title:</strong> ${title}</p>
          <p><strong>Category:</strong> ${categoryText}</p>
          <p><strong>File:</strong> ${file.name}</p>
          <p><strong>Size:</strong> ${formatFileSize(file.size)}</p>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, upload document',
      cancelButtonText: 'Review first',
      confirmButtonColor: '#166534',
    }).then((result) => {
      if (!result.isConfirmed) {
        return;
      }

      form.dataset.confirmed = '1';
      form.requestSubmit();
    });
  });
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

  initFloatingActionMenus({
    scopeSelector: '[data-action-dropdown]',
    toggleSelector: '[data-action-trigger]',
    menuSelector: '[data-action-menu]',
    itemSelector: '[data-action-item]',
    initFlag: 'employeeDocumentActionMenusInitialized',
  });

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
    const menu = row.querySelector('[data-action-menu]');
    const items = Array.from(row.querySelectorAll('[data-action-item]'));

    if (!menu || !items.length) {
      return;
    }

    menu.addEventListener('click', (event) => {
      event.stopPropagation();
    });

    items.forEach((item) => {
      item.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const action = item.getAttribute('data-action-item') || '';
        runAction(row, action);
      });
    });
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
  wireUploadModal();
  wireArchiveRestoreActions();
  wireHrRequestForm();
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
