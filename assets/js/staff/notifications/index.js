(function () {
  const searchInput = document.getElementById('staffNotificationsSearch');
  const statusFilter = document.getElementById('staffNotificationsStatusFilter');
  const rows = Array.from(document.querySelectorAll('[data-staff-notif-search]'));
  const emptyRow = document.getElementById('staffNotificationsFilterEmpty');
  const modal = document.getElementById('staffNotificationModal');
  const modalBackdrop = document.getElementById('staffNotificationModalBackdrop');
  const modalClose = document.getElementById('staffNotificationModalClose');
  const modalDone = document.getElementById('staffNotificationModalDone');
  const modalTitle = document.getElementById('staffNotificationModalTitle');
  const modalMessage = document.getElementById('staffNotificationModalMessage');
  const modalCreated = document.getElementById('staffNotificationModalCreated');
  const modalStatus = document.getElementById('staffNotificationModalStatus');
  const modalCategory = document.getElementById('staffNotificationModalCategory');
  const modalLink = document.getElementById('staffNotificationModalLink');
  const unreadCountEl = document.getElementById('staffNotificationUnreadCount');
  const readCountEl = document.getElementById('staffNotificationReadCount');
  const totalCountEl = document.getElementById('staffNotificationTotalCount');

  const parseCount = (element) => {
    if (!element) return 0;
    return Number.parseInt(element.textContent || '0', 10) || 0;
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
  };

  const applyReadUIState = (row) => {
    if (!row) {
      return;
    }

    row.dataset.isRead = '1';
    row.setAttribute('data-staff-notif-status', 'Read');
    row.setAttribute('data-notification-status', 'Read');
    row.classList.remove('bg-emerald-50/70');

    const statusPill = row.querySelector('[data-notification-status-pill]');
    if (statusPill) {
      statusPill.textContent = 'Read';
      statusPill.className = 'inline-flex items-center justify-center rounded-full px-2.5 py-1 text-xs bg-emerald-100 text-emerald-800';
    }

    if ((statusFilter?.value || '').trim().toLowerCase() === 'unread') {
      row.classList.add('hidden');
    }
  };

  const markNotificationAsRead = async (notificationId) => {
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    const csrfToken = csrfInput instanceof HTMLInputElement ? csrfInput.value : '';
    if (!notificationId || !csrfToken) {
      return null;
    }

    const body = new URLSearchParams();
    body.set('form_action', 'mark_notification_read');
    body.set('notification_id', notificationId);
    body.set('csrf_token', csrfToken);
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

  const openModal = async (row) => {
    if (!modal || !row) {
      return;
    }

    const title = row.getAttribute('data-notification-title') || 'Notification';
    const message = row.getAttribute('data-notification-message') || 'No details available.';
    const created = row.getAttribute('data-notification-created') || '-';
    const status = row.getAttribute('data-notification-status') || 'Read';
    const category = row.getAttribute('data-notification-category') || 'General';
    const link = row.getAttribute('data-notification-link') || '';
    const isUnread = status.toLowerCase() === 'unread';

    if (modalTitle) {
      modalTitle.textContent = title;
    }
    if (modalMessage) {
      modalMessage.textContent = message;
    }
    if (modalCreated) {
      modalCreated.textContent = created;
    }
    if (modalStatus) {
      modalStatus.textContent = status;
      modalStatus.className = `inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${isUnread ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'}`;
    }
    if (modalCategory) {
      modalCategory.textContent = category;
    }
    if (modalLink) {
      if (link) {
        modalLink.href = link;
        modalLink.classList.remove('hidden');
        modalLink.classList.add('inline-flex');
      } else {
        modalLink.href = '#';
        modalLink.classList.add('hidden');
        modalLink.classList.remove('inline-flex');
      }
    }

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');

    const notificationId = row.getAttribute('data-notification-id') || '';
    const isRead = row.getAttribute('data-is-read') === '1';
    if (!isRead && notificationId) {
      const result = await markNotificationAsRead(notificationId);
      if (result) {
        applyReadUIState(row);
        updateCountersAfterRead(Number.parseInt(String(result.unread_count ?? ''), 10));

        if (modalStatus) {
          modalStatus.textContent = 'Read';
          modalStatus.className = 'inline-flex rounded-full px-2.5 py-1 text-xs font-medium bg-emerald-100 text-emerald-700';
        }
      }
    }
  };

  const closeModal = () => {
    if (!modal) {
      return;
    }

    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  };

  const applyFilters = () => {
    if (!rows.length) {
      if (emptyRow) {
        emptyRow.classList.add('hidden');
      }
      return;
    }

    const needle = (searchInput?.value || '').trim().toLowerCase();
    const status = (statusFilter?.value || '').trim().toLowerCase();

    let visibleCount = 0;
    rows.forEach((row) => {
      const rowSearch = (row.getAttribute('data-staff-notif-search') || '').toLowerCase();
      const rowStatus = (row.getAttribute('data-staff-notif-status') || '').toLowerCase();

      const matchesSearch = needle === '' || rowSearch.includes(needle);
      const matchesStatus = status === '' || rowStatus === status;
      const visible = matchesSearch && matchesStatus;

      row.classList.toggle('hidden', !visible);
      if (visible) {
        visibleCount += 1;
      }
    });

    if (emptyRow) {
      emptyRow.classList.toggle('hidden', visibleCount > 0);
    }
  };

  searchInput?.addEventListener('input', applyFilters);
  statusFilter?.addEventListener('change', applyFilters);
  applyFilters();

  const detailRows = Array.from(document.querySelectorAll('[data-staff-notification-row]'));
  detailRows.forEach((row) => {
    row.addEventListener('click', (event) => {
      const target = event.target;
      if (target instanceof Element && target.closest('a, button, form, input, select, textarea')) {
        return;
      }

      openModal(row);
    });

    row.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        openModal(row);
      }
    });
  });

  modalBackdrop?.addEventListener('click', closeModal);
  modalClose?.addEventListener('click', closeModal);
  modalDone?.addEventListener('click', closeModal);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
      closeModal();
    }
  });
})();
