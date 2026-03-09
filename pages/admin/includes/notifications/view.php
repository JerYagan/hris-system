<?php
$statusPill = static function (bool $isRead): array {
    if ($isRead) {
        return ['Read', 'bg-emerald-100 text-emerald-800'];
    }
    return ['Unread', 'bg-amber-100 text-amber-800'];
};

$categoryIcon = static function (string $value): string {
    $key = strtolower(trim($value));
    if (str_contains($key, 'system')) {
        return 'campaign';
    }
    if (str_contains($key, 'hr') || str_contains($key, 'announcement')) {
        return 'announcement';
    }
    if (str_contains($key, 'application') || str_contains($key, 'recruitment')) {
        return 'update';
    }
    if (str_contains($key, 'learning') || str_contains($key, 'development') || str_contains($key, 'training')) {
        return 'school';
    }

    return 'notifications';
};

$formatNotificationDateTime = static function (?string $dateTime): string {
    $value = trim((string)$dateTime);
    if ($value === '') {
        return '-';
    }

    $formatted = function_exists('formatDateTimeForPhilippines')
        ? formatDateTimeForPhilippines($value, 'M d, Y h:i A')
        : date('M d, Y h:i A', strtotime($value));

    return $formatted !== '-' ? ($formatted . ' PST') : '-';
};
?>

