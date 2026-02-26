import { bindTableFilters, initAdminShellInteractions, initModalSystem, initStatusChangeConfirmations, openModal } from '/hris-system/assets/js/shared/admin-core.js';

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
  bindTableFilters({
    tableId: 'dashboardPendingLeaveTable',
    searchInputId: 'dashboardLeaveSearch',
    searchDataAttr: 'data-dashboard-leave-search',
    statusFilterId: 'dashboardLeaveStatusFilter',
    statusDataAttr: 'data-dashboard-leave-status',
  });

  bindTableFilters({
    tableId: 'dashboardNotificationsTable',
    searchInputId: 'dashboardNotificationsSearch',
    searchDataAttr: 'data-dashboard-notification-search',
    statusFilterId: 'dashboardNotificationsStatusFilter',
    statusDataAttr: 'data-dashboard-notification-status',
  });

  bindTableFilters({
    tableId: 'dashboardDepartmentTable',
    searchInputId: 'dashboardDepartmentSearch',
    searchDataAttr: 'data-dashboard-department-search',
    statusFilterId: '',
    statusDataAttr: '',
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
