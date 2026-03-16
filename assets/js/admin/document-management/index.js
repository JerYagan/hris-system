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

const ensureFlatpickr = async () => {
  if (window.flatpickr) {
    return window.flatpickr;
  }

  await new Promise((resolve, reject) => {
    const existing = document.querySelector('script[data-flatpickr="true"]');
    if (existing) {
      existing.addEventListener('load', () => resolve(), { once: true });
      existing.addEventListener('error', () => reject(new Error('Failed to load Flatpickr.')), { once: true });
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
    script.defer = true;
    script.dataset.flatpickr = 'true';
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('Failed to load Flatpickr.'));
    document.head.appendChild(script);
  });

  return window.flatpickr;
};

const setValue = (id, value) => {
  const el = document.getElementById(id);
  if (el) {
    el.value = value;
  }
};

const setText = (id, value) => {
  const el = document.getElementById(id);
  if (el) {
    el.textContent = value;
  }
};

const closeAllActionMenus = () => {
  document.querySelectorAll('[data-doc-action-menu]').forEach((menu) => {
    menu.classList.add('hidden');
  });
};

const initActionDropdowns = () => {
  const toggles = Array.from(document.querySelectorAll('[data-doc-action-toggle]'));
  if (!toggles.length) {
    return;
  }

  toggles.forEach((toggle) => {
    toggle.addEventListener('click', (event) => {
      event.stopPropagation();
      const wrap = toggle.closest('[data-doc-action-wrap]');
      const menu = wrap?.querySelector('[data-doc-action-menu]');
      if (!menu) {
        return;
      }

      const isHidden = menu.classList.contains('hidden');
      closeAllActionMenus();
      if (isHidden) {
        menu.classList.remove('hidden');
      }
    });
  });

  document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element) || !event.target.closest('[data-doc-action-wrap]')) {
      closeAllActionMenus();
    }
  });

  document.querySelectorAll('[data-doc-action-menu] a, [data-doc-action-menu] button').forEach((item) => {
    item.addEventListener('click', () => {
      closeAllActionMenus();
    });
  });
};

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