<?php if ($state && $message): ?>
    <?php
    $alertClass = $state === 'success'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
        : 'border-red-200 bg-red-50 text-red-700';
    $icon = $state === 'success' ? 'check_circle' : 'error';
    ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm flex gap-2 <?= htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8') ?>">
        <span class="material-symbols-outlined text-base"><?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?></span>
        <span><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm flex gap-2">
        <span class="material-symbols-outlined text-base">error</span>
        <span><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Notifications</p>
            <p id="adminNotificationTotalCount" class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$totalNotifications, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Visible to current admin account</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase tracking-wide text-amber-700">Unread</p>
            <p id="adminNotificationUnreadCount" class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$unreadNotifications, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Needs attention</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Read</p>
            <p id="adminNotificationReadCount" class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$readNotifications, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Already reviewed</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-blue-50">
            <p class="text-xs uppercase tracking-wide text-blue-700">Top Category</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $topCategory)), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)$topCategoryCount, ENT_QUOTES, 'UTF-8') ?> notification<?= $topCategoryCount === 1 ? '' : 's' ?></p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Email Notification Settings</h2>
        <p class="text-sm text-slate-500 mt-1">Provider is set to SMTP. Use this test action to confirm SMTP and sender configuration.</p>
    </header>
    <form action="notifications.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <input type="hidden" name="form_action" value="send_test_notification_email">
        <div class="md:col-span-2">
            <label class="text-slate-600">Test Recipient Email</label>
            <input type="email" name="recipient_email" value="<?= htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="you@example.com" required>
        </div>
        <div>
            <label class="text-slate-600">Configured Sender</label>
            <input type="text" value="<?= htmlspecialchars($mailFrom !== '' ? $mailFrom : 'Not configured', ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Send Test Email</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Recent Notifications</h2>
            <p class="text-sm text-slate-500 mt-1">Announcement broadcasts are managed in Create Announcement. This feed shows actionable notification items.</p>
        </div>
        <form action="notifications.php" method="POST">
            <input type="hidden" name="form_action" value="mark_all_notifications_read">
            <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Mark All as Read</button>
        </form>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Notifications</label>
            <input id="adminNotificationsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by title, message, or category">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="adminNotificationsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="Unread">Unread</option>
                <option value="Read">Read</option>
            </select>
        </div>
    </div>

    <div class="p-6">
        <?php if (empty($notifications)): ?>
            <div class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-sm text-slate-500">No notifications found.</div>
        <?php else: ?>
            <div id="adminNotificationsList" class="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    $notificationId = (string)($notification['id'] ?? '');
                    $title = (string)($notification['title'] ?? 'Untitled Notification');
                    $category = (string)($notification['category'] ?? 'general');
                    $body = (string)($notification['body'] ?? '');
                    $linkUrl = cleanText($notification['link_url'] ?? null);
                    $isRead = (bool)($notification['is_read'] ?? false);
                    $createdAt = (string)($notification['created_at'] ?? '');
                    $createdLabel = $formatNotificationDateTime($createdAt);
                    [$statusLabel, $statusClass] = $statusPill($isRead);
                    $searchText = strtolower(trim($title . ' ' . $body . ' ' . $category . ' ' . $statusLabel));
                    $categoryLabel = ucfirst(str_replace('_', ' ', strtolower($category)));
                    ?>
                    <article
                        data-notification-id="<?= htmlspecialchars($notificationId, ENT_QUOTES, 'UTF-8') ?>"
                        data-is-read="<?= $isRead ? '1' : '0' ?>"
                        data-notif-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>"
                        data-notif-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>"
                        data-admin-notification-row
                        data-notification-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
                        data-notification-category="<?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?>"
                        data-notification-message="<?= htmlspecialchars($body !== '' ? $body : 'No additional message provided.', ENT_QUOTES, 'UTF-8') ?>"
                        data-notification-created="<?= htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8') ?>"
                        data-notification-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>"
                        data-notification-link="<?= htmlspecialchars((string)($linkUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        tabindex="0"
                        class="flex cursor-pointer items-start gap-4 px-6 py-4 transition-colors duration-150 hover:bg-slate-50 <?= $isRead ? '' : 'bg-emerald-50/70' ?>"
                    >
                        <span class="material-symbols-outlined mt-1 text-emerald-700"><?= htmlspecialchars($categoryIcon($category), ENT_QUOTES, 'UTF-8') ?></span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h3 class="truncate font-medium text-slate-800"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
                                <span class="text-xs text-slate-500"><?= htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>

                            <p class="mt-1 truncate text-sm text-slate-600"><?= htmlspecialchars($body, ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="mt-1 text-xs text-slate-500"><?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <span data-notification-status-pill class="inline-flex items-center justify-center rounded-full px-2.5 py-1 text-xs <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>

                            <?php if ($linkUrl): ?>
                                <a href="<?= htmlspecialchars($linkUrl, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1 rounded border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-50"><span class="material-symbols-outlined text-[15px]">open_in_new</span>Open</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div id="adminNotificationsFilterEmpty" class="hidden rounded-xl border border-dashed border-slate-200 px-4 py-8 text-sm text-slate-500">No notifications match your search/filter criteria.</div>
        <?php endif; ?>
    </div>
</section>

<div id="adminNotificationModal" class="fixed inset-0 z-50 hidden">
    <button type="button" id="adminNotificationModalBackdrop" class="absolute inset-0 bg-slate-950/55" aria-label="Close notification details"></button>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <p class="text-base font-semibold text-slate-800">Notification Details</p>
                    <p id="adminNotificationModalCreated" class="mt-1 text-xs text-slate-500">-</p>
                </div>
                <button type="button" id="adminNotificationModalClose" class="rounded-md border border-slate-200 px-2 py-1 text-slate-600 hover:bg-slate-50" aria-label="Close notification details">
                    <span class="material-symbols-outlined text-base">close</span>
                </button>
            </div>

            <div class="space-y-4 px-5 py-4 text-sm text-slate-700">
                <div class="flex flex-wrap items-center gap-2">
                    <span id="adminNotificationModalStatus" class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Read</span>
                    <span id="adminNotificationModalCategory" class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">General</span>
                </div>

                <div>
                    <h3 id="adminNotificationModalTitle" class="text-lg font-semibold text-slate-800">Notification</h3>
                    <p id="adminNotificationModalMessage" class="mt-3 whitespace-pre-line break-words text-slate-600">No details available.</p>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                    <a id="adminNotificationModalLink" href="#" class="hidden items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
                        <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                        <span>Open related record</span>
                    </a>
                    <button type="button" id="adminNotificationModalDone" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const searchInput = document.getElementById('adminNotificationsSearch');
        const statusFilter = document.getElementById('adminNotificationsStatusFilter');
        const rows = Array.from(document.querySelectorAll('[data-notif-search]'));
        const modal = document.getElementById('adminNotificationModal');
        const emptyState = document.getElementById('adminNotificationsFilterEmpty');
        const modalBackdrop = document.getElementById('adminNotificationModalBackdrop');
        const modalClose = document.getElementById('adminNotificationModalClose');
        const modalDone = document.getElementById('adminNotificationModalDone');
        const modalTitle = document.getElementById('adminNotificationModalTitle');
        const modalMessage = document.getElementById('adminNotificationModalMessage');
        const modalCreated = document.getElementById('adminNotificationModalCreated');
        const modalStatus = document.getElementById('adminNotificationModalStatus');
        const modalCategory = document.getElementById('adminNotificationModalCategory');
        const modalLink = document.getElementById('adminNotificationModalLink');
        const unreadCountEl = document.getElementById('adminNotificationUnreadCount');
        const readCountEl = document.getElementById('adminNotificationReadCount');
        const totalCountEl = document.getElementById('adminNotificationTotalCount');

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
            if (!row) return;

            row.dataset.isRead = '1';
            row.setAttribute('data-notif-status', 'Read');
            row.setAttribute('data-notification-status', 'Read');
            row.classList.remove('bg-emerald-50/70');

            const pill = row.querySelector('[data-notification-status-pill]');
            if (pill) {
                pill.textContent = 'Read';
                pill.className = 'inline-flex items-center justify-center rounded-full px-2.5 py-1 text-xs bg-emerald-100 text-emerald-800';
            }

            if ((statusFilter?.value || '').trim().toLowerCase() === 'unread') {
                row.classList.add('hidden');
            }
        };

        const markNotificationAsRead = async (notificationId) => {
            if (!notificationId) {
                return null;
            }

            const body = new URLSearchParams();
            body.set('form_action', 'mark_notification_read');
            body.set('notification_id', notificationId);
            body.set('async', '1');

            const response = await fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: body.toString(),
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

        const applyFilters = () => {
            const needle = (searchInput?.value || '').trim().toLowerCase();
            const status = (statusFilter?.value || '').trim().toLowerCase();

            let visibleCount = 0;
            rows.forEach((row) => {
                const rowSearch = (row.getAttribute('data-notif-search') || '').toLowerCase();
                const rowStatus = (row.getAttribute('data-notif-status') || '').toLowerCase();
                const matchesSearch = needle === '' || rowSearch.includes(needle);
                const matchesStatus = status === '' || rowStatus === status;
                const visible = matchesSearch && matchesStatus;
                row.classList.toggle('hidden', !visible);
                if (visible) {
                    visibleCount += 1;
                }
            });

            if (emptyState) {
                emptyState.classList.toggle('hidden', visibleCount > 0);
            }
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

            if (modalTitle) modalTitle.textContent = title;
            if (modalMessage) modalMessage.textContent = message;
            if (modalCreated) modalCreated.textContent = created;
            if (modalStatus) {
                modalStatus.textContent = status;
                modalStatus.className = 'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ' + (isUnread ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700');
            }
            if (modalCategory) modalCategory.textContent = category;
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

        rows.forEach((row) => {
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

        searchInput?.addEventListener('input', applyFilters);
        statusFilter?.addEventListener('change', applyFilters);
        modalBackdrop?.addEventListener('click', closeModal);
        modalClose?.addEventListener('click', closeModal);
        modalDone?.addEventListener('click', closeModal);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        applyFilters();
    })();
</script>
