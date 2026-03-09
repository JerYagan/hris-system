<?php
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/actions.php';
require_once __DIR__ . '/includes/dashboard/data.php';

$pageTitle = 'Staff Dashboard | DA HRIS';
$activePage = 'dashboard.php';
$breadcrumbs = ['Dashboard'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/staff/dashboard/index.js';

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$pendingApprovalsPerPage = 10;
$pendingApprovalsTotal = count($dashboardPendingApprovals);
$pendingApprovalsPage = max(1, (int)($_GET['approvals_page'] ?? 1));
$pendingApprovalsTotalPages = max(1, (int)ceil($pendingApprovalsTotal / $pendingApprovalsPerPage));
if ($pendingApprovalsPage > $pendingApprovalsTotalPages) {
    $pendingApprovalsPage = $pendingApprovalsTotalPages;
}

$pendingApprovalsStart = ($pendingApprovalsPage - 1) * $pendingApprovalsPerPage;
$pendingApprovalsRows = array_slice($dashboardPendingApprovals, $pendingApprovalsStart, $pendingApprovalsPerPage);

$buildApprovalsPageLink = static function (int $page) : string {
    $params = $_GET;
    $params['approvals_page'] = max(1, $page);
    return 'dashboard.php?' . http_build_query($params);
};

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Staff Dashboard</h1>
    <p class="text-sm text-gray-500">Operational summary, pending approvals, and role-relevant notifications.</p>
</div>

<?php if ($state && $message): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($dataLoadError): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <?= htmlspecialchars($dataLoadError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
    <div class="bg-white border rounded-xl p-5">
        <p class="text-sm text-gray-500">Active Employees</p>
        <p class="text-2xl font-bold text-green-700 mt-1"><?= (int)($dashboardMetrics['active_employees'] ?? 0) ?></p>
        <p class="text-xs text-gray-500 mt-1">Current active records in your scope</p>
    </div>

    <div class="bg-white border rounded-xl p-5">
        <p class="text-sm text-gray-500">Pending Applications</p>
        <p class="text-2xl font-bold text-blue-700 mt-1"><?= (int)($dashboardMetrics['pending_applications'] ?? 0) ?></p>
        <p class="text-xs text-gray-500 mt-1">For screening and interview</p>
    </div>

    <div class="bg-white border rounded-xl p-5">
        <p class="text-sm text-gray-500">Documents for Verification</p>
        <p class="text-2xl font-bold text-yellow-700 mt-1"><?= (int)($dashboardMetrics['documents_for_verification'] ?? 0) ?></p>
        <p class="text-xs text-gray-500 mt-1">Awaiting validation</p>
    </div>

    <div class="bg-white border rounded-xl p-5">
        <p class="text-sm text-gray-500">Payroll Tasks</p>
        <p class="text-2xl font-bold text-purple-700 mt-1"><?= (int)($dashboardMetrics['payroll_tasks'] ?? 0) ?></p>
        <p class="text-xs text-gray-500 mt-1">Draft/computed/approved runs</p>
    </div>
</div>

<section class="bg-white border rounded-xl p-6 mb-8">
    <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="font-semibold text-gray-800">Pending Approvals</h2>
        <p class="text-xs text-gray-500">
            Showing
            <span class="font-medium text-gray-700"><?= $pendingApprovalsTotal === 0 ? 0 : ($pendingApprovalsStart + 1) ?></span>
            -
            <span class="font-medium text-gray-700"><?= min($pendingApprovalsStart + count($pendingApprovalsRows), $pendingApprovalsTotal) ?></span>
            of
            <span class="font-medium text-gray-700"><?= $pendingApprovalsTotal ?></span>
        </p>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">Request</th>
                    <th class="px-4 py-3">Subject</th>
                    <th class="px-4 py-3">Module</th>
                    <th class="px-4 py-3">Submitted At</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php if (empty($pendingApprovalsRows)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No pending approvals found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pendingApprovalsRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars((string)($row['request'] ?? 'Pending Approval'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($row['owner'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($row['module'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars(formatDateTimeForPhilippines(cleanText($row['created_at'] ?? null), 'M d, Y · h:i A'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)($row['status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="<?= htmlspecialchars((string)($row['action_url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>" class="text-sm font-medium text-green-700 hover:underline">Open queue</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pendingApprovalsTotalPages > 1): ?>
        <div class="mt-4 flex items-center justify-between">
            <a href="<?= htmlspecialchars($buildApprovalsPageLink(max(1, $pendingApprovalsPage - 1)), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-medium <?= $pendingApprovalsPage <= 1 ? 'pointer-events-none opacity-50' : 'text-slate-700 hover:bg-slate-50' ?>">Previous</a>
            <p class="text-xs text-slate-500">Page <?= $pendingApprovalsPage ?> of <?= $pendingApprovalsTotalPages ?></p>
            <a href="<?= htmlspecialchars($buildApprovalsPageLink(min($pendingApprovalsTotalPages, $pendingApprovalsPage + 1)), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-medium <?= $pendingApprovalsPage >= $pendingApprovalsTotalPages ? 'pointer-events-none opacity-50' : 'text-slate-700 hover:bg-slate-50' ?>">Next</a>
        </div>
    <?php endif; ?>
</section>

<section class="bg-white border rounded-xl p-6">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div id="staffDashboardNotificationsCard" class="min-w-0">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-800">Notifications</h2>
                <a href="notifications.php" class="text-sm text-green-700 hover:underline">View all</a>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_10rem]">
                <div>
                    <label for="staffDashboardNotificationsSearch" class="text-xs font-medium uppercase tracking-wide text-slate-500">Search Notifications</label>
                    <input id="staffDashboardNotificationsSearch" type="search" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Search by title, message, or category">
                </div>
                <div>
                    <label for="staffDashboardNotificationsStatusFilter" class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
                    <select id="staffDashboardNotificationsStatusFilter" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All Status</option>
                        <option value="New">New</option>
                        <option value="Read">Read</option>
                    </select>
                </div>
            </div>

            <ul class="space-y-4 text-sm" id="staffDashboardNotificationsList">
                <?php if (empty($dashboardRoleNotifications)): ?>
                    <li class="border-l-4 border-slate-300 pl-4">
                        <p class="font-medium text-gray-800">No notifications</p>
                        <p class="text-xs text-gray-500">Action-related alerts will appear here.</p>
                    </li>
                <?php else: ?>
                    <?php foreach ($dashboardRoleNotifications as $item): ?>
                        <li
                            class="border-l-4 border-blue-500 pl-4"
                            data-dashboard-notification-row
                            data-dashboard-search="<?= htmlspecialchars(strtolower(trim((string)($item['title'] ?? '') . ' ' . (string)($item['body'] ?? '') . ' ' . (string)($item['category'] ?? '') . ' ' . (string)($item['meta'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>"
                            data-dashboard-status="<?= htmlspecialchars((string)($item['status_label'] ?? 'New'), ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($item['title'] ?? 'Notification'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($item['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                        <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-700"><?= htmlspecialchars((string)($item['category'] ?? 'Notification'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span><?= htmlspecialchars((string)($item['meta'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <a href="<?= htmlspecialchars((string)($item['link_url'] ?? 'notifications.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-green-700 hover:underline">Open</a>
                                    </div>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full <?= htmlspecialchars((string)($item['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)($item['status_label'] ?? 'New'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <li id="staffDashboardNotificationsEmpty" class="hidden border-l-4 border-slate-300 pl-4">
                        <p class="font-medium text-gray-800">No matching notifications</p>
                        <p class="text-xs text-gray-500">Try a different search term or status filter.</p>
                    </li>
                <?php endif; ?>
            </ul>

            <?php if (!empty($dashboardRoleNotifications)): ?>
                <div class="mt-4 flex items-center justify-between gap-3">
                    <p id="staffDashboardNotificationsInfo" class="text-xs text-slate-500">Page 1 of 1</p>
                    <div class="flex items-center gap-2">
                        <button type="button" id="staffDashboardNotificationsPrev" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">Previous</button>
                        <button type="button" id="staffDashboardNotificationsNext" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">Next</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div id="staffDashboardAnnouncementsCard" class="min-w-0">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-800">Announcements</h2>
                <a href="notifications.php" class="text-sm text-green-700 hover:underline">View all</a>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_10rem]">
                <div>
                    <label for="staffDashboardAnnouncementsSearch" class="text-xs font-medium uppercase tracking-wide text-slate-500">Search Announcements</label>
                    <input id="staffDashboardAnnouncementsSearch" type="search" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Search by title or date">
                </div>
                <div>
                    <label for="staffDashboardAnnouncementsStatusFilter" class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
                    <select id="staffDashboardAnnouncementsStatusFilter" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All Status</option>
                        <option value="New">New</option>
                        <option value="Read">Read</option>
                    </select>
                </div>
            </div>

            <ul class="space-y-4 text-sm" id="staffDashboardAnnouncementsList">
                <?php if (empty($dashboardAnnouncements)): ?>
                    <li class="border-l-4 border-slate-300 pl-4">
                        <p class="font-medium text-gray-800">No announcements available</p>
                        <p class="text-xs text-gray-500">Announcements will appear here once published.</p>
                    </li>
                <?php else: ?>
                    <?php foreach ($dashboardAnnouncements as $item): ?>
                        <li
                            class="border-l-4 border-green-500 pl-4"
                            data-dashboard-announcement-row
                            data-dashboard-search="<?= htmlspecialchars(strtolower(trim((string)($item['title'] ?? '') . ' ' . (string)($item['meta'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>"
                            data-dashboard-status="<?= htmlspecialchars((string)($item['status_label'] ?? 'New'), ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($item['title'] ?? 'Announcement'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($item['meta'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full <?= htmlspecialchars((string)($item['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)($item['status_label'] ?? 'New'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <li id="staffDashboardAnnouncementsEmpty" class="hidden border-l-4 border-slate-300 pl-4">
                        <p class="font-medium text-gray-800">No matching announcements</p>
                        <p class="text-xs text-gray-500">Try a different search term or status filter.</p>
                    </li>
                <?php endif; ?>
            </ul>

            <?php if (!empty($dashboardAnnouncements)): ?>
                <div class="mt-4 flex items-center justify-between gap-3">
                    <p id="staffDashboardAnnouncementsInfo" class="text-xs text-slate-500">Page 1 of 1</p>
                    <div class="flex items-center gap-2">
                        <button type="button" id="staffDashboardAnnouncementsPrev" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">Previous</button>
                        <button type="button" id="staffDashboardAnnouncementsNext" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">Next</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