const initUploadOwnerSearch = () => {
  const ownerInput = document.getElementById('uploadOwnerSearch');
  const ownerHiddenInput = document.getElementById('uploadOwnerPersonId');
  const ownerResults = document.getElementById('uploadOwnerResults');
  const ownerDatasetEl = document.getElementById('uploadOwnerDataset');
  const uploadForm = document.getElementById('adminUploadDocumentForm');
  if (!ownerInput || !ownerHiddenInput || !ownerResults || !ownerDatasetEl || !uploadForm) {
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

  ownerInput.addEventListener('focus', () => {
    filterAndRender();
  });

  ownerInput.addEventListener('input', () => {
    filterAndRender();
  });

  ownerInput.addEventListener('change', () => {
    syncOwnerFromInput();
  });

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
};

const initUploadFileInputUI = () => {
  const fileInput = document.getElementById('adminDocumentFileInput');
  const dropzone = document.getElementById('adminDocumentDropzone');
  const fileNameLabel = document.getElementById('adminDocumentFileName');
  if (!fileInput || !dropzone || !fileNameLabel) {
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
};

const initUploadModalTrigger = () => {
  const trigger = document.querySelector('[data-open-upload-modal]');
  if (!trigger) {
    return;
  }

  trigger.addEventListener('click', () => {
    openModal('adminUploadDocumentModal');
  });
};

const initCategoryModalTrigger = () => {
  const trigger = document.getElementById('openDocumentCategoryModal');
  if (!trigger) {
    return;
  }

  trigger.addEventListener('click', () => {
    openModal('documentCategoryModal');
  });
};

const initSectionTabs = () => {
  document.querySelectorAll('[data-section-toggle]').forEach((toggle) => {
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

    const first = buttons[0].getAttribute('data-section-tab') || '';
    buttons.forEach((button) => {
      button.addEventListener('click', () => {
        activate(button.getAttribute('data-section-tab') || '');
      });
    });

    activate(first);
  });
};

const initManagedTables = () => {
  document.querySelectorAll('[data-managed-table]').forEach((container) => {
    const rows = Array.from(container.querySelectorAll('tbody [data-table-row]'));
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

      document.querySelectorAll('[data-doc-audit]').forEach((button) => {
        button.addEventListener('click', () => {
          closeAllActionMenus();
          const title = button.getAttribute('data-document-title') || 'Document';
          const auditEntries = parseJsonArray(button.getAttribute('data-audit-trail') || '[]');

          setText('documentAuditTitle', title);

          const body = document.getElementById('documentAuditBody');
          if (!body) {
            return;
          }

          if (!auditEntries.length) {
            body.innerHTML = '<p class="text-slate-500">No audit trail entries available.</p>';
          } else {
            body.innerHTML = auditEntries.map((entry) => {
              const actionLabel = escapeHtml(entry.action_label || 'Updated');
              const actorLabel = escapeHtml(entry.actor_label || 'System');
              const createdLabel = escapeHtml(entry.created_label || '-');
              const notes = escapeHtml(entry.notes || '');

              return `
                <article class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                  <p class="font-medium text-slate-800">${actionLabel}</p>
                  <p class="text-xs text-slate-500 mt-1">${actorLabel} • ${createdLabel}</p>
                  ${notes ? `<p class="text-sm text-slate-600 mt-2">${notes}</p>` : ''}
                </article>
              `;
            }).join('');
          }

          openModal('documentAuditModal');
        });
      });
        const rowAccount = String(row.getAttribute('data-account') || '').toLowerCase();
        const rowDate = parseDate(row.getAttribute('data-date') || '');

        if (query && !rowSearch.includes(query)) {
          return false;
        }
      initCategoryModalTrigger();

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
        if (total === 0) {
          tableMeta.textContent = 'Showing 0 to 0 of 0 entries';
        } else {
          tableMeta.textContent = `Showing ${start + 1} to ${Math.min(start + pageSize, total)} of ${total} entries`;
        }
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

    if (accountTabs) {
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

const initDatePickers = async () => {
  const dateInputs = Array.from(document.querySelectorAll('[data-table-date-from], [data-table-date-to], [data-upload-document-date]'));
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

const initDocumentActions = () => {
  document.querySelectorAll('[data-doc-review]').forEach((button) => {
    button.addEventListener('click', () => {
      closeAllActionMenus();
      setValue('reviewDocumentId', button.getAttribute('data-document-id') || '');
      setValue('reviewDocumentTitle', button.getAttribute('data-document-title') || '-');
      setValue('reviewCurrentStatus', button.getAttribute('data-current-status') || '-');
      setValue('reviewStatusSelect', button.getAttribute('data-review-default') || 'approved');
      openModal('reviewDocumentModal');
    });
  });

  document.querySelectorAll('[data-doc-archive]').forEach((button) => {
    button.addEventListener('click', () => {
      closeAllActionMenus();
      setValue('archiveDocumentId', button.getAttribute('data-document-id') || '');
      setValue('archiveDocumentTitle', button.getAttribute('data-document-title') || '-');
      setValue('archiveCurrentStatus', button.getAttribute('data-current-status') || '-');
      openModal('archiveDocumentModal');
    });
  });

  document.querySelectorAll('[data-doc-uploader-open]').forEach((button) => {
    button.addEventListener('click', () => {
      const email = button.getAttribute('data-uploader-email') || 'Uploader';
      const type = button.getAttribute('data-uploader-type') || 'Unknown';
      const documents = parseJsonArray(button.getAttribute('data-uploader-documents') || '[]');

      setText('uploaderDocumentsTitle', `Uploaded Documents - ${email}`);
      setText('uploaderDocumentsMeta', `${type} • ${documents.length} file(s)`);

      const body = document.getElementById('uploaderDocumentsBody');
      if (!body) {
        return;
      }

      if (!documents.length) {
        body.innerHTML = '<tr><td class="px-4 py-3 text-slate-500" colspan="5">No documents found for this uploader.</td></tr>';
      } else {
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
      }

      openModal('uploaderDocumentsModal');
    });
  });
};

const initReviewValidation = () => {
  const form = document.getElementById('reviewDocumentForm');
  const statusSelect = document.getElementById('reviewStatusSelect');
  const notesInput = document.getElementById('reviewNotesInput');
  if (!form || !statusSelect || !notesInput) {
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
};

const initRestoreConfirmations = async () => {
  const forms = Array.from(document.querySelectorAll('[data-restore-form]'));
  if (!forms.length) {
    return;
  }

  let Swal = null;
  if (window.Swal) {
    Swal = window.Swal;
  } else {
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
      Swal = window.Swal;
    } catch (_error) {
      Swal = null;
    }
  }

  forms.forEach((form) => {
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

export default function initAdminDocumentManagementPage() {
  initAdminShellInteractions();
  initModalSystem();
  initStatusChangeConfirmations();
  initUploadModalTrigger();
  initUploadOwnerSearch();
  initUploadFileInputUI();
  initSectionTabs();
  initManagedTables();
  initActionDropdowns();
  initDocumentActions();
  initReviewValidation();
  initRestoreConfirmations().catch(console.error);
  initDatePickers().catch(console.error);
}
