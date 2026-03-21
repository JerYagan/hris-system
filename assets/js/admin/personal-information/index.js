import {
  openModal,
  closeModal,
  initAdminShellInteractions,
  initModalSystem,
  initStatusChangeConfirmations,
} from '/hris-system/assets/js/shared/admin-core.js';

const PAGE_SIZE = 10;

const normalize = (value) => (value || '').toString().trim().toLowerCase();

const parseJson = (value, fallback = []) => {
  if (!value) {
    return fallback;
  }

  try {
    const parsed = JSON.parse(value);
    return parsed ?? fallback;
  } catch (_error) {
    return fallback;
  }
};

const escapeHtml = (value) => (value || '').toString()
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#39;');

const fetchHtml = async (url) => {
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

  const html = await response.text();
  if (!response.ok) {
    throw new Error(`Employee region request failed with status ${response.status}`);
  }

  return html;
};

const showConfirm = async (title, text) => {
  if (window.Swal && typeof window.Swal.fire === 'function') {
    const result = await window.Swal.fire({
      title,
      text,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Continue',
      cancelButtonText: 'Cancel',
      reverseButtons: true,
    });
    return Boolean(result?.isConfirmed);
  }

  return window.confirm(text);
};

const setupPaginatedRows = ({
  rows,
  emptyRow,
  info,
  prevButton,
  nextButton,
  filter,
}) => {
  let currentPage = 1;
  let filteredRows = rows;

  const updateUi = () => {
    const totalPages = Math.max(1, Math.ceil(filteredRows.length / PAGE_SIZE));
    if (currentPage > totalPages) {
      currentPage = totalPages;
    }

    if (info) {
      info.textContent = `Page ${currentPage} of ${totalPages}`;
    }

    if (prevButton) {
      const disabled = currentPage <= 1 || filteredRows.length === 0;
      prevButton.disabled = disabled;
      prevButton.classList.toggle('opacity-60', disabled);
      prevButton.classList.toggle('cursor-not-allowed', disabled);
    }

    if (nextButton) {
      const disabled = currentPage >= totalPages || filteredRows.length === 0;
      nextButton.disabled = disabled;
      nextButton.classList.toggle('opacity-60', disabled);
      nextButton.classList.toggle('cursor-not-allowed', disabled);
    }
  };

  const render = () => {
    rows.forEach((row) => row.classList.add('hidden'));

    const start = (currentPage - 1) * PAGE_SIZE;
    filteredRows.slice(start, start + PAGE_SIZE).forEach((row) => row.classList.remove('hidden'));

    if (emptyRow) {
      emptyRow.classList.toggle('hidden', filteredRows.length > 0);
    }

    updateUi();
  };

  const apply = () => {
    filteredRows = rows.filter((row) => filter(row));
    currentPage = 1;
    render();
  };

  prevButton?.addEventListener('click', () => {
    if (currentPage <= 1) {
      return;
    }
    currentPage -= 1;
    render();
  });

  nextButton?.addEventListener('click', () => {
    const totalPages = Math.max(1, Math.ceil(filteredRows.length / PAGE_SIZE));
    if (currentPage >= totalPages) {
      return;
    }
    currentPage += 1;
    render();
  });

  apply();
  return { apply };
};

