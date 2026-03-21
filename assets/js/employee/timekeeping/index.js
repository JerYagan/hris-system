import { runPageStatePass } from '../../shared/ui/page-state.js';

const toggleModal = (modal, open) => {
  if (!modal) return;
  modal.classList.toggle('hidden', !open);
  modal.setAttribute('aria-hidden', open ? 'false' : 'true');

  const hasOpenModal = Array.from(document.querySelectorAll('[id$="Modal"]')).some(
    (element) => !element.classList.contains('hidden')
  );
  document.body.classList.toggle('overflow-hidden', hasOpenModal);
};

const wireModal = ({ openSelector, closeSelector, modalId, onOpen }) => {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  document.querySelectorAll(openSelector).forEach((button) => {
    button.addEventListener('click', () => {
      if (typeof onOpen === 'function') {
        onOpen(button);
      }
      toggleModal(modal, true);
    });
  });

  document.querySelectorAll(closeSelector).forEach((button) => {
    button.addEventListener('click', () => toggleModal(modal, false));
  });

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      toggleModal(modal, false);
    }
  });
};

const wireAdjustmentModalData = () => {
  const attendanceIdInput = document.getElementById('adjustmentAttendanceLogId');
  const attendanceInfo = document.getElementById('adjustmentAttendanceInfo');

  return (button) => {
    const attendanceId = button.getAttribute('data-attendance-id') || '';
    const attendanceDate = button.getAttribute('data-attendance-date') || '';
    const currentTimeIn = button.getAttribute('data-current-time-in') || '-';
    const currentTimeOut = button.getAttribute('data-current-time-out') || '-';

    if (attendanceIdInput) {
      attendanceIdInput.value = attendanceId;
    }

    if (attendanceInfo) {
      const dateLabel = attendanceDate ? attendanceDate : 'Manual request';
      attendanceInfo.textContent = `${dateLabel} · In: ${currentTimeIn} · Out: ${currentTimeOut}`;
    }
  };
};

const normalizeText = (value) => (value || '').toString().trim().toLowerCase();

const ensureFlatpickr = async () => {
  if (window.flatpickr) {
    return window.flatpickr;
  }

  const waitForFlatpickr = () => new Promise((resolve) => {
    let attempts = 0;
    const maxAttempts = 120;
    const timer = window.setInterval(() => {
      attempts += 1;
      if (window.flatpickr || attempts >= maxAttempts) {
        window.clearInterval(timer);
        resolve(window.flatpickr || null);
      }
    }, 25);
  });

  const existingScript = document.querySelector('script[data-flatpickr="true"]');
  if (existingScript) {
    return waitForFlatpickr();
  }

  if (!document.querySelector('link[data-flatpickr="true"]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
    link.dataset.flatpickr = 'true';
    document.head.appendChild(link);
  }

  await new Promise((resolve) => {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
    script.defer = true;
    script.dataset.flatpickr = 'true';
    script.onload = () => resolve();
    script.onerror = () => resolve();
    document.head.appendChild(script);
  });

  return waitForFlatpickr();
};

const toDateStamp = (value) => {
  const raw = (value || '').toString().trim();
  if (!raw) return null;
  const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!match) return null;
  const stamp = Date.parse(`${match[1]}-${match[2]}-${match[3]}T00:00:00`);
  return Number.isNaN(stamp) ? null : stamp;
};

