const PAGE_SIZE = 10;
const MIN_SKELETON_MS = 250;

const confirmAction = async ({ title, text, confirmButtonText = 'Confirm' }) => {
  if (window.Swal && typeof window.Swal.fire === 'function') {
    const result = await window.Swal.fire({
      title,
      text,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText,
      cancelButtonText: 'Cancel',
      reverseButtons: true,
    });

    return Boolean(result && result.isConfirmed);
  }

  return window.confirm(text);
};

const debounce = (fn, wait = 250) => {
  let timer = null;

  return (...args) => {
    if (timer) {
      window.clearTimeout(timer);
    }

    timer = window.setTimeout(() => {
      fn(...args);
    }, wait);
  };
};

const initReportTable = (config) => {
  const rows = Array.from(document.querySelectorAll(`[data-report-row="${config.key}"]`));
  if (!rows.length) {
    return;
  }

  const table = document.getElementById(config.tableId);
  if (table?.dataset.staffReportInitialized === 'true') {
    return;
  }

  const searchInput = config.searchInputId ? document.getElementById(config.searchInputId) : null;
  const statusFilter = config.statusFilterId ? document.getElementById(config.statusFilterId) : null;
  const departmentFilter = config.departmentFilterId ? document.getElementById(config.departmentFilterId) : null;
  const startDateInput = config.startDateInputId ? document.getElementById(config.startDateInputId) : null;
  const endDateInput = config.endDateInputId ? document.getElementById(config.endDateInputId) : null;
  const emptyRow = document.getElementById(config.emptyRowId);
  const prevButton = document.getElementById(config.prevButtonId);
  const nextButton = document.getElementById(config.nextButtonId);
  const pageLabel = document.getElementById(config.pageLabelId);
  const paginationInfo = document.getElementById(config.paginationInfoId);

  let currentPage = 1;

  const apply = () => {
    const searchNeedle = (searchInput?.value || '').trim().toLowerCase();
    const statusNeedle = (statusFilter?.value || '').trim().toLowerCase();
    const departmentNeedle = (departmentFilter?.value || '').trim().toLowerCase();
    const startDate = (startDateInput?.value || '').trim();
    const endDate = (endDateInput?.value || '').trim();

    const filteredRows = rows.filter((row) => {
      const rowSearch = (row.getAttribute('data-report-search') || '').toLowerCase();
      const rowStatus = (row.getAttribute('data-report-status') || '').toLowerCase();
      const rowDepartment = (row.getAttribute('data-report-department') || '').toLowerCase();
      const rowDate = (row.getAttribute('data-report-date') || '').trim();

      if (searchNeedle !== '' && !rowSearch.includes(searchNeedle)) {
        return false;
      }
      if (statusNeedle !== '' && rowStatus !== statusNeedle) {
        return false;
      }
      if (departmentNeedle !== '' && rowDepartment !== departmentNeedle) {
        return false;
      }
      if (startDate !== '' && (rowDate === '' || rowDate < startDate)) {
        return false;
      }
      if (endDate !== '' && (rowDate === '' || rowDate > endDate)) {
        return false;
      }

      return true;
    });

    const totalRows = filteredRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / PAGE_SIZE));
    if (currentPage > totalPages) {
      currentPage = totalPages;
    }

    const startIndex = totalRows === 0 ? 0 : (currentPage - 1) * PAGE_SIZE;
    const endIndex = totalRows === 0 ? 0 : Math.min(startIndex + PAGE_SIZE, totalRows);
    const visibleSet = new Set(filteredRows.slice(startIndex, endIndex));

    rows.forEach((row) => {
      row.classList.toggle('hidden', !visibleSet.has(row));
    });

    emptyRow?.classList.toggle('hidden', totalRows !== 0);

    if (paginationInfo) {
      paginationInfo.textContent = totalRows === 0
        ? 'Showing 0 to 0 of 0 entries'
        : `Showing ${startIndex + 1} to ${endIndex} of ${totalRows} entries`;
    }

    if (pageLabel) {
      pageLabel.textContent = `Page ${totalRows === 0 ? 1 : currentPage} of ${totalRows === 0 ? 1 : totalPages}`;
    }

    if (prevButton) {
      prevButton.disabled = totalRows === 0 || currentPage <= 1;
    }
    if (nextButton) {
      nextButton.disabled = totalRows === 0 || currentPage >= totalPages;
    }
  };

  const debouncedApply = debounce(() => {
    currentPage = 1;
    apply();
  }, 250);

  searchInput?.addEventListener('input', debouncedApply);
  statusFilter?.addEventListener('change', () => {
    currentPage = 1;
    apply();
  });
  departmentFilter?.addEventListener('change', () => {
    currentPage = 1;
    apply();
  });
  startDateInput?.addEventListener('change', () => {
    currentPage = 1;
    apply();
  });
  endDateInput?.addEventListener('change', () => {
    currentPage = 1;
    apply();
  });
  prevButton?.addEventListener('click', () => {
    if (currentPage > 1) {
      currentPage -= 1;
      apply();
    }
  });
  nextButton?.addEventListener('click', () => {
    currentPage += 1;
    apply();
  });

  if (table) {
    table.dataset.staffReportInitialized = 'true';
  }

  apply();
};

