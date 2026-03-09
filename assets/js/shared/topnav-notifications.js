(() => {
  const CLOSE_TOPNAV_EVENT = 'hris:close-topnav-notifications';
  const REQUEST_SIDEBAR_CLOSE_EVENT = 'hris:request-close-sidebar';
  const PROFILE_OPENED_EVENT = 'hris:profile-menu-opened';
  const SIDEBAR_OPENED_EVENT = 'hris:sidebar-opened';
  const AUX_MENU_OPENED_EVENT = 'hris:applicant-menu-opened';
  const SNAPSHOT_REFRESH_INTERVAL = 60000;

  const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

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

  const dispatchUiEvent = (name, detail = {}) => {
    document.dispatchEvent(new CustomEvent(name, { detail }));
  };

  const closeCompanionMenus = () => {
    [
      'profileMenu',
      'applicantUserMenu',
      'applicantRecruitmentMenu',
      'applicantMobileMenu',
    ].forEach((id) => {
      const element = document.getElementById(id);
      if (element) {
        element.classList.add('hidden');
      }
    });
  };

  const buildSnapshotUrl = (root) => {
    const endpoint = String(root.getAttribute('data-endpoint') || '').trim();
    const snapshotAction = String(root.getAttribute('data-snapshot-action') || 'topnav_snapshot').trim();
    if (!endpoint || !snapshotAction) {
      return '';
    }

    const url = new URL(endpoint, window.location.href);
    url.searchParams.set('action', snapshotAction);
    url.searchParams.set('async', '1');
    url.searchParams.set('_ts', String(Date.now()));
    return url.toString();
  };

  const formatPhilippinesTimestamp = (value) => {
    const raw = String(value ?? '').trim();
    if (raw === '') {
      return '-';
    }

    const date = new Date(raw);
    if (Number.isNaN(date.getTime())) {
      return raw;
    }

    return `${new Intl.DateTimeFormat('en-PH', {
      timeZone: 'Asia/Manila',
      month: 'short',
      day: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
    }).format(date)} PST`;
  };

  const normalizeNotification = (item) => {
    const createdAtRaw = String(item?.created_at || '').trim();
    const createdAtLabel = String(item?.created_at_label || '').trim();

    return {
      id: String(item?.id || ''),
      title: String(item?.title || 'Notification'),
      body: String(item?.body || 'No details available.'),
      link_url: String(item?.link_url || ''),
      category: String(item?.category || 'general'),
      created_at_label: createdAtRaw !== '' ? formatPhilippinesTimestamp(createdAtRaw) : (createdAtLabel || '-'),
      is_read: Boolean(item?.is_read),
    };
  };

  const renderItems = (root, items) => {
    const itemsContainer = root.querySelector('[data-topnav-items]');
    if (!itemsContainer) {
      return;
    }

    const normalizedItems = Array.isArray(items) ? items.map(normalizeNotification).filter((item) => item.id !== '') : [];
    root.__topnavItems = normalizedItems;

    if (normalizedItems.length === 0) {
      itemsContainer.innerHTML = '<div class="rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No notifications available.</div>';
      return;
    }

    itemsContainer.innerHTML = normalizedItems.map((item) => {
      const isRead = Boolean(item.is_read);
      return `
        <button
          type="button"
          data-topnav-item
          data-notification-id="${escapeHtml(item.id)}"
          data-notification-title="${escapeHtml(item.title)}"
          data-notification-body="${escapeHtml(item.body)}"
          data-notification-link="${escapeHtml(item.link_url)}"
          data-notification-category="${escapeHtml(item.category)}"
          data-notification-created="${escapeHtml(item.created_at_label)}"
          data-notification-read="${isRead ? '1' : '0'}"
          class="mb-2 block w-full rounded-xl border px-4 py-3 text-left transition hover:bg-slate-50 ${isRead ? 'border-slate-200 bg-white' : 'border-amber-200 bg-amber-50/60'}"
        >
          <div class="flex items-start justify-between gap-3">
            <p class="text-sm font-medium text-slate-800">${escapeHtml(item.title)}</p>
            <span data-topnav-item-status class="inline-flex rounded-full px-2 py-0.5 text-[11px] ${isRead ? 'bg-slate-100 text-slate-600' : 'bg-amber-100 text-amber-700'}">${isRead ? 'Read' : 'Unread'}</span>
          </div>
          <p class="mt-1 line-clamp-2 text-xs text-slate-600">${escapeHtml(item.body)}</p>
          <p class="mt-2 text-[11px] text-slate-400">${escapeHtml(item.created_at_label)}</p>
        </button>
      `;
    }).join('');
  };

  const updateDetailModal = (detailModal, notification) => {
    if (!detailModal || !notification) {
      return;
    }

    const detailTitle = detailModal.querySelector('[data-topnav-detail-title]');
    const detailBody = detailModal.querySelector('[data-topnav-detail-body]');
    const detailCreated = detailModal.querySelector('[data-topnav-detail-created]');
    const detailStatus = detailModal.querySelector('[data-topnav-detail-status]');
    const detailLink = detailModal.querySelector('[data-topnav-detail-link]');
    const detailLinkEmpty = detailModal.querySelector('[data-topnav-detail-link-empty]');

    if (detailTitle) {
      detailTitle.textContent = notification.title;
    }
    if (detailBody) {
      detailBody.textContent = notification.body;
    }
    if (detailCreated) {
      detailCreated.textContent = notification.created_at_label;
    }
    if (detailStatus) {
      detailStatus.textContent = notification.is_read ? 'Read' : 'Unread';
      detailStatus.classList.toggle('bg-amber-100', !notification.is_read);
      detailStatus.classList.toggle('text-amber-700', !notification.is_read);
      detailStatus.classList.toggle('bg-slate-100', notification.is_read);
      detailStatus.classList.toggle('text-slate-600', notification.is_read);
    }

    if (detailLink && detailLinkEmpty) {
      if (notification.link_url !== '') {
        detailLink.href = notification.link_url;
        detailLink.classList.remove('hidden');
        detailLinkEmpty.classList.add('hidden');
      } else {
        detailLink.href = '#';
        detailLink.classList.add('hidden');
        detailLinkEmpty.classList.remove('hidden');
      }
    }

    detailModal.setAttribute('data-active-notification-id', notification.id);
  };

  const fetchSnapshot = async (root) => {
    const snapshotUrl = buildSnapshotUrl(root);
    if (!snapshotUrl) {
      return null;
    }

    try {
      const response = await fetch(snapshotUrl, {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
        },
        credentials: 'same-origin',
        cache: 'no-store',
      });

      if (!response.ok) {
        return null;
      }

      const payload = await response.json();
      if (!payload || payload.ok !== true) {
        return null;
      }

      return {
        unreadCount: Number.parseInt(String(payload.unread_count ?? ''), 10),
        items: Array.isArray(payload.items) ? payload.items.map(normalizeNotification) : [],
      };
    } catch (_error) {
      return null;
    }
  };

  const initRealtimeSync = async (root, syncSnapshot) => {
    const supabaseUrl = String(root.getAttribute('data-supabase-url') || '').trim();
    const anonKey = String(root.getAttribute('data-supabase-anon-key') || '').trim();
    const accessToken = String(root.getAttribute('data-realtime-access-token') || '').trim();
    const userId = String(root.getAttribute('data-user-id') || '').trim();

    const startPollingFallback = () => {
      if (root.__topnavPollingIntervalId) {
        return;
      }

      const intervalId = window.setInterval(() => {
        syncSnapshot({ preserveDetail: true }).catch(() => {});
      }, SNAPSHOT_REFRESH_INTERVAL);
      root.__topnavPollingIntervalId = intervalId;
    };

    if (!supabaseUrl || !anonKey || !accessToken || !userId) {
      startPollingFallback();
      return;
    }

    try {
      const { createClient } = await import('https://cdn.jsdelivr.net/npm/@supabase/supabase-js/+esm');
      const client = createClient(supabaseUrl, anonKey, {
        auth: {
          persistSession: false,
          autoRefreshToken: false,
          detectSessionInUrl: false,
        },
        accessToken: async () => accessToken,
      });

      const channel = client
        .channel(`topnav-notifications-${userId}`)
        .on(
          'postgres_changes',
          {
            event: '*',
            schema: 'public',
            table: 'notifications',
            filter: `recipient_user_id=eq.${userId}`,
          },
          () => {
            syncSnapshot({ preserveDetail: true }).catch(() => {});
          }
        )
        .subscribe((status) => {
          if (status === 'CHANNEL_ERROR' || status === 'TIMED_OUT') {
            startPollingFallback();
          }
        });

      root.__topnavSupabaseClient = client;
      root.__topnavRealtimeChannel = channel;

      window.addEventListener('beforeunload', () => {
        if (root.__topnavRealtimeChannel && root.__topnavSupabaseClient) {
          root.__topnavSupabaseClient.removeChannel(root.__topnavRealtimeChannel);
        }
      }, { once: true });
    } catch (_error) {
      startPollingFallback();
    }
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

    const closeRootPanels = () => {
      closeModal(listModal);
      closeModal(detailModal);
    };

    if (!trigger || !listModal || !detailModal) {
      return;
    }

    const syncSnapshot = async ({ preserveDetail = false } = {}) => {
      const snapshot = await fetchSnapshot(root);
      if (!snapshot) {
        return;
      }

      if (Number.isFinite(snapshot.unreadCount)) {
        setUnreadCount(root, snapshot.unreadCount);
      }

      renderItems(root, snapshot.items);

      if (preserveDetail && detailModal && !detailModal.classList.contains('hidden')) {
        const activeId = String(detailModal.getAttribute('data-active-notification-id') || '').trim();
        if (activeId !== '') {
          const activeNotification = snapshot.items.find((item) => item.id === activeId);
          if (activeNotification) {
            updateDetailModal(detailModal, activeNotification);
          }
        }
      }
    };

    [
      'admin:close-topnav-notifications',
      CLOSE_TOPNAV_EVENT,
      PROFILE_OPENED_EVENT,
      SIDEBAR_OPENED_EVENT,
      AUX_MENU_OPENED_EVENT,
    ].forEach((eventName) => {
      document.addEventListener(eventName, () => {
        closeRootPanels();
      });
    });

    trigger.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      closeCompanionMenus();
      dispatchUiEvent(CLOSE_TOPNAV_EVENT, { source: 'topnav-trigger' });
      dispatchUiEvent(REQUEST_SIDEBAR_CLOSE_EVENT, { source: 'topnav-trigger' });
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

    const itemsContainer = root.querySelector('[data-topnav-items]');
    itemsContainer?.addEventListener('click', async (event) => {
      const item = event.target instanceof Element ? event.target.closest('[data-topnav-item]') : null;
      if (!(item instanceof HTMLElement)) {
        return;
      }

      const notification = normalizeNotification({
        id: item.getAttribute('data-notification-id') || '',
        title: item.getAttribute('data-notification-title') || 'Notification',
        body: item.getAttribute('data-notification-body') || 'No details available.',
        link_url: item.getAttribute('data-notification-link') || '',
        category: item.getAttribute('data-notification-category') || 'general',
        created_at_label: item.getAttribute('data-notification-created') || '-',
        is_read: String(item.getAttribute('data-notification-read') || '0') === '1',
      });

      updateDetailModal(detailModal, notification);

      dispatchUiEvent(CLOSE_TOPNAV_EVENT, { source: 'topnav-item' });
        closeModal(listModal);
        openModal(detailModal);

      if (!notification.is_read) {
        const result = await markRead(root, notification.id);
        if (result.ok) {
          markItemReadUi(item);
          updateDetailModal(detailModal, {
            ...notification,
            is_read: true,
          });

          if (Number.isFinite(result.unreadCount)) {
            setUnreadCount(root, result.unreadCount);
          } else {
            setUnreadCount(root, parseUnreadCount(root) - 1);
          }

          await syncSnapshot({ preserveDetail: true });
        }
      }
    });

    initRealtimeSync(root, syncSnapshot).catch(() => {});
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
