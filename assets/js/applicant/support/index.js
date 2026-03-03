const initApplicantSupportAttachment = () => {
  const attachmentInput = document.getElementById('applicantSupportAttachment');
  const attachmentTrigger = document.getElementById('applicantSupportAttachmentTrigger');
  const attachmentFilename = document.getElementById('applicantSupportAttachmentFilename');
  const attachmentPreview = document.getElementById('applicantSupportAttachmentPreview');
  const attachmentPreviewImage = document.getElementById('applicantSupportAttachmentPreviewImage');
  const attachmentPreviewName = document.getElementById('applicantSupportAttachmentPreviewName');
  const attachmentPreviewMeta = document.getElementById('applicantSupportAttachmentPreviewMeta');
  const supportForm = document.getElementById('applicantSupportForm');
  const feedbackNode = document.getElementById('supportPageFeedback');

  if (!(attachmentInput instanceof HTMLInputElement)) {
    return;
  }

  const bytesToSize = (value) => {
    if (!Number.isFinite(value) || value <= 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = value;
    let unit = 0;
    while (size >= 1024 && unit < units.length - 1) {
      size /= 1024;
      unit += 1;
    }
    return `${size.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
  };

  if (feedbackNode instanceof HTMLElement && window.Swal) {
    const state = String(feedbackNode.dataset.state || '').trim().toLowerCase();
    const message = String(feedbackNode.dataset.message || '').trim();
    if (message && (state === 'success' || state === 'error')) {
      window.Swal.fire({
        icon: state === 'success' ? 'success' : 'error',
        title: state === 'success' ? 'Success' : 'Unable to Continue',
        text: message,
        confirmButtonColor: '#1B5E20',
      });
    }
  }

  if (supportForm instanceof HTMLFormElement) {
    supportForm.addEventListener('submit', async (event) => {
      if ((supportForm.dataset.confirmedSubmit || '') === 'true') {
        return;
      }

      event.preventDefault();

      if (!window.Swal) {
        supportForm.submit();
        return;
      }

      const result = await window.Swal.fire({
        icon: 'question',
        title: 'Send support inquiry?',
        text: 'Your inquiry will be submitted to HR support.',
        showCancelButton: true,
        confirmButtonText: 'Send Message',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#1B5E20',
      });

      if (result.isConfirmed) {
        supportForm.dataset.confirmedSubmit = 'true';
        supportForm.submit();
      }
    });
  }

  attachmentTrigger?.addEventListener('click', () => {
    attachmentInput.click();
  });

  attachmentInput.addEventListener('change', () => {
    const file = attachmentInput.files && attachmentInput.files[0] ? attachmentInput.files[0] : null;

    if (!file) {
      if (attachmentFilename) attachmentFilename.textContent = 'No file selected.';
      if (attachmentPreview) attachmentPreview.classList.add('hidden');
      if (attachmentPreviewImage) {
        attachmentPreviewImage.classList.add('hidden');
        attachmentPreviewImage.removeAttribute('src');
      }
      return;
    }

    if (attachmentFilename) {
      attachmentFilename.textContent = file.name;
    }

    if (attachmentPreview && attachmentPreviewName && attachmentPreviewMeta) {
      attachmentPreview.classList.remove('hidden');
      attachmentPreviewName.textContent = file.name;
      attachmentPreviewMeta.textContent = `${bytesToSize(file.size)} • ${file.type || 'Unknown type'}`;
    }

    const isImage = /^image\//i.test(file.type);
    if (!attachmentPreviewImage) {
      return;
    }

    if (!isImage) {
      attachmentPreviewImage.classList.add('hidden');
      attachmentPreviewImage.removeAttribute('src');
      return;
    }

    const reader = new FileReader();
    reader.onload = () => {
      attachmentPreviewImage.src = String(reader.result || '');
      attachmentPreviewImage.classList.remove('hidden');
    };
    reader.readAsDataURL(file);
  });
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApplicantSupportAttachment);
} else {
  initApplicantSupportAttachment();
}
