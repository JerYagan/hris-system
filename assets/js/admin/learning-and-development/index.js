import {
  initAdminShellInteractions,
  initModalSystem,
  initStatusChangeConfirmations,
  openModal,
} from '/hris-system/assets/js/shared/admin-core.js';

const normalize = (value) => String(value || '').trim().toLowerCase();

const ensureSweetAlert = async () => {
  if (window.Swal) {
    return window.Swal;
  }

  await new Promise((resolve, reject) => {
    const existing = document.querySelector('script[data-swal="true"]');
    if (existing) {
      existing.addEventListener('load', () => resolve(), { once: true });
      existing.addEventListener('error', () => reject(new Error('Failed to load SweetAlert2.')), { once: true });
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    script.defer = true;
    script.dataset.swal = 'true';
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('Failed to load SweetAlert2.'));
    document.head.appendChild(script);
  });

  return window.Swal;
};

const ensureFlatpickr = async () => {
  if (window.flatpickr) {
    return window.flatpickr;
  }

  await new Promise((resolve, reject) => {
    const existing = document.querySelector('script[data-flatpickr="true"]');
    if (existing) {
      existing.addEventListener('load', () => resolve(), { once: true });
      existing.addEventListener('error', () => reject(new Error('Failed to load Flatpickr.')), { once: true });
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
    script.defer = true;
    script.dataset.flatpickr = 'true';
    script.onload = () => resolve();
    script.onerror = () => reject(new Error('Failed to load Flatpickr.'));
    document.head.appendChild(script);
  });

  return window.flatpickr;
};

const initLearningFlashAlert = async () => {
  const flash = document.getElementById('learningFlashMessage');
  if (!flash) {
    return;
  }

  const state = normalize(flash.getAttribute('data-state'));
  const message = String(flash.getAttribute('data-message') || '').trim();
  if (!message) {
    return;
  }

  try {
    const Swal = await ensureSweetAlert();
    await Swal.fire({
      icon: state === 'success' ? 'success' : 'error',
      title: state === 'success' ? 'Success' : 'Action failed',
      text: message,
      confirmButtonColor: '#0f172a',
    });
  } catch (_error) {
    window.alert(message);
  }
};

const initLearningDatePickers = async () => {
  const dateInput = document.getElementById('learningScheduleDateInput');
  const timeInput = document.getElementById('learningScheduleTimeInput');
  if (!dateInput && !timeInput) {
    return;
  }

  const flatpickr = await ensureFlatpickr();

  if (dateInput) {
    flatpickr(dateInput, {
      dateFormat: 'Y-m-d',
      allowInput: true,
    });
  }

  if (timeInput) {
    flatpickr(timeInput, {
      enableTime: true,
      noCalendar: true,
      dateFormat: 'H:i',
      time_24hr: true,
      allowInput: true,
    });
  }
};

const initLearningParticipantSearch = () => {
  const searchInput = document.getElementById('learningParticipantSearchInput');
  const emptyHint = document.getElementById('learningParticipantEmptyHint');
  const participantRows = Array.from(document.querySelectorAll('[data-learning-participant-row]'));

  if (!(searchInput instanceof HTMLInputElement)) {
    return;
  }

  if (!participantRows.length) {
    if (emptyHint) {
      emptyHint.classList.remove('hidden');
    }
    return;
  }

  const run = () => {
    const query = normalize(searchInput.value);
    let visibleCount = 0;

    participantRows.forEach((row) => {
      const searchText = normalize(row.getAttribute('data-search') || row.textContent || '');
      const matched = query === '' || searchText.includes(query);
      row.classList.toggle('hidden', !matched);
      if (matched) {
        visibleCount += 1;
      }
    });

    if (emptyHint) {
      emptyHint.classList.toggle('hidden', visibleCount > 0);
    }
  };

  searchInput.addEventListener('input', run);
  run();
};

const initPaginatedFilterTable = ({
  tableId,
  rowSelector,
  searchInputId,
  statusFilterId,
  searchAttr,
  statusAttr,
  pageSizeSelectId,
  pageInfoId,
  prevButtonId,
  nextButtonId,
  defaultPageSize = 10,
}) => {
  const table = document.getElementById(tableId);
  if (!table) {
    return;
  }

  const rows = Array.from(table.querySelectorAll(`tbody ${rowSelector}`));
  const searchInput = searchInputId ? document.getElementById(searchInputId) : null;
  const statusInput = statusFilterId ? document.getElementById(statusFilterId) : null;
  const pageSizeSelect = pageSizeSelectId ? document.getElementById(pageSizeSelectId) : null;
  const pageInfo = pageInfoId ? document.getElementById(pageInfoId) : null;
  const prevButton = prevButtonId ? document.getElementById(prevButtonId) : null;
  const nextButton = nextButtonId ? document.getElementById(nextButtonId) : null;

  let currentPage = 1;

  const getPageSize = () => {
    const value = Number(pageSizeSelect?.value || defaultPageSize);
    return Number.isFinite(value) && value > 0 ? value : defaultPageSize;
  };

  const run = () => {
    const query = normalize(searchInput?.value || '');
    const selectedStatus = normalize(statusInput?.value || '');

    const filteredRows = rows.filter((row) => {
      const rowSearchText = normalize(row.getAttribute(searchAttr));
      const rowStatus = normalize(row.getAttribute(statusAttr));
      const searchMatch = query === '' || rowSearchText.includes(query);
      const statusMatch = selectedStatus === '' || rowStatus === selectedStatus;
      return searchMatch && statusMatch;
    });

    const pageSize = getPageSize();
    const totalPages = filteredRows.length > 0 ? Math.ceil(filteredRows.length / pageSize) : 0;

    if (totalPages === 0) {
      currentPage = 1;
    } else if (currentPage > totalPages) {
      currentPage = totalPages;
    }

    const start = (currentPage - 1) * pageSize;
    const end = start + pageSize;

    rows.forEach((row) => {
      row.style.display = 'none';
    });

    filteredRows.slice(start, end).forEach((row) => {
      row.style.display = '';
    });

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

  pageSizeSelect?.addEventListener('change', () => {
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

const initLearningAttendanceUpdateModal = () => {
  const enrollmentIdInput = document.getElementById('learningEnrollmentId');
  const trainingInput = document.getElementById('learningAttendanceTraining');
  const employeeInput = document.getElementById('learningAttendanceEmployee');
  const currentStatusInput = document.getElementById('learningAttendanceCurrentStatus');
  const newStatusInput = document.getElementById('learningAttendanceNewStatus');

  const statusLabel = (status) => {
    const normalized = normalize(status);
    if (normalized === 'completed') return 'Present';
    if (normalized === 'enrolled') return 'Enrolled';
    if (normalized === 'failed') return 'Absent';
    if (normalized === 'dropped') return 'Dropped';
    return normalized ? normalized.charAt(0).toUpperCase() + normalized.slice(1) : '-';
  };

  document.querySelectorAll('[data-learning-attendance-update]').forEach((button) => {
    button.addEventListener('click', () => {
      const enrollmentId = button.getAttribute('data-enrollment-id') || '';
      const trainingTitle = button.getAttribute('data-training-title') || '';
      const employeeName = button.getAttribute('data-employee-name') || '';
      const currentStatus = button.getAttribute('data-current-status') || 'enrolled';

      if (enrollmentIdInput) enrollmentIdInput.value = enrollmentId;
      if (trainingInput) trainingInput.value = trainingTitle;
      if (employeeInput) employeeInput.value = employeeName;
      if (currentStatusInput) currentStatusInput.value = statusLabel(currentStatus);
      if (newStatusInput) newStatusInput.value = normalize(currentStatus) || 'enrolled';

      openModal('learningAttendanceModal');
    });
  });
};

const initLearningTrainingDetailsModal = () => {
  const detailsProgramCode = document.getElementById('learningDetailsProgramCode');
  const detailsTitle = document.getElementById('learningDetailsTitle');
  const detailsType = document.getElementById('learningDetailsType');
  const detailsCategory = document.getElementById('learningDetailsCategory');
  const detailsDate = document.getElementById('learningDetailsDate');
  const detailsTime = document.getElementById('learningDetailsTime');
  const detailsProvider = document.getElementById('learningDetailsProvider');
  const detailsVenue = document.getElementById('learningDetailsVenue');
  const detailsMode = document.getElementById('learningDetailsMode');
  const detailsStatusPill = document.getElementById('learningDetailsStatusPill');
  const detailsParticipantsCount = document.getElementById('learningDetailsParticipantsCount');
  const detailsParticipantsList = document.getElementById('learningDetailsParticipantsList');

  document.querySelectorAll('[data-learning-training-view]').forEach((button) => {
    button.addEventListener('click', () => {
      const statusLabel = button.getAttribute('data-status') || '-';

      if (detailsProgramCode) detailsProgramCode.textContent = button.getAttribute('data-program-code') || '-';
      if (detailsTitle) detailsTitle.textContent = button.getAttribute('data-title') || '-';
      if (detailsType) detailsType.textContent = button.getAttribute('data-training-type') || '-';
      if (detailsCategory) detailsCategory.textContent = button.getAttribute('data-training-category') || '-';
      if (detailsDate) detailsDate.textContent = button.getAttribute('data-schedule-date') || '-';
      if (detailsTime) detailsTime.textContent = button.getAttribute('data-schedule-time') || '-';
      if (detailsProvider) detailsProvider.textContent = button.getAttribute('data-provider') || '-';
      if (detailsVenue) detailsVenue.textContent = button.getAttribute('data-venue') || '-';
      if (detailsMode) detailsMode.textContent = button.getAttribute('data-mode') || '-';
      if (detailsParticipantsCount) detailsParticipantsCount.textContent = button.getAttribute('data-participants-count') || '0';

      if (detailsStatusPill) {
        const normalizedStatus = normalize(statusLabel);
        detailsStatusPill.textContent = statusLabel;
        detailsStatusPill.className = 'px-2.5 py-1 text-xs rounded-full bg-slate-100 text-slate-700';

        if (normalizedStatus === 'completed') {
          detailsStatusPill.className = 'px-2.5 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800';
        } else if (normalizedStatus === 'in progress' || normalizedStatus === 'planned' || normalizedStatus === 'open' || normalizedStatus === 'ongoing') {
          detailsStatusPill.className = 'px-2.5 py-1 text-xs rounded-full bg-blue-100 text-blue-800';
        } else if (normalizedStatus === 'needs review' || normalizedStatus === 'cancelled' || normalizedStatus === 'failed') {
          detailsStatusPill.className = 'px-2.5 py-1 text-xs rounded-full bg-amber-100 text-amber-800';
        } else if (normalizedStatus === 'dropped') {
          detailsStatusPill.className = 'px-2.5 py-1 text-xs rounded-full bg-rose-100 text-rose-800';
        }
      }

      if (detailsParticipantsList) {
        const participantsRaw = button.getAttribute('data-participants-list') || '';
        const participants = participantsRaw
          .split(',')
          .map((name) => name.trim())
          .filter((name) => name !== '' && name !== 'No participants enrolled yet.');

        detailsParticipantsList.innerHTML = '';

        if (participants.length === 0) {
          const emptyChip = document.createElement('span');
          emptyChip.className = 'px-2 py-1 text-xs rounded bg-slate-100 text-slate-700';
          emptyChip.textContent = 'No participants enrolled yet.';
          detailsParticipantsList.appendChild(emptyChip);
        } else {
          participants.forEach((participant) => {
            const chip = document.createElement('span');
            chip.className = 'px-2 py-1 text-xs rounded bg-slate-100 text-slate-700';
            chip.textContent = participant;
            detailsParticipantsList.appendChild(chip);
          });
        }
      }

      openModal('learningTrainingDetailsModal');
    });
  });
};

const initLearningAndDevelopmentTables = () => {
  initPaginatedFilterTable({
    tableId: 'learningHistoryTable',
    rowSelector: 'tr[data-learning-history-search]',
    searchInputId: 'learningHistorySearch',
    statusFilterId: 'learningHistoryActionFilter',
    searchAttr: 'data-learning-history-search',
    statusAttr: 'data-learning-history-action',
    pageInfoId: 'learningHistoryPageInfo',
    prevButtonId: 'learningHistoryPrevPage',
    nextButtonId: 'learningHistoryNextPage',
    defaultPageSize: 10,
  });

  initPaginatedFilterTable({
    tableId: 'learningTrainingRecordsTable',
    rowSelector: 'tr[data-learning-record-search]',
    searchInputId: 'learningRecordsSearch',
    statusFilterId: 'learningRecordsStatusFilter',
    searchAttr: 'data-learning-record-search',
    statusAttr: 'data-learning-record-status',
    pageSizeSelectId: 'learningRecordsPageSize',
    pageInfoId: 'learningRecordsPageInfo',
    prevButtonId: 'learningRecordsPrevPage',
    nextButtonId: 'learningRecordsNextPage',
    defaultPageSize: 10,
  });

  initPaginatedFilterTable({
    tableId: 'learningScheduleTable',
    rowSelector: 'tr[data-learning-schedule-search]',
    searchInputId: 'learningScheduleSearch',
    statusFilterId: 'learningScheduleStatusFilter',
    searchAttr: 'data-learning-schedule-search',
    statusAttr: 'data-learning-schedule-status',
    pageSizeSelectId: 'learningSchedulePageSize',
    pageInfoId: 'learningSchedulePageInfo',
    prevButtonId: 'learningSchedulePrevPage',
    nextButtonId: 'learningScheduleNextPage',
    defaultPageSize: 10,
  });

  initPaginatedFilterTable({
    tableId: 'learningAttendanceTable',
    rowSelector: 'tr[data-learning-attendance-search]',
    searchInputId: 'learningAttendanceSearch',
    statusFilterId: 'learningAttendanceStatusFilter',
    searchAttr: 'data-learning-attendance-search',
    statusAttr: 'data-learning-attendance-status',
    pageSizeSelectId: 'learningAttendancePageSize',
    pageInfoId: 'learningAttendancePageInfo',
    prevButtonId: 'learningAttendancePrevPage',
    nextButtonId: 'learningAttendanceNextPage',
    defaultPageSize: 10,
  });
};

export default function initAdminLearningAndDevelopmentPage() {
  initAdminShellInteractions();
  initModalSystem();
  initStatusChangeConfirmations();
  initLearningAndDevelopmentTables();
  initLearningAttendanceUpdateModal();
  initLearningTrainingDetailsModal();
  initLearningParticipantSearch();
  initLearningDatePickers().catch(console.error);
  initLearningFlashAlert().catch(console.error);
}