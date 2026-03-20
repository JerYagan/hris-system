<?php
$escape = static function (mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$dashboardContentSection = (string)($dashboardContentSection ?? 'full');
$renderSummarySection = in_array($dashboardContentSection, ['full', 'summary'], true);
$renderApprovalsSection = in_array($dashboardContentSection, ['full', 'approvals'], true);
$renderNotificationsSection = in_array($dashboardContentSection, ['full', 'notifications'], true);
$renderAnnouncementsSection = in_array($dashboardContentSection, ['full', 'announcements'], true);
?>

<?php if ($renderSummarySection): ?>
    <?php if (!empty($dataLoadError)): ?>
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= $escape($dataLoadError) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
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
<?php endif; ?>

<?php if ($renderApprovalsSection): ?>
    <?php if (!empty($dataLoadError)): ?>
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= $escape($dataLoadError) ?>
        </div>
    <?php endif; ?>

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
                <?php if (empty($dashboardPendingApprovals)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No pending approvals found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dashboardPendingApprovals as $row): ?>
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800"><?= $escape((string)($row['request'] ?? 'Pending Approval')) ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= $escape((string)($row['owner'] ?? '-')) ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= $escape((string)($row['module'] ?? '-')) ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= $escape(formatDateTimeForPhilippines(cleanText($row['created_at'] ?? null), 'M d, Y · h:i A')) ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium <?= $escape((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700')) ?>">
                                    <?= $escape((string)($row['status_label'] ?? 'Pending')) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="<?= $escape((string)($row['action_url'] ?? '#')) ?>" class="text-sm font-medium text-green-700 hover:underline">Open queue</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex items-center justify-between">
        <button
            type="button"
            class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-medium <?= $dashboardPendingApprovalsPage <= 1 ? 'opacity-50 cursor-not-allowed' : 'text-slate-700 hover:bg-slate-50' ?>"
            data-dashboard-approvals-page="<?= max(1, $dashboardPendingApprovalsPage - 1) ?>"
            <?= $dashboardPendingApprovalsPage <= 1 ? 'disabled' : '' ?>
        >Previous</button>
        <p class="text-xs text-slate-500">
            Showing
            <span class="font-medium text-gray-700"><?= $dashboardPendingApprovalsTotal === 0 ? 0 : ($dashboardPendingApprovalsStart + 1) ?></span>
            -
            <span class="font-medium text-gray-700"><?= min($dashboardPendingApprovalsStart + count($dashboardPendingApprovals), $dashboardPendingApprovalsTotal) ?></span>
            of
            <span class="font-medium text-gray-700"><?= $dashboardPendingApprovalsTotal ?></span>
            · Page <?= $dashboardPendingApprovalsTotal === 0 ? 0 : $dashboardPendingApprovalsPage ?> of <?= $dashboardPendingApprovalsTotal === 0 ? 0 : $dashboardPendingApprovalsTotalPages ?>
        </p>
        <button
            type="button"
            class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-medium <?= $dashboardPendingApprovalsPage >= $dashboardPendingApprovalsTotalPages ? 'opacity-50 cursor-not-allowed' : 'text-slate-700 hover:bg-slate-50' ?>"
            data-dashboard-approvals-page="<?= min($dashboardPendingApprovalsTotalPages, $dashboardPendingApprovalsPage + 1) ?>"
            <?= $dashboardPendingApprovalsPage >= $dashboardPendingApprovalsTotalPages ? 'disabled' : '' ?>
        >Next</button>
    </div>
<?php endif; ?>

<?php if ($renderNotificationsSection): ?>
    <?php if (!empty($dataLoadError)): ?>
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= $escape($dataLoadError) ?>
        </div>
    <?php endif; ?>

    <div id="staffDashboardNotificationsCard" class="min-w-0 bg-white border rounded-xl p-6">
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
                        data-dashboard-search="<?= $escape(strtolower(trim((string)($item['title'] ?? '') . ' ' . (string)($item['body'] ?? '') . ' ' . (string)($item['category'] ?? '') . ' ' . (string)($item['meta'] ?? '')))) ?>"
                        data-dashboard-status="<?= $escape((string)($item['status_label'] ?? 'New')) ?>"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-800"><?= $escape((string)($item['title'] ?? 'Notification')) ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= $escape((string)($item['body'] ?? '')) ?></p>
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                    <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-700"><?= $escape((string)($item['category'] ?? 'Notification')) ?></span>
                                    <span><?= $escape((string)($item['meta'] ?? '-')) ?></span>
                                    <a href="<?= $escape((string)($item['link_url'] ?? 'notifications.php')) ?>" class="text-green-700 hover:underline">Open</a>
                                </div>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full <?= $escape((string)($item['status_class'] ?? 'bg-slate-100 text-slate-700')) ?>">
                                <?= $escape((string)($item['status_label'] ?? 'New')) ?>
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
<?php endif; ?>

<?php if ($renderAnnouncementsSection): ?>
    <?php if (!empty($dataLoadError)): ?>
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= $escape($dataLoadError) ?>
        </div>
    <?php endif; ?>

    <div id="staffDashboardAnnouncementsCard" class="min-w-0 bg-white border rounded-xl p-6">
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
                        data-dashboard-search="<?= $escape(strtolower(trim((string)($item['title'] ?? '') . ' ' . (string)($item['meta'] ?? '')))) ?>"
                        data-dashboard-status="<?= $escape((string)($item['status_label'] ?? 'New')) ?>"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-800"><?= $escape((string)($item['title'] ?? 'Announcement')) ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= $escape((string)($item['meta'] ?? '-')) ?></p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full <?= $escape((string)($item['status_class'] ?? 'bg-slate-100 text-slate-700')) ?>">
                                <?= $escape((string)($item['status_label'] ?? 'New')) ?>
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
<?php endif; ?>