const initPendingReviewSection = () => {
  const searchInput = document.getElementById('pendingProfileSearchInput');
  const statusFilter = document.getElementById('pendingProfileStatusFilter');
  const dateFilter = document.getElementById('pendingProfileDateFilter');
  const rows = Array.from(document.querySelectorAll('#personalInfoPendingReviewTable tbody tr[data-pending-review-row]'));
  const emptyRow = document.getElementById('pendingProfileFilterEmpty');
  const info = document.getElementById('pendingProfilePaginationInfo');
  const prevButton = document.getElementById('pendingProfilePrevPage');
  const nextButton = document.getElementById('pendingProfileNextPage');

  const paginator = setupPaginatedRows({
    rows,
    emptyRow,
    info,
    prevButton,
    nextButton,
    filter: (row) => {
      const query = normalize(searchInput?.value);
      const status = normalize(statusFilter?.value);
      const submittedDate = (dateFilter?.value || '').trim();
      const rowSearch = normalize(row.getAttribute('data-review-search'));
      const rowStatus = normalize(row.getAttribute('data-review-status'));
      const rowDate = (row.getAttribute('data-review-date') || '').trim();

      return (query === '' || rowSearch.includes(query))
        && (status === '' || rowStatus === status)
        && (submittedDate === '' || rowDate === submittedDate);
    },
  });

  const triggerApply = () => paginator.apply();
  searchInput?.addEventListener('input', triggerApply);
  statusFilter?.addEventListener('change', triggerApply);
  dateFilter?.addEventListener('change', triggerApply);

  const modalId = 'personalInfoRecommendationReviewModal';
  const employeeField = document.getElementById('recommendationReviewEmployee');
  const submittedByField = document.getElementById('recommendationReviewSubmittedBy');
  const submittedAtField = document.getElementById('recommendationReviewSubmittedAt');
  const dueAtField = document.getElementById('recommendationReviewDueAt');
  const reminderAtField = document.getElementById('recommendationReviewReminderAt');
  const summaryField = document.getElementById('recommendationReviewSummary');
  const notificationSummaryField = document.getElementById('recommendationReviewNotificationSummary');
  const detailsBox = document.getElementById('recommendationReviewDetails');
  const decisionSelect = document.getElementById('recommendationReviewDecisionSelect');
  const remarksInput = document.getElementById('recommendationReviewRemarksInput');
  const submitButton = document.getElementById('recommendationReviewSubmit');
  const form = document.getElementById('personalInfoRecommendationReviewForm');
  const formLogId = document.getElementById('recommendationReviewLogId');
  const formPersonId = document.getElementById('recommendationReviewPersonId');
  const formDecision = document.getElementById('recommendationReviewDecision');
  const formRemarks = document.getElementById('recommendationReviewRemarks');

  document.querySelectorAll('[data-pending-review-open]').forEach((button) => {
    button.addEventListener('click', () => {
      const details = parseJson(button.getAttribute('data-details'), []);
      if (employeeField) {
        employeeField.value = button.getAttribute('data-employee-name') || '';
      }
      if (submittedByField) {
        submittedByField.value = button.getAttribute('data-submitted-by') || '';
      }
      if (submittedAtField) {
        submittedAtField.value = button.getAttribute('data-submitted-at') || '';
      }
      if (dueAtField) {
        dueAtField.value = button.getAttribute('data-due-at') || '';
      }
      if (reminderAtField) {
        reminderAtField.value = button.getAttribute('data-reminder-at') || '';
      }
      if (summaryField) {
        summaryField.value = button.getAttribute('data-summary') || '';
      }
      if (notificationSummaryField) {
        notificationSummaryField.value = button.getAttribute('data-notification-summary') || '';
      }
      if (detailsBox) {
        detailsBox.innerHTML = Array.isArray(details) && details.length > 0
          ? details.map((detail) => `<div class="py-1"><span class="font-medium text-slate-700">${escapeHtml(detail.label || 'Change')}</span><div class="text-slate-600 mt-1">${escapeHtml(detail.value || '-')}</div></div>`).join('')
          : '<p class="text-slate-500">No field-level details were provided.</p>';
      }
      if (decisionSelect) {
        decisionSelect.value = 'approve';
      }
      if (remarksInput) {
        remarksInput.value = '';
      }
      if (formLogId) {
        formLogId.value = button.getAttribute('data-recommendation-log-id') || '';
      }
      if (formPersonId) {
        formPersonId.value = button.getAttribute('data-person-id') || '';
      }
      openModal(modalId);
    });
  });

  submitButton?.addEventListener('click', () => {
    const finalizeSubmit = async () => {
      if (!(form instanceof HTMLFormElement)) {
        return;
      }

      const decision = (decisionSelect instanceof HTMLSelectElement ? decisionSelect.value : 'approve').trim();
      const remarks = (remarksInput instanceof HTMLTextAreaElement ? remarksInput.value : '').trim();
      if (decision === 'reject' && remarks === '') {
        if (window.Swal && typeof window.Swal.fire === 'function') {
          window.Swal.fire({ icon: 'error', text: 'Remarks are required when rejecting a request.' });
        }
        return;
      }

      const employeeName = (employeeField instanceof HTMLInputElement ? employeeField.value : '').trim() || 'this employee';
      const confirmed = await showConfirm(
        decision === 'approve' ? 'Approve and apply profile changes?' : 'Reject this profile change request?',
        decision === 'approve'
          ? `Approved profile changes for ${employeeName} will be applied to the live employee record.`
          : `This will reject the pending profile change request for ${employeeName}.`
      );

      if (!confirmed) {
        return;
      }

      if (formDecision) {
        formDecision.value = decision;
      }
      if (formRemarks) {
        formRemarks.value = remarks;
      }
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
        return;
      }
      form.submit();
    };

    finalizeSubmit().catch(console.error);
  });
};

