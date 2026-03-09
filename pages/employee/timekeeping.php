<?php
/**
 * Employee Timekeeping
 * DA-ATI HRIS
 */

require_once __DIR__ . '/includes/timekeeping/bootstrap.php';
require_once __DIR__ . '/includes/timekeeping/actions.php';
require_once __DIR__ . '/includes/timekeeping/data.php';

$leaveBalanceRows = $leaveBalanceRows ?? [];
$leavePointSummary = $leavePointSummary ?? ['sl' => 0.0, 'vl' => 0.0, 'cto' => 0.0];
$postedLeavePointSummary = $postedLeavePointSummary ?? ['sl' => 0.0, 'vl' => 0.0, 'cto' => 0.0];
$usedLeavePointSummary = $usedLeavePointSummary ?? ['sl' => 0.0, 'vl' => 0.0, 'cto' => 0.0];
$pendingLeavePointSummary = $pendingLeavePointSummary ?? ['sl' => 0.0, 'vl' => 0.0, 'cto' => 0.0];
$leaveBalanceLastUpdatedAt = $leaveBalanceLastUpdatedAt ?? null;
$leaveBalanceRefreshUrl = $leaveBalanceRefreshUrl ?? 'timekeeping.php?partial=leave-balance';

$pageTitle = 'Timekeeping | DA HRIS';
$activePage = 'timekeeping.php';
$breadcrumbs = ['Timekeeping'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/employee/timekeeping/index.js';

ob_start();

$escape = static function (mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$formatDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '-';
    }
    $ts = strtotime($value);
    return $ts === false ? '-' : date('M j, Y', $ts);
};

$formatDateTime = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '-';
    }
    $ts = strtotime($value);
    return $ts === false ? '-' : date('M j, Y g:i A', $ts);
};

$formatTime = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '-';
    }
    $ts = strtotime($value);
    return $ts === false ? '-' : date('g:i A', $ts);
};

$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
    'approved', 'released', 'present' => [ucfirst($key), 'bg-emerald-50 text-emerald-700'],
    'pending', 'submitted', 'late' => [ucfirst($key), 'bg-amber-50 text-amber-700'],
    'rejected', 'absent' => [ucfirst($key), 'bg-rose-50 text-rose-700'],
    'leave' => ['Leave', 'bg-sky-50 text-sky-700'],
    default => [ucfirst($key !== '' ? $key : 'draft'), 'bg-slate-100 text-slate-700'],
    };
};

$queryParams = [];
if (!empty($attendanceStatusFilter)) {
    $queryParams['attendance_status'] = $attendanceStatusFilter;
}
if (!empty($attendanceFrom)) {
    $queryParams['attendance_from'] = $attendanceFrom;
}
if (!empty($attendanceTo)) {
    $queryParams['attendance_to'] = $attendanceTo;
}

$todayManila = (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
$attendanceExportFrom = $attendanceFrom ?? date('Y-m-01');
$attendanceExportTo = $attendanceTo ?? date('Y-m-t');

$renderLeaveBalanceSection = static function () use (
    $escape,
    $formatDateTime,
    $leaveBalanceLastUpdatedAt,
    $leaveBalanceRefreshUrl,
    $leaveBalanceRows,
    $leavePointSummary,
    $pendingLeavePointSummary,
    $postedLeavePointSummary
): void {
?>
<section id="leave-balance" data-refresh-url="<?= $escape($leaveBalanceRefreshUrl ?? 'timekeeping.php?partial=leave-balance') ?>" class="bg-white rounded-xl shadow p-6 mb-6">
  <div class="flex flex-col gap-2 mb-4 md:flex-row md:items-start md:justify-between">
    <div>
      <h2 class="text-lg font-bold">Accumulated Leave/CTO <span class="text-daGreen">Points</span></h2>
      <p class="text-xs text-gray-500 mt-1">This section shows only the total accumulated SL, VL, and CTO points based on what Admin inserted for your record.</p>
    </div>
    <div class="text-xs text-gray-500 md:text-right">
      <p class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Live sync enabled</p>
      <p class="mt-2">Admin balance last updated: <?= $escape($formatDateTime($leaveBalanceLastUpdatedAt)) ?></p>
      <p class="mt-1">Section refreshes automatically every 60 seconds.</p>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm mb-4">
    <div class="rounded-lg bg-sky-50 px-4 py-3 border border-sky-100">
      <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">SL Points</p>
      <p class="mt-1 text-xl font-bold text-sky-800"><?= $escape(number_format((float)($leavePointSummary['sl'] ?? 0), 2)) ?></p>
    </div>
    <div class="rounded-lg bg-emerald-50 px-4 py-3 border border-emerald-100">
      <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">VL Points</p>
      <p class="mt-1 text-xl font-bold text-emerald-800"><?= $escape(number_format((float)($leavePointSummary['vl'] ?? 0), 2)) ?></p>
    </div>
    <div class="rounded-lg bg-amber-50 px-4 py-3 border border-amber-100">
      <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">CTO Points</p>
      <p class="mt-1 text-xl font-bold text-amber-800"><?= $escape(number_format((float)($leavePointSummary['cto'] ?? 0), 2)) ?></p>
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b text-gray-500">
          <th class="text-left py-3">Leave Type</th>
          <th class="text-left py-3">Total Accumulated Points</th>
          <th class="text-left py-3">Accumulated / Updated</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($leaveBalanceRows)): ?>
          <tr><td class="py-3 text-gray-500" colspan="3">No accumulated leave point records found yet.</td></tr>
        <?php else: ?>
          <?php foreach ($leaveBalanceRows as $balance): ?>
            <tr class="border-b">
              <td class="py-3"><?= $escape((string)($balance['leave_name'] ?? '-')) ?></td>
              <td class="py-3"><?= $escape(number_format((float)($balance['admin_posted_total'] ?? 0), 2)) ?></td>
              <td class="py-3"><?= $escape($formatDateTime($balance['updated_at'] ?? null)) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php
};

