import {
  initAdminShellInteractions,
  initModalSystem,
  initStatusChangeConfirmations,
  openModal,
} from '/hris-system/assets/js/shared/admin-core.js';

const escapeHtml = (value) => String(value || '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#39;');

const parseJsonArray = (value) => {
  try {
    const parsed = JSON.parse(value || '[]');
    return Array.isArray(parsed) ? parsed : [];
  } catch (_error) {
    return [];
  }
};

const parseDate = (value) => {
  const ts = Date.parse(String(value || '').trim());
  return Number.isNaN(ts) ? null : ts;
};

let flatpickrPromise = null;

const ensureFlatpickr = async () => {
  if (window.flatpickr) {
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

const buildPartialUrl = (baseUrl, partial, extraParams = {}) => {
  const url = new URL(baseUrl, window.location.href);
  url.searchParams.set('partial', partial);
  Object.entries(extraParams).forEach(([key, value]) => {
    if (value === null || value === undefined || String(value).trim() === '') {
      return;
    }
    url.searchParams.set(key, value);
  });
  return url.toString();
};

const setValue = (id, value) => {
  const element = document.getElementById(id);
  if (element) {
    element.value = value;
  }
};

const setText = (id, value) => {
  const element = document.getElementById(id);
  if (element) {
    element.textContent = value;
  }
};

const initManagedTables = (root = document) => {
  root.querySelectorAll('[data-managed-table]').forEach((container) => {
    if (container.dataset.dmTableInitialized === 'true') {
      return;
    }

    const rows = Array.from(container.querySelectorAll('tbody [data-table-row]'));
    container.dataset.dmTableInitialized = 'true';

    if (!rows.length) {
      return;
    }

    let currentPage = 1;
    const pageSize = 10;
    let activeAccount = 'all';

    const searchInput = container.querySelector('[data-table-search]');
    const statusSelect = container.querySelector('[data-table-status]');
    const categorySelect = container.querySelector('[data-table-category]');
    const dateFromInput = container.querySelector('[data-table-date-from]');
    const dateToInput = container.querySelector('[data-table-date-to]');
    const prevButton = container.querySelector('[data-page-prev]');
    const nextButton = container.querySelector('[data-page-next]');
    const pageInfo = container.querySelector('[data-page-info]');
    const tableMeta = container.querySelector('[data-table-meta]');
    const accountTabs = container.closest('section')?.querySelector('[data-account-tabs]');

    const filterRows = () => {
      const query = String(searchInput?.value || '').toLowerCase().trim();
      const status = String(statusSelect?.value || '').toLowerCase().trim();
      const category = String(categorySelect?.value || '').toLowerCase().trim();
      const dateFrom = parseDate(dateFromInput?.value || '');
      const dateToRaw = parseDate(dateToInput?.value || '');
      const dateTo = dateToRaw === null ? null : (dateToRaw + (24 * 60 * 60 * 1000) - 1);

      const filtered = rows.filter((row) => {
        const rowSearch = String(row.getAttribute('data-search') || '').toLowerCase();
        const rowStatus = String(row.getAttribute('data-status') || '').toLowerCase();
        const rowCategory = String(row.getAttribute('data-category') || '').toLowerCase();
        const rowAccount = String(row.getAttribute('data-account') || '').toLowerCase();
        const rowDate = parseDate(row.getAttribute('data-date') || '');

        if (query && !rowSearch.includes(query)) {
          return false;
        }

        if (status && rowStatus !== status) {
          return false;
        }

        if (category && rowCategory !== category) {
          return false;
        }

        if (activeAccount !== 'all' && rowAccount !== activeAccount) {
          return false;
        }

        if (dateFrom !== null && (rowDate === null || rowDate < dateFrom)) {
          return false;
        }

        if (dateTo !== null && (rowDate === null || rowDate > dateTo)) {
          return false;
        }

        return true;
      });

      const total = filtered.length;
      const totalPages = Math.max(1, Math.ceil(total / pageSize));
      if (currentPage > totalPages) {
        currentPage = totalPages;
      }

      const start = (currentPage - 1) * pageSize;
      const visibleSet = new Set(filtered.slice(start, start + pageSize));

      rows.forEach((row) => {
        row.style.display = visibleSet.has(row) ? '' : 'none';
      });

      if (tableMeta) {
        tableMeta.textContent = total === 0
          ? 'Showing 0 to 0 of 0 entries'
          : `Showing ${start + 1} to ${Math.min(start + pageSize, total)} of ${total} entries`;
      }

      if (pageInfo) {
        pageInfo.textContent = `Page ${total === 0 ? 1 : currentPage} of ${totalPages}`;
      }

      if (prevButton) {
        prevButton.disabled = currentPage <= 1;
        prevButton.classList.toggle('opacity-50', prevButton.disabled);
      }

      if (nextButton) {
        nextButton.disabled = currentPage >= totalPages;
        nextButton.classList.toggle('opacity-50', nextButton.disabled);
      }

      container.dispatchEvent(new CustomEvent('dm:table-filtered'));
    };

    const run = () => {
      currentPage = 1;
      filterRows();
    };

    searchInput?.addEventListener('input', run);
    statusSelect?.addEventListener('change', run);
    categorySelect?.addEventListener('change', run);
    dateFromInput?.addEventListener('change', run);
    dateToInput?.addEventListener('change', run);

    prevButton?.addEventListener('click', () => {
      if (currentPage <= 1) {
        return;
      }
      currentPage -= 1;
      filterRows();
    });

    nextButton?.addEventListener('click', () => {
      currentPage += 1;
      filterRows();
    });

    if (accountTabs && accountTabs.dataset.dmAccountTabsInitialized !== 'true') {
      accountTabs.dataset.dmAccountTabsInitialized = 'true';
      accountTabs.querySelectorAll('[data-account-tab]').forEach((button) => {
        button.addEventListener('click', () => {
          activeAccount = (button.getAttribute('data-account-tab') || 'all').toLowerCase();
          accountTabs.querySelectorAll('[data-account-tab]').forEach((item) => {
            const active = item === button;
            item.classList.toggle('bg-white', active);
            item.classList.toggle('border', active);
            item.classList.toggle('border-slate-200', active);
            item.classList.toggle('text-slate-700', active);
            item.classList.toggle('text-slate-600', !active);
          });
          run();
        });
      });
    }

    filterRows();
  });
};

const initSectionTabs = (root = document) => {
  root.querySelectorAll('[data-section-toggle]').forEach((toggle) => {
    if (toggle.dataset.dmSectionTabsInitialized === 'true') {
      return;
    }

    const buttons = Array.from(toggle.querySelectorAll('[data-section-tab]'));
    if (!buttons.length) {
      return;
    }

    const activate = (key) => {
      buttons.forEach((button) => {
        const active = (button.getAttribute('data-section-tab') || '') === key;
        button.classList.toggle('bg-white', active);
        button.classList.toggle('border', active);
        button.classList.toggle('border-slate-200', active);
        button.classList.toggle('text-slate-700', active);
        button.classList.toggle('text-slate-600', !active);
      });

      const parent = toggle.closest('section');
      if (!parent) {
        return;
      }

      parent.querySelectorAll('[data-section-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', panel.getAttribute('data-section-panel') !== key);
      });
    };

    buttons.forEach((button) => {
      button.addEventListener('click', () => {
        activate(button.getAttribute('data-section-tab') || '');
      });
    });

    toggle.dataset.dmSectionTabsInitialized = 'true';
    activate(buttons[0].getAttribute('data-section-tab') || '');
  });
};

