<?php
/**
 * Employee Timekeeping
 * DA-ATI HRIS
 */

require_once __DIR__ . '/includes/timekeeping/bootstrap.php';
require_once __DIR__ . '/includes/timekeeping/actions.php';
require_once __DIR__ . '/includes/timekeeping/data.php';
require_once __DIR__ . '/../shared/lib/rfid-attendance.php';

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
  'travel' => ['Approved Travel', 'bg-indigo-50 text-indigo-700'],
  'needs_revision' => ['Needs Revision', 'bg-blue-50 text-blue-700'],
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
$rfidSimulatorEnabled = rfidSimulatorEnabled($supabaseUrl, $headers);
$attendanceExportTo = $attendanceTo ?? date('Y-m-t');
$attendanceExportYear = $attendanceTo !== null ? date('Y', strtotime($attendanceTo)) : date('Y');
$attendanceYearlyViewUrl = 'export/attendance.php?' . http_build_query([
  'report_scope' => 'yearly',
  'year' => $attendanceExportYear,
  'disposition' => 'inline',
]);
$attendanceYearlyDownloadUrl = 'export/attendance.php?' . http_build_query([
  'report_scope' => 'yearly',
  'year' => $attendanceExportYear,
  'disposition' => 'attachment',
]);
$ctoExpiryBucketRows = $ctoExpiryBucketRows ?? [];
$displayLeaveBalanceRows = $employeeIsCos
    ? array_values(array_filter($leaveBalanceRows, static function (array $row): bool {
        $leaveCode = strtolower(trim((string)($row['leave_code'] ?? '')));
        $leaveName = strtolower(trim((string)($row['leave_name'] ?? '')));
        return $leaveCode === 'cto' || str_contains($leaveName, 'cto') || str_contains($leaveName, 'compensatory');
    }))
    : $leaveBalanceRows;

