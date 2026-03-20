const STAFF_DASHBOARD_PAGE_SIZE = 5;
const MIN_SKELETON_MS = 250;

const initPaginatedFeed = ({
  cardId,
  rowSelector,
  searchInputId,
  statusFilterId,
  emptyStateId,
  infoId,
  prevButtonId,
  nextButtonId,
}) => {
  const card = document.getElementById(cardId);
  const rows = Array.from(document.querySelectorAll(rowSelector));

  if (!card || rows.length === 0) {
    return;
  }

  const searchInput = document.getElementById(searchInputId);
  const statusFilter = document.getElementById(statusFilterId);
  const emptyState = document.getElementById(emptyStateId);
  const info = document.getElementById(infoId);
  const prevButton = document.getElementById(prevButtonId);
  const nextButton = document.getElementById(nextButtonId);

  let currentPage = 1;
  let filteredRows = rows.slice();
  let initialized = false;

  const normalize = (value) => (value || '').toString().trim().toLowerCase();

  const updateControls = () => {
    const totalRows = filteredRows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / STAFF_DASHBOARD_PAGE_SIZE));
    currentPage = Math.min(currentPage, totalPages);

    if (info) {
      info.textContent = `Page ${totalRows === 0 ? 0 : currentPage} of ${totalRows === 0 ? 0 : totalPages}`;
    }

    if (prevButton) {
      const disabled = totalRows === 0 || currentPage <= 1;
      prevButton.disabled = disabled;
      prevButton.classList.toggle('opacity-60', disabled);
      prevButton.classList.toggle('cursor-not-allowed', disabled);
    }

    if (nextButton) {
      const disabled = totalRows === 0 || currentPage >= totalPages;
      nextButton.disabled = disabled;
      nextButton.classList.toggle('opacity-60', disabled);
      nextButton.classList.toggle('cursor-not-allowed', disabled);
    }
  };

  const render = () => {
    rows.forEach((row) => row.classList.add('hidden'));

    const start = (currentPage - 1) * STAFF_DASHBOARD_PAGE_SIZE;
    const end = start + STAFF_DASHBOARD_PAGE_SIZE;
    filteredRows.slice(start, end).forEach((row) => row.classList.remove('hidden'));

    if (emptyState) {
      emptyState.classList.toggle('hidden', filteredRows.length > 0);
    }

    updateControls();
  };

  const applyFilters = () => {
    const keyword = normalize(searchInput ? searchInput.value : '');
    const status = normalize(statusFilter ? statusFilter.value : '');

    filteredRows = rows.filter((row) => {
      const searchText = normalize(row.getAttribute('data-dashboard-search'));
      const rowStatus = normalize(row.getAttribute('data-dashboard-status'));

      return (keyword === '' || searchText.includes(keyword))
        && (status === '' || rowStatus === status);
    });

    currentPage = 1;
    render();
  };

  const initialize = () => {
    if (initialized) {
      return;
    }

    initialized = true;

    searchInput?.addEventListener('input', applyFilters);
    statusFilter?.addEventListener('change', applyFilters);

    prevButton?.addEventListener('click', () => {
      if (currentPage <= 1) {
        return;
      }

      currentPage -= 1;
      render();
    });

    nextButton?.addEventListener('click', () => {
      const totalPages = Math.max(1, Math.ceil(filteredRows.length / STAFF_DASHBOARD_PAGE_SIZE));
      if (currentPage >= totalPages) {
        return;
      }

      currentPage += 1;
      render();
    });

    applyFilters();
  };

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }

        initialize();
        observer.disconnect();
      });
    }, {
      rootMargin: '120px 0px',
      threshold: 0.05,
    });

    observer.observe(card);
    return;
  }

  initialize();
};

