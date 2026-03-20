<?php
$currency = static fn(float $amount): string => '₱' . number_format($amount, 2);

$payslipStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    if ($key === 'released') {
        return ['Released', 'bg-emerald-100 text-emerald-800'];
    }

    return ['Pending', 'bg-amber-100 text-amber-800'];
};
?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">View Employee Payslips</h2>
        <p class="text-sm text-slate-500 mt-1">Track generated payslip records and release status by employee payroll item. Breakdown details load when you open a payslip.</p>
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
                    <th class="text-left px-4 py-3">Net Pay</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($payslipTableRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="5">No payroll items found.</td>
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
                            <td class="px-4 py-3"><?= htmlspecialchars($currency((float)$row['net_pay']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[95px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" data-open-admin-payslip-breakdown data-payroll-item-id="<?= htmlspecialchars((string)($row['item_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 shadow-sm"><span class="material-symbols-outlined text-[15px]">receipt_long</span>View Breakdown</button>
                                    <?php if (!empty($row['pdf_storage_path'])): ?>
                                        <a href="<?= htmlspecialchars((string)$row['pdf_storage_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"><span class="material-symbols-outlined text-[15px]">description</span>View Payslip</a>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1.5 text-xs rounded-md border border-slate-200 text-slate-400 bg-slate-50">Not Uploaded</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="px-6 pb-4 flex items-center justify-between gap-3 text-sm">
        <p id="payrollPayslipsPageInfo" class="text-slate-500">Showing 0 to 0 of 0 entries</p>
        <div class="flex items-center gap-2">
            <button id="payrollPayslipsPrev" type="button" class="px-3 py-1.5 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Previous</button>
            <span id="payrollPayslipsPageLabel" class="text-slate-500 min-w-[88px] text-center">Page 1 of 1</span>
            <button id="payrollPayslipsNext" type="button" class="px-3 py-1.5 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<div id="adminPayslipBreakdownModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="adminPayslipBreakdownModal"></div>
    <div class="relative min-h-full flex items-start sm:items-center justify-center p-4 sm:p-6 overflow-y-auto">
        <div class="w-full max-w-4xl max-h-[92vh] overflow-y-auto bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Payslip Breakdown</h3>
                <button type="button" data-modal-close="adminPayslipBreakdownModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <div class="p-6 space-y-4 text-sm">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <p class="text-slate-500 text-xs uppercase">Employee</p>
                        <p id="adminPayslipBreakdownEmployee" class="font-medium text-slate-800 mt-1">-</p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-xs uppercase">Period</p>
                        <p id="adminPayslipBreakdownPeriod" class="font-medium text-slate-800 mt-1">-</p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-xs uppercase">Payslip No.</p>
                        <p id="adminPayslipBreakdownNo" class="font-medium text-slate-800 mt-1">-</p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-xs uppercase">Status</p>
                        <p id="adminPayslipBreakdownStatus" class="font-medium text-slate-800 mt-1">-</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 font-medium text-slate-700">Earnings</div>
                        <div id="adminPayslipBreakdownEarnings" class="divide-y divide-slate-100"></div>
                    </div>
                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 font-medium text-slate-700">Deductions</div>
                        <div id="adminPayslipBreakdownDeductions" class="divide-y divide-slate-100"></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <article class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-500">Gross Pay</p>
                        <p id="adminPayslipBreakdownGross" class="text-base font-semibold text-slate-800 mt-1">₱0.00</p>
                    </article>
                    <article class="rounded-lg border border-slate-200 bg-rose-50 px-4 py-3">
                        <p class="text-xs text-rose-700">Total Deductions</p>
                        <p id="adminPayslipBreakdownTotalDeductions" class="text-base font-semibold text-rose-700 mt-1">₱0.00</p>
                    </article>
                    <article class="rounded-lg border border-slate-200 bg-emerald-50 px-4 py-3">
                        <p class="text-xs text-emerald-700">Net Pay</p>
                        <p id="adminPayslipBreakdownNet" class="text-base font-semibold text-emerald-700 mt-1">₱0.00</p>
                    </article>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                    <p id="adminPayslipBreakdownAttendance" class="text-xs text-slate-600">Attendance impact: -</p>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 flex justify-end">
                <button type="button" data-modal-close="adminPayslipBreakdownModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Close</button>
            </div>
        </div>
    </div>
</div>