const initUploadOwnerSearch = () => {
  const ownerInput = document.getElementById('uploadOwnerSearch');
  const ownerHiddenInput = document.getElementById('uploadOwnerPersonId');
  const ownerResults = document.getElementById('uploadOwnerResults');
  const ownerDatasetEl = document.getElementById('uploadOwnerDataset');
  const uploadForm = document.getElementById('adminUploadDocumentForm');
  if (!ownerInput || !ownerHiddenInput || !ownerResults || !ownerDatasetEl || !uploadForm || uploadForm.dataset.dmOwnerSearchInitialized === 'true') {
    return;
  }

  const ownerList = (() => {
    try {
      const parsed = JSON.parse(ownerDatasetEl.textContent || '[]');
      if (!Array.isArray(parsed)) {
        return [];
      }
      return parsed
        .map((owner) => ({
          id: String(owner?.id || '').trim(),
          name: String(owner?.name || '').trim(),
          email: String(owner?.email || '').trim(),
          photoUrl: String(owner?.photo_url || '').trim(),
          roleKey: String(owner?.role_key || '').trim().toLowerCase(),
        }))
        .filter((owner) => owner.id && owner.name);
    } catch (_error) {
      return [];
    }
  })();

  if (!ownerList.length) {
    uploadForm.dataset.dmOwnerSearchInitialized = 'true';
    return;
  }

  const buildInitials = (name) => {
    const pieces = String(name || '').trim().split(/\s+/).filter(Boolean);
    return (pieces[0]?.[0] || '').toUpperCase() + (pieces[1]?.[0] || '').toUpperCase();
  };

  const renderResultRows = (rows) => {
    if (!rows.length) {
      ownerResults.innerHTML = '<div class="px-3 py-2 text-xs text-slate-500">No matching owners found.</div>';
      ownerResults.classList.remove('hidden');
      return;
    }

    ownerResults.innerHTML = rows.map((owner) => {
      const imageNode = owner.photoUrl
        ? `<img src="${escapeHtml(owner.photoUrl)}" alt="${escapeHtml(owner.name)}" class="h-9 w-9 rounded-full object-cover border border-slate-200" loading="lazy">`
        : `<div class="h-9 w-9 rounded-full bg-slate-200 text-slate-600 text-xs font-semibold flex items-center justify-center border border-slate-300">${escapeHtml(buildInitials(owner.name) || 'U')}</div>`;

      return `
        <button
          type="button"
          class="w-full px-3 py-2 text-left hover:bg-slate-50 flex items-center gap-3"
          data-owner-result
          data-owner-id="${escapeHtml(owner.id)}"
          data-owner-name="${escapeHtml(owner.name)}"
        >
          ${imageNode}
          <span class="min-w-0">
            <span class="block text-sm font-medium text-slate-700 truncate">${escapeHtml(owner.name)}</span>
            <span class="block text-xs text-slate-500 truncate">${escapeHtml(owner.email || 'No email')}</span>
            <span class="block text-[11px] text-slate-400 truncate">${escapeHtml(owner.roleKey === 'staff' ? 'Staff' : 'Employee')}</span>
          </span>
        </button>
      `;
    }).join('');

    ownerResults.classList.remove('hidden');
  };

  const findOwnerByName = (name) => ownerList.find((owner) => owner.name.toLowerCase() === String(name || '').trim().toLowerCase());

  const syncOwnerFromInput = () => {
    const matched = findOwnerByName(ownerInput.value);
    ownerHiddenInput.value = matched?.id || '';
  };

  const filterAndRender = () => {
    const query = String(ownerInput.value || '').trim().toLowerCase();
    if (!query) {
      renderResultRows(ownerList);
      syncOwnerFromInput();
      return;
    }

    const filtered = ownerList.filter((owner) => owner.name.toLowerCase().includes(query) || owner.email.toLowerCase().includes(query));
    renderResultRows(filtered);
    syncOwnerFromInput();
  };

  ownerInput.addEventListener('focus', filterAndRender);
  ownerInput.addEventListener('input', filterAndRender);
  ownerInput.addEventListener('change', syncOwnerFromInput);

  ownerResults.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target.closest('[data-owner-result]') : null;
    if (!target) {
      return;
    }

    ownerInput.value = target.getAttribute('data-owner-name') || '';
    ownerHiddenInput.value = target.getAttribute('data-owner-id') || '';
    ownerResults.classList.add('hidden');
  });

  document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
      return;
    }
    if (event.target.closest('#uploadOwnerSearch') || event.target.closest('#uploadOwnerResults')) {
      return;
    }
    ownerResults.classList.add('hidden');
  });

  filterAndRender();

  uploadForm.addEventListener('submit', (event) => {
    syncOwnerFromInput();
    if (String(ownerHiddenInput.value || '').trim() !== '') {
      return;
    }

    event.preventDefault();
    if (window.Swal && typeof window.Swal.fire === 'function') {
      window.Swal.fire({
        icon: 'warning',
        title: 'Select a valid owner',
        text: 'Please choose an owner from the searchable list before uploading.',
        confirmButtonColor: '#0f172a',
      });
    } else {
      window.alert('Please choose a valid owner from the searchable list before uploading.');
    }
  });

  uploadForm.dataset.dmOwnerSearchInitialized = 'true';
};