$renderLeaveBalanceSection = static function () use (
    $escape,
    $employeeIsCos,
    $formatDateTime,
    $ctoExpiryBucketRows,
    $displayLeaveBalanceRows,
    $leaveBalanceLastUpdatedAt,
    $leaveBalanceRefreshUrl,
    $leavePointSummary,
    $pendingLeavePointSummary,
    $postedLeavePointSummary
): void {
?>
<section id="leave-balance" data-refresh-url="<?= $escape($leaveBalanceRefreshUrl ?? 'timekeeping.php?partial=leave-balance') ?>" class="bg-white rounded-xl shadow p-6 mb-6">
  <div class="flex flex-col gap-2 mb-4 md:flex-row md:items-start md:justify-between">
    <div>
      <h2 class="text-lg font-bold"><?= $employeeIsCos ? 'CTO Expiry and Payroll Policy' : 'Accumulated Leave/CTO <span class="text-daGreen">Points</span>' ?></h2>
      <p class="text-xs text-gray-500 mt-1">
        <?= $employeeIsCos
            ? 'COS employees do not maintain leave cards. This section shows compensatory time-off tracking and expiry buckets only.'
            : 'This section shows only the total accumulated SL, VL, and CTO points based on what Admin inserted for your record.' ?>
      </p>
    </div>
    <div class="text-xs text-gray-500 md:text-right">
      <p class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Live sync enabled</p>
      <p class="mt-2">Admin balance last updated: <?= $escape($formatDateTime($leaveBalanceLastUpdatedAt)) ?></p>
      <p class="mt-1">Section refreshes automatically every 60 seconds.</p>
    </div>
  </div>

  <?php if ($employeeIsCos): ?>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm mb-4">
      <div class="rounded-lg bg-amber-50 px-4 py-3 border border-amber-100 sm:col-span-1">
        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">CTO Points</p>
        <p class="mt-1 text-xl font-bold text-amber-800"><?= $escape(number_format((float)($leavePointSummary['cto'] ?? 0), 2)) ?></p>
      </div>
      <div class="rounded-lg bg-slate-50 px-4 py-3 border border-slate-200 sm:col-span-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-700">COS Payroll Rule</p>
        <p class="mt-1 text-sm text-slate-700">Late, absence, and undertime impact is tracked for payroll integration instead of leave-card deduction.</p>
      </div>
    </div>
  <?php else: ?>
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
  <?php endif; ?>

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
        <?php if (empty($displayLeaveBalanceRows)): ?>
          <tr><td class="py-3 text-gray-500" colspan="3">No accumulated leave point records found yet.</td></tr>
        <?php else: ?>
          <?php foreach ($displayLeaveBalanceRows as $balance): ?>
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

  <div class="mt-6">
    <div>
      <h3 class="text-base font-semibold text-slate-800">CTO Expiry Tracking</h3>
      <p class="mt-1 text-xs text-slate-500">Credits are grouped by earning half-year so expiring CTO can be monitored by <span class="font-medium">JAN-JUN</span> and <span class="font-medium">JULY-DEC</span> buckets.</p>
    </div>

    <?php if (empty($ctoExpiryBucketRows)): ?>
      <p class="mt-3 text-sm text-slate-500">No CTO expiry buckets are available yet.</p>
    <?php else: ?>
      <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($ctoExpiryBucketRows as $bucket): ?>
          <article class="rounded-xl border border-amber-100 bg-amber-50/60 px-4 py-4 text-sm">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700"><?= $escape((string)($bucket['display_label'] ?? 'CTO Bucket')) ?></p>
                <p class="mt-1 text-lg font-bold text-slate-800"><?= $escape(number_format((float)($bucket['remaining_points'] ?? 0), 2)) ?></p>
                <p class="text-xs text-slate-500">Remaining points</p>
              </div>
              <span class="inline-flex rounded-full bg-white px-2.5 py-1 text-[11px] font-medium text-amber-700 border border-amber-200"><?= $escape((string)($bucket['bucket_label'] ?? 'CTO')) ?></span>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-xs text-slate-600">
              <div>
                <dt class="uppercase tracking-wide text-slate-500">Posted</dt>
                <dd class="mt-1 font-semibold text-slate-800"><?= $escape(number_format((float)($bucket['posted_points'] ?? 0), 2)) ?></dd>
              </div>
              <div>
                <dt class="uppercase tracking-wide text-slate-500">Used</dt>
                <dd class="mt-1 font-semibold text-slate-800"><?= $escape(number_format((float)($bucket['used_points'] ?? 0), 2)) ?></dd>
              </div>
              <div>
                <dt class="uppercase tracking-wide text-slate-500">Pending</dt>
                <dd class="mt-1 font-semibold text-slate-800"><?= $escape(number_format((float)($bucket['pending_points'] ?? 0), 2)) ?></dd>
              </div>
              <div>
                <dt class="uppercase tracking-wide text-slate-500">Projected</dt>
                <dd class="mt-1 font-semibold text-slate-800"><?= $escape(number_format((float)($bucket['projected_remaining'] ?? 0), 2)) ?></dd>
              </div>
            </dl>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
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
  <p class="text-sm text-gray-500"><?= $employeeIsCos
      ? 'Manage your attendance, track CTO expiry, and submit official business, COS flexible schedule, travel, or record-level time adjustment requests.'
  : 'Manage your attendance, review synced leave and CTO balances, and submit official business, travel, or record-level time adjustment requests.' ?></p>
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
      <button data-open-special-request data-request-type="official_business" class="inline-flex items-center gap-2 border px-5 py-2 rounded-lg text-sm font-medium"><span class="material-symbols-outlined text-base">work_history</span>File Official Business</button>
      <?php if ($employeeIsCos): ?>
        <button data-open-special-request data-request-type="cos_schedule" class="inline-flex items-center gap-2 border px-5 py-2 rounded-lg text-sm font-medium"><span class="material-symbols-outlined text-base">schedule</span>Request COS Schedule</button>
      <?php endif; ?>
      <button data-open-special-request data-request-type="travel_order" class="inline-flex items-center gap-2 border px-5 py-2 rounded-lg text-sm font-medium"><span class="material-symbols-outlined text-base">flight_takeoff</span>File Travel Order</button>
      <button data-open-special-request data-request-type="travel_abroad" class="inline-flex items-center gap-2 border px-5 py-2 rounded-lg text-sm font-medium"><span class="material-symbols-outlined text-base">public</span>File Travel Abroad</button>
    </div>
  </div>

  <div class="mb-4 grid gap-3 md:grid-cols-2">
    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
      <p class="font-semibold text-slate-800">Flexible schedule policy</p>
      <p class="mt-1 text-xs text-slate-600">Permanent flexi windows remain separate. COS flexible schedule requests are reviewed case by case, and approved COS requests may extend up to 10:00 PM.</p>
    </div>
    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
      <p class="font-semibold text-slate-800"><?= $employeeIsCos ? 'COS attendance and payroll policy' : 'Official attendance and leave record policy' ?></p>
      <p class="mt-1 text-xs text-slate-600"><?= $employeeIsCos
          ? 'COS late, absence, and undertime impact are reflected in payroll integration rules. Leave-card actions stay hidden because COS employees do not maintain leave cards.'
          : 'The external live-synced leave card remains the official source record. This page keeps the internal synced balance view, yearly attendance reports, and request shortcuts available in one place.' ?></p>
    </div>
  </div>

  <div class="mb-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4 text-sm">
    <a href="<?= $escape($attendanceYearlyViewUrl) ?>" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-200 bg-white px-4 py-3 hover:bg-slate-50">
      <p class="font-semibold text-slate-800">Yearly Attendance Report</p>
      <p class="mt-1 text-xs text-slate-500">View the yearly PDF with employee acknowledgment and HR Head signature sections.</p>
    </a>
    <a href="<?= $escape($attendanceYearlyDownloadUrl) ?>" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-200 bg-white px-4 py-3 hover:bg-slate-50">
      <p class="font-semibold text-slate-800">Download Yearly Attendance</p>
      <p class="mt-1 text-xs text-slate-500">Download the signed-yearly attendance layout for the selected year.</p>
    </a>
    <a href="<?= $escape($officialBusinessTemplateUrl) ?>" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-200 bg-white px-4 py-3 hover:bg-slate-50">
      <p class="font-semibold text-slate-800">Official Business Report</p>
      <p class="mt-1 text-xs text-slate-500">Open the approved editable template in a new tab for viewing or browser-based editing.</p>
    </a>
    <a href="<?= $escape($officialBusinessTemplateUrl) ?>" download class="rounded-lg border border-slate-200 bg-white px-4 py-3 hover:bg-slate-50">
      <p class="font-semibold text-slate-800">Download OB Template</p>
      <p class="mt-1 text-xs text-slate-500">Download a blank Official Business Report template.</p>
    </a>
    <a href="<?= $escape($applicationForLeaveTemplateUrl) ?>" download class="rounded-lg border border-slate-200 bg-white px-4 py-3 hover:bg-slate-50">
      <p class="font-semibold text-slate-800">Download Leave Form</p>
      <p class="mt-1 text-xs text-slate-500">Download the Application for Leave template.</p>
    </a>
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
    <div class="flex items-center gap-2">
      <?php if ($rfidSimulatorEnabled): ?>
        <a href="rfid-simulator.php" class="inline-flex items-center gap-2 border px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50"><span class="material-symbols-outlined text-base">contactless</span>RFID Simulator</a>
      <?php endif; ?>
      <button type="button" data-open-attendance-export class="inline-flex items-center gap-2 border px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50"><span class="material-symbols-outlined text-base">picture_as_pdf</span>View / Export PDF</button>
    </div>
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
        <?php foreach (['present', 'late', 'absent', 'leave', 'travel', 'holiday', 'rest_day'] as $statusOption): ?>
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
                <?php if (($row['display_status'] ?? '') === 'travel' && !empty($row['travel_label'])): ?>
                  <p class="text-[11px] text-indigo-700 mt-1"><?= $escape((string)$row['travel_label']) ?></p>
                <?php endif; ?>
                <?php if (!empty($row['source'])): ?>
                  <p class="text-[11px] text-slate-500 mt-1">Source: <?= $escape(ucwords(str_replace('_', ' ', (string)$row['source']))) ?></p>
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

<?php if ($employeeIsCos): ?>
<section class="bg-white rounded-xl shadow p-6 mb-6">
  <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
      <h2 class="text-lg font-bold">COS Weekly <span class="text-daGreen">Schedule Proposal</span></h2>
      <p class="mt-1 text-sm text-gray-500">Submit your proposed weekly COS schedule here. You can include late shifts up to 10:00 PM, and staff will endorse the request before admin final approval.</p>
    </div>
    <button data-open-special-request data-request-type="cos_schedule" class="inline-flex items-center gap-2 border px-5 py-2 rounded-lg text-sm font-medium self-start"><span class="material-symbols-outlined text-base">schedule</span>Propose Weekly COS Schedule</button>
  </div>
  <div class="mt-4 grid gap-3 md:grid-cols-2">
    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
      <p class="font-semibold text-slate-800">Approval Path</p>
      <p class="mt-1 text-xs text-slate-600">COS schedule proposals are submitted by the employee, reviewed by staff for operational fit, then approved, rejected, or returned for changes by admin.</p>
    </div>
    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
      <p class="font-semibold text-slate-800">COS Rules</p>
      <p class="mt-1 text-xs text-slate-600">COS employees do not use the regular leave-card model. Attendance stays flexible, while Official Business, Travel Order, and Travel Abroad requests remain available when needed.</p>
    </div>
  </div>
</section>
<?php endif; ?>

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
  <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
    <div>
      <h2 class="text-lg font-bold">Special <span class="text-daGreen">Timekeeping Requests</span></h2>
      <p class="text-sm text-gray-500 mt-1">Track Official Business, COS flexible schedule, Travel Order, and Travel Abroad requests with notes and approval timeline.</p>
    </div>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b text-gray-500">
          <th class="text-left py-3">Request Type</th>
          <th class="text-left py-3">Date</th>
          <th class="text-left py-3">Start</th>
          <th class="text-left py-3">End</th>
          <th class="text-left py-3">Hours</th>
          <th class="text-left py-3">Details</th>
          <th class="text-left py-3">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($specialTimekeepingRows)): ?>
          <tr><td class="py-3 text-gray-500" colspan="7">No special timekeeping requests yet.</td></tr>
        <?php else: ?>
          <?php foreach ($specialTimekeepingRows as $request): ?>
            <?php [$label, $pillClass] = $statusPill((string)($request['status'] ?? 'pending')); ?>
            <tr class="border-b js-module-filter-row" data-source="ob" data-status="<?= $escape(strtolower((string)($request['status'] ?? 'pending'))) ?>" data-date="<?= $escape((string)($request['overtime_date'] ?? '')) ?>" data-search="<?= $escape(strtolower(trim(($request['request_label'] ?? '') . ' ' . ($request['overtime_date'] ?? '') . ' ' . ($request['reason'] ?? '') . ' ' . ($request['detail_summary'] ?? '') . ' ' . ($request['timeline_summary'] ?? '') . ' ' . ($request['status'] ?? '') . ' ' . ($request['start_time'] ?? '') . ' ' . ($request['end_time'] ?? '')))) ?>">
              <td class="py-3 font-medium text-gray-800"><?= $escape((string)($request['request_label'] ?? 'Special Request')) ?></td>
              <td class="py-3"><?= $escape($formatDate($request['overtime_date'] ?? '')) ?></td>
              <td class="py-3"><?= $escape((string)($request['start_time'] ?? '-')) ?></td>
              <td class="py-3"><?= $escape((string)($request['end_time'] ?? '-')) ?></td>
              <td class="py-3"><?= $escape(number_format((float)($request['hours_requested'] ?? 0), 2)) ?></td>
              <td class="py-3">
                <p><?= $escape((string)($request['reason'] ?? '')) ?></p>
                <?php if (!empty($request['detail_summary'])): ?>
                  <p class="mt-1 text-[11px] text-slate-500"><?= $escape((string)$request['detail_summary']) ?></p>
                <?php endif; ?>
                <?php if (!empty($request['timeline_summary'])): ?>
                  <p class="mt-1 text-[11px] text-slate-500"><?= $escape((string)$request['timeline_summary']) ?></p>
                <?php endif; ?>
                <?php if (!empty($request['attachment_url']) && !empty($request['attachment_name'])): ?>
                  <a href="<?= $escape((string)$request['attachment_url']) ?>" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex items-center gap-1 text-[11px] font-medium text-daGreen hover:underline">
                    <span class="material-symbols-outlined text-sm">attach_file</span><?= $escape((string)$request['attachment_name']) ?>
                  </a>
                <?php endif; ?>
              </td>
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
      <h2 class="text-lg font-semibold">Attendance Report PDF</h2>
      <button type="button" data-close-attendance-export><span class="material-icons">close</span></button>
    </div>

    <form method="get" action="export/attendance.php" target="_blank" class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <div>
        <label class="text-gray-500">Report Scope</label>
        <select name="report_scope" class="w-full mt-1 border rounded-lg p-2">
          <option value="date_range">Date Range Attendance Report</option>
          <option value="yearly">Yearly Attendance Report</option>
        </select>
      </div>
      <div>
        <label class="text-gray-500">From Date</label>
        <input type="date" name="from" value="<?= $escape((string)$attendanceExportFrom) ?>" class="w-full mt-1 border rounded-lg p-2" required>
      </div>
      <div>
        <label class="text-gray-500">To Date</label>
        <input type="date" name="to" value="<?= $escape((string)$attendanceExportTo) ?>" class="w-full mt-1 border rounded-lg p-2" required>
      </div>
      <div>
        <label class="text-gray-500">Year</label>
        <input type="number" name="year" min="2000" max="2100" value="<?= $escape((string)$attendanceExportYear) ?>" class="w-full mt-1 border rounded-lg p-2">
      </div>
      <p class="text-xs text-gray-500">Date range mode exports the selected period. Yearly mode generates January to December for the chosen year and adds both employee acknowledgment and HR Head signature areas.</p>

      <div class="pt-2 flex justify-end gap-3">
        <button type="button" data-close-attendance-export class="border px-4 py-2 rounded-lg text-sm">Cancel</button>
        <button type="submit" name="disposition" value="inline" class="border border-daGreen text-daGreen px-4 py-2 rounded-lg text-sm">View PDF</button>
        <button type="submit" name="disposition" value="attachment" class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm">Download PDF</button>
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