const initGlobalTableFilters = () => {
  const searchInput = document.getElementById('moduleSearchInput');
  const tableFilter = document.getElementById('moduleTableFilter');
  const statusFilter = document.getElementById('moduleStatusFilter');
  const dateFrom = document.getElementById('moduleDateFrom');
  const dateTo = document.getElementById('moduleDateTo');
  const resetButton = document.getElementById('moduleFilterReset');
  const rows = Array.from(document.querySelectorAll('.js-module-filter-row'));

  if (rows.length === 0 || (!searchInput && !tableFilter && !statusFilter && !dateFrom && !dateTo && !resetButton)) {
    return;
  }

  const applyFilters = ({ notifyInvalid = false } = {}) => {
    const query = normalizeText(searchInput ? searchInput.value : '');
    const table = normalizeText(tableFilter ? tableFilter.value : 'all') || 'all';
    const status = normalizeText(statusFilter ? statusFilter.value : '');
    const fromStamp = toDateStamp(dateFrom ? dateFrom.value : '');
    const toStamp = toDateStamp(dateTo ? dateTo.value : '');

    if (fromStamp !== null && toStamp !== null && fromStamp > toStamp) {
      if (notifyInvalid && window.Swal && typeof window.Swal.fire === 'function') {
        window.Swal.fire({
          icon: 'warning',
          title: 'Invalid date range',
          text: 'From Date cannot be later than To Date.',
          confirmButtonColor: '#0B7A75',
        });
      }
      return;
    }

    rows.forEach((row) => {
      const rowSearch = normalizeText(row.getAttribute('data-search'));
      const rowSource = normalizeText(row.getAttribute('data-source'));
      const rowStatus = normalizeText(row.getAttribute('data-status'));
      const rowDateStamp = toDateStamp(row.getAttribute('data-date'));

      const matchesSearch = query === '' || rowSearch.includes(query);
      const matchesTable = table === 'all' || rowSource === table;
      const matchesStatus = status === '' || rowStatus === status;
      const matchesFrom = fromStamp === null || (rowDateStamp !== null && rowDateStamp >= fromStamp);
      const matchesTo = toStamp === null || (rowDateStamp !== null && rowDateStamp <= toStamp);

      row.classList.toggle('hidden', !(matchesSearch && matchesTable && matchesStatus && matchesFrom && matchesTo));
    });
  };

  if (searchInput) {
    let debounceHandle;
    searchInput.addEventListener('input', () => {
      if (debounceHandle) window.clearTimeout(debounceHandle);
      debounceHandle = window.setTimeout(() => applyFilters(), 120);
    });
  }

  [tableFilter, statusFilter].forEach((element) => {
    if (!element) return;
    element.addEventListener('change', () => applyFilters());
  });

  [dateFrom, dateTo].forEach((element) => {
    if (!element) return;
    element.addEventListener('change', () => applyFilters({ notifyInvalid: true }));
  });

  if (resetButton) {
    resetButton.addEventListener('click', () => {
      if (searchInput) searchInput.value = '';
      if (tableFilter) tableFilter.value = 'all';
      if (statusFilter) statusFilter.value = '';
      if (dateFrom) dateFrom.value = '';
      if (dateTo) dateTo.value = '';
      applyFilters();

      if (window.Swal && typeof window.Swal.fire === 'function') {
        window.Swal.fire({
          icon: 'success',
          title: 'Filters reset',
          text: 'All table records are visible again.',
          timer: 1400,
          showConfirmButton: false,
        });
      }
    });
  }

  applyFilters();
};

const initFilterDatePickers = async () => {
  const flatpickr = await ensureFlatpickr();
  if (!flatpickr) {
    return;
  }

  const dateInputs = Array.from(document.querySelectorAll('main input[type="date"]:not([data-flatpickr="off"])'));
  dateInputs.forEach((input) => {
    if (!(input instanceof HTMLInputElement) || input.dataset.flatpickrInitialized === 'true') {
      return;
    }

    const modalContainer = input.closest('#leaveModal, #specialRequestModal, #adjustmentModal');
    const modalPanel = modalContainer ? modalContainer.querySelector('.bg-white') : null;

    flatpickr(input, {
      dateFormat: 'Y-m-d',
      allowInput: true,
      disableMobile: true,
      minDate: input.getAttribute('min') || null,
      appendTo: modalPanel || undefined,
      positionElement: input,
      onOpen: (_selectedDates, _dateStr, instance) => {
        if (instance && instance.calendarContainer) {
          instance.calendarContainer.style.zIndex = '70';
        }
      },
    });

    input.dataset.flatpickrInitialized = 'true';
  });
};

