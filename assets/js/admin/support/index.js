import { initAdminShellInteractions } from '/hris-system/assets/js/shared/admin-core.js';

const initAdminSupportPage = () => {
  if (document.body?.dataset?.adminSupportPageInitialized === 'true') {
    return;
  }

  if (document.body) {
    document.body.dataset.adminSupportPageInitialized = 'true';
  }

  initAdminShellInteractions();

  const feedbackNode = document.getElementById('supportPageFeedback');
  const manageForm = document.getElementById('adminSupportManageForm');

  if (feedbackNode instanceof HTMLElement && window.Swal) {
    const state = String(feedbackNode.dataset.state || '').trim().toLowerCase();
    const message = String(feedbackNode.dataset.message || '').trim();

    if (message && (state === 'success' || state === 'error')) {
      window.Swal.fire({
        icon: state === 'success' ? 'success' : 'error',
        title: state === 'success' ? 'Update Saved' : 'Update Failed',
        text: message,
        confirmButtonColor: '#0f172a',
      });
    }
  }

  if (!(manageForm instanceof HTMLFormElement)) {
    return;
  }

  manageForm.addEventListener('submit', async (event) => {
    if ((manageForm.dataset.confirmedSubmit || '') === 'true') {
      return;
    }

    event.preventDefault();

    if (!window.Swal) {
      manageForm.submit();
      return;
    }

    const statusField = manageForm.querySelector('select[name="ticket_status"]');
    const nextStatus = statusField instanceof HTMLSelectElement
      ? statusField.options[statusField.selectedIndex]?.text || 'the selected status'
      : 'the selected status';

    const result = await window.Swal.fire({
      icon: 'question',
      title: 'Confirm ticket update?',
      text: `This will update the ticket to ${nextStatus}.`,
      showCancelButton: true,
      confirmButtonText: 'Save Update',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#0f172a',
    });

    if (result.isConfirmed) {
      manageForm.dataset.confirmedSubmit = 'true';
      manageForm.submit();
    }
  });
};

export default initAdminSupportPage;
