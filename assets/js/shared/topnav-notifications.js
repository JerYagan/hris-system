(() => {
  const formatBadge = (count) => {
    if (count <= 0) {
      return '0';
    }
    return count > 99 ? '99+' : String(count);
  };

  const parseUnreadCount = (root) => {
    const badge = root.querySelector('[data-topnav-unread-badge]');
    if (!badge) {
      return 0;
    }

    const raw = Number.parseInt(String(badge.getAttribute('data-unread-count') || '0'), 10);
    return Number.isFinite(raw) && raw > 0 ? raw : 0;
  };

  const setUnreadCount = (root, count) => {
    const normalized = Math.max(0, Number.isFinite(count) ? count : 0);
    const badge = root.querySelector('[data-topnav-unread-badge]');
    const unreadText = root.querySelector('[data-topnav-unread-text]');

    if (badge) {
      badge.setAttribute('data-unread-count', String(normalized));
      badge.textContent = formatBadge(normalized);
      if (normalized > 0) {
        badge.classList.remove('hidden');
      } else {
        badge.classList.add('hidden');
      }
    }

    if (unreadText) {
      unreadText.textContent = String(normalized);
    }
  };

  const markItemReadUi = (item) => {
    item.setAttribute('data-notification-read', '1');
    const status = item.querySelector('[data-topnav-item-status]');
    if (status) {
      status.textContent = 'Read';
      status.classList.remove('bg-amber-100', 'text-amber-700');
      status.classList.add('bg-slate-100', 'text-slate-600');
    }

    item.classList.remove('border-amber-200', 'bg-amber-50/60');
    item.classList.add('border-slate-200', 'bg-white');
  };

  const closeModal = (modal) => {
    if (!modal) {
      return;
    }
    modal.classList.add('hidden');
  };

  const openModal = (modal) => {
    if (!modal) {
      return;
    }
    modal.classList.remove('hidden');
  };

  const markRead = async (root, notificationId) => {
    if (!notificationId) {
      return { ok: false };
    }

    const endpoint = String(root.getAttribute('data-endpoint') || '').trim();
    const actionField = String(root.getAttribute('data-action-field') || 'action').trim();
    const markReadAction = String(root.getAttribute('data-mark-read-action') || '').trim();
    const idField = String(root.getAttribute('data-id-field') || 'notification_id').trim();
    const csrfField = String(root.getAttribute('data-csrf-field') || 'csrf_token').trim();
    const csrfToken = String(root.getAttribute('data-csrf-token') || '').trim();

    if (!endpoint || !markReadAction) {
      return { ok: false };
    }

    const formData = new FormData();
    formData.set(actionField, markReadAction);
    formData.set(idField, notificationId);
    formData.set('async', '1');
    if (csrfToken !== '') {
      formData.set(csrfField, csrfToken);
    }

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
        },
        credentials: 'same-origin',
      });

      const contentType = String(response.headers.get('content-type') || '').toLowerCase();
      if (contentType.includes('application/json')) {
        const payload = await response.json();
        if (payload && payload.ok === true) {
          return { ok: true, unreadCount: Number.parseInt(String(payload.unread_count ?? ''), 10) };
        }
      }

      return { ok: response.ok };
    } catch (_error) {
      return { ok: false };
    }
  };

  const initRoot = (root) => {
    const trigger = root.querySelector('[data-topnav-notification-trigger]');
    const listModal = root.querySelector('[data-topnav-list-modal]');
    const detailModal = root.querySelector('[data-topnav-detail-modal]');
    const items = Array.from(root.querySelectorAll('[data-topnav-item]'));

    if (!trigger || !listModal || !detailModal) {
      return;
    }

    trigger.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      closeModal(detailModal);
      openModal(listModal);
    });

    root.querySelectorAll('[data-topnav-close="list"]').forEach((button) => {
      button.addEventListener('click', () => closeModal(listModal));
    });

    root.querySelectorAll('[data-topnav-close="detail"]').forEach((button) => {
      button.addEventListener('click', () => closeModal(detailModal));
    });

    document.addEventListener('click', (event) => {
      const target = event.target;
      if (!(target instanceof Node)) {
        return;
      }

      if (!root.contains(target)) {
        closeModal(listModal);
        closeModal(detailModal);
      }
    });

    const detailTitle = detailModal.querySelector('[data-topnav-detail-title]');
    const detailBody = detailModal.querySelector('[data-topnav-detail-body]');
    const detailCreated = detailModal.querySelector('[data-topnav-detail-created]');
    const detailStatus = detailModal.querySelector('[data-topnav-detail-status]');
    const detailLink = detailModal.querySelector('[data-topnav-detail-link]');
    const detailLinkEmpty = detailModal.querySelector('[data-topnav-detail-link-empty]');

    items.forEach((item) => {
      item.addEventListener('click', async () => {
        const notificationId = String(item.getAttribute('data-notification-id') || '').trim();
        const title = String(item.getAttribute('data-notification-title') || 'Notification');
        const body = String(item.getAttribute('data-notification-body') || 'No details available.');
        const created = String(item.getAttribute('data-notification-created') || '-');
        const link = String(item.getAttribute('data-notification-link') || '').trim();
        const isRead = String(item.getAttribute('data-notification-read') || '0') === '1';

        if (detailTitle) {
          detailTitle.textContent = title;
        }
        if (detailBody) {
          detailBody.textContent = body;
        }
        if (detailCreated) {
          detailCreated.textContent = created;
        }
        if (detailStatus) {
          detailStatus.textContent = isRead ? 'Read' : 'Unread';
          detailStatus.classList.toggle('bg-amber-100', !isRead);
          detailStatus.classList.toggle('text-amber-700', !isRead);
          detailStatus.classList.toggle('bg-slate-100', isRead);
          detailStatus.classList.toggle('text-slate-600', isRead);
        }

        if (detailLink && detailLinkEmpty) {
          if (link !== '') {
            detailLink.href = link;
            detailLink.classList.remove('hidden');
            detailLinkEmpty.classList.add('hidden');
          } else {
            detailLink.href = '#';
            detailLink.classList.add('hidden');
            detailLinkEmpty.classList.remove('hidden');
          }
        }

        closeModal(listModal);
        openModal(detailModal);

        if (!isRead) {
          const result = await markRead(root, notificationId);
          if (result.ok) {
            markItemReadUi(item);
            if (detailStatus) {
              detailStatus.textContent = 'Read';
              detailStatus.classList.remove('bg-amber-100', 'text-amber-700');
              detailStatus.classList.add('bg-slate-100', 'text-slate-600');
            }

            if (Number.isFinite(result.unreadCount)) {
              setUnreadCount(root, result.unreadCount);
            } else {
              setUnreadCount(root, parseUnreadCount(root) - 1);
            }
          }
        }
      });
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-topnav-notifications]').forEach(initRoot);

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape') {
        return;
      }
      document.querySelectorAll('[data-topnav-list-modal], [data-topnav-detail-modal]').forEach((modal) => {
        modal.classList.add('hidden');
      });
    });
  });
})();
