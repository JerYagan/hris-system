<?php
require_once __DIR__ . '/includes/notifications/bootstrap.php';
require_once __DIR__ . '/includes/notifications/actions.php';
require_once __DIR__ . '/includes/notifications/data.php';

$pageTitle = 'Notifications | Staff';
$activePage = 'notifications.php';
$breadcrumbs = ['Notifications'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
?>

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
?>

<?php if ($state && $message): ?>
    <?php
    $alertClass = $state === 'success'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
        : 'border-rose-200 bg-rose-50 text-rose-800';
    ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Notifications</p>
            <p id="staffNotificationTotalCount" class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$totalNotifications, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Visible to current staff account</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase tracking-wide text-amber-700">Unread</p>
            <p id="staffNotificationUnreadCount" class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$unreadNotifications, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Needs attention</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Read</p>
            <p id="staffNotificationReadCount" class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$readNotifications, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Already reviewed</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-blue-50">
            <p class="text-xs uppercase tracking-wide text-blue-700">Top Category</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $topCategory)), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)$topCategoryCount, ENT_QUOTES, 'UTF-8') ?> notification<?= $topCategoryCount === 1 ? '' : 's' ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-violet-50">
            <p class="text-xs uppercase tracking-wide text-violet-700">Audit Entries Today</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$auditTodayCount, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Out of <?= htmlspecialchars((string)$auditCount, ENT_QUOTES, 'UTF-8') ?> recent actions</p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Staff Notification Feed</h2>
            <p class="text-sm text-slate-500 mt-1">Search and filter your notifications, then mark individual or all items as read.</p>
        </div>
        <form action="notifications.php" method="POST">
            <input type="hidden" name="form_action" value="mark_all_notifications_read">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Mark All as Read</button>
        </form>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Notifications</label>
            <input id="staffNotificationsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by title, message, or category">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="staffNotificationsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
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
            <div id="staffNotificationsList" class="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    $notificationId = (string)($notification['id'] ?? '');
                    $title = (string)($notification['title'] ?? 'Untitled Notification');
                    $category = (string)($notification['category'] ?? 'general');
                    $body = (string)($notification['body'] ?? '');
                    $linkUrl = cleanText($notification['link_url'] ?? null);
                    $isRead = (bool)($notification['is_read'] ?? false);
                    $createdAt = (string)($notification['created_at'] ?? '');
                    $createdLabel = $createdAt !== '' ? formatDateTimeForPhilippines($createdAt, 'M d, Y h:i A') : '-';
                    [$statusLabel, $statusClass] = $statusPill($isRead);
                    $searchText = strtolower(trim($title . ' ' . $body . ' ' . $category . ' ' . $statusLabel));
                    $categoryLabel = ucfirst(str_replace('_', ' ', strtolower($category)));
                    ?>
                    <article
                        data-notification-id="<?= htmlspecialchars($notificationId, ENT_QUOTES, 'UTF-8') ?>"
                        data-is-read="<?= $isRead ? '1' : '0' ?>"
                        data-staff-notif-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>"
                        data-staff-notif-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>"
                        data-staff-notification-row
                        data-notification-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
                        data-notification-category="<?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?>"
                        data-notification-message="<?= htmlspecialchars($body !== '' ? $body : 'No additional message provided.', ENT_QUOTES, 'UTF-8') ?>"
                        data-notification-created="<?= htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8') ?>"
                        data-notification-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>"
                        data-notification-link="<?= htmlspecialchars((string)($linkUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        tabindex="0"
                        class="cursor-pointer overflow-hidden px-6 py-4 transition-colors duration-150 hover:bg-slate-50 <?= $isRead ? '' : 'bg-emerald-50/70' ?>"
                    >
                        <div class="flex min-w-0 flex-col gap-3 md:flex-row md:items-start md:gap-4">
                            <div class="flex w-0 flex-1 items-start gap-4 overflow-hidden">
                                <span class="material-symbols-outlined mt-1 shrink-0 text-emerald-700"><?= htmlspecialchars($categoryIcon($category), ENT_QUOTES, 'UTF-8') ?></span>

                                <div class="w-0 flex-1 overflow-hidden overflow-x-hidden">
                                    <div class="flex min-w-0 flex-wrap items-start justify-between gap-2 overflow-x-hidden">
                                        <h3 class="block w-full min-w-0 truncate font-medium text-slate-800 md:w-auto md:max-w-[calc(100%-8rem)]"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
                                        <span class="shrink-0 text-xs text-slate-500"><?= htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>

                                    <p class="mt-1 block w-full min-w-0 text-sm text-slate-600" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($body, ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="mt-1 block w-full min-w-0 truncate text-xs text-slate-500"><?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>

                            <div class="flex min-w-0 flex-wrap items-center gap-2 md:shrink-0 md:justify-end">
                                <span data-notification-status-pill class="inline-flex items-center justify-center rounded-full px-2.5 py-1 text-xs <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>

                                <?php if ($linkUrl): ?>
                                    <a href="<?= htmlspecialchars($linkUrl, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex max-w-full items-center gap-1 rounded border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-50"><span class="material-symbols-outlined text-[15px]">open_in_new</span>Open</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div id="staffNotificationsFilterEmpty" class="hidden rounded-xl border border-dashed border-slate-200 px-4 py-8 text-sm text-slate-500">No notifications match your search/filter criteria.</div>
        <?php endif; ?>
    </div>
</section>

<div id="staffNotificationModal" class="fixed inset-0 z-50 hidden">
    <button type="button" id="staffNotificationModalBackdrop" class="absolute inset-0 bg-slate-950/55" aria-label="Close notification details"></button>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <p class="text-base font-semibold text-slate-800">Notification Details</p>
                    <p id="staffNotificationModalCreated" class="mt-1 text-xs text-slate-500">-</p>
                </div>
                <button type="button" id="staffNotificationModalClose" class="rounded-md border border-slate-200 px-2 py-1 text-slate-600 hover:bg-slate-50" aria-label="Close notification details">
                    <span class="material-symbols-outlined text-base">close</span>
                </button>
            </div>

            <div class="space-y-4 px-5 py-4 text-sm text-slate-700">
                <div class="flex flex-wrap items-center gap-2">
                    <span id="staffNotificationModalStatus" class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Read</span>
                    <span id="staffNotificationModalCategory" class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">General</span>
                </div>

                <div>
                    <h3 id="staffNotificationModalTitle" class="text-lg font-semibold text-slate-800">Notification</h3>
                    <p id="staffNotificationModalMessage" class="mt-3 whitespace-pre-line break-words text-slate-600">No details available.</p>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                    <a id="staffNotificationModalLink" href="#" class="hidden items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
                        <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                        <span>Open related record</span>
                    </a>
                    <button type="button" id="staffNotificationModalDone" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Audit Trail (Recent Staff Actions)</h2>
        <p class="text-sm text-slate-500 mt-1">Recent activity logs generated by this staff account across modules.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">When</th>
                    <th class="text-left px-4 py-3">Module</th>
                    <th class="text-left px-4 py-3">Entity</th>
                    <th class="text-left px-4 py-3">Action</th>
                    <th class="text-left px-4 py-3">Entity ID</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($recentAuditRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="5">No audit log entries found for this account.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentAuditRows as $row): ?>
                        <?php
                        $createdAt = cleanText($row['created_at'] ?? null) ?? '';
                        $createdLabel = $createdAt !== '' ? formatDateTimeForPhilippines($createdAt, 'M d, Y h:i A') : '-';
                        $moduleName = ucfirst(str_replace('_', ' ', strtolower((string)(cleanText($row['module_name'] ?? null) ?? 'general'))));
                        $entityName = ucfirst(str_replace('_', ' ', strtolower((string)(cleanText($row['entity_name'] ?? null) ?? 'record'))));
                        $actionName = ucfirst(str_replace('_', ' ', strtolower((string)(cleanText($row['action_name'] ?? null) ?? 'updated'))));
                        $entityId = cleanText($row['entity_id'] ?? null) ?? '-';
                        ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($moduleName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($entityName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($actionName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-xs text-slate-500"><?= htmlspecialchars($entityId, ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script src="../../assets/js/staff/notifications/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
