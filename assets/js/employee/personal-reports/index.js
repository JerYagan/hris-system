import { runPageStatePass } from '../../shared/ui/page-state.js';

const toggleModal = (modal, open) => {
  if (!modal) return;
  modal.classList.toggle('hidden', !open);
  modal.setAttribute('aria-hidden', open ? 'false' : 'true');
};

const initEmployeePersonalReportsPage = () => {
  runPageStatePass({ pageKey: 'employee-personal-reports' });

  const modal = document.getElementById('requestReportModal');

  document.querySelectorAll('[data-open-request-report]').forEach((button) => {
    button.addEventListener('click', () => toggleModal(modal, true));
  });

  document.querySelectorAll('[data-close-request-report]').forEach((button) => {
    button.addEventListener('click', () => toggleModal(modal, false));
  });

  modal?.addEventListener('click', (event) => {
    if (event.target === modal) {
      toggleModal(modal, false);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      toggleModal(modal, false);
    }
  });

  const quickAction = new URLSearchParams(window.location.search).get('quick_action');
  if (quickAction === 'generate-report') {
    toggleModal(modal, true);
  }
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEmployeePersonalReportsPage);
} else {
  initEmployeePersonalReportsPage();
}

export default initEmployeePersonalReportsPage;