if (($_GET['partial'] ?? '') === 'leave-balance') {
    ob_start();
    $renderLeaveBalanceSection();
    echo ob_get_clean();
    return;
}
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold">Timekeeping</h1>
  <p class="text-sm text-gray-500">Manage your attendance, view the leave card file, and submit official business or record-level time adjustment requests.</p>
</div>

<?php if (!empty($message)): ?>
  <?php $alertClass = ($state ?? '') === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'; ?>
  <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $escape($alertClass) ?>" aria-live="polite">
    <?= $escape($message) ?>
  </div>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
  <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" aria-live="polite">
    <?= $escape($dataLoadError) ?>
  </div>
<?php endif; ?>

<section class="bg-white rounded-xl shadow p-6 mb-6">
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold">Attendance <span class="text-daGreen">Overview</span></h2>
    <div class="flex flex-wrap gap-2">
      <a href="/hris-system/assets/Leave_Card_Template.xlsx" download class="inline-flex items-center gap-2 bg-daGreen text-white px-5 py-2 rounded-lg text-sm font-medium hover:opacity-90"><span class="material-symbols-outlined text-base">visibility</span>View Leave Card</a>
      <button data-open-ob class="inline-flex items-center gap-2 border px-5 py-2 rounded-lg text-sm font-medium"><span class="material-symbols-outlined text-base">work_history</span>File Official Business</button>
    </div>
  </div>

  <div class="grid md:grid-cols-4 gap-4 text-sm mb-4">
    <div><label class="text-gray-500">Employee Name</label><input disabled value="<?= $escape($employeeName) ?>" class="w-full mt-1 p-2 bg-gray-100 rounded-lg"></div>
    <div><label class="text-gray-500">Employee ID</label><input disabled value="<?= $escape($employeeCode) ?>" class="w-full mt-1 p-2 bg-gray-100 rounded-lg"></div>
    <div><label class="text-gray-500">Month</label><input disabled value="<?= $escape($attendanceSummary['month_label'] ?? date('F Y')) ?>" class="w-full mt-1 p-2 bg-gray-100 rounded-lg"></div>
    <div><label class="text-gray-500">Working Days</label><input disabled value="<?= $escape((string)($attendanceSummary['working_days'] ?? 0)) ?>" class="w-full mt-1 p-2 bg-gray-100 rounded-lg"></div>
  </div>

  <div class="grid md:grid-cols-3 gap-4 text-sm">
    <div class="border rounded-lg p-3"><p class="text-gray-500">Present</p><p class="font-semibold text-lg"><?= $escape((string)($attendanceSummary['present_days'] ?? 0)) ?></p></div>
    <div class="border rounded-lg p-3"><p class="text-gray-500">Late</p><p class="font-semibold text-lg"><?= $escape((string)($attendanceSummary['late_days'] ?? 0)) ?></p></div>
    <div class="border rounded-lg p-3"><p class="text-gray-500">Leave</p><p class="font-semibold text-lg"><?= $escape((string)($attendanceSummary['leave_days'] ?? 0)) ?></p></div>
  </div>
