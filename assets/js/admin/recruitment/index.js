import { bindTableFilters, initAdminShellInteractions, initModalSystem, initStatusChangeConfirmations, openModal } from '/hris-system/assets/js/shared/admin-core.js';

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

const initRecruitmentFilters = () => {
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
  const requirementsEl = document.getElementById('recruitmentViewRequirements');
  const applicantsBodyEl = document.getElementById('recruitmentViewApplicantsBody');
  const applicantProfileNameEl = document.getElementById('recruitmentApplicantProfileName');
  const applicantProfileMetaEl = document.getElementById('recruitmentApplicantProfileMeta');
  const applicantProfileContactEl = document.getElementById('recruitmentApplicantProfileContact');
  const applicantProfilePhotoEl = document.getElementById('recruitmentApplicantProfilePhoto');
  const applicantProfilePhotoFallbackEl = document.getElementById('recruitmentApplicantProfilePhotoFallback');
  const applicantEligibilityEl = document.getElementById('recruitmentApplicantEligibility');
  const applicantEducationEl = document.getElementById('recruitmentApplicantEducation');
  const applicantTrainingEl = document.getElementById('recruitmentApplicantTraining');
  const applicantExperienceEl = document.getElementById('recruitmentApplicantExperience');
  const applicantWorkExperienceEl = document.getElementById('recruitmentApplicantWorkExperience');
  const applicantLinksEmptyEl = document.getElementById('recruitmentApplicantLinksEmpty');
  const applicantDocumentsBodyEl = document.getElementById('recruitmentApplicantDocumentsBody');

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

  document.querySelectorAll('[data-recruitment-job-view]').forEach((button) => {
    button.addEventListener('click', () => {
      const postingId = (button.getAttribute('data-posting-id') || '').trim();
      const posting = postingViewData && typeof postingViewData === 'object' ? postingViewData[postingId] : null;
      if (!posting || typeof posting !== 'object') {
        return;
      }

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

      openModal('recruitmentPostingViewModal');
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
  initRecruitmentFilters();
  initPositionFilters();
  initCreatePositionModeAndCriteria();
  initEditPositionMode();
  initRecruitmentActions();
  initRecruitmentDecisionConfirmations();
  initRecruitmentDatePickers().catch(console.error);
}