const initActionConfirmations = () => {
  const forms = Array.from(document.querySelectorAll('form[action="timekeeping.php"]'));
  if (forms.length === 0) {
    return;
  }

  forms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (form.dataset.confirmed === '1') {
        form.dataset.confirmed = '0';
        return;
      }

      const actionInput = form.querySelector('input[name="action"]');
      const actionName = normalizeText(actionInput ? actionInput.value : '');

      const actionMeta = {
        create_time_adjustment_request: {
          title: 'Submit time adjustment request?',
          text: 'Your time adjustment request will be submitted for review.',
          confirm: 'Yes, submit',
        },
        create_official_business_request: {
          title: 'Submit official business request?',
          text: 'Your official business request will be submitted for review.',
          confirm: 'Yes, submit',
        },
        create_cos_schedule_request: {
          title: 'Submit COS flexible schedule request?',
          text: 'Your COS flexible schedule request will be submitted for staff and admin review.',
          confirm: 'Yes, submit',
        },
        create_travel_order_request: {
          title: 'Submit Travel Order request?',
          text: 'Your Travel Order request and attachment will be submitted for review.',
          confirm: 'Yes, submit',
        },
        create_travel_abroad_request: {
          title: 'Submit Travel Abroad request?',
          text: 'Your Travel Abroad request and attachment will be submitted for review.',
          confirm: 'Yes, submit',
        },
        cancel_leave_request: {
          title: 'Cancel leave request?',
          text: 'Only pending leave requests can be cancelled.',
          confirm: 'Yes, cancel',
        },
      };

      const meta = actionMeta[actionName];
      if (!meta) {
        return;
      }

      event.preventDefault();

      const continueSubmit = () => {
        form.dataset.confirmed = '1';
        form.requestSubmit();
      };

      if (!(window.Swal && typeof window.Swal.fire === 'function')) {
        return;
      }

      if (actionName === 'cancel_leave_request') {
        window.Swal.fire({
          icon: 'warning',
          title: meta.title,
          text: meta.text,
          input: 'textarea',
          inputLabel: 'Cancellation Reason',
          inputPlaceholder: 'Enter reason for cancelling this leave request',
          inputAttributes: {
            maxlength: '500',
          },
          inputValidator: (value) => {
            if (!value || !value.toString().trim()) {
              return 'Cancellation reason is required.';
            }
            return null;
          },
          showCancelButton: true,
          confirmButtonText: meta.confirm,
          cancelButtonText: 'Back',
          confirmButtonColor: '#b91c1c',
        }).then((result) => {
          if (!result.isConfirmed) {
            return;
          }

          let reasonInput = form.querySelector('input[name="cancel_reason"]');
          if (!reasonInput) {
            reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'cancel_reason';
            form.appendChild(reasonInput);
          }
          reasonInput.value = (result.value || '').toString().trim();
          continueSubmit();
        });
        return;
      }

      window.Swal.fire({
          icon: actionName === 'cancel_leave_request' ? 'warning' : 'question',
          title: meta.title,
          text: meta.text,
          showCancelButton: true,
          confirmButtonText: meta.confirm,
          cancelButtonText: 'Cancel',
          confirmButtonColor: actionName === 'cancel_leave_request' ? '#b91c1c' : '#0B7A75',
        }).then((result) => {
          if (result.isConfirmed) {
            continueSubmit();
          }
        });
    });
  });
};

