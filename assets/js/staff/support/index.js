const initStaffSupportPage = () => {
  const feedbackNode = document.getElementById('supportPageFeedback');
  const manageForm = document.getElementById('staffSupportManageForm');

  if (feedbackNode instanceof HTMLElement && window.Swal) {
    const state = String(feedbackNode.dataset.state || '').trim().toLowerCase();
    const message = String(feedbackNode.dataset.message || '').trim();

    if (message && (state === 'success' || state === 'error')) {
      window.Swal.fire({
        icon: state === 'success' ? 'success' : 'error',
        title: state === 'success' ? 'Update Sent' : 'Update Failed',
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

    const result = await window.Swal.fire({
      icon: 'question',
      title: 'Send staff update?',
      text: 'This update will be recorded and sent to admin (and requester if selected).',
      showCancelButton: true,
      confirmButtonText: 'Send Update',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#0f172a',
    });

    if (result.isConfirmed) {
      manageForm.dataset.confirmedSubmit = 'true';
      manageForm.submit();
    }
  });
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initStaffSupportPage);
} else {
  initStaffSupportPage();
}

export default initStaffSupportPage;
