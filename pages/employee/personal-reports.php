<?php
require_once __DIR__ . '/includes/personal-reports/bootstrap.php';
require_once __DIR__ . '/includes/personal-reports/actions.php';
require_once __DIR__ . '/includes/personal-reports/data.php';

$pageTitle = 'My reports | DA HRIS';
$activePage = 'personal-reports.php';
$breadcrumbs = ['My reports'];
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

$attendanceExportFrom = (string)($reportFilters['date_from'] ?? date('Y-01-01'));
$attendanceExportTo = (string)($reportFilters['date_to'] ?? date('Y-m-d'));
$attendanceReportYear = (string)date('Y', strtotime($attendanceExportTo !== '' ? $attendanceExportTo : date('Y-m-d')));
$attendanceReportDownloadUrl = 'export/attendance.php?' . http_build_query([
  'from' => $attendanceExportFrom,
  'to' => $attendanceExportTo,
]);
$attendanceYearlyViewUrl = 'export/attendance.php?' . http_build_query([
  'report_scope' => 'yearly',
  'year' => $attendanceReportYear,
  'disposition' => 'inline',
]);
$attendanceYearlyDownloadUrl = 'export/attendance.php?' . http_build_query([
  'report_scope' => 'yearly',
  'year' => $attendanceReportYear,
  'disposition' => 'attachment',
]);
$templateLinkDefaults = [
  'official_business_report_template_url' => 'https://docs.google.com/document/d/1oF-k_14HArDNj3YxyIEOAQQwO2lTNUcy/edit',
  'application_for_leave_template_url' => 'https://docs.google.com/spreadsheets/d/1jEz7xOB82ndjYqf0teL7DUU0gePZlEjx/edit?gid=419957008#gid=419957008',
];
$templateLinkSettings = systemSettingLinksMap(
    $supabaseUrl,
    $headers,
    ['official_business_report_template_url', 'application_for_leave_template_url']
);
$officialBusinessTemplateUrl = (string)($templateLinkSettings['official_business_report_template_url'] ?? $templateLinkDefaults['official_business_report_template_url']);
$applicationForLeaveTemplateUrl = (string)($templateLinkSettings['application_for_leave_template_url'] ?? $templateLinkDefaults['application_for_leave_template_url']);

$payrollExportParams = [];
if (!empty($reportFilters['date_from'])) {
  $payrollExportParams['from'] = (string)$reportFilters['date_from'];
}
if (!empty($reportFilters['date_to'])) {
  $payrollExportParams['to'] = (string)$reportFilters['date_to'];
}
$payrollReportDownloadUrl = 'export/payroll.php' . (!empty($payrollExportParams) ? ('?' . http_build_query($payrollExportParams)) : '');
$documentsExportParams = [];
if (!empty($reportFilters['status'])) {
  $documentsExportParams['status'] = (string)$reportFilters['status'];
}
$documentsReportDownloadUrl = 'export/documents.php' . (!empty($documentsExportParams) ? ('?' . http_build_query($documentsExportParams)) : '');

$generatedReportTypeLabel = static function (string $value): string {
    return match (strtolower(trim($value))) {
        'attendance' => 'Attendance',
        'payroll' => 'Payroll',
        'documents' => 'Documents',
        default => ucfirst($value !== '' ? $value : 'Report'),
    };
};
?>

<div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">My reports</h1>
    <p class="text-sm text-slate-500">Download key files and review your recent report data in one place.</p>
  </div>
  <div class="inline-flex items-center gap-2 self-start rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600">
    <span class="material-icons text-sm text-daGreen">inventory_2</span>
    Personal exports only
  </div>
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

