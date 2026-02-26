import { bindTableFilters, initAdminShellInteractions, initModalSystem, initStatusChangeConfirmations, openModal } from '/hris-system/assets/js/shared/admin-core.js';

const escapeHtml = (value) => String(value || '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#39;');

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

const parseJsonArray = (value) => {
  try {
    const parsed = JSON.parse(value || '[]');
    return Array.isArray(parsed) ? parsed : [];
  } catch (_error) {
    return [];
  }
};

const initDocumentFilters = () => {
  bindTableFilters({
    tableId: 'documentManagementTable',
    searchInputId: 'documentManagementSearch',
    searchDataAttr: 'data-doc-search',
    statusFilterId: 'documentManagementStatusFilter',
    statusDataAttr: 'data-doc-status',
  });
};

const closeAllActionMenus = () => {
  document.querySelectorAll('[data-doc-action-menu]').forEach((menu) => {
    menu.classList.add('hidden');
  });
};

const initActionMenus = () => {
  document.querySelectorAll('[data-doc-action-menu-toggle]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      const scope = button.closest('[data-doc-action-scope]');
      const menu = scope?.querySelector('[data-doc-action-menu]');
      if (!menu) {
        return;
      }

      const shouldShow = menu.classList.contains('hidden');
      closeAllActionMenus();
      menu.classList.toggle('hidden', !shouldShow);
    });
  });

  document.addEventListener('click', (event) => {
    const inActionScope = event.target.closest('[data-doc-action-scope]');
    if (!inActionScope) {
      closeAllActionMenus();
    }
  });
};

const renderDocumentPreview = (url, extension) => {
  const content = document.getElementById('documentViewerContent');
  if (!content) {
    return;
  }

  const ext = String(extension || '').toLowerCase();
  if (!url) {
    content.innerHTML = '<p class="text-sm text-slate-500">Preview is unavailable for this document.</p>';
    return;
  }

  if (['pdf'].includes(ext)) {
    content.innerHTML = `<iframe src="${escapeHtml(url)}" class="w-full h-full rounded-lg border border-slate-200" title="Document Preview"></iframe>`;
    return;
  }

  if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(ext)) {
    content.innerHTML = `<img src="${escapeHtml(url)}" alt="Document Preview" class="max-h-full max-w-full rounded-lg border border-slate-200 bg-white p-2" />`;
    return;
  }

  content.innerHTML = '<p class="text-sm text-slate-500">Inline preview is not supported for this file type. Use Open in New Tab.</p>';
};

const initDocumentActions = () => {
  document.querySelectorAll('[data-doc-review]').forEach((button) => {
    button.addEventListener('click', () => {
      closeAllActionMenus();
      setValue('reviewDocumentId', button.getAttribute('data-document-id') || '');
      setValue('reviewDocumentTitle', button.getAttribute('data-document-title') || '-');
      setValue('reviewCurrentStatus', button.getAttribute('data-current-status') || '-');
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

  document.querySelectorAll('[data-doc-view]').forEach((button) => {
    button.addEventListener('click', () => {
      closeAllActionMenus();
      const title = button.getAttribute('data-document-title') || 'Document Viewer';
      const bucket = button.getAttribute('data-document-bucket') || '-';
      const path = button.getAttribute('data-document-path') || '-';
      const url = button.getAttribute('data-document-url') || '';
      const extension = button.getAttribute('data-document-extension') || '';

      setText('documentViewerTitle', title);
      setText('documentViewerMeta', `${bucket}/${path}`);

      const openLink = document.getElementById('documentViewerOpenLink');
      if (openLink) {
        openLink.setAttribute('href', url || '#');
      }

      renderDocumentPreview(url, extension);
      openModal('documentViewerModal');
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
      if (body) {
        if (!documents.length) {
          body.innerHTML = '<tr><td class="px-4 py-3 text-slate-500" colspan="5">No documents found for this uploader.</td></tr>';
        } else {
          body.innerHTML = documents.map((item) => {
            const title = escapeHtml(item.title || '-');
            const category = escapeHtml(item.category || '-');
            const status = escapeHtml(item.status || '-');
            const updated = escapeHtml(item.updated_at || '-');
            const storage = escapeHtml(item.storage || '-');
            return `
              <tr>
                <td class="px-4 py-3">${title}</td>
                <td class="px-4 py-3">${category}</td>
                <td class="px-4 py-3">${status}</td>
                <td class="px-4 py-3">${updated}</td>
                <td class="px-4 py-3 text-xs text-slate-500">${storage}</td>
              </tr>
            `;
          }).join('');
        }
      }

      openModal('uploaderDocumentsModal');
    });
  });
};

export default function initAdminDocumentManagementPage() {
  initAdminShellInteractions();
  initModalSystem();
  initStatusChangeConfirmations();
  initDocumentFilters();
  initActionMenus();
  initDocumentActions();
}
