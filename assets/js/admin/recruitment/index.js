import { bindTableFilters, initAdminShellInteractions, initModalSystem, initStatusChangeConfirmations, openModal } from '/hris-system/assets/js/shared/admin-core.js';

const MIN_SKELETON_MS = 250;

const parseJsonScriptNode = (id) => {
  const node = document.getElementById(id);
  if (!node) {
    return {};
  }

  try {
    const parsed = JSON.parse(node.textContent || '{}');
    return parsed && typeof parsed === 'object' ? parsed : {};
  } catch (_error) {
    return {};
  }
};

const escapeHtml = (value) => {
  const text = (value || '').toString();
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
};

const emitQaPerfSectionMetric = ({ section, status = 'success', fetchMs = null, displayMs = null, detail = '', url = '' }) => {
  if (typeof document === 'undefined' || typeof window.CustomEvent !== 'function') {
    return;
  }

  document.dispatchEvent(new CustomEvent('hris:qa-perf-section', {
    detail: {
      page: window.location.pathname,
      section,
      status,
      fetch_ms: fetchMs,
      display_ms: displayMs,
      detail,
      url,
      source: 'admin-recruitment',
    },
  }));
};

const fetchJsonResponse = async (url) => {
  const response = await fetch(url, {
    credentials: 'same-origin',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
    },
  });

  if (response.redirected) {
    window.location.href = response.url;
    return null;
  }

  let payload = null;
  try {
    payload = await response.json();
  } catch (_error) {
    payload = null;
  }

  if (!response.ok) {
    const message = payload && typeof payload.error === 'string' && payload.error.trim() !== ''
      ? payload.error.trim()
      : `Recruitment JSON request failed with status ${response.status}`;
    throw new Error(message);
  }

  if (!payload || typeof payload !== 'object') {
    throw new Error('Recruitment detail request returned an invalid payload.');
  }

  if (typeof payload.error === 'string' && payload.error.trim() !== '') {
    throw new Error(payload.error.trim());
  }

  return payload;
};

const setInputValue = (id, value) => {
  const element = document.getElementById(id);
  if (!element) {
    return;
  }
  element.value = value;
};

const setCheckboxChecked = (id, checked) => {
  const element = document.getElementById(id);
  if (!(element instanceof HTMLInputElement) || element.type !== 'checkbox') {
    return;
  }
  element.checked = Boolean(checked);
};

const parseNumericValue = (value, fallback) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
};

const educationLevelFromYears = (years) => {
  const numericYears = Number.isFinite(Number(years)) ? Number(years) : 0;
  if (numericYears >= 6) {
    return 'graduate';
  }
  if (numericYears >= 4) {
    return 'college';
  }
  if (numericYears >= 2) {
    return 'vocational';
  }
  if (numericYears >= 1) {
    return 'secondary';
  }

  return 'elementary';
};

const initRecruitmentFilters = ({ reloadListings } = {}) => {
  const filtersForm = document.getElementById('recruitmentListingsFilters');
  const pageInput = document.getElementById('recruitmentPageInput');
  const searchInput = document.getElementById('recruitmentSearchInput');
  const statusFilter = document.getElementById('recruitmentStatusFilter');
  const prevButton = document.getElementById('recruitmentPrevPage');
  const nextButton = document.getElementById('recruitmentNextPage');

  if (filtersForm && pageInput && searchInput && statusFilter && typeof reloadListings === 'function') {
    let debounceTimer = 0;
    const submitCurrentState = () => {
      reloadListings(new FormData(filtersForm));
    };

    searchInput.addEventListener('input', () => {
      pageInput.value = '1';
      window.clearTimeout(debounceTimer);
      debounceTimer = window.setTimeout(() => {
        submitCurrentState();
      }, 200);
    });

    statusFilter.addEventListener('change', () => {
      pageInput.value = '1';
      submitCurrentState();
    });

    prevButton?.addEventListener('click', () => {
      const page = prevButton.getAttribute('data-recruitment-page') || '';
      if (page === '') {
        return;
      }

      pageInput.value = page;
      submitCurrentState();
    });

    nextButton?.addEventListener('click', () => {
      const page = nextButton.getAttribute('data-recruitment-page') || '';
      if (page === '') {
        return;
      }

      pageInput.value = page;
      submitCurrentState();
    });

    return;
  }

  bindTableFilters({
    tableId: 'recruitmentPostingsTable',
    searchInputId: 'recruitmentPostingsSearch',
    searchDataAttr: 'data-recruitment-postings-search',
    statusFilterId: 'recruitmentPostingsStatusFilter',
    statusDataAttr: 'data-recruitment-postings-status',
  });
};

