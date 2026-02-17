import { runPageStatePass } from '../../shared/ui/page-state.js';

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
  const statusFilter = document.getElementById('documentStatusFilter');
  const groupFilter = document.getElementById('documentGroupFilter');
  const file201TypeFilter = document.getElementById('document201TypeFilter');
  const rows = Array.from(document.querySelectorAll('[data-document-row]'));
  const filterEmpty = document.getElementById('documentFilterEmpty');

  if (!rows.length || !searchInput || !categoryFilter || !statusFilter || !groupFilter || !file201TypeFilter) {
    return;
  }

  const render = () => {
    const term = (searchInput.value || '').toLowerCase().trim();
    const category = (categoryFilter.value || '').toLowerCase().trim();
    const status = (statusFilter.value || '').toLowerCase().trim();
    const group = (groupFilter.value || 'all').toLowerCase().trim();
    const file201Type = (file201TypeFilter.value || '').toLowerCase().trim();

    let visibleCount = 0;

    rows.forEach((row) => {
      const haystack = (row.getAttribute('data-search') || '').toLowerCase();
      const rowCategory = (row.getAttribute('data-category') || '').toLowerCase();
      const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
      const rowGroup = (row.getAttribute('data-group') || '').toLowerCase();
      const row201Type = (row.getAttribute('data-201-type') || '').toLowerCase();

      const passTerm = term === '' || haystack.includes(term);
      const passCategory = category === '' || rowCategory === category;
      const passStatus = status === '' || rowStatus === status;
      const passGroup = group === 'all' || rowGroup === group;
      const pass201Type = file201Type === '' || row201Type === file201Type;
      const show = passTerm && passCategory && passStatus && passGroup && pass201Type;

      row.classList.toggle('hidden', !show);
      if (show) {
        visibleCount += 1;
      }
    });

    if (filterEmpty) {
      const isFiltered = term !== '' || category !== '' || status !== '' || group !== 'all' || file201Type !== '';
      filterEmpty.classList.toggle('hidden', visibleCount > 0 || !isFiltered);
    }
  };

  [searchInput, categoryFilter, statusFilter, groupFilter, file201TypeFilter].forEach((input) => {
    input.addEventListener('input', render);
    input.addEventListener('change', render);
  });

  document.querySelectorAll('[data-clear-filters]').forEach((button) => {
    button.addEventListener('click', () => {
      searchInput.value = '';
      categoryFilter.value = '';
      statusFilter.value = '';
      groupFilter.value = 'all';
      file201TypeFilter.value = '';
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