const initExportFormInteractions = () => {
  const exportForm = document.getElementById('staffReportExportForm');
  if (!(exportForm instanceof HTMLFormElement) || exportForm.dataset.staffReportExportInitialized === 'true') {
    return;
  }

  const coverageSelect = document.getElementById('staffReportCoverageSelect');
  const customRangeWrap = document.getElementById('staffReportCustomDateRange');
  const startInput = document.getElementById('staffReportStartDate');
  const endInput = document.getElementById('staffReportEndDate');
  const exportSubmit = document.getElementById('staffReportExportSubmit');
  const exportReset = document.getElementById('staffReportExportReset');

  const syncCoverageUi = () => {
    const custom = (coverageSelect?.value || '').toLowerCase() === 'custom_range';
    customRangeWrap?.classList.toggle('hidden', !custom);
    if (startInput instanceof HTMLInputElement) {
      startInput.required = custom;
    }
    if (endInput instanceof HTMLInputElement) {
      endInput.required = custom;
    }
  };

  coverageSelect?.addEventListener('change', syncCoverageUi);
  syncCoverageUi();

  exportForm.addEventListener('submit', async (event) => {
    const reportType = (exportForm.querySelector('[name="report_type"]')?.value || 'report').replace(/_/g, ' ');
    const fileFormat = (exportForm.querySelector('[name="file_format"]')?.value || '').toUpperCase();

    event.preventDefault();
    const confirmed = await confirmAction({
      title: 'Generate report export?',
      text: `Generate ${reportType} export as ${fileFormat}?`,
      confirmButtonText: 'Generate',
    });
    if (!confirmed) {
      return;
    }

    if (exportSubmit instanceof HTMLButtonElement) {
      exportSubmit.disabled = true;
      exportSubmit.textContent = 'Generating...';
    }

    exportForm.submit();
  });

  exportReset?.addEventListener('click', async (event) => {
    event.preventDefault();
    const confirmed = await confirmAction({
      title: 'Reset export filters?',
      text: 'Clear the current export selections and restore the default report export options?',
      confirmButtonText: 'Reset',
    });

    if (!confirmed) {
      return;
    }

    exportForm.reset();
    syncCoverageUi();
  });

  exportForm.dataset.staffReportExportInitialized = 'true';
};

const initDatasetInteractions = (datasetKey) => {
  if (datasetKey === 'workforce') {
    initReportTable({
      key: 'employees',
      tableId: 'staffReportEmployeesTable',
      searchInputId: 'staffReportEmployeesSearch',
      statusFilterId: 'staffReportEmployeesStatusFilter',
      departmentFilterId: 'staffReportEmployeesDepartmentFilter',
      startDateInputId: null,
      endDateInputId: null,
      emptyRowId: 'staffReportEmployeesFilterEmpty',
      prevButtonId: 'staffReportEmployeesPrev',
      nextButtonId: 'staffReportEmployeesNext',
      pageLabelId: 'staffReportEmployeesPageLabel',
      paginationInfoId: 'staffReportEmployeesPaginationInfo',
    });
  }

  if (datasetKey === 'timekeeping') {
    initReportTable({
      key: 'timekeeping',
      tableId: 'staffReportTimekeepingTable',
      searchInputId: 'staffReportTimekeepingSearch',
      statusFilterId: 'staffReportTimekeepingStatusFilter',
      departmentFilterId: 'staffReportTimekeepingDepartmentFilter',
      startDateInputId: 'staffReportTimekeepingStartDate',
      endDateInputId: 'staffReportTimekeepingEndDate',
      emptyRowId: 'staffReportTimekeepingFilterEmpty',
      prevButtonId: 'staffReportTimekeepingPrev',
      nextButtonId: 'staffReportTimekeepingNext',
      pageLabelId: 'staffReportTimekeepingPageLabel',
      paginationInfoId: 'staffReportTimekeepingPaginationInfo',
    });
  }

  if (datasetKey === 'payroll') {
    initReportTable({
      key: 'payroll',
      tableId: 'staffReportPayrollTable',
      searchInputId: 'staffReportPayrollSearch',
      statusFilterId: 'staffReportPayrollStatusFilter',
      departmentFilterId: 'staffReportPayrollDepartmentFilter',
      startDateInputId: 'staffReportPayrollStartDate',
      endDateInputId: 'staffReportPayrollEndDate',
      emptyRowId: 'staffReportPayrollFilterEmpty',
      prevButtonId: 'staffReportPayrollPrev',
      nextButtonId: 'staffReportPayrollNext',
      pageLabelId: 'staffReportPayrollPageLabel',
      paginationInfoId: 'staffReportPayrollPaginationInfo',
    });
  }

  if (datasetKey === 'recruitment') {
    initReportTable({
      key: 'recruitment',
      tableId: 'staffReportRecruitmentTable',
      searchInputId: 'staffReportRecruitmentSearch',
      statusFilterId: 'staffReportRecruitmentStatusFilter',
      departmentFilterId: 'staffReportRecruitmentDepartmentFilter',
      startDateInputId: 'staffReportRecruitmentStartDate',
      endDateInputId: 'staffReportRecruitmentEndDate',
      emptyRowId: 'staffReportRecruitmentFilterEmpty',
      prevButtonId: 'staffReportRecruitmentPrev',
      nextButtonId: 'staffReportRecruitmentNext',
      pageLabelId: 'staffReportRecruitmentPageLabel',
      paginationInfoId: 'staffReportRecruitmentPaginationInfo',
    });
  }
};

