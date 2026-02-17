import { runPageStatePass } from '../../shared/ui/page-state.js';

const toggleModal = (modal, open) => {
  if (!modal) return;
  modal.classList.toggle('hidden', !open);
  modal.setAttribute('aria-hidden', open ? 'false' : 'true');
};

const initEmployeePraisePage = () => {
  runPageStatePass({ pageKey: 'employee-praise' });

  const detailModal = document.getElementById('praiseDetailModal');
  const detailTitle = document.getElementById('praiseDetailTitle');
  const detailBody = document.getElementById('praiseDetailBody');
  const remarksField = document.getElementById('selfRemarks');
  const remarksCounter = document.getElementById('selfRemarksCounter');

  const updateRemarksCounter = () => {
    if (!remarksField || !remarksCounter) return;
    const currentLength = remarksField.value.length;
    const maxLength = Number.parseInt(remarksField.getAttribute('maxlength') || '2000', 10);
    remarksCounter.textContent = `${currentLength} / ${Number.isNaN(maxLength) ? 2000 : maxLength}`;
  };

  if (remarksField && remarksCounter) {
    updateRemarksCounter();
    remarksField.addEventListener('input', updateRemarksCounter);
  }

  document.querySelectorAll('[data-open-detail]').forEach((button) => {
    button.addEventListener('click', () => {
      if (!detailModal) return;

      const title = button.getAttribute('data-detail-title') || 'Details';
      const body = button.getAttribute('data-detail-body') || 'No details available.';

      if (detailTitle) {
        detailTitle.textContent = title;
      }
      if (detailBody) {
        detailBody.textContent = body;
      }

      toggleModal(detailModal, true);
    });
  });

  document.querySelectorAll('[data-close-detail]').forEach((button) => {
    button.addEventListener('click', () => toggleModal(detailModal, false));
  });

  detailModal?.addEventListener('click', (event) => {
    if (event.target === detailModal) {
      toggleModal(detailModal, false);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      toggleModal(detailModal, false);
    }
  });

  const quickAction = new URLSearchParams(window.location.search).get('quick_action');
  if (quickAction === 'submit-self-evaluation') {
    const selfEvaluationForm = document.getElementById('selfEvaluationForm');
    selfEvaluationForm?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    window.setTimeout(() => {
      const firstInput = document.getElementById('selfCycleId');
      firstInput?.focus();
    }, 300);
  }
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEmployeePraisePage);
} else {
  initEmployeePraisePage();
}

export default initEmployeePraisePage;
