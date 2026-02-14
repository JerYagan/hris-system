<?php
$attendancePill = static function (string $status): array {
    $key = strtolower(trim($status));
    if (in_array($key, ['present'], true)) {
        return ['Present', 'bg-emerald-100 text-emerald-800'];
    }
    if (in_array($key, ['late'], true)) {
        return ['Late', 'bg-amber-100 text-amber-800'];
    }
    if (in_array($key, ['leave', 'holiday', 'rest_day'], true)) {
        return [ucwords(str_replace('_', ' ', $key)), 'bg-blue-100 text-blue-800'];
    }
    if (in_array($key, ['absent'], true)) {
        return ['Absent', 'bg-rose-100 text-rose-800'];
    }

    return [ucwords(str_replace('_', ' ', $key !== '' ? $key : 'present')), 'bg-slate-100 text-slate-700'];
};

$requestPill = static function (string $status): array {
    $key = strtolower(trim($status));
    if ($key === 'approved') {
        return ['Approved', 'bg-emerald-100 text-emerald-800'];
    }
    if ($key === 'rejected') {
        return ['Rejected', 'bg-rose-100 text-rose-800'];
    }
    if ($key === 'needs_revision') {
        return ['Needs Revision', 'bg-blue-100 text-blue-800'];
    }

    return ['Pending', 'bg-amber-100 text-amber-800'];
};
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Timekeeping</h1>
        <p class="text-sm text-slate-300 mt-2">Monitor attendance and process leave or time adjustment requests using modal actions.</p>
    </div>
