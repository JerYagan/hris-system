import { runPageStatePass } from '../../shared/ui/page-state.js';

const normalize = (value) => (value || '').toString().trim().toLowerCase();

const initTrainingDetailsModal = () => {
  const modal = document.getElementById('lndTrainingDetailsModal');
  if (!modal) {
    return;
  }

  const detailsTitle = document.getElementById('lndDetailsTitle');
  const detailsProvider = document.getElementById('lndDetailsProvider');
  const detailsType = document.getElementById('lndDetailsType');
  const detailsCategory = document.getElementById('lndDetailsCategory');
  const detailsSchedule = document.getElementById('lndDetailsSchedule');
  const detailsProgramStatus = document.getElementById('lndDetailsProgramStatus');
  const detailsEnrollment = document.getElementById('lndDetailsEnrollment');
  const detailsAttendance = document.getElementById('lndDetailsAttendance');

  const closeModal = () => {
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden');
  };

  const openModal = () => {
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');
  };

  const setText = (node, value, fallback = '-') => {
    if (!node) {
      return;
    }
    const normalized = String(value || '').trim();
    node.textContent = normalized || fallback;
  };

  const showDetails = (card) => {
    if (!card) {
      return;
    }

    setText(detailsTitle, card.getAttribute('data-title'));
    setText(detailsProvider, card.getAttribute('data-provider'));
    setText(detailsType, card.getAttribute('data-type'));
    setText(detailsCategory, card.getAttribute('data-category'));
    setText(detailsSchedule, card.getAttribute('data-schedule'));
    setText(detailsProgramStatus, card.getAttribute('data-program-status'));
    setText(detailsEnrollment, card.getAttribute('data-enrollment-status'));
    setText(detailsAttendance, card.getAttribute('data-attendance'));

    openModal();
  };

  document.querySelectorAll('[data-lnd-training-card]').forEach((card) => {
    card.addEventListener('click', () => showDetails(card));

    card.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter' && event.key !== ' ') {
        return;
      }
      event.preventDefault();
      showDetails(card);
    });
  });

  document.querySelectorAll('[data-lnd-view-details]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      const card = button.closest('[data-lnd-training-card]');
      showDetails(card);
    });
  });

  modal.querySelectorAll('[data-lnd-modal-close]').forEach((trigger) => {
    trigger.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
      return;
    }

    if (!modal.classList.contains('hidden')) {
      closeModal();
    }
  });
};

const initTrainingHistoryModal = () => {
  const modal = document.getElementById('trainingHistoryModal');
  const openButton = document.getElementById('openTrainingHistoryModal');

  if (!modal || !openButton) {
    return;
  }

  const closeModal = () => {
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden');
  };

  const openModal = () => {
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');
  };

  openButton.addEventListener('click', openModal);

  modal.querySelectorAll('[data-training-history-modal-close]').forEach((trigger) => {
    trigger.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
      return;
    }

    if (!modal.classList.contains('hidden')) {
      closeModal();
    }
  });
};

const initTrainingViewTabs = () => {
  const tabs = Array.from(document.querySelectorAll('[data-lnd-tab]'));
  const panels = Array.from(document.querySelectorAll('[data-lnd-tab-panel]'));

  if (!tabs.length || !panels.length) {
    return;
  }

  const activate = (target) => {
    const normalizedTarget = normalize(target);

    tabs.forEach((tab) => {
      const isActive = normalize(tab.getAttribute('data-lnd-tab')) === normalizedTarget;
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
      tab.classList.toggle('bg-white', isActive);
      tab.classList.toggle('text-gray-900', isActive);
      tab.classList.toggle('shadow-sm', isActive);
      tab.classList.toggle('text-gray-600', !isActive);
    });

    panels.forEach((panel) => {
      const isActive = normalize(panel.getAttribute('data-lnd-tab-panel')) === normalizedTarget;
      panel.classList.toggle('hidden', !isActive);
    });
  };

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      activate(tab.getAttribute('data-lnd-tab') || 'available');
    });
  });

  activate('available');
};