<div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
  <form method="get" action="personal-reports.php" class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_1fr_auto] xl:items-end">
    <div>
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">From</label>
      <input name="date_from" value="<?= $escape((string)($reportFilters['date_from'] ?? '')) ?>" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>

    <div>
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">To</label>
      <input name="date_to" value="<?= $escape((string)($reportFilters['date_to'] ?? '')) ?>" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>

    <div>
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Type</label>
      <select name="report_type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="">All Reports</option>
        <?php foreach (['attendance', 'payroll', 'documents'] as $type): ?>
          <option value="<?= $escape($type) ?>" <?= (($reportFilters['report_type'] ?? '') === $type) ? 'selected' : '' ?>><?= $escape(ucfirst($type)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
      <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="">All Status</option>
        <?php foreach (['queued', 'processing', 'ready', 'failed'] as $status): ?>
          <option value="<?= $escape($status) ?>" <?= (($reportFilters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= $escape(ucfirst($status)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-daGreen px-4 py-2 font-medium text-white"><span class="material-icons text-sm">filter_alt</span>Apply</button>
  </form>
</div>

<section id="available-downloads" class="mb-8">
  <div class="mb-4 flex items-end justify-between gap-3">
    <div>
      <h2 class="text-lg font-semibold text-slate-900">Downloads</h2>
      <p class="text-sm text-slate-500">Quick access to your report exports and forms.</p>
    </div>
  </div>

  <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="mb-4 flex items-start justify-between gap-3">
        <div>
          <h3 class="text-base font-semibold text-slate-800">Attendance Summary</h3>
          <p class="mt-1 text-sm text-slate-500">Selected date range</p>
        </div>
        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-blue-100 text-blue-700">PDF</span>
      </div>
      <a href="<?= $escape($attendanceReportDownloadUrl) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"><span class="material-icons text-sm">download</span>Download</a>
    </article>

    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="mb-4 flex items-start justify-between gap-3">
        <div>
          <h3 class="text-base font-semibold text-slate-800">Yearly Attendance</h3>
          <p class="mt-1 text-sm text-slate-500">Annual PDF for <?= $escape($attendanceReportYear) ?></p>
        </div>
        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-amber-100 text-amber-700">PDF</span>
      </div>
      <div class="grid grid-cols-1 gap-2">
        <a href="<?= $escape($attendanceYearlyViewUrl) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100"><span class="material-icons text-sm">visibility</span>View</a>
        <a href="<?= $escape($attendanceYearlyDownloadUrl) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"><span class="material-icons text-sm">download</span>Download</a>
      </div>
    </article>

    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="mb-4 flex items-start justify-between gap-3">
        <div>
          <h3 class="text-base font-semibold text-slate-800">Payroll History</h3>
          <p class="mt-1 text-sm text-slate-500">Filtered payroll export</p>
        </div>
        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-emerald-100 text-emerald-700">XLS</span>
      </div>
      <a href="<?= $escape($payrollReportDownloadUrl) ?>" class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"><span class="material-icons text-sm">download</span>Download</a>
    </article>

    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="mb-4 flex items-start justify-between gap-3">
        <div>
          <h3 class="text-base font-semibold text-slate-800">Document Status</h3>
          <p class="mt-1 text-sm text-slate-500">Current document review snapshot</p>
        </div>
        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-violet-100 text-violet-700">XLS</span>
      </div>
      <a href="<?= $escape($documentsReportDownloadUrl) ?>" class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"><span class="material-icons text-sm">download</span>Download</a>
    </article>

    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:col-span-2 xl:col-span-4">
      <div class="mb-4 flex items-start justify-between gap-3">
        <div>
          <h3 class="text-base font-semibold text-slate-800">Forms and Templates</h3>
          <p class="mt-1 text-sm text-slate-500">Official Business and Leave forms.</p>
        </div>
        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-slate-100 text-slate-700">Forms</span>
      </div>
      <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
        <a href="<?= $escape($officialBusinessTemplateUrl) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"><span class="material-icons text-sm">edit_document</span>Open OB Form</a>
        <a href="<?= $escape($officialBusinessTemplateUrl) ?>" download class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"><span class="material-icons text-sm">download</span>Download OB</a>
        <a href="<?= $escape($applicationForLeaveTemplateUrl) ?>" download class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"><span class="material-icons text-sm">download</span>Download Leave</a>
      </div>
    </article>
  </div>
</section>

<section class="mb-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
  <div class="mb-4 flex justify-between items-center gap-3">
    <div>
      <h2 class="text-lg font-semibold text-slate-900">Generated Reports</h2>
      <p class="text-sm text-slate-500">Recent files prepared for your account.</p>
    </div>
  </div>

  <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
      <div class="md:col-span-2">
        <label class="block text-xs font-medium uppercase tracking-wide text-slate-500 mb-1" for="employeeGeneratedReportsSearch">Search</label>
        <input id="employeeGeneratedReportsSearch" type="search" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Report type, format, or file name">
      </div>
      <div>
        <label class="block text-xs font-medium uppercase tracking-wide text-slate-500 mb-1" for="employeeGeneratedReportsStatusFilter">Status</label>
        <select id="employeeGeneratedReportsStatusFilter" class="w-full rounded-lg border border-slate-300 px-3 py-2">
          <option value="">All Statuses</option>
          <option value="queued">Queued</option>
          <option value="processing">Processing</option>
          <option value="ready">Ready</option>
          <option value="failed">Failed</option>
        </select>
      </div>
    </div>
  </div>

  <div class="overflow-x-auto rounded-xl border border-slate-200">
    <table id="employeeGeneratedReportsTable" class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-4 py-3 text-left">Report Type</th>
          <th class="px-4 py-3 text-left">Format</th>
          <th class="px-4 py-3 text-left">Status</th>
          <th class="px-4 py-3 text-left">Created</th>
          <th class="px-4 py-3 text-left">Generated</th>
          <th class="px-4 py-3 text-left">Action</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php if (empty($generatedReportRows)): ?>
          <tr><td class="px-4 py-3 text-gray-500" colspan="6">No generated reports available yet.</td></tr>
        <?php else: ?>
          <?php foreach ($generatedReportRows as $report): ?>
            <tr
              data-generated-report-row="1"
              data-generated-report-search="<?= $escape(strtolower(trim((string)($report['report_type'] ?? ''))) . ' ' . strtolower(trim((string)($report['file_format'] ?? ''))). ' ' . strtolower(trim((string)($report['status'] ?? ''))) . ' ' . strtolower(trim((string)($report['storage_path'] ?? '')))) ?>"
              data-generated-report-status="<?= $escape(strtolower(trim((string)($report['status'] ?? 'queued')))) ?>"
            >
              <td class="px-4 py-3"><?= $escape($generatedReportTypeLabel((string)($report['report_type'] ?? 'report'))) ?></td>
              <td class="px-4 py-3 uppercase"><?= $escape((string)($report['file_format'] ?? 'pdf')) ?></td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= $escape((string)($report['status_class'] ?? 'bg-slate-100 text-slate-700')) ?>">
                  <?= $escape((string)($report['status_label'] ?? 'Queued')) ?>
                </span>
              </td>
              <td class="px-4 py-3"><?= $escape($formatDate($report['created_at'] ?? null)) ?></td>
              <td class="px-4 py-3"><?= $escape($formatDate($report['generated_at'] ?? null)) ?></td>
              <td class="px-4 py-3">
                <?php if (!empty($report['download_url'])): ?>
                  <a href="<?= $escape((string)$report['download_url']) ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"><span class="material-icons text-sm">download</span>Download</a>
                <?php elseif (($report['status'] ?? '') === 'processing' || ($report['status'] ?? '') === 'queued'): ?>
                  <span class="text-xs text-slate-500">File is still being prepared.</span>
                <?php elseif (!empty($report['storage_path'])): ?>
                  <span class="text-xs text-gray-600"><?= $escape((string)$report['storage_path']) ?></span>
                <?php else: ?>
                  <span class="text-xs text-rose-600">File unavailable</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <tr id="employeeGeneratedReportsEmpty" class="hidden">
            <td class="px-4 py-3 text-gray-500" colspan="6">No generated report rows match your search/filter criteria.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div id="employeeGeneratedReportsPagination" class="px-4 py-3 border-t flex items-center justify-between gap-3 text-sm text-gray-600">
      <p id="employeeGeneratedReportsPaginationInfo">Showing 0 to 0 of 0 entries</p>
      <div class="flex items-center gap-2">
        <button type="button" id="employeeGeneratedReportsPrev" class="px-3 py-1.5 border rounded-md hover:bg-gray-50 disabled:opacity-50">Previous</button>
        <span id="employeeGeneratedReportsPageLabel">Page 1 of 1</span>
        <button type="button" id="employeeGeneratedReportsNext" class="px-3 py-1.5 border rounded-md hover:bg-gray-50 disabled:opacity-50">Next</button>
      </div>
    </div>
  </div>
</section>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
<section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
  <div class="mb-4 flex justify-between items-center gap-3">
    <div>
      <h2 class="text-lg font-semibold text-slate-900">Attendance</h2>
      <p class="text-sm text-slate-500">Monthly presence snapshot.</p>
    </div>
    <a href="<?= $escape($attendanceReportDownloadUrl) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"><span class="material-icons text-sm">download</span>PDF</a>
  </div>

  <div class="overflow-x-auto rounded-xl border border-slate-200">
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
          <tr><td class="px-4 py-3 text-gray-500" colspan="4">No attendance data yet.</td></tr>
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

<section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
  <div class="mb-4 flex justify-between items-center gap-3">
    <div>
      <h2 class="text-lg font-semibold text-slate-900">Payroll</h2>
      <p class="text-sm text-slate-500">Recent pay periods and totals.</p>
    </div>
    <a href="<?= $escape($payrollReportDownloadUrl) ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"><span class="material-icons text-sm">download</span>XLS</a>
  </div>

  <div class="overflow-x-auto rounded-xl border border-slate-200">
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
          <tr><td class="px-4 py-3 text-gray-500" colspan="4">No payroll data yet.</td></tr>
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

</div>

<section class="mb-10 mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
  <div class="mb-4 flex justify-between items-center gap-3">
    <div>
      <h2 class="text-lg font-semibold text-slate-900">Documents</h2>
      <p class="text-sm text-slate-500">Latest uploaded files and review status.</p>
    </div>
    <a href="<?= $escape($documentsReportDownloadUrl) ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"><span class="material-icons text-sm">download</span>XLS</a>
  </div>

  <div class="overflow-x-auto rounded-xl border border-slate-200">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-4 py-3 text-left">Document</th>
          <th class="px-4 py-3 text-left">Category</th>
          <th class="px-4 py-3 text-left">Status</th>
          <th class="px-4 py-3 text-left">Version</th>
          <th class="px-4 py-3 text-left">Updated</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php if (empty($documentSummaryRows)): ?>
          <tr><td class="px-4 py-3 text-gray-500" colspan="5">No document records yet.</td></tr>
        <?php else: ?>
          <?php foreach (array_slice($documentSummaryRows, 0, 10) as $row): ?>
            <?php
              $documentStatus = strtolower((string)($row['status'] ?? 'draft'));
              $documentStatusClass = match ($documentStatus) {
                  'approved' => 'bg-emerald-100 text-emerald-700',
                  'submitted' => 'bg-blue-100 text-blue-700',
                  'needs_revision' => 'bg-amber-100 text-amber-800',
                  'rejected' => 'bg-rose-100 text-rose-700',
                  'archived' => 'bg-slate-100 text-slate-700',
                  default => 'bg-slate-100 text-slate-700',
              };
              $documentStatusLabel = ucwords(str_replace('_', ' ', $documentStatus !== '' ? $documentStatus : 'draft'));
            ?>
            <tr>
              <td class="px-4 py-3 font-medium text-slate-800"><?= $escape((string)($row['title'] ?? 'Untitled Document')) ?></td>
              <td class="px-4 py-3"><?= $escape((string)($row['category_name'] ?? 'Others')) ?></td>
              <td class="px-4 py-3"><span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= $escape($documentStatusClass) ?>"><?= $escape($documentStatusLabel) ?></span></td>
              <td class="px-4 py-3">v<?= $escape((string)($row['current_version_no'] ?? 1)) ?></td>
              <td class="px-4 py-3"><?= $escape($formatDate($row['updated_at'] ?? null)) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php
$content = ob_get_clean();
include './includes/layout.php';
