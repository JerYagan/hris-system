<?php
require_once __DIR__ . '/includes/reports/bootstrap.php';
require_once __DIR__ . '/includes/reports/actions.php';

if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

$pageTitle = 'Reports and Analytics | Staff';
$activePage = 'reports.php';
$breadcrumbs = ['Reports and Analytics'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/staff/reports/index.js';

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$staffReportsPartial = (string)($_GET['partial'] ?? '');
$staffReportsPartialMap = [
    'report-summary' => 'summary',
    'report-export-form' => 'export-form',
    'report-workforce' => 'workforce',
    'report-timekeeping' => 'timekeeping',
    'report-payroll' => 'payroll',
    'report-recruitment' => 'recruitment',
];

if (isset($staffReportsPartialMap[$staffReportsPartial])) {
    $staffReportsDataStage = $staffReportsPartialMap[$staffReportsPartial];
    require_once __DIR__ . '/includes/reports/data.php';
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    require __DIR__ . '/includes/reports/content.php';
    exit;
}

ob_start();
?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Reports and Analytics</h1>
    <p class="text-sm text-gray-500">Review staff report summaries first, request detailed datasets only when needed, and keep export generation separate from the page shell.</p>
</div>

<?php if ($state && $message): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section
    id="staffReportsAsyncRegion"
    data-report-summary-url="reports.php?partial=report-summary"
    data-report-export-url="reports.php?partial=report-export-form"
    data-report-workforce-url="reports.php?partial=report-workforce"
    data-report-timekeeping-url="reports.php?partial=report-timekeeping"
    data-report-payroll-url="reports.php?partial=report-payroll"
    data-report-recruitment-url="reports.php?partial=report-recruitment"
>
    <div id="staffReportsSummarySkeleton" class="space-y-6" aria-live="polite" role="status">
        <?php for ($section = 0; $section < 2; $section += 1): ?>
            <section class="bg-white border border-slate-200 rounded-2xl p-6">
                <div class="h-6 w-64 rounded bg-slate-200 animate-pulse"></div>
                <div class="h-4 w-80 max-w-full rounded bg-slate-200 animate-pulse mt-2"></div>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
                    <?php for ($card = 0; $card < 4; $card += 1): ?>
                        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50 min-h-[112px]">
                            <div class="h-4 w-28 rounded bg-slate-200 animate-pulse"></div>
                            <div class="h-8 w-16 rounded bg-slate-200 animate-pulse mt-4"></div>
                            <div class="h-3 w-full rounded bg-slate-200 animate-pulse mt-3"></div>
                        </article>
                    <?php endfor; ?>
                </div>
            </section>
        <?php endfor; ?>
    </div>

    <div id="staffReportsSummaryError" class="hidden mb-6 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm" aria-live="polite">
        <p class="font-medium">Staff report summary could not be loaded.</p>
        <p class="mt-1">Retry to fetch the shell-first headline metrics.</p>
        <button type="button" id="staffReportsSummaryRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry summary</button>
    </div>

    <div id="staffReportsSummaryContent" class="hidden"></div>

    <section class="bg-white border border-slate-200 rounded-2xl mb-6">
        <header class="px-6 py-4 border-b border-slate-200">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">Detailed Report Requests</h2>
                    <p class="text-sm text-slate-500 mt-1">Timekeeping, payroll, recruitment, and workforce datasets stay out of first paint until you explicitly open them.</p>
                </div>
                <nav id="staffReportsDatasetTabs" class="grid w-full grid-cols-1 gap-2 sm:grid-cols-2 xl:w-auto xl:min-w-[32rem] xl:grid-cols-2" aria-label="Staff report datasets">
                    <button type="button" class="staff-report-dataset-tab inline-flex w-full items-center justify-center rounded-full border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-medium text-slate-700 hover:bg-slate-50" data-staff-report-tab="workforce" data-staff-report-url="reports.php?partial=report-workforce">Workforce Directory</button>
                    <button type="button" class="staff-report-dataset-tab inline-flex w-full items-center justify-center rounded-full border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-medium text-slate-700 hover:bg-slate-50" data-staff-report-tab="timekeeping" data-staff-report-url="reports.php?partial=report-timekeeping">Timekeeping Trends</button>
                    <button type="button" class="staff-report-dataset-tab inline-flex w-full items-center justify-center rounded-full border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-medium text-slate-700 hover:bg-slate-50" data-staff-report-tab="payroll" data-staff-report-url="reports.php?partial=report-payroll">Payroll Summaries</button>
                    <button type="button" class="staff-report-dataset-tab inline-flex w-full items-center justify-center rounded-full border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-medium text-slate-700 hover:bg-slate-50" data-staff-report-tab="recruitment" data-staff-report-url="reports.php?partial=report-recruitment">Recruitment Metrics</button>
                </nav>
            </div>
        </header>

        <div id="staffReportsDatasetIdle" class="px-6 py-8 text-sm text-slate-600">
            <p class="font-medium text-slate-800">No heavy report dataset loaded yet.</p>
            <p class="mt-1">Choose a report above to fetch its filters and table only when you need that dataset.</p>
        </div>

        <div id="staffReportsDatasetLoading" class="hidden p-6" aria-live="polite" role="status">
            <div class="space-y-4">
                <div class="h-6 w-64 rounded bg-slate-200 animate-pulse"></div>
                <div class="h-4 w-80 max-w-full rounded bg-slate-200 animate-pulse"></div>
                <div class="h-64 rounded bg-slate-100 animate-pulse"></div>
            </div>
        </div>

        <div id="staffReportsDatasetError" class="hidden px-6 py-5 text-sm text-amber-700" aria-live="polite">
            <p class="font-medium">This staff report dataset could not be loaded.</p>
            <p class="mt-1">Retry to fetch the selected report table and filters.</p>
            <button type="button" id="staffReportsDatasetRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry dataset</button>
        </div>

        <div id="staffReportsDatasetContent" class="hidden p-6"></div>
    </section>

    <div id="staffReportsExportSkeleton" class="bg-white border border-slate-200 rounded-2xl p-6" aria-live="polite" role="status">
        <div class="h-6 w-48 rounded bg-slate-200 animate-pulse"></div>
        <div class="h-4 w-80 max-w-full rounded bg-slate-200 animate-pulse mt-2"></div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
            <?php for ($field = 0; $field < 6; $field += 1): ?>
                <div>
                    <div class="h-4 w-24 rounded bg-slate-200 animate-pulse"></div>
                    <div class="h-10 rounded bg-slate-100 animate-pulse mt-2"></div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <div id="staffReportsExportError" class="hidden mt-6 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm" aria-live="polite">
        <p class="font-medium">Export controls could not be loaded.</p>
        <p class="mt-1">Retry to fetch the separate export form.</p>
        <button type="button" id="staffReportsExportRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry export</button>
    </div>

    <div id="staffReportsExportContent" class="hidden mt-6"></div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';