const createPaginatedFilter = ({
  rows,
  searchInput,
  statusInput,
  pageInfo,
  prevButton,
  nextButton,
  emptyRow,
  pageSize = 10,
}) => {
  let currentPage = 1;

  const run = () => {
    if (!rows.length) {
      if (pageInfo) {
        pageInfo.textContent = 'Page 0 of 0';
      }

      if (prevButton) {
        prevButton.disabled = true;
      }

      if (nextButton) {
        nextButton.disabled = true;
      }

      if (emptyRow) {
        emptyRow.classList.add('hidden');
      }

      return;
    }

    const query = normalize(searchInput?.value || '');
    const status = normalize(statusInput?.value || '');

    const filteredRows = rows.filter((row) => {
      const searchText = normalize(row.getAttribute('data-search'));
      const rowStatus = normalize(row.getAttribute('data-status'));
      const searchMatch = query === '' || searchText.includes(query);
      const statusMatch = status === '' || rowStatus === status;
      return searchMatch && statusMatch;
    });

    const totalPages = filteredRows.length > 0 ? Math.ceil(filteredRows.length / pageSize) : 0;

    if (totalPages === 0) {
      currentPage = 1;
    } else if (currentPage > totalPages) {
      currentPage = totalPages;
    }

    const start = (currentPage - 1) * pageSize;
    const end = start + pageSize;

    rows.forEach((row) => row.classList.add('hidden'));
    filteredRows.slice(start, end).forEach((row) => row.classList.remove('hidden'));

    if (emptyRow) {
      emptyRow.classList.toggle('hidden', filteredRows.length > 0);
    }

    if (pageInfo) {
      pageInfo.textContent = totalPages === 0
        ? 'Page 0 of 0'
        : `Page ${currentPage} of ${totalPages}`;
    }

    if (prevButton) {
      prevButton.disabled = totalPages === 0 || currentPage <= 1;
    }

    if (nextButton) {
      nextButton.disabled = totalPages === 0 || currentPage >= totalPages;
    }
  };

  searchInput?.addEventListener('input', () => {
    currentPage = 1;
    run();
  });

  statusInput?.addEventListener('change', () => {
    currentPage = 1;
    run();
  });

  prevButton?.addEventListener('click', () => {
    if (currentPage <= 1) {
      return;
    }

    currentPage -= 1;
    run();
  });

  nextButton?.addEventListener('click', () => {
    currentPage += 1;
    run();
  });

  run();
};

const initEmployeeLearningAndDevelopmentPage = () => {
  runPageStatePass({ pageKey: 'employee-learning-and-development' });
  initTrainingViewTabs();
  initTrainingDetailsModal();
  initTrainingHistoryModal();

  const availableCards = Array.from(document.querySelectorAll('[data-lnd-available-card]'));
  const availableSearch = document.getElementById('lndAvailableSearch');
  const availableStatus = document.getElementById('lndAvailableStatus');
  const availablePageInfo = document.getElementById('lndAvailablePageInfo');
  const availablePrevPage = document.getElementById('lndAvailablePrevPage');
  const availableNextPage = document.getElementById('lndAvailableNextPage');
  const availableFilterEmpty = document.getElementById('lndAvailableFilterEmpty');

  const takenCards = Array.from(document.querySelectorAll('[data-lnd-taken-card]'));
  const takenSearch = document.getElementById('lndTakenSearch');
  const takenStatus = document.getElementById('lndTakenStatus');
  const takenPageInfo = document.getElementById('lndTakenPageInfo');
  const takenPrevPage = document.getElementById('lndTakenPrevPage');
  const takenNextPage = document.getElementById('lndTakenNextPage');
  const takenFilterEmpty = document.getElementById('lndTakenFilterEmpty');

  createPaginatedFilter({
    rows: availableCards,
    searchInput: availableSearch,
    statusInput: availableStatus,
    pageInfo: availablePageInfo,
    prevButton: availablePrevPage,
    nextButton: availableNextPage,
    emptyRow: availableFilterEmpty,
    pageSize: 10,
  });

  createPaginatedFilter({
    rows: takenCards,
    searchInput: takenSearch,
    statusInput: takenStatus,
    pageInfo: takenPageInfo,
    prevButton: takenPrevPage,
    nextButton: takenNextPage,
    emptyRow: takenFilterEmpty,
    pageSize: 10,
  });
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEmployeeLearningAndDevelopmentPage);
} else {
  initEmployeeLearningAndDevelopmentPage();
}

export default initEmployeeLearningAndDevelopmentPage;