const setTextContent = (id, value) => {
  const element = document.getElementById(id);
  if (!element) {
    return;
  }
  element.textContent = value;
};

const setLinkState = (id, url) => {
  const link = document.getElementById(id);
  if (!link) {
    return false;
  }

  const href = (url || '').toString().trim();
  if (href === '') {
    link.classList.add('hidden');
    link.setAttribute('href', '#');
    return false;
  }

  link.classList.remove('hidden');
  link.setAttribute('href', href);
  return true;
};

const initPositionFilters = () => {
  const applyPositionFilter = (employmentTypeSelectId, positionSelectId) => {
    const employmentTypeSelect = document.getElementById(employmentTypeSelectId);
    const positionSelect = document.getElementById(positionSelectId);
    if (!employmentTypeSelect || !positionSelect) {
      return;
    }

    const selectedType = (employmentTypeSelect.value || '').trim().toLowerCase();
    const options = Array.from(positionSelect.options || []);
    options.forEach((option, index) => {
      if (index === 0) {
        option.hidden = false;
        option.disabled = false;
        return;
      }

      const optionType = (option.getAttribute('data-employment-type') || '').trim().toLowerCase();
      const isMatch = selectedType === '' || optionType === selectedType;
      option.hidden = !isMatch;
      option.disabled = !isMatch;
    });

    const selectedOption = positionSelect.options[positionSelect.selectedIndex];
    if (selectedOption && selectedOption.disabled) {
      positionSelect.value = '';
    }
  };

  const bind = (employmentTypeSelectId, positionSelectId) => {
    const employmentTypeSelect = document.getElementById(employmentTypeSelectId);
    if (!employmentTypeSelect) {
      return;
    }

    employmentTypeSelect.addEventListener('change', () => {
      applyPositionFilter(employmentTypeSelectId, positionSelectId);
    });

    applyPositionFilter(employmentTypeSelectId, positionSelectId);
  };

  bind('recruitmentCreateEmploymentType', 'recruitmentCreatePositionId');
  bind('recruitmentEditEmploymentType', 'recruitmentEditPositionId');
};