const initQuickEditList = (regionRoot) => {
  const searchInput = regionRoot.querySelector('#quickEditProfileSearchInput');
  const quickEditList = regionRoot.querySelector('#quickEditProfileList');
  const options = Array.from(regionRoot.querySelectorAll('[data-quick-edit-profile-option]'));
  const emptyState = regionRoot.querySelector('#quickEditProfileEmpty');
  const addButton = regionRoot.querySelector('[data-person-profile-add]');
  const profileSource = (quickEditList?.getAttribute('data-profile-source') || 'personal-information').trim();

  const apply = () => {
    const query = normalize(searchInput?.value);
    let visibleCount = 0;
    options.forEach((option) => {
      const matches = query === '' || normalize(option.getAttribute('data-search-text')).includes(query);
      option.classList.toggle('hidden', !matches);
      if (matches) {
        visibleCount += 1;
      }
    });

    if (emptyState) {
      emptyState.classList.toggle('hidden', visibleCount > 0);
    }
  };

  searchInput?.addEventListener('input', apply);
  apply();

  options.forEach((option) => {
    option.addEventListener('click', () => {
      const personId = (option.getAttribute('data-person-id') || '').trim();
      if (personId === '') {
        return;
      }
      window.location.href = `employee-profile.php?person_id=${encodeURIComponent(personId)}&source=${encodeURIComponent(profileSource)}`;
    });
  });

  addButton?.addEventListener('click', () => {
    const form = regionRoot.querySelector('#personalInfoProfileForm');
    const actionField = regionRoot.querySelector('#profileAction');
    const personIdField = regionRoot.querySelector('#profilePersonId');
    const employeeLabel = regionRoot.querySelector('#personalInfoProfileEmployeeLabel');

    if (form instanceof HTMLFormElement) {
      form.reset();
    }
    if (actionField instanceof HTMLInputElement) {
      actionField.value = 'add';
    }
    if (personIdField instanceof HTMLInputElement) {
      personIdField.value = '';
    }
    if (employeeLabel instanceof HTMLElement) {
      employeeLabel.textContent = 'New employee profile';
    }
    openModal('personalInfoProfileModal');
  });
};

const initEmployeeTable = (regionRoot) => {
  const searchInput = regionRoot.querySelector('#personalInfoRecordsSearchInput');
  const departmentFilter = regionRoot.querySelector('#personalInfoRecordsDepartmentFilter');
  const statusFilter = regionRoot.querySelector('#personalInfoRecordsStatusFilter');
  const resetButton = regionRoot.querySelector('#personalInfoResetFilters');
  const rows = Array.from(regionRoot.querySelectorAll('#personalInfoEmployeesTable tbody tr[data-profile-search]'));
  const emptyRow = regionRoot.querySelector('#personalInfoFilterEmpty');
  const info = regionRoot.querySelector('#personalInfoPaginationInfo');
  const prevButton = regionRoot.querySelector('#personalInfoPrevPage');
  const nextButton = regionRoot.querySelector('#personalInfoNextPage');

  const paginator = setupPaginatedRows({
    rows,
    emptyRow,
    info,
    prevButton,
    nextButton,
    filter: (row) => {
      const query = normalize(searchInput?.value);
      const department = normalize(departmentFilter?.value);
      const status = normalize(statusFilter?.value);
      const rowSearch = normalize(row.getAttribute('data-profile-search'));
      const rowDepartment = normalize(row.getAttribute('data-profile-department'));
      const rowStatus = normalize(row.getAttribute('data-profile-status'));

      return (query === '' || rowSearch.includes(query))
        && (department === '' || rowDepartment === department)
        && (status === '' || rowStatus === status);
    },
  });

  const triggerApply = () => paginator.apply();
  searchInput?.addEventListener('input', triggerApply);
  departmentFilter?.addEventListener('change', triggerApply);
  statusFilter?.addEventListener('change', triggerApply);
  resetButton?.addEventListener('click', () => {
    if (searchInput instanceof HTMLInputElement) {
      searchInput.value = '';
    }
    if (departmentFilter instanceof HTMLSelectElement) {
      departmentFilter.value = '';
    }
    if (statusFilter instanceof HTMLSelectElement) {
      statusFilter.value = '';
    }
    paginator.apply();
  });
};

