<?php
require_once __DIR__ . '/includes/payroll-management/bootstrap.php';
require_once __DIR__ . '/includes/payroll-management/actions.php';

$pageTitle = 'Payroll Management | Staff';
$activePage = 'payroll-management.php';
$breadcrumbs = ['Payroll Management'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/staff/payroll-management/index.js';

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$staffPayrollPartial = (string)($_GET['partial'] ?? '');
if (in_array($staffPayrollPartial, ['staff-payroll-summary', 'staff-payroll-secondary'], true)) {
    $staffPayrollDataStage = $staffPayrollPartial === 'staff-payroll-summary' ? 'summary' : 'secondary';
    $staffPayrollContentSection = $staffPayrollDataStage;
    require_once __DIR__ . '/includes/payroll-management/data.php';
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    require __DIR__ . '/includes/payroll-management/content.php';
    exit;
}

ob_start();
?>
<div id="payrollFlashState" class="hidden" data-state="<?= htmlspecialchars((string)$state, ENT_QUOTES, 'UTF-8') ?>" data-message="<?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>"></div>

<?php if ($state && $message): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="bg-white border rounded-xl mb-6 px-6 py-5">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Payroll Management</h1>
            <p class="text-sm text-gray-500 mt-1">Load payroll context first, then defer runs, salary adjustments, and payslip generation workflows until after first paint.</p>
        </div>
        <a href="#staffPayrollSecondaryRegion" class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 hover:text-slate-900">
            <span class="material-symbols-outlined text-base">payments</span>
            Jump to payroll workflows
        </a>
    </div>
</section>

<section
    id="staffPayrollAsyncRegion"
    data-payroll-summary-url="payroll-management.php?partial=staff-payroll-summary"
    data-payroll-secondary-url="payroll-management.php?partial=staff-payroll-secondary"
>
    <div id="staffPayrollSummarySkeleton" class="space-y-4 mb-6" aria-live="polite" role="status">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <?php for ($index = 0; $index < 4; $index += 1): ?>
                <article class="bg-white border rounded-xl p-4 min-h-[128px]">
                    <div class="h-4 w-28 animate-pulse rounded bg-slate-200"></div>
                    <div class="mt-4 h-8 w-24 animate-pulse rounded bg-slate-200"></div>
                    <div class="mt-4 h-3 w-full animate-pulse rounded bg-slate-200"></div>
                </article>
            <?php endfor; ?>
        </div>
        <section class="bg-white border rounded-xl p-6">
            <div class="h-5 w-44 animate-pulse rounded bg-slate-200 mb-4"></div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php for ($index = 0; $index < 3; $index += 1): ?>
                    <article class="rounded-xl border p-4">
                        <div class="h-4 w-20 animate-pulse rounded bg-slate-200"></div>
                        <div class="mt-3 h-7 w-16 animate-pulse rounded bg-slate-200"></div>
                    </article>
                <?php endfor; ?>
            </div>
        </section>
    </div>

    <div id="staffPayrollSummaryError" class="hidden mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" aria-live="polite">
        <p class="font-medium">Payroll summary could not be loaded.</p>
        <p class="mt-1">Retry to load the current cutoff and staff payroll queue overview.</p>
        <button type="button" id="staffPayrollSummaryRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry summary</button>
    </div>

    <div id="staffPayrollSummaryContent" class="hidden"></div>

    <div id="staffPayrollSecondaryRegion">
        <div id="staffPayrollSecondarySkeleton" class="space-y-6" aria-live="polite" role="status">
            <?php for ($section = 0; $section < 3; $section += 1): ?>
                <section class="bg-white border rounded-xl p-6">
                    <div class="h-5 w-52 animate-pulse rounded bg-slate-200 mb-4"></div>
                    <div class="h-64 animate-pulse rounded bg-slate-100"></div>
                </section>
            <?php endfor; ?>
        </div>

        <div id="staffPayrollSecondaryError" class="hidden rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" aria-live="polite">
            <p class="font-medium">Payroll workflows could not be loaded.</p>
            <p class="mt-1">Retry to load payroll periods, staff adjustments, and payslip workflows.</p>
            <button type="button" id="staffPayrollSecondaryRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry payroll workflows</button>
        </div>

        <div id="staffPayrollSecondaryContent" class="hidden"></div>
    </div>
</section>
<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