<div id="specialRequestModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden" aria-hidden="true">
  <div class="bg-white w-full max-w-lg rounded-xl shadow-lg max-h-[90vh] flex flex-col">
    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 id="specialRequestModalTitle" class="text-lg font-semibold">Special Timekeeping Request</h2>
      <button type="button" data-close-special-request><span class="material-icons">close</span></button>
    </div>

    <form method="post" action="timekeeping.php" enctype="multipart/form-data" class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
      <input type="hidden" id="specialRequestAction" name="action" value="create_official_business_request">

      <div>
        <label class="text-gray-500">Request Type</label>
        <select id="specialRequestType" class="w-full mt-1 border rounded-lg p-2 bg-gray-50" disabled>
          <option value="official_business">Official Business</option>
          <?php if ($employeeIsCos): ?>
            <option value="cos_schedule">COS Flexible Schedule</option>
          <?php endif; ?>
          <option value="travel_order">Travel Order</option>
          <option value="travel_abroad">Travel Abroad</option>
        </select>
      </div>

      <div>
        <label id="specialRequestDateLabel" class="text-gray-500">Request Date</label>
        <input type="date" name="request_date" min="<?= $escape($todayManila) ?>" class="w-full mt-1 border rounded-lg p-2" required>
      </div>
      <div id="specialRequestTimeGrid" class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-gray-500">Start Time</label>
          <input type="time" name="start_time" class="mt-1 w-full border rounded-lg p-2" required>
        </div>
        <div>
          <label class="text-gray-500">End Time</label>
          <input type="time" name="end_time" class="mt-1 w-full border rounded-lg p-2" required>
        </div>
      </div>
      <div>
        <label class="text-gray-500">Hours Requested</label>
        <input type="number" name="hours_requested" step="0.25" min="0.25" max="24" class="w-full mt-1 border rounded-lg p-2" placeholder="Total hours" required>
      </div>
      <div id="specialCosWeeklyField" class="hidden rounded-lg border border-slate-200 bg-slate-50 p-4">
        <div>
          <p class="font-semibold text-slate-800">Weekly COS Schedule</p>
          <p class="mt-1 text-[11px] text-slate-500">Select the days to propose for the week. Each selected day must end no later than 10:00 PM.</p>
        </div>
        <div class="mt-4 overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b text-slate-500">
                <th class="py-2 text-left">Use</th>
                <th class="py-2 text-left">Day</th>
                <th class="py-2 text-left">Start</th>
                <th class="py-2 text-left">End</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (timekeepingCosWeeklyDayCatalog() as $dayKey => $dayLabel): ?>
                <tr class="border-b last:border-b-0">
                  <td class="py-2 pr-2">
                    <input type="hidden" name="weekly_schedule_day[]" value="<?= $escape($dayKey) ?>">
                    <input type="checkbox" name="weekly_schedule_enabled[]" value="<?= $escape($dayKey) ?>" class="rounded border-slate-300 text-daGreen focus:ring-daGreen">
                  </td>
                  <td class="py-2 pr-2 font-medium text-slate-700"><?= $escape($dayLabel) ?></td>
                  <td class="py-2 pr-2"><input type="time" name="weekly_schedule_start[]" class="w-full rounded-lg border p-2"></td>
                  <td class="py-2"><input type="time" name="weekly_schedule_end[]" class="w-full rounded-lg border p-2"></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div id="specialDestinationField" class="hidden">
        <label id="specialDestinationLabel" class="text-gray-500">Destination / Coverage</label>
        <input type="text" name="destination" class="w-full mt-1 border rounded-lg p-2" maxlength="255" placeholder="Destination, office, or coverage area">
      </div>
      <div id="specialReferenceField" class="hidden">
        <label class="text-gray-500">Reference Number</label>
        <input type="text" name="reference_number" class="w-full mt-1 border rounded-lg p-2" maxlength="120" placeholder="Travel order number or memorandum reference">
      </div>
      <div id="specialAttachmentField" class="hidden">
        <label id="specialAttachmentLabel" class="text-gray-500">Supporting Attachment</label>
        <input id="specialAttachmentInput" type="file" name="supporting_attachment" class="w-full mt-1 border rounded-lg p-2" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
        <p class="mt-1 text-[11px] text-slate-500">Accepted: PDF, JPG, PNG, DOC, DOCX up to 10MB.</p>
      </div>
      <textarea id="specialRequestReason" name="reason" class="w-full border rounded-lg p-2" rows="3" placeholder="Reason / justification" required></textarea>
      <p id="specialRequestHelpText" class="text-[11px] text-slate-500">Provide a clear justification so staff and admin can review the request quickly.</p>

      <div class="pt-2 flex justify-end gap-3">
        <button type="button" data-close-special-request class="border px-4 py-2 rounded-lg text-sm">Cancel</button>
        <button type="submit" class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm">Submit</button>
      </div>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include './includes/layout.php';
