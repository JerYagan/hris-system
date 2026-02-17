import { runPageStatePass } from '../../shared/ui/page-state.js';

const toggleModal = (modal, open) => {
  if (!modal) return;
  modal.classList.toggle('hidden', !open);
  modal.setAttribute('aria-hidden', open ? 'false' : 'true');
};

const initEmployeeSupportPage = () => {
  runPageStatePass({ pageKey: 'employee-support' });

  const messageField = document.getElementById('supportMessage');
  const messageCounter = document.getElementById('supportMessageCounter');
  const detailModal = document.getElementById('supportDetailModal');
  const detailTitle = document.getElementById('supportDetailTitle');
  const detailBody = document.getElementById('supportDetailBody');

  const updateCounter = () => {
    if (!messageField || !messageCounter) return;
    const length = messageField.value.length;
    const maxLength = Number.parseInt(messageField.getAttribute('maxlength') || '3000', 10);
    messageCounter.textContent = `${length} / ${Number.isNaN(maxLength) ? 3000 : maxLength}`;
  };

  if (messageField && messageCounter) {
    updateCounter();
    messageField.addEventListener('input', updateCounter);
  }

  document.querySelectorAll('[data-open-support-detail]').forEach((button) => {
    button.addEventListener('click', () => {
      if (!detailModal) return;
      const subject = button.getAttribute('data-detail-subject') || 'Support Inquiry';
      const body = button.getAttribute('data-detail-body') || 'No details available.';

      if (detailTitle) detailTitle.textContent = subject;
      if (detailBody) detailBody.textContent = body;
      toggleModal(detailModal, true);
    });
  });

  document.querySelectorAll('[data-close-support-detail]').forEach((button) => {
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
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEmployeeSupportPage);
} else {
  initEmployeeSupportPage();
}

export default initEmployeeSupportPage;
