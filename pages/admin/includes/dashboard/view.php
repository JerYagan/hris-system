<?php if ($state && $message): ?>
    <?php $alertClass = $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'; ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm"><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <article class="bg-white border border-slate-200 rounded-xl p-4">
        <p class="text-xs uppercase text-slate-500 tracking-wide">Attendance Alerts</p>
        <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['attendance_alerts'] ?></p>
        <p class="text-xs text-amber-700 mt-1">Flagged in attendance overview</p>
    </article>
    <article class="bg-white border border-slate-200 rounded-xl p-4">
        <p class="text-xs uppercase text-slate-500 tracking-wide">Pending Leave Requests</p>
        <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['pending_leave_requests'] ?></p>
        <p class="text-xs text-amber-700 mt-1">Awaiting admin action</p>
    </article>
    <article class="bg-white border border-slate-200 rounded-xl p-4">
        <p class="text-xs uppercase text-slate-500 tracking-wide">Draft Announcements</p>
        <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['draft_announcements'] ?></p>
        <p class="text-xs text-blue-700 mt-1">Saved in announcement drafts</p>
    </article>
    <article class="bg-white border border-slate-200 rounded-xl p-4">
        <p class="text-xs uppercase text-slate-500 tracking-wide">Unread Notifications</p>
        <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['unread_notifications'] ?></p>
        <p class="text-xs text-purple-700 mt-1">Includes <?= (int)$dashboardSummary['high_priority_notifications'] ?> high-priority items</p>
    </article>
</section>