const initUploadFileInputUI = () => {
  const fileInput = document.getElementById('adminDocumentFileInput');
  const dropzone = document.getElementById('adminDocumentDropzone');
  const fileNameLabel = document.getElementById('adminDocumentFileName');
  if (!fileInput || !dropzone || !fileNameLabel || dropzone.dataset.dmUploadUiInitialized === 'true') {
    return;
  }

  const refreshFileName = () => {
    const file = fileInput.files?.[0];
    fileNameLabel.textContent = file ? file.name : 'No file selected';
  };

  dropzone.addEventListener('click', () => {
    fileInput.click();
  });

  dropzone.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      fileInput.click();
    }
  });

  fileInput.addEventListener('change', refreshFileName);

  dropzone.addEventListener('dragover', (event) => {
    event.preventDefault();
    dropzone.classList.add('border-slate-500', 'bg-slate-100');
  });

  dropzone.addEventListener('dragleave', () => {
    dropzone.classList.remove('border-slate-500', 'bg-slate-100');
  });

  dropzone.addEventListener('drop', (event) => {
    event.preventDefault();
    dropzone.classList.remove('border-slate-500', 'bg-slate-100');
    const droppedFiles = event.dataTransfer?.files;
    if (!droppedFiles || !droppedFiles.length) {
      return;
    }

    try {
      const transfer = new DataTransfer();
      transfer.items.add(droppedFiles[0]);
      fileInput.files = transfer.files;
      refreshFileName();
    } catch (_error) {
      fileInput.click();
    }
  });

  dropzone.dataset.dmUploadUiInitialized = 'true';
};

