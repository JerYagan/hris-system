import { initAdminShellInteractions, initStatusChangeConfirmations } from '/hris-system/assets/js/shared/admin-core.js';

const MIN_SKELETON_MS = 250;

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
  pageSize = 10,
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
  let initialized = false;

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
    if (!initialized) {
      return;
    }

    currentPage = 1;
    apply();
  };

  searchInput?.addEventListener('input', resetToFirstPage);
  filterConfig.forEach(({ elementId }) => {
    document.getElementById(elementId)?.addEventListener('change', resetToFirstPage);
  });
  prevButton.addEventListener('click', () => {
    if (!initialized) {
      return;
    }

    if (currentPage > 1) {
      currentPage -= 1;
      apply();
    }
  });
  nextButton.addEventListener('click', () => {
    if (!initialized) {
      return;
    }

    currentPage += 1;
    apply();
  });

  const initialize = () => {
    if (initialized) {
      return;
    }

    initialized = true;
    apply();
    table.dataset.datatableInitialized = 'true';
  };

  if (typeof window.IntersectionObserver !== 'function') {
    initialize();
    return;
  }

  const scope = table.closest('section') || table.parentElement || table;
  const observer = new window.IntersectionObserver((entries) => {
    if (!entries.some((entry) => entry.isIntersecting)) {
      return;
    }

    observer.disconnect();
    initialize();
  }, {
    rootMargin: '240px 0px',
  });

  observer.observe(scope);
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

const readReportChartPayload = () => {
  const payloadNode = document.getElementById('reportAnalyticsChartPayload');
  if (!(payloadNode instanceof HTMLScriptElement)) {
    return null;
  }

  try {
    const parsed = JSON.parse(payloadNode.textContent || '{}');
    return parsed && typeof parsed === 'object' ? parsed : null;
  } catch {
    return null;
  }
};

const buildChart = (canvasId, config) => {
  const chartFactory = window.Chart;
  const canvas = document.getElementById(canvasId);
  if (typeof chartFactory !== 'function' || !(canvas instanceof HTMLCanvasElement)) {
    return;
  }

  const labels = Array.isArray(config?.data?.labels) ? config.data.labels : [];
  if (labels.length === 0) {
    return;
  }

  // eslint-disable-next-line no-new
  new chartFactory(canvas, config);
};

