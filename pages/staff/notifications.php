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
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$totalNotifications, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Visible to current staff account</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase tracking-wide text-amber-700">Unread</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$unreadNotifications, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Needs attention</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Read</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$readNotifications, ENT_QUOTES, 'UTF-8') ?></p>
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

    <div class="p-6 overflow-x-auto">
        <table id="staffNotificationsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Title</th>
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Message</th>
                    <th class="text-left px-4 py-3">Created</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($notifications)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="6">No notifications found.</td>
                    </tr>
                <?php else: ?>
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
                        ?>
                        <tr data-staff-notif-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-staff-notif-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', strtolower($category))), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($body, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[85px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <?php if (!$isRead): ?>
                                        <form action="notifications.php" method="POST" class="staff-mark-read-form">
                                            <input type="hidden" name="form_action" value="mark_notification_read">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="notification_id" value="<?= htmlspecialchars($notificationId, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"><span class="material-symbols-outlined text-[15px]">mark_email_read</span>Mark Read</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($linkUrl): ?>
                                        <a href="<?= htmlspecialchars($linkUrl, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"><span class="material-symbols-outlined text-[15px]">open_in_new</span>Open</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="staffNotificationsFilterEmpty" class="hidden">
                    <td class="px-4 py-3 text-slate-500" colspan="6">No notifications match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

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
