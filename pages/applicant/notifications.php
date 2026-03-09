<?php
require_once __DIR__ . '/includes/notifications/bootstrap.php';
require_once __DIR__ . '/includes/notifications/actions.php';
require_once __DIR__ . '/includes/notifications/data.php';

$csrfToken = ensureCsrfToken();

$pageTitle = 'Notifications | DA HRIS';
$activePage = 'notifications.php';
$breadcrumbs = ['Notifications'];
$retryUrl = htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? 'notifications.php'), ENT_QUOTES, 'UTF-8');

ob_start();
?>

<?php if (!empty($message)): ?>
<section class="mb-6 rounded-xl border px-4 py-3 text-sm <?= ($state ?? '') === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
    <?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>
</section>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
<section class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <span><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></span>
        <a href="<?= $retryUrl ?>" class="inline-flex items-center justify-center rounded-md border border-amber-300 bg-white px-3 py-1.5 text-xs text-amber-800 hover:bg-amber-100">Retry</a>
    </div>
</section>
<?php endif; ?>

<section class="mb-5 rounded-xl border bg-white p-4 sm:p-5">
    <div class="rounded-xl border bg-gradient-to-r from-green-50 via-white to-green-50 p-4 sm:p-5">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <span class="material-symbols-outlined rounded-xl bg-green-700 p-2 text-3xl text-white">notifications</span>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Notification Inbox</h1>
                    <p class="mt-1 text-sm text-gray-600">Stay updated with changes to your applications and recruitment announcements.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="rounded-lg border bg-white px-4 py-3 text-center">
                    <p class="font-semibold text-gray-800"><?= (int)$notificationStats['recent'] ?></p>
                    <p class="text-xs text-gray-500">Recent Updates</p>
                </div>
                <div class="rounded-lg border bg-white px-4 py-3 text-center">
                    <p id="applicantNotificationUnreadCount" class="font-semibold text-gray-800"><?= (int)$notificationStats['unread'] ?></p>
                    <p class="text-xs text-gray-500">Unread</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mb-6 rounded-xl border bg-white p-4">
    <div class="flex flex-col gap-3 text-sm sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
        <span class="font-medium text-gray-600">Filter:</span>
        <?php
        $filterMap = [
            'all' => 'All',
            'unread' => 'Unread',
            'application' => 'Application',
            'system' => 'System',
        ];
        ?>
        <?php foreach ($filterMap as $filterKey => $filterLabel): ?>
            <a href="notifications.php?filter=<?= urlencode($filterKey) ?>" class="rounded-full px-3 py-1 <?= $filter === $filterKey ? 'bg-green-100 text-green-700' : 'border text-gray-600 hover:bg-gray-50' ?>">
                <?= htmlspecialchars($filterLabel, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
        </div>

        <?php if ($notificationStats['unread'] > 0): ?>
            <form method="POST" action="notifications.php" class="w-full sm:w-auto">
                <input type="hidden" name="action" value="mark_all_read">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="w-full rounded-md border px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 sm:w-auto">Mark all as read</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<section class="rounded-xl border bg-white">
    <header class="border-b px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Recent Notifications</h2>
    </header>

    <?php if (empty($notifications)): ?>
        <div class="p-8 text-center">
            <span class="material-symbols-outlined text-4xl text-gray-400">inbox</span>
            <h3 class="mt-3 text-lg font-semibold text-gray-800"><?= $isFilterEmpty ? 'No matching notifications' : 'No notifications yet' ?></h3>
            <p class="mt-1 text-sm text-gray-600">
                <?= $isFilterEmpty ? 'Try switching to a different filter.' : 'You are all caught up. New updates will appear here.' ?>
            </p>
            <?php if ($isFilterEmpty): ?>
                <a href="notifications.php" class="mt-4 inline-flex items-center rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Clear filter</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="space-y-4 p-6">
            <?php foreach ($notifications as $notification): ?>
                <?php
                $categoryMeta = (array)($categoryStyles[$notification['category']] ?? $categoryStyles['default']);
                ?>
                <?php
                $notificationTitle = (string)($notification['title'] ?? 'Notification');
                $notificationBody = (string)($notification['body'] ?? '');
                $notificationCreated = '-';
                if (!empty($notification['created_at'])) {
                    $formattedCreatedAt = function_exists('formatDateTimeForPhilippines')
                        ? formatDateTimeForPhilippines((string)$notification['created_at'], 'M j, Y · g:i A')
                        : date('M j, Y · g:i A', strtotime((string)$notification['created_at']));
                    $notificationCreated = $formattedCreatedAt !== '-' ? ($formattedCreatedAt . ' PST') : '-';
                }
                $notificationStatus = !$notification['is_read'] ? 'Unread' : 'Read';
                ?>
                <article
                    class="cursor-pointer rounded-xl border p-5 transition hover:bg-gray-50 <?= !$notification['is_read'] ? 'border-green-200 bg-green-50' : '' ?>"
                    data-notification-id="<?= htmlspecialchars((string)($notification['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    data-is-read="<?= !empty($notification['is_read']) ? '1' : '0' ?>"
                    data-applicant-notification-row
                    data-notification-title="<?= htmlspecialchars($notificationTitle, ENT_QUOTES, 'UTF-8') ?>"
                    data-notification-category="<?= htmlspecialchars((string)($categoryMeta['label'] ?? 'General'), ENT_QUOTES, 'UTF-8') ?>"
                    data-notification-message="<?= htmlspecialchars($notificationBody !== '' ? $notificationBody : 'No additional message provided.', ENT_QUOTES, 'UTF-8') ?>"
                    data-notification-created="<?= htmlspecialchars($notificationCreated, ENT_QUOTES, 'UTF-8') ?>"
                    data-notification-status="<?= htmlspecialchars($notificationStatus, ENT_QUOTES, 'UTF-8') ?>"
                    data-notification-link="<?= htmlspecialchars((string)($notification['link_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    tabindex="0"
                >
                    <div class="flex gap-3">
                        <span class="material-symbols-outlined mt-1 <?= htmlspecialchars((string)$categoryMeta['icon_color'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$categoryMeta['icon'], ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h3 class="truncate font-medium text-gray-800"><?= htmlspecialchars($notificationTitle, ENT_QUOTES, 'UTF-8') ?></h3>
                                <span class="text-xs text-gray-500">
                                    <?= htmlspecialchars($notificationCreated, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <p class="mt-1 truncate text-sm text-gray-600"><?= htmlspecialchars($notificationBody, ENT_QUOTES, 'UTF-8') ?></p>

                            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs">
                                <span data-notification-status-label class="inline-flex items-center gap-1 <?= !$notification['is_read'] ? 'font-medium text-green-700' : 'text-gray-500' ?>">
                                    <span class="material-symbols-outlined text-xs"><?= !$notification['is_read'] ? 'mark_email_unread' : 'drafts' ?></span>
                                    <?= !$notification['is_read'] ? 'Unread' : 'Read' ?>
                                </span>

                                <?php if (!empty($notification['link_url'])): ?>
                                    <a href="<?= htmlspecialchars((string)$notification['link_url'], ENT_QUOTES, 'UTF-8') ?>" class="text-green-700 hover:underline">Open</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="border-t bg-gray-50 px-6 py-3 text-sm text-gray-600">Showing <?= count($notifications) ?> notification(s)</div>
    <?php endif; ?>
</section>

<div id="applicantNotificationModal" class="fixed inset-0 z-50 hidden">
    <button type="button" id="applicantNotificationModalBackdrop" class="absolute inset-0 bg-slate-950/55" aria-label="Close notification details"></button>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <p class="text-base font-semibold text-slate-800">Notification Details</p>
                    <p id="applicantNotificationModalCreated" class="mt-1 text-xs text-slate-500">-</p>
                </div>
                <button type="button" id="applicantNotificationModalClose" class="rounded-md border border-slate-200 px-2 py-1 text-slate-600 hover:bg-slate-50" aria-label="Close notification details">
                    <span class="material-symbols-outlined text-base">close</span>
                </button>
            </div>

            <div class="space-y-4 px-5 py-4 text-sm text-slate-700">
                <div class="flex flex-wrap items-center gap-2">
                    <span id="applicantNotificationModalStatus" class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Read</span>
                    <span id="applicantNotificationModalCategory" class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">General</span>
                </div>

                <div>
                    <h3 id="applicantNotificationModalTitle" class="text-lg font-semibold text-slate-800">Notification</h3>
                    <p id="applicantNotificationModalMessage" class="mt-3 overflow-x-auto whitespace-pre-line break-all text-slate-600">No details available.</p>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                    <a id="applicantNotificationModalLink" href="#" class="hidden items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
                        <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                        <span>Open related record</span>
                    </a>
                    <button type="button" id="applicantNotificationModalDone" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('applicantNotificationModal');
        const modalBackdrop = document.getElementById('applicantNotificationModalBackdrop');
        const modalClose = document.getElementById('applicantNotificationModalClose');
        const modalDone = document.getElementById('applicantNotificationModalDone');
        const modalTitle = document.getElementById('applicantNotificationModalTitle');
        const modalMessage = document.getElementById('applicantNotificationModalMessage');
        const modalCreated = document.getElementById('applicantNotificationModalCreated');
        const modalStatus = document.getElementById('applicantNotificationModalStatus');
        const modalCategory = document.getElementById('applicantNotificationModalCategory');
        const modalLink = document.getElementById('applicantNotificationModalLink');
        const unreadCountEl = document.getElementById('applicantNotificationUnreadCount');
        const rows = Array.from(document.querySelectorAll('[data-applicant-notification-row]'));
        const currentFilter = new URLSearchParams(window.location.search).get('filter') || 'all';

        const parseCount = (element) => {
            if (!element) return 0;
            return Number.parseInt(element.textContent || '0', 10) || 0;
        };

        const updateCountersAfterRead = (unreadCountFromApi) => {
            if (!unreadCountEl) return;

            const currentUnread = parseCount(unreadCountEl);
            const nextUnread = Number.isFinite(unreadCountFromApi) ? Math.max(0, unreadCountFromApi) : Math.max(0, currentUnread - 1);
            unreadCountEl.textContent = String(nextUnread);
        };

        const applyReadUIState = (row) => {
            if (!row) {
                return;
            }

            row.dataset.isRead = '1';
            row.setAttribute('data-notification-status', 'Read');
            row.classList.remove('border-green-200', 'bg-green-50');

            const statusLabel = row.querySelector('[data-notification-status-label]');
            if (statusLabel) {
                statusLabel.className = 'inline-flex items-center gap-1 text-gray-500';
                statusLabel.innerHTML = '<span class="material-symbols-outlined text-xs">drafts</span>Read';
            }

            if (currentFilter === 'unread') {
                row.classList.add('hidden');
            }
        };

        const markNotificationAsRead = async (notificationId) => {
            if (!notificationId) {
                return null;
            }

            const body = new URLSearchParams();
            body.set('action', 'mark_read');
            body.set('notification_id', notificationId);
            body.set('csrf_token', <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>);
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

        modalBackdrop?.addEventListener('click', closeModal);
        modalClose?.addEventListener('click', closeModal);
        modalDone?.addEventListener('click', closeModal);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    })();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