const initReportCharts = () => {
  const payload = readReportChartPayload();
  if (!payload) {
    return;
  }

  buildChart('reportEmployeeStatusChart', {
    type: 'doughnut',
    data: {
      labels: payload.employeeStatus?.labels || [],
      datasets: [{
        data: payload.employeeStatus?.values || [],
        backgroundColor: ['#047857', '#d97706', '#be123c'],
        borderColor: '#ffffff',
        borderWidth: 2,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
        },
      },
    },
  });

  buildChart('reportDivisionHeadcountChart', {
    type: 'bar',
    data: {
      labels: payload.divisionHeadcount?.labels || [],
      datasets: [{
        label: 'Employees',
        data: payload.divisionHeadcount?.values || [],
        backgroundColor: '#0f766e',
        borderRadius: 8,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: 'y',
      scales: {
        x: {
          beginAtZero: true,
          ticks: {
            precision: 0,
          },
        },
      },
      plugins: {
        legend: {
          display: false,
        },
      },
    },
  });

  buildChart('reportDemographicsChart', {
    type: 'bar',
    data: {
      labels: payload.demographicsByDivision?.labels || [],
      datasets: [
        {
          label: 'Male',
          data: payload.demographicsByDivision?.male || [],
          backgroundColor: '#2563eb',
          borderRadius: 6,
        },
        {
          label: 'Female',
          data: payload.demographicsByDivision?.female || [],
          backgroundColor: '#db2777',
          borderRadius: 6,
        },
        {
          label: 'Unspecified',
          data: payload.demographicsByDivision?.unspecified || [],
          backgroundColor: '#94a3b8',
          borderRadius: 6,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: {
          stacked: true,
        },
        y: {
          stacked: true,
          beginAtZero: true,
          ticks: {
            precision: 0,
          },
        },
      },
      plugins: {
        legend: {
          position: 'bottom',
        },
      },
    },
  });

  buildChart('reportTurnoverTrainingChart', {
    type: 'bar',
    data: {
      labels: payload.turnoverTraining?.labels || [],
      datasets: [
        {
          label: 'Hires (365d)',
          data: payload.turnoverTraining?.hires || [],
          backgroundColor: '#10b981',
          borderRadius: 6,
          yAxisID: 'y',
        },
        {
          label: 'Separations (365d)',
          data: payload.turnoverTraining?.separations || [],
          backgroundColor: '#f97316',
          borderRadius: 6,
          yAxisID: 'y',
        },
        {
          type: 'line',
          label: 'Turnover Rate %',
          data: payload.turnoverTraining?.turnoverRate || [],
          borderColor: '#b91c1c',
          backgroundColor: '#b91c1c',
          tension: 0.35,
          yAxisID: 'y1',
        },
        {
          type: 'line',
          label: 'Training Completion %',
          data: payload.turnoverTraining?.trainingCompletionRate || [],
          borderColor: '#1d4ed8',
          backgroundColor: '#1d4ed8',
          tension: 0.35,
          yAxisID: 'y1',
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0,
          },
        },
        y1: {
          beginAtZero: true,
          position: 'right',
          grid: {
            drawOnChartArea: false,
          },
          ticks: {
            callback: (value) => `${value}%`,
          },
        },
      },
      plugins: {
        legend: {
          position: 'bottom',
        },
      },
    },
  });

  buildChart('reportActivityByModuleChart', {
    type: 'bar',
    data: {
      labels: payload.activityByModule?.labels || [],
      datasets: [
        {
          label: 'Admin',
          data: payload.activityByModule?.admin || [],
          backgroundColor: '#7c3aed',
          borderRadius: 6,
        },
        {
          label: 'Staff',
          data: payload.activityByModule?.staff || [],
          backgroundColor: '#0ea5e9',
          borderRadius: 6,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: 'y',
      scales: {
        x: {
          stacked: true,
          beginAtZero: true,
          ticks: {
            precision: 0,
          },
        },
        y: {
          stacked: true,
        },
      },
      plugins: {
        legend: {
          position: 'bottom',
        },
      },
    },
  });
};

const initReportSecondaryFeatures = () => {
  initDateInputs();
  initReportCoverageToggle();
  initReportCharts();

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
};

const setReportAnalyticsTabActiveState = (buttons, activeTab) => {
  buttons.forEach((button) => {
    const isActive = button.dataset.reportAnalyticsTab === activeTab;
    button.classList.toggle('bg-slate-900', isActive);
    button.classList.toggle('text-white', isActive);
    button.classList.toggle('bg-white', !isActive);
    button.classList.toggle('text-slate-700', !isActive);
    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
  });
};

const initAdminReportAnalyticsAsync = () => {
  const region = document.getElementById('adminReportAnalyticsAsyncRegion');
  const summaryUrl = region?.getAttribute('data-report-summary-url') || '';
  const workforceUrl = region?.getAttribute('data-report-workforce-url') || '';

  const summarySkeleton = document.getElementById('adminReportAnalyticsSummarySkeleton');
  const summaryContent = document.getElementById('adminReportAnalyticsSummaryContent');
  const summaryError = document.getElementById('adminReportAnalyticsSummaryError');
  const summaryRetry = document.getElementById('adminReportAnalyticsSummaryRetry');

  const secondarySkeleton = document.getElementById('adminReportAnalyticsSecondarySkeleton');
  const secondaryContent = document.getElementById('adminReportAnalyticsSecondaryContent');
  const secondaryError = document.getElementById('adminReportAnalyticsSecondaryError');
  const secondaryRetry = document.getElementById('adminReportAnalyticsSecondaryRetry');

  const tabContent = document.getElementById('adminReportAnalyticsTabContent');
  const tabLoading = document.getElementById('adminReportAnalyticsTabLoading');
  const tabError = document.getElementById('adminReportAnalyticsTabError');
  const tabRetry = document.getElementById('adminReportAnalyticsTabRetry');
  const tabButtons = Array.from(document.querySelectorAll('#adminReportAnalyticsTabs .report-analytics-tab'));

  const summaryState = { requestId: 0 };
  const tabState = { requestId: 0, activeTab: 'workforce', activeUrl: workforceUrl };
  let secondaryStarted = false;

  if (!region || summaryUrl === '' || workforceUrl === '' || !summarySkeleton || !summaryContent || !summaryError || !summaryRetry || !secondarySkeleton || !secondaryContent || !secondaryError || !secondaryRetry || !tabContent || !tabLoading || !tabError || !tabRetry || tabButtons.length === 0) {
    return false;
  }

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
      throw new Error(`Report analytics partial request failed with status ${response.status}`);
    }

    const html = await response.text();
    if (html.trim() === '') {
      throw new Error('Report analytics partial returned an empty response.');
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

  const setTabLoadingState = (isLoading) => {
    tabLoading.classList.toggle('hidden', !isLoading);
    tabContent.classList.toggle('hidden', isLoading);
    if (isLoading) {
      tabError.classList.add('hidden');
    }
    tabRetry.disabled = isLoading;
    tabButtons.forEach((button) => {
      button.disabled = isLoading;
    });
  };

  const showTabErrorState = () => {
    tabLoading.classList.add('hidden');
    tabContent.classList.add('hidden');
    tabError.classList.remove('hidden');
    tabRetry.disabled = false;
    tabButtons.forEach((button) => {
      button.disabled = false;
    });
  };

  const loadSection = async ({ url, skeleton, content, errorBox, retryButton, onSuccess, state }) => {
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

  const loadTab = async (button, { initial = false } = {}) => {
    const requestedUrl = button?.dataset?.reportAnalyticsUrl || '';
    const requestedTab = button?.dataset?.reportAnalyticsTab || '';
    if (requestedUrl === '' || requestedTab === '') {
      return;
    }

    tabState.requestId += 1;
    tabState.activeTab = requestedTab;
    tabState.activeUrl = requestedUrl;
    const requestId = tabState.requestId;
    const startedAt = window.performance?.now?.() ?? Date.now();

    if (initial) {
      setLoadingState({ skeleton: secondarySkeleton, content: secondaryContent, errorBox: secondaryError, retryButton: secondaryRetry }, true);
    } else {
      secondaryError.classList.add('hidden');
      secondaryContent.classList.remove('hidden');
      setTabLoadingState(true);
    }

    setReportAnalyticsTabActiveState(tabButtons, requestedTab);

    try {
      const html = await fetchPartialHtml(requestedUrl);
      const elapsed = (window.performance?.now?.() ?? Date.now()) - startedAt;
      const remaining = Math.max(0, MIN_SKELETON_MS - elapsed);

      window.setTimeout(() => {
        if (requestId !== tabState.requestId) {
          return;
        }

        tabContent.innerHTML = html;
        tabContent.classList.remove('hidden');
        tabLoading.classList.add('hidden');
        tabError.classList.add('hidden');
        tabRetry.disabled = false;
        tabButtons.forEach((tabButton) => {
          tabButton.disabled = false;
        });

        if (initial) {
          secondaryContent.classList.remove('hidden');
          secondarySkeleton.classList.add('hidden');
          secondaryError.classList.add('hidden');
          secondaryRetry.disabled = false;
        }

        initReportSecondaryFeatures();
      }, remaining);
    } catch (error) {
      console.error(error);
      if (initial) {
        showErrorState({ skeleton: secondarySkeleton, content: secondaryContent, errorBox: secondaryError, retryButton: secondaryRetry });
      } else {
        showTabErrorState();
      }
    }
  };

  tabButtons.forEach((button) => {
    button.addEventListener('click', () => {
      if (button.dataset.reportAnalyticsTab === tabState.activeTab) {
        return;
      }

      loadTab(button).catch(console.error);
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
      onSuccess: () => {
        if (!secondaryStarted) {
          secondaryStarted = true;
          loadTab(tabButtons[0], { initial: true }).catch(console.error);
        }
      },
    }).catch(console.error);
  });

  secondaryRetry.addEventListener('click', () => {
    const activeButton = tabButtons.find((button) => button.dataset.reportAnalyticsTab === tabState.activeTab) || tabButtons[0];
    loadTab(activeButton, { initial: true }).catch(console.error);
  });

  tabRetry.addEventListener('click', () => {
    const activeButton = tabButtons.find((button) => button.dataset.reportAnalyticsTab === tabState.activeTab) || tabButtons[0];
    loadTab(activeButton).catch(console.error);
  });

  loadSection({
    url: summaryUrl,
    skeleton: summarySkeleton,
    content: summaryContent,
    errorBox: summaryError,
    retryButton: summaryRetry,
    state: summaryState,
    onSuccess: () => {
      if (!secondaryStarted) {
        secondaryStarted = true;
        loadTab(tabButtons[0], { initial: true }).catch(console.error);
      }
    },
  }).catch(console.error);

  return true;
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

  if (!initAdminReportAnalyticsAsync()) {
    initReportSecondaryFeatures();
  }
}