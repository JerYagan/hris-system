<?php
/**
 * Employee Payroll
 * DA-ATI HRIS
 */

require_once __DIR__ . '/includes/payroll/bootstrap.php';
require_once __DIR__ . '/includes/payroll/actions.php';
require_once __DIR__ . '/includes/payroll/data.php';

$pageTitle = 'Payroll | DA HRIS';
$activePage = 'payroll.php';
$breadcrumbs = ['Payroll'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/employee/payroll/index.js';

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

$payslipStatusMeta = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'released' => ['Released', 'bg-approved text-green-800'],
        'pending' => ['Pending', 'bg-pending text-yellow-800'],
        default => ['Draft', 'bg-gray-200 text-gray-700'],
    };
};
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold">Payroll</h1>
  <p class="text-sm text-gray-500">View payroll breakdown and retrieve your payslips.</p>
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

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
  <div class="bg-white p-5 rounded-lg shadow border">
    <p class="text-sm text-gray-500">Latest Net Pay</p>
    <h2 class="text-2xl font-bold mt-2"><?= $escape($formatCurrency((float)($payrollSummary['latest_net_pay'] ?? 0))) ?></h2>
    <p class="text-xs text-gray-400 mt-1"><?= $escape((string)($payrollSummary['latest_period_label'] ?? '-')) ?></p>
  </div>

  <div class="bg-white p-5 rounded-lg shadow border">
    <p class="text-sm text-gray-500">Gross Pay</p>
    <h2 class="text-2xl font-bold mt-2"><?= $escape($formatCurrency((float)($payrollSummary['latest_gross_pay'] ?? 0))) ?></h2>
    <p class="text-xs text-gray-400 mt-1">Latest payroll period</p>
  </div>

  <div class="bg-white p-5 rounded-lg shadow border">
    <p class="text-sm text-gray-500">Total Deductions</p>
    <h2 class="text-2xl font-bold mt-2 text-red-600"><?= $escape($formatCurrency((float)($payrollSummary['latest_deductions'] ?? 0))) ?></h2>
    <p class="text-xs text-gray-400 mt-1">Latest payroll period</p>
  </div>
</div>