const initCreatePositionModeAndCriteria = () => {
  const criteriaPayload = parseJsonScriptNode('recruitmentCreatePositionCriteriaData');
  const defaults = criteriaPayload && typeof criteriaPayload.defaults === 'object'
    ? criteriaPayload.defaults
    : {};
  const positions = criteriaPayload && typeof criteriaPayload.positions === 'object'
    ? criteriaPayload.positions
    : {};

  const modeSelect = document.getElementById('recruitmentCreatePositionMode');
  const predefinedWrap = document.getElementById('recruitmentCreatePredefinedPositionWrap');
  const newWrap = document.getElementById('recruitmentCreateNewPositionWrap');
  const predefinedSelect = document.getElementById('recruitmentCreatePositionId');
  const newTitleInput = document.getElementById('recruitmentCreateNewPositionTitle');

  const applyCriteriaValues = (criteria) => {
    const source = criteria && typeof criteria === 'object' ? criteria : defaults;
    const eligibilityOption = (source.eligibility_option || defaults.eligibility_option || 'csc_prc').toString().toLowerCase();
    setCheckboxChecked('recruitmentCreateCriteriaEligibilityRequired', eligibilityOption !== 'none');

    const educationYears = parseNumericValue(source.minimum_education_years, parseNumericValue(defaults.minimum_education_years, 2));
    const trainingHours = parseNumericValue(source.minimum_training_hours, parseNumericValue(defaults.minimum_training_hours, 4));
    const experienceYears = parseNumericValue(source.minimum_experience_years, parseNumericValue(defaults.minimum_experience_years, 1));

    setInputValue(
      'recruitmentCreateCriteriaEducationLevel',
      (source.minimum_education_level || defaults.minimum_education_level || '').toString().toLowerCase() || educationLevelFromYears(educationYears)
    );
    setInputValue('recruitmentCreateCriteriaTrainingHours', trainingHours.toString());
    setInputValue('recruitmentCreateCriteriaExperienceYears', experienceYears.toString());
  };

  const applyPositionCriteriaFromSelection = () => {
    if (!(predefinedSelect instanceof HTMLSelectElement)) {
      return;
    }

    const selectedPositionId = (predefinedSelect.value || '').trim().toLowerCase();
    if (!selectedPositionId) {
      applyCriteriaValues(defaults);
      return;
    }

    applyCriteriaValues(positions[selectedPositionId] || defaults);
  };

  const applyMode = () => {
    if (!(modeSelect instanceof HTMLSelectElement)) {
      return;
    }

    const mode = (modeSelect.value || 'predefined').toLowerCase();
    const useNew = mode === 'new';

    if (predefinedWrap) {
      predefinedWrap.classList.toggle('hidden', useNew);
    }
    if (newWrap) {
      newWrap.classList.toggle('hidden', !useNew);
    }

    if (predefinedSelect instanceof HTMLSelectElement) {
      predefinedSelect.required = !useNew;
      predefinedSelect.disabled = useNew;
      if (useNew) {
        predefinedSelect.value = '';
      }
    }

    if (newTitleInput instanceof HTMLInputElement) {
      newTitleInput.required = useNew;
      newTitleInput.disabled = !useNew;
      if (!useNew) {
        newTitleInput.value = '';
      }
    }

    if (useNew) {
      applyCriteriaValues(defaults);
    } else {
      applyPositionCriteriaFromSelection();
    }
  };

  if (modeSelect instanceof HTMLSelectElement) {
    modeSelect.addEventListener('change', applyMode);
  }

  if (predefinedSelect instanceof HTMLSelectElement) {
    predefinedSelect.addEventListener('change', applyPositionCriteriaFromSelection);
  }

  const createEmploymentType = document.getElementById('recruitmentCreateEmploymentType');
  if (createEmploymentType) {
    createEmploymentType.addEventListener('change', () => {
      window.requestAnimationFrame(() => {
        const mode = (modeSelect instanceof HTMLSelectElement ? modeSelect.value : 'predefined').toLowerCase();
        if (mode !== 'new') {
          applyPositionCriteriaFromSelection();
        }
      });
    });
  }

  applyMode();
};

const initEditPositionMode = () => {
  const modeSelect = document.getElementById('recruitmentEditPositionMode');
  const predefinedWrap = document.getElementById('recruitmentEditPredefinedPositionWrap');
  const newWrap = document.getElementById('recruitmentEditNewPositionWrap');
  const predefinedSelect = document.getElementById('recruitmentEditPositionId');
  const newTitleInput = document.getElementById('recruitmentEditNewPositionTitle');

  const applyMode = () => {
    if (!(modeSelect instanceof HTMLSelectElement)) {
      return;
    }

    const mode = (modeSelect.value || 'predefined').toLowerCase();
    const useNew = mode === 'new';

    if (predefinedWrap) {
      predefinedWrap.classList.toggle('hidden', useNew);
    }
    if (newWrap) {
      newWrap.classList.toggle('hidden', !useNew);
    }

    if (predefinedSelect instanceof HTMLSelectElement) {
      predefinedSelect.required = !useNew;
      predefinedSelect.disabled = useNew;
      if (useNew) {
        predefinedSelect.value = '';
      }
    }

    if (newTitleInput instanceof HTMLInputElement) {
      newTitleInput.required = useNew;
      newTitleInput.disabled = !useNew;
      if (!useNew) {
        newTitleInput.value = '';
      }
    }
  };

  if (modeSelect instanceof HTMLSelectElement) {
    modeSelect.addEventListener('change', applyMode);
  }

  applyMode();
};

