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
  const detailNotes = document.getElementById('supportDetailNotes');
  const detailNotesWrap = document.getElementById('supportDetailNotesWrap');
  const attachmentInput = document.getElementById('supportAttachment');
  const attachmentTrigger = document.getElementById('supportAttachmentTrigger');
  const attachmentFilename = document.getElementById('supportAttachmentFilename');
  const attachmentPreview = document.getElementById('supportAttachmentPreview');
  const attachmentPreviewImage = document.getElementById('supportAttachmentPreviewImage');
  const attachmentPreviewName = document.getElementById('supportAttachmentPreviewName');
  const attachmentPreviewMeta = document.getElementById('supportAttachmentPreviewMeta');
  const supportForm = document.getElementById('supportInquiryForm');
  const feedbackNode = document.getElementById('supportPageFeedback');

  const showFeedbackAlert = () => {
    if (!(feedbackNode instanceof HTMLElement) || !window.Swal) {
      return;
    }

    const state = String(feedbackNode.dataset.state || '').trim().toLowerCase();
    const message = String(feedbackNode.dataset.message || '').trim();
    if (!message || (state !== 'success' && state !== 'error')) {
      return;
    }

    window.Swal.fire({
      icon: state === 'success' ? 'success' : 'error',
      title: state === 'success' ? 'Success' : 'Unable to Continue',
      text: message,
      confirmButtonColor: '#1B5E20',
    });
  };

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

  showFeedbackAlert();

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
        title: 'Submit support ticket?',
        text: 'Your support request will be sent to Admin for review.',
        showCancelButton: true,
        confirmButtonText: 'Submit Ticket',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#1B5E20',
      });

      if (result.isConfirmed) {
        supportForm.dataset.confirmedSubmit = 'true';
        supportForm.submit();
      }
    });
  }

  if (attachmentTrigger && attachmentInput instanceof HTMLInputElement) {
    attachmentTrigger.addEventListener('click', () => {
      attachmentInput.click();
    });
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

  if (attachmentInput instanceof HTMLInputElement) {
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
      if (attachmentPreviewImage) {
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
      }
    });
  }

  document.querySelectorAll('[data-open-support-detail]').forEach((button) => {
    button.addEventListener('click', () => {
      if (!detailModal) return;
      const subject = button.getAttribute('data-detail-subject') || 'Support Inquiry';
      const body = button.getAttribute('data-detail-body') || 'No details available.';
      const notes = button.getAttribute('data-detail-notes') || '';

      if (detailTitle) detailTitle.textContent = subject;
      if (detailBody) detailBody.textContent = body;
      if (detailNotes && detailNotesWrap) {
        detailNotes.textContent = notes;
        detailNotesWrap.classList.toggle('hidden', notes.trim() === '');
      }
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
