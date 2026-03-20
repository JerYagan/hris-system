<?php
$staffPayrollContentSection = (string)($staffPayrollContentSection ?? 'secondary');
$formatCurrency = static fn(float $amount): string => '₱' . number_format($amount, 2);
?>

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($staffPayrollContentSection === 'summary'): ?>
    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <article class="bg-white border rounded-xl p-4 min-h-[128px] flex flex-col justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Current Period</p>
                <p class="text-lg font-semibold text-slate-800 mt-2"><?= htmlspecialchars((string)($staffPayrollSummary['current_period_label'] ?? 'No payroll period found'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <p class="text-xs text-slate-500 mt-4"><?= htmlspecialchars((string)($staffPayrollSummary['current_period_status'] ?? 'No run yet'), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="bg-white border rounded-xl p-4 min-h-[128px] flex flex-col justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Current Net Total</p>
                <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars($formatCurrency((float)($staffPayrollSummary['current_period_net_total'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <p class="text-xs text-emerald-700 mt-4"><?= (int)($staffPayrollSummary['current_period_employee_count'] ?? 0) ?> employee(s) in the latest run</p>
        </article>
        <article class="bg-white border rounded-xl p-4 min-h-[128px] flex flex-col justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Pending Admin Review</p>
                <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)($staffPayrollSummary['pending_admin_review_count'] ?? 0) ?></p>
            </div>
            <p class="text-xs text-amber-700 mt-4">Computed staff runs still waiting for admin action</p>
        </article>
        <article class="bg-white border rounded-xl p-4 min-h-[128px] flex flex-col justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Ready For Payslips</p>
                <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)($staffPayrollSummary['approved_runs_count'] ?? 0) ?></p>
            </div>
            <p class="text-xs text-blue-700 mt-4"><?= (int)($staffPayrollSummary['open_periods_count'] ?? 0) ?> open or processing payroll period(s)</p>
        </article>
    </section>

    <section class="bg-white border rounded-xl p-6 mb-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Current Payroll Snapshot</h2>
                <p class="text-sm text-gray-500 mt-1">Use the latest payroll period as the starting point, then load periods, staff recommendations, and payslip actions in the deferred workflow region.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm min-w-0 lg:min-w-[420px]">
                <article class="rounded-xl border bg-slate-50 px-4 py-3">
                    <p class="text-xs uppercase text-slate-500">Latest Run</p>
                    <p class="text-base font-semibold text-slate-800 mt-1"><?= htmlspecialchars((string)($staffPayrollSummary['current_run_status_label'] ?? 'No run yet'), ENT_QUOTES, 'UTF-8') ?></p>
                </article>
                <article class="rounded-xl border bg-violet-50 px-4 py-3">
                    <p class="text-xs uppercase text-violet-700">Latest Gross</p>
                    <p class="text-base font-semibold text-slate-800 mt-1"><?= htmlspecialchars($formatCurrency((float)($staffPayrollSummary['current_period_gross_total'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></p>
                </article>
                <article class="rounded-xl border bg-emerald-50 px-4 py-3">
                    <p class="text-xs uppercase text-emerald-700">Released Runs</p>
                    <p class="text-base font-semibold text-slate-800 mt-1"><?= (int)($staffPayrollSummary['released_runs_count'] ?? 0) ?></p>
                </article>
            </div>
        </div>
    </section>
<?php else: ?>
    <?php require __DIR__ . '/view.php'; ?>
<?php endif; ?>