const initRecruitmentActions = () => {
  const postingViewData = parseJsonScriptNode('recruitmentPostingViewData');
  const asyncRegion = document.getElementById('adminRecruitmentAsyncRegion');
  const postingViewBaseUrl = asyncRegion?.getAttribute('data-recruitment-posting-view-url') || '';
  const postingViewCache = new Map(Object.entries(postingViewData && typeof postingViewData === 'object' ? postingViewData : {}));
  let postingViewRequestId = 0;
  const requirementsEl = document.getElementById('recruitmentViewRequirements');
  const applicantsBodyEl = document.getElementById('recruitmentViewApplicantsBody');

  const renderRequirements = (requirements) => {
    if (!requirementsEl) {
      return;
    }

    if (!Array.isArray(requirements) || requirements.length === 0) {
      requirementsEl.innerHTML = '<li>-</li>';
      return;
    }

    requirementsEl.innerHTML = requirements.map((item) => `<li>${escapeHtml(item)}</li>`).join('');
  };

  const renderApplicants = (applicants) => {
    if (!applicantsBodyEl) {
      return;
    }

    if (!Array.isArray(applicants) || applicants.length === 0) {
      applicantsBodyEl.innerHTML = '<tr><td colspan="7" class="px-3 py-3 text-slate-500">No applicants yet.</td></tr>';
      return;
    }

    applicantsBodyEl.innerHTML = applicants.map((applicant) => {
      const applicantName = escapeHtml(applicant && applicant.applicant_name ? applicant.applicant_name : 'Applicant');
      const applicantEmail = escapeHtml(applicant && applicant.applicant_email ? applicant.applicant_email : '-');
      const appliedPosition = escapeHtml(applicant && applicant.applied_position ? applicant.applied_position : '-');
      const submittedLabel = escapeHtml(applicant && applicant.submitted_label ? applicant.submitted_label : '-');
      const screeningLabel = escapeHtml(applicant && applicant.initial_screening_label ? applicant.initial_screening_label : 'Applied');
      const screeningClass = applicant && applicant.initial_screening_class ? applicant.initial_screening_class : 'bg-slate-100 text-slate-700';
      const recommendationScore = Number.isFinite(Number(applicant && applicant.score)) ? Number(applicant.score) : 0;
      const recommendationLabel = escapeHtml(applicant && applicant.score_label ? applicant.score_label : 'Low');
      const recommendationClass = applicant && applicant.score_class ? applicant.score_class : 'bg-slate-200 text-slate-700';
      const basis = escapeHtml(applicant && applicant.basis ? applicant.basis : '-');
      const viewProfileUrl = escapeHtml(applicant && applicant.view_profile_url ? applicant.view_profile_url : '#');

      return `
        <tr>
          <td class="px-3 py-2">
            <a href="${viewProfileUrl}" target="_blank" rel="noopener noreferrer" class="font-medium text-slate-800 hover:underline">${applicantName}</a>
            <div class="text-xs text-slate-500">${applicantEmail}</div>
          </td>
          <td class="px-3 py-2 text-slate-700">${appliedPosition}</td>
          <td class="px-3 py-2 text-slate-700">${submittedLabel}</td>
          <td class="px-3 py-2"><span class="inline-flex px-2 py-1 text-xs rounded-full ${screeningClass}">${screeningLabel}</span></td>
          <td class="px-3 py-2">
            <div class="font-medium text-slate-800">${recommendationScore}%</div>
            <div class="mt-1"><span class="inline-flex px-2 py-0.5 text-[11px] rounded-full ${recommendationClass}">${recommendationLabel}</span></div>
          </td>
          <td class="px-3 py-2 text-slate-700">${basis}</td>
          <td class="px-3 py-2">
            <div class="inline-flex items-center gap-2">
              <a href="${viewProfileUrl}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm text-xs"><span class="material-symbols-outlined text-[16px]">person_search</span>View Profile</a>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  };

  const setPostingViewLoadingState = () => {
    setTextContent('recruitmentViewPosition', 'Loading...');
    setTextContent('recruitmentViewPlantillaItemNo', '-');
    setTextContent('recruitmentViewOffice', '-');
    setTextContent('recruitmentViewEmploymentType', '-');
    setTextContent('recruitmentViewStatus', '-');
    setTextContent('recruitmentViewOpenDate', '-');
    setTextContent('recruitmentViewCloseDate', '-');
    setTextContent('recruitmentViewDescription', 'Loading recruitment posting details...');
    setTextContent('recruitmentViewQualifications', '-');
    setTextContent('recruitmentViewResponsibilities', '-');
    setTextContent('recruitmentViewCriteriaEligibility', '-');
    setTextContent('recruitmentViewCriteriaEducation', '-');
    setTextContent('recruitmentViewCriteriaTraining', '-');
    setTextContent('recruitmentViewCriteriaExperience', '-');
    if (requirementsEl) {
      requirementsEl.innerHTML = '<li>Loading requirements...</li>';
    }
    if (applicantsBodyEl) {
      applicantsBodyEl.innerHTML = '<tr><td colspan="7" class="px-3 py-3 text-slate-500">Loading applicants...</td></tr>';
    }
  };

  const renderPostingView = (posting) => {
    setTextContent('recruitmentViewPosition', posting.position_title || posting.posting_title || '-');
    setTextContent('recruitmentViewPlantillaItemNo', posting.plantilla_item_no || '-');
    setTextContent('recruitmentViewOffice', posting.office_name || '-');
    setTextContent('recruitmentViewEmploymentType', posting.employment_type || '-');
    setTextContent('recruitmentViewStatus', posting.status_label || '-');
    setTextContent('recruitmentViewOpenDate', posting.open_date_label || '-');
    setTextContent('recruitmentViewCloseDate', posting.close_date_label || '-');
    setTextContent('recruitmentViewDescription', posting.description || '-');
    setTextContent('recruitmentViewQualifications', posting.qualifications || '-');
    setTextContent('recruitmentViewResponsibilities', posting.responsibilities || '-');
    setTextContent('recruitmentViewCriteriaEligibility', posting.criteria?.eligibility || '-');
    setTextContent('recruitmentViewCriteriaEducation', posting.criteria?.education || '-');
    setTextContent('recruitmentViewCriteriaTraining', posting.criteria?.training || '-');
    setTextContent('recruitmentViewCriteriaExperience', posting.criteria?.experience || '-');

    renderRequirements(posting.requirements || []);
    renderApplicants(posting.applicants || []);
  };

  const loadPostingView = async (postingId) => {
    if (postingViewCache.has(postingId)) {
      return postingViewCache.get(postingId);
    }

    if (!postingViewBaseUrl) {
      throw new Error('Recruitment posting detail endpoint is not available.');
    }

    const url = new URL(postingViewBaseUrl, window.location.href);
    url.searchParams.set('posting_id', postingId);
    const payload = await fetchJsonResponse(url.toString());
    postingViewCache.set(postingId, payload);
    return payload;
  };

  document.querySelectorAll('[data-recruitment-job-view]').forEach((button) => {
    button.addEventListener('click', async () => {
      const postingId = (button.getAttribute('data-posting-id') || '').trim();
      if (postingId === '') {
        return;
      }

      postingViewRequestId += 1;
      const requestId = postingViewRequestId;
      const startedAt = window.performance?.now?.() ?? Date.now();
      setPostingViewLoadingState();
      openModal('recruitmentPostingViewModal');

      try {
        const posting = await loadPostingView(postingId);
        if (requestId !== postingViewRequestId) {
          return;
        }

        renderPostingView(posting);
        const elapsed = (window.performance?.now?.() ?? Date.now()) - startedAt;
        emitQaPerfSectionMetric({
          section: 'Posting View',
          status: 'success',
          fetchMs: elapsed,
          displayMs: elapsed,
          url: postingViewBaseUrl,
        });
      } catch (error) {
        if (requestId !== postingViewRequestId) {
          return;
        }

        setTextContent('recruitmentViewPosition', 'Unavailable');
        setTextContent('recruitmentViewDescription', error instanceof Error ? error.message : 'Recruitment posting details could not be loaded.');
        setTextContent('recruitmentViewQualifications', '-');
        setTextContent('recruitmentViewResponsibilities', '-');
        setTextContent('recruitmentViewCriteriaEligibility', '-');
        setTextContent('recruitmentViewCriteriaEducation', '-');
        setTextContent('recruitmentViewCriteriaTraining', '-');
        setTextContent('recruitmentViewCriteriaExperience', '-');
        if (requirementsEl) {
          requirementsEl.innerHTML = '<li>-</li>';
        }
        if (applicantsBodyEl) {
          applicantsBodyEl.innerHTML = '<tr><td colspan="7" class="px-3 py-3 text-rose-600">Recruitment posting details could not be loaded.</td></tr>';
        }
        const elapsed = (window.performance?.now?.() ?? Date.now()) - startedAt;
        emitQaPerfSectionMetric({
          section: 'Posting View',
          status: 'error',
          fetchMs: elapsed,
          displayMs: elapsed,
          detail: error instanceof Error ? error.message : 'Posting view failed.',
          url: postingViewBaseUrl,
        });
        console.error(error);
      }
    });
  });

  document.querySelectorAll('[data-recruitment-job-edit]').forEach((button) => {
    button.addEventListener('click', () => {
      const editModeSelect = document.getElementById('recruitmentEditPositionMode');
      if (editModeSelect instanceof HTMLSelectElement) {
        editModeSelect.value = 'predefined';
        editModeSelect.dispatchEvent(new Event('change'));
      }

      setInputValue('recruitmentEditPostingId', button.getAttribute('data-posting-id') || '');
      setInputValue('recruitmentEditTitle', button.getAttribute('data-title') || '');
      setInputValue('recruitmentEditOfficeId', button.getAttribute('data-office-id') || '');
      setInputValue('recruitmentEditEmploymentType', button.getAttribute('data-employment-type') || 'contractual');
      const editEmploymentType = document.getElementById('recruitmentEditEmploymentType');
      if (editEmploymentType) {
        editEmploymentType.dispatchEvent(new Event('change'));
      }
      setInputValue('recruitmentEditPositionId', button.getAttribute('data-position-id') || '');
      setInputValue('recruitmentEditNewPositionTitle', '');
      setInputValue('recruitmentEditPlantillaItemNo', button.getAttribute('data-plantilla-item-no') || '');
      setInputValue('recruitmentEditOpenDate', button.getAttribute('data-open-date') || '');
      setInputValue('recruitmentEditCloseDate', button.getAttribute('data-close-date') || '');
      setInputValue('recruitmentEditStatus', button.getAttribute('data-posting-status') || 'draft');
      setInputValue('recruitmentEditDescription', button.getAttribute('data-description') || '');
      setInputValue('recruitmentEditQualifications', button.getAttribute('data-qualifications') || '');
      setInputValue('recruitmentEditResponsibilities', button.getAttribute('data-responsibilities') || '');
      const eligibilityOption = (button.getAttribute('data-eligibility-option') || 'csc_prc').toLowerCase();
      setCheckboxChecked('recruitmentEditCriteriaEligibilityRequired', eligibilityOption !== 'none');
      const educationLevel = (button.getAttribute('data-minimum-education-level') || '').toLowerCase();
      const educationYears = parseNumericValue(button.getAttribute('data-minimum-education-years') || '0', 0);
      setInputValue('recruitmentEditCriteriaEducationLevel', educationLevel || educationLevelFromYears(educationYears));
      setInputValue('recruitmentEditCriteriaTrainingHours', button.getAttribute('data-minimum-training-hours') || '0');
      setInputValue('recruitmentEditCriteriaExperienceYears', button.getAttribute('data-minimum-experience-years') || '0');

      const editModal = document.getElementById('recruitmentEditJobModal');
      const selectedRequiredKeys = new Set(
        (button.getAttribute('data-required-document-keys') || '')
          .split(',')
          .map((key) => key.trim().toLowerCase())
          .filter((key) => key !== '')
      );

      if (editModal) {
        editModal.querySelectorAll('input[name="required_documents[]"]').forEach((checkbox) => {
          if (!(checkbox instanceof HTMLInputElement) || checkbox.type !== 'checkbox') {
            return;
          }

          if (!selectedRequiredKeys.size) {
            checkbox.checked = true;
            return;
          }

          checkbox.checked = selectedRequiredKeys.has((checkbox.value || '').trim().toLowerCase());
        });
      }

      openModal('recruitmentEditJobModal');
    });
  });

  document.querySelectorAll('[data-recruitment-job-archive]').forEach((button) => {
    button.addEventListener('click', () => {
      const postingId = button.getAttribute('data-posting-id') || '';
      const title = button.getAttribute('data-title') || '-';
      setInputValue('recruitmentArchivePostingId', postingId);

      const titleElement = document.getElementById('recruitmentArchivePostingTitle');
      if (titleElement) {
        titleElement.textContent = title;
      }

      openModal('recruitmentArchiveJobModal');
    });
  });

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) {
      return;
    }

    document.querySelectorAll('[data-recruitment-action-menu]').forEach((menu) => {
      if (menu.contains(target)) {
        return;
      }
      menu.removeAttribute('open');
    });
  });
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

const initRecruitmentDatePickers = async () => {
  const dateInputs = [
    ...document.querySelectorAll('input[name="open_date"]'),
    ...document.querySelectorAll('input[name="close_date"]'),
    ...document.querySelectorAll('#recruitmentEditOpenDate, #recruitmentEditCloseDate'),
  ];

  if (!dateInputs.length) {
    return;
  }

  const flatpickr = await ensureFlatpickr();
  dateInputs.forEach((input) => {
    flatpickr(input, {
      dateFormat: 'Y-m-d',
      allowInput: true,
    });
  });
};

const initRecruitmentDecisionConfirmations = () => {
  const applicantDecisionForm = document.getElementById('applicantDecisionForm');
  const decisionSelect = applicantDecisionForm?.querySelector('select[name="decision"]');
  const basisSelect = applicantDecisionForm?.querySelector('select[name="basis"]');
  const applicantNameLabel = document.getElementById('applicantProfileName');

  const syncApplicantDecisionConfirmation = () => {
    if (!(applicantDecisionForm instanceof HTMLFormElement)) {
      return;
    }

    const applicantName = String(applicantNameLabel?.textContent || '').trim() || 'this applicant';
    const decision = String(decisionSelect?.value || 'approve_for_next_stage').trim().toLowerCase();
    const basis = String(basisSelect?.value || '').trim();

    if (decision === 'disqualify_application') {
      applicantDecisionForm.dataset.confirmTitle = 'Disqualify this application?';
      applicantDecisionForm.dataset.confirmText = `${applicantName} will be marked as disqualified${basis !== '' ? ` based on ${basis}` : ''}.`;
      applicantDecisionForm.dataset.confirmButtonText = 'Disqualify application';
      applicantDecisionForm.dataset.confirmButtonColor = '#dc2626';
      return;
    }

    if (decision === 'return_for_compliance') {
      applicantDecisionForm.dataset.confirmTitle = 'Return application for compliance?';
      applicantDecisionForm.dataset.confirmText = `${applicantName} will be returned for compliance follow-up${basis !== '' ? ` based on ${basis}` : ''}.`;
      applicantDecisionForm.dataset.confirmButtonText = 'Return for compliance';
      applicantDecisionForm.dataset.confirmButtonColor = '#0f172a';
      return;
    }

    applicantDecisionForm.dataset.confirmTitle = 'Approve applicant for the next stage?';
    applicantDecisionForm.dataset.confirmText = `${applicantName} will advance to the next approved recruitment stage${basis !== '' ? ` based on ${basis}` : ''}.`;
    applicantDecisionForm.dataset.confirmButtonText = 'Approve for next stage';
    applicantDecisionForm.dataset.confirmButtonColor = '#0f172a';
  };

  if (decisionSelect instanceof HTMLSelectElement) {
    decisionSelect.addEventListener('change', syncApplicantDecisionConfirmation);
  }

  if (basisSelect instanceof HTMLSelectElement) {
    basisSelect.addEventListener('change', syncApplicantDecisionConfirmation);
  }

  syncApplicantDecisionConfirmation();
};

export default function initAdminRecruitmentPage() {
  initAdminShellInteractions();
  initModalSystem();
  initStatusChangeConfirmations();

  const initAdminRecruitmentAsync = () => {
    const region = document.getElementById('adminRecruitmentAsyncRegion');
    if (!region) {
      return false;
    }

    const summaryUrl = region.getAttribute('data-recruitment-summary-url') || '';
    const listingsUrl = region.getAttribute('data-recruitment-listings-url') || '';
    const secondaryUrl = region.getAttribute('data-recruitment-secondary-url') || '';

    const summarySkeleton = document.getElementById('adminRecruitmentSummarySkeleton');
    const summaryContent = document.getElementById('adminRecruitmentSummaryContent');
    const summaryError = document.getElementById('adminRecruitmentSummaryError');
    const summaryRetry = document.getElementById('adminRecruitmentSummaryRetry');

    const listingsSkeleton = document.getElementById('adminRecruitmentListingsSkeleton');
    const listingsContent = document.getElementById('adminRecruitmentListingsContent');
    const listingsError = document.getElementById('adminRecruitmentListingsError');
    const listingsRetry = document.getElementById('adminRecruitmentListingsRetry');

    const secondarySkeleton = document.getElementById('adminRecruitmentSecondarySkeleton');
    const secondaryContent = document.getElementById('adminRecruitmentSecondaryContent');
    const secondaryError = document.getElementById('adminRecruitmentSecondaryError');
    const secondaryRetry = document.getElementById('adminRecruitmentSecondaryRetry');

    if (!summaryUrl || !listingsUrl || !secondaryUrl || !summarySkeleton || !summaryContent || !summaryError || !summaryRetry || !listingsSkeleton || !listingsContent || !listingsError || !listingsRetry || !secondarySkeleton || !secondaryContent || !secondaryError || !secondaryRetry) {
      return false;
    }

    const summaryState = { requestId: 0 };
    const listingsState = { requestId: 0 };
    const secondaryState = { requestId: 0 };

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
        throw new Error(`Recruitment partial request failed with status ${response.status}`);
      }

      const html = await response.text();
      if (html.trim() === '') {
        throw new Error('Recruitment partial returned an empty response.');
      }

      return html;
    };

    const loadSection = async ({ sectionName, url, params, skeleton, content, errorBox, retryButton, onSuccess, state }) => {
      setLoadingState({ skeleton, content, errorBox, retryButton }, true);
      state.requestId += 1;
      const requestId = state.requestId;
      const startedAt = window.performance?.now?.() ?? Date.now();
      const requestUrl = buildSectionUrl(url, params);

      try {
        const html = await fetchPartialHtml(requestUrl);
        const fetchElapsed = (window.performance?.now?.() ?? Date.now()) - startedAt;
        const remaining = Math.max(0, MIN_SKELETON_MS - fetchElapsed);

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

          const displayElapsed = (window.performance?.now?.() ?? Date.now()) - startedAt;
          emitQaPerfSectionMetric({
            section: sectionName,
            status: 'success',
            fetchMs: fetchElapsed,
            displayMs: displayElapsed,
            url: requestUrl,
          });
        }, remaining);
      } catch (error) {
        console.error(error);
        showErrorState({ skeleton, content, errorBox, retryButton });
        const elapsed = (window.performance?.now?.() ?? Date.now()) - startedAt;
        emitQaPerfSectionMetric({
          section: sectionName,
          status: 'error',
          fetchMs: elapsed,
          displayMs: elapsed,
          detail: error instanceof Error ? error.message : 'Section load failed.',
          url: requestUrl,
        });
      }
    };

    const setSecondaryActionsEnabled = (enabled) => {
      document.querySelectorAll('[data-recruitment-secondary-action]').forEach((element) => {
        element.disabled = !enabled;
        element.setAttribute('aria-disabled', enabled ? 'false' : 'true');
      });
    };

    const loadSummary = () => loadSection({
      sectionName: 'Summary',
      url: summaryUrl,
      skeleton: summarySkeleton,
      content: summaryContent,
      errorBox: summaryError,
      retryButton: summaryRetry,
      state: summaryState,
    });

    const loadListings = (params) => loadSection({
      sectionName: 'Listings',
      url: listingsUrl,
      params,
      skeleton: listingsSkeleton,
      content: listingsContent,
      errorBox: listingsError,
      retryButton: listingsRetry,
      state: listingsState,
      onSuccess: () => {
        initRecruitmentFilters({ reloadListings: loadListings });
      },
    });

    const loadSecondary = () => loadSection({
      sectionName: 'Secondary',
      url: secondaryUrl,
      skeleton: secondarySkeleton,
      content: secondaryContent,
      errorBox: secondaryError,
      retryButton: secondaryRetry,
      state: secondaryState,
      onSuccess: () => {
        initModalSystem();
        initStatusChangeConfirmations();
        initRecruitmentFilters({ reloadListings: loadListings });
        initPositionFilters();
        initCreatePositionModeAndCriteria();
        initEditPositionMode();
        initRecruitmentActions();
        initRecruitmentDecisionConfirmations();
        setSecondaryActionsEnabled(true);
        initRecruitmentDatePickers().catch(console.error);
      },
    });

    summaryRetry.addEventListener('click', () => {
      loadSummary().catch?.(console.error);
    });

    listingsRetry.addEventListener('click', () => {
      loadListings().catch?.(console.error);
    });

    secondaryRetry.addEventListener('click', () => {
      loadSecondary().catch?.(console.error);
    });

    setSecondaryActionsEnabled(false);
    loadSummary()
      .catch(console.error)
      .finally(() => loadListings().catch(console.error).finally(() => loadSecondary().catch(console.error)));

    return true;
  };

  if (initAdminRecruitmentAsync()) {
    return;
  }

  initRecruitmentFilters();
  initPositionFilters();
  initCreatePositionModeAndCriteria();
  initEditPositionMode();
  initRecruitmentActions();
  initRecruitmentDecisionConfirmations();
  initRecruitmentDatePickers().catch(console.error);
}
