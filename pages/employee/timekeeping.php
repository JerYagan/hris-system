<?php
/**
 * Employee Timekeeping
 * DA-ATI HRIS
 */

require_once __DIR__ . '/includes/timekeeping/bootstrap.php';
require_once __DIR__ . '/includes/timekeeping/actions.php';
require_once __DIR__ . '/includes/timekeeping/data.php';

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
        'approved', 'released', 'present' => [ucfirst($key), 'bg-approved text-green-800'],
        'pending', 'submitted', 'late' => [ucfirst($key), 'bg-pending text-yellow-800'],
        'rejected', 'absent' => [ucfirst($key), 'bg-rejected text-red-800'],
        'leave' => ['Leave', 'bg-blue-100 text-blue-700'],
        default => [ucfirst($key !== '' ? $key : 'draft'), 'bg-gray-200 text-gray-700'],
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
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold">Timekeeping</h1>
  <p class="text-sm text-gray-500">Manage your attendance, leave, time adjustment, and overtime requests.</p>
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
      <button data-open-leave class="bg-daGreen text-white px-5 py-2 rounded-lg text-sm font-medium hover:opacity-90">Create Leave Request</button>
      <button data-open-overtime class="border px-5 py-2 rounded-lg text-sm font-medium">File Overtime</button>
      <button data-open-adjustment class="border px-5 py-2 rounded-lg text-sm font-medium">Request Time Adjustment</button>
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
            <?php [$label, $pillClass] = $statusPill((string)($row['attendance_status'] ?? 'present')); ?>
            <tr class="border-b">
              <td class="py-3"><?= $escape($formatDate($row['attendance_date'] ?? '')) ?></td>
              <td class="py-3"><?= $escape($formatTime($row['time_in'] ?? '')) ?></td>
              <td class="py-3"><?= $escape($formatTime($row['time_out'] ?? '')) ?></td>
              <td class="py-3"><?= $escape(number_format((float)($row['hours_worked'] ?? 0), 2)) ?></td>
              <td class="py-3"><span class="px-3 py-1 rounded-full <?= $escape($pillClass) ?>"><?= $escape($label) ?></span></td>
              <td class="py-3">
                <button
                  type="button"
                  data-open-adjustment
                  data-attendance-id="<?= $escape((string)($row['id'] ?? '')) ?>"
                  data-attendance-date="<?= $escape((string)($row['attendance_date'] ?? '')) ?>"
                  data-current-time-in="<?= $escape($formatTime($row['time_in'] ?? '')) ?>"
                  data-current-time-out="<?= $escape($formatTime($row['time_out'] ?? '')) ?>"
                  class="border px-3 py-1 rounded-lg text-xs"
                >Request Adjustment</button>
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

