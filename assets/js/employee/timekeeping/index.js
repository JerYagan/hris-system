import { runPageStatePass } from '../../shared/ui/page-state.js';

const toggleModal = (modal, open) => {
  if (!modal) return;
  modal.classList.toggle('hidden', !open);
  modal.setAttribute('aria-hidden', open ? 'false' : 'true');
};

const wireModal = ({ openSelector, closeSelector, modalId, onOpen }) => {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  document.querySelectorAll(openSelector).forEach((button) => {
    button.addEventListener('click', () => {
      if (typeof onOpen === 'function') {
        onOpen(button);
      }
      toggleModal(modal, true);
    });
  });

  document.querySelectorAll(closeSelector).forEach((button) => {
    button.addEventListener('click', () => toggleModal(modal, false));
  });

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      toggleModal(modal, false);
    }
  });
};

const wireAdjustmentModalData = () => {
  const attendanceIdInput = document.getElementById('adjustmentAttendanceLogId');
  const attendanceInfo = document.getElementById('adjustmentAttendanceInfo');

  return (button) => {
    const attendanceId = button.getAttribute('data-attendance-id') || '';
    const attendanceDate = button.getAttribute('data-attendance-date') || '';
    const currentTimeIn = button.getAttribute('data-current-time-in') || '-';
    const currentTimeOut = button.getAttribute('data-current-time-out') || '-';

    if (attendanceIdInput) {
      attendanceIdInput.value = attendanceId;
    }

    if (attendanceInfo) {
      const dateLabel = attendanceDate ? attendanceDate : 'Manual request';
      attendanceInfo.textContent = `${dateLabel} · In: ${currentTimeIn} · Out: ${currentTimeOut}`;
    }
  };
};

const initEmployeeTimekeepingPage = () => {
  runPageStatePass({ pageKey: 'employee-timekeeping' });

  wireModal({
    openSelector: '[data-open-leave]',
    closeSelector: '[data-close-leave]',
    modalId: 'leaveModal',
  });

  wireModal({
    openSelector: '[data-open-overtime]',
    closeSelector: '[data-close-overtime]',
    modalId: 'overtimeModal',
  });

  wireModal({
    openSelector: '[data-open-adjustment]',
    closeSelector: '[data-close-adjustment]',
    modalId: 'adjustmentModal',
    onOpen: wireAdjustmentModalData(),
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    ['leaveModal', 'overtimeModal', 'adjustmentModal'].forEach((modalId) => {
      const modal = document.getElementById(modalId);
      if (modal && !modal.classList.contains('hidden')) {
        toggleModal(modal, false);
      }
    });
  });

  const quickAction = new URLSearchParams(window.location.search).get('quick_action');
  if (quickAction === 'create-leave') {
    const leaveModal = document.getElementById('leaveModal');
    toggleModal(leaveModal, true);
  }
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEmployeeTimekeepingPage);
} else {
  initEmployeeTimekeepingPage();
}

export default initEmployeeTimekeepingPage;