</section>

<section class="bg-white rounded-xl shadow p-6 mb-6">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-bold">Attendance <span class="text-daGreen">Records</span></h2>
    <button type="button" data-open-attendance-export class="inline-flex items-center gap-2 border px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50"><span class="material-symbols-outlined text-base">picture_as_pdf</span>Export PDF</button>
  </div>

  <form method="get" action="timekeeping.php" class="grid md:grid-cols-4 gap-3 mb-4 text-sm">
    <div>
      <label class="text-gray-500">From</label>
      <input type="date" name="attendance_from" value="<?= $escape((string)$attendanceFrom) ?>" class="w-full mt-1 border rounded-lg p-2">
    </div>
    <div>
      <label class="text-gray-500">To</label>
      <input type="date" name="attendance_to" value="<?= $escape((string)$attendanceTo) ?>" class="w-full mt-1 border rounded-lg p-2">
    </div>
    <div>
      <label class="text-gray-500">Status</label>
      <select name="attendance_status" class="w-full mt-1 border rounded-lg p-2">
          <option value="">All</option>
        <?php foreach (['present', 'late', 'absent', 'leave', 'holiday', 'rest_day'] as $statusOption): ?>
          <option value="<?= $escape($statusOption) ?>" <?= $attendanceStatusFilter === $statusOption ? 'selected' : '' ?>><?= $escape(ucwords(str_replace('_', ' ', $statusOption))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="flex items-end gap-2">
      <button type="submit" class="border px-4 py-2 rounded-lg">Apply</button>
      <a href="timekeeping.php" class="border px-4 py-2 rounded-lg">Reset</a>
    </div>
  </form>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b text-gray-500">
          <th class="text-left py-3">Date</th>
          <th class="text-left py-3">Time In</th>
          <th class="text-left py-3">Time Out</th>
          <th class="text-left py-3">Hours</th>
          <th class="text-left py-3">Status</th>
          <th class="text-left py-3">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($attendanceRows)): ?>
          <tr><td class="py-4 text-gray-500" colspan="6">No attendance records found.</td></tr>
        <?php else: ?>
          <?php foreach ($attendanceRows as $row): ?>
            <?php [$label, $pillClass] = $statusPill((string)($row['display_status'] ?? ($row['attendance_status'] ?? 'present'))); ?>
            <tr class="border-b js-module-filter-row" data-source="attendance" data-status="<?= $escape(strtolower((string)($row['display_status'] ?? ($row['attendance_status'] ?? 'present')))) ?>" data-date="<?= $escape((string)($row['attendance_date'] ?? '')) ?>" data-search="<?= $escape(strtolower(trim(($row['attendance_date'] ?? '') . ' ' . ($row['attendance_status'] ?? '') . ' ' . ($row['display_status'] ?? '') . ' ' . ($formatTime($row['time_in'] ?? '') ?? '') . ' ' . ($formatTime($row['time_out'] ?? '') ?? '')))) ?>">
              <td class="py-3"><?= $escape($formatDate($row['attendance_date'] ?? '')) ?></td>
              <td class="py-3"><?= $escape($formatTime($row['time_in'] ?? '')) ?></td>
              <td class="py-3"><?= $escape($formatTime($row['time_out'] ?? '')) ?></td>
              <td class="py-3"><?= $escape(number_format((float)($row['hours_worked'] ?? 0), 2)) ?></td>
              <td class="py-3">
                <span class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full font-medium <?= $escape($pillClass) ?>"><?= $escape($label) ?></span>
                <?php if (!empty($row['is_late_by_policy'])): ?>
                  <p class="text-[11px] text-amber-700 mt-1">Late by approved policy (9:01 AM+)</p>
                <?php endif; ?>
              </td>
              <td class="py-3">
                <button
                  type="button"
                  data-open-adjustment
                  data-attendance-id="<?= $escape((string)($row['id'] ?? '')) ?>"
                  data-attendance-date="<?= $escape((string)($row['attendance_date'] ?? '')) ?>"
                  data-current-time-in="<?= $escape($formatTime($row['time_in'] ?? '')) ?>"
                  data-current-time-out="<?= $escape($formatTime($row['time_out'] ?? '')) ?>"
                  class="inline-flex items-center gap-1 border px-3 py-1 rounded-lg text-xs"
                ><span class="material-symbols-outlined text-sm">edit_calendar</span>Request Adjustment</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="mt-4 flex items-center justify-between text-sm">
    <div class="text-gray-500">Page <?= $escape((string)$attendancePage) ?></div>
    <div class="flex gap-2">
      <?php if ($attendanceHasPrev): ?>
        <?php $prevParams = $queryParams; $prevParams['attendance_page'] = $attendancePage - 1; ?>
        <a href="timekeeping.php?<?= $escape(http_build_query($prevParams)) ?>" class="border px-3 py-1 rounded-lg">Previous</a>
      <?php endif; ?>
      <?php if ($attendanceHasNext): ?>
        <?php $nextParams = $queryParams; $nextParams['attendance_page'] = $attendancePage + 1; ?>
        <a href="timekeeping.php?<?= $escape(http_build_query($nextParams)) ?>" class="border px-3 py-1 rounded-lg">Next</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php $renderLeaveBalanceSection(); ?>