const initReviewValidation = () => {
  const form = document.getElementById('reviewDocumentForm');
  const statusSelect = document.getElementById('reviewStatusSelect');
  const notesInput = document.getElementById('reviewNotesInput');
  if (!form || !statusSelect || !notesInput || form.dataset.dmReviewValidationInitialized === 'true') {
    return;
  }

  form.addEventListener('submit', (event) => {
    const status = String(statusSelect.value || '').toLowerCase();
    const notes = String(notesInput.value || '').trim();
    if ((status === 'rejected' || status === 'needs_revision') && !notes) {
      event.preventDefault();
      window.alert('Review notes are required for rejected or needs revision decisions.');
    }
  });

  form.dataset.dmReviewValidationInitialized = 'true';
};

const initBulkReviewValidation = () => {
  const form = document.getElementById('bulkReviewDocumentForm');
  const statusSelect = document.getElementById('bulkReviewStatusSelect');
  const notesInput = document.getElementById('bulkReviewNotesInput');
  const idsHost = document.getElementById('bulkReviewDocumentIds');
  if (!form || !statusSelect || !notesInput || !idsHost || form.dataset.dmBulkReviewValidationInitialized === 'true') {
    return;
  }

  form.addEventListener('submit', (event) => {
    const selectedIds = idsHost.querySelectorAll('input[name="document_ids[]"]');
    const status = String(statusSelect.value || '').toLowerCase();
    const notes = String(notesInput.value || '').trim();

    if (!selectedIds.length) {
      event.preventDefault();
      window.alert('Select at least one document before submitting a bulk review.');
      return;
    }

    if (status === 'rejected' && !notes) {
      event.preventDefault();
      window.alert('Review notes are required for bulk rejection.');
    }
  });

  form.dataset.dmBulkReviewValidationInitialized = 'true';
};

