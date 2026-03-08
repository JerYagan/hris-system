import { initAdminShellInteractions, initModalSystem, initStatusChangeConfirmations, openModal } from '/assets/js/shared/admin-core.js';

const parseJsonArray = (value) => {
  try {
    const parsed = JSON.parse(value || '[]');
    return Array.isArray(parsed) ? parsed : [];
  } catch (_error) {
    return [];
  }
};

const ensureChartJs = async () => {
  if (window.Chart) {
    return window.Chart;
  }

  await new Promise((resolve, reject) => {
    const existing = document.querySelector('script[data-chartjs="true"]');
    if (existing) {
      existing.addEventListener('load', () => resolve(), { once: true });
      existing.addEventListener('error', () => reject(new Error('Unable to load Chart.js')), { once: true });
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    script.defer = true;
    script.dataset.chartjs = 'true';
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('Unable to load Chart.js'));
    document.head.appendChild(script);
  });

  return window.Chart;
};

const initCharts = async () => {
  const canvases = Array.from(document.querySelectorAll('canvas[data-chart-type]'));
  if (!canvases.length) {
    return;
  }

  const ChartLib = await ensureChartJs();
  canvases.forEach((canvas) => {
    const labels = parseJsonArray(canvas.getAttribute('data-chart-labels'));
    const values = parseJsonArray(canvas.getAttribute('data-chart-values'));
    const colors = parseJsonArray(canvas.getAttribute('data-chart-colors'));
    const chartType = canvas.getAttribute('data-chart-type') || 'bar';
    const chartLabel = canvas.getAttribute('data-chart-label') || 'Dataset';

    if (!labels.length || !values.length || labels.length !== values.length) {
      return;
    }

    const context = canvas.getContext('2d');
    if (!context) {
      return;
    }

    new ChartLib(context, {
      type: chartType,
      data: {
        labels,
        datasets: [
          {
            label: chartLabel,
            data: values,
            backgroundColor: colors.length ? colors : ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
            borderWidth: 0,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: chartType === 'doughnut' ? 'bottom' : 'top',
          },
        },
      },
    });
  });
};

const initDashboardActions = () => {
  document.querySelectorAll('[data-dashboard-leave-open]').forEach((button) => {
    button.addEventListener('click', () => {
      const leaveRequestId = button.getAttribute('data-leave-request-id') || '';
      const employeeName = button.getAttribute('data-employee-name') || '-';
      const currentStatus = button.getAttribute('data-current-status') || '-';
      const dateRange = button.getAttribute('data-date-range') || '-';

      const idInput = document.getElementById('dashboardLeaveRequestId');
      const employeeInput = document.getElementById('dashboardLeaveEmployeeName');
      const statusInput = document.getElementById('dashboardLeaveCurrentStatus');
      const rangeInput = document.getElementById('dashboardLeaveDateRange');

      if (idInput) idInput.value = leaveRequestId;
      if (employeeInput) employeeInput.value = employeeName;
      if (statusInput) statusInput.value = currentStatus;
      if (rangeInput) rangeInput.value = dateRange;

      openModal('dashboardLeaveReviewModal');
    });
  });

  document.querySelectorAll('[data-dashboard-notification-open]').forEach((button) => {
    button.addEventListener('click', () => {
      const notificationId = button.getAttribute('data-notification-id') || '';
      const title = button.getAttribute('data-notification-title') || '-';
      const idInput = document.getElementById('dashboardNotificationId');
      const titleElement = document.getElementById('dashboardNotificationTitle');

      if (idInput) idInput.value = notificationId;
      if (titleElement) titleElement.textContent = title;

      openModal('dashboardNotificationModal');
    });
  });
};