const fetchPartialHtml = async (url) => {
  const response = await fetch(url, {
    credentials: 'same-origin',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
    },
  });

  if (response.redirected) {
    window.location.href = response.url;
    return '';
  }

  if (!response.ok) {
    throw new Error(`Staff reports partial request failed with status ${response.status}`);
  }

  const html = await response.text();
  if (html.trim() === '') {
    throw new Error('Staff reports partial returned an empty response.');
  }

  return html;
};

const setLoadingState = ({ skeleton, content, errorBox, retryButton }, isLoading) => {
  skeleton.classList.toggle('hidden', !isLoading);
  content.classList.toggle('hidden', isLoading);
  if (isLoading) {
    errorBox.classList.add('hidden');
  }
  retryButton.disabled = isLoading;
};

const showErrorState = ({ skeleton, content, errorBox, retryButton }) => {
  skeleton.classList.add('hidden');
  content.classList.add('hidden');
  errorBox.classList.remove('hidden');
  retryButton.disabled = false;
};

const setDatasetButtonState = (buttons, activeTab = '') => {
  buttons.forEach((button) => {
    const isActive = button.dataset.staffReportTab === activeTab;
    button.classList.toggle('bg-slate-900', isActive);
    button.classList.toggle('text-white', isActive);
    button.classList.toggle('bg-white', !isActive);
    button.classList.toggle('text-slate-700', !isActive);
  });
};

