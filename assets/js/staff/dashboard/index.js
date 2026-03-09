const STAFF_DASHBOARD_PAGE_SIZE = 5;

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

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initStaffDashboardPage, { once: true });
} else {
  initStaffDashboardPage();
}

export default initStaffDashboardPage;