<section class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-slate-800">Attendance Overview</h2>
        <a href="timekeeping.php" class="text-sm text-emerald-700 hover:underline">Open module</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase text-emerald-700">Present Today</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['present_today'] ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-rose-50">
            <p class="text-xs uppercase text-rose-700">Absent</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['absent_today'] ?></p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-slate-800">Pending Leave Requests</h2>
        <a href="timekeeping.php" class="text-sm text-emerald-700 hover:underline">Review all</a>
    </div>

    <div class="pb-3 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600" for="dashboardLeaveSearch">Search Requests</label>
            <input id="dashboardLeaveSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search employee, leave type, or date range">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600" for="dashboardLeaveStatusFilter">Status</label>
            <select id="dashboardLeaveStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="dashboardPendingLeaveTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Leave Type</th>
                    <th class="text-left px-4 py-3">Days</th>
                    <th class="text-left px-4 py-3">Date Range</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($pendingLeaveRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="6">No pending leave requests available.</td></tr>
                <?php else: ?>
                    <?php foreach ($pendingLeaveRows as $row): ?>
                        <tr
                            data-dashboard-leave-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>"
                            data-dashboard-leave-status="<?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['employee_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['leave_type'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['days_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['date_range'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)$row['status_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"
                                    data-dashboard-leave-open
                                    data-leave-request-id="<?= htmlspecialchars((string)$row['id'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-employee-name="<?= htmlspecialchars((string)$row['employee_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status="<?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-date-range="<?= htmlspecialchars((string)$row['date_range'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <span class="material-symbols-outlined text-[15px]">rate_review</span>
                                    Review
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-slate-800">Create New Announcement</h2>
        <button type="button" data-modal-open="dashboardAnnouncementModal" class="text-sm text-emerald-700 hover:underline">Open form</button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">Latest Draft</p>
            <p class="font-medium text-slate-800 mt-2"><?= htmlspecialchars((string)$dashboardSummary['latest_announcement_title'], ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)$dashboardSummary['latest_announcement_timestamp'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">Publish Queue</p>
            <p class="font-medium text-slate-800 mt-2"><?= (int)$dashboardSummary['queued_announcements'] ?> announcements pending</p>
            <p class="text-xs text-slate-500 mt-1">Awaiting final admin confirmation</p>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-slate-800">View Notifications</h2>
        <a href="notifications.php" class="text-sm text-emerald-700 hover:underline">Open all notifications</a>
    </div>

    <div class="pb-3 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600" for="dashboardNotificationsSearch">Search Notifications</label>
            <input id="dashboardNotificationsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search title, body, or category">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600" for="dashboardNotificationsStatusFilter">Status</label>
            <select id="dashboardNotificationsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="Unread">Unread</option>
                <option value="Read">Read</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="dashboardNotificationsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Title</th>
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Created</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($notificationsRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="5">No notifications available.</td></tr>
                <?php else: ?>
                    <?php foreach ($notificationsRows as $row): ?>
                        <tr
                            data-dashboard-notification-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>"
                            data-dashboard-notification-status="<?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800"><?= htmlspecialchars((string)$row['title'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)$row['body'], ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)ucfirst((string)$row['category']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)$row['status_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <?php if ((bool)$row['is_read']): ?>
                                    <span class="text-xs text-slate-500">Read</span>
                                <?php else: ?>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"
                                        data-dashboard-notification-open
                                        data-notification-id="<?= htmlspecialchars((string)$row['id'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-notification-title="<?= htmlspecialchars((string)$row['title'], ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">mark_email_read</span>
                                        Mark as Read
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-slate-800">Plantilla</h2>
            <a href="report-analytics.php" class="text-sm text-emerald-700 hover:underline">Open plantilla</a>
        </div>
        <div class="space-y-2 text-sm">
            <p class="flex justify-between"><span>Approved Positions</span><span class="font-semibold text-slate-800"><?= (int)$dashboardSummary['approved_positions'] ?></span></p>
            <p class="flex justify-between"><span>Filled Positions</span><span class="font-semibold text-slate-800"><?= (int)$dashboardSummary['filled_positions'] ?></span></p>
            <p class="flex justify-between"><span>Vacant Positions</span><span class="font-semibold text-slate-800"><?= (int)$dashboardSummary['vacant_positions'] ?></span></p>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-slate-800">Employees per Department</h2>
            <a href="report-analytics.php" class="text-sm text-emerald-700 hover:underline">View full report</a>
        </div>

        <div class="mb-3">
            <label class="text-sm text-slate-600" for="dashboardDepartmentSearch">Search Department</label>
            <input id="dashboardDepartmentSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search department name">
        </div>

        <div class="overflow-x-auto">
            <table id="dashboardDepartmentTable" class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Department</th>
                        <th class="text-left px-4 py-3">Headcount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($departmentRows)): ?>
                        <tr><td class="px-4 py-3 text-slate-500" colspan="2">No department headcount records available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($departmentRows as $row): ?>
                            <tr data-dashboard-department-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>">
                                <td class="px-4 py-3"><?= htmlspecialchars((string)$row['department'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 font-semibold text-slate-800"><?= (int)$row['headcount'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl p-6 mt-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-slate-800">Recruitment Pipeline Chart</h2>
        <span class="text-xs text-slate-500">Updated <?= htmlspecialchars((string)$pipelineChart['updated_at'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="h-72">
        <canvas
            data-chart-type="bar"
            data-chart-label="Applicants"
            data-chart-labels='<?= htmlspecialchars((string)json_encode($pipelineChart['labels'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>'
            data-chart-values='<?= htmlspecialchars((string)json_encode($pipelineChart['values'], JSON_NUMERIC_CHECK), ENT_QUOTES, 'UTF-8') ?>'
            data-chart-colors='["#0f172a", "#10b981", "#3b82f6", "#f59e0b"]'
        ></canvas>
    </div>
</section>

<div id="dashboardLeaveReviewModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="dashboardLeaveReviewModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Review Leave Request</h3>
                <button type="button" data-modal-close="dashboardLeaveReviewModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="dashboard.php" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm">
                <input type="hidden" name="form_action" value="review_leave_request_dashboard">
                <input type="hidden" name="leave_request_id" id="dashboardLeaveRequestId">

                <div>
                    <label class="text-slate-600">Employee</label>
                    <input id="dashboardLeaveEmployeeName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Current Status</label>
                    <input id="dashboardLeaveCurrentStatus" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Date Range</label>
                    <input id="dashboardLeaveDateRange" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600" for="dashboardLeaveDecision">Decision</label>
                    <select id="dashboardLeaveDecision" name="decision" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="approved">Approve</option>
                        <option value="rejected">Reject</option>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600" for="dashboardLeaveNotes">Notes</label>
                    <textarea id="dashboardLeaveNotes" name="notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Optional review notes"></textarea>
                </div>

                <div class="flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="dashboardLeaveReviewModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="dashboardAnnouncementModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="dashboardAnnouncementModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Create New Announcement</h3>
                <button type="button" data-modal-close="dashboardAnnouncementModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="dashboard.php" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm">
                <input type="hidden" name="form_action" value="save_dashboard_announcement">

                <div>
                    <label class="text-slate-600" for="dashboardAnnouncementTitle">Title</label>
                    <input id="dashboardAnnouncementTitle" name="announcement_title" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$dashboardSummary['latest_announcement_title'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div>
                    <label class="text-slate-600" for="dashboardAnnouncementBody">Message</label>
                    <textarea id="dashboardAnnouncementBody" name="announcement_body" rows="5" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required><?= htmlspecialchars((string)$dashboardSummary['latest_announcement_body'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div>
                    <label class="text-slate-600" for="dashboardAnnouncementState">Action</label>
                    <select id="dashboardAnnouncementState" name="announcement_state" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                        <option value="draft">Save as Draft</option>
                        <option value="queued">Add to Publish Queue</option>
                    </select>
                </div>

                <div class="flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="dashboardAnnouncementModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="dashboardNotificationModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="dashboardNotificationModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-lg bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Mark Notification as Read</h3>
                <button type="button" data-modal-close="dashboardNotificationModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="dashboard.php" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm">
                <input type="hidden" name="form_action" value="mark_dashboard_notification_read">
                <input type="hidden" name="notification_id" id="dashboardNotificationId">

                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-slate-700">
                    <p class="text-xs uppercase text-slate-500">Notification</p>
                    <p id="dashboardNotificationTitle" class="font-medium text-slate-800 mt-1">-</p>
                </div>

                <div class="flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="dashboardNotificationModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>
