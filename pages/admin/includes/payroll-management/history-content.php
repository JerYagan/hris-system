<?php
$currency = static fn(float $amount): string => '₱' . number_format($amount, 2);

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
            <label class="text-sm text-slate-600">Entries per page</label>
            <select id="payrollSetupLogsPageSize" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="10" selected>10</option>
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
                <p id="payrollSetupLogsPageInfo" class="text-slate-500">Showing 0 to 0 of 0 entries</p>
                <div class="flex items-center gap-2">
                    <button id="payrollSetupLogsPrev" type="button" class="px-3 py-1.5 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Previous</button>
                    <span id="payrollSetupLogsPageLabel" class="text-slate-500 min-w-[88px] text-center">Page 1 of 1</span>
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

    <div class="px-6 pb-3 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Estimate History</label>
            <input id="payrollPeriodsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by period code, range, or status">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="payrollPeriodsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="Open">Open</option>
                <option value="Processing">Processing</option>
                <option value="Posted">Posted</option>
                <option value="Closed">Closed</option>
            </select>
        </div>
    </div>

    <?php if (!empty($payrollEstimateHistoryRows)): ?>
        <form id="payrollBulkDeletePeriodsForm" action="payroll-management.php" method="POST" class="px-6 pb-2 flex justify-end">
            <input type="hidden" name="form_action" value="delete_payroll_period_bulk">
            <button id="payrollBulkDeletePeriodsButton" type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs rounded-lg border border-rose-300 bg-white text-rose-700 hover:bg-rose-50 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed" disabled aria-disabled="true">
                <span class="material-symbols-outlined text-[15px]">delete</span>Delete Selected
            </button>
        </form>
    <?php endif; ?>

    <div id="payrollEstimateHistory" class="px-6 pb-6 overflow-x-auto">
        <table id="payrollPeriodsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3 w-12">
                        <input id="payrollPeriodsSelectAll" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                    </th>
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
                        <td class="px-4 py-3 text-slate-500" colspan="7">No period estimates available.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payrollEstimateHistoryRows as $history): ?>
                        <?php
                        $historyPeriodId = (string)($history['period_id'] ?? '');
                        $periodStatus = ucfirst((string)($history['status'] ?? 'open'));
                        $periodSearch = strtolower(trim((string)($history['period_code'] ?? '') . ' ' . (string)($history['period_label'] ?? '') . ' ' . $periodStatus));
                        ?>
                        <tr data-payroll-period-search="<?= htmlspecialchars($periodSearch, ENT_QUOTES, 'UTF-8') ?>" data-payroll-period-status="<?= htmlspecialchars($periodStatus, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <?php if ($historyPeriodId !== ''): ?>
                                    <input type="checkbox" name="period_ids[]" value="<?= htmlspecialchars($historyPeriodId, ENT_QUOTES, 'UTF-8') ?>" form="payrollBulkDeletePeriodsForm" data-payroll-period-select class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars((string)$history['period_code'] . ' • ' . (string)$history['period_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($periodStatus, ENT_QUOTES, 'UTF-8') ?></td>
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
    <div class="px-6 pb-4 flex items-center justify-between gap-3 text-sm">
        <p id="payrollPeriodsPageInfo" class="text-slate-500">Showing 0 to 0 of 0 entries</p>
        <div class="flex items-center gap-2">
            <button id="payrollPeriodsPrev" type="button" class="px-3 py-1.5 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Previous</button>
            <span id="payrollPeriodsPageLabel" class="text-slate-500 min-w-[88px] text-center">Page 1 of 1</span>
            <button id="payrollPeriodsNext" type="button" class="px-3 py-1.5 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>
