import { runPageStatePass } from '../../shared/ui/page-state.js';

const toggleModal = (modal, open) => {
  if (!modal) return;
  modal.classList.toggle('hidden', !open);
  modal.setAttribute('aria-hidden', open ? 'false' : 'true');
};

const initEmployeeNotificationsPage = () => {
  runPageStatePass({ pageKey: 'employee-notifications' });

  const modal = document.getElementById('notificationModal');
  const modalTitle = document.getElementById('notificationModalTitle');
  const modalMeta = document.getElementById('notificationModalMeta');
  const modalHeading = document.getElementById('notificationModalHeading');
  const modalBody = document.getElementById('notificationModalBody');
  const modalLink = document.getElementById('notificationModalLink');
  const unreadCountEl = document.getElementById('notificationUnreadCount');
  const readCountEl = document.getElementById('notificationReadCount');
  const totalCountEl = document.getElementById('notificationTotalCount');
  const markAllReadButton = document.getElementById('markAllReadButton');
  const statusFilter = document.getElementById('notifStatus');

  const parseCount = (element) => {
    if (!element) return 0;
    return Number.parseInt(element.textContent || '0', 10) || 0;
  };

  const syncMarkAllState = () => {
    if (!markAllReadButton || !unreadCountEl) return;
    const unread = parseCount(unreadCountEl);
    markAllReadButton.disabled = unread <= 0;
  };

  const applyReadUIState = (row) => {
    if (!row) return;

    row.dataset.isRead = '1';
    row.classList.remove('bg-green-50');

    const trigger = row.querySelector('[data-open-notification]');
    if (trigger) {
      trigger.setAttribute('data-notification-is-read', '1');
    }

    row.querySelector('[data-notification-unread-dot]')?.classList.add('hidden');
    row.querySelector('[data-notification-mark-form]')?.classList.add('hidden');
    row.querySelector('[data-notification-read-label]')?.classList.remove('hidden');

    if (statusFilter instanceof HTMLSelectElement && statusFilter.value === 'unread') {
      row.classList.add('hidden');
    }
  };

  const updateCountersAfterRead = (unreadCountFromApi) => {
    if (!unreadCountEl || !readCountEl || !totalCountEl) return;

    const total = parseCount(totalCountEl);
    const currentUnread = parseCount(unreadCountEl);
    const currentRead = parseCount(readCountEl);

    const nextUnread = Number.isFinite(unreadCountFromApi) ? Math.max(0, unreadCountFromApi) : Math.max(0, currentUnread - 1);
    const consumed = currentUnread - nextUnread;
    const nextRead = Math.min(total, Math.max(0, currentRead + (consumed > 0 ? consumed : 1)));

    unreadCountEl.textContent = String(nextUnread);
    readCountEl.textContent = String(nextRead);
    syncMarkAllState();
  };

  const markNotificationAsRead = async (notificationId) => {
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    const csrfToken = csrfInput instanceof HTMLInputElement ? csrfInput.value : '';
    if (!notificationId || !csrfToken) return null;

    const body = new URLSearchParams();
    body.set('csrf_token', csrfToken);
    body.set('action', 'mark_notification_read');
    body.set('notification_id', notificationId);
    body.set('async', '1');

    const response = await fetch('notifications.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: body.toString(),
      credentials: 'same-origin',
    });

    if (!response.ok) {
      return null;
    }

    const result = await response.json().catch(() => null);
    if (!result || result.ok !== true) {
      return null;
    }

    return result;
  };

  document.querySelectorAll('[data-notification-mark-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formElement = event.currentTarget;
      if (!(formElement instanceof HTMLFormElement)) return;

      const row = formElement.closest('[data-notification-item]');
      const notificationId = row?.getAttribute('data-notification-id') || '';
      const result = await markNotificationAsRead(notificationId);
      if (!result) return;

      applyReadUIState(row);
      updateCountersAfterRead(Number.parseInt(String(result.unread_count ?? ''), 10));
    });
  });

  document.querySelectorAll('[data-open-notification]').forEach((button) => {
    button.addEventListener('click', async () => {
      if (!modal) return;

      const row = button.closest('[data-notification-item]');
      const notificationId = button.getAttribute('data-notification-id') || '';
      const isRead = button.getAttribute('data-notification-is-read') === '1';

      const title = button.getAttribute('data-notification-title') || 'Notification Details';
      const body = button.getAttribute('data-notification-body') || 'No details available.';
      const category = button.getAttribute('data-notification-category') || 'General';
      const created = button.getAttribute('data-notification-created') || '';
      const link = button.getAttribute('data-notification-link') || '';

      if (modalTitle) modalTitle.textContent = 'Notification Details';
      if (modalMeta) modalMeta.textContent = `${category}${created ? ` · ${created}` : ''}`;
      if (modalHeading) modalHeading.textContent = title;
      if (modalBody) modalBody.textContent = body;

      if (modalLink) {
        if (link) {
          modalLink.setAttribute('href', link);
          modalLink.classList.remove('hidden');
        } else {
          modalLink.setAttribute('href', '#');
          modalLink.classList.add('hidden');
        }
      }

      toggleModal(modal, true);

      if (!isRead && notificationId) {
        const result = await markNotificationAsRead(notificationId);
        if (result) {
          applyReadUIState(row);
          updateCountersAfterRead(Number.parseInt(String(result.unread_count ?? ''), 10));
        }
      }
    });
  });

  document.querySelectorAll('[data-close-notification]').forEach((button) => {
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

  syncMarkAllState();
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEmployeeNotificationsPage);
} else {
  initEmployeeNotificationsPage();
}

export default initEmployeeNotificationsPage;
