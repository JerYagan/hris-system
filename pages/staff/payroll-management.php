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
    <p class="text-sm text-gray-500">Review payroll periods, monitor runs, and manage status transitions with approval-safe actions.</p>
</div>

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

<section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Open Periods</p>
        <p class="text-2xl font-semibold text-violet-700 mt-1"><?= (int)($payrollMetrics['open_periods'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Processing Periods</p>
        <p class="text-2xl font-semibold text-amber-700 mt-1"><?= (int)($payrollMetrics['processing_periods'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Active Runs</p>
        <p class="text-2xl font-semibold text-blue-700 mt-1"><?= (int)($payrollMetrics['active_runs'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Released Runs</p>
        <p class="text-2xl font-semibold text-emerald-700 mt-1"><?= (int)($payrollMetrics['released_runs'] ?? 0) ?></p>
    </article>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Payroll Periods</h2>
            <p class="text-sm text-gray-500 mt-1">Track payroll period lifecycle and update status using controlled transitions.</p>
        </div>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="payrollPeriodSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="payrollPeriodSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by period code, date range, or status">
        </div>
        <div>
            <label for="payrollPeriodStatusFilter" class="text-sm text-gray-600">All Statuses</label>
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
                        <tr data-payroll-period-row data-payroll-period-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-payroll-period-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars((string)($row['period_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['period_range'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['payout_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <p><?= (int)($row['run_count'] ?? 0) ?> run(s)</p>
                                <p class="text-xs text-gray-500 mt-1"><?= (int)($row['employee_count'] ?? 0) ?> employee(s)</p>
                            </td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Open'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-open-period-modal
                                    data-period-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-period-code="<?= htmlspecialchars((string)($row['period_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status-label="<?= htmlspecialchars((string)($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    class="px-3 py-1.5 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100"
                                >
                                    Update Status
                                </button>
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
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Payroll Runs</h2>
        <p class="text-sm text-gray-500 mt-1">Review run output and progress status from draft to release lifecycle.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="payrollRunSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="payrollRunSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by run ID, period code, office, or status">
        </div>
        <div>
            <label for="payrollRunStatusFilter" class="text-sm text-gray-600">All Statuses</label>
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
                    <th class="text-left px-4 py-3">Office</th>
                    <th class="text-left px-4 py-3">Employees</th>
                    <th class="text-left px-4 py-3">Gross / Net</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($payrollRunRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="7">No payroll runs found in your current scope.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payrollRunRows as $row): ?>
                        <tr data-payroll-run-row data-payroll-run-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-payroll-run-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars((string)($row['short_id'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['period_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
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
                                <button
                                    type="button"
                                    data-open-run-modal
                                    data-run-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-run-short-id="<?= htmlspecialchars((string)($row['short_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status-label="<?= htmlspecialchars((string)($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    class="px-3 py-1.5 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100"
                                >
                                    Update Status
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="payrollRunFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="7">No payroll runs match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<div id="payrollPeriodStatusModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Review Payroll Period Status</h3>
            <button type="button" id="payrollPeriodModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal"><span class="material-symbols-outlined">close</span></button>
        </div>

        <form id="payrollPeriodForm" method="POST" action="payroll-management.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="update_payroll_period_status">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="period_id" id="payrollPeriodId" value="">

            <div>
                <label class="text-gray-600">Period</label>
                <p id="payrollPeriodCode" class="mt-1 text-sm font-medium text-gray-800">-</p>
            </div>
            <div>
                <label class="text-gray-600">Current Status</label>
                <p id="payrollPeriodCurrentStatus" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label for="payrollPeriodNewStatus" class="text-gray-600">Decision</label>
                <select id="payrollPeriodNewStatus" name="new_status" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select status</option>
                    <option value="open">Open</option>
                    <option value="processing">Processing</option>
                    <option value="posted">Posted</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div>
                <label for="payrollPeriodNotes" class="text-gray-600">Notes</label>
                <textarea id="payrollPeriodNotes" name="status_notes" rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Add period status notes."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="payrollPeriodModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="payrollPeriodSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Decision</button>
            </div>
        </form>
    </div>
</div>

<div id="payrollRunStatusModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Review Payroll Run Status</h3>
            <button type="button" id="payrollRunModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal"><span class="material-symbols-outlined">close</span></button>
        </div>

        <form id="payrollRunForm" method="POST" action="payroll-management.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="update_payroll_run_status">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="run_id" id="payrollRunId" value="">

            <div>
                <label class="text-gray-600">Run</label>
                <p id="payrollRunCode" class="mt-1 text-sm font-medium text-gray-800">-</p>
            </div>
            <div>
                <label class="text-gray-600">Current Status</label>
                <p id="payrollRunCurrentStatus" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label for="payrollRunNewStatus" class="text-gray-600">Decision</label>
                <select id="payrollRunNewStatus" name="new_status" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select status</option>
                    <option value="draft">Draft</option>
                    <option value="computed">Computed</option>
                    <option value="approved">Approved</option>
                    <option value="released">Released</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <label for="payrollRunNotes" class="text-gray-600">Notes</label>
                <textarea id="payrollRunNotes" name="status_notes" rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Add run status notes."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="payrollRunModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="payrollRunSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Decision</button>
            </div>
        </form>
    </div>
</div>

<script src="../../assets/js/staff/payroll-management/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