const initProfileArchive = (regionRoot) => {
  const archiveForm = regionRoot.querySelector('#personalInfoArchiveForm');
  const personIdField = regionRoot.querySelector('#personalInfoArchivePersonId');
  const employeeNameField = regionRoot.querySelector('#personalInfoArchiveEmployeeName');

  regionRoot.querySelectorAll('[data-person-profile-archive]').forEach((button) => {
    button.addEventListener('click', async () => {
      const employeeName = (button.getAttribute('data-employee-name') || 'employee').trim();
      const confirmed = await showConfirm('Archive employee profile?', `Archive ${employeeName}?`);
      if (!confirmed || !(archiveForm instanceof HTMLFormElement)) {
        return;
      }

      if (personIdField instanceof HTMLInputElement) {
        personIdField.value = button.getAttribute('data-person-id') || '';
      }
      if (employeeNameField instanceof HTMLInputElement) {
        employeeNameField.value = employeeName;
      }
      archiveForm.submit();
    });
  });
};

const initMergeModal = (regionRoot) => {
  const form = regionRoot.querySelector('#personalInfoMergeForm');
  const sourceIdField = regionRoot.querySelector('#personalInfoMergeSourcePersonId');
  const sourceNameField = regionRoot.querySelector('#personalInfoMergeSourceEmployeeName');
  const sourceLabel = regionRoot.querySelector('#personalInfoMergeSourceLabel');
  const targetSelect = regionRoot.querySelector('#personalInfoMergeTargetSelect');
  const resolutionSelect = regionRoot.querySelector('#personalInfoMergeResolutionSelect');
  const notesInput = regionRoot.querySelector('#personalInfoMergeNotesInput');
  const targetIdField = regionRoot.querySelector('#personalInfoMergeTargetPersonId');
  const resolutionField = regionRoot.querySelector('#personalInfoMergeResolutionMode');
  const notesField = regionRoot.querySelector('#personalInfoMergeResolutionNotes');
  const submitButton = regionRoot.querySelector('#personalInfoMergeSubmit');

  regionRoot.querySelectorAll('[data-person-merge-open]').forEach((button) => {
    button.addEventListener('click', () => {
      const personId = button.getAttribute('data-person-id') || '';
      const employeeName = button.getAttribute('data-employee-name') || 'Selected employee';
      if (sourceIdField instanceof HTMLInputElement) {
        sourceIdField.value = personId;
      }
      if (sourceNameField instanceof HTMLInputElement) {
        sourceNameField.value = employeeName;
      }
      if (sourceLabel instanceof HTMLInputElement) {
        sourceLabel.value = employeeName;
      }
      if (targetSelect instanceof HTMLSelectElement) {
        targetSelect.value = '';
      }
      if (resolutionSelect instanceof HTMLSelectElement) {
        resolutionSelect.value = 'merge';
      }
      if (notesInput instanceof HTMLTextAreaElement) {
        notesInput.value = '';
      }
      openModal('personalInfoMergeModal');
    });
  });

  submitButton?.addEventListener('click', async () => {
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    const sourceId = sourceIdField instanceof HTMLInputElement ? sourceIdField.value.trim() : '';
    const targetId = targetSelect instanceof HTMLSelectElement ? targetSelect.value.trim() : '';
    const resolution = resolutionSelect instanceof HTMLSelectElement ? resolutionSelect.value.trim() : 'merge';
    const notes = notesInput instanceof HTMLTextAreaElement ? notesInput.value.trim() : '';

    if (resolution === 'merge' && (targetId === '' || targetId === sourceId)) {
      if (window.Swal && typeof window.Swal.fire === 'function') {
        window.Swal.fire({ icon: 'error', text: 'Select a different employee profile to keep.' });
      }
      return;
    }

    const confirmed = await showConfirm('Submit duplicate resolution?', 'This will apply the selected duplicate-profile resolution.');
    if (!confirmed) {
      return;
    }

    if (targetIdField instanceof HTMLInputElement) {
      targetIdField.value = targetId;
    }
    if (resolutionField instanceof HTMLInputElement) {
      resolutionField.value = resolution;
    }
    if (notesField instanceof HTMLInputElement) {
      notesField.value = notes;
    }
    form.submit();
  });
};