const initDashboardFilters = () => {
  const bindDashboardTable = ({
    tableId,
    searchInputId,
    searchDataAttr,
    statusFilterId,
    statusDataAttr,
    secondaryFilterId,
    secondaryDataAttr,
    metaId,
    prevId,
    nextId,
    pageSize = 10,
  }) => {
    const table = document.getElementById(tableId);
    if (!table) {
      return;
    }

    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const searchInput = searchInputId ? document.getElementById(searchInputId) : null;
    const statusFilter = statusFilterId ? document.getElementById(statusFilterId) : null;
    const secondaryFilter = secondaryFilterId ? document.getElementById(secondaryFilterId) : null;
    const metaNode = metaId ? document.getElementById(metaId) : null;
    const prevBtn = prevId ? document.getElementById(prevId) : null;
    const nextBtn = nextId ? document.getElementById(nextId) : null;

    let currentPage = 1;

    const apply = () => {
      const query = String(searchInput?.value || '').toLowerCase().trim();
      const selectedStatus = String(statusFilter?.value || '').toLowerCase().trim();
      const selectedSecondary = String(secondaryFilter?.value || '').toLowerCase().trim();

      const filteredRows = rows.filter((row) => {
        const rowSearch = String(row.getAttribute(searchDataAttr) || '').toLowerCase();
        const rowStatus = statusDataAttr ? String(row.getAttribute(statusDataAttr) || '').toLowerCase() : '';
        const rowSecondary = secondaryDataAttr ? String(row.getAttribute(secondaryDataAttr) || '').toLowerCase() : '';
        const searchMatch = query === '' || rowSearch.includes(query);
        const statusMatch = selectedStatus === '' || rowStatus === selectedStatus;
        const secondaryMatch = selectedSecondary === '' || rowSecondary === selectedSecondary;
        return searchMatch && statusMatch && secondaryMatch;
      });

      const total = filteredRows.length;
      const totalPages = Math.max(1, Math.ceil(total / pageSize));
      if (currentPage > totalPages) {
        currentPage = totalPages;
      }
      if (currentPage < 1) {
        currentPage = 1;
      }

      const start = (currentPage - 1) * pageSize;
      const end = start + pageSize;
      const visibleSet = new Set(filteredRows.slice(start, end));

      rows.forEach((row) => {
        row.style.display = visibleSet.has(row) ? '' : 'none';
      });

      if (metaNode) {
        if (total === 0) {
          metaNode.textContent = 'Showing 0 to 0 of 0 entries';
        } else {
          metaNode.textContent = `Showing ${start + 1} to ${Math.min(end, total)} of ${total} entries`;
        }
      }

      if (prevBtn) {
        prevBtn.disabled = currentPage <= 1;
      }
      if (nextBtn) {
        nextBtn.disabled = currentPage >= totalPages || total === 0;
      }
    };

    const rerenderFromFirstPage = () => {
      currentPage = 1;
      apply();
    };

    searchInput?.addEventListener('input', rerenderFromFirstPage);
    statusFilter?.addEventListener('change', rerenderFromFirstPage);
    secondaryFilter?.addEventListener('change', rerenderFromFirstPage);
    prevBtn?.addEventListener('click', () => {
      currentPage -= 1;
      apply();
    });
    nextBtn?.addEventListener('click', () => {
      currentPage += 1;
      apply();
    });

    apply();
  };

  bindDashboardTable({
    tableId: 'dashboardPendingLeaveTable',
    searchInputId: 'dashboardLeaveSearch',
    searchDataAttr: 'data-dashboard-leave-search',
    statusFilterId: 'dashboardLeaveStatusFilter',
    statusDataAttr: 'data-dashboard-leave-status',
    secondaryFilterId: '',
    secondaryDataAttr: '',
    metaId: 'dashboardLeavePaginationMeta',
    prevId: 'dashboardLeavePrevPage',
    nextId: 'dashboardLeaveNextPage',
  });

  bindDashboardTable({
    tableId: 'dashboardNotificationsTable',
    searchInputId: 'dashboardNotificationsSearch',
    searchDataAttr: 'data-dashboard-notification-search',
    statusFilterId: 'dashboardNotificationsStatusFilter',
    statusDataAttr: 'data-dashboard-notification-status',
    secondaryFilterId: 'dashboardNotificationsCategoryFilter',
    secondaryDataAttr: 'data-dashboard-notification-category',
    metaId: 'dashboardNotificationsPaginationMeta',
    prevId: 'dashboardNotificationsPrevPage',
    nextId: 'dashboardNotificationsNextPage',
  });

  bindDashboardTable({
    tableId: 'dashboardDepartmentTable',
    searchInputId: 'dashboardDepartmentSearch',
    searchDataAttr: 'data-dashboard-department-search',
    statusFilterId: '',
    statusDataAttr: '',
    secondaryFilterId: '',
    secondaryDataAttr: '',
    metaId: 'dashboardDepartmentPaginationMeta',
    prevId: 'dashboardDepartmentPrevPage',
    nextId: 'dashboardDepartmentNextPage',
  });
};

export default function initAdminDashboardPage() {
  initAdminShellInteractions();
  initModalSystem();
  initStatusChangeConfirmations();
  initDashboardFilters();
  initDashboardActions();
  initCharts().catch(console.error);
}