const initStaffReportsPage = () => {
  const region = document.getElementById('staffReportsAsyncRegion');
  if (!region || document.body?.dataset?.staffReportsInitialized === 'true') {
    return;
  }

  if (document.body) {
    document.body.dataset.staffReportsInitialized = 'true';
  }

  const summaryUrl = region.getAttribute('data-report-summary-url') || '';
  const exportUrl = region.getAttribute('data-report-export-url') || '';
  const summarySkeleton = document.getElementById('staffReportsSummarySkeleton');
  const summaryContent = document.getElementById('staffReportsSummaryContent');
  const summaryError = document.getElementById('staffReportsSummaryError');
  const summaryRetry = document.getElementById('staffReportsSummaryRetry');
  const exportSkeleton = document.getElementById('staffReportsExportSkeleton');
  const exportContent = document.getElementById('staffReportsExportContent');
  const exportError = document.getElementById('staffReportsExportError');
  const exportRetry = document.getElementById('staffReportsExportRetry');
  const datasetIdle = document.getElementById('staffReportsDatasetIdle');
  const datasetLoading = document.getElementById('staffReportsDatasetLoading');
  const datasetContent = document.getElementById('staffReportsDatasetContent');
  const datasetError = document.getElementById('staffReportsDatasetError');
  const datasetRetry = document.getElementById('staffReportsDatasetRetry');
  const datasetButtons = Array.from(document.querySelectorAll('#staffReportsDatasetTabs .staff-report-dataset-tab'));

  if (!summarySkeleton || !summaryContent || !summaryError || !summaryRetry || !exportSkeleton || !exportContent || !exportError || !exportRetry || !datasetIdle || !datasetLoading || !datasetContent || !datasetError || !datasetRetry) {
    return;
  }

  const summaryState = { requestId: 0 };
  const exportState = { requestId: 0 };
  const datasetState = { requestId: 0, activeTab: '', activeUrl: '' };

  const loadSection = async ({ url, skeleton, content, errorBox, retryButton, state, onSuccess }) => {
    setLoadingState({ skeleton, content, errorBox, retryButton }, true);
    state.requestId += 1;
    const requestId = state.requestId;
    const startedAt = window.performance?.now?.() ?? Date.now();

    try {
      const html = await fetchPartialHtml(url);
      const elapsed = (window.performance?.now?.() ?? Date.now()) - startedAt;
      const remaining = Math.max(0, MIN_SKELETON_MS - elapsed);

      window.setTimeout(() => {
        if (requestId !== state.requestId) {
          return;
        }

        content.innerHTML = html;
        content.classList.remove('hidden');
        skeleton.classList.add('hidden');
        errorBox.classList.add('hidden');
        retryButton.disabled = false;

        if (typeof onSuccess === 'function') {
          onSuccess();
        }
      }, remaining);
    } catch (error) {
      console.error(error);
      showErrorState({ skeleton, content, errorBox, retryButton });
    }
  };

  const loadDataset = async (button) => {
    const requestedUrl = button?.dataset?.staffReportUrl || '';
    const requestedTab = button?.dataset?.staffReportTab || '';
    if (requestedUrl === '' || requestedTab === '') {
      return;
    }

    datasetState.requestId += 1;
    datasetState.activeTab = requestedTab;
    datasetState.activeUrl = requestedUrl;
    const requestId = datasetState.requestId;
    const startedAt = window.performance?.now?.() ?? Date.now();

    datasetIdle.classList.add('hidden');
    datasetError.classList.add('hidden');
    datasetContent.classList.add('hidden');
    datasetLoading.classList.remove('hidden');
    datasetRetry.disabled = true;
    datasetButtons.forEach((tabButton) => {
      tabButton.disabled = true;
    });
    setDatasetButtonState(datasetButtons, requestedTab);

    try {
      const html = await fetchPartialHtml(requestedUrl);
      const elapsed = (window.performance?.now?.() ?? Date.now()) - startedAt;
      const remaining = Math.max(0, MIN_SKELETON_MS - elapsed);

      window.setTimeout(() => {
        if (requestId !== datasetState.requestId) {
          return;
        }

        datasetContent.innerHTML = html;
        datasetContent.classList.remove('hidden');
        datasetLoading.classList.add('hidden');
        datasetError.classList.add('hidden');
        datasetRetry.disabled = false;
        datasetButtons.forEach((tabButton) => {
          tabButton.disabled = false;
        });

        initDatasetInteractions(requestedTab);
      }, remaining);
    } catch (error) {
      console.error(error);
      datasetLoading.classList.add('hidden');
      datasetContent.classList.add('hidden');
      datasetError.classList.remove('hidden');
      datasetRetry.disabled = false;
      datasetButtons.forEach((tabButton) => {
        tabButton.disabled = false;
      });
    }
  };

  datasetButtons.forEach((button) => {
    button.addEventListener('click', () => {
      if (button.dataset.staffReportTab === datasetState.activeTab) {
        return;
      }

      loadDataset(button).catch(console.error);
    });
  });

  summaryRetry.addEventListener('click', () => {
    loadSection({
      url: summaryUrl,
      skeleton: summarySkeleton,
      content: summaryContent,
      errorBox: summaryError,
      retryButton: summaryRetry,
      state: summaryState,
    }).catch(console.error);
  });

  exportRetry.addEventListener('click', () => {
    loadSection({
      url: exportUrl,
      skeleton: exportSkeleton,
      content: exportContent,
      errorBox: exportError,
      retryButton: exportRetry,
      state: exportState,
      onSuccess: initExportFormInteractions,
    }).catch(console.error);
  });

  datasetRetry.addEventListener('click', () => {
    const activeButton = datasetButtons.find((button) => button.dataset.staffReportTab === datasetState.activeTab);
    if (activeButton) {
      loadDataset(activeButton).catch(console.error);
    }
  });

  loadSection({
    url: summaryUrl,
    skeleton: summarySkeleton,
    content: summaryContent,
    errorBox: summaryError,
    retryButton: summaryRetry,
    state: summaryState,
  }).catch(console.error);

  loadSection({
    url: exportUrl,
    skeleton: exportSkeleton,
    content: exportContent,
    errorBox: exportError,
    retryButton: exportRetry,
    state: exportState,
    onSuccess: initExportFormInteractions,
  }).catch(console.error);
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initStaffReportsPage, { once: true });
} else {
  initStaffReportsPage();
}

export default initStaffReportsPage;