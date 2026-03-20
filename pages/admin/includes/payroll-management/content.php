<?php
$payrollContentSection = (string)($payrollContentSection ?? 'full');

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
    if ($key === 'pending_review') {
        return ['Pending Review', 'bg-amber-100 text-amber-800'];
    }
    if ($key === 'cancelled') {
        return ['Cancelled', 'bg-rose-100 text-rose-800'];
    }

    return ['Draft', 'bg-amber-100 text-amber-800'];
};
?>

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm"><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($payrollContentSection === 'summary'): ?>
    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[132px] flex flex-col justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Current Cutoff</p>
                <p class="text-lg font-semibold text-slate-800 mt-2"><?= htmlspecialchars((string)($payrollSummary['current_cutoff_label'] ?? 'No payroll period found'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <p class="text-xs text-slate-500 mt-4"><?= htmlspecialchars((string)($payrollSummary['current_cutoff_status'] ?? 'No batch generated'), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[132px] flex flex-col justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Current Net Estimate</p>
                <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars($currency((float)($payrollSummary['current_cutoff_net'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <p class="text-xs text-emerald-700 mt-4">Gross <?= htmlspecialchars($currency((float)($payrollSummary['current_cutoff_gross'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[132px] flex flex-col justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Pending Reviews</p>
                <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)($payrollSummary['pending_batches'] ?? 0) ?></p>
            </div>
            <p class="text-xs text-amber-700 mt-4">Computed batches still awaiting admin action</p>
        </article>
        <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[132px] flex flex-col justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Ready To Release</p>
                <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)($payrollSummary['ready_to_release'] ?? 0) ?></p>
            </div>
            <p class="text-xs text-blue-700 mt-4"><?= (int)($payrollSummary['open_periods'] ?? 0) ?> open or processing payroll period(s)</p>
        </article>
    </section>

    <section class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Current Cutoff Snapshot</h2>
                <p class="text-sm text-slate-500 mt-1">Keep the overview focused on current payroll reporting, release readiness, and payslip activity.</p>
            </div>
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm min-w-0 lg:min-w-[420px]">
                <article class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs uppercase text-slate-500">Employees</p>
                    <p class="text-lg font-semibold text-slate-800 mt-1"><?= (int)($payrollSummary['current_cutoff_employees'] ?? 0) ?></p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-emerald-50 px-4 py-3">
                    <p class="text-xs uppercase text-emerald-700">Estimated Gross</p>
                    <p class="text-lg font-semibold text-slate-800 mt-1"><?= htmlspecialchars($currency((float)($payrollSummary['current_cutoff_gross'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-blue-50 px-4 py-3">
                    <p class="text-xs uppercase text-blue-700">Estimated Net</p>
                    <p class="text-lg font-semibold text-slate-800 mt-1"><?= htmlspecialchars($currency((float)($payrollSummary['current_cutoff_net'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></p>
                </article>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap justify-end gap-2">
            <button type="button" data-modal-open="reviewSalaryAdjustmentsModal" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm">
                <span class="material-symbols-outlined text-[15px]">rule_settings</span>Review Salary Adjustments
            </button>
            <button type="button" data-modal-open="releasePayslipsModal" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm">
                <span class="material-symbols-outlined text-[15px]">forward_to_inbox</span>Send Payslips
            </button>
        </div>
    </section>

    <?php
    $hidePayrollHeaderSection = true;
    $hidePayrollBatchesSection = true;
    $hidePayrollSalarySetupLogsSection = true;
    $hidePayrollEstimateHistorySection = true;
    $payrollRefreshTarget = 'summary';
    require __DIR__ . '/view.php';
    ?>
<?php elseif ($payrollContentSection === 'setup'): ?>
    <?php
    $hidePayrollPayslipsSection = true;
    $hidePayrollBatchesSection = true;
    $payrollRefreshTarget = 'setup';
    require __DIR__ . '/view.php';
    ?>
<?php elseif ($payrollContentSection === 'batches'): ?>
    <?php
    $currentBatchPage = (int)($payrollBatchPagination['page'] ?? 1);
    $totalBatchPages = (int)($payrollBatchPagination['total_pages'] ?? 1);
    $canGoPrev = $currentBatchPage > 1;
    $canGoNext = $currentBatchPage < $totalBatchPages;
    ?>
    <section class="bg-white border border-slate-200 rounded-2xl mb-6">
        <header class="px-6 py-4 border-b border-slate-200 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Approve Payroll Batches</h2>
                <p class="text-sm text-slate-500 mt-1">Review computed payroll batches without leaving the main payroll workspace.</p>
            </div>
            <button type="button" data-payroll-refresh-tab="batches" class="inline-flex items-center gap-1.5 self-start rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                <span class="material-symbols-outlined text-[15px]">refresh</span>Refresh Table
            </button>
        </header>

        <form id="payrollBatchesFilters" class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
            <input type="hidden" id="payrollBatchesPage" name="payroll_batch_page" value="<?= (int)($payrollBatchFilters['page'] ?? 1) ?>">
            <div class="w-full md:w-1/2">
                <label class="text-sm text-slate-600" for="payrollBatchesSearch">Search Batches</label>
                <input id="payrollBatchesSearch" name="payroll_batch_search" type="search" value="<?= htmlspecialchars((string)($payrollBatchFilters['search_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by period, status, or batch ID">
            </div>
            <div class="w-full md:w-56">
                <label class="text-sm text-slate-600" for="payrollBatchesStatusFilter">Status Filter</label>
                <select id="payrollBatchesStatusFilter" name="payroll_batch_status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Status</option>
                    <option value="pending_review" <?= (($payrollBatchFilters['status'] ?? '') === 'pending_review') ? 'selected' : '' ?>>Pending Review</option>
                    <option value="draft" <?= (($payrollBatchFilters['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Draft</option>
                    <option value="computed" <?= (($payrollBatchFilters['status'] ?? '') === 'computed') ? 'selected' : '' ?>>Computed</option>
                    <option value="approved" <?= (($payrollBatchFilters['status'] ?? '') === 'approved') ? 'selected' : '' ?>>Approved</option>
                    <option value="released" <?= (($payrollBatchFilters['status'] ?? '') === 'released') ? 'selected' : '' ?>>Released</option>
                    <option value="cancelled" <?= (($payrollBatchFilters['status'] ?? '') === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
        </form>

        <?php if (!empty($batchRows)): ?>
            <form id="payrollBulkDeleteBatchesForm" action="payroll-management.php" method="POST" class="px-6 pb-2 flex justify-end">
                <input type="hidden" name="form_action" value="delete_payroll_batch_bulk">
                <button id="payrollBulkDeleteBatchesButton" type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs rounded-lg border border-rose-300 bg-white text-rose-700 hover:bg-rose-50 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed" disabled aria-disabled="true">
                    <span class="material-symbols-outlined text-[15px]">delete</span>Delete Selected
                </button>
            </form>
        <?php endif; ?>

        <div class="p-6 overflow-x-auto">
            <table id="payrollBatchesTable" class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3 w-12">
                            <input id="payrollBatchesSelectAll" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                        </th>
                        <th class="text-left px-4 py-3">Batch ID</th>
                        <th class="text-left px-4 py-3">Cutoff Period</th>
                        <th class="text-left px-4 py-3">Employee Count</th>
                        <th class="text-left px-4 py-3">Total Net Pay</th>
                        <th class="text-left px-4 py-3">Staff Recommendation</th>
                        <th class="text-left px-4 py-3">Adjustment Review</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($batchRows)): ?>
                        <tr>
                            <td class="px-4 py-3 text-slate-500" colspan="9">No payroll batches found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($batchRows as $batch): ?>
                            <?php [$statusLabel, $statusClass] = $runStatusPill((string)($batch['status'] ?? 'draft')); ?>
                            <tr class="hover:bg-slate-100 transition-colors">
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="run_ids[]" value="<?= htmlspecialchars((string)($batch['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" form="payrollBulkDeleteBatchesForm" data-payroll-batch-select class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                                </td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($batch['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($batch['period_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($batch['employee_count'] ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($currency((float)($batch['total_net'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3">
                                    <p class="text-slate-700"><?= htmlspecialchars((string)($batch['staff_recommendation'] ?? 'Not yet submitted by Staff'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars(formatDateTimeForPhilippines((string)($batch['staff_submitted_at'] ?? ''), 'M d, Y h:i A'), ENT_QUOTES, 'UTF-8') ?></p>
                                </td>
                                <td class="px-4 py-3">
                                    <?php
                                    $adjustmentSubmitted = (int)($batch['adjustment_submitted_count'] ?? 0);
                                    $adjustmentPending = (int)($batch['adjustment_pending_count'] ?? 0);
                                    $adjustmentApproved = (int)($batch['adjustment_approved_count'] ?? 0);
                                    $adjustmentRejected = (int)($batch['adjustment_rejected_count'] ?? 0);
                                    ?>
                                    <?php if ($adjustmentSubmitted > 0): ?>
                                        <p class="text-slate-700"><?= htmlspecialchars((string)$adjustmentSubmitted, ENT_QUOTES, 'UTF-8') ?> submitted</p>
                                        <p class="text-xs mt-1 <?= $adjustmentPending > 0 ? 'text-rose-600' : 'text-slate-500' ?>">Pending: <?= htmlspecialchars((string)$adjustmentPending, ENT_QUOTES, 'UTF-8') ?> · Approved: <?= htmlspecialchars((string)$adjustmentApproved, ENT_QUOTES, 'UTF-8') ?> · Rejected: <?= htmlspecialchars((string)$adjustmentRejected, ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php else: ?>
                                        <p class="text-slate-500 text-xs">No staff-submitted adjustments</p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3"><span class="inline-flex items-center justify-center text-center min-w-[95px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            data-payroll-review
                                            data-run-id="<?= htmlspecialchars((string)($batch['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-period-label="<?= htmlspecialchars((string)($batch['period_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-current-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>"
                                            data-employee-count="<?= htmlspecialchars((string)($batch['employee_count'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"
                                            data-total-net="<?= htmlspecialchars($currency((float)($batch['total_net'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>"
                                            data-staff-recommendation="<?= htmlspecialchars((string)($batch['staff_recommendation'] ?? 'Not yet submitted by Staff'), ENT_QUOTES, 'UTF-8') ?>"
                                            data-staff-submitted="<?= htmlspecialchars(formatDateTimeForPhilippines((string)($batch['staff_submitted_at'] ?? ''), 'M d, Y h:i A'), ENT_QUOTES, 'UTF-8') ?>"
                                            data-admin-reviewed="<?= htmlspecialchars(formatDateTimeForPhilippines((string)($batch['admin_reviewed_at'] ?? ''), 'M d, Y h:i A'), ENT_QUOTES, 'UTF-8') ?>"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">rule</span>Review
                                        </button>

                                        <form action="payroll-management.php" method="POST" class="inline">
                                            <input type="hidden" name="form_action" value="delete_payroll_batch">
                                            <input type="hidden" name="run_id" value="<?= htmlspecialchars((string)($batch['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
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
        <div class="px-6 pb-4 flex items-center justify-between gap-3 text-sm">
            <p id="payrollBatchesPageInfo" class="text-slate-500">Showing <?= (int)($payrollBatchPagination['start'] ?? 0) ?> to <?= (int)($payrollBatchPagination['end'] ?? 0) ?> of <?= (int)($payrollBatchPagination['total_rows'] ?? 0) ?> entries</p>
            <div class="flex items-center gap-2">
                <button id="payrollBatchesPrev" type="button" data-payroll-batches-page="<?= $canGoPrev ? $currentBatchPage - 1 : 1 ?>" class="px-3 py-1.5 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed" <?= $canGoPrev ? '' : 'disabled aria-disabled="true"' ?>>Previous</button>
                <span id="payrollBatchesPageLabel" class="text-slate-500 min-w-[88px] text-center">Page <?= $currentBatchPage ?> of <?= $totalBatchPages ?></span>
                <button id="payrollBatchesNext" type="button" data-payroll-batches-page="<?= $canGoNext ? $currentBatchPage + 1 : $totalBatchPages ?>" class="px-3 py-1.5 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed" <?= $canGoNext ? '' : 'disabled aria-disabled="true"' ?>>Next</button>
            </div>
        </div>
    </section>

    <script id="payrollBatchBreakdownByRunData" type="application/json"><?= json_encode($batchBreakdownByRun, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

    <div id="reviewPayrollBatchModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-slate-900/60" data-modal-close="reviewPayrollBatchModal"></div>
        <div class="relative min-h-full flex items-start sm:items-center justify-center p-4 sm:p-6 overflow-y-auto">
            <div class="w-full max-w-6xl max-h-[92vh] overflow-y-auto bg-white rounded-2xl border border-slate-200 shadow-xl">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Review Payroll Batch</h3>
                    <button type="button" data-modal-close="reviewPayrollBatchModal" class="text-slate-500 hover:text-slate-700">✕</button>
                </div>
                <form id="reviewPayrollBatchForm" action="payroll-management.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm" data-confirm-title="Submit payroll batch decision?" data-confirm-text="This updates payroll batch status and related approval fields." data-confirm-button-text="Submit decision">
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
                    <div class="md:col-span-2">
                        <label class="text-slate-600">Staff Recommendation</label>
                        <textarea id="payrollBatchRecommendation" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></textarea>
                    </div>
                    <div>
                        <label class="text-slate-600">Submitted by Staff</label>
                        <input id="payrollBatchSubmittedAt" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                    </div>
                    <div>
                        <label class="text-slate-600">Last Admin Review</label>
                        <input id="payrollBatchReviewedAt" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                    </div>
                    <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Payroll Computation Breakdown</p>
                        <p class="text-xs text-slate-600 mt-1">Includes salary setup components, leave-card aligned deductions, and approved adjustment impact per employee.</p>
                        <div class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <p class="text-xs text-slate-500">Employees</p>
                                <p id="payrollBatchBreakdownEmployees" class="text-sm font-semibold text-slate-800 mt-1">0</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <p class="text-xs text-slate-500">Gross Total</p>
                                <p id="payrollBatchBreakdownGross" class="text-sm font-semibold text-slate-800 mt-1">₱0.00</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <p class="text-xs text-slate-500">Net Total</p>
                                <p id="payrollBatchBreakdownNet" class="text-sm font-semibold text-slate-800 mt-1">₱0.00</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <p class="text-xs text-slate-500">Rows</p>
                                <p id="payrollBatchBreakdownRows" class="text-sm font-semibold text-slate-800 mt-1">0</p>
                            </div>
                        </div>
                        <div class="mt-3 overflow-x-auto border border-slate-200 rounded-lg bg-white">
                            <table class="w-full text-xs">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="text-left px-3 py-2">Employee</th>
                                        <th class="text-right px-3 py-2">Basic Pay</th>
                                        <th class="text-right px-3 py-2">Allowances</th>
                                        <th class="text-right px-3 py-2">CTO Leave UT w/ Pay</th>
                                        <th class="text-right px-3 py-2">Statutory</th>
                                        <th class="text-right px-3 py-2">Timekeeping</th>
                                        <th class="text-right px-3 py-2">Late/Undertime</th>
                                        <th class="text-left px-3 py-2">Remarks</th>
                                        <th class="text-right px-3 py-2">Adj +/-</th>
                                        <th class="text-right px-3 py-2">Gross</th>
                                        <th class="text-right px-3 py-2">Net</th>
                                    </tr>
                                </thead>
                                <tbody id="payrollBatchBreakdownBody" class="divide-y divide-slate-100">
                                    <tr id="payrollBatchBreakdownEmptyRow">
                                        <td class="px-3 py-3 text-slate-500" colspan="11">No computation breakdown available for this batch.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-500">Salary Adjustment Recommendations</p>
                                <p class="text-xs text-slate-600 mt-1">Run-scoped staff recommendations and current admin review status for each adjustment.</p>
                            </div>
                            <div id="payrollBatchAdjustmentApprovalWarning" class="hidden rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                                Approval blocked: pending salary adjustment reviews in this batch.
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <p class="text-xs text-slate-500">Submitted</p>
                                <p id="payrollBatchAdjustmentSubmitted" class="text-sm font-semibold text-slate-800 mt-1">0</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <p class="text-xs text-slate-500">Pending Review</p>
                                <p id="payrollBatchAdjustmentPending" class="text-sm font-semibold text-rose-700 mt-1">0</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <p class="text-xs text-slate-500">Approved</p>
                                <p id="payrollBatchAdjustmentApproved" class="text-sm font-semibold text-emerald-700 mt-1">0</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <p class="text-xs text-slate-500">Rejected</p>
                                <p id="payrollBatchAdjustmentRejected" class="text-sm font-semibold text-rose-700 mt-1">0</p>
                            </div>
                        </div>
                        <div class="mt-3 overflow-x-auto border border-slate-200 rounded-lg bg-white">
                            <table class="w-full text-xs">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="text-left px-3 py-2">Code</th>
                                        <th class="text-left px-3 py-2">Employee</th>
                                        <th class="text-left px-3 py-2">Type</th>
                                        <th class="text-right px-3 py-2">Amount</th>
                                        <th class="text-left px-3 py-2">Staff Recommendation</th>
                                        <th class="text-left px-3 py-2">Admin Review</th>
                                    </tr>
                                </thead>
                                <tbody id="payrollBatchAdjustmentBody" class="divide-y divide-slate-100">
                                    <tr id="payrollBatchAdjustmentEmptyRow">
                                        <td class="px-3 py-3 text-slate-500" colspan="6">No staff-submitted salary adjustment recommendations in this batch.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div>
                        <label class="text-slate-600">Decision</label>
                        <select id="payrollBatchDecision" name="decision" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            <option value="approved">Approve</option>
                            <option value="cancelled">Cancel Batch</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-slate-600">Notes</label>
                        <textarea name="notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="State the reason for approve/cancel decision." required></textarea>
                    </div>
                    <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                        <button type="button" data-modal-close="reviewPayrollBatchModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Decision</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php require __DIR__ . '/view.php'; ?>
<?php endif; ?>
