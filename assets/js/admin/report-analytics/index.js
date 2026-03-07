import { initAdminShellInteractions, initStatusChangeConfirmations } from '/hris-system/assets/js/shared/admin-core.js';

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

const bindPaginatedTable = ({
  tableId,
  searchInputId,
  searchDataAttr,
  filterConfig = [],
  emptyRowId,
  prevId,
  nextId,
  pageLabelId,
  paginationInfoId,
  pageSize = 20,
}) => {
  const table = document.getElementById(tableId);
  const searchInput = searchInputId ? document.getElementById(searchInputId) : null;
  const emptyRow = emptyRowId ? document.getElementById(emptyRowId) : null;
  const prevButton = prevId ? document.getElementById(prevId) : null;
  const nextButton = nextId ? document.getElementById(nextId) : null;
  const pageLabel = pageLabelId ? document.getElementById(pageLabelId) : null;
  const paginationInfo = paginationInfoId ? document.getElementById(paginationInfoId) : null;

  if (!table || !prevButton || !nextButton || !pageLabel || !paginationInfo || table.dataset.datatableInitialized === 'true') {
    return;
  }

  const rows = Array.from(table.querySelectorAll('tbody tr')).filter((row) => !row.querySelector('td[colspan]'));
  let currentPage = 1;

  const apply = () => {
    const query = String(searchInput?.value || '').trim().toLowerCase();
    const filteredRows = rows.filter((row) => {
      const rowSearch = String(row.getAttribute(searchDataAttr) || '').toLowerCase();
      const searchMatch = query === '' || rowSearch.includes(query);
      if (!searchMatch) {
        return false;
      }

      return filterConfig.every(({ elementId, rowAttr }) => {
        const element = document.getElementById(elementId);
        const selected = String(element?.value || '').trim().toLowerCase();
        if (selected === '') {
          return true;
        }

        const rowValue = String(row.getAttribute(rowAttr) || '').toLowerCase();
        return rowValue === selected;
      });
    });

    const totalRows = filteredRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));
    if (currentPage > totalPages) {
      currentPage = totalPages;
    }
    if (currentPage < 1) {
      currentPage = 1;
    }

    const startIndex = totalRows === 0 ? 0 : (currentPage - 1) * pageSize;
    const endIndex = totalRows === 0 ? 0 : Math.min(startIndex + pageSize, totalRows);
    const visibleRows = new Set(filteredRows.slice(startIndex, endIndex));

    rows.forEach((row) => {
      row.classList.toggle('hidden', !visibleRows.has(row));
    });

    if (emptyRow) {
      emptyRow.classList.toggle('hidden', totalRows !== 0);
    }

    paginationInfo.textContent = totalRows === 0
      ? 'Showing 0 to 0 of 0 entries'
      : `Showing ${startIndex + 1} to ${endIndex} of ${totalRows} entries`;
    pageLabel.textContent = `Page ${totalRows === 0 ? 1 : currentPage} of ${totalRows === 0 ? 1 : totalPages}`;
    prevButton.disabled = totalRows === 0 || currentPage <= 1;
    nextButton.disabled = totalRows === 0 || currentPage >= totalPages;
  };

  const resetToFirstPage = () => {
    currentPage = 1;
    apply();
  };

  searchInput?.addEventListener('input', resetToFirstPage);
  filterConfig.forEach(({ elementId }) => {
    document.getElementById(elementId)?.addEventListener('change', resetToFirstPage);
  });
  prevButton.addEventListener('click', () => {
    if (currentPage > 1) {
      currentPage -= 1;
      apply();
    }
  });
  nextButton.addEventListener('click', () => {
    currentPage += 1;
    apply();
  });

  apply();
  table.dataset.datatableInitialized = 'true';
};

const initReportCoverageToggle = () => {
  const coverageSelect = document.getElementById('reportCoverageSelect');
  const customDateRange = document.getElementById('reportCustomDateRange');
  const customStartDate = document.getElementById('reportCustomStartDate');
  const customEndDate = document.getElementById('reportCustomEndDate');

  if (!(coverageSelect instanceof HTMLSelectElement) || !(customDateRange instanceof HTMLElement) || !(customStartDate instanceof HTMLInputElement) || !(customEndDate instanceof HTMLInputElement)) {
    return;
  }

  const toggle = () => {
    const isCustom = (coverageSelect.value || '').trim().toLowerCase() === 'custom_range';
    customDateRange.classList.toggle('hidden', !isCustom);
    customStartDate.required = isCustom;
    customEndDate.required = isCustom;
  };

  coverageSelect.addEventListener('change', toggle);
  toggle();
};

export default function initAdminReportAnalyticsPage() {
  if (document.body?.dataset?.adminReportAnalyticsInitialized === 'true') {
    return;
  }

  if (document.body) {
    document.body.dataset.adminReportAnalyticsInitialized = 'true';
  }

  initAdminShellInteractions();
  initStatusChangeConfirmations();
  initDateInputs();
  initReportCoverageToggle();

  bindPaginatedTable({
    tableId: 'reportEmployeesTable',
    searchInputId: 'reportEmployeesSearch',
    searchDataAttr: 'data-report-employee-search',
    filterConfig: [
      { elementId: 'reportEmployeesDepartmentFilter', rowAttr: 'data-report-employee-department' },
      { elementId: 'reportEmployeesStatusFilter', rowAttr: 'data-report-employee-status' },
    ],
    emptyRowId: 'reportEmployeesFilterEmpty',
    prevId: 'reportEmployeesPrev',
    nextId: 'reportEmployeesNext',
    pageLabelId: 'reportEmployeesPageLabel',
    paginationInfoId: 'reportEmployeesPaginationInfo',
  });

  bindPaginatedTable({
    tableId: 'reportDemographicsTable',
    searchInputId: 'reportDemographicsSearch',
    searchDataAttr: 'data-report-demographics-search',
    emptyRowId: 'reportDemographicsFilterEmpty',
    prevId: 'reportDemographicsPrev',
    nextId: 'reportDemographicsNext',
    pageLabelId: 'reportDemographicsPageLabel',
    paginationInfoId: 'reportDemographicsPaginationInfo',
  });

  bindPaginatedTable({
    tableId: 'reportTurnoverTable',
    searchInputId: 'reportTurnoverSearch',
    searchDataAttr: 'data-report-turnover-search',
    emptyRowId: 'reportTurnoverFilterEmpty',
    prevId: 'reportTurnoverPrev',
    nextId: 'reportTurnoverNext',
    pageLabelId: 'reportTurnoverPageLabel',
    paginationInfoId: 'reportTurnoverPaginationInfo',
  });

  bindPaginatedTable({
    tableId: 'reportActivitiesTable',
    searchInputId: 'reportActivitiesSearch',
    searchDataAttr: 'data-report-activities-search',
    filterConfig: [
      { elementId: 'reportActivitiesRoleFilter', rowAttr: 'data-report-activities-role' },
      { elementId: 'reportActivitiesModuleFilter', rowAttr: 'data-report-activities-module' },
    ],
    emptyRowId: 'reportActivitiesFilterEmpty',
    prevId: 'reportActivitiesPrev',
    nextId: 'reportActivitiesNext',
    pageLabelId: 'reportActivitiesPageLabel',
    paginationInfoId: 'reportActivitiesPaginationInfo',
  });
}