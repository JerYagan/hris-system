<?php
require_once __DIR__ . '/includes/personal-reports/bootstrap.php';
require_once __DIR__ . '/includes/personal-reports/actions.php';
require_once __DIR__ . '/includes/personal-reports/data.php';

$pageTitle = 'Personal Reports | DA HRIS';
$activePage = 'personal-reports.php';
$breadcrumbs = ['Personal Reports'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/employee/personal-reports/index.js';

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

$formatCurrency = static function (float $value): string {
    return '₱ ' . number_format($value, 2);
};

$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'ready' => ['Ready', 'bg-approved text-green-800'],
        'processing', 'queued' => [ucfirst($key), 'bg-pending text-yellow-800'],
        'failed' => ['Failed', 'bg-rejected text-red-800'],
        default => [ucfirst($key !== '' ? $key : 'queued'), 'bg-gray-200 text-gray-700'],
    };
};
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold">Personal Reports</h1>
  <p class="text-sm text-gray-500">View your generated reports and request new exports.</p>
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

<div class="bg-white border rounded-lg p-4 mb-6">
  <form method="get" action="personal-reports.php" class="flex flex-wrap gap-4 items-end text-sm">
    <div>
      <label class="block text-gray-600 mb-1">From</label>
      <input name="date_from" value="<?= $escape((string)($reportFilters['date_from'] ?? '')) ?>" type="date" class="border rounded-lg px-3 py-2">
    </div>

    <div>
      <label class="block text-gray-600 mb-1">To</label>
      <input name="date_to" value="<?= $escape((string)($reportFilters['date_to'] ?? '')) ?>" type="date" class="border rounded-lg px-3 py-2">
    </div>

    <div>
      <label class="block text-gray-600 mb-1">Report Type</label>
      <select name="report_type" class="border rounded-lg px-3 py-2">
        <option value="">All Reports</option>
        <?php foreach (['attendance', 'payroll', 'performance', 'documents'] as $type): ?>
          <option value="<?= $escape($type) ?>" <?= (($reportFilters['report_type'] ?? '') === $type) ? 'selected' : '' ?>><?= $escape(ucfirst($type)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="block text-gray-600 mb-1">Status</label>
      <select name="status" class="border rounded-lg px-3 py-2">
        <option value="">All Status</option>
        <?php foreach (['queued', 'processing', 'ready', 'failed'] as $status): ?>
          <option value="<?= $escape($status) ?>" <?= (($reportFilters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= $escape(ucfirst($status)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="block text-gray-600 mb-1">Evaluation Quarter</label>
      <select name="evaluation_quarter" class="border rounded-lg px-3 py-2">
        <option value="">All Quarters</option>
        <option value="q1" <?= (($reportFilters['evaluation_quarter'] ?? '') === 'q1') ? 'selected' : '' ?>>Q1</option>
        <option value="q2" <?= (($reportFilters['evaluation_quarter'] ?? '') === 'q2') ? 'selected' : '' ?>>Q2</option>
        <option value="q3" <?= (($reportFilters['evaluation_quarter'] ?? '') === 'q3') ? 'selected' : '' ?>>Q3</option>
        <option value="q4" <?= (($reportFilters['evaluation_quarter'] ?? '') === 'q4') ? 'selected' : '' ?>>Q4</option>
      </select>
    </div>

    <button type="submit" class="bg-daGreen text-white px-4 py-2 rounded-lg inline-flex items-center gap-1.5"><span class="material-icons text-sm">filter_alt</span>Apply Filters</button>
    <button type="button" data-open-request-report class="border px-4 py-2 rounded-lg inline-flex items-center gap-1.5"><span class="material-icons text-sm">description</span>Request Report</button>
  </form>
</div>

<section class="mb-8">
  <div class="flex justify-between items-center mb-3">
    <div>
      <h2 class="text-lg font-semibold">Generated Reports</h2>
      <p class="text-sm text-gray-500">History and status of your requested report exports.</p>
    </div>
  </div>

  <div class="bg-white border rounded-lg overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-4 py-3 text-left">Report Type</th>
          <th class="px-4 py-3 text-left">Format</th>
          <th class="px-4 py-3 text-left">Created</th>
          <th class="px-4 py-3 text-left">Generated</th>
          <th class="px-4 py-3 text-left">Status</th>
          <th class="px-4 py-3 text-left">Output</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php if (empty($generatedReportRows)): ?>
          <tr><td class="px-4 py-3 text-gray-500" colspan="6">No generated report requests yet.</td></tr>
        <?php else: ?>
          <?php foreach ($generatedReportRows as $report): ?>
            <?php [$statusLabel, $statusClass] = $statusPill((string)($report['status'] ?? 'queued')); ?>
            <tr>
              <td class="px-4 py-3"><?= $escape(ucfirst((string)($report['report_type'] ?? 'report'))) ?></td>
              <td class="px-4 py-3 uppercase"><?= $escape((string)($report['file_format'] ?? 'pdf')) ?></td>
              <td class="px-4 py-3"><?= $escape($formatDate($report['created_at'] ?? null)) ?></td>
              <td class="px-4 py-3"><?= $escape($formatDate($report['generated_at'] ?? null)) ?></td>
              <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= $escape($statusClass) ?>"><?= $escape($statusLabel) ?></span></td>
              <td class="px-4 py-3">
                <?php if (!empty($report['storage_path'])): ?>
                  <span class="text-xs text-gray-600"><?= $escape((string)$report['storage_path']) ?></span>
                <?php else: ?>
                  <span class="text-xs text-gray-400">Pending output</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="mb-10">
  <div class="flex justify-between items-center mb-3">
    <div>
      <h2 class="text-lg font-semibold">Attendance Summary Report</h2>
      <p class="text-sm text-gray-500">Monthly summary of attendance, absences, and late records.</p>
    </div>
  </div>

  <div class="bg-white border rounded-lg overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-4 py-3 text-left">Month</th>
          <th class="px-4 py-3 text-left">Days Present</th>
          <th class="px-4 py-3 text-left">Late</th>
          <th class="px-4 py-3 text-left">Absent</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php if (empty($attendanceSummaryRows)): ?>
          <tr><td class="px-4 py-3 text-gray-500" colspan="4">No attendance summary rows available.</td></tr>
        <?php else: ?>
          <?php foreach ($attendanceSummaryRows as $row): ?>
            <tr>
              <td class="px-4 py-3"><?= $escape((string)($row['month_label'] ?? '-')) ?></td>
              <td class="px-4 py-3"><?= $escape((string)($row['present'] ?? 0)) ?></td>
              <td class="px-4 py-3"><?= $escape((string)($row['late'] ?? 0)) ?></td>
              <td class="px-4 py-3"><?= $escape((string)($row['absent'] ?? 0)) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="mb-10">
  <div class="flex justify-between items-center mb-3">
    <div>
      <h2 class="text-lg font-semibold">Payroll History Report</h2>
      <p class="text-sm text-gray-500">Record of payroll periods and pay values.</p>
    </div>
  </div>

  <div class="bg-white border rounded-lg overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-4 py-3 text-left">Pay Period</th>
          <th class="px-4 py-3 text-left">Gross Pay</th>
          <th class="px-4 py-3 text-left">Deductions</th>
          <th class="px-4 py-3 text-left">Net Pay</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php if (empty($payrollSummaryRows)): ?>
          <tr><td class="px-4 py-3 text-gray-500" colspan="4">No payroll history rows available.</td></tr>
        <?php else: ?>
          <?php foreach ($payrollSummaryRows as $row): ?>
            <tr>
              <td class="px-4 py-3"><?= $escape((string)($row['period_label'] ?? '-')) ?></td>
              <td class="px-4 py-3"><?= $escape($formatCurrency((float)($row['gross_pay'] ?? 0))) ?></td>
              <td class="px-4 py-3"><?= $escape($formatCurrency((float)($row['deductions_total'] ?? 0))) ?></td>
              <td class="px-4 py-3 font-medium"><?= $escape($formatCurrency((float)($row['net_pay'] ?? 0))) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section>
  <div class="flex justify-between items-center mb-3">
    <div>
      <h2 class="text-lg font-semibold">Performance Summary Report</h2>
      <p class="text-sm text-gray-500">PRAISE evaluations and performance ratings.</p>
    </div>
  </div>

  <div class="bg-white border rounded-lg overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-4 py-3 text-left">Quarter</th>
          <th class="px-4 py-3 text-left">Evaluation Period</th>
          <th class="px-4 py-3 text-left">Cycle</th>
          <th class="px-4 py-3 text-left">Rating</th>
          <th class="px-4 py-3 text-left">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php if (empty($performanceSummaryRows)): ?>
          <tr><td class="px-4 py-3 text-gray-500" colspan="5">No performance summary rows available.</td></tr>
        <?php else: ?>
          <?php foreach ($performanceSummaryRows as $row): ?>
            <?php [$statusLabel, $statusClass] = $statusPill((string)($row['status'] ?? 'draft')); ?>
            <tr>
              <td class="px-4 py-3"><?= $escape((string)($row['quarter_label'] ?? '-')) ?></td>
              <td class="px-4 py-3"><?= $escape((string)($row['period_label'] ?? '-')) ?></td>
              <td class="px-4 py-3"><?= $escape((string)($row['cycle_name'] ?? '-')) ?></td>
              <td class="px-4 py-3"><?= $escape((string)($row['final_rating'] ?? '-')) ?></td>
              <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= $escape($statusClass) ?>"><?= $escape($statusLabel) ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<div id="requestReportModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden" aria-hidden="true">
  <div class="bg-white w-full max-w-lg rounded-xl shadow-lg max-h-[90vh] flex flex-col">
    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 class="text-lg font-semibold">Request Report Export</h2>
      <button type="button" data-close-request-report><span class="material-icons">close</span></button>
    </div>

    <form method="post" action="personal-reports.php" class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
      <input type="hidden" name="action" value="request_report">

      <div>
        <label class="text-gray-600">Report Type</label>
        <select name="report_type" class="w-full mt-1 border rounded-lg p-2" required>
          <option value="attendance">Attendance</option>
          <option value="payroll">Payroll</option>
          <option value="performance">Performance</option>
          <option value="documents">Documents</option>
        </select>
      </div>

      <div>
        <label class="text-gray-600">File Format</label>
        <select name="file_format" class="w-full mt-1 border rounded-lg p-2" required>
          <option value="pdf">PDF</option>
          <option value="csv">CSV</option>
          <option value="xlsx">XLSX</option>
        </select>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-gray-600">Date From</label>
          <input type="date" name="date_from" class="w-full mt-1 border rounded-lg p-2">
        </div>
        <div>
          <label class="text-gray-600">Date To</label>
          <input type="date" name="date_to" class="w-full mt-1 border rounded-lg p-2">
        </div>
      </div>

      <div>
        <label class="text-gray-600">Evaluation Quarter (Optional)</label>
        <select name="evaluation_quarter" class="w-full mt-1 border rounded-lg p-2">
          <option value="">All Quarters</option>
          <option value="q1">Q1</option>
          <option value="q2">Q2</option>
          <option value="q3">Q3</option>
          <option value="q4">Q4</option>
        </select>
      </div>

      <div class="pt-2 flex justify-end gap-3">
        <button type="button" data-close-request-report class="border px-4 py-2 rounded-lg text-sm inline-flex items-center gap-1.5"><span class="material-icons text-sm">close</span>Cancel</button>
        <button type="submit" class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm inline-flex items-center gap-1.5"><span class="material-icons text-sm">send</span>Queue Report</button>
      </div>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include './includes/layout.php';