<section class="bg-white rounded-xl shadow p-6 mb-6">
  <h2 class="text-lg font-bold mb-4">Leave <span class="text-daGreen">Balance</span></h2>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b text-gray-500">
          <th class="text-left py-3">Leave Type</th>
          <th class="text-left py-3">Earned</th>
          <th class="text-left py-3">Used</th>
          <th class="text-left py-3">Remaining</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($leaveBalanceRows)): ?>
          <tr><td class="py-3 text-gray-500" colspan="4">No leave balance records found for current year.</td></tr>
        <?php else: ?>
          <?php foreach ($leaveBalanceRows as $balance): ?>
            <tr class="border-b">
              <td class="py-3"><?= $escape((string)($balance['leave_name'] ?? '-')) ?></td>
              <td class="py-3"><?= $escape(number_format((float)($balance['earned_credits'] ?? 0), 2)) ?></td>
              <td class="py-3"><?= $escape(number_format((float)($balance['used_credits'] ?? 0), 2)) ?></td>
              <td class="py-3 font-medium"><?= $escape(number_format((float)($balance['remaining_credits'] ?? 0), 2)) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="bg-white rounded-xl shadow p-6 mb-6">
  <h2 class="text-lg font-bold mb-4">Leave <span class="text-daGreen">Requests</span></h2>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b text-gray-500">
          <th class="text-left py-3">Type</th>
          <th class="text-left py-3">From</th>
          <th class="text-left py-3">To</th>
          <th class="text-left py-3">Days</th>
          <th class="text-left py-3">Reason</th>
          <th class="text-left py-3">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($leaveRequestRows)): ?>
          <tr><td class="py-3 text-gray-500" colspan="6">No leave requests yet.</td></tr>
        <?php else: ?>
          <?php foreach ($leaveRequestRows as $request): ?>
            <?php [$label, $pillClass] = $statusPill((string)($request['status'] ?? 'pending')); ?>
            <tr class="border-b">
              <td class="py-3"><?= $escape((string)($request['leave_name'] ?? 'Leave')) ?></td>
              <td class="py-3"><?= $escape($formatDate($request['date_from'] ?? '')) ?></td>
              <td class="py-3"><?= $escape($formatDate($request['date_to'] ?? '')) ?></td>
              <td class="py-3"><?= $escape(number_format((float)($request['days_count'] ?? 0), 2)) ?></td>
              <td class="py-3"><?= $escape((string)($request['reason'] ?? '')) ?></td>
              <td class="py-3"><span class="px-3 py-1 rounded-full <?= $escape($pillClass) ?>"><?= $escape($label) ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

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
            <tr class="border-b">
              <td class="py-3"><?= $escape($formatDate($adjustment['attendance_date'] ?? '')) ?></td>
              <td class="py-3"><?= $escape($formatDateTime($adjustment['requested_time_in'] ?? '')) ?></td>
              <td class="py-3"><?= $escape($formatDateTime($adjustment['requested_time_out'] ?? '')) ?></td>
              <td class="py-3"><?= $escape((string)($adjustment['reason'] ?? '')) ?></td>
              <td class="py-3"><span class="px-3 py-1 rounded-full <?= $escape($pillClass) ?>"><?= $escape($label) ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="bg-white rounded-xl shadow p-6">
  <h2 class="text-lg font-bold mb-4">Overtime <span class="text-daGreen">Requests</span></h2>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b text-gray-500">
          <th class="text-left py-3">Date</th>
          <th class="text-left py-3">Start</th>
          <th class="text-left py-3">End</th>
          <th class="text-left py-3">Hours</th>
          <th class="text-left py-3">Reason</th>
          <th class="text-left py-3">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($overtimeRows)): ?>
          <tr><td class="py-3 text-gray-500" colspan="6">No overtime requests yet.</td></tr>
        <?php else: ?>
          <?php foreach ($overtimeRows as $overtime): ?>
            <?php [$label, $pillClass] = $statusPill((string)($overtime['status'] ?? 'pending')); ?>
            <tr class="border-b">
              <td class="py-3"><?= $escape($formatDate($overtime['overtime_date'] ?? '')) ?></td>
              <td class="py-3"><?= $escape((string)($overtime['start_time'] ?? '-')) ?></td>
              <td class="py-3"><?= $escape((string)($overtime['end_time'] ?? '-')) ?></td>
              <td class="py-3"><?= $escape(number_format((float)($overtime['hours_requested'] ?? 0), 2)) ?></td>
              <td class="py-3"><?= $escape((string)($overtime['reason'] ?? '')) ?></td>
              <td class="py-3"><span class="px-3 py-1 rounded-full <?= $escape($pillClass) ?>"><?= $escape($label) ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<div id="leaveModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden" aria-hidden="true">
  <div class="bg-white w-full max-w-lg rounded-xl shadow-lg max-h-[90vh] flex flex-col">
    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 class="text-lg font-semibold">Create Leave Request</h2>
      <button type="button" data-close-leave><span class="material-icons">close</span></button>
    </div>

    <form method="post" action="timekeeping.php" class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
      <input type="hidden" name="action" value="create_leave_request">

      <select name="leave_type_id" class="w-full border rounded-lg p-2" required>
        <option value="">Select leave type</option>
        <?php foreach ($leaveTypeOptions as $type): ?>
          <option value="<?= $escape((string)($type['id'] ?? '')) ?>"><?= $escape((string)($type['leave_name'] ?? '')) ?></option>
        <?php endforeach; ?>
      </select>

      <div class="grid grid-cols-2 gap-3">
        <input type="date" name="date_from" class="border rounded-lg p-2" required>
        <input type="date" name="date_to" class="border rounded-lg p-2" required>
      </div>

      <input type="number" step="0.25" min="0.25" name="days_count" class="w-full border rounded-lg p-2" placeholder="Days Count (e.g. 1 or 0.5)" required>
      <textarea name="reason" class="w-full border rounded-lg p-2" rows="3" placeholder="Reason for leave" required></textarea>

      <div class="pt-2 flex justify-end gap-3">
        <button type="button" data-close-leave class="border px-4 py-2 rounded-lg text-sm">Cancel</button>
        <button type="submit" class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm">Submit</button>
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
        <p class="text-xs text-gray-500">Attendance Record</p>
        <p id="adjustmentAttendanceInfo" class="text-sm font-medium">Manual request</p>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-gray-500">Requested Time In</label>
          <input type="datetime-local" name="requested_time_in" class="w-full border rounded-lg p-2 mt-1">
        </div>
        <div>
          <label class="text-gray-500">Requested Time Out</label>
          <input type="datetime-local" name="requested_time_out" class="w-full border rounded-lg p-2 mt-1">
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

<div id="overtimeModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden" aria-hidden="true">
  <div class="bg-white w-full max-w-lg rounded-xl shadow-lg max-h-[90vh] flex flex-col">
    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 class="text-lg font-semibold">Overtime Request</h2>
      <button type="button" data-close-overtime><span class="material-icons">close</span></button>
    </div>

    <form method="post" action="timekeeping.php" class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
      <input type="hidden" name="action" value="create_overtime_request">

      <input type="date" name="overtime_date" class="w-full border rounded-lg p-2" required>
      <div class="grid grid-cols-2 gap-3">
        <input type="time" name="start_time" class="border rounded-lg p-2" required>
        <input type="time" name="end_time" class="border rounded-lg p-2" required>
      </div>
      <input type="number" name="hours_requested" step="0.25" min="0.25" max="24" class="w-full border rounded-lg p-2" placeholder="Overtime Hours" required>
      <textarea name="reason" class="w-full border rounded-lg p-2" rows="3" placeholder="Reason" required></textarea>

      <div class="pt-2 flex justify-end gap-3">
        <button type="button" data-close-overtime class="border px-4 py-2 rounded-lg text-sm">Cancel</button>
        <button type="submit" class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm">Submit</button>
      </div>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include './includes/layout.php';
