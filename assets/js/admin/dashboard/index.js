import { initAdminShellInteractions, initModalSystem, initStatusChangeConfirmations } from '/hris-system/assets/js/shared/admin-core.js';

const MIN_SKELETON_MS = 250;

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

const initDashboardSecondaryControls = ({ reloadSecondary }) => {
  const filtersForm = document.getElementById('dashboardDepartmentFilters');
  const pageInput = document.getElementById('dashboardDepartmentPage');
  const searchInput = document.getElementById('dashboardDepartmentSearch');
  const previousButton = document.getElementById('dashboardDepartmentPrevPage');
  const nextButton = document.getElementById('dashboardDepartmentNextPage');

  if (!filtersForm || !pageInput || !searchInput) {
    return;
  }

  let searchTimer = 0;

  const submitCurrentState = () => {
    reloadSecondary(new FormData(filtersForm));
  };

  filtersForm.addEventListener('submit', (event) => {
    event.preventDefault();
    pageInput.value = '1';
    submitCurrentState();
  });

  searchInput.addEventListener('input', () => {
    pageInput.value = '1';
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
      submitCurrentState();
    }, 250);
  });

  [previousButton, nextButton].forEach((button) => {
    button?.addEventListener('click', () => {
      const targetPage = button.getAttribute('data-dashboard-secondary-page') || '';
      if (targetPage === '') {
        return;
      }

      pageInput.value = targetPage;
      submitCurrentState();
    });
  });
};

const initAdminDashboardAsync = () => {
  const region = document.getElementById('adminDashboardAsyncRegion');
  const summaryUrl = region?.getAttribute('data-dashboard-summary-url') || '';
  const secondaryUrl = region?.getAttribute('data-dashboard-secondary-url') || '';

  const summarySkeleton = document.getElementById('adminDashboardSummarySkeleton');
  const summaryContent = document.getElementById('adminDashboardSummaryContent');
  const summaryError = document.getElementById('adminDashboardSummaryError');
  const summaryRetry = document.getElementById('adminDashboardSummaryRetry');

  const secondarySkeleton = document.getElementById('adminDashboardSecondarySkeleton');
  const secondaryContent = document.getElementById('adminDashboardSecondaryContent');
  const secondaryError = document.getElementById('adminDashboardSecondaryError');
  const secondaryRetry = document.getElementById('adminDashboardSecondaryRetry');
  const summaryState = { requestId: 0 };
  const secondaryState = { requestId: 0 };

  if (!region || summaryUrl === '' || secondaryUrl === '' || !summarySkeleton || !summaryContent || !summaryError || !summaryRetry || !secondarySkeleton || !secondaryContent || !secondaryError || !secondaryRetry) {
    return false;
  }

  const buildSectionUrl = (baseUrl, params) => {
    const url = new URL(baseUrl, window.location.href);

    if (!(params instanceof FormData)) {
      return url.toString();
    }

    Array.from(params.entries()).forEach(([key, value]) => {
      url.searchParams.set(key, String(value));
    });

    return url.toString();
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
      throw new Error(`Dashboard partial request failed with status ${response.status}`);
    }

    const html = await response.text();
    if (html.trim() === '') {
      throw new Error('Dashboard partial returned an empty response.');
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

  const loadSection = async ({ url, params, skeleton, content, errorBox, retryButton, onSuccess, state }) => {
    setLoadingState({ skeleton, content, errorBox, retryButton }, true);
    state.requestId += 1;
    const requestId = state.requestId;
    const startedAt = window.performance?.now?.() ?? Date.now();

    try {
      const html = await fetchPartialHtml(buildSectionUrl(url, params));
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

  const loadSecondary = (params) => loadSection({
    url: secondaryUrl,
    params,
    skeleton: secondarySkeleton,
    content: secondaryContent,
    errorBox: secondaryError,
    retryButton: secondaryRetry,
    state: secondaryState,
    onSuccess: () => {
      initDashboardSecondaryControls({ reloadSecondary: loadSecondary });
      initCharts().catch(console.error);
    },
  });

  secondaryRetry.addEventListener('click', () => {
    loadSecondary().catch(console.error);
  });

  loadSection({
    url: summaryUrl,
    skeleton: summarySkeleton,
    content: summaryContent,
    errorBox: summaryError,
    retryButton: summaryRetry,
    state: summaryState,
  }).catch(console.error);

  loadSecondary().catch(console.error);

  return true;
};

export default function initAdminDashboardPage() {
  initAdminShellInteractions();
  initModalSystem();
  initStatusChangeConfirmations();
  if (!initAdminDashboardAsync()) {
    initCharts().catch(console.error);
  }
}