<section class="bg-white rounded-xl shadow p-6 mb-6">
  <h2 class="text-lg font-bold mb-4">Time <span class="text-daGreen">Adjustment Requests</span></h2>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b text-gray-500">
          <th class="text-left py-3">Attendance Date</th>
          <th class="text-left py-3">Requested In</th>
          <th class="text-left py-3">Requested Out</th>
          <th class="text-left py-3">Reason</th>
          <th class="text-left py-3">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($timeAdjustmentRows)): ?>
          <tr><td class="py-3 text-gray-500" colspan="5">No time adjustment requests yet.</td></tr>
        <?php else: ?>
          <?php foreach ($timeAdjustmentRows as $adjustment): ?>
            <?php [$label, $pillClass] = $statusPill((string)($adjustment['status'] ?? 'pending')); ?>
            <tr class="border-b js-module-filter-row" data-source="adjustment" data-status="<?= $escape(strtolower((string)($adjustment['status'] ?? 'pending'))) ?>" data-date="<?= $escape((string)($adjustment['attendance_date'] ?? '')) ?>" data-search="<?= $escape(strtolower(trim(($adjustment['attendance_date'] ?? '') . ' ' . ($adjustment['reason'] ?? '') . ' ' . ($adjustment['status'] ?? '')))) ?>">
              <td class="py-3"><?= $escape($formatDate($adjustment['attendance_date'] ?? '')) ?></td>
              <td class="py-3"><?= $escape($formatDateTime($adjustment['requested_time_in'] ?? '')) ?></td>
              <td class="py-3"><?= $escape($formatDateTime($adjustment['requested_time_out'] ?? '')) ?></td>
              <td class="py-3"><?= $escape((string)($adjustment['reason'] ?? '')) ?></td>
              <td class="py-3"><span class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full font-medium <?= $escape($pillClass) ?>"><?= $escape($label) ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="bg-white rounded-xl shadow p-6">
  <h2 class="text-lg font-bold mb-4">Official Business <span class="text-daGreen">Requests</span></h2>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b text-gray-500">
          <th class="text-left py-3">Date</th>
          <th class="text-left py-3">Time Out</th>
          <th class="text-left py-3">Time In</th>
          <th class="text-left py-3">Hours</th>
          <th class="text-left py-3">Reason</th>
          <th class="text-left py-3">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($overtimeRows)): ?>
          <tr><td class="py-3 text-gray-500" colspan="6">No official business requests yet.</td></tr>
        <?php else: ?>
          <?php foreach ($overtimeRows as $overtime): ?>
            <?php [$label, $pillClass] = $statusPill((string)($overtime['status'] ?? 'pending')); ?>
            <tr class="border-b js-module-filter-row" data-source="ob" data-status="<?= $escape(strtolower((string)($overtime['status'] ?? 'pending'))) ?>" data-date="<?= $escape((string)($overtime['overtime_date'] ?? '')) ?>" data-search="<?= $escape(strtolower(trim(($overtime['overtime_date'] ?? '') . ' ' . ($overtime['reason'] ?? '') . ' ' . ($overtime['status'] ?? '') . ' ' . ($overtime['start_time'] ?? '') . ' ' . ($overtime['end_time'] ?? '')))) ?>">
              <td class="py-3"><?= $escape($formatDate($overtime['overtime_date'] ?? '')) ?></td>
              <td class="py-3"><?= $escape((string)($overtime['start_time'] ?? '-')) ?></td>
              <td class="py-3"><?= $escape((string)($overtime['end_time'] ?? '-')) ?></td>
              <td class="py-3"><?= $escape(number_format((float)($overtime['hours_requested'] ?? 0), 2)) ?></td>
              <td class="py-3"><?= $escape((string)($overtime['reason'] ?? '')) ?></td>
              <td class="py-3"><span class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full font-medium <?= $escape($pillClass) ?>"><?= $escape($label) ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<div id="attendanceExportModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden" aria-hidden="true">
  <div class="bg-white w-full max-w-lg rounded-xl shadow-lg max-h-[90vh] flex flex-col">
    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 class="text-lg font-semibold">Export Attendance PDF</h2>
      <button type="button" data-close-attendance-export><span class="material-icons">close</span></button>
    </div>

    <form method="get" action="export/attendance.php" target="_blank" class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <div>
        <label class="text-gray-500">From Date</label>
        <input type="date" name="from" value="<?= $escape((string)$attendanceExportFrom) ?>" class="w-full mt-1 border rounded-lg p-2" required>
      </div>
      <div>
        <label class="text-gray-500">To Date</label>
        <input type="date" name="to" value="<?= $escape((string)$attendanceExportTo) ?>" class="w-full mt-1 border rounded-lg p-2" required>
      </div>
      <p class="text-xs text-gray-500">The PDF will include your name, selected date range, attendance table, and signature/date lines.</p>

      <div class="pt-2 flex justify-end gap-3">
        <button type="button" data-close-attendance-export class="border px-4 py-2 rounded-lg text-sm">Cancel</button>
        <button type="submit" class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm">Generate PDF</button>
      </div>
    </form>
  </div>
