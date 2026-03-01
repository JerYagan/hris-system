import { runPageStatePass } from '../../shared/ui/page-state.js';

const toggleModal = (modal, open) => {
  if (!modal) return;
  modal.classList.toggle('hidden', !open);
  modal.setAttribute('aria-hidden', open ? 'false' : 'true');
};

const initEmployeePersonalReportsPage = () => {
  runPageStatePass({ pageKey: 'employee-personal-reports' });

  const pageSize = 10;
  const generatedRows = Array.from(document.querySelectorAll('[data-generated-report-row="1"]'));
  const generatedSearch = document.getElementById('employeeGeneratedReportsSearch');
  const generatedStatusFilter = document.getElementById('employeeGeneratedReportsStatusFilter');
  const generatedEmpty = document.getElementById('employeeGeneratedReportsEmpty');
  const generatedPrev = document.getElementById('employeeGeneratedReportsPrev');
  const generatedNext = document.getElementById('employeeGeneratedReportsNext');
  const generatedPageLabel = document.getElementById('employeeGeneratedReportsPageLabel');
  const generatedPaginationInfo = document.getElementById('employeeGeneratedReportsPaginationInfo');
  let generatedCurrentPage = 1;

  const applyGeneratedTableState = () => {
    if (!generatedRows.length || !generatedPrev || !generatedNext || !generatedPageLabel || !generatedPaginationInfo) {
      return;
    }

    const searchNeedle = (generatedSearch?.value || '').trim().toLowerCase();
    const statusNeedle = (generatedStatusFilter?.value || '').trim().toLowerCase();

    const filteredRows = generatedRows.filter((row) => {
      const rowSearch = (row.getAttribute('data-generated-report-search') || '').toLowerCase();
      const rowStatus = (row.getAttribute('data-generated-report-status') || '').toLowerCase();

      if (searchNeedle !== '' && !rowSearch.includes(searchNeedle)) {
        return false;
      }
      if (statusNeedle !== '' && rowStatus !== statusNeedle) {
        return false;
      }

      return true;
    });

    const totalRows = filteredRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));
    if (generatedCurrentPage > totalPages) {
      generatedCurrentPage = totalPages;
    }
    if (generatedCurrentPage < 1) {
      generatedCurrentPage = 1;
    }

    const startIndex = totalRows === 0 ? 0 : (generatedCurrentPage - 1) * pageSize;
    const endIndex = totalRows === 0 ? 0 : Math.min(startIndex + pageSize, totalRows);
    const visibleRows = new Set(filteredRows.slice(startIndex, endIndex));

    generatedRows.forEach((row) => {
      row.classList.toggle('hidden', !visibleRows.has(row));
    });

    if (generatedEmpty) {
      generatedEmpty.classList.toggle('hidden', totalRows !== 0);
    }

    generatedPaginationInfo.textContent = totalRows === 0
      ? 'Showing 0 to 0 of 0 entries'
      : `Showing ${startIndex + 1} to ${endIndex} of ${totalRows} entries`;
    generatedPageLabel.textContent = `Page ${totalRows === 0 ? 1 : generatedCurrentPage} of ${totalRows === 0 ? 1 : totalPages}`;
    generatedPrev.disabled = totalRows === 0 || generatedCurrentPage <= 1;
    generatedNext.disabled = totalRows === 0 || generatedCurrentPage >= totalPages;
  };

  generatedSearch?.addEventListener('input', () => {
    generatedCurrentPage = 1;
    applyGeneratedTableState();
  });

  generatedStatusFilter?.addEventListener('change', () => {
    generatedCurrentPage = 1;
    applyGeneratedTableState();
  });

  generatedPrev?.addEventListener('click', () => {
    if (generatedCurrentPage > 1) {
      generatedCurrentPage -= 1;
      applyGeneratedTableState();
    }
  });

  generatedNext?.addEventListener('click', () => {
    generatedCurrentPage += 1;
    applyGeneratedTableState();
  });

  applyGeneratedTableState();

  const modal = document.getElementById('requestReportModal');

  document.querySelectorAll('[data-open-request-report]').forEach((button) => {
    button.addEventListener('click', () => toggleModal(modal, true));
  });

  document.querySelectorAll('[data-close-request-report]').forEach((button) => {
    button.addEventListener('click', () => toggleModal(modal, false));
  });

  modal?.addEventListener('click', (event) => {
    if (event.target === modal) {
      toggleModal(modal, false);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      toggleModal(modal, false);
    }
  });

  const quickAction = new URLSearchParams(window.location.search).get('quick_action');
  if (quickAction === 'generate-report') {
    toggleModal(modal, true);
  }
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEmployeePersonalReportsPage);
} else {
  initEmployeePersonalReportsPage();
}

export default initEmployeePersonalReportsPage;