const initAssignmentAndStatusModals = (regionRoot) => {
  const assignmentModal = regionRoot.querySelector('#personalInfoAssignmentModal');
  const assignmentPersonId = regionRoot.querySelector('#personalInfoAssignmentPersonId');
  const assignmentEmployee = regionRoot.querySelector('#personalInfoAssignmentEmployeeDisplay');
  const assignmentOffice = assignmentModal?.querySelector('select[name="office_id"]');
  const assignmentPosition = assignmentModal?.querySelector('select[name="position_id"]');

  regionRoot.querySelectorAll('[data-person-assignment-open]').forEach((button) => {
    button.addEventListener('click', () => {
      if (assignmentPersonId instanceof HTMLInputElement) {
        assignmentPersonId.value = button.getAttribute('data-person-id') || '';
      }
      if (assignmentEmployee instanceof HTMLInputElement) {
        assignmentEmployee.value = button.getAttribute('data-employee-name') || '';
      }
      if (assignmentOffice instanceof HTMLSelectElement) {
        assignmentOffice.value = button.getAttribute('data-current-office-id') || '';
      }
      if (assignmentPosition instanceof HTMLSelectElement) {
        assignmentPosition.value = button.getAttribute('data-current-position-id') || '';
      }
      openModal('personalInfoAssignmentModal');
    });
  });

  const statusPersonId = regionRoot.querySelector('#personalInfoStatusPersonId');
  const statusEmployee = regionRoot.querySelector('#personalInfoStatusEmployeeDisplay');
  const statusCurrent = regionRoot.querySelector('#personalInfoStatusCurrentDisplay');
  const statusNew = regionRoot.querySelector('#personalInfoStatusNewStatus');
  const statusNotes = regionRoot.querySelector('#personalInfoStatusSpecification');

  regionRoot.querySelectorAll('[data-person-status-open]').forEach((button) => {
    button.addEventListener('click', () => {
      if (statusPersonId instanceof HTMLInputElement) {
        statusPersonId.value = button.getAttribute('data-person-id') || '';
      }
      if (statusEmployee instanceof HTMLInputElement) {
        statusEmployee.value = button.getAttribute('data-employee-name') || '';
      }
      if (statusCurrent instanceof HTMLInputElement) {
        statusCurrent.value = button.getAttribute('data-current-status') || '';
      }
      if (statusNew instanceof HTMLSelectElement) {
        const currentStatus = normalize(button.getAttribute('data-current-status'));
        statusNew.value = currentStatus === 'active' ? 'inactive' : 'active';
      }
      if (statusNotes instanceof HTMLTextAreaElement) {
        statusNotes.value = '';
      }
      openModal('personalInfoStatusModal');
    });
  });
};

const initProfileModal = (regionRoot) => {
  const modalId = 'personalInfoProfileModal';
  const actionField = regionRoot.querySelector('#profileAction');
  const personIdField = regionRoot.querySelector('#profilePersonId');
  const employeeLabel = regionRoot.querySelector('#personalInfoProfileEmployeeLabel');

  regionRoot.querySelectorAll('[data-person-profile-open]').forEach((button) => {
    button.addEventListener('click', () => {
      if (actionField instanceof HTMLInputElement) {
        actionField.value = 'edit';
      }
      if (personIdField instanceof HTMLInputElement) {
        personIdField.value = button.getAttribute('data-person-id') || '';
      }
      if (employeeLabel instanceof HTMLElement) {
        employeeLabel.textContent = button.getAttribute('data-employee-name') || 'Selected employee';
      }

      const fieldMap = {
        profileFirstName: 'data-first-name',
        profileMiddleName: 'data-middle-name',
        profileSurname: 'data-surname',
        profileNameExtension: 'data-name-extension',
        profileDateOfBirth: 'data-date-of-birth',
        profilePlaceOfBirth: 'data-place-of-birth',
        profileSexAtBirth: 'data-sex-at-birth',
        profileCivilStatus: 'data-civil-status',
        profileHeightM: 'data-height-m',
        profileWeightKg: 'data-weight-kg',
        profileBloodType: 'data-blood-type',
        profileCitizenship: 'data-citizenship',
        profileDualCitizenshipCountry: 'data-dual-citizenship-country',
        profileResidentialHouseNo: 'data-residential-house-no',
        profileResidentialStreet: 'data-residential-street',
        profileResidentialSubdivision: 'data-residential-subdivision',
        profileResidentialBarangay: 'data-residential-barangay',
        profileResidentialCity: 'data-residential-city-municipality',
        profileResidentialProvince: 'data-residential-province',
        profileResidentialZipCode: 'data-residential-zip-code',
        profilePermanentHouseNo: 'data-permanent-house-no',
        profilePermanentStreet: 'data-permanent-street',
        profilePermanentSubdivision: 'data-permanent-subdivision',
        profilePermanentBarangay: 'data-permanent-barangay',
        profilePermanentCity: 'data-permanent-city-municipality',
        profilePermanentProvince: 'data-permanent-province',
        profilePermanentZipCode: 'data-permanent-zip-code',
        profileUmidNo: 'data-umid-no',
        profilePagibigNo: 'data-pagibig-no',
        profilePhilhealthNo: 'data-philhealth-no',
        profilePsnNo: 'data-psn-no',
        profileTinNo: 'data-tin-no',
        profileAgencyEmployeeNo: 'data-agency-employee-no',
        profileTelephoneNo: 'data-telephone-no',
        profileEmail: 'data-email',
        profileMobile: 'data-mobile',
      };

      Object.entries(fieldMap).forEach(([fieldId, attrName]) => {
        const element = regionRoot.querySelector(`#${fieldId}`);
        if (element instanceof HTMLInputElement || element instanceof HTMLSelectElement || element instanceof HTMLTextAreaElement) {
          element.value = button.getAttribute(attrName) || '';
        }
      });

      openModal(modalId);
    });
  });
};