</div>

<div id="adjustmentModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden" aria-hidden="true">
  <div class="bg-white w-full max-w-lg rounded-xl shadow-lg max-h-[90vh] flex flex-col">
    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 class="text-lg font-semibold">Request Time Adjustment</h2>
      <button type="button" data-close-adjustment><span class="material-icons">close</span></button>
    </div>

    <form method="post" action="timekeeping.php" class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
      <input type="hidden" name="action" value="create_time_adjustment_request">
      <input type="hidden" id="adjustmentAttendanceLogId" name="attendance_log_id" value="">

      <div class="rounded-lg border p-3 bg-gray-50">
        <p class="text-xs text-gray-500">Select Time Adjustment Date</p>
        <p id="adjustmentAttendanceInfo" class="text-sm font-medium">Manual request</p>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-gray-500">Requested Time In</label>
          <input type="time" name="requested_time_in" class="w-full border rounded-lg p-2 mt-1">
        </div>
        <div>
          <label class="text-gray-500">Requested Time Out</label>
          <input type="time" name="requested_time_out" class="w-full border rounded-lg p-2 mt-1">
        </div>
      </div>

      <textarea name="reason" class="w-full border rounded-lg p-2" rows="3" placeholder="Reason for adjustment" required></textarea>

      <div class="pt-2 flex justify-end gap-3">
        <button type="button" data-close-adjustment class="border px-4 py-2 rounded-lg text-sm">Cancel</button>
        <button type="submit" class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm">Submit</button>
      </div>
    </form>
  </div>
</div>

<div id="obModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden" aria-hidden="true">
  <div class="bg-white w-full max-w-lg rounded-xl shadow-lg max-h-[90vh] flex flex-col">
    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 class="text-lg font-semibold">Official Business Request</h2>
      <button type="button" data-close-ob><span class="material-icons">close</span></button>
    </div>

    <form method="post" action="timekeeping.php" class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
      <input type="hidden" name="action" value="create_official_business_request">

      <div>
        <label class="text-gray-500">Select Official Business Date</label>
        <input type="date" name="ob_date" min="<?= $escape($todayManila) ?>" class="w-full mt-1 border rounded-lg p-2" required>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <input type="time" name="time_out" class="border rounded-lg p-2" required>
        <input type="time" name="time_in" class="border rounded-lg p-2" required>
      </div>
      <input type="number" name="hours_requested" step="0.25" min="0.25" max="24" class="w-full border rounded-lg p-2" placeholder="Official Business Hours" required>
      <textarea name="reason" class="w-full border rounded-lg p-2" rows="3" placeholder="Reason for official business" required></textarea>

      <div class="pt-2 flex justify-end gap-3">
        <button type="button" data-close-ob class="border px-4 py-2 rounded-lg text-sm">Cancel</button>
        <button type="submit" class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm">Submit</button>
      </div>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include './includes/layout.php';
