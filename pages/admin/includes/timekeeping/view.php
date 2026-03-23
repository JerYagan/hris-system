<?php
$attendancePill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'present' => ['Present', 'bg-emerald-100 text-emerald-800'],
        'late' => ['Late', 'bg-amber-100 text-amber-800'],
        'absent' => ['Absent', 'bg-rose-100 text-rose-800'],
        'leave' => ['Leave', 'bg-blue-100 text-blue-800'],
        'travel' => ['Approved Travel', 'bg-indigo-100 text-indigo-800'],
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

    return formatDateTimeForPhilippines($value, $format);
};

$formatTime = static function (?string $raw): string {
    $value = trim((string)$raw);
    if ($value === '') {
        return '-';
    }

    return formatDateTimeForPhilippines($value, 'h:i A');
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
        <p class="text-sm text-slate-500 mt-1">Standard flexi schedules (7AM-4PM, 8AM-5PM, 9AM-6PM) remain enabled. COS schedule requests are reviewed separately and may extend up to 10:00 PM when approved. Time-in at 9:01 AM onwards is tagged as late.</p>
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
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Timekeeping Quick Actions</h2>
                <p class="text-sm text-slate-500 mt-1">Launch the most important admin timekeeping tools from dedicated modal workspaces at the top of the page.</p>
            </div>
        </div>
    </header>
    <div class="p-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <button type="button" data-quick-modal-open="attendanceHelperModal" class="group rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">Manual Support Tool</span>
                    <h3 class="mt-3 text-base font-semibold text-slate-800">Employee Attendance Helper</h3>
                    <p class="mt-2 text-sm text-slate-500">Open a focused modal to encode missed or delayed time-in and time-out entries.</p>
                </div>
                <span class="material-symbols-outlined text-amber-500">fact_check</span>
            </div>
            <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-amber-800">Open helper <span class="material-symbols-outlined text-[16px]">arrow_forward</span></span>
        </button>
        <button type="button" data-quick-modal-open="leaveCardLogModal" class="group rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-800">Quick Encode</span>
                    <h3 class="mt-3 text-base font-semibold text-slate-800">Log Leave from Leave Card</h3>
                    <p class="mt-2 text-sm text-slate-500">Launch the leave-card encoder without scrolling through the rest of the page.</p>
                </div>
                <span class="material-symbols-outlined text-emerald-500">event_note</span>
            </div>
            <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-emerald-800">Open logger <span class="material-symbols-outlined text-[16px]">arrow_forward</span></span>
        </button>
        <button type="button" data-quick-modal-open="holidayConfigModal" class="group rounded-2xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <span class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-800">Policy Setup</span>
                    <h3 class="mt-3 text-base font-semibold text-slate-800">Holiday/Suspension Configuration</h3>
                    <p class="mt-2 text-sm text-slate-500">Manage holiday dates and payroll handling rules in a dedicated configuration modal.</p>
                </div>
                <span class="material-symbols-outlined text-sky-500">calendar_month</span>
            </div>
            <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-sky-800">Open configuration <span class="material-symbols-outlined text-[16px]">arrow_forward</span></span>
        </button>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Contractual Employees (COS)</h2>
        <p class="text-sm text-slate-500 mt-1">Review active COS personnel and their latest flexible schedule proposal status before approving related requests.</p>
    </header>
    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Division</th>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Employment Status</th>
                    <th class="text-left px-4 py-3">Latest COS Proposal</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($cosEmployeeRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="6">No active COS employees found.</td></tr>
                <?php else: ?>
                    <?php foreach ($cosEmployeeRows as $row): ?>
                        <?php
                            $cosStatusRaw = strtolower((string)($row["latest_cos_status_raw"] ?? 'pending'));
                            $cosLocked = !empty($row["latest_cos_request_id"]) && in_array($cosStatusRaw, ['approved', 'rejected', 'cancelled'], true);
                            $cosWindow = trim((string)($row["latest_cos_window"] ?? '-'));
                        ?>
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars((string)($row["employee_name"] ?? "-"), ENT_QUOTES, "UTF-8") ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row["office_name"] ?? "-"), ENT_QUOTES, "UTF-8") ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row["position_title"] ?? "-"), ENT_QUOTES, "UTF-8") ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row["employment_status"] ?? "COS"), ENT_QUOTES, "UTF-8") ?></td>
                            <td class="px-4 py-3">
                                <?= htmlspecialchars((string)($row["latest_cos_status"] ?? "-"), ENT_QUOTES, "UTF-8") ?>
                                <?php if (!empty($row["latest_cos_requested_label"]) && $row["latest_cos_requested_label"] !== "-"): ?>
                                    <span class="block text-xs text-slate-500 mt-1">Requested: <?= htmlspecialchars((string)($row["latest_cos_requested_label"] ?? "-"), ENT_QUOTES, "UTF-8") ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if (!empty($row["latest_cos_request_id"])): ?>
                                    <button type="button" data-ob-review data-request-id="<?= htmlspecialchars((string)($row["latest_cos_request_id"] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)($row["employee_name"] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?>" data-request-type-label="<?= htmlspecialchars((string)($row["latest_cos_request_label"] ?? 'COS Schedule Proposal'), ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars((string)($row["latest_cos_status"] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?>" data-window="<?= htmlspecialchars($cosWindow !== '' ? $cosWindow : '-', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50" <?= $cosLocked ? 'disabled' : '' ?>>
                                        <span class="material-symbols-outlined text-[15px]">rate_review</span><?= $cosLocked ? 'Locked' : 'Review' ?>
                                    </button>
                                <?php else: ?>
                                    <span class="text-xs text-slate-500">No request</span>
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
    <div class="px-6 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Attendance</label>
            <input id="attendanceTableSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by employee, date, or time">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="attendanceTableStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="Present">Present</option>
                <option value="Late">Late</option>
                <option value="Absent">Absent</option>
            </select>
        </div>
    </div>
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
                        <?php
                        [$statusLabel, $statusClass] = $attendancePill((string)($log['display_status'] ?? 'present'));
                        $attendanceSearch = strtolower(trim((string)($log['employee_name'] ?? '') . ' ' . $formatDate((string)($log['attendance_date'] ?? '')) . ' ' . $formatTime((string)($log['time_in'] ?? '')) . ' ' . $formatTime((string)($log['time_out'] ?? '')) . ' ' . $statusLabel));
                        ?>
                        <tr data-page-row data-table-search="<?= htmlspecialchars($attendanceSearch, ENT_QUOTES, 'UTF-8') ?>" data-table-filter="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($log['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($formatDate((string)($log['attendance_date'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($formatTime((string)($log['time_in'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($formatTime((string)($log['time_out'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (!empty($log['source_label'])): ?>
                                    <span class="block text-xs text-slate-500 mt-1">Source: <?= htmlspecialchars((string)$log['source_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="attendancePaginationInfo" class="text-xs text-slate-500">Showing 0 to 0 of 0 entries</p>
        <div class="flex items-center gap-2">
            <button type="button" id="attendancePrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <span id="attendancePageLabel" class="text-xs text-slate-500 min-w-[88px] text-center">Page 1 of 1</span>
            <button type="button" id="attendanceNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">RFID Attendance Review</h2>
        <p class="text-sm text-slate-500 mt-1">Monitor assigned RFID coverage, kiosk readiness, and recent scan outcomes from one admin surface.</p>
    </header>
    <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
        <div class="rounded-lg border border-slate-200 p-3 bg-slate-50">
            <p class="text-slate-500 text-xs uppercase tracking-wide">Active RFID Cards</p>
            <p class="font-semibold text-emerald-700 mt-1"><?= (int)($rfidSummaryToday['active_cards'] ?? 0) ?></p>
        </div>
        <div class="rounded-lg border border-slate-200 p-3 bg-slate-50">
            <p class="text-slate-500 text-xs uppercase tracking-wide">Active Devices</p>
            <p class="font-semibold text-sky-700 mt-1"><?= (int)($rfidSummaryToday['active_devices'] ?? 0) ?></p>
        </div>
        <div class="rounded-lg border border-slate-200 p-3 bg-slate-50">
            <p class="text-slate-500 text-xs uppercase tracking-wide">Successful Taps Today</p>
            <p class="font-semibold text-emerald-700 mt-1"><?= (int)($rfidSummaryToday['tap_success'] ?? 0) ?></p>
        </div>
        <div class="rounded-lg border border-slate-200 p-3 bg-slate-50">
            <p class="text-slate-500 text-xs uppercase tracking-wide">RFID Exceptions Today</p>
            <p class="font-semibold text-rose-700 mt-1"><?= (int)($rfidSummaryToday['tap_failures'] ?? 0) ?></p>
        </div>
    </div>

    <div class="px-6 pb-6">
        <div class="overflow-x-auto">
            <table id="rfidRecentEventsTable" class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Scanned At</th>
                        <th class="text-left px-4 py-3">Employee</th>
                        <th class="text-left px-4 py-3">Card UID</th>
                        <th class="text-left px-4 py-3">Source</th>
                        <th class="text-left px-4 py-3">Device</th>
                        <th class="text-left px-4 py-3">Result</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($rfidRecentEventRows)): ?>
                        <tr><td class="px-4 py-3 text-slate-500" colspan="6">No RFID scan events found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rfidRecentEventRows as $row): ?>
                            <tr data-page-row>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($row['scanned_at_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-800"><?= htmlspecialchars((string)($row['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)($row['employee_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars((string)($row['card_uid_masked'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($row['request_source_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($row['device_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['result_class'] ?? 'bg-slate-200 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['result_label'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if (!empty($row['attendance_linked'])): ?>
                                        <span class="block text-xs text-emerald-700 mt-1">Linked to attendance log</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="pt-4 flex items-center justify-between gap-3">
                <p id="rfidRecentEventsPaginationInfo" class="text-xs text-slate-500">Showing 0 to 0 of 0 entries</p>
                <div class="flex items-center gap-2">
                    <button type="button" id="rfidRecentEventsPrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
                    <span id="rfidRecentEventsPageLabel" class="text-xs text-slate-500 min-w-[88px] text-center">Page 1 of 1</span>
                    <button type="button" id="rfidRecentEventsNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
                </div>
            </div>
        </div>
    </div>
</section>


<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Staff Recommendations Queue</h2>
        <p class="text-sm text-slate-500 mt-1">Review staff-submitted recommendations with approval controls and audit log context.</p>
    </header>
    <div class="px-6 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Recommendations</label>
            <input id="staffRecommendationsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by staff, employee, type, or status">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Type Filter</label>
            <select id="staffRecommendationsTypeFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Types</option>
                <option value="Leave">Leave</option>
                <option value="Time Adjustment">Time Adjustment</option>
                <option value="CTO">CTO</option>
                <option value="Official Business">Official Business</option>
                <option value="COS Flexible Schedule">COS Flexible Schedule</option>
                <option value="Travel Order">Travel Order</option>
                <option value="Travel Abroad">Travel Abroad</option>
            </select>
        </div>
    </div>
    <div class="p-6 overflow-x-auto">
        <table id="staffRecommendationsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Submitted At</th>
                    <th class="text-left px-4 py-3">Staff</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Recommended</th>
                    <th class="text-left px-4 py-3">Current</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($staffRecommendationRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="7">No staff recommendations found.</td></tr>
                <?php else: ?>
                    <?php foreach ($staffRecommendationRows as $row): ?>
                        <?php
                        [$recommendedLabel, $recommendedClass] = $attendancePill((string)($row['recommended_status'] ?? 'pending'));
                        [$currentLabel, $currentClass] = $attendancePill((string)($row['current_status'] ?? 'pending'));
                        $actionType = (string)($row['action_type'] ?? '');
                        $isFinal = (bool)($row['is_final'] ?? false);
                        $requestTypeLabel = (string)($row['request_type'] ?? '-');
                        if ($actionType === 'adjustment') {
                            $requestTypeLabel = 'Time Adjustment';
                        }
                        $recommendationSearch = strtolower(trim($formatDate((string)($row['submitted_at'] ?? ''), 'M d, Y h:i A') . ' ' . (string)($row['staff_actor'] ?? '') . ' ' . $requestTypeLabel . ' ' . (string)($row['employee_name'] ?? '') . ' ' . $recommendedLabel . ' ' . $currentLabel));
                        ?>
                        <tr data-page-row data-table-search="<?= htmlspecialchars($recommendationSearch, ENT_QUOTES, 'UTF-8') ?>" data-table-filter="<?= htmlspecialchars($requestTypeLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars($formatDate((string)($row['submitted_at'] ?? ''), 'M d, Y h:i A'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['staff_actor'] ?? 'Staff User'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($requestTypeLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($recommendedClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($recommendedLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($currentClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($currentLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <?php if ($actionType === 'leave'): ?>
                                    <button type="button" data-leave-review data-leave-request-id="<?= htmlspecialchars((string)($row['entity_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars((string)($row['current_status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?>" data-date-range="<?= htmlspecialchars((string)($row['date_range'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-leave-type="<?= htmlspecialchars((string)($row['leave_type'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8') ?>" data-leave-reason="<?= htmlspecialchars((string)($row['reason'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50" <?= $isFinal ? 'disabled' : '' ?>><span class="material-symbols-outlined text-[15px]">approval</span><?= $isFinal ? 'Finalized' : 'Approve/Reject' ?></button>
                                <?php elseif ($actionType === 'adjustment'): ?>
                                    <button type="button" data-adjust-review data-request-id="<?= htmlspecialchars((string)($row['entity_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars((string)($row['current_status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?>" data-requested-window="<?= htmlspecialchars((string)($row['window'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50" <?= $isFinal ? 'disabled' : '' ?>><span class="material-symbols-outlined text-[15px]">approval</span><?= $isFinal ? 'Finalized' : 'Approve/Reject' ?></button>
                                <?php elseif ($actionType === 'cto'): ?>
                                    <button type="button" data-cto-review data-request-id="<?= htmlspecialchars((string)($row['entity_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars((string)($row['current_status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?>" data-window="<?= htmlspecialchars((string)($row['window'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50" <?= $isFinal ? 'disabled' : '' ?>><span class="material-symbols-outlined text-[15px]">approval</span><?= $isFinal ? 'Finalized' : 'Approve/Reject' ?></button>
                                <?php elseif ($actionType === 'ob'): ?>
                                    <button type="button" data-ob-review data-request-id="<?= htmlspecialchars((string)($row['entity_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?>" data-request-type-label="<?= htmlspecialchars((string)($requestTypeLabel ?? 'Special Request'), ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars((string)($row['current_status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?>" data-window="<?= htmlspecialchars((string)($row['window'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50" <?= $isFinal ? 'disabled' : '' ?>><span class="material-symbols-outlined text-[15px]">approval</span><?= $isFinal ? 'Finalized' : 'Approve/Reject' ?></button>
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
    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="staffRecommendationsPaginationInfo" class="text-xs text-slate-500">Showing 0 to 0 of 0 entries</p>
        <div class="flex items-center gap-2">
            <button type="button" id="staffRecommendationsPrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <span id="staffRecommendationsPageLabel" class="text-xs text-slate-500 min-w-[88px] text-center">Page 1 of 1</span>
            <button type="button" id="staffRecommendationsNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Time Adjustment Requests</h2>
        <p class="text-sm text-slate-500 mt-1">Final admin decisions with lock after approved/rejected.</p>
    </header>
    <div class="px-6 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Requests</label>
            <input id="timeAdjustmentsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by employee, date, reason, or status">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="timeAdjustmentsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
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
                        $adjustmentSearch = strtolower(trim($employeeName . ' ' . $formatDate((string)($request['attendance']['attendance_date'] ?? '')) . ' ' . $requestedWindow . ' ' . (string)($request['reason'] ?? '') . ' ' . $statusLabel));
                        ?>
                        <tr data-page-row data-table-search="<?= htmlspecialchars($adjustmentSearch, ENT_QUOTES, 'UTF-8') ?>" data-table-filter="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
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
    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="timeAdjustmentsPaginationInfo" class="text-xs text-slate-500">Showing 0 to 0 of 0 entries</p>
        <div class="flex items-center gap-2">
            <button type="button" id="timeAdjustmentsPrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <span id="timeAdjustmentsPageLabel" class="text-xs text-slate-500 min-w-[88px] text-center">Page 1 of 1</span>
            <button type="button" id="timeAdjustmentsNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Leave/CTO Requests</h2>
        <p class="text-sm text-slate-500 mt-1">Unified admin queue for leave-style approvals, including CTO requests.</p>
    </header>
    <div class="px-6 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Requests</label>
            <input id="leaveCtoSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by employee, type, range, reason, or status">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="leaveCtoStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
                <option value="Cancelled">Cancelled</option>
            </select>
        </div>
    </div>
    <div class="p-6 overflow-x-auto">
        <table id="leaveCtoRequestsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Request Type</th>
                    <th class="text-left px-4 py-3">Leave Type</th>
                    <th class="text-left px-4 py-3">Date/Range</th>
                    <th class="text-left px-4 py-3">Window/Hours</th>
                    <th class="text-left px-4 py-3">Reason</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($leaveCtoRequests)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="8">No leave/CTO requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($leaveCtoRequests as $entry): ?>
                        <?php
                        $statusRaw = strtolower((string)($entry['status_raw'] ?? 'pending'));
                        $locked = in_array($statusRaw, ['approved', 'rejected', 'cancelled'], true);
                        [$statusLabel, $statusClass] = $attendancePill($statusRaw);
                        $requestSource = (string)($entry['request_source'] ?? 'leave_requests');
                        $employeeName = (string)($entry['employee_name'] ?? 'Unknown Employee');
                        $requestTypeLabel = (string)($entry['request_type_label'] ?? 'Leave');
                        $leaveType = (string)($entry['leave_type'] ?? '-');
                        $dateLabel = $requestSource === 'leave_requests'
                            ? str_replace(' - ', ' to ', (string)($entry['date_label'] ?? '-'))
                            : $formatDate((string)($entry['date_label'] ?? ''));
                        $windowLabel = '-';
                        if ($requestSource === 'overtime_requests') {
                            $windowLabel = htmlspecialchars((string)($entry['window'] ?? '-'), ENT_QUOTES, 'UTF-8')
                                . ' / '
                                . htmlspecialchars(number_format((float)($entry['hours_requested'] ?? 0), 2), ENT_QUOTES, 'UTF-8')
                                . 'h';
                        }
                        $leaveCtoSearch = strtolower(trim($employeeName . ' ' . $requestTypeLabel . ' ' . $leaveType . ' ' . $dateLabel . ' ' . strip_tags($windowLabel) . ' ' . (string)($entry['reason'] ?? '') . ' ' . $statusLabel));
                        ?>
                        <tr data-page-row data-table-search="<?= htmlspecialchars($leaveCtoSearch, ENT_QUOTES, 'UTF-8') ?>" data-table-filter="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($requestTypeLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($leaveType, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= $requestSource === 'overtime_requests' ? $windowLabel : '-' ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($entry['reason'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <?php if ($requestSource === 'leave_requests'): ?>
                                    <button type="button" data-leave-review data-leave-request-id="<?= htmlspecialchars((string)($entry['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>" data-date-range="<?= htmlspecialchars((string)($entry['date_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>" data-leave-type="<?= htmlspecialchars($leaveType, ENT_QUOTES, 'UTF-8') ?>" data-leave-reason="<?= htmlspecialchars((string)($entry['reason'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50" <?= $locked ? 'disabled' : '' ?>>
                                        <span class="material-symbols-outlined text-[15px]">rate_review</span><?= $locked ? 'Locked' : 'Review' ?>
                                    </button>
                                <?php else: ?>
                                    <button type="button" data-cto-review data-request-id="<?= htmlspecialchars((string)($entry['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>" data-window="<?= htmlspecialchars((string)($entry['window'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50" <?= $locked ? 'disabled' : '' ?>>
                                        <span class="material-symbols-outlined text-[15px]">rate_review</span><?= $locked ? 'Locked' : 'Review' ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="leaveCtoPaginationInfo" class="text-xs text-slate-500">Showing 0 to 0 of 0 entries</p>
        <div class="flex items-center gap-2">
            <button type="button" id="leaveCtoPrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <span id="leaveCtoPageLabel" class="text-xs text-slate-500 min-w-[88px] text-center">Page 1 of 1</span>
            <button type="button" id="leaveCtoNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Special Timekeeping Requests</h2>
        <p class="text-sm text-slate-500 mt-1">Pending and finalized approvals for Official Business, COS schedules, Travel Orders, and Travel Abroad requests.</p>
    </header>
    <div class="px-6 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Special Requests</label>
            <input id="obRequestsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by employee, date, reason, or status">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="obRequestsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
                <option value="Cancelled">Cancelled</option>
            </select>
        </div>
    </div>
    <div class="p-6 overflow-x-auto">
        <table id="obRequestsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Request Type</th>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Window</th>
                    <th class="text-left px-4 py-3">Details</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($obRequests)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="7">No special timekeeping requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($obRequests as $ob): ?>
                        <?php
                        $statusRaw = strtolower((string)($ob['status'] ?? 'pending'));
                        $locked = in_array($statusRaw, ['approved', 'rejected', 'cancelled'], true);
                        [$statusLabel, $statusClass] = $attendancePill($statusRaw);
                        $window = $formatTime((string)($ob['start_time'] ?? '')) . ' - ' . $formatTime((string)($ob['end_time'] ?? ''));
                        $obSearch = strtolower(trim((string)($ob['employee_name'] ?? '') . ' ' . (string)($ob['request_label'] ?? '') . ' ' . $formatDate((string)($ob['overtime_date'] ?? '')) . ' ' . $window . ' ' . (string)($ob['reason'] ?? '') . ' ' . (string)($ob['detail_summary'] ?? '') . ' ' . $statusLabel));
                        ?>
                        <tr data-page-row data-table-search="<?= htmlspecialchars($obSearch, ENT_QUOTES, 'UTF-8') ?>" data-table-filter="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($ob['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($ob['request_label'] ?? 'Special Request'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($formatDate((string)($ob['overtime_date'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($window, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <p><?= htmlspecialchars((string)($ob['reason'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php if (!empty($ob['detail_summary'])): ?>
                                    <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)$ob['detail_summary'], ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                                <?php if (!empty($ob['attachment_url']) && !empty($ob['attachment_name'])): ?>
                                    <a href="<?= htmlspecialchars((string)$ob['attachment_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 mt-1 text-xs font-medium text-slate-700 hover:underline">
                                        <span class="material-symbols-outlined text-sm">attach_file</span><?= htmlspecialchars((string)$ob['attachment_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <button type="button" data-ob-review data-request-id="<?= htmlspecialchars((string)($ob['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)($ob['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?>" data-request-type-label="<?= htmlspecialchars((string)($ob['request_label'] ?? 'Special Request'), ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>" data-window="<?= htmlspecialchars($window, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50" <?= $locked ? 'disabled' : '' ?>>
                                    <span class="material-symbols-outlined text-[15px]">rate_review</span><?= $locked ? 'Locked' : 'Review' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="obRequestsPaginationInfo" class="text-xs text-slate-500">Showing 0 to 0 of 0 entries</p>
        <div class="flex items-center gap-2">
            <button type="button" id="obRequestsPrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <span id="obRequestsPageLabel" class="text-xs text-slate-500 min-w-[88px] text-center">Page 1 of 1</span>
            <button type="button" id="obRequestsNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Employee Timekeeping History</h2>
        <p class="text-sm text-slate-500 mt-1">Recent cross-request history for review context.</p>
    </header>
    <div class="px-6 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search History</label>
            <input id="timekeepingHistorySearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by employee, type, summary, or status">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Type Filter</label>
            <select id="timekeepingHistoryTypeFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Types</option>
                <option value="Attendance">Attendance</option>
                <option value="Leave">Leave</option>
                <option value="CTO">CTO</option>
                <option value="Official Business">Official Business</option>
                <option value="COS Flexible Schedule">COS Flexible Schedule</option>
                <option value="Travel Order">Travel Order</option>
                <option value="Travel Abroad">Travel Abroad</option>
                <option value="Time Adjustment">Time Adjustment</option>
            </select>
        </div>
    </div>
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
                        <?php
                        [$statusLabel, $statusClass] = $attendancePill((string)($entry['status'] ?? 'pending'));
                        $historyType = (string)($entry['entry_type'] ?? '-');
                        $historySearch = strtolower(trim((string)($entry['employee_name'] ?? '') . ' ' . $historyType . ' ' . $formatDate((string)($entry['entry_date'] ?? '')) . ' ' . (string)($entry['summary'] ?? '') . ' ' . $statusLabel));
                        ?>
                        <tr data-page-row data-table-search="<?= htmlspecialchars($historySearch, ENT_QUOTES, 'UTF-8') ?>" data-table-filter="<?= htmlspecialchars($historyType, ENT_QUOTES, 'UTF-8') ?>">
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
    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="timekeepingHistoryPaginationInfo" class="text-xs text-slate-500">Showing 0 to 0 of 0 entries</p>
        <div class="flex items-center gap-2">
            <button type="button" id="timekeepingHistoryPrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <span id="timekeepingHistoryPageLabel" class="text-xs text-slate-500 min-w-[88px] text-center">Page 1 of 1</span>
            <button type="button" id="timekeepingHistoryNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<div id="attendanceHelperModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="attendanceHelperModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-5xl bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Employee Attendance Helper</h3>
                    <p class="text-sm text-slate-500 mt-1">Fallback encoder for missed or delayed attendance entries.</p>
                </div>
                <button type="button" data-modal-close="attendanceHelperModal" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close attendance helper modal">
                    <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                </button>
            </div>
            <form id="attendanceHelperForm" action="timekeeping.php" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm md:grid-cols-4">
                <input type="hidden" name="form_action" value="log_employee_attendance">
                <input type="hidden" name="attendance_person_id" id="attendanceHelperPersonId" value="" required>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Employee (Search by ID or Name)</label>
                    <div class="relative mt-1">
                        <input id="attendanceHelperEmployeeSearch" type="text" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="Type employee ID or name" autocomplete="off" required>
                        <div id="attendanceHelperEmployeeResults" class="hidden absolute z-20 mt-1 w-full rounded-md border border-slate-200 bg-white shadow-lg max-h-56 overflow-y-auto">
                            <?php foreach ($employeeOptions as $employeeOption): ?>
                                <?php
                                $employeeName = (string)($employeeOption['name'] ?? $employeeOption['label'] ?? 'Unknown Employee');
                                $employeeCode = trim((string)($employeeOption['employee_code'] ?? ''));
                                $optionLabel = (string)($employeeOption['label'] ?? $employeeName);
                                $searchText = strtolower(trim($optionLabel . ' ' . $employeeName . ' ' . $employeeCode));
                                ?>
                                <button
                                    type="button"
                                    class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b border-slate-100 last:border-b-0"
                                    data-attendance-employee-option
                                    data-person-id="<?= htmlspecialchars((string)($employeeOption['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-label="<?= htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8') ?>"
                                    data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <span class="block text-slate-800"><?= htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1" id="attendanceHelperEmployeeHint">Select an employee from the custom search results.</p>
                </div>
                <div>
                    <label class="text-slate-600">Entry Type</label>
                    <select id="attendanceHelperEntryType" name="attendance_entry_type" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="time_in">Time In</option>
                        <option value="time_out">Time Out</option>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Attendance Date</label>
                    <input id="attendanceHelperDate" type="date" name="attendance_date" value="<?= htmlspecialchars($todayDate, ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                </div>
                <div>
                    <label class="text-slate-600">Attendance Time</label>
                    <input id="attendanceHelperTime" type="time" name="attendance_time" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                </div>
                <div class="md:col-span-3">
                    <label class="text-slate-600">Reference / Notes</label>
                    <textarea name="attendance_reference" rows="2" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Reason for manual logging or related note (optional)"></textarea>
                </div>
                <div class="md:col-span-4 flex justify-end gap-3">
                    <button type="button" data-modal-close="attendanceHelperModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm rounded-md bg-daGreen text-white hover:opacity-90">
                        <span class="material-symbols-outlined text-[16px]">fact_check</span>Log Attendance Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="leaveCardLogModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="leaveCardLogModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-6xl bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Log Leave from Leave Card</h3>
                    <p class="text-sm text-slate-500 mt-1">Encode approved leave-card entries without leaving the top workflow.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/hris-system/assets/Leave_Card_Template.xlsx" download class="inline-flex items-center gap-1.5 px-3 py-2 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 whitespace-nowrap">
                        <span class="material-symbols-outlined text-[16px]">download</span>Download Template
                    </a>
                    <button type="button" data-modal-close="leaveCardLogModal" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close leave log modal">
                        <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                    </button>
                </div>
            </div>
            <form id="leaveCardLogForm" action="timekeeping.php" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                <input type="hidden" name="form_action" value="log_leave_from_card">
                <input type="hidden" name="person_id" id="leaveLogPersonId" value="" required>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Employee (Search by ID or Name)</label>
                    <div class="relative mt-1">
                        <input id="leaveLogEmployeeSearch" type="text" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="Type employee ID or name" autocomplete="off" required>
                        <div id="leaveLogEmployeeResults" class="hidden absolute z-20 mt-1 w-full rounded-md border border-slate-200 bg-white shadow-lg max-h-56 overflow-y-auto">
                            <?php foreach ($employeeOptions as $employeeOption): ?>
                                <?php
                                $employeeName = (string)($employeeOption['name'] ?? $employeeOption['label'] ?? 'Unknown Employee');
                                $employeeCode = trim((string)($employeeOption['employee_code'] ?? ''));
                                $optionLabel = (string)($employeeOption['label'] ?? $employeeName);
                                $searchText = strtolower(trim($optionLabel . ' ' . $employeeName . ' ' . $employeeCode));
                                ?>
                                <button
                                    type="button"
                                    class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b border-slate-100 last:border-b-0"
                                    data-employee-option
                                    data-person-id="<?= htmlspecialchars((string)($employeeOption['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-label="<?= htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8') ?>"
                                    data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <span class="block text-slate-800"><?= htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1" id="leaveLogEmployeeHint">Select an employee from the custom search results.</p>
                </div>
                <div>
                    <label class="text-slate-600">Leave Type</label>
                    <select id="leaveLogLeaveType" name="leave_type_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="">Select leave type</option>
                        <?php foreach ($leaveTypeOptions as $leaveTypeOption): ?>
                            <option value="<?= htmlspecialchars((string)($leaveTypeOption['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-leave-code="<?= htmlspecialchars((string)($leaveTypeOption['leave_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-leave-name="<?= htmlspecialchars((string)($leaveTypeOption['leave_name'] ?? 'Leave'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($leaveTypeOption['leave_name'] ?? 'Leave'), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Date From</label>
                    <input id="leaveLogDateFrom" type="date" name="date_from" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                </div>
                <div>
                    <label class="text-slate-600">Date To</label>
                    <input id="leaveLogDateTo" type="date" name="date_to" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                </div>
                <div>
                    <label class="text-slate-600">Leave Days</label>
                    <input id="leaveLogDays" type="number" name="days_count" min="1" step="1" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" placeholder="Auto-computed" readonly required>
                    <p class="text-[11px] text-slate-500 mt-1">Auto-computed from Date From and Date To (inclusive).</p>
                </div>
                <div>
                    <label class="text-slate-600">SL Points</label>
                    <input id="leaveLogSlPoints" name="sl_points" type="number" min="0" step="0.01" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="0.00" value="0.00">
                    <p class="text-[11px] text-slate-500 mt-1">Editable accumulated Sick Leave points to add for this employee.</p>
                </div>
                <div>
                    <label class="text-slate-600">VL Points</label>
                    <input id="leaveLogVlPoints" name="vl_points" type="number" min="0" step="0.01" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="0.00" value="0.00">
                    <p class="text-[11px] text-slate-500 mt-1">Editable accumulated Vacation Leave points to add for this employee.</p>
                </div>
                <div>
                    <label class="text-slate-600">CTO Points</label>
                    <input id="leaveLogCtoPoints" name="cto_points" type="number" min="0" step="0.01" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="0.00" value="0.00">
                    <p class="text-[11px] text-slate-500 mt-1">Editable accumulated CTO or other leave points to add for this employee.</p>
                </div>
                <div class="md:col-span-3">
                    <label class="text-slate-600">Reference / Notes</label>
                    <textarea name="reference" rows="2" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Leave card control number or remarks (optional)"></textarea>
                </div>
                <div class="md:col-span-3 flex justify-end gap-3">
                    <button type="button" data-modal-close="leaveCardLogModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm rounded-md bg-daGreen text-white hover:opacity-90">
                        <span class="material-symbols-outlined text-[16px]">save</span>Log Leave Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="holidayConfigModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="holidayConfigModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-6xl max-h-[90vh] bg-white rounded-2xl border border-slate-200 shadow-xl overflow-y-auto">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Holiday/Suspension Configuration</h3>
                    <p class="text-sm text-slate-500 mt-1">Configure holiday dates and payroll paid-handling rules from a dedicated modal.</p>
                </div>
                <button type="button" data-modal-close="holidayConfigModal" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close holiday configuration modal">
                    <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                </button>
            </div>
            <form action="timekeeping.php" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
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
                <div class="md:col-span-2 flex justify-end gap-3">
                    <button type="button" data-modal-close="holidayConfigModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Holiday/Payroll Settings</button>
                </div>
            </form>

            <div class="border-t border-slate-200 px-6 pt-4">
                <div class="pb-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
                    <div class="w-full md:w-1/2">
                        <label class="text-sm text-slate-600">Search Holidays</label>
                        <input id="holidayConfigSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by date, holiday name, or type">
                    </div>
                    <div class="w-full md:w-56">
                        <label class="text-sm text-slate-600">Type Filter</label>
                        <select id="holidayConfigTypeFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                            <option value="">All Types</option>
                            <option value="Regular">Regular</option>
                            <option value="Special">Special</option>
                            <option value="Local">Local</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="px-6 pb-4 overflow-x-auto">
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
                                <?php
                                $holidayTypeLabel = ucfirst((string)($holiday['holiday_type'] ?? '-'));
                                $holidaySearch = strtolower(trim($formatDate((string)($holiday['holiday_date'] ?? '')) . ' ' . (string)($holiday['holiday_name'] ?? '') . ' ' . $holidayTypeLabel));
                                ?>
                                <tr data-page-row data-table-search="<?= htmlspecialchars($holidaySearch, ENT_QUOTES, 'UTF-8') ?>" data-table-filter="<?= htmlspecialchars($holidayTypeLabel, ENT_QUOTES, 'UTF-8') ?>">
                                    <td class="px-4 py-3"><?= htmlspecialchars($formatDate((string)($holiday['holiday_date'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars((string)($holiday['holiday_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars($holidayTypeLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-6 pb-4 flex items-center justify-between gap-3">
                <p id="holidayConfigPaginationInfo" class="text-xs text-slate-500">Showing 0 to 0 of 0 entries</p>
                <div class="flex items-center gap-2">
                    <button type="button" id="holidayConfigPrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
                    <span id="holidayConfigPageLabel" class="text-xs text-slate-500 min-w-[88px] text-center">Page 1 of 1</span>
                    <button type="button" id="holidayConfigNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="reviewAdjustmentModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="reviewAdjustmentModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Review Time Adjustment</h3>
                <button type="button" data-modal-close="reviewAdjustmentModal" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close time adjustment review modal">
                    <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                </button>
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
                <button type="button" data-modal-close="reviewLeaveModal" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close leave review modal">
                    <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                </button>
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
                <button type="button" data-modal-close="reviewCtoModal" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close CTO review modal">
                    <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                </button>
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
                <h3 id="reviewObModalTitle" class="text-lg font-semibold text-slate-800">Review Special Timekeeping Request</h3>
                <button type="button" data-modal-close="reviewObModal" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close special request review modal">
                    <span class="material-symbols-outlined text-[20px] leading-none">close</span>
                </button>
            </div>
            <form action="timekeeping.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="review_ob_request">
                <input type="hidden" id="obRequestId" name="request_id" value="">
                <div class="md:col-span-2"><label class="text-slate-600">Employee</label><input id="obEmployeeName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Request Type</label><input id="obRequestTypeLabel" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Current Status</label><input id="obCurrentStatus" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Requested Window</label><input id="obWindow" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Decision</label><select name="decision" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required><option value="approved">Approve</option><option value="rejected">Reject</option><option value="needs_revision">Needs Revision</option></select></div>
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
            if ('value' in el) {
                el.value = value || '';
                return;
            }
            el.textContent = value || '';
        }
    };

    const openModal = (id) => {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = (id) => {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');

        const hasVisibleModal = Array.from(document.querySelectorAll('[data-modal]')).some((candidate) => !candidate.classList.contains('hidden'));
        if (!hasVisibleModal) {
            document.body.classList.remove('overflow-hidden');
        }
    };

    const initializeFilterablePaginatedTable = ({
        tableId,
        infoId,
        pageLabelId,
        prevId,
        nextId,
        searchInputId,
        filterId,
        emptyMessage,
        pageSize = 10,
    }) => {
        const table = document.getElementById(tableId);
        const info = document.getElementById(infoId);
        const pageLabel = document.getElementById(pageLabelId);
        const prevButton = document.getElementById(prevId);
        const nextButton = document.getElementById(nextId);
        const searchInput = searchInputId ? document.getElementById(searchInputId) : null;
        const filterInput = filterId ? document.getElementById(filterId) : null;
        const tbody = table ? table.querySelector('tbody') : null;
        const rows = table ? Array.from(table.querySelectorAll('tbody tr[data-page-row]')) : [];
        let currentPage = 1;
        let initialized = false;

        if (!table || !tbody) {
            return;
        }

        let emptyRow = tbody.querySelector('[data-filter-empty-row="true"]');
        if (!emptyRow && rows.length > 0) {
            const columnCount = Array.from(table.querySelectorAll('thead th')).length || 1;
            emptyRow = document.createElement('tr');
            emptyRow.dataset.filterEmptyRow = 'true';
            emptyRow.className = 'hidden';
            emptyRow.innerHTML = `<td class="px-4 py-3 text-slate-500" colspan="${columnCount}">${emptyMessage}</td>`;
            tbody.appendChild(emptyRow);
        }

        const updateControls = () => {
            const query = (searchInput?.value || '').trim().toLowerCase();
            const selectedFilter = (filterInput?.value || '').trim().toLowerCase();
            const filteredRows = rows.filter((row) => {
                const rowSearch = (row.getAttribute('data-table-search') || '').toLowerCase();
                const rowFilter = (row.getAttribute('data-table-filter') || '').toLowerCase();
                const searchMatch = query === '' || rowSearch.includes(query);
                const filterMatch = selectedFilter === '' || rowFilter === selectedFilter;
                return searchMatch && filterMatch;
            });

            const totalRows = filteredRows.length;
            const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));
            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            const startIndex = totalRows === 0 ? 0 : (currentPage - 1) * pageSize;
            const endIndex = totalRows === 0 ? 0 : Math.min(startIndex + pageSize, totalRows);
            const visibleRows = new Set(filteredRows.slice(startIndex, endIndex));

            rows.forEach((row) => {
                row.classList.toggle('hidden', !visibleRows.has(row));
            });

            emptyRow?.classList.toggle('hidden', totalRows !== 0);

            if (info) {
                info.textContent = totalRows === 0
                    ? 'Showing 0 to 0 of 0 entries'
                    : `Showing ${startIndex + 1} to ${endIndex} of ${totalRows} entries`;
            }

            if (pageLabel) {
                pageLabel.textContent = `Page ${currentPage} of ${totalPages}`;
            }

            const syncButtonState = (button, disabled) => {
                if (!button) return;
                button.disabled = disabled;
                button.classList.toggle('opacity-60', disabled);
                button.classList.toggle('cursor-not-allowed', disabled);
            };

            syncButtonState(prevButton, currentPage <= 1 || totalRows === 0);
            syncButtonState(nextButton, currentPage >= totalPages || totalRows === 0);
        };

        const resetToFirstPage = () => {
            if (!initialized) {
                return;
            }

            currentPage = 1;
            updateControls();
        };

        searchInput?.addEventListener('input', resetToFirstPage);
        filterInput?.addEventListener('change', resetToFirstPage);

        prevButton?.addEventListener('click', () => {
            if (!initialized) {
                return;
            }

            if (currentPage <= 1) {
                return;
            }
            currentPage -= 1;
            updateControls();
        });

        nextButton?.addEventListener('click', () => {
            if (!initialized) {
                return;
            }

            const query = (searchInput?.value || '').trim().toLowerCase();
            const selectedFilter = (filterInput?.value || '').trim().toLowerCase();
            const filteredCount = rows.filter((row) => {
                const rowSearch = (row.getAttribute('data-table-search') || '').toLowerCase();
                const rowFilter = (row.getAttribute('data-table-filter') || '').toLowerCase();
                const searchMatch = query === '' || rowSearch.includes(query);
                const filterMatch = selectedFilter === '' || rowFilter === selectedFilter;
                return searchMatch && filterMatch;
            }).length;
            const totalPages = Math.max(1, Math.ceil(filteredCount / pageSize));
            if (currentPage >= totalPages) {
                return;
            }
            currentPage += 1;
            updateControls();
        });

        const initialize = () => {
            if (initialized) {
                return;
            }

            initialized = true;
            updateControls();
        };

        if (typeof window.IntersectionObserver !== 'function') {
            initialize();
            return;
        }

        const scope = table.closest('section') || table.closest('[data-modal]') || table.parentElement || table;
        const observer = new window.IntersectionObserver((entries) => {
            if (!entries.some((entry) => entry.isIntersecting)) {
                return;
            }

            observer.disconnect();
            initialize();
        }, {
            rootMargin: '240px 0px',
        });

        observer.observe(scope);
    };

    const bindEmployeePicker = ({
        searchInputId,
        resultsId,
        hiddenInputId,
        hintId,
        optionSelector,
    }) => {
        const searchInput = document.getElementById(searchInputId);
        const results = document.getElementById(resultsId);
        const hiddenInput = document.getElementById(hiddenInputId);
        const hint = document.getElementById(hintId);
        const optionButtons = Array.from(document.querySelectorAll(optionSelector));

        const showResults = () => {
            results?.classList.remove('hidden');
        };

        const hideResults = () => {
            results?.classList.add('hidden');
        };

        const filterResults = () => {
            if (!searchInput || !results) return;
            const query = (searchInput.value || '').trim().toLowerCase();
            let visibleCount = 0;

            optionButtons.forEach((button) => {
                const haystack = (button.getAttribute('data-search') || '').toLowerCase();
                const visible = query === '' || haystack.includes(query);
                button.classList.toggle('hidden', !visible);
                if (visible) {
                    visibleCount += 1;
                }
            });

            showResults();
            if (hint) {
                hint.textContent = visibleCount > 0
                    ? 'Select an employee from the custom search results.'
                    : 'No employee matched your search.';
            }
        };

        const selectEmployee = (button) => {
            if (!button || !searchInput || !hiddenInput) return;
            const personId = button.getAttribute('data-person-id') || '';
            const label = button.getAttribute('data-label') || '';
            hiddenInput.value = personId;
            searchInput.value = label;
            searchInput.setCustomValidity('');
            if (hint) {
                hint.textContent = 'Selected: ' + label;
            }
            hideResults();
        };

        searchInput?.addEventListener('focus', filterResults);
        searchInput?.addEventListener('input', () => {
            if (hiddenInput) {
                hiddenInput.value = '';
            }
            filterResults();
        });

        optionButtons.forEach((button) => {
            button.addEventListener('click', () => selectEmployee(button));
        });

        document.addEventListener('click', (event) => {
            if (!results || !searchInput) return;
            const target = event.target;
            if (!(target instanceof Node)) return;
            const clickedInside = results.contains(target) || searchInput.contains(target);
            if (!clickedInside) {
                hideResults();
            }
        });

        return {
            searchInput,
            hiddenInput,
            showResults,
        };
    };

    document.getElementById('attendancePrintButton')?.addEventListener('click', () => window.print());

    [
        {
            tableId: 'rfidRecentEventsTable',
            infoId: 'rfidRecentEventsPaginationInfo',
            pageLabelId: 'rfidRecentEventsPageLabel',
            prevId: 'rfidRecentEventsPrevPage',
            nextId: 'rfidRecentEventsNextPage',
            searchInputId: null,
            filterId: null,
            emptyMessage: 'No RFID scan events found.',
            pageSize: 15,
        },
        {
            tableId: 'attendanceSnapshotTable',
            infoId: 'attendancePaginationInfo',
            pageLabelId: 'attendancePageLabel',
            prevId: 'attendancePrevPage',
            nextId: 'attendanceNextPage',
            searchInputId: 'attendanceTableSearch',
            filterId: 'attendanceTableStatusFilter',
            emptyMessage: 'No attendance records match your search/filter criteria.',
        },
        {
            tableId: 'staffRecommendationsTable',
            infoId: 'staffRecommendationsPaginationInfo',
            pageLabelId: 'staffRecommendationsPageLabel',
            prevId: 'staffRecommendationsPrevPage',
            nextId: 'staffRecommendationsNextPage',
            searchInputId: 'staffRecommendationsSearch',
            filterId: 'staffRecommendationsTypeFilter',
            emptyMessage: 'No staff recommendations match your search/filter criteria.',
        },
        {
            tableId: 'timeAdjustmentsTable',
            infoId: 'timeAdjustmentsPaginationInfo',
            pageLabelId: 'timeAdjustmentsPageLabel',
            prevId: 'timeAdjustmentsPrevPage',
            nextId: 'timeAdjustmentsNextPage',
            searchInputId: 'timeAdjustmentsSearch',
            filterId: 'timeAdjustmentsStatusFilter',
            emptyMessage: 'No time adjustment requests match your search/filter criteria.',
        },
        {
            tableId: 'leaveCtoRequestsTable',
            infoId: 'leaveCtoPaginationInfo',
            pageLabelId: 'leaveCtoPageLabel',
            prevId: 'leaveCtoPrevPage',
            nextId: 'leaveCtoNextPage',
            searchInputId: 'leaveCtoSearch',
            filterId: 'leaveCtoStatusFilter',
            emptyMessage: 'No leave or CTO requests match your search/filter criteria.',
        },
        {
            tableId: 'obRequestsTable',
            infoId: 'obRequestsPaginationInfo',
            pageLabelId: 'obRequestsPageLabel',
            prevId: 'obRequestsPrevPage',
            nextId: 'obRequestsNextPage',
            searchInputId: 'obRequestsSearch',
            filterId: 'obRequestsStatusFilter',
            emptyMessage: 'No special timekeeping requests match your search/filter criteria.',
        },
        {
            tableId: 'timekeepingHistoryTable',
            infoId: 'timekeepingHistoryPaginationInfo',
            pageLabelId: 'timekeepingHistoryPageLabel',
            prevId: 'timekeepingHistoryPrevPage',
            nextId: 'timekeepingHistoryNextPage',
            searchInputId: 'timekeepingHistorySearch',
            filterId: 'timekeepingHistoryTypeFilter',
            emptyMessage: 'No timekeeping history entries match your search/filter criteria.',
        },
        {
            tableId: 'holidayConfigTable',
            infoId: 'holidayConfigPaginationInfo',
            pageLabelId: 'holidayConfigPageLabel',
            prevId: 'holidayConfigPrevPage',
            nextId: 'holidayConfigNextPage',
            searchInputId: 'holidayConfigSearch',
            filterId: 'holidayConfigTypeFilter',
            emptyMessage: 'No holiday configuration entries match your search/filter criteria.',
        },
    ].forEach(initializeFilterablePaginatedTable);

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
            const requestTypeLabel = button.getAttribute('data-request-type-label') || 'Special Request';
            setValue('obRequestId', button.getAttribute('data-request-id'));
            setValue('obEmployeeName', button.getAttribute('data-employee-name'));
            setValue('obRequestTypeLabel', requestTypeLabel);
            setValue('obCurrentStatus', button.getAttribute('data-current-status'));
            setValue('obWindow', button.getAttribute('data-window'));
            setValue('reviewObModalTitle', `Review ${requestTypeLabel}`);
            openModal('reviewObModal');
        });
    });

    document.querySelectorAll('[data-quick-modal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-quick-modal-open') || '';
            if (modalId) {
                openModal(modalId);
            }
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-modal-close') || '';
            if (modalId) {
                closeModal(modalId);
            }
        });
    });

    const leaveLogForm = document.getElementById('leaveCardLogForm');
    const leaveEmployeePicker = bindEmployeePicker({
        searchInputId: 'leaveLogEmployeeSearch',
        resultsId: 'leaveLogEmployeeResults',
        hiddenInputId: 'leaveLogPersonId',
        hintId: 'leaveLogEmployeeHint',
        optionSelector: '[data-employee-option]',
    });
    const employeeSearchInput = leaveEmployeePicker.searchInput;
    const employeeHiddenInput = leaveEmployeePicker.hiddenInput;
    const leaveTypeSelect = document.getElementById('leaveLogLeaveType');
    const leaveDateFrom = document.getElementById('leaveLogDateFrom');
    const leaveDateTo = document.getElementById('leaveLogDateTo');
    const leaveDays = document.getElementById('leaveLogDays');
    const leaveLogSlPoints = document.getElementById('leaveLogSlPoints');
    const leaveLogVlPoints = document.getElementById('leaveLogVlPoints');
    const leaveLogCtoPoints = document.getElementById('leaveLogCtoPoints');

    const attendanceHelperForm = document.getElementById('attendanceHelperForm');
    const attendanceEmployeePicker = bindEmployeePicker({
        searchInputId: 'attendanceHelperEmployeeSearch',
        resultsId: 'attendanceHelperEmployeeResults',
        hiddenInputId: 'attendanceHelperPersonId',
        hintId: 'attendanceHelperEmployeeHint',
        optionSelector: '[data-attendance-employee-option]',
    });
    const attendanceHelperSearchInput = attendanceEmployeePicker.searchInput;
    const attendanceHelperHiddenInput = attendanceEmployeePicker.hiddenInput;
    const attendanceHelperEntryType = document.getElementById('attendanceHelperEntryType');
    const attendanceHelperDate = document.getElementById('attendanceHelperDate');
    const attendanceHelperTime = document.getElementById('attendanceHelperTime');

    const computeLeaveDays = () => {
        if (!leaveDateFrom || !leaveDateTo || !leaveDays) return;

        const fromValue = leaveDateFrom.value;
        const toValue = leaveDateTo.value;

        if (!fromValue || !toValue) {
            leaveDays.value = '';
            leaveDateTo.setCustomValidity('');
            return;
        }

        const fromDate = new Date(fromValue + 'T00:00:00');
        const toDate = new Date(toValue + 'T00:00:00');
        if (Number.isNaN(fromDate.getTime()) || Number.isNaN(toDate.getTime())) {
            leaveDays.value = '';
            leaveDateTo.setCustomValidity('Invalid date range.');
            return;
        }

        const diffMs = toDate.getTime() - fromDate.getTime();
        if (diffMs < 0) {
            leaveDays.value = '';
            leaveDateTo.setCustomValidity('Date To cannot be earlier than Date From.');
            return;
        }

        const totalDays = Math.floor(diffMs / 86400000) + 1;
        leaveDateTo.setCustomValidity('');
        leaveDays.value = String(totalDays);
        syncLeavePointFields();
    };

    const resetLeavePointFields = () => {
        [leaveLogSlPoints, leaveLogVlPoints, leaveLogCtoPoints].forEach((input) => {
            if (input) {
                input.value = '0.00';
            }
        });
    };

    const resolveLeavePointBucket = () => {
        if (!(leaveTypeSelect instanceof HTMLSelectElement)) {
            return '';
        }

        const selectedOption = leaveTypeSelect.options[leaveTypeSelect.selectedIndex];
        if (!(selectedOption instanceof HTMLOptionElement)) {
            return '';
        }

        const leaveCode = (selectedOption.dataset.leaveCode || '').trim().toLowerCase();
        const leaveName = (selectedOption.dataset.leaveName || '').trim().toLowerCase();

        if (leaveCode === 'sl' || leaveName.includes('sick')) {
            return 'sl';
        }

        if (leaveCode === 'vl' || leaveName.includes('vacation')) {
            return 'vl';
        }

        if (leaveCode === 'cto' || leaveName.includes('cto') || leaveName.includes('compensatory')) {
            return 'cto';
        }

        return '';
    };

    const syncLeavePointFields = () => {
        resetLeavePointFields();

        const totalDays = Number.parseFloat(leaveDays?.value || '0');
        if (!Number.isFinite(totalDays) || totalDays <= 0) {
            return;
        }

        const bucket = resolveLeavePointBucket();
        const formattedValue = totalDays.toFixed(2);
        if (bucket === 'sl' && leaveLogSlPoints) {
            leaveLogSlPoints.value = formattedValue;
        } else if (bucket === 'vl' && leaveLogVlPoints) {
            leaveLogVlPoints.value = formattedValue;
        } else if (bucket === 'cto' && leaveLogCtoPoints) {
            leaveLogCtoPoints.value = formattedValue;
        }
    };

    leaveDateFrom?.addEventListener('change', computeLeaveDays);
    leaveDateTo?.addEventListener('change', computeLeaveDays);
    leaveTypeSelect?.addEventListener('change', syncLeavePointFields);

    const resolveManualPointTotal = () => {
        const slValue = Number.parseFloat(leaveLogSlPoints?.value || '0');
        const vlValue = Number.parseFloat(leaveLogVlPoints?.value || '0');
        const ctoValue = Number.parseFloat(leaveLogCtoPoints?.value || '0');

        return {
            sl: Number.isFinite(slValue) ? Math.max(0, slValue) : 0,
            vl: Number.isFinite(vlValue) ? Math.max(0, vlValue) : 0,
            cto: Number.isFinite(ctoValue) ? Math.max(0, ctoValue) : 0,
        };
    };

    const showTimekeepingConfirmDialog = ({ title, html, text, confirmButtonText }) => {
        if (!(window.Swal && typeof window.Swal.fire === 'function')) {
            return Promise.resolve(false);
        }

        return window.Swal.fire({
            icon: 'question',
            title: title || 'Confirm action?',
            html,
            text,
            showCancelButton: true,
            confirmButtonText: confirmButtonText || 'Confirm',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#64748b',
            focusCancel: true,
        }).then((result) => Boolean(result.isConfirmed));
    };

    leaveLogForm?.addEventListener('submit', (event) => {
        if (!employeeHiddenInput || !employeeSearchInput) return;
        if (!employeeHiddenInput.value) {
            event.preventDefault();
            employeeSearchInput.setCustomValidity('Please select an employee from the search results.');
            employeeSearchInput.reportValidity();
            leaveEmployeePicker.showResults();
            return;
        }
        employeeSearchInput.setCustomValidity('');
        computeLeaveDays();

        const totalDays = Number.parseFloat(leaveDays?.value || '0');
        if (!Number.isFinite(totalDays) || totalDays <= 0) {
            event.preventDefault();
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    icon: 'warning',
                    title: 'Incomplete leave entry',
                    text: 'Please provide a valid leave date range before logging the leave card entry.',
                    confirmButtonColor: '#16a34a',
                });
            }
            return;
        }

        const manualPoints = resolveManualPointTotal();
        const hasAnyPoints = manualPoints.sl > 0 || manualPoints.vl > 0 || manualPoints.cto > 0;
        if (!hasAnyPoints) {
            event.preventDefault();
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    icon: 'warning',
                    title: 'No leave points entered',
                    text: 'Enter at least one SL, VL, or CTO point value before logging this employee leave entry.',
                    confirmButtonColor: '#16a34a',
                });
            }
            return;
        }

        if (leaveLogForm.dataset.confirmed === 'true') {
            leaveLogForm.dataset.confirmed = 'false';
            return;
        }

        event.preventDefault();

        const selectedEmployeeLabel = (employeeSearchInput.value || 'Selected employee').trim();
        const selectedLeaveOption = leaveTypeSelect instanceof HTMLSelectElement
            ? leaveTypeSelect.options[leaveTypeSelect.selectedIndex]
            : null;
        const leaveTypeLabel = selectedLeaveOption instanceof HTMLOptionElement
            ? (selectedLeaveOption.dataset.leaveName || selectedLeaveOption.textContent || 'Leave')
            : 'Leave';
        const pointSummary = [
            `SL: <strong>${manualPoints.sl.toFixed(2)}</strong>`,
            `VL: <strong>${manualPoints.vl.toFixed(2)}</strong>`,
            `CTO: <strong>${manualPoints.cto.toFixed(2)}</strong>`,
        ].join(' -+ ');

        const submitForm = () => {
            leaveLogForm.dataset.confirmed = 'true';
            leaveLogForm.requestSubmit();
        };

        showTimekeepingConfirmDialog({
            title: 'Log leave entry?',
            html: `You are about to log <strong>${String(totalDays.toFixed(2))}</strong> day(s) of <strong>${leaveTypeLabel}</strong> for <strong>${selectedEmployeeLabel}</strong>.<br><br>${pointSummary}`,
            confirmButtonText: 'Yes, log leave'
        }).then((confirmed) => {
            if (confirmed) {
                submitForm();
            }
        });
    });

    attendanceHelperForm?.addEventListener('submit', (event) => {
        if (!attendanceHelperHiddenInput || !attendanceHelperSearchInput) {
            return;
        }

        if (!attendanceHelperHiddenInput.value) {
            event.preventDefault();
            attendanceHelperSearchInput.setCustomValidity('Please select an employee from the search results.');
            attendanceHelperSearchInput.reportValidity();
            attendanceEmployeePicker.showResults();
            return;
        }

        attendanceHelperSearchInput.setCustomValidity('');

        if (attendanceHelperForm.dataset.confirmed === 'true') {
            attendanceHelperForm.dataset.confirmed = 'false';
            return;
        }

        event.preventDefault();

        const employeeLabel = (attendanceHelperSearchInput.value || 'Selected employee').trim();
        const entryTypeLabel = attendanceHelperEntryType instanceof HTMLSelectElement
            ? (attendanceHelperEntryType.options[attendanceHelperEntryType.selectedIndex]?.textContent || 'Attendance Entry')
            : 'Attendance Entry';
        const attendanceDateLabel = attendanceHelperDate instanceof HTMLInputElement ? attendanceHelperDate.value : '';
        const attendanceTimeLabel = attendanceHelperTime instanceof HTMLInputElement ? attendanceHelperTime.value : '';

        const submitForm = () => {
            attendanceHelperForm.dataset.confirmed = 'true';
            attendanceHelperForm.requestSubmit();
        };

        showTimekeepingConfirmDialog({
            title: 'Log attendance entry?',
            html: `You are about to log <strong>${entryTypeLabel}</strong> for <strong>${employeeLabel}</strong> on <strong>${attendanceDateLabel}</strong> at <strong>${attendanceTimeLabel}</strong>.`,
            confirmButtonText: 'Yes, log attendance'
        }).then((confirmed) => {
            if (confirmed) {
                submitForm();
            }
        });
    });

    resetLeavePointFields();
})();
</script>
