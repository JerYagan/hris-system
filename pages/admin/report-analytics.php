<?php
require_once __DIR__ . '/includes/report-analytics/bootstrap.php';
require_once __DIR__ . '/includes/report-analytics/actions.php';

$pageTitle = 'Reports and Analytics | Admin';
$activePage = 'report-analytics.php';
$breadcrumbs = ['Reports and Analytics'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/admin/report-analytics/index.js';

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);


$reportAnalyticsPartial = (string)($_GET['partial'] ?? '');
$reportAnalyticsPartialMap = [
	'report-summary' => 'summary',
	'report-workforce' => 'workforce',
	'report-demographics' => 'demographics',
	'report-turnover' => 'turnover',
	'report-operations' => 'operations',
];
if (isset($reportAnalyticsPartialMap[$reportAnalyticsPartial])) {
	$reportAnalyticsDataStage = $reportAnalyticsPartialMap[$reportAnalyticsPartial];
	require_once __DIR__ . '/includes/report-analytics/data.php';
	header('Content-Type: text/html; charset=UTF-8');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	require __DIR__ . '/includes/report-analytics/content.php';
	exit;
}

ob_start();
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

<section
	id="adminReportAnalyticsAsyncRegion"
	data-report-summary-url="report-analytics.php?partial=report-summary"
	data-report-workforce-url="report-analytics.php?partial=report-workforce"
	data-report-demographics-url="report-analytics.php?partial=report-demographics"
	data-report-turnover-url="report-analytics.php?partial=report-turnover"
	data-report-operations-url="report-analytics.php?partial=report-operations"
>
	<div id="adminReportAnalyticsSummarySkeleton" class="space-y-6" aria-live="polite" role="status">
		<section class="bg-white border border-slate-200 rounded-2xl mb-6">
			<header class="px-6 py-4 border-b border-slate-200">
				<div class="h-6 w-64 rounded bg-slate-200 animate-pulse"></div>
				<div class="h-4 w-80 max-w-full rounded bg-slate-200 animate-pulse mt-2"></div>
			</header>
			<div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
				<?php for ($index = 0; $index < 4; $index += 1): ?>
					<article class="rounded-xl border border-slate-200 p-4 bg-slate-50 min-h-[122px]">
						<div class="h-4 w-28 rounded bg-slate-200 animate-pulse"></div>
						<div class="h-8 w-16 rounded bg-slate-200 animate-pulse mt-4"></div>
						<div class="h-3 w-full rounded bg-slate-200 animate-pulse mt-3"></div>
					</article>
				<?php endfor; ?>
			</div>
			<div class="px-6 pb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
				<?php for ($index = 0; $index < 2; $index += 1): ?>
					<article class="rounded-xl border border-slate-200 p-4">
						<div class="h-4 w-32 rounded bg-slate-200 animate-pulse"></div>
						<div class="h-5 w-52 rounded bg-slate-200 animate-pulse mt-3"></div>
					</article>
				<?php endfor; ?>
			</div>
		</section>

		<section class="bg-white border border-slate-200 rounded-2xl mb-6 p-6">
			<div class="h-6 w-56 rounded bg-slate-200 animate-pulse"></div>
			<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
				<?php for ($index = 0; $index < 6; $index += 1): ?>
					<article class="rounded-xl border border-slate-200 p-4 bg-slate-50 min-h-[108px]">
						<div class="h-4 w-32 rounded bg-slate-200 animate-pulse"></div>
						<div class="h-8 w-16 rounded bg-slate-200 animate-pulse mt-4"></div>
					</article>
				<?php endfor; ?>
			</div>
		</section>
	</div>

	<div id="adminReportAnalyticsSummaryError" class="hidden mb-6 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm" aria-live="polite">
		<p class="font-medium">Report headline metrics could not be loaded.</p>
		<p class="mt-1">Retry to load the KPI cards and overview counts.</p>
		<button type="button" id="adminReportAnalyticsSummaryRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry KPIs</button>
	</div>

	<div id="adminReportAnalyticsSummaryContent" class="hidden"></div>

	<div id="adminReportAnalyticsSecondarySkeleton" class="space-y-6" aria-live="polite" role="status">
		<?php for ($section = 0; $section < 4; $section += 1): ?>
			<section class="bg-white border border-slate-200 rounded-2xl p-6">
				<div class="h-6 w-64 rounded bg-slate-200 animate-pulse"></div>
				<div class="h-4 w-96 max-w-full rounded bg-slate-200 animate-pulse mt-2"></div>
				<div class="h-64 rounded bg-slate-100 animate-pulse mt-6"></div>
			</section>
		<?php endfor; ?>
	</div>

	<div id="adminReportAnalyticsSecondaryError" class="hidden rounded-lg border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm" aria-live="polite">
		<p class="font-medium">Report analytics sections could not be loaded.</p>
		<p class="mt-1">Retry to load charts, tables, and export tools.</p>
		<button type="button" id="adminReportAnalyticsSecondaryRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry analytics</button>
	</div>

	<div id="adminReportAnalyticsSecondaryContent" class="hidden space-y-6">
		<section class="bg-white border border-slate-200 rounded-2xl">
			<header class="px-6 py-4 border-b border-slate-200">
				<div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
					<div>
						<h2 class="text-lg font-semibold text-slate-800">Analytics Workspace</h2>
						<p class="text-sm text-slate-500 mt-1">Charts and heavy report sections now load per tab so one slow region does not stall the whole page.</p>
					</div>
					<nav id="adminReportAnalyticsTabs" class="flex flex-wrap gap-2" aria-label="Report analytics sections">
						<button type="button" class="report-analytics-tab inline-flex items-center rounded-full border border-slate-300 bg-slate-900 px-4 py-2 text-sm font-medium text-white" data-report-analytics-tab="workforce" data-report-analytics-url="report-analytics.php?partial=report-workforce">Workforce</button>
						<button type="button" class="report-analytics-tab inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" data-report-analytics-tab="demographics" data-report-analytics-url="report-analytics.php?partial=report-demographics">Demographics</button>
						<button type="button" class="report-analytics-tab inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" data-report-analytics-tab="turnover" data-report-analytics-url="report-analytics.php?partial=report-turnover">Turnover & Training</button>
						<button type="button" class="report-analytics-tab inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" data-report-analytics-tab="operations" data-report-analytics-url="report-analytics.php?partial=report-operations">Operations & Export</button>
					</nav>
				</div>
			</header>

			<div id="adminReportAnalyticsTabLoading" class="hidden p-6" aria-live="polite" role="status">
				<div class="space-y-4">
					<div class="h-6 w-56 rounded bg-slate-200 animate-pulse"></div>
					<div class="h-4 w-96 max-w-full rounded bg-slate-200 animate-pulse"></div>
					<div class="h-72 rounded bg-slate-100 animate-pulse"></div>
				</div>
			</div>

			<div id="adminReportAnalyticsTabError" class="hidden px-6 py-5 text-sm text-amber-700" aria-live="polite">
				<p class="font-medium">This analytics tab could not be loaded.</p>
				<p class="mt-1">Retry to fetch the selected analytics section.</p>
				<button type="button" id="adminReportAnalyticsTabRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry section</button>
			</div>

			<div id="adminReportAnalyticsTabContent" class="p-6"></div>
		</section>
	</div>
</section>
<?php
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