const initStaffDashboardPage = () => {
  const region = document.getElementById('staffDashboardAsyncRegion');
  const summaryUrl = region?.getAttribute('data-dashboard-summary-url') || '';
  const approvalsUrl = region?.getAttribute('data-dashboard-approvals-url') || '';
  const notificationsUrl = region?.getAttribute('data-dashboard-notifications-url') || '';
  const announcementsUrl = region?.getAttribute('data-dashboard-announcements-url') || '';

  const summarySkeleton = document.getElementById('staffDashboardSummarySkeleton');
  const summaryContent = document.getElementById('staffDashboardSummaryContent');
  const summaryError = document.getElementById('staffDashboardSummaryError');
  const summaryRetry = document.getElementById('staffDashboardSummaryRetry');

  const approvalsSkeleton = document.getElementById('staffDashboardApprovalsSkeleton');
  const approvalsContent = document.getElementById('staffDashboardApprovalsContent');
  const approvalsError = document.getElementById('staffDashboardApprovalsError');
  const approvalsRetry = document.getElementById('staffDashboardApprovalsRetry');

  const notificationsSkeleton = document.getElementById('staffDashboardNotificationsSkeleton');
  const notificationsContent = document.getElementById('staffDashboardNotificationsContent');
  const notificationsError = document.getElementById('staffDashboardNotificationsError');
  const notificationsRetry = document.getElementById('staffDashboardNotificationsRetry');

  const announcementsSkeleton = document.getElementById('staffDashboardAnnouncementsSkeleton');
  const announcementsContent = document.getElementById('staffDashboardAnnouncementsContent');
  const announcementsError = document.getElementById('staffDashboardAnnouncementsError');
  const announcementsRetry = document.getElementById('staffDashboardAnnouncementsRetry');

  if (!region || summaryUrl === '' || approvalsUrl === '' || notificationsUrl === '' || announcementsUrl === '' || !summarySkeleton || !summaryContent || !summaryError || !summaryRetry || !approvalsSkeleton || !approvalsContent || !approvalsError || !approvalsRetry || !notificationsSkeleton || !notificationsContent || !notificationsError || !notificationsRetry || !announcementsSkeleton || !announcementsContent || !announcementsError || !announcementsRetry) {
    return;
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
      throw new Error(`Dashboard partial request failed with status ${response.status}`);
    }

    const html = await response.text();
    if (html.trim() === '') {
      throw new Error('Dashboard partial returned an empty response.');
    }

    return html;
  };

  const setSectionLoadingState = ({ skeleton, content, errorBox, retryButton }, isLoading) => {
    skeleton.classList.toggle('hidden', !isLoading);
    content.classList.toggle('hidden', isLoading);
    if (isLoading) {
      errorBox.classList.add('hidden');
    }
    retryButton.disabled = isLoading;
  };

  const showSectionError = ({ skeleton, content, errorBox, retryButton }) => {
    skeleton.classList.add('hidden');
    content.classList.add('hidden');
    errorBox.classList.remove('hidden');
    retryButton.disabled = false;
  };

  const loadSection = async ({ url, skeleton, content, errorBox, retryButton, onSuccess }) => {
    setSectionLoadingState({ skeleton, content, errorBox, retryButton }, true);
    const startedAt = window.performance?.now?.() ?? Date.now();

    try {
      const html = await fetchPartialHtml(url);
      const elapsed = (window.performance?.now?.() ?? Date.now()) - startedAt;
      const remaining = Math.max(0, MIN_SKELETON_MS - elapsed);

      window.setTimeout(() => {
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
      showSectionError({ skeleton, content, errorBox, retryButton });
    }
  };

  const hydrateNotifications = () => {
    initPaginatedFeed({
      cardId: 'staffDashboardNotificationsCard',
      rowSelector: '[data-dashboard-notification-row]',
      searchInputId: 'staffDashboardNotificationsSearch',
      statusFilterId: 'staffDashboardNotificationsStatusFilter',
      emptyStateId: 'staffDashboardNotificationsEmpty',
      infoId: 'staffDashboardNotificationsInfo',
      prevButtonId: 'staffDashboardNotificationsPrev',
      nextButtonId: 'staffDashboardNotificationsNext',
    });
  };

  const hydrateAnnouncements = () => {
    initPaginatedFeed({
      cardId: 'staffDashboardAnnouncementsCard',
      rowSelector: '[data-dashboard-announcement-row]',
      searchInputId: 'staffDashboardAnnouncementsSearch',
      statusFilterId: 'staffDashboardAnnouncementsStatusFilter',
      emptyStateId: 'staffDashboardAnnouncementsEmpty',
      infoId: 'staffDashboardAnnouncementsInfo',
      prevButtonId: 'staffDashboardAnnouncementsPrev',
      nextButtonId: 'staffDashboardAnnouncementsNext',
    });
  };

  const loadApprovalsPage = async (page = 1) => {
    const nextUrl = `${approvalsUrl}&approvals_page=${Math.max(1, Number(page) || 1)}`;
    await loadSection({
      url: nextUrl,
      skeleton: approvalsSkeleton,
      content: approvalsContent,
      errorBox: approvalsError,
      retryButton: approvalsRetry,
      onSuccess: () => {
        approvalsContent.querySelectorAll('[data-dashboard-approvals-page]').forEach((button) => {
          button.addEventListener('click', () => {
            if (button.disabled) {
              return;
            }

            const targetPage = Number(button.getAttribute('data-dashboard-approvals-page') || '1');
            loadApprovalsPage(targetPage).catch(console.error);
          });
        });
      },
    });
  };

  summaryRetry.addEventListener('click', () => {
    loadSection({
      url: summaryUrl,
      skeleton: summarySkeleton,
      content: summaryContent,
      errorBox: summaryError,
      retryButton: summaryRetry,
    }).catch(console.error);
  });

  approvalsRetry.addEventListener('click', () => {
    loadApprovalsPage(1).catch(console.error);
  });

  notificationsRetry.addEventListener('click', () => {
    loadSection({
      url: notificationsUrl,
      skeleton: notificationsSkeleton,
      content: notificationsContent,
      errorBox: notificationsError,
      retryButton: notificationsRetry,
      onSuccess: hydrateNotifications,
    }).catch(console.error);
  });

  announcementsRetry.addEventListener('click', () => {
    loadSection({
      url: announcementsUrl,
      skeleton: announcementsSkeleton,
      content: announcementsContent,
      errorBox: announcementsError,
      retryButton: announcementsRetry,
      onSuccess: hydrateAnnouncements,
    }).catch(console.error);
  });

  loadSection({
    url: summaryUrl,
    skeleton: summarySkeleton,
    content: summaryContent,
    errorBox: summaryError,
    retryButton: summaryRetry,
  }).catch(console.error);

  loadApprovalsPage(1).catch(console.error);

  loadSection({
    url: notificationsUrl,
    skeleton: notificationsSkeleton,
    content: notificationsContent,
    errorBox: notificationsError,
    retryButton: notificationsRetry,
    onSuccess: hydrateNotifications,
  }).catch(console.error);

  loadSection({
    url: announcementsUrl,
    skeleton: announcementsSkeleton,
    content: announcementsContent,
    errorBox: announcementsError,
    retryButton: announcementsRetry,
    onSuccess: hydrateAnnouncements,
  }).catch(console.error);
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initStaffDashboardPage, { once: true });
} else {
  initStaffDashboardPage();
}

export default initStaffDashboardPage;