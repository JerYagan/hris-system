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

$setupStatusPill = static function (string $status): array {
    $normalized = strtolower(trim($status));
    if ($normalized === 'scheduled') {
        return ['Scheduled', 'bg-blue-100 text-blue-800'];
    }
    if ($normalized === 'ended') {
        return ['Ended', 'bg-slate-100 text-slate-700'];
    }

    return ['Current', 'bg-emerald-100 text-emerald-800'];
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
        <h2 class="text-lg font-semibold text-slate-800">Manage Salary Setup (Base Pay, Deductions, Allowance)</h2>
        <p class="text-sm text-slate-500 mt-1">Save compensation setup and create a new effective salary configuration for the selected employee.</p>
        <p id="payrollEffectivityHint" class="text-xs text-slate-500 mt-2">Tip: Future effectivity dates are scheduled and only apply to payroll periods that cover that date.</p>
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
                    $monthlyRate = (string)($setupCompensationByPerson[$personId]['monthly_rate'] ?? '');
                    $basePay = (string)($setupCompensationByPerson[$personId]['base_pay'] ?? '');
                    $allowanceTotal = (string)($setupCompensationByPerson[$personId]['allowance_total'] ?? '');
                    $taxDeduction = (string)($setupCompensationByPerson[$personId]['tax_deduction'] ?? '');
                    $governmentDeductions = (string)($setupCompensationByPerson[$personId]['government_deductions'] ?? '');
                    $otherDeductions = (string)($setupCompensationByPerson[$personId]['other_deductions'] ?? '');
                    $payFrequency = (string)($setupCompensationByPerson[$personId]['pay_frequency'] ?? 'semi_monthly');
                    ?>
                    <option
                        value="<?= htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8') ?>"
                        data-person-id="<?= htmlspecialchars($personId, ENT_QUOTES, 'UTF-8') ?>"
                        data-monthly-rate="<?= htmlspecialchars($monthlyRate, ENT_QUOTES, 'UTF-8') ?>"
                        data-base-pay="<?= htmlspecialchars($basePay, ENT_QUOTES, 'UTF-8') ?>"
                        data-allowance-total="<?= htmlspecialchars($allowanceTotal, ENT_QUOTES, 'UTF-8') ?>"
                        data-tax-deduction="<?= htmlspecialchars($taxDeduction, ENT_QUOTES, 'UTF-8') ?>"
                        data-government-deductions="<?= htmlspecialchars($governmentDeductions, ENT_QUOTES, 'UTF-8') ?>"
                        data-other-deductions="<?= htmlspecialchars($otherDeductions, ENT_QUOTES, 'UTF-8') ?>"
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
            <input id="payrollOtherDeduction" type="number" step="0.01" min="0" name="other_deductions" value="0" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
        </div>
        <div class="md:col-span-4">
            <p class="text-xs text-slate-500">Deduction inputs are applied to generated payroll totals and payslip net pay.</p>
        </div>
        <div>
            <label class="text-slate-600">Computed Monthly Rate</label>
            <input id="payrollComputedMonthlyRate" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" value="₱0.00" readonly>
        </div>
        <div>
            <label class="text-slate-600">Estimated Net Per Cycle</label>
            <input id="payrollEstimatedNetCycle" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" value="₱0.00" readonly>
        </div>
        <div class="md:col-span-4 flex justify-end gap-3 mt-2">
            <button type="reset" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Salary Setup</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Select Employee for Salary Setup</h2>
        <p class="text-sm text-slate-500 mt-1">Use this table to pick an employee instead of typing the name. Search typing is still supported above.</p>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Employee</label>
            <input id="payrollEmployeePickerSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by employee name or ID">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="payrollEmployeePickerStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="on_leave">On Leave</option>
                <option value="resigned">Resigned</option>
                <option value="terminated">Terminated</option>
                <option value="retired">Retired</option>
            </select>
        </div>
    </div>

    <div class="p-6 pt-3 overflow-x-auto">
        <table id="payrollEmployeePickerTable" data-simple-table="true" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Employee ID</th>
                    <th class="text-left px-4 py-3">Employment Status</th>
                    <th class="text-left px-4 py-3">Current Computed Monthly Rate</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($employeePickerRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="5">No employees available for setup.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employeePickerRows as $row): ?>
                        <?php
                        $personId = (string)($row['person_id'] ?? '');
                        $employeeName = (string)($row['name'] ?? 'Unknown Employee');
                        $label = $employeeName . ' — ' . strtoupper(substr(str_replace('-', '', $personId), 0, 6));
                        $searchText = strtolower(trim($employeeName . ' ' . $personId));
                        $status = strtolower(trim((string)($row['status'] ?? 'active')));
                        ?>
                        <tr data-payroll-employee-picker-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-payroll-employee-picker-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($personId, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $status)), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($currency((float)($row['monthly_rate'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-payroll-select-employee
                                    data-employee-label="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"
                                >
                                    <span class="material-symbols-outlined text-[15px]">person_add</span>Select
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
        <h2 class="text-lg font-semibold text-slate-800">Salary Setup Logs</h2>
        <p class="text-sm text-slate-500 mt-1">Review all salary setup entries. Delete an incorrect entry to re-align the compensation timeline.</p>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Salary Logs</label>
            <input id="payrollSetupLogsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by employee, setup ID, or date">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Timeline Status</label>
            <select id="payrollSetupLogsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All</option>
                <option value="current">Current</option>
                <option value="scheduled">Scheduled</option>
                <option value="ended">Ended</option>
            </select>
        </div>
        <div class="w-full md:w-44">
            <label class="text-sm text-slate-600">Entries</label>
            <select id="payrollSetupLogsPageSize" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

    <?php if (!empty($salarySetupLogRows)): ?>
        <form id="payrollBulkDeleteSetupForm" action="payroll-management.php" method="POST" class="px-6 pb-2 flex justify-end">
            <input type="hidden" name="form_action" value="delete_salary_setup_bulk">
            <button id="payrollBulkDeleteSetupButton" type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs rounded-lg border border-rose-300 bg-white text-rose-700 hover:bg-rose-50 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed" disabled aria-disabled="true">
                <span class="material-symbols-outlined text-[15px]">delete</span>Delete Selected
            </button>
        </form>
    <?php endif; ?>

    <div class="p-6 pt-3 overflow-x-auto">
        <table id="payrollSetupLogsTable" data-simple-table="true" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3 w-12">
                        <input id="payrollSetupLogsSelectAll" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                    </th>
                    <th class="text-left px-4 py-3">Setup ID</th>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Effective Range</th>
                    <th class="text-left px-4 py-3">Computed Monthly Rate</th>
                    <th class="text-left px-4 py-3">Frequency</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($salarySetupLogRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="8">No salary setup logs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($salarySetupLogRows as $row): ?>
                        <?php
                        [$setupStatusText, $setupStatusClass] = $setupStatusPill((string)($row['status'] ?? 'current'));
                        $setupId = (string)($row['id'] ?? '');
                        $effectiveFrom = (string)($row['effective_from'] ?? '-');
                        $effectiveTo = (string)($row['effective_to'] ?? '');
                        $effectiveRange = $effectiveFrom . ' to ' . ($effectiveTo !== '' ? $effectiveTo : 'Present');
                        $searchText = strtolower(trim($setupId . ' ' . (string)($row['employee_name'] ?? '') . ' ' . $effectiveRange));
                        ?>
                        <tr data-payroll-setup-log-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-payroll-setup-log-status="<?= htmlspecialchars((string)($row['status'] ?? 'current'), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <input type="checkbox" name="setup_ids[]" value="<?= htmlspecialchars($setupId, ENT_QUOTES, 'UTF-8') ?>" form="payrollBulkDeleteSetupForm" data-payroll-setup-log-select class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($setupId, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-800 font-medium"><?= htmlspecialchars((string)($row['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($effectiveRange, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($currency((float)($row['monthly_rate'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)($row['pay_frequency'] ?? 'semi_monthly'))), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[95px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($setupStatusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($setupStatusText, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <form action="payroll-management.php" method="POST" class="inline">
                                    <input type="hidden" name="form_action" value="delete_salary_setup">
                                    <input type="hidden" name="setup_id" value="<?= htmlspecialchars($setupId, ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" data-payroll-delete-setup class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-rose-300 bg-white text-rose-700 hover:bg-rose-50 shadow-sm">
                                        <span class="material-symbols-outlined text-[15px]">delete</span>Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if (!empty($salarySetupLogRows)): ?>
            <div class="mt-4 flex items-center justify-between gap-3 text-sm">
                <p id="payrollSetupLogsPageInfo" class="text-slate-500">Showing 0 of 0</p>
                <div class="flex items-center gap-2">
                    <button id="payrollSetupLogsPrev" type="button" class="px-3 py-1.5 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Previous</button>
                    <button id="payrollSetupLogsNext" type="button" class="px-3 py-1.5 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Next</button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Generate Payroll Reports</h2>
            <p class="text-sm text-slate-500 mt-1">Use payroll summary values from current and previous cutoffs, then continue export when needed.</p>
        </div>
        <a href="#payrollEstimateHistory" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">View Estimate History</a>
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

    <div id="payrollEstimateHistory" class="px-6 pb-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Period</th>
                    <th class="text-left px-4 py-3">Period Status</th>
                    <th class="text-left px-4 py-3">Employees</th>
                    <th class="text-left px-4 py-3">Estimated Gross</th>
                    <th class="text-left px-4 py-3">Estimated Net</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($payrollEstimateHistoryRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="6">No period estimates available.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payrollEstimateHistoryRows as $history): ?>
                        <?php
                        $historyPeriodId = (string)($history['period_id'] ?? '');
                        ?>
                        <tr>
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars((string)$history['period_code'] . ' • ' . (string)$history['period_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars(ucfirst((string)$history['status']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)$history['employee_count'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($currency((float)$history['estimated_gross']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-800 font-medium"><?= htmlspecialchars($currency((float)$history['estimated_net']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <?php if ($historyPeriodId !== ''): ?>
                                    <form action="payroll-management.php" method="POST" class="inline">
                                        <input type="hidden" name="form_action" value="delete_payroll_period">
                                        <input type="hidden" name="period_id" value="<?= htmlspecialchars($historyPeriodId, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" data-payroll-delete-period class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-rose-300 bg-white text-rose-700 hover:bg-rose-50 shadow-sm">
                                            <span class="material-symbols-outlined text-[15px]">delete</span>Delete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">N/A</span>
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
    <header class="px-6 py-4 border-b border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Generate Payroll Batch</h2>
            <p class="text-sm text-slate-500 mt-1">Create computed payroll items from active employee salary setup before approval and release.</p>
        </div>
        <div class="inline-flex items-center gap-2 text-xs">
            <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">Eligible Periods: <?= htmlspecialchars((string)count($generationPeriodOptions), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </header>

    <form id="generatePayrollBatchForm" action="payroll-management.php" method="POST" class="p-6 text-sm space-y-4">
        <input type="hidden" name="form_action" value="generate_payroll_batch">
        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="text-slate-700 font-medium">Payroll Period</label>
                <select id="generatePayrollPeriod" name="payroll_period_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-white" required>
                    <option value="">Select payroll period</option>
                    <?php foreach ($generationPeriodOptions as $period): ?>
                        <?php
                        $periodId = (string)($period['id'] ?? '');
                        $periodCode = (string)($period['period_code'] ?? 'PR');
                        $periodStart = (string)($period['period_start'] ?? '');
                        $periodEnd = (string)($period['period_end'] ?? '');
                        $status = ucfirst((string)($period['status'] ?? 'open'));
                        $label = $periodCode;
                        if ($periodStart !== '' && $periodEnd !== '') {
                            $label .= ' • ' . date('M d, Y', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd));
                        }
                        $label .= ' • ' . $status;
                        ?>
                        <option
                            value="<?= htmlspecialchars($periodId, ENT_QUOTES, 'UTF-8') ?>"
                            data-period-code="<?= htmlspecialchars($periodCode, ENT_QUOTES, 'UTF-8') ?>"
                            data-period-start="<?= htmlspecialchars($periodStart, ENT_QUOTES, 'UTF-8') ?>"
                            data-period-end="<?= htmlspecialchars($periodEnd, ENT_QUOTES, 'UTF-8') ?>"
                            data-period-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                        ><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <p id="generatePayrollPeriodHint" class="text-xs text-slate-500 mt-1">Select a period to review estimated employees and totals before generation.</p>
                <?php if (empty($generationPeriodOptions)): ?>
                    <p class="text-xs text-amber-700 mt-1">No eligible payroll periods available for generation.</p>
                <?php endif; ?>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-xs uppercase text-slate-500">Preview Snapshot</p>
                <p id="generatePayrollPreviewQuickEmployees" class="text-sm font-medium text-slate-800 mt-2">No period selected</p>
                <p id="generatePayrollPreviewQuickNet" class="text-sm text-slate-600 mt-1">Net: ₱0.00</p>
            </div>
        </div>
        <div class="flex items-center justify-end gap-3">
            <button type="button" id="openGeneratePayrollSummary" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800 disabled:opacity-60 disabled:cursor-not-allowed" <?= empty($generationPeriodOptions) ? 'disabled aria-disabled="true"' : '' ?>>Review Summary</button>
        </div>
    </form>
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
        <table id="payrollBatchesTable" data-simple-table="true" class="w-full text-sm">
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
                        <tr class="hover:bg-slate-100 transition-colors" data-payroll-batch-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-payroll-batch-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$batch['id'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$batch['period_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$batch['employee_count'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($currency((float)$batch['total_net']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[95px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        data-payroll-review
                                        data-run-id="<?= htmlspecialchars((string)$batch['id'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-period-label="<?= htmlspecialchars((string)$batch['period_label'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>"
                                        data-employee-count="<?= htmlspecialchars((string)$batch['employee_count'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-total-net="<?= htmlspecialchars($currency((float)$batch['total_net']), ENT_QUOTES, 'UTF-8') ?>"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">rule</span>Review
                                    </button>

                                    <form action="payroll-management.php" method="POST" class="inline">
                                        <input type="hidden" name="form_action" value="delete_payroll_batch">
                                        <input type="hidden" name="run_id" value="<?= htmlspecialchars((string)$batch['id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" data-payroll-delete-batch class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-rose-300 bg-white text-rose-700 hover:bg-rose-50 shadow-sm">
                                            <span class="material-symbols-outlined text-[15px]">delete</span>Delete
                                        </button>
                                    </form>
                                </div>
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
        <table id="payrollPayslipsTable" data-simple-table="true" class="w-full text-sm">
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
                        <tr class="hover:bg-slate-100 transition-colors" data-payroll-payslip-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-payroll-payslip-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['employee_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['period_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($currency((float)$row['gross_pay']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($currency((float)$row['deductions_total']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($currency((float)$row['net_pay']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[95px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <?php if (!empty($row['pdf_storage_path'])): ?>
                                    <a href="<?= htmlspecialchars((string)$row['pdf_storage_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"><span class="material-symbols-outlined text-[15px]">description</span>View Payslip</a>
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
            <p class="text-sm text-slate-500 mt-1">Release payslips, generate payslip documents, send SMTP email notifications (immediate mode), and create in-app notifications for the selected payroll batch.</p>
        </div>
        <div class="inline-flex items-center gap-2 text-xs">
            <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">Ready Batches: <?= htmlspecialchars((string)count($releaseEligibleRuns), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </header>

    <form id="releasePayslipsForm" action="payroll-management.php" method="POST" class="p-6 text-sm space-y-4">
        <input type="hidden" name="form_action" value="release_payslips">
        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="text-slate-700 font-medium">Payroll Batch</label>
                <select id="releasePayrollRunSelect" name="payroll_run_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-white" required>
                    <option value="">Select payroll batch</option>
                    <?php foreach ($releaseEligibleRuns as $run): ?>
                        <option
                            value="<?= htmlspecialchars((string)$run['id'], ENT_QUOTES, 'UTF-8') ?>"
                            data-period-code="<?= htmlspecialchars((string)$run['period_code'], ENT_QUOTES, 'UTF-8') ?>"
                            data-period-label="<?= htmlspecialchars((string)$run['period_label'], ENT_QUOTES, 'UTF-8') ?>"
                            data-status="<?= htmlspecialchars(ucfirst((string)$run['status']), ENT_QUOTES, 'UTF-8') ?>"
                            data-employee-count="<?= htmlspecialchars((string)($run['employee_count'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"
                            data-total-net="<?= htmlspecialchars((string)($run['total_net'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <?= htmlspecialchars((string)$run['period_code'] . ' • ' . (string)$run['period_label'] . ' • ' . ucfirst((string)$run['status']), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-slate-700 font-medium">Recipient Group</label>
                <select name="recipient_group" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-white">
                    <option value="all_active">All Active Employees</option>
                </select>
            </div>
            <div>
                <label class="text-slate-700 font-medium">Delivery Mode</label>
                <select name="delivery_mode" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-white">
                    <option value="immediate">Send Immediately</option>
                </select>
            </div>
            <div class="flex items-end">
                <button id="releasePayslipsSubmit" type="submit" class="w-full px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800 disabled:opacity-60 disabled:cursor-not-allowed" <?= empty($releaseEligibleRuns) ? 'disabled aria-disabled="true"' : '' ?>>Send Payslips</button>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p id="releasePayslipRunHint" class="text-xs text-slate-600">Select a payroll batch to review release details before sending payslips.</p>
            <p id="releasePayslipRunMeta" class="text-sm text-slate-800 mt-1">No payroll batch selected.</p>
            <?php if (empty($releaseEligibleRuns)): ?>
                <p class="text-xs text-amber-700 mt-1">No computed/approved payroll batches are ready for release.</p>
            <?php endif; ?>
        </div>
    </form>
</section>

<div id="generatePayrollSummaryModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="generatePayrollSummaryModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-4xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Confirm Payroll Batch Generation</h3>
                <button type="button" data-modal-close="generatePayrollSummaryModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <form action="payroll-management.php" method="POST" class="p-6 space-y-4 text-sm">
                <input type="hidden" name="form_action" value="generate_payroll_batch">
                <input type="hidden" id="generatePayrollPeriodIdConfirm" name="payroll_period_id" value="">

                <div>
                    <label class="text-slate-600">Payroll Period</label>
                    <input id="generatePayrollPeriodLabelConfirm" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>

                <div id="generatePayrollPreviewEmpty" class="rounded-lg border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3 <?= empty($generationPreviewRows) ? '' : 'hidden' ?>">
                        No active employees with salary setup are available for payroll generation.
                </div>

                <div id="generatePayrollPreviewContent" class="space-y-4 <?= empty($generationPreviewRows) ? 'hidden' : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
                            <p class="text-xs uppercase text-slate-500">Employees</p>
                            <p id="generatePayrollPreviewEmployeeCount" class="text-lg font-semibold text-slate-800 mt-1"><?= htmlspecialchars((string)$generationPreviewTotals['employee_count'], ENT_QUOTES, 'UTF-8') ?></p>
                        </article>
                        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
                            <p class="text-xs uppercase text-emerald-700">Estimated Gross</p>
                            <p id="generatePayrollPreviewGross" class="text-lg font-semibold text-slate-800 mt-1"><?= htmlspecialchars($currency((float)$generationPreviewTotals['gross_pay']), ENT_QUOTES, 'UTF-8') ?></p>
                        </article>
                        <article class="rounded-xl border border-slate-200 p-4 bg-blue-50">
                            <p class="text-xs uppercase text-blue-700">Estimated Net</p>
                            <p id="generatePayrollPreviewNet" class="text-lg font-semibold text-slate-800 mt-1"><?= htmlspecialchars($currency((float)$generationPreviewTotals['net_pay']), ENT_QUOTES, 'UTF-8') ?></p>
                        </article>
                    </div>

                    <div id="generatePayrollPreviewTableWrap" class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="max-h-72 overflow-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 text-slate-600 sticky top-0">
                                    <tr>
                                        <th class="text-left px-4 py-3">Employee</th>
                                        <th class="text-left px-4 py-3">Pay Frequency</th>
                                        <th class="text-right px-4 py-3">Estimated Gross</th>
                                        <th class="text-right px-4 py-3">Estimated Net</th>
                                    </tr>
                                </thead>
                                <tbody id="generatePayrollPreviewBody" class="divide-y divide-slate-100">
                                    <?php foreach ($generationPreviewRows as $preview): ?>
                                        <tr>
                                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars((string)$preview['employee_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)$preview['pay_frequency'])), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="px-4 py-3 text-right text-slate-700"><?= htmlspecialchars($currency((float)$preview['gross_pay']), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="px-4 py-3 text-right text-slate-800 font-medium"><?= htmlspecialchars($currency((float)$preview['net_pay']), ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-1">
                    <button type="button" data-modal-close="generatePayrollSummaryModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button id="generatePayrollConfirmButton" type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800" <?= empty($generationPreviewRows) ? 'disabled aria-disabled="true"' : '' ?>>Confirm & Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script id="generationPreviewByPeriodData" type="application/json"><?= json_encode($generationPreviewByPeriod, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

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
