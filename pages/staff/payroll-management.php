<?php
require_once __DIR__ . '/includes/payroll-management/bootstrap.php';
require_once __DIR__ . '/includes/payroll-management/actions.php';
require_once __DIR__ . '/includes/payroll-management/data.php';

$pageTitle = 'Payroll Management | Staff';
$activePage = 'payroll-management.php';
$breadcrumbs = ['Payroll Management'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Payroll Management</h1>
    <p class="text-sm text-gray-500">Manage company-level payroll workflows for monthly computation, salary adjustments, and payslip generation.</p>
</div>

<div id="payrollFlashState" class="hidden" data-state="<?= htmlspecialchars((string)$state, ENT_QUOTES, 'UTF-8') ?>" data-message="<?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>"></div>

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

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Compute Monthly Payroll</h2>
        <p class="text-sm text-gray-500 mt-1">View payroll periods and compute payroll per selected period.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="payrollPeriodSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="payrollPeriodSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by period code, date range, or status">
        </div>
        <div>
            <label for="payrollPeriodStatusFilter" class="text-sm text-gray-600">Status Filter</label>
            <select id="payrollPeriodStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="open">Open</option>
                <option value="processing">Processing</option>
                <option value="posted">Posted</option>
                <option value="closed">Closed</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="payrollPeriodsTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Period</th>
                    <th class="text-left px-4 py-3">Date Range</th>
                    <th class="text-left px-4 py-3">Payout Date</th>
                    <th class="text-left px-4 py-3">Runs</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($payrollPeriodRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="6">No payroll periods found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payrollPeriodRows as $row): ?>
                        <tr data-payroll-period-row data-payroll-period-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-payroll-period-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars((string)($row['period_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['period_range'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['payout_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <p><?= (int)($row['run_count'] ?? 0) ?> run(s)</p>
                                <p class="text-xs text-gray-500 mt-1"><?= (int)($row['employee_count'] ?? 0) ?> employee(s)</p>
                            </td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Open'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        data-open-compute-modal
                                        data-period-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-period-code="<?= htmlspecialchars((string)($row['period_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-md border border-violet-200 bg-violet-50 text-violet-700 hover:bg-violet-100 disabled:opacity-60 disabled:cursor-not-allowed"
                                        <?= strtolower((string)($row['status_raw'] ?? 'open')) === 'closed' ? 'disabled aria-disabled="true"' : '' ?>
                                    >
                                        <span class="material-symbols-outlined text-[14px]">calculate</span>
                                        Compute Payroll
                                    </button>
                                    <button
                                        type="button"
                                        data-open-export-modal
                                        data-period-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-period-code="<?= htmlspecialchars((string)($row['period_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-period-range="<?= htmlspecialchars((string)($row['period_range'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-md border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
                                    >
                                        <span class="material-symbols-outlined text-[14px]">download</span>
                                        Export CSV
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="payrollPeriodFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="6">No payroll periods match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Review Salary Adjustment</h2>
            <p class="text-sm text-gray-500 mt-1">Create salary adjustments for employees and review pending adjustments.</p>
        </div>
        <button type="button" id="openCreateSalaryAdjustmentModal" class="inline-flex items-center gap-1.5 justify-center px-3 py-2 text-xs rounded-md border border-violet-200 bg-violet-50 text-violet-700 hover:bg-violet-100">
            <span class="material-symbols-outlined text-[14px]">add</span>
            Create Salary Adjustment
        </button>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="salaryAdjustmentSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="salaryAdjustmentSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by adjustment code, employee, period, or description">
        </div>
        <div>
            <label for="salaryAdjustmentStatusFilter" class="text-sm text-gray-600">Status Filter</label>
            <select id="salaryAdjustmentStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="salaryAdjustmentsTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Adjustment Code</th>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Period</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Amount</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($payrollAdjustmentRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="7">No salary adjustments found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payrollAdjustmentRows as $row): ?>
                        <tr data-salary-adjustment-row data-salary-adjustment-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-salary-adjustment-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars((string)($row['adjustment_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['period_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['adjustment_type_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">₱<?= number_format((float)($row['amount'] ?? 0), 2) ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <?php if (strtolower((string)($row['status_raw'] ?? 'pending')) === 'pending'): ?>
                                    <button
                                        type="button"
                                        data-open-adjustment-modal
                                        data-adjustment-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-adjustment-code="<?= htmlspecialchars((string)($row['adjustment_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status="<?= htmlspecialchars((string)($row['status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?>"
                                        data-description="<?= htmlspecialchars((string)($row['description'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-md border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100"
                                    >
                                        <span class="material-symbols-outlined text-[13px]">gavel</span>
                                        Review Adjustment
                                    </button>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 text-xs rounded-md border border-slate-200 bg-slate-50 text-slate-700">
                                        <span class="material-symbols-outlined text-[13px]">verified</span>
                                        Reviewed by Staff
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="salaryAdjustmentFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="7">No salary adjustments match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Generate Payslip</h2>
        <p class="text-sm text-gray-500 mt-1">Generate payslips from approved payroll runs.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="payrollRunSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="payrollRunSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by run ID, period code, or status">
        </div>
        <div>
            <label for="payrollRunStatusFilter" class="text-sm text-gray-600">Status Filter</label>
            <select id="payrollRunStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="computed">Computed</option>
                <option value="approved">Approved</option>
                <option value="released">Released</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="payrollRunsTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Run ID</th>
                    <th class="text-left px-4 py-3">Period</th>
                    <th class="text-left px-4 py-3">Employees</th>
                    <th class="text-left px-4 py-3">Gross / Net</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($payrollRunRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="6">No payroll runs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payrollRunRows as $row): ?>
                        <tr data-payroll-run-row data-payroll-run-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-payroll-run-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars((string)($row['short_id'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['period_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <p><?= (int)($row['employee_count'] ?? 0) ?> employee(s)</p>
                                <p class="text-xs text-gray-500 mt-1"><?= (int)($row['released_count'] ?? 0) ?> payslip(s) released</p>
                            </td>
                            <td class="px-4 py-3">
                                <p>₱<?= number_format((float)($row['gross_total'] ?? 0), 2) ?></p>
                                <p class="text-xs text-gray-500 mt-1">Net: ₱<?= number_format((float)($row['net_total'] ?? 0), 2) ?></p>
                            </td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Draft'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        data-open-generate-modal
                                        data-run-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-run-short-id="<?= htmlspecialchars((string)($row['short_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-md border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 disabled:opacity-60 disabled:cursor-not-allowed"
                                        <?= !empty($row['can_generate']) ? '' : 'disabled aria-disabled="true"' ?>
                                    >
                                        <span class="material-symbols-outlined text-[14px]">receipt_long</span>
                                        Generate Payslip
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="payrollRunFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="6">No payroll runs match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<div id="exportPayrollCsvModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Export Payroll CSV</h3>
            <button type="button" id="exportPayrollCsvModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal"><span class="material-symbols-outlined">close</span></button>
        </div>

        <form id="exportPayrollCsvForm" method="POST" action="payroll-management.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="export_payroll_csv">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="period_id" id="exportPayrollPeriodId" value="">

            <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 space-y-1">
                <p><span class="text-slate-500">Period Code:</span> <span id="exportPayrollPeriodCode" class="font-medium text-slate-800">-</span></p>
                <p><span class="text-slate-500">Date Range:</span> <span id="exportPayrollPeriodRange" class="font-medium text-slate-800">-</span></p>
            </div>

            <p class="text-xs text-gray-500">Exports the complete payroll item dataset for the selected period.</p>

            <div class="flex justify-end gap-3">
                <button type="button" id="exportPayrollCsvModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="exportPayrollCsvSubmit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md bg-emerald-700 text-white hover:bg-emerald-800 disabled:opacity-60 disabled:cursor-not-allowed">
                    <span class="material-symbols-outlined text-[16px]">download</span>
                    Export CSV
                </button>
            </div>
        </form>
    </div>
</div>

<div id="computePayrollModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-2xl rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Compute Monthly Payroll</h3>
            <button type="button" id="computePayrollModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal"><span class="material-symbols-outlined">close</span></button>
        </div>

        <form id="computePayrollForm" method="POST" action="payroll-management.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="compute_monthly_payroll">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="period_id" id="computePayrollPeriodId" value="">

            <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs text-slate-500 uppercase tracking-wide">Selected Period</p>
                <p id="computePayrollPeriodLabel" class="font-semibold text-slate-800 mt-1">-</p>
            </div>

            <div>
                <p class="text-sm text-gray-700 mb-2">Employees included in this payroll period</p>
                <div class="max-h-72 overflow-auto border rounded-md">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-slate-600 sticky top-0">
                            <tr>
                                <th class="text-left px-3 py-2">Employee</th>
                                <th class="text-right px-3 py-2">Estimated Net</th>
                            </tr>
                        </thead>
                        <tbody id="computePayrollEmployeesBody" class="divide-y"></tbody>
                    </table>
                </div>
                <p id="computePayrollEmployeeCount" class="text-xs text-slate-500 mt-2">0 employee(s)</p>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" id="computePayrollModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="computePayrollSubmit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md bg-violet-700 text-white hover:bg-violet-800 disabled:opacity-60 disabled:cursor-not-allowed">
                    <span class="material-symbols-outlined text-[16px]">calculate</span>
                    Compute Payroll
                </button>
            </div>
        </form>
    </div>
</div>

<div id="salaryAdjustmentReviewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Review Salary Adjustment</h3>
            <button type="button" id="salaryAdjustmentModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal"><span class="material-symbols-outlined">close</span></button>
        </div>

        <form id="salaryAdjustmentForm" method="POST" action="payroll-management.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="review_salary_adjustment">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="adjustment_id" id="salaryAdjustmentId" value="">

            <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 space-y-1">
                <p><span class="text-slate-500">Adjustment Code:</span> <span id="salaryAdjustmentCode" class="font-medium text-slate-800">-</span></p>
                <p><span class="text-slate-500">Employee:</span> <span id="salaryAdjustmentEmployee" class="font-medium text-slate-800">-</span></p>
                <p><span class="text-slate-500">Current Status:</span> <span id="salaryAdjustmentCurrentStatus" class="font-medium text-slate-800">-</span></p>
                <p><span class="text-slate-500">Description:</span> <span id="salaryAdjustmentDescription" class="font-medium text-slate-800">-</span></p>
            </div>

            <div>
                <label for="salaryAdjustmentDecision" class="text-gray-600">Decision</label>
                <select id="salaryAdjustmentDecision" name="decision" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select decision</option>
                    <option value="approved">Approve</option>
                    <option value="rejected">Reject</option>
                </select>
            </div>

            <div>
                <label for="salaryAdjustmentNotes" class="text-gray-600">Review Notes</label>
                <textarea id="salaryAdjustmentNotes" name="review_notes" rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Optional notes for this decision."></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" id="salaryAdjustmentModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="salaryAdjustmentSubmit" class="px-4 py-2 rounded-md bg-violet-700 text-white hover:bg-violet-800 disabled:opacity-60 disabled:cursor-not-allowed">Save Decision</button>
            </div>
        </form>
    </div>
</div>

<div id="generatePayslipModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-2xl rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Generate Payslips</h3>
            <button type="button" id="generatePayslipModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal"><span class="material-symbols-outlined">close</span></button>
        </div>

        <form id="generatePayslipForm" method="POST" action="payroll-management.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="generate_payslip_run">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="run_id" id="generatePayslipRunId" value="">

            <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs text-slate-500 uppercase tracking-wide">Selected Run</p>
                <p id="generatePayslipRunLabel" class="font-semibold text-slate-800 mt-1">-</p>
            </div>

            <div>
                <p class="text-sm text-gray-700 mb-2">Employees included in this payroll run</p>
                <div class="max-h-72 overflow-auto border rounded-md">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-slate-600 sticky top-0">
                            <tr>
                                <th class="text-left px-3 py-2">Employee</th>
                                <th class="text-right px-3 py-2">Net Pay</th>
                            </tr>
                        </thead>
                        <tbody id="generatePayslipEmployeesBody" class="divide-y"></tbody>
                    </table>
                </div>
                <p id="generatePayslipEmployeeCount" class="text-xs text-slate-500 mt-2">0 employee(s)</p>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" id="generatePayslipModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="generatePayslipSubmit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md bg-blue-700 text-white hover:bg-blue-800 disabled:opacity-60 disabled:cursor-not-allowed">
                    <span class="material-symbols-outlined text-[16px]">receipt_long</span>
                    Generate Payslips
                </button>
            </div>
        </form>
    </div>
</div>

<div id="createSalaryAdjustmentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Create Salary Adjustment</h3>
            <button type="button" id="createSalaryAdjustmentModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal"><span class="material-symbols-outlined">close</span></button>
        </div>

        <form id="createSalaryAdjustmentForm" method="POST" action="payroll-management.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="create_salary_adjustment">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div>
                <label for="createSalaryAdjustmentItem" class="text-gray-600">Employee Payroll Item</label>
                <select id="createSalaryAdjustmentItem" name="payroll_item_id" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select employee and payroll run</option>
                    <?php foreach ($salaryAdjustmentCreateRows as $row): ?>
                        <option value="<?= htmlspecialchars((string)($row['item_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="createSalaryAdjustmentType" class="text-gray-600">Adjustment Type</label>
                <select id="createSalaryAdjustmentType" name="adjustment_type" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="deduction">Deduction</option>
                    <option value="earning">Earning</option>
                </select>
            </div>
            <div>
                <label for="createSalaryAdjustmentCode" class="text-gray-600">Adjustment Code</label>
                <input id="createSalaryAdjustmentCode" name="adjustment_code" type="text" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="e.g. ABSENCE, ALLOWANCE, CORRECTION">
            </div>
            <div>
                <label for="createSalaryAdjustmentDescription" class="text-gray-600">Description</label>
                <textarea id="createSalaryAdjustmentDescription" name="description" rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Enter reason/details for this adjustment." required></textarea>
            </div>
            <div>
                <label for="createSalaryAdjustmentAmount" class="text-gray-600">Amount</label>
                <input id="createSalaryAdjustmentAmount" name="amount" type="number" min="0.01" step="0.01" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="0.00" required>
            </div>
            <?php if (empty($salaryAdjustmentCreateRows)): ?>
                <p class="text-xs text-amber-700">No editable payroll items available. Generate payroll runs first before creating salary adjustments.</p>
            <?php endif; ?>
            <div class="flex justify-end gap-3">
                <button type="button" id="createSalaryAdjustmentModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="createSalaryAdjustmentSubmit" class="px-4 py-2 rounded-md bg-violet-700 text-white hover:bg-violet-800 disabled:opacity-60 disabled:cursor-not-allowed" <?= empty($salaryAdjustmentCreateRows) ? 'disabled aria-disabled="true"' : '' ?>>Create Adjustment</button>
            </div>
        </form>
    </div>
</div>

<script id="payrollComputePreviewData" type="application/json"><?= htmlspecialchars((string)json_encode($computePreviewByPeriod, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></script>
<script id="payrollGeneratePreviewData" type="application/json"><?= htmlspecialchars((string)json_encode($generatePreviewByRun, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></script>

<script src="../../assets/js/staff/payroll-management/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
