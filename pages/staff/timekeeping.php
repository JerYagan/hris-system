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
    <p class="text-sm text-gray-500">Review attendance and process office-scoped leave, overtime, and adjustment requests.</p>
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

<section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Attendance Logs</p>
        <p class="text-2xl font-semibold text-gray-800 mt-1"><?= (int)($timekeepingMetrics['attendance_logs'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Pending Leave</p>
        <p class="text-2xl font-semibold text-amber-700 mt-1"><?= (int)($timekeepingMetrics['pending_leave'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Pending Overtime</p>
        <p class="text-2xl font-semibold text-amber-700 mt-1"><?= (int)($timekeepingMetrics['pending_overtime'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Pending Adjustments</p>
        <p class="text-2xl font-semibold text-amber-700 mt-1"><?= (int)($timekeepingMetrics['pending_adjustments'] ?? 0) ?></p>
    </article>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Attendance Snapshot</h2>
        <p class="text-sm text-gray-500 mt-1">Latest attendance records in your current office scope.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Office</th>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Time In</th>
                    <th class="text-left px-4 py-3">Time Out</th>
                    <th class="text-left px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($attendanceRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="6">No attendance logs found in your scope.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($attendanceRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['date_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['time_in_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['time_out_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Leave Requests</h2>
        <p class="text-sm text-gray-500 mt-1">Review leave status changes with confirmation and transition checks.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="leaveSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="leaveSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by employee, office, leave type, reason, or date">
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
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($leaveRequestRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="7">No leave requests found in your scope.</td>
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
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-open-leave-modal
                                    data-request-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status-label="<?= htmlspecialchars((string)($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-date-range="<?= htmlspecialchars((string)($row['date_range'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    class="px-3 py-1.5 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100"
                                >
                                    Review
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="leaveFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="7">No leave requests match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Overtime Requests</h2>
            <p class="text-sm text-gray-500 mt-1">Review overtime endorsements using the same decision modal pattern.</p>
        </div>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="overtimeSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="overtimeSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by employee, office, reason, or date">
        </div>
        <div>
            <label for="overtimeStatusFilter" class="text-sm text-gray-600">All Statuses</label>
            <select id="overtimeStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm" id="overtimeRequestsTable">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Requested Window</th>
                    <th class="text-left px-4 py-3">Hours</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Requested</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($overtimeRequestRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="7">No overtime requests found in your scope.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($overtimeRequestRows as $row): ?>
                        <tr data-overtime-row data-overtime-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-overtime-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['overtime_date'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['time_window'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['hours_requested'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['requested_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-open-overtime-modal
                                    data-request-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status-label="<?= htmlspecialchars((string)($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-requested-window="<?= htmlspecialchars((string)($row['time_window'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    class="px-3 py-1.5 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100"
                                >
                                    Review
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="overtimeFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="7">No overtime requests match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
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
            <input id="adjustmentSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by employee, office, reason, or date">
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
                        <td class="px-4 py-3 text-gray-500" colspan="7">No adjustment requests found in your scope.</td>
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
                                    class="px-3 py-1.5 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100"
                                >
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
</section>

<div id="leaveRequestModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Review Leave Request</h3>
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
                <label for="leaveDecision" class="text-gray-600">Decision</label>
                <select id="leaveDecision" name="decision" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select decision</option>
                    <option value="approved">Approve</option>
                    <option value="rejected">Reject</option>
                    <option value="cancelled">Cancel</option>
                </select>
            </div>
            <div>
                <label for="leaveNotes" class="text-gray-600">Notes</label>
                <textarea id="leaveNotes" name="notes" rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Add approval or rejection notes."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="leaveModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="leaveSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Decision</button>
            </div>
        </form>
    </div>
</div>

<div id="overtimeRequestModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Review Overtime Request</h3>
            <button type="button" id="overtimeModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="overtimeForm" method="POST" action="timekeeping.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="review_overtime_request">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="request_id" id="overtimeRequestId" value="">

            <div>
                <label class="text-gray-600">Employee</label>
                <p id="overtimeEmployeeName" class="mt-1 text-sm font-medium text-gray-800">-</p>
            </div>
            <div>
                <label class="text-gray-600">Current Status</label>
                <p id="overtimeCurrentStatus" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label class="text-gray-600">Requested Window</label>
                <p id="overtimeRequestedWindow" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label for="overtimeDecision" class="text-gray-600">Decision</label>
                <select id="overtimeDecision" name="decision" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select decision</option>
                    <option value="approved">Approve</option>
                    <option value="rejected">Reject</option>
                    <option value="cancelled">Cancel</option>
                </select>
            </div>
            <div>
                <label for="overtimeNotes" class="text-gray-600">Notes</label>
                <textarea id="overtimeNotes" name="notes" rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Add overtime review notes."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="overtimeModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="overtimeSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Decision</button>
            </div>
        </form>
    </div>
</div>

<div id="adjustmentRequestModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Review Time Adjustment</h3>
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
                <label for="adjustmentDecision" class="text-gray-600">Decision</label>
                <select id="adjustmentDecision" name="decision" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select decision</option>
                    <option value="approved">Approve</option>
                    <option value="rejected">Reject</option>
                    <option value="needs_revision">Needs Revision</option>
                </select>
            </div>
            <div>
                <label for="adjustmentNotes" class="text-gray-600">Notes</label>
                <textarea id="adjustmentNotes" name="notes" rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Add correction notes for the requester."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="adjustmentModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="adjustmentSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Decision</button>
            </div>
        </form>
    </div>
</div>

<script src="../../assets/js/staff/timekeeping/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
