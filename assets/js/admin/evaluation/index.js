import { initAdminShellInteractions, initModalSystem, initStatusChangeConfirmations } from '/hris-system/assets/js/shared/admin-core.js';

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

const initPositionCriteriaAutofill = () => {
  const positionSelect = document.getElementById('evaluationPositionSelect');
  const eligibilitySelect = document.getElementById('evaluationPositionEligibility');
  const educationInput = document.getElementById('evaluationPositionEducation');
  const trainingInput = document.getElementById('evaluationPositionTraining');
  const experienceInput = document.getElementById('evaluationPositionExperience');

  if (!positionSelect || !eligibilitySelect || !educationInput || !trainingInput || !experienceInput) {
    return;
  }

  const overrides = parseJsonScriptNode('evaluationPositionCriteriaData');
  const defaultEducation = educationInput.value || '2';
  const defaultTraining = trainingInput.value || '4';
  const defaultExperience = experienceInput.value || '1';

  const applyValues = () => {
    const positionId = (positionSelect.value || '').trim().toLowerCase();
    const row = positionId !== '' && overrides[positionId] && typeof overrides[positionId] === 'object'
      ? overrides[positionId]
      : null;

    eligibilitySelect.value = row && row.eligibility ? String(row.eligibility) : 'csc_prc';
    educationInput.value = row && Number.isFinite(Number(row.minimum_education_years))
      ? String(row.minimum_education_years)
      : defaultEducation;
    trainingInput.value = row && Number.isFinite(Number(row.minimum_training_hours))
      ? String(row.minimum_training_hours)
      : defaultTraining;
    experienceInput.value = row && Number.isFinite(Number(row.minimum_experience_years))
      ? String(row.minimum_experience_years)
      : defaultExperience;
  };

  positionSelect.addEventListener('change', applyValues);
  applyValues();
};

const initEvaluationTable = () => {
  const rows = Array.from(document.querySelectorAll('tr[data-evaluation-row]'));
  if (!rows.length) {
    return;
  }

  const searchInput = document.getElementById('evaluationSearch');
  const resultFilter = document.getElementById('evaluationResultFilter');
  const hiredFilter = document.getElementById('evaluationHiredFilter');
  const noMatchesRow = document.getElementById('evaluationNoMatchesRow');
  const prevButton = document.getElementById('evaluationPagePrev');
  const nextButton = document.getElementById('evaluationPageNext');
  const pageIndicator = document.getElementById('evaluationPageIndicator');
  const paginationMeta = document.getElementById('evaluationPaginationMeta');

  const pageSize = 10;
  let page = 1;

  const render = () => {
    const query = (searchInput?.value || '').trim().toLowerCase();
    const result = (resultFilter?.value || '').trim().toLowerCase();
    const hiredScope = (hiredFilter?.value || 'hide').trim().toLowerCase();

    const filtered = rows.filter((row) => {
      const haystack = (row.getAttribute('data-search') || '').toLowerCase();
      const rowResult = (row.getAttribute('data-result') || '').toLowerCase();
      const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
      const queryMatch = query === '' || haystack.includes(query);
      const resultMatch = result === '' || rowResult === result;
      const hiredMatch = hiredScope === 'show' || rowStatus !== 'hired';
      return queryMatch && resultMatch && hiredMatch;
    });

    const totalRows = filtered.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));
    page = Math.min(Math.max(page, 1), totalPages);

    const startIndex = totalRows === 0 ? 0 : (page - 1) * pageSize;
    const endIndex = totalRows === 0 ? 0 : Math.min(startIndex + pageSize, totalRows);

    rows.forEach((row) => {
      row.classList.add('hidden');
    });

    filtered.slice(startIndex, endIndex).forEach((row) => {
      row.classList.remove('hidden');
    });

    if (noMatchesRow) {
      noMatchesRow.classList.toggle('hidden', totalRows !== 0);
    }

    if (prevButton) {
      prevButton.disabled = page <= 1;
    }
    if (nextButton) {
      nextButton.disabled = page >= totalPages;
    }

    if (pageIndicator) {
      pageIndicator.textContent = `Page ${page} of ${totalPages}`;
    }

    if (paginationMeta) {
      const from = totalRows === 0 ? 0 : startIndex + 1;
      paginationMeta.textContent = `Showing ${from} to ${endIndex} of ${totalRows} entries`;
    }
  };

  searchInput?.addEventListener('input', () => {
    page = 1;
    render();
  });

  resultFilter?.addEventListener('change', () => {
    page = 1;
    render();
  });

  hiredFilter?.addEventListener('change', () => {
    page = 1;
    render();
  });

  prevButton?.addEventListener('click', () => {
    page -= 1;
    render();
  });

  nextButton?.addEventListener('click', () => {
    page += 1;
    render();
  });

  render();
};

export default function initAdminEvaluationPage() {
  initAdminShellInteractions();
  initModalSystem();
  initStatusChangeConfirmations();
  initPositionCriteriaAutofill();
  initEvaluationTable();
}