const initBulkSelectionTables = (root = document) => {
  root.querySelectorAll('[data-managed-table]').forEach((container) => {
    if (container.dataset.dmBulkSelectionInitialized === 'true') {
      return;
    }

    const toolbar = container.querySelector('[data-bulk-selection-toolbar]');
    const selectAll = container.querySelector('[data-bulk-select-all]');
    const checkboxes = Array.from(container.querySelectorAll('[data-bulk-document-checkbox]'));
    if (!toolbar || !selectAll || !checkboxes.length) {
      container.dataset.dmBulkSelectionInitialized = 'true';
      return;
    }

    const getVisibleCheckboxes = () => checkboxes.filter((checkbox) => checkbox.closest('tr')?.style.display !== 'none');

    const syncToolbar = () => {
      const selected = checkboxes.filter((checkbox) => checkbox.checked);
      const selectedCount = selected.length;
      const countLabel = toolbar.querySelector('[data-bulk-selection-count]');
      if (countLabel) {
        countLabel.textContent = String(selectedCount);
      }

      toolbar.classList.toggle('hidden', selectedCount === 0);
      toolbar.classList.toggle('flex', selectedCount > 0);

      const visibleCheckboxes = getVisibleCheckboxes();
      const visibleCheckedCount = visibleCheckboxes.filter((checkbox) => checkbox.checked).length;
      selectAll.checked = visibleCheckboxes.length > 0 && visibleCheckedCount === visibleCheckboxes.length;
      selectAll.indeterminate = visibleCheckedCount > 0 && visibleCheckedCount < visibleCheckboxes.length;
    };

    selectAll.addEventListener('change', () => {
      const visibleCheckboxes = getVisibleCheckboxes();
      visibleCheckboxes.forEach((checkbox) => {
        checkbox.checked = selectAll.checked;
      });
      syncToolbar();
    });

    checkboxes.forEach((checkbox) => {
      checkbox.addEventListener('change', syncToolbar);
    });

    container.addEventListener('dm:table-filtered', syncToolbar);

    syncToolbar();
    container.dataset.dmBulkSelectionInitialized = 'true';
  });
};

