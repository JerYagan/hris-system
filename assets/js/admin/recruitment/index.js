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

const initRecruitmentActions = () => {
  const postingViewData = parseJsonScriptNode('recruitmentPostingViewData');
  const requirementsEl = document.getElementById('recruitmentViewRequirements');
  const applicantsBodyEl = document.getElementById('recruitmentViewApplicantsBody');
  const applicantProfileNameEl = document.getElementById('recruitmentApplicantProfileName');
  const applicantProfileMetaEl = document.getElementById('recruitmentApplicantProfileMeta');
  const applicantProfileContactEl = document.getElementById('recruitmentApplicantProfileContact');
  const applicantProfilePhotoEl = document.getElementById('recruitmentApplicantProfilePhoto');
  const applicantProfilePhotoFallbackEl = document.getElementById('recruitmentApplicantProfilePhotoFallback');
  const applicantCareerSummaryEl = document.getElementById('recruitmentApplicantCareerSummary');
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
      setInputValue('recruitmentEditPostingId', button.getAttribute('data-posting-id') || '');
      setInputValue('recruitmentEditTitle', button.getAttribute('data-title') || '');
      setInputValue('recruitmentEditOfficeId', button.getAttribute('data-office-id') || '');
      setInputValue('recruitmentEditEmploymentType', button.getAttribute('data-employment-type') || 'contractual');
      setInputValue('recruitmentEditPositionId', button.getAttribute('data-position-id') || '');
      setInputValue('recruitmentEditPlantillaItemNo', button.getAttribute('data-plantilla-item-no') || '');
      setInputValue('recruitmentEditOpenDate', button.getAttribute('data-open-date') || '');
      setInputValue('recruitmentEditCloseDate', button.getAttribute('data-close-date') || '');
      setInputValue('recruitmentEditStatus', button.getAttribute('data-posting-status') || 'draft');
      setInputValue('recruitmentEditDescription', button.getAttribute('data-description') || '');
      setInputValue('recruitmentEditQualifications', button.getAttribute('data-qualifications') || '');
      setInputValue('recruitmentEditResponsibilities', button.getAttribute('data-responsibilities') || '');
      setInputValue('recruitmentEditCriteriaEligibility', button.getAttribute('data-eligibility-option') || 'csc_prc');
      setInputValue('recruitmentEditCriteriaEducationYears', button.getAttribute('data-minimum-education-years') || '0');
      setInputValue('recruitmentEditCriteriaTrainingHours', button.getAttribute('data-minimum-training-hours') || '0');
      setInputValue('recruitmentEditCriteriaExperienceYears', button.getAttribute('data-minimum-experience-years') || '0');

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

export default function initAdminRecruitmentPage() {
  initAdminShellInteractions();
  initModalSystem();
  initStatusChangeConfirmations();
  initRecruitmentFilters();
  initPositionFilters();
  initRecruitmentActions();
  initRecruitmentDatePickers().catch(console.error);
}