const wireSpecialRequestModalData = () => {
  const title = document.getElementById('specialRequestModalTitle');
  const actionInput = document.getElementById('specialRequestAction');
  const typeSelect = document.getElementById('specialRequestType');
  const dateLabel = document.getElementById('specialRequestDateLabel');
  const reasonInput = document.getElementById('specialRequestReason');
  const helpText = document.getElementById('specialRequestHelpText');
  const destinationField = document.getElementById('specialDestinationField');
  const destinationLabel = document.getElementById('specialDestinationLabel');
  const destinationInput = destinationField ? destinationField.querySelector('input[name="destination"]') : null;
  const referenceField = document.getElementById('specialReferenceField');
  const referenceInput = referenceField ? referenceField.querySelector('input[name="reference_number"]') : null;
  const attachmentField = document.getElementById('specialAttachmentField');
  const attachmentLabel = document.getElementById('specialAttachmentLabel');
  const attachmentInput = document.getElementById('specialAttachmentInput');
  const startTimeInput = document.querySelector('#specialRequestModal input[name="start_time"]');
  const endTimeInput = document.querySelector('#specialRequestModal input[name="end_time"]');
  const hoursRequestedInput = document.querySelector('#specialRequestModal input[name="hours_requested"]');
  const timeGrid = document.getElementById('specialRequestTimeGrid');
  const cosWeeklyField = document.getElementById('specialCosWeeklyField');
  const weeklyScheduleInputs = Array.from(document.querySelectorAll('#specialCosWeeklyField input'));

  const configByType = {
    official_business: {
      action: 'create_official_business_request',
      title: 'Official Business Request',
      dateLabel: 'Official Business Date',
      reasonPlaceholder: 'Reason for official business',
      helpText: 'Provide enough context so staff and admin can review the request quickly.',
      destinationLabel: 'Destination / Coverage',
      showDestination: false,
      showReference: false,
      showAttachment: false,
      requireAttachment: false,
      maxEndTime: '',
    },
    cos_schedule: {
      action: 'create_cos_schedule_request',
      title: 'COS Flexible Schedule Request',
      dateLabel: 'Requested Schedule Date',
      reasonPlaceholder: 'Reason for the COS schedule adjustment',
      helpText: 'COS flexible schedules are reviewed separately. Approved COS requests may extend only up to 10:00 PM.',
      destinationLabel: 'Destination / Coverage',
      showDestination: false,
      showReference: false,
      showAttachment: false,
      requireAttachment: false,
      maxEndTime: '22:00',
      useWeeklySchedule: true,
    },
    travel_order: {
      action: 'create_travel_order_request',
      title: 'Travel Order Request',
      dateLabel: 'Travel Date',
      reasonPlaceholder: 'Purpose / justification for the travel order request',
      helpText: 'Attach the supporting travel order or memorandum before submitting.',
      destinationLabel: 'Destination / Coverage',
      showDestination: true,
      showReference: true,
      showAttachment: true,
      requireAttachment: true,
      maxEndTime: '',
      useWeeklySchedule: false,
    },
    travel_abroad: {
      action: 'create_travel_abroad_request',
      title: 'Travel Abroad Request',
      dateLabel: 'Travel Date',
      reasonPlaceholder: 'Purpose / justification for the travel abroad request',
      helpText: 'Attach the approved travel document, itinerary, or memorandum before submitting.',
      destinationLabel: 'Country / Destination',
      showDestination: true,
      showReference: true,
      showAttachment: true,
      requireAttachment: true,
      maxEndTime: '',
      useWeeklySchedule: false,
    },
  };

  return (button) => {
    const requestType = button.getAttribute('data-request-type') || 'official_business';
    const config = configByType[requestType] || configByType.official_business;

    if (title) title.textContent = config.title;
    if (actionInput) actionInput.value = config.action;
    if (typeSelect) typeSelect.value = requestType;
    if (dateLabel) dateLabel.textContent = config.dateLabel;
    if (reasonInput) reasonInput.placeholder = config.reasonPlaceholder;
    if (helpText) helpText.textContent = config.helpText;
    if (destinationLabel) destinationLabel.textContent = config.destinationLabel;

    destinationField?.classList.toggle('hidden', !config.showDestination);
    referenceField?.classList.toggle('hidden', !config.showReference);
    attachmentField?.classList.toggle('hidden', !config.showAttachment);
    timeGrid?.classList.toggle('hidden', !!config.useWeeklySchedule);
    cosWeeklyField?.classList.toggle('hidden', !config.useWeeklySchedule);

    if (destinationInput) {
      destinationInput.required = config.showDestination;
      if (!config.showDestination) destinationInput.value = '';
    }

    if (referenceInput && !config.showReference) {
      referenceInput.value = '';
    }

    if (attachmentInput) {
      attachmentInput.required = config.requireAttachment;
      if (!config.showAttachment) {
        attachmentInput.value = '';
      }
    }

    if (attachmentLabel) {
      attachmentLabel.textContent = config.requireAttachment ? 'Supporting Attachment (required)' : 'Supporting Attachment';
    }

    if (startTimeInput instanceof HTMLInputElement) {
      startTimeInput.required = !config.useWeeklySchedule;
      if (config.useWeeklySchedule) {
        startTimeInput.value = '';
      }
    }

    if (endTimeInput instanceof HTMLInputElement) {
      endTimeInput.required = !config.useWeeklySchedule;
      if (config.useWeeklySchedule) {
        endTimeInput.value = '';
      }
      if (config.maxEndTime) {
        endTimeInput.max = config.maxEndTime;
      } else {
        endTimeInput.removeAttribute('max');
      }
    }

    if (hoursRequestedInput instanceof HTMLInputElement) {
      hoursRequestedInput.required = !config.useWeeklySchedule;
      if (config.useWeeklySchedule) {
        hoursRequestedInput.value = '';
      }
    }

    weeklyScheduleInputs.forEach((input) => {
      if (!(input instanceof HTMLInputElement) || config.useWeeklySchedule) {
        return;
      }

      if (input.type === 'checkbox') {
        input.checked = false;
      } else if (input.name !== 'weekly_schedule_day[]') {
        input.value = '';
      }
    });

    document.querySelectorAll('#specialCosWeeklyField input[name="weekly_schedule_end[]"]').forEach((input) => {
      if (!(input instanceof HTMLInputElement)) {
        return;
      }

      if (config.maxEndTime) {
        input.max = config.maxEndTime;
      } else {
        input.removeAttribute('max');
      }
    });
  };
};

