<?php
$currency = static fn(float $amount): string => '₱' . number_format($amount, 2);

$runStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    if ($key === 'released') {
        return ['Released', 'bg-emerald-100 text-emerald-800'];
    }
    if ($key === 'approved') {
        return ['Approved', 'bg-blue-100 text-blue-800'];
    }
    if ($key === 'computed') {
        return ['Computed', 'bg-violet-100 text-violet-800'];
    }
    if ($key === 'cancelled') {
        return ['Cancelled', 'bg-rose-100 text-rose-800'];
    }

    return ['Draft', 'bg-amber-100 text-amber-800'];
};

$payslipStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    if ($key === 'released') {
        return ['Released', 'bg-emerald-100 text-emerald-800'];
    }

    return ['Pending', 'bg-amber-100 text-amber-800'];
};
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Payroll Management</h1>
        <p class="text-sm text-slate-300 mt-2">Configure compensation, review payroll batches, and release employee payslips using live payroll data.</p>
    </div>
</div>

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
        <h2 class="text-lg font-semibold text-slate-800">Manage Salary Setup (Base Pay, Deductions, Allowance)</h2>
        <p class="text-sm text-slate-500 mt-1">Save compensation setup and create a new effective salary configuration for the selected employee.</p>
    </header>

    <form id="payrollSalarySetupForm" action="payroll-management.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <input type="hidden" name="form_action" value="save_salary_setup">
        <input type="hidden" name="person_id" id="payrollSelectedPersonId" value="">
        <div>
            <label class="text-slate-600">Employee</label>
            <input
                id="payrollEmployeeSearch"
                type="text"
                list="payrollEmployeeOptions"
                class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"
                placeholder="Type employee name"
                autocomplete="off"
                required
            >
            <datalist id="payrollEmployeeOptions">
                <?php foreach ($employeesForSetup as $employee): ?>
                    <?php
                    $personId = (string)$employee['id'];
                    $optionLabel = (string)$employee['name'] . ' — ' . strtoupper(substr(str_replace('-', '', $personId), 0, 6));
                    $monthlyRate = (string)($latestCompensationByPerson[$personId]['monthly_rate'] ?? '');
                    $payFrequency = (string)($latestCompensationByPerson[$personId]['pay_frequency'] ?? 'semi_monthly');
                    ?>
                    <option
                        value="<?= htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8') ?>"
                        data-person-id="<?= htmlspecialchars($personId, ENT_QUOTES, 'UTF-8') ?>"
                        data-monthly-rate="<?= htmlspecialchars($monthlyRate, ENT_QUOTES, 'UTF-8') ?>"
                        data-pay-frequency="<?= htmlspecialchars($payFrequency, ENT_QUOTES, 'UTF-8') ?>"
                    ></option>
                <?php endforeach; ?>
            </datalist>
            <p class="text-xs text-slate-500 mt-1">Search by name and pick a suggested employee.</p>
        </div>
        <div>
            <label class="text-slate-600">Effective From</label>
            <input type="date" name="effective_from" value="<?= htmlspecialchars(gmdate('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
        </div>
        <div>
            <label class="text-slate-600">Pay Frequency</label>
            <select id="payrollPayFrequency" name="pay_frequency" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                <option value="semi_monthly">Semi-monthly</option>
                <option value="monthly">Monthly</option>
                <option value="weekly">Weekly</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Base Pay</label>
            <input id="payrollBasePay" type="number" step="0.01" min="0" name="base_pay" value="0" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
        </div>
        <div>
            <label class="text-slate-600">Total Allowance</label>
            <input type="number" step="0.01" min="0" name="allowance_total" value="0" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
        </div>
        <div>
            <label class="text-slate-600">Tax Deduction</label>
            <input type="number" step="0.01" min="0" name="tax_deduction" value="0" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
        </div>
        <div>
            <label class="text-slate-600">Government Deductions</label>
            <input type="number" step="0.01" min="0" name="government_deductions" value="0" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
        </div>
        <div>
            <label class="text-slate-600">Other Deductions</label>
            <input type="number" step="0.01" min="0" name="other_deductions" value="0" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
        </div>
        <div class="md:col-span-4 flex justify-end gap-3 mt-2">
            <button type="reset" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Salary Setup</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Generate Payroll Reports</h2>
            <p class="text-sm text-slate-500 mt-1">Use payroll summary values from current cutoff and continue export in report analytics.</p>
        </div>
        <a href="report-analytics.php" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Generate Report</a>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase text-slate-500">Current Cutoff</p>
            <p class="font-semibold text-slate-800 mt-2"><?= htmlspecialchars($currentCutoffLabel, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)$currentCutoffEmployeeCount, ENT_QUOTES, 'UTF-8') ?> employee<?= $currentCutoffEmployeeCount === 1 ? '' : 's' ?> included</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase text-emerald-700">Estimated Gross</p>
            <p class="font-semibold text-slate-800 mt-2"><?= htmlspecialchars($currency((float)$currentCutoffGross), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Before deductions</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase text-amber-700">Estimated Net</p>
            <p class="font-semibold text-slate-800 mt-2"><?= htmlspecialchars($currency((float)$currentCutoffNet), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">After all deductions</p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Approve Payroll Batches</h2>
        <p class="text-sm text-slate-500 mt-1">Review computed payroll batches and update release readiness.</p>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Batches</label>
            <input id="payrollBatchesSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by period, status, or batch ID">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="payrollBatchesStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="Draft">Draft</option>
                <option value="Computed">Computed</option>
                <option value="Approved">Approved</option>
                <option value="Released">Released</option>
                <option value="Cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="payrollBatchesTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Batch ID</th>
                    <th class="text-left px-4 py-3">Cutoff Period</th>
                    <th class="text-left px-4 py-3">Employee Count</th>
                    <th class="text-left px-4 py-3">Total Net Pay</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($batchRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="6">No payroll batches found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($batchRows as $batch): ?>
                        <?php
                        [$statusLabel, $statusClass] = $runStatusPill((string)$batch['status']);
                        $searchText = strtolower(trim((string)$batch['id'] . ' ' . (string)$batch['period_label'] . ' ' . $statusLabel));
                        ?>
                        <tr data-payroll-batch-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-payroll-batch-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$batch['id'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$batch['period_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$batch['employee_count'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($currency((float)$batch['total_net']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[95px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-payroll-review
                                    data-run-id="<?= htmlspecialchars((string)$batch['id'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-period-label="<?= htmlspecialchars((string)$batch['period_label'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>"
                                    data-employee-count="<?= htmlspecialchars((string)$batch['employee_count'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-total-net="<?= htmlspecialchars($currency((float)$batch['total_net']), ENT_QUOTES, 'UTF-8') ?>"
                                    class="px-2.5 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50"
                                >
                                    Review
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
        <h2 class="text-lg font-semibold text-slate-800">View Employee Payslips</h2>
        <p class="text-sm text-slate-500 mt-1">Track generated payslip records and release status by employee payroll item.</p>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Payslips</label>
            <input id="payrollPayslipsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by employee, period, or payslip no.">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="payrollPayslipsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="Released">Released</option>
                <option value="Pending">Pending</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="payrollPayslipsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Cutoff</th>
                    <th class="text-left px-4 py-3">Gross Pay</th>
                    <th class="text-left px-4 py-3">Deductions</th>
                    <th class="text-left px-4 py-3">Net Pay</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($payslipTableRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="7">No payroll items found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payslipTableRows as $row): ?>
                        <?php
                        [$statusLabel, $statusClass] = $payslipStatusPill((string)$row['status']);
                        $searchText = strtolower(trim((string)$row['employee_name'] . ' ' . (string)$row['period_label'] . ' ' . (string)$row['payslip_no']));
                        ?>
                        <tr data-payroll-payslip-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-payroll-payslip-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['employee_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['period_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($currency((float)$row['gross_pay']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($currency((float)$row['deductions_total']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($currency((float)$row['net_pay']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[95px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <?php if (!empty($row['pdf_storage_path'])): ?>
                                    <a href="<?= htmlspecialchars((string)$row['pdf_storage_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="px-2.5 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">View Payslip</a>
                                <?php else: ?>
                                    <span class="px-2.5 py-1.5 text-xs rounded-md border border-slate-200 text-slate-400 bg-slate-50">Not Uploaded</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Send Payslip to Employees via Email</h2>
            <p class="text-sm text-slate-500 mt-1">Release payslips and trigger in-app notification records for the selected payroll batch.</p>
        </div>
    </header>

    <form action="payroll-management.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <input type="hidden" name="form_action" value="release_payslips">
        <div>
            <label class="text-slate-600">Payroll Batch</label>
            <select name="payroll_run_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                <option value="">Select payroll batch</option>
                <?php foreach ($releaseEligibleRuns as $run): ?>
                    <option value="<?= htmlspecialchars((string)$run['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string)$run['period_code'] . ' • ' . (string)$run['period_label'] . ' • ' . ucfirst((string)$run['status']), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Recipient Group</label>
            <select name="recipient_group" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="all_active">All Active Employees</option>
                <option value="selected_department">Selected Department</option>
                <option value="selected_employees">Selected Employees</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Delivery Mode</label>
            <select name="delivery_mode" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="immediate">Send Immediately</option>
                <option value="scheduled">Schedule Send</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Send Payslips</button>
        </div>
    </form>
</section>

<div id="reviewPayrollBatchModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="reviewPayrollBatchModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Review Payroll Batch</h3>
                <button type="button" data-modal-close="reviewPayrollBatchModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="payroll-management.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="review_payroll_batch">
                <input type="hidden" id="payrollRunId" name="run_id" value="">
                <div class="md:col-span-2">
                    <label class="text-slate-600">Cutoff Period</label>
                    <input id="payrollBatchPeriod" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Current Status</label>
                    <input id="payrollBatchStatus" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Employee Count</label>
                    <input id="payrollBatchEmployees" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Total Net Pay</label>
                    <input id="payrollBatchNet" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Decision</label>
                    <select name="decision" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="approved">Approve</option>
                        <option value="cancelled">Cancel Batch</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Notes</label>
                    <textarea name="notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add review notes or exception details."></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="reviewPayrollBatchModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Decision</button>
                </div>
            </form>
        </div>
    </div>
</div>