</div>

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
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Attendance Snapshot</h2>
        <p class="text-sm text-slate-500 mt-1">Latest logs used for timekeeping validation.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table id="attendanceSnapshotTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Time In</th>
                    <th class="text-left px-4 py-3">Time Out</th>
                    <th class="text-left px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($attendanceLogs)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="5">No attendance logs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($attendanceLogs as $log): ?>
                        <?php
                        $employeeName = trim(((string)($log['person']['first_name'] ?? '')) . ' ' . ((string)($log['person']['surname'] ?? '')));
                        if ($employeeName === '') {
                            $employeeName = 'Unknown Employee';
                        }
                        $attendanceDate = (string)($log['attendance_date'] ?? '');
                        $dateLabel = $attendanceDate !== '' ? date('M d, Y', strtotime($attendanceDate)) : '-';
                        $timeIn = cleanText($log['time_in'] ?? null);
                        $timeOut = cleanText($log['time_out'] ?? null);
                        $timeInLabel = $timeIn ? date('h:i A', strtotime($timeIn)) : '-';
                        $timeOutLabel = $timeOut ? date('h:i A', strtotime($timeOut)) : '-';
                        [$statusLabel, $statusClass] = $attendancePill((string)($log['attendance_status'] ?? 'present'));
                        ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($timeInLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($timeOutLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[95px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Time Adjustment Requests</h2>
        <p class="text-sm text-slate-500 mt-1">Review employee correction requests for missed or incorrect log entries.</p>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Requests</label>
            <input id="adjustmentsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by employee, reason, or date">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="adjustmentsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
                <option value="Needs Revision">Needs Revision</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="timeAdjustmentsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Attendance Date</th>
                    <th class="text-left px-4 py-3">Requested Window</th>
                    <th class="text-left px-4 py-3">Reason</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Submitted</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($adjustmentRequests)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="7">No time adjustment requests found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($adjustmentRequests as $request): ?>
                        <?php
                        $requestId = (string)($request['id'] ?? '');
                        $employeeName = trim(((string)($request['person']['first_name'] ?? '')) . ' ' . ((string)($request['person']['surname'] ?? '')));
                        if ($employeeName === '') {
                            $employeeName = 'Unknown Employee';
                        }
                        $attendanceDate = (string)($request['attendance']['attendance_date'] ?? '');
                        $attendanceDateLabel = $attendanceDate !== '' ? date('M d, Y', strtotime($attendanceDate)) : '-';

                        $requestedTimeIn = cleanText($request['requested_time_in'] ?? null);
                        $requestedTimeOut = cleanText($request['requested_time_out'] ?? null);
                        $requestedWindow = ($requestedTimeIn ? date('h:i A', strtotime($requestedTimeIn)) : '-') . ' - ' . ($requestedTimeOut ? date('h:i A', strtotime($requestedTimeOut)) : '-');

                        $reason = (string)($request['reason'] ?? '-');
                        $submittedAt = (string)($request['created_at'] ?? '');
                        $submittedLabel = $submittedAt !== '' ? date('M d, Y', strtotime($submittedAt)) : '-';

                        [$statusLabel, $statusClass] = $requestPill((string)($request['status'] ?? 'pending'));
                        $searchText = strtolower(trim($employeeName . ' ' . $reason . ' ' . $attendanceDateLabel . ' ' . $statusLabel));
                        ?>
                        <tr data-adjust-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-adjust-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($attendanceDateLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($requestedWindow, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[105px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($submittedLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-adjust-review
                                    data-request-id="<?= htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8') ?>"
                                    data-employee-name="<?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>"
                                    data-requested-window="<?= htmlspecialchars($requestedWindow, ENT_QUOTES, 'UTF-8') ?>"
                                    class="px-2.5 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50"
                                >
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

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Leave Requests</h2>
        <p class="text-sm text-slate-500 mt-1">Apply leave decisions and keep payroll-impacting records updated.</p>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Requests</label>
            <input id="leaveRequestsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by employee, leave type, or reason">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="leaveRequestsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
                <option value="Cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="leaveRequestsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Leave Type</th>
                    <th class="text-left px-4 py-3">Date Range</th>
                    <th class="text-left px-4 py-3">Days</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Requested</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($leaveRequests)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="7">No leave requests found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leaveRequests as $leave): ?>
                        <?php
                        $leaveRequestId = (string)($leave['id'] ?? '');
                        $employeeName = trim(((string)($leave['person']['first_name'] ?? '')) . ' ' . ((string)($leave['person']['surname'] ?? '')));
                        if ($employeeName === '') {
                            $employeeName = 'Unknown Employee';
                        }

                        $leaveType = (string)($leave['leave_type']['leave_name'] ?? 'Unassigned');
                        $dateFrom = (string)($leave['date_from'] ?? '');
                        $dateTo = (string)($leave['date_to'] ?? '');
                        $dateRange = ($dateFrom !== '' ? date('M d, Y', strtotime($dateFrom)) : '-') . ' - ' . ($dateTo !== '' ? date('M d, Y', strtotime($dateTo)) : '-');
                        $daysCount = (string)($leave['days_count'] ?? '-');

                        $statusRaw = strtolower((string)($leave['status'] ?? 'pending'));
                        $statusLabel = ucfirst($statusRaw);
                        $statusClass = $statusRaw === 'approved'
                            ? 'bg-emerald-100 text-emerald-800'
                            : ($statusRaw === 'rejected'
                                ? 'bg-rose-100 text-rose-800'
                                : ($statusRaw === 'cancelled' ? 'bg-slate-200 text-slate-700' : 'bg-amber-100 text-amber-800'));

                        $requestedAt = (string)($leave['created_at'] ?? '');
                        $requestedLabel = $requestedAt !== '' ? date('M d, Y', strtotime($requestedAt)) : '-';
                        $reason = (string)($leave['reason'] ?? '-');
                        $searchText = strtolower(trim($employeeName . ' ' . $leaveType . ' ' . $reason . ' ' . $statusLabel));
                        ?>
                        <tr data-leave-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-leave-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($leaveType, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($dateRange, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($daysCount, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[95px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($requestedLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-leave-review
                                    data-leave-request-id="<?= htmlspecialchars($leaveRequestId, ENT_QUOTES, 'UTF-8') ?>"
                                    data-employee-name="<?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>"
                                    data-date-range="<?= htmlspecialchars($dateRange, ENT_QUOTES, 'UTF-8') ?>"
                                    class="px-2.5 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50"
                                >
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

<div id="reviewAdjustmentModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="reviewAdjustmentModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Review Time Adjustment</h3>
                <button type="button" data-modal-close="reviewAdjustmentModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="timekeeping.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="review_time_adjustment">
                <input type="hidden" id="adjustmentRequestId" name="request_id" value="">
                <div class="md:col-span-2">
                    <label class="text-slate-600">Employee</label>
                    <input id="adjustmentEmployeeName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Current Status</label>
                    <input id="adjustmentCurrentStatus" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Requested Window</label>
                    <input id="adjustmentRequestedWindow" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Decision</label>
                    <select name="decision" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="approved">Approve</option>
                        <option value="rejected">Reject</option>
                        <option value="needs_revision">Needs Revision</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Notes</label>
                    <textarea name="notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add decision rationale or required corrections."></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="reviewAdjustmentModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Decision</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="reviewLeaveModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="reviewLeaveModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Review Leave Request</h3>
                <button type="button" data-modal-close="reviewLeaveModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="timekeeping.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="review_leave_request">
                <input type="hidden" id="leaveRequestId" name="leave_request_id" value="">
                <div class="md:col-span-2">
                    <label class="text-slate-600">Employee</label>
                    <input id="leaveEmployeeName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Current Status</label>
                    <input id="leaveCurrentStatus" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Date Range</label>
                    <input id="leaveDateRange" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Decision</label>
                    <select name="decision" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="approved">Approve</option>
                        <option value="rejected">Reject</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Notes</label>
                    <textarea name="notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add approval conditions or rejection reason."></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="reviewLeaveModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Decision</button>
                </div>
            </form>
        </div>
    </div>
</div>