<div class="bg-white rounded-lg shadow border">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 px-6 py-4 border-b">
    <h2 class="text-lg font-bold">Payslip <span class="text-daGreen">History</span></h2>

    <div class="w-full md:w-52">
      <label class="text-xs text-gray-500">Filter by year</label>
      <select id="payrollYearFilter" class="w-full border rounded-md px-3 py-2 text-sm">
        <option value="">All Years</option>
        <?php foreach ($payrollYears as $year): ?>
          <option value="<?= $escape((string)$year) ?>"><?= $escape((string)$year) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <?php if (empty($employeePayrollRows)): ?>
    <div class="px-6 py-10 text-center text-sm text-gray-500">
      No payroll records available yet.
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-6 py-3 text-left">Pay Period</th>
            <th class="px-6 py-3 text-left">Gross Pay</th>
            <th class="px-6 py-3 text-left">Deductions</th>
            <th class="px-6 py-3 text-left">Net Pay</th>
            <th class="px-6 py-3 text-left">Status</th>
            <th class="px-6 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody id="payrollTableBody" class="divide-y">
          <?php foreach ($employeePayrollRows as $row): ?>
            <?php
              [$statusLabel, $statusClass] = $payslipStatusMeta((string)($row['status'] ?? 'pending'));
              $hasPdf = !empty($row['pdf_storage_path']) && !empty($row['payslip_id']);
              $detailPayload = [
                'period_label' => (string)($row['period_label'] ?? '-'),
                'payslip_no' => (string)($row['payslip_no'] ?? '-'),
                'status_label' => $statusLabel,
                'gross_pay' => (float)($row['gross_pay'] ?? 0),
                'deductions_total' => (float)($row['deductions_total'] ?? 0),
                'net_pay' => (float)($row['net_pay'] ?? 0),
                'released_at' => (string)($row['released_at'] ?? ''),
                'earnings' => (array)($row['earnings'] ?? []),
                'deductions' => (array)($row['deductions'] ?? []),
              ];
              $detailPayloadJson = htmlspecialchars((string)json_encode($detailPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
            ?>
            <tr class="hover:bg-gray-50" data-payroll-row data-year="<?= $escape((string)($row['period_year'] ?? '')) ?>">
              <td class="px-6 py-4"><?= $escape((string)($row['period_label'] ?? '-')) ?></td>
              <td class="px-6 py-4"><?= $escape($formatCurrency((float)($row['gross_pay'] ?? 0))) ?></td>
              <td class="px-6 py-4"><?= $escape($formatCurrency((float)($row['deductions_total'] ?? 0))) ?></td>
              <td class="px-6 py-4 font-medium"><?= $escape($formatCurrency((float)($row['net_pay'] ?? 0))) ?></td>
              <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full <?= $escape($statusClass) ?>"><?= $escape($statusLabel) ?></span></td>
              <td class="px-6 py-4 text-right">
                <div class="inline-flex items-center gap-2">
                  <button type="button" data-open-payslip-detail data-payload="<?= $detailPayloadJson ?>" class="text-blue-600 hover:underline text-sm">View Breakdown</button>
                  <?php if ($hasPdf): ?>
                    <a href="view-payslip.php?payslip_id=<?= $escape((string)$row['payslip_id']) ?>" target="_blank" rel="noopener" class="text-blue-600 hover:underline text-sm">View PDF</a>
                    <a href="download-payslip.php?payslip_id=<?= $escape((string)$row['payslip_id']) ?>" class="text-gray-700 hover:underline text-sm">Download</a>
                  <?php else: ?>
                    <span class="text-gray-400 text-sm">No PDF</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div id="payrollFilterEmpty" class="hidden px-6 py-8 text-center text-sm text-gray-500 border-t">
      No payroll records found for the selected year.
    </div>
  <?php endif; ?>
</div>

<div id="payslipModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden" aria-hidden="true">
  <div class="bg-white w-full max-w-3xl rounded-lg shadow-lg max-h-[88vh] flex flex-col">
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h2 class="text-lg font-semibold">Payslip Breakdown</h2>
      <button type="button" data-close-payslip><span class="material-icons">close</span></button>
    </div>

    <div class="px-6 py-5 space-y-6 text-sm overflow-y-auto">
      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <p class="text-gray-500">Pay Period</p>
          <p class="font-medium" id="payslipPeriod">-</p>
        </div>
        <div>
          <p class="text-gray-500">Payslip No.</p>
          <p class="font-medium" id="payslipNo">-</p>
        </div>
        <div>
          <p class="text-gray-500">Status</p>
          <p class="font-medium" id="payslipStatus">-</p>
        </div>
      </div>

      <div>
        <h3 class="font-semibold mb-2">Earnings</h3>
        <div id="payslipEarnings" class="border rounded-md divide-y"></div>
      </div>

      <div>
        <h3 class="font-semibold mb-2">Deductions</h3>
        <div id="payslipDeductions" class="border rounded-md divide-y"></div>
      </div>

      <div class="border-t pt-4 grid md:grid-cols-3 gap-4">
        <div>
          <p class="text-gray-500">Gross Pay</p>
          <p class="font-semibold" id="payslipGross">₱ 0.00</p>
        </div>
        <div>
          <p class="text-gray-500">Total Deductions</p>
          <p class="font-semibold text-red-600" id="payslipTotalDeductions">₱ 0.00</p>
        </div>
        <div>
          <p class="text-gray-500">Net Pay</p>
          <p class="font-semibold text-green-700" id="payslipNet">₱ 0.00</p>
        </div>
      </div>
    </div>

    <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
      <button type="button" data-close-payslip class="px-4 py-2 border rounded-md text-sm">Close</button>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include './includes/layout.php';
