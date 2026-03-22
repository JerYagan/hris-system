<?php
require_once __DIR__ . '/includes/timekeeping/bootstrap.php';
require_once __DIR__ . '/includes/timekeeping/actions.php';
require_once __DIR__ . '/includes/timekeeping/data.php';

$pageTitle = 'Timekeeping | Staff';
$activePage = 'timekeeping.php';
$breadcrumbs = ['Timekeeping'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Timekeeping</h1>
    <p class="text-sm text-gray-500">Review attendance and process employee leave/CTO, time adjustment, COS flexible schedule, official business, and travel requests across the organization.</p>
</div>

<?php if ($state && $message): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($dataLoadError): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Attendance Records</p>
        <p class="text-2xl font-semibold text-gray-800 mt-1"><?= (int)($timekeepingMetrics['attendance_logs'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Pending Leave</p>
        <p class="text-2xl font-semibold text-amber-700 mt-1"><?= (int)($timekeepingMetrics['pending_leave'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Pending CTO</p>
        <p class="text-2xl font-semibold text-amber-700 mt-1"><?= (int)($timekeepingMetrics['pending_cto'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Pending Adjustments</p>
        <p class="text-2xl font-semibold text-amber-700 mt-1"><?= (int)($timekeepingMetrics['pending_adjustments'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Active RFID Cards</p>
        <p class="text-2xl font-semibold text-emerald-700 mt-1"><?= (int)($timekeepingMetrics['active_rfid_cards'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">RFID Scan Exceptions</p>
        <p class="text-2xl font-semibold text-rose-700 mt-1"><?= (int)($timekeepingMetrics['rfid_event_failures'] ?? 0) ?></p>
    </article>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Contractual Employees (COS)</h2>
        <p class="text-sm text-gray-500 mt-1">Use this roster to identify COS employees quickly before reviewing weekly flexible schedule proposals and travel-related requests.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Division</th>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Employment Status</th>
                    <th class="text-left px-4 py-3">Latest COS Proposal</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($cosEmployeeRows)): ?>
                    <tr><td class="px-4 py-3 text-gray-500" colspan="6">No active COS employees found in the current scope.</td></tr>
                <?php else: ?>
                    <?php foreach ($cosEmployeeRows as $row): ?>
                        <?php
                            $cosStatusRaw = strtolower((string)($row['latest_cos_status_raw'] ?? 'pending'));
                            $cosLocked = !empty($row['latest_cos_request_id']) && in_array($cosStatusRaw, ['approved', 'rejected', 'cancelled'], true);
                            $cosHasRequest = !empty($row['latest_cos_request_id']);
                        ?>
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars((string)($row['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['position_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['employment_status'] ?? 'COS'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['latest_cos_status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><?php if (!empty($row['latest_cos_requested_label']) && $row['latest_cos_requested_label'] !== '-'): ?><span class="block text-xs text-gray-500 mt-1">Requested: <?= htmlspecialchars((string)$row['latest_cos_requested_label'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></td>
                            <td class="px-4 py-3">
                                <?php if ($cosHasRequest): ?>
                                    <button
                                        type="button"
                                        data-open-ob-modal
                                        data-request-id="<?= htmlspecialchars((string)($row['latest_cos_request_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-request-type-label="<?= htmlspecialchars((string)($row['latest_cos_request_label'] ?? 'COS Schedule Proposal'), ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status="<?= htmlspecialchars($cosStatusRaw, ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status-label="<?= htmlspecialchars((string)($row['latest_cos_status'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?>"
                                        data-requested-window="<?= htmlspecialchars((string)($row['latest_cos_window'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                        data-reason="<?= htmlspecialchars((string)($row['latest_cos_reason'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100 disabled:opacity-50"
                                        <?= $cosLocked ? 'disabled' : '' ?>
                                    >
                                        <span class="material-symbols-outlined text-sm">fact_check</span>
                                        <?= $cosLocked ? 'Locked' : 'Review' ?>
                                    </button>
                                <?php else: ?>
                                    <span class="text-xs text-gray-500">No request</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">RFID Employee Registration</h2>
        <p class="text-sm text-gray-500 mt-1">Use employee ID to auto-fill employee name, division, and position before generating the RFID card record.</p>
    </header>

    <form id="rfidRegistrationForm" method="POST" action="timekeeping.php" class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <input type="hidden" name="form_action" value="assign_rfid_card">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div>
            <label for="rfidEmployeeId" class="text-gray-600">Employee ID</label>
            <input id="rfidEmployeeId" name="employee_id" type="text" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="EMP-0001" required>
        </div>
        <div>
            <label for="rfidEmployeeName" class="text-gray-600">Employee Name</label>
            <input id="rfidEmployeeName" type="text" class="w-full mt-1 border rounded-md px-3 py-2 bg-gray-50" placeholder="Juan Dela Cruz" readonly>
        </div>
        <div>
            <label for="rfidDepartment" class="text-gray-600">Division</label>
            <input id="rfidDepartment" type="text" class="w-full mt-1 border rounded-md px-3 py-2 bg-gray-50" placeholder="HR Division" readonly>
        </div>
        <div>
            <label for="rfidPosition" class="text-gray-600">Position</label>
            <input id="rfidPosition" type="text" class="w-full mt-1 border rounded-md px-3 py-2 bg-gray-50" placeholder="HR Assistant I" readonly>
        </div>
        <div>
            <label for="rfidCardUid" class="text-gray-600">RFID Card UID</label>
            <input id="rfidCardUid" name="card_uid" type="text" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="04AABBCC11" required>
        </div>
        <div>
            <label for="rfidCardLabel" class="text-gray-600">Card Label</label>
            <input id="rfidCardLabel" name="card_label" type="text" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Main Office Badge">
        </div>
        <div class="md:col-span-3 flex justify-end">
            <button type="submit" id="rfidGenerateButton" class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">
                <span class="material-symbols-outlined text-sm">contactless</span>
                Assign / Replace RFID Card
            </button>
        </div>
    </form>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">RFID Card Assignments</h2>
        <p class="text-sm text-gray-500 mt-1">Active and recent card assignments for employees in scope.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Division</th>
                    <th class="text-left px-4 py-3">Card UID</th>
                    <th class="text-left px-4 py-3">Label</th>
                    <th class="text-left px-4 py-3">Issued</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($rfidAssignedCardRows)): ?>
                    <tr><td class="px-4 py-3 text-gray-500" colspan="7">No RFID card assignments found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rfidAssignedCardRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['employee_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars((string)($row['card_uid_masked'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['card_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <p><?= htmlspecialchars((string)($row['issued_at_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php if (($row['deactivated_at_label'] ?? '-') !== '-'): ?>
                                    <p class="text-xs text-gray-500 mt-1">Ended: <?= htmlspecialchars((string)$row['deactivated_at_label'], ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <?php if (($row['status_raw'] ?? '') === 'active'): ?>
                                    <form method="POST" action="timekeeping.php" class="inline-flex">
                                        <input type="hidden" name="form_action" value="deactivate_rfid_card">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="card_id" value="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs rounded-md border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100">
                                            <span class="material-symbols-outlined text-sm">block</span>
                                            Deactivate
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">No action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">RFID Attendance Assist</h2>
                <p class="text-sm text-gray-500 mt-1">Temporary/supportive helper only while scanner integration is pending. Use employee ID to auto-fill name before logging.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 text-xs px-2.5 py-1 font-medium">Temporary Supportive Tool</span>
        </div>
    </header>

    <form id="rfidAttendanceAssistForm" method="POST" action="timekeeping.php" class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <input type="hidden" name="form_action" value="staff_rfid_attendance_assist">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div>
            <label for="rfidAttendanceEmployeeId" class="text-gray-600">Employee ID</label>
            <input id="rfidAttendanceEmployeeId" name="employee_id" type="text" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="EMP-0001" required>
        </div>
        <div>
            <label for="rfidAttendanceEmployeeName" class="text-gray-600">Employee Name</label>
            <input id="rfidAttendanceEmployeeName" type="text" class="w-full mt-1 border rounded-md px-3 py-2 bg-gray-50" placeholder="Juan Dela Cruz" readonly>
        </div>
        <div>
            <label for="rfidAttendanceScannedAt" class="text-gray-600">Tap Timestamp</label>
            <input id="rfidAttendanceScannedAt" name="scanned_at" type="datetime-local" class="w-full mt-1 border rounded-md px-3 py-2">
        </div>
        <div class="md:col-span-1 flex items-end">
            <button type="submit" id="rfidLogAttendanceButton" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">
                <span class="material-symbols-outlined text-sm">fact_check</span>
                Process Assigned RFID Tap
            </button>
        </div>
    </form>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Recent RFID Scan Events</h2>
        <p class="text-sm text-gray-500 mt-1">Review successful taps, duplicates, and scan failures without leaving the staff timekeeping page.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Scanned At</th>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Card UID</th>
                    <th class="text-left px-4 py-3">Source</th>
                    <th class="text-left px-4 py-3">Device</th>
                    <th class="text-left px-4 py-3">Result</th>
                    <th class="text-left px-4 py-3">Message</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($rfidRecentEventRows)): ?>
                    <tr><td class="px-4 py-3 text-gray-500" colspan="7">No RFID scan events found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rfidRecentEventRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['scanned_at_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['employee_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars((string)($row['card_uid_masked'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['request_source_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['device_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['result_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['result_label'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (!empty($row['attendance_linked'])): ?>
                                    <span class="block text-xs text-emerald-700 mt-1">Linked to attendance log</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['result_message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Attendance Records</h2>
        <p class="text-sm text-gray-500 mt-1">Latest attendance records across all active employees.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
            <label for="attendanceDatePreset" class="text-sm text-gray-600">Date Filter</label>
            <select id="attendanceDatePreset" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="today" selected>Today</option>
                <option value="all">All Dates</option>
                <option value="custom">Custom Range</option>
            </select>
        </div>
        <div>
            <label for="attendanceDateFrom" class="text-sm text-gray-600">From</label>
            <input id="attendanceDateFrom" type="date" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
        </div>
        <div>
            <label for="attendanceDateTo" class="text-sm text-gray-600">To</label>
            <input id="attendanceDateTo" type="date" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm" id="attendanceSnapshotTable">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Division</th>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Time In</th>
                    <th class="text-left px-4 py-3">Time Out</th>
                    <th class="text-left px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($attendanceRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="6">No attendance logs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($attendanceRows as $row): ?>
                        <tr data-attendance-row data-attendance-date="<?= htmlspecialchars((string)($row['attendance_date_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['date_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['time_in_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['time_out_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (!empty($row['source_label'])): ?>
                                    <span class="block text-xs text-gray-500 mt-1">Source: <?= htmlspecialchars((string)$row['source_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="attendancePaginationInfo" class="text-xs text-slate-500">Page 1 of 1</p>
        <div class="flex items-center gap-2">
            <button type="button" id="attendancePrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <button type="button" id="attendanceNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Leave/CTO Requests</h2>
        <p class="text-sm text-gray-500 mt-1">Review leave and CTO recommendations with confirmation and transition checks.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="leaveSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="leaveSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by employee, division, leave type, reason, or date">
        </div>
        <div>
            <label for="leaveStatusFilter" class="text-sm text-gray-600">All Statuses</label>
            <select id="leaveStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm" id="leaveRequestsTable">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Leave Type</th>
                    <th class="text-left px-4 py-3">Date Range</th>
                    <th class="text-left px-4 py-3">Days</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Requested</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($leaveRequestRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="6">No leave requests found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leaveRequestRows as $row): ?>
                        <tr data-leave-row data-leave-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-leave-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['leave_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['date_range'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['days_count'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['requested_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="leaveFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="6">No leave requests match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="leavePaginationInfo" class="text-xs text-slate-500">Page 1 of 1</p>
        <div class="flex items-center gap-2">
            <button type="button" id="leavePrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <button type="button" id="leaveNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<section class="bg-white border rounded-xl mt-6">
    <header class="px-6 py-4 border-b">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Special Timekeeping Requests</h2>
            <p class="text-sm text-gray-500 mt-1">Review Official Business, COS flexible schedule, Travel Order, and Travel Abroad requests routed through the special request workflow.</p>
        </div>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="obSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="obSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by employee, division, reason, or date">
        </div>
        <div>
            <label for="obStatusFilter" class="text-sm text-gray-600">All Statuses</label>
            <select id="obStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="needs_revision">Needs Revision</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm" id="obRequestsTable">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Request Type</th>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Requested Window</th>
                    <th class="text-left px-4 py-3">Details</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Requested</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($officialBusinessRequestRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="8">No special timekeeping requests found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($officialBusinessRequestRows as $row): ?>
                        <tr data-ob-row data-ob-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-ob-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['request_label'] ?? 'Special Request'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['overtime_date'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['time_window'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <p><?= htmlspecialchars((string)($row['reason'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php if (!empty($row['detail_summary'])): ?>
                                    <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)$row['detail_summary'], ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                                <?php if (!empty($row['attachment_url']) && !empty($row['attachment_name'])): ?>
                                    <a href="<?= htmlspecialchars((string)$row['attachment_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 mt-1 text-xs font-medium text-green-700 hover:underline">
                                        <span class="material-symbols-outlined text-sm">attach_file</span>
                                        <?= htmlspecialchars((string)$row['attachment_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['requested_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-open-ob-modal
                                    data-request-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-request-type-label="<?= htmlspecialchars((string)($row['request_label'] ?? 'Special Request'), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status-label="<?= htmlspecialchars((string)($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-requested-window="<?= htmlspecialchars((string)($row['time_window'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-reason="<?= htmlspecialchars(trim((string)($row['reason'] ?? '-') . (!empty($row['detail_summary']) ? ' | ' . (string)$row['detail_summary'] : '')), ENT_QUOTES, 'UTF-8') ?>"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100"
                                >
                                    <span class="material-symbols-outlined text-sm">fact_check</span>
                                    Review
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="obFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="8">No special timekeeping requests match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="obPaginationInfo" class="text-xs text-slate-500">Page 1 of 1</p>
        <div class="flex items-center gap-2">
            <button type="button" id="obPrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <button type="button" id="obNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<section class="bg-white border rounded-xl mt-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Time Adjustment Requests</h2>
        <p class="text-sm text-gray-500 mt-1">Review correction requests for attendance log updates.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="adjustmentSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="adjustmentSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by employee, division, reason, or date">
        </div>
        <div>
            <label for="adjustmentStatusFilter" class="text-sm text-gray-600">All Statuses</label>
            <select id="adjustmentStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="needs_revision">Needs Revision</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm" id="adjustmentRequestsTable">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Attendance Date</th>
                    <th class="text-left px-4 py-3">Requested Window</th>
                    <th class="text-left px-4 py-3">Reason</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Submitted</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($adjustmentRequestRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="7">No adjustment requests found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($adjustmentRequestRows as $row): ?>
                        <tr data-adjustment-row data-adjustment-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-adjustment-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['attendance_date'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['requested_window'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['reason'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['submitted_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-open-adjustment-modal
                                    data-request-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status-label="<?= htmlspecialchars((string)($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-requested-window="<?= htmlspecialchars((string)($row['requested_window'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-reason="<?= htmlspecialchars((string)($row['reason'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100"
                                >
                                    <span class="material-symbols-outlined text-sm">fact_check</span>
                                    Review
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="adjustmentFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="7">No adjustment requests match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="adjustmentPaginationInfo" class="text-xs text-slate-500">Page 1 of 1</p>
        <div class="flex items-center gap-2">
            <button type="button" id="adjustmentPrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <button type="button" id="adjustmentNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<div id="leaveRequestModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Recommend Leave Decision</h3>
            <button type="button" id="leaveModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="leaveForm" method="POST" action="timekeeping.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="review_leave_request">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="request_id" id="leaveRequestId" value="">

            <div>
                <label class="text-gray-600">Employee</label>
                <p id="leaveEmployeeName" class="mt-1 text-sm font-medium text-gray-800">-</p>
            </div>
            <div>
                <label class="text-gray-600">Current Status</label>
                <p id="leaveCurrentStatus" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label class="text-gray-600">Date Range</label>
                <p id="leaveDateRange" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label class="text-gray-600">Reason</label>
                <p id="leaveReason" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label for="leaveDecision" class="text-gray-600">Recommendation</label>
                <select id="leaveDecision" name="decision" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select recommendation</option>
                    <option value="approved">Recommend Approval</option>
                    <option value="rejected">Recommend Rejection</option>
                    <option value="cancelled">Recommend Cancellation</option>
                </select>
            </div>
            <div>
                <label for="leaveNotes" class="text-gray-600">Notes</label>
                <textarea id="leaveNotes" name="notes" rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Add recommendation notes for admin review."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="leaveModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="leaveSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Submit Recommendation</button>
            </div>
        </form>
    </div>
</div>

<div id="obRequestModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 id="obModalTitle" class="text-lg font-semibold text-gray-800">Recommend Special Timekeeping Decision</h3>
            <button type="button" id="obModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="obForm" method="POST" action="timekeeping.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="review_ob_request">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="request_id" id="obRequestId" value="">

            <div>
                <label class="text-gray-600">Employee</label>
                <p id="obEmployeeName" class="mt-1 text-sm font-medium text-gray-800">-</p>
            </div>
            <div>
                <label class="text-gray-600">Request Type</label>
                <p id="obRequestTypeLabel" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label class="text-gray-600">Current Status</label>
                <p id="obCurrentStatus" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label class="text-gray-600">Requested Window</label>
                <p id="obRequestedWindow" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label class="text-gray-600">Reason</label>
                <p id="obReason" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label for="obDecision" class="text-gray-600">Recommendation</label>
                <select id="obDecision" name="decision" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select recommendation</option>
                    <option value="approved">Recommend Approval</option>
                    <option value="rejected">Recommend Rejection</option>
                    <option value="needs_revision">Recommend Request Changes</option>
                </select>
            </div>
            <div>
                <label for="obNotes" class="text-gray-600">Notes</label>
                <textarea id="obNotes" name="notes" rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Add recommendation notes for admin review."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="obModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="obSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Submit Recommendation</button>
            </div>
        </form>
    </div>
</div>

<div id="adjustmentRequestModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Recommend Time Adjustment Decision</h3>
            <button type="button" id="adjustmentModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="adjustmentForm" method="POST" action="timekeeping.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="review_time_adjustment">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="request_id" id="adjustmentRequestId" value="">

            <div>
                <label class="text-gray-600">Employee</label>
                <p id="adjustmentEmployeeName" class="mt-1 text-sm font-medium text-gray-800">-</p>
            </div>
            <div>
                <label class="text-gray-600">Current Status</label>
                <p id="adjustmentCurrentStatus" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label class="text-gray-600">Requested Window</label>
                <p id="adjustmentRequestedWindow" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label class="text-gray-600">Reason</label>
                <p id="adjustmentReason" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label for="adjustmentDecision" class="text-gray-600">Recommendation</label>
                <select id="adjustmentDecision" name="decision" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select recommendation</option>
                    <option value="approved">Recommend Approval</option>
                    <option value="rejected">Recommend Rejection</option>
                    <option value="needs_revision">Recommend Needs Revision</option>
                </select>
            </div>
            <div>
                <label for="adjustmentNotes" class="text-gray-600">Notes</label>
                <textarea id="adjustmentNotes" name="notes" rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Add recommendation notes for admin review."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="adjustmentModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="adjustmentSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Submit Recommendation</button>
            </div>
        </form>
    </div>
</div>

<script id="rfidEmployeeLookupData" type="application/json"><?= (string)json_encode(
    $rfidEmployeeLookup,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?></script>

<script src="../../assets/js/staff/timekeeping/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