const initLiveLeaveBalance = () => {
  const getSection = () => document.getElementById('leave-balance');
  const initialSection = getSection();
  const refreshUrl = initialSection?.getAttribute('data-refresh-url');

  if (!initialSection || !refreshUrl) {
    return;
  }

  let isRefreshing = false;

  const refreshSection = async () => {
    const currentSection = getSection();
    if (!currentSection || isRefreshing) {
      return;
    }

    isRefreshing = true;

    try {
      const response = await window.fetch(refreshUrl, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });

      if (!response.ok) {
        return;
      }

      const html = await response.text();
      if (!html.trim()) {
        return;
      }

      const parser = new window.DOMParser();
      const documentFragment = parser.parseFromString(html, 'text/html');
      const nextSection = documentFragment.getElementById('leave-balance') || documentFragment.body.firstElementChild;

      if (!nextSection) {
        return;
      }

      currentSection.replaceWith(nextSection);
    } catch (error) {
      console.error(error);
    } finally {
      isRefreshing = false;
    }
  };

  window.setInterval(refreshSection, 60000);

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      void refreshSection();
    }
  });

  window.addEventListener('focus', () => {
    void refreshSection();
  });
};

const initEmployeeTimekeepingPage = async () => {
  runPageStatePass({ pageKey: 'employee-timekeeping' });

  wireModal({
    openSelector: '[data-open-attendance-export]',
    closeSelector: '[data-close-attendance-export]',
    modalId: 'attendanceExportModal',
  });

  wireModal({
    openSelector: '[data-open-special-request]',
    closeSelector: '[data-close-special-request]',
    modalId: 'specialRequestModal',
    onOpen: wireSpecialRequestModalData(),
  });

  wireModal({
    openSelector: '[data-open-adjustment]',
    closeSelector: '[data-close-adjustment]',
    modalId: 'adjustmentModal',
    onOpen: wireAdjustmentModalData(),
  });

  await initFilterDatePickers();
  initGlobalTableFilters();
  initActionConfirmations();
  initLiveLeaveBalance();

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    ['attendanceExportModal', 'specialRequestModal', 'adjustmentModal'].forEach((modalId) => {
      const modal = document.getElementById(modalId);
      if (modal && !modal.classList.contains('hidden')) {
        toggleModal(modal, false);
      }
    });
  });
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    initEmployeeTimekeepingPage().catch(console.error);
  });
} else {
  initEmployeeTimekeepingPage().catch(console.error);
}

export default initEmployeeTimekeepingPage;