const initActionMenuDelegation = (regionRoot) => {
  regionRoot.querySelectorAll('[data-action-menu-item]').forEach((menuItem) => {
    menuItem.addEventListener('click', () => {
      const target = menuItem.getAttribute('data-action-target') || '';
      const scope = menuItem.closest('[data-person-action-scope]');
      if (!scope || target === '') {
        return;
      }
      const trigger = scope.querySelector(`[data-action-trigger="${target}"]`);
      if (trigger instanceof HTMLButtonElement) {
        trigger.click();
      }
    });
  });
};

const initModalCloseDelegation = () => {
  if (document.body?.dataset?.adminPersonalInfoModalCloseBound === 'true') {
    return;
  }

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    const closeTrigger = target.closest('[data-modal-close]');
    if (!closeTrigger) {
      return;
    }

    const modalId = closeTrigger.getAttribute('data-modal-close') || '';
    if (modalId !== '') {
      closeModal(modalId);
    }
  });

  document.body.dataset.adminPersonalInfoModalCloseBound = 'true';
};

const bindEmployeeRegion = (regionRoot) => {
  initActionMenuDelegation(regionRoot);
  initQuickEditList(regionRoot);
  initEmployeeTable(regionRoot);
  initProfileArchive(regionRoot);
  initMergeModal(regionRoot);
  initAssignmentAndStatusModals(regionRoot);
  initProfileModal(regionRoot);
};

const initDeferredEmployeeRegion = () => {
  const region = document.getElementById('adminPersonalInfoEmployeeRegion');
  if (!region) {
    return;
  }

  const url = region.getAttribute('data-personal-info-employee-region-url') || '';
  const skeleton = document.getElementById('adminPersonalInfoEmployeeRegionSkeleton');
  const errorBox = document.getElementById('adminPersonalInfoEmployeeRegionError');
  const content = document.getElementById('adminPersonalInfoEmployeeRegionContent');
  const retryButton = document.getElementById('adminPersonalInfoEmployeeRegionRetry');

  const load = async () => {
    if (skeleton) {
      skeleton.classList.remove('hidden');
    }
    if (errorBox) {
      errorBox.classList.add('hidden');
    }
    if (content) {
      content.classList.add('hidden');
      content.innerHTML = '';
    }

    try {
      const html = await fetchHtml(url);
      if (content) {
        content.innerHTML = html;
        content.classList.remove('hidden');
        bindEmployeeRegion(content);
      }
      if (skeleton) {
        skeleton.classList.add('hidden');
      }
    } catch (error) {
      console.error(error);
      if (skeleton) {
        skeleton.classList.add('hidden');
      }
      if (errorBox) {
        errorBox.classList.remove('hidden');
      }
    }
  };

  retryButton?.addEventListener('click', () => {
    load().catch(console.error);
  });

  load().catch(console.error);
};

const initAdminPersonalInformationPage = () => {
  initAdminShellInteractions();
  initModalSystem();
  initStatusChangeConfirmations();
  initModalCloseDelegation();
  initPendingReviewSection();
  initDeferredEmployeeRegion();

  if (!document.getElementById('adminPersonalInfoEmployeeRegion') && document.getElementById('personalInfoEmployeesTable')) {
    bindEmployeeRegion(document);
  }
};

export default initAdminPersonalInformationPage;