const initRestoreConfirmations = async (root = document) => {
  const forms = Array.from(root.querySelectorAll('[data-restore-form]')).filter((form) => form.dataset.dmRestoreConfirmationInitialized !== 'true');
  if (!forms.length) {
    return;
  }

  let Swal = window.Swal || null;
  if (!Swal) {
    try {
      await new Promise((resolve, reject) => {
        const existing = document.querySelector('script[data-swal="true"]');
        if (existing) {
          existing.addEventListener('load', () => resolve(), { once: true });
          existing.addEventListener('error', () => reject(new Error('Failed to load SweetAlert2.')), { once: true });
          return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        script.defer = true;
        script.dataset.swal = 'true';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load SweetAlert2.'));
        document.head.appendChild(script);
      });
      Swal = window.Swal || null;
    } catch (_error) {
      Swal = null;
    }
  }

  forms.forEach((form) => {
    form.dataset.dmRestoreConfirmationInitialized = 'true';
    form.addEventListener('submit', async (event) => {
      if (form.dataset.confirmedSubmit === 'true') {
        return;
      }

      event.preventDefault();

      if (Swal) {
        const result = await Swal.fire({
          title: 'Restore this document?',
          text: 'This moves the record back to submitted status for review queues.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Restore',
          cancelButtonText: 'Cancel',
          confirmButtonColor: '#0f172a',
          reverseButtons: true,
        });

        if (!result.isConfirmed) {
          return;
        }
      } else if (!window.confirm('Restore this document to submitted status?')) {
        return;
      }

      form.dataset.confirmedSubmit = 'true';
      form.submit();
    });
  });
};

const initDatePickers = async (root = document) => {
  const dateInputs = Array.from(root.querySelectorAll('[data-table-date-from], [data-table-date-to], [data-upload-document-date]'));
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

const initializeDynamicScope = (root = document) => {
  initSectionTabs(root);
  initManagedTables(root);
  initBulkSelectionTables(root);
  initUploadOwnerSearch();
  initUploadFileInputUI();
  initReviewValidation();
  initBulkReviewValidation();
  initRestoreConfirmations(root).catch(console.error);
  initDatePickers(root).catch(console.error);
};

const ensureModalHub = async () => {
  const host = document.getElementById('adminDocumentManagementModalHost');
  if (!host) {
    return null;
  }

  if (host.dataset.loaded === 'true') {
    return host;
  }

  const baseUrl = host.getAttribute('data-base-url') || 'document-management.php';
  host.innerHTML = '<div class="hidden"></div>';
  const html = await fetchHtml(buildPartialUrl(baseUrl, 'modals'));
  host.innerHTML = html;
  host.dataset.loaded = 'true';
  initModalSystem();
  initStatusChangeConfirmations();
  initializeDynamicScope(host);
  return host;
};

const populateUploaderDocuments = (button) => {
  const email = button.getAttribute('data-uploader-email') || 'Uploader';
  const type = button.getAttribute('data-uploader-type') || 'Unknown';
  const documents = parseJsonArray(button.getAttribute('data-uploader-documents') || '[]');
  const body = document.getElementById('uploaderDocumentsBody');
  if (!body) {
    return;
  }

  setText('uploaderDocumentsTitle', `Uploaded Documents - ${email}`);
  setText('uploaderDocumentsMeta', `${type} • ${documents.length} file(s)`);

  if (!documents.length) {
    body.innerHTML = '<tr><td class="px-4 py-3 text-slate-500" colspan="5">No documents found for this uploader.</td></tr>';
    return;
  }

  body.innerHTML = documents.map((item) => {
    const title = escapeHtml(item.title || '-');
    const category = escapeHtml(item.category || '-');
    const status = escapeHtml(item.status || '-');
    const updated = escapeHtml(item.updated || '-');
    const previewUrl = escapeHtml(item.preview_url || '#');
    const downloadUrl = escapeHtml(item.download_url || item.url || '#');
    return `
      <tr>
        <td class="px-4 py-3">${title}</td>
        <td class="px-4 py-3">${category}</td>
        <td class="px-4 py-3">${status}</td>
        <td class="px-4 py-3">${updated}</td>
        <td class="px-4 py-3">
          <div class="inline-flex items-center gap-2">
            <a href="${previewUrl}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">
              <span class="material-symbols-outlined text-[14px]">visibility</span>View
            </a>
            <a href="${downloadUrl}" download class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-md border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
              <span class="material-symbols-outlined text-[14px]">download</span>Download
            </a>
          </div>
        </td>
      </tr>
    `;
  }).join('');
};

const initAsyncWorkspace = () => {
  const region = document.querySelector('[data-admin-doc-async-region]');
  if (!region) {
    return;
  }

  const baseUrl = region.getAttribute('data-base-url') || 'document-management.php';
  const loadingState = region.querySelector('[data-doc-async-loading]');
  const errorState = region.querySelector('[data-doc-async-error]');
  const emptyState = region.querySelector('[data-doc-async-empty]');
  const contentState = region.querySelector('[data-doc-async-content]');
  const triggers = Array.from(region.querySelectorAll('[data-doc-async-trigger]'));
  const cache = new Map();
  let lastSection = '';

  const setButtonState = (activeSection) => {
    triggers.forEach((button) => {
      const active = (button.getAttribute('data-doc-async-trigger') || '') === activeSection;
      button.classList.toggle('bg-slate-900', active);
      button.classList.toggle('text-white', active);
      button.classList.toggle('border-slate-900', active);
      button.classList.toggle('bg-white', !active);
      button.classList.toggle('text-slate-700', !active);
      button.classList.toggle('border-slate-300', !active);
    });
  };

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
    initializeDynamicScope(contentState);
  };

  const showError = () => {
    loadingState?.classList.add('hidden');
    contentState?.classList.add('hidden');
    emptyState?.classList.add('hidden');
    errorState?.classList.remove('hidden');
  };

  const loadSection = async (section, forceRefresh = false) => {
    if (!section) {
      return;
    }

    lastSection = section;
    setButtonState(section);

    if (!forceRefresh && cache.has(section)) {
      showContent(cache.get(section));
      return;
    }

    showLoading();

    try {
      const html = await fetchHtml(buildPartialUrl(baseUrl, section));
      cache.set(section, html);
      showContent(html);
    } catch (_error) {
      showError();
    }
  };

  triggers.forEach((button) => {
    button.addEventListener('click', () => {
      loadSection(button.getAttribute('data-doc-async-trigger') || '');
    });
  });

  region.querySelector('[data-doc-async-retry]')?.addEventListener('click', () => {
    if (lastSection) {
      loadSection(lastSection, true);
    }
  });
};

const loadAuditTrail = async (button) => {
  const host = document.getElementById('adminDocumentManagementModalHost');
  const baseUrl = host?.getAttribute('data-base-url') || 'document-management.php';
  const documentId = button.getAttribute('data-document-id') || '';
  const title = button.getAttribute('data-document-title') || 'Document';
  if (!documentId) {
    return;
  }

  await ensureModalHub();
  const body = document.getElementById('documentAuditBody');
  if (!body) {
    return;
  }

  body.dataset.documentId = documentId;
  body.dataset.documentTitle = title;
  setText('documentAuditTitle', title);
  body.innerHTML = '<p class="text-slate-500">Loading audit trail...</p>';
  openModal('documentAuditModal');

  try {
    const html = await fetchHtml(buildPartialUrl(baseUrl, 'audit', { document_id: documentId }));
    body.innerHTML = html;
  } catch (_error) {
    body.innerHTML = '<div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><p>Unable to load audit trail.</p><button type="button" data-doc-audit-retry class="mt-3 inline-flex items-center gap-2 rounded-md bg-slate-900 px-3 py-2 text-xs text-white hover:bg-slate-800">Retry</button></div>';
  }
};

const initDelegatedActions = () => {
  if (document.body.dataset.dmDelegatedActionsInitialized === 'true') {
    return;
  }

  document.body.dataset.dmDelegatedActionsInitialized = 'true';

  document.addEventListener('click', async (event) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target) {
      return;
    }

    const uploadTrigger = target.closest('[data-open-upload-modal]');
    if (uploadTrigger) {
      event.preventDefault();
      await ensureModalHub();
      openModal('adminUploadDocumentModal');
      return;
    }

    const categoryTrigger = target.closest('#openDocumentCategoryModal');
    if (categoryTrigger) {
      event.preventDefault();
      await ensureModalHub();
      openModal('documentCategoryModal');
      return;
    }

    const reviewTrigger = target.closest('[data-doc-review]');
    if (reviewTrigger) {
      event.preventDefault();
      await ensureModalHub();
      setValue('reviewDocumentId', reviewTrigger.getAttribute('data-document-id') || '');
      setValue('reviewDocumentTitle', reviewTrigger.getAttribute('data-document-title') || '-');
      setValue('reviewCurrentStatus', reviewTrigger.getAttribute('data-current-status') || '-');
      setValue('reviewStatusSelect', reviewTrigger.getAttribute('data-review-default') || 'approved');
      openModal('reviewDocumentModal');
      return;
    }

    const bulkReviewTrigger = target.closest('[data-doc-bulk-open]');
    if (bulkReviewTrigger) {
      event.preventDefault();
      const table = bulkReviewTrigger.closest('[data-managed-table]');
      if (!table) {
        return;
      }

      const selected = Array.from(table.querySelectorAll('[data-bulk-document-checkbox]:checked'));
      if (!selected.length) {
        window.alert('Select at least one document first.');
        return;
      }

      await ensureModalHub();
      const idsHost = document.getElementById('bulkReviewDocumentIds');
      const summaryInput = document.getElementById('bulkReviewSelectionSummary');
      const statusSelect = document.getElementById('bulkReviewStatusSelect');
      const notesInput = document.getElementById('bulkReviewNotesInput');
      const label = table.querySelector('[data-bulk-selection-toolbar]')?.getAttribute('data-bulk-label') || 'document';
      if (!idsHost || !summaryInput || !statusSelect || !notesInput) {
        return;
      }

      idsHost.innerHTML = selected.map((checkbox) => `<input type="hidden" name="document_ids[]" value="${escapeHtml(checkbox.value || '')}">`).join('');
      summaryInput.value = `${selected.length} ${label}${selected.length === 1 ? '' : 's'} selected`;
      statusSelect.value = bulkReviewTrigger.getAttribute('data-bulk-review-status') || 'approved';
      notesInput.value = '';
      openModal('bulkReviewDocumentModal');
      return;
    }

    const fulfillRequestTrigger = target.closest('[data-doc-request-fulfill]');
    if (fulfillRequestTrigger) {
      event.preventDefault();
      await ensureModalHub();
      setValue('fulfillRequestId', fulfillRequestTrigger.getAttribute('data-request-id') || '');
      setValue('fulfillRequestTitle', fulfillRequestTrigger.getAttribute('data-request-title') || '-');
      setValue('fulfillRequesterLabel', fulfillRequestTrigger.getAttribute('data-requester-label') || '-');
      setValue('fulfillPurposeLabel', fulfillRequestTrigger.getAttribute('data-purpose-label') || '-');
      setValue('fulfillDocumentTitle', fulfillRequestTrigger.getAttribute('data-default-title') || '');
      setValue('fulfillNotesInput', '');
      openModal('fulfillDocumentRequestModal');
      return;
    }

    const archiveTrigger = target.closest('[data-doc-archive]');
    if (archiveTrigger) {
      event.preventDefault();
      await ensureModalHub();
      setValue('archiveDocumentId', archiveTrigger.getAttribute('data-document-id') || '');
      setValue('archiveDocumentTitle', archiveTrigger.getAttribute('data-document-title') || '-');
      setValue('archiveCurrentStatus', archiveTrigger.getAttribute('data-current-status') || '-');
      openModal('archiveDocumentModal');
      return;
    }

    const uploaderTrigger = target.closest('[data-doc-uploader-open]');
    if (uploaderTrigger) {
      event.preventDefault();
      await ensureModalHub();
      populateUploaderDocuments(uploaderTrigger);
      openModal('uploaderDocumentsModal');
      return;
    }

    const auditTrigger = target.closest('[data-doc-audit]');
    if (auditTrigger) {
      event.preventDefault();
      await loadAuditTrail(auditTrigger);
      return;
    }

    const auditRetry = target.closest('[data-doc-audit-retry]');
    if (auditRetry) {
      event.preventDefault();
      const body = document.getElementById('documentAuditBody');
      if (!body) {
        return;
      }
      const retryButton = document.createElement('button');
      retryButton.setAttribute('data-document-id', body.dataset.documentId || '');
      retryButton.setAttribute('data-document-title', body.dataset.documentTitle || 'Document');
      await loadAuditTrail(retryButton);
    }
  });
};

export default function initAdminDocumentManagementPage() {
  initAdminShellInteractions();
  initModalSystem();
  initStatusChangeConfirmations();
  initializeDynamicScope(document);
  initAsyncWorkspace();
  initDelegatedActions();
}