<?php
$attendancePill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'present' => ['Present', 'bg-emerald-100 text-emerald-800'],
        'late' => ['Late', 'bg-amber-100 text-amber-800'],
        'absent' => ['Absent', 'bg-rose-100 text-rose-800'],
        'leave' => ['Leave', 'bg-blue-100 text-blue-800'],
        'holiday' => ['Holiday', 'bg-indigo-100 text-indigo-800'],
        'rest_day' => ['Rest Day', 'bg-slate-200 text-slate-700'],
        'approved' => ['Approved', 'bg-emerald-100 text-emerald-800'],
        'rejected' => ['Rejected', 'bg-rose-100 text-rose-800'],
        'needs_revision' => ['Needs Revision', 'bg-blue-100 text-blue-800'],
        'cancelled' => ['Cancelled', 'bg-slate-200 text-slate-700'],
        default => [ucwords(str_replace('_', ' ', $key !== '' ? $key : 'pending')), 'bg-amber-100 text-amber-800'],
    };
};

$formatDate = static function (?string $raw, string $format = 'M d, Y'): string {
    $value = trim((string)$raw);
    if ($value === '') {
        return '-';
    }

    $ts = strtotime($value);
    if ($ts === false) {
        return $value;
    }

    return date($format, $ts);
};

$formatTime = static function (?string $raw): string {
    $value = trim((string)$raw);
    if ($value === '') {
        return '-';
    }

    $ts = strtotime($value);
    if ($ts === false) {
        return $value;
    }

    return date('h:i A', $ts);
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
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Timekeeping Policy Baseline</h2>
        <p class="text-sm text-slate-500 mt-1">Flexi schedules (7AM-4PM, 8AM-5PM, 9AM-6PM) are enabled. Time-in at 9:01 AM onwards is tagged as late.</p>
    </header>
    <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
        <div class="rounded-lg border border-slate-200 p-3 bg-slate-50">
            <p class="text-slate-500 text-xs uppercase tracking-wide">Snapshot Date</p>
            <p class="font-semibold text-slate-800 mt-1"><?= htmlspecialchars((string)($attendanceSummaryToday['date_label'] ?? date('M d, Y')), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="rounded-lg border border-slate-200 p-3 bg-slate-50">
            <p class="text-slate-500 text-xs uppercase tracking-wide">Present</p>
            <p class="font-semibold text-emerald-700 mt-1"><?= (int)($attendanceSummaryToday['present'] ?? 0) ?></p>
        </div>
        <div class="rounded-lg border border-slate-200 p-3 bg-slate-50">
            <p class="text-slate-500 text-xs uppercase tracking-wide">Late</p>
            <p class="font-semibold text-amber-700 mt-1"><?= (int)($attendanceSummaryToday['late'] ?? 0) ?></p>
        </div>
        <div class="rounded-lg border border-slate-200 p-3 bg-slate-50">
            <p class="text-slate-500 text-xs uppercase tracking-wide">Absent</p>
            <p class="font-semibold text-rose-700 mt-1"><?= (int)($attendanceSummaryToday['absent'] ?? 0) ?></p>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Staff Recommendations Queue</h2>
        <p class="text-sm text-slate-500 mt-1">Review staff-submitted recommendations with approval controls and audit log context.</p>
    </header>
    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Submitted At</th>
                    <th class="text-left px-4 py-3">Staff</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Recommended</th>
                    <th class="text-left px-4 py-3">Current</th>
                    <th class="text-left px-4 py-3">Audit Notes</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($staffRecommendationRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="8">No staff recommendations found.</td></tr>
                <?php else: ?>
                    <?php foreach ($staffRecommendationRows as $row): ?>
                        <?php
                        [$recommendedLabel, $recommendedClass] = $attendancePill((string)($row['recommended_status'] ?? 'pending'));
                        [$currentLabel, $currentClass] = $attendancePill((string)($row['current_status'] ?? 'pending'));
                        $actionType = (string)($row['action_type'] ?? '');
                        $isFinal = (bool)($row['is_final'] ?? false);
                        ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars($formatDate((string)($row['submitted_at'] ?? ''), 'M d, Y h:i A'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['staff_actor'] ?? 'Staff User'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['request_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($recommendedClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($recommendedLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($currentClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($currentLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <p class="text-slate-700"><?= htmlspecialchars((string)($row['notes'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-[11px] text-slate-500 mt-1">Log: <?= htmlspecialchars((string)($row['log_id'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($actionType === 'leave'): ?>
                                    <button type="button" data-leave-review data-leave-request-id="<?= htmlspecialchars((string)($row['entity_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars((string)($row['current_status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?>" data-date-range="<?= htmlspecialchars((string)($row['date_range'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-leave-type="<?= htmlspecialchars((string)($row['leave_type'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8') ?>" data-leave-reason="<?= htmlspecialchars((string)($row['reason'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50" <?= $isFinal ? 'disabled' : '' ?>><span class="material-symbols-outlined text-[15px]">approval</span><?= $isFinal ? 'Finalized' : 'Approve/Reject' ?></button>
                                <?php elseif ($actionType === 'adjustment'): ?>
                                    <button type="button" data-adjust-review data-request-id="<?= htmlspecialchars((string)($row['entity_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars((string)($row['current_status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?>" data-requested-window="<?= htmlspecialchars((string)($row['window'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50" <?= $isFinal ? 'disabled' : '' ?>><span class="material-symbols-outlined text-[15px]">approval</span><?= $isFinal ? 'Finalized' : 'Approve/Reject' ?></button>
                                <?php elseif ($actionType === 'cto'): ?>
                                    <button type="button" data-cto-review data-request-id="<?= htmlspecialchars((string)($row['entity_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars((string)($row['current_status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?>" data-window="<?= htmlspecialchars((string)($row['window'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50" <?= $isFinal ? 'disabled' : '' ?>><span class="material-symbols-outlined text-[15px]">approval</span><?= $isFinal ? 'Finalized' : 'Approve/Reject' ?></button>
                                <?php elseif ($actionType === 'ob'): ?>
                                    <button type="button" data-ob-review data-request-id="<?= htmlspecialchars((string)($row['entity_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars((string)($row['current_status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?>" data-window="<?= htmlspecialchars((string)($row['window'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50" <?= $isFinal ? 'disabled' : '' ?>><span class="material-symbols-outlined text-[15px]">approval</span><?= $isFinal ? 'Finalized' : 'Approve/Reject' ?></button>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Attendance Records</h2>
            <p class="text-sm text-slate-500 mt-1">Current day attendance only.</p>
        </div>
        <div class="flex gap-2">
            <a href="timekeeping.php?export=attendance_today_csv" class="px-3 py-2 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Download CSV</a>
            <button type="button" id="attendancePrintButton" class="px-3 py-2 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Print</button>
        </div>
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
                    <tr><td class="px-4 py-3 text-slate-500" colspan="5">No attendance logs found for today.</td></tr>
                <?php else: ?>
                    <?php foreach ($attendanceLogs as $log): ?>
                        <?php [$statusLabel, $statusClass] = $attendancePill((string)($log['display_status'] ?? 'present')); ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($log['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($formatDate((string)($log['attendance_date'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($formatTime((string)($log['time_in'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($formatTime((string)($log['time_out'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
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
        <p class="text-sm text-slate-500 mt-1">Final admin decisions with lock after approved/rejected.</p>
    </header>
    <div class="p-6 overflow-x-auto">
        <table id="timeAdjustmentsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Requested Window</th>
                    <th class="text-left px-4 py-3">Reason</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($adjustmentRequests)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="6">No time adjustment requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($adjustmentRequests as $request): ?>
                        <?php
                        $statusRaw = strtolower((string)($request['status'] ?? 'pending'));
                        $locked = in_array($statusRaw, ['approved', 'rejected'], true);
                        [$statusLabel, $statusClass] = $attendancePill($statusRaw);
                        $employeeName = trim((string)($request['person']['first_name'] ?? '') . ' ' . (string)($request['person']['surname'] ?? ''));
                        if ($employeeName === '') {
                            $employeeName = 'Unknown Employee';
                        }
                        $requestedWindow = $formatTime((string)($request['requested_time_in'] ?? '')) . ' - ' . $formatTime((string)($request['requested_time_out'] ?? ''));
                        ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($formatDate((string)($request['attendance']['attendance_date'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($requestedWindow, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($request['reason'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <button type="button" data-adjust-review data-request-id="<?= htmlspecialchars((string)($request['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>" data-requested-window="<?= htmlspecialchars($requestedWindow, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50" <?= $locked ? 'disabled' : '' ?>>
                                    <span class="material-symbols-outlined text-[15px]">rate_review</span><?= $locked ? 'Locked' : 'Review' ?>
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
        <p class="text-sm text-slate-500 mt-1">Leave requests remain defaulted to pending and rejected entries are locked.</p>
    </header>
    <div class="p-6 overflow-x-auto">
        <table id="leaveRequestsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Leave Type</th>
                    <th class="text-left px-4 py-3">Date Range</th>
                    <th class="text-left px-4 py-3">Reason</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($leaveRequests)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="6">No leave requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($leaveRequests as $leave): ?>
                        <?php
                        $statusRaw = strtolower((string)($leave['status'] ?? 'pending'));
                        $locked = in_array($statusRaw, ['approved', 'rejected', 'cancelled'], true);
                        [$statusLabel, $statusClass] = $attendancePill($statusRaw);
                        $employeeName = trim((string)($leave['person']['first_name'] ?? '') . ' ' . (string)($leave['person']['surname'] ?? ''));
                        if ($employeeName === '') {
                            $employeeName = 'Unknown Employee';
                        }
                        $leaveType = (string)($leave['leave_type']['leave_name'] ?? 'Unassigned');
                        $dateRange = $formatDate((string)($leave['date_from'] ?? '')) . ' - ' . $formatDate((string)($leave['date_to'] ?? ''));
                        $reason = (string)($leave['reason'] ?? '-');
                        ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($leaveType, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($dateRange, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <button type="button" data-leave-review data-leave-request-id="<?= htmlspecialchars((string)($leave['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>" data-date-range="<?= htmlspecialchars($dateRange, ENT_QUOTES, 'UTF-8') ?>" data-leave-type="<?= htmlspecialchars($leaveType, ENT_QUOTES, 'UTF-8') ?>" data-leave-reason="<?= htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50" <?= $locked ? 'disabled' : '' ?>>
                                    <span class="material-symbols-outlined text-[15px]">rate_review</span><?= $locked ? 'Locked' : 'Review' ?>
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
        <h2 class="text-lg font-semibold text-slate-800">CTO Requests</h2>
        <p class="text-sm text-slate-500 mt-1">Admin final decision queue for CTO-only process.</p>
    </header>
    <div class="p-6 overflow-x-auto">
        <table id="ctoRequestsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Window</th>
                    <th class="text-left px-4 py-3">Hours</th>
                    <th class="text-left px-4 py-3">Reason</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($ctoRequests)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="7">No CTO requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($ctoRequests as $cto): ?>
                        <?php
                        $statusRaw = strtolower((string)($cto['status'] ?? 'pending'));
                        $locked = in_array($statusRaw, ['approved', 'rejected', 'cancelled'], true);
                        [$statusLabel, $statusClass] = $attendancePill($statusRaw);
                        $window = $formatTime((string)($cto['start_time'] ?? '')) . ' - ' . $formatTime((string)($cto['end_time'] ?? ''));
                        ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($cto['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($formatDate((string)($cto['overtime_date'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($window, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars(number_format((float)($cto['hours_requested'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($cto['reason'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <button type="button" data-cto-review data-request-id="<?= htmlspecialchars((string)($cto['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)($cto['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>" data-window="<?= htmlspecialchars($window, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50" <?= $locked ? 'disabled' : '' ?>>
                                    <span class="material-symbols-outlined text-[15px]">rate_review</span><?= $locked ? 'Locked' : 'Review' ?>
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
        <h2 class="text-lg font-semibold text-slate-800">Official Business Requests</h2>
        <p class="text-sm text-slate-500 mt-1">Pending and finalized OB approvals.</p>
    </header>
    <div class="p-6 overflow-x-auto">
        <table id="obRequestsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Window</th>
                    <th class="text-left px-4 py-3">Reason</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($obRequests)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="6">No OB requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($obRequests as $ob): ?>
                        <?php
                        $statusRaw = strtolower((string)($ob['status'] ?? 'pending'));
                        $locked = in_array($statusRaw, ['approved', 'rejected', 'cancelled'], true);
                        [$statusLabel, $statusClass] = $attendancePill($statusRaw);
                        $window = $formatTime((string)($ob['start_time'] ?? '')) . ' - ' . $formatTime((string)($ob['end_time'] ?? ''));
                        ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($ob['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($formatDate((string)($ob['overtime_date'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($window, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($ob['reason'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <button type="button" data-ob-review data-request-id="<?= htmlspecialchars((string)($ob['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)($ob['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>" data-window="<?= htmlspecialchars($window, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50" <?= $locked ? 'disabled' : '' ?>>
                                    <span class="material-symbols-outlined text-[15px]">rate_review</span><?= $locked ? 'Locked' : 'Review' ?>
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
        <h2 class="text-lg font-semibold text-slate-800">Employee Timekeeping History</h2>
        <p class="text-sm text-slate-500 mt-1">Complete cross-request history for review context.</p>
    </header>
    <div class="p-6 overflow-x-auto">
        <table id="timekeepingHistoryTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Summary</th>
                    <th class="text-left px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($historyEntries)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="5">No timekeeping history records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($historyEntries as $entry): ?>
                        <?php [$statusLabel, $statusClass] = $attendancePill((string)($entry['status'] ?? 'pending')); ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($entry['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($entry['entry_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($formatDate((string)($entry['entry_date'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($entry['summary'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Holiday/Suspension Configuration</h2>
        <p class="text-sm text-slate-500 mt-1">Configure holidays and payroll paid-handling behavior.</p>
    </header>
    <form action="timekeeping.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <input type="hidden" name="form_action" value="save_holiday_config">
        <div>
            <label class="text-slate-600">Holiday Date</label>
            <input type="date" name="holiday_date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-slate-600">Holiday Name</label>
            <input type="text" name="holiday_name" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="e.g. National Heroes Day">
        </div>
        <div>
            <label class="text-slate-600">Holiday Type</label>
            <select name="holiday_type" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="regular">Regular</option>
                <option value="special">Special</option>
                <option value="local">Local</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Payroll Paid Handling</label>
            <select name="paid_handling" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="policy_based" <?= (($holidayPayrollPolicy['paid_handling'] ?? 'policy_based') === 'policy_based') ? 'selected' : '' ?>>Policy-based</option>
                <option value="always_paid" <?= (($holidayPayrollPolicy['paid_handling'] ?? '') === 'always_paid') ? 'selected' : '' ?>>Always Paid</option>
                <option value="always_unpaid" <?= (($holidayPayrollPolicy['paid_handling'] ?? '') === 'always_unpaid') ? 'selected' : '' ?>>Always Unpaid</option>
            </select>
        </div>
        <label class="inline-flex items-center gap-2"><input type="checkbox" name="apply_to_regular" <?= !empty($holidayPayrollPolicy['apply_to_regular']) ? 'checked' : '' ?>> <span>Apply paid rules to Regular holidays</span></label>
        <label class="inline-flex items-center gap-2"><input type="checkbox" name="apply_to_special" <?= !empty($holidayPayrollPolicy['apply_to_special']) ? 'checked' : '' ?>> <span>Apply paid rules to Special holidays</span></label>
        <label class="inline-flex items-center gap-2"><input type="checkbox" name="apply_to_local" <?= !empty($holidayPayrollPolicy['apply_to_local']) ? 'checked' : '' ?>> <span>Apply paid rules to Local holidays</span></label>
        <label class="inline-flex items-center gap-2"><input type="checkbox" name="include_suspension" <?= !empty($holidayPayrollPolicy['include_suspension']) ? 'checked' : '' ?>> <span>Include suspension days in paid handling policy</span></label>
        <div class="md:col-span-2 flex justify-end">
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Holiday/Payroll Settings</button>
        </div>
    </form>

    <div class="px-6 pb-6 overflow-x-auto">
        <table id="holidayConfigTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Holiday</th>
                    <th class="text-left px-4 py-3">Type</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($holidayRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="3">No holiday configuration records yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($holidayRows as $holiday): ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars($formatDate((string)($holiday['holiday_date'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($holiday['holiday_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars(ucfirst((string)($holiday['holiday_type'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></td>
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
                <div class="md:col-span-2"><label class="text-slate-600">Employee</label><input id="adjustmentEmployeeName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Current Status</label><input id="adjustmentCurrentStatus" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Requested Window</label><input id="adjustmentRequestedWindow" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Decision</label><select name="decision" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required><option value="approved">Approve</option><option value="rejected">Reject</option><option value="needs_revision">Needs Revision</option></select></div>
                <div class="md:col-span-2"><label class="text-slate-600">Notes</label><textarea name="notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add decision rationale or required corrections."></textarea></div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2"><button type="button" data-modal-close="reviewAdjustmentModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button><button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Decision</button></div>
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
                <div class="md:col-span-2"><label class="text-slate-600">Employee</label><input id="leaveEmployeeName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Current Status</label><input id="leaveCurrentStatus" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Date Range</label><input id="leaveDateRange" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Leave Type</label><input id="leaveTypeLabel" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div class="md:col-span-2"><label class="text-slate-600">Description/Reason</label><textarea id="leaveReasonLabel" rows="2" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></textarea></div>
                <div><label class="text-slate-600">Decision</label><select name="decision" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required><option value="approved">Approve</option><option value="rejected">Reject</option></select></div>
                <div class="md:col-span-2"><label class="text-slate-600">Notes</label><textarea name="notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add approval conditions or rejection reason."></textarea></div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2"><button type="button" data-modal-close="reviewLeaveModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button><button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Decision</button></div>
            </form>
        </div>
    </div>
</div>

<div id="reviewCtoModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="reviewCtoModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Review CTO Request</h3>
                <button type="button" data-modal-close="reviewCtoModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="timekeeping.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="review_cto_request">
                <input type="hidden" id="ctoRequestId" name="request_id" value="">
                <div class="md:col-span-2"><label class="text-slate-600">Employee</label><input id="ctoEmployeeName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Current Status</label><input id="ctoCurrentStatus" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Requested Window</label><input id="ctoWindow" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Decision</label><select name="decision" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required><option value="approved">Approve</option><option value="rejected">Reject</option></select></div>
                <div class="md:col-span-2"><label class="text-slate-600">Notes</label><textarea name="notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></textarea></div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2"><button type="button" data-modal-close="reviewCtoModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button><button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Decision</button></div>
            </form>
        </div>
    </div>
</div>

<div id="reviewObModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="reviewObModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Review Official Business Request</h3>
                <button type="button" data-modal-close="reviewObModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="timekeeping.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="review_ob_request">
                <input type="hidden" id="obRequestId" name="request_id" value="">
                <div class="md:col-span-2"><label class="text-slate-600">Employee</label><input id="obEmployeeName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Current Status</label><input id="obCurrentStatus" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Requested Window</label><input id="obWindow" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Decision</label><select name="decision" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required><option value="approved">Approve</option><option value="rejected">Reject</option></select></div>
                <div class="md:col-span-2"><label class="text-slate-600">Notes</label><textarea name="notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></textarea></div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2"><button type="button" data-modal-close="reviewObModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button><button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Decision</button></div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const setValue = (id, value) => {
        const el = document.getElementById(id);
        if (el) {
            el.value = value || '';
        }
    };

    const openModal = (id) => {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };

    document.getElementById('attendancePrintButton')?.addEventListener('click', () => window.print());

    document.querySelectorAll('[data-adjust-review]').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.hasAttribute('disabled')) return;
            setValue('adjustmentRequestId', button.getAttribute('data-request-id'));
            setValue('adjustmentEmployeeName', button.getAttribute('data-employee-name'));
            setValue('adjustmentCurrentStatus', button.getAttribute('data-current-status'));
            setValue('adjustmentRequestedWindow', button.getAttribute('data-requested-window'));
            openModal('reviewAdjustmentModal');
        });
    });

    document.querySelectorAll('[data-leave-review]').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.hasAttribute('disabled')) return;
            setValue('leaveRequestId', button.getAttribute('data-leave-request-id'));
            setValue('leaveEmployeeName', button.getAttribute('data-employee-name'));
            setValue('leaveCurrentStatus', button.getAttribute('data-current-status'));
            setValue('leaveDateRange', button.getAttribute('data-date-range'));
            setValue('leaveTypeLabel', button.getAttribute('data-leave-type'));
            setValue('leaveReasonLabel', button.getAttribute('data-leave-reason'));
            openModal('reviewLeaveModal');
        });
    });

    document.querySelectorAll('[data-cto-review]').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.hasAttribute('disabled')) return;
            setValue('ctoRequestId', button.getAttribute('data-request-id'));
            setValue('ctoEmployeeName', button.getAttribute('data-employee-name'));
            setValue('ctoCurrentStatus', button.getAttribute('data-current-status'));
            setValue('ctoWindow', button.getAttribute('data-window'));
            openModal('reviewCtoModal');
        });
    });

    document.querySelectorAll('[data-ob-review]').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.hasAttribute('disabled')) return;
            setValue('obRequestId', button.getAttribute('data-request-id'));
            setValue('obEmployeeName', button.getAttribute('data-employee-name'));
            setValue('obCurrentStatus', button.getAttribute('data-current-status'));
            setValue('obWindow', button.getAttribute('data-window'));
            openModal('reviewObModal');
        });
    });
})();
</script>
