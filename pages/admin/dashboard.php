<?php
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/actions.php';

$pageTitle = 'Admin Dashboard | DA HRIS';
$activePage = 'dashboard.php';
$breadcrumbs = ['Admin Dashboard'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/admin/dashboard/index.js';

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$dashboardPartial = (string)($_GET['partial'] ?? '');
if (in_array($dashboardPartial, ['dashboard-summary', 'dashboard-secondary'], true)) {
	$dashboardDataStage = $dashboardPartial === 'dashboard-summary' ? 'summary' : 'secondary';
	$dashboardContentSection = $dashboardDataStage;
	require_once __DIR__ . '/includes/dashboard/data.php';
	header('Content-Type: text/html; charset=UTF-8');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	require __DIR__ . '/includes/dashboard/content.php';
	exit;
}

ob_start();
?>
<?php if ($state && $message): ?>
	<?php $alertClass = $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'; ?>
	<div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section
	id="adminDashboardAsyncRegion"
	data-dashboard-summary-url="dashboard.php?partial=dashboard-summary"
	data-dashboard-secondary-url="dashboard.php?partial=dashboard-secondary"
>
	<div id="adminDashboardSummarySkeleton" class="space-y-6" aria-live="polite" role="status">
		<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
			<?php for ($index = 0; $index < 6; $index += 1): ?>
				<article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[130px] flex flex-col justify-between">
					<div class="h-4 w-36 animate-pulse rounded bg-slate-200"></div>
					<div class="mt-4 h-8 w-20 animate-pulse rounded bg-slate-200"></div>
					<div class="mt-3 h-3 w-full animate-pulse rounded bg-slate-200"></div>
				</article>
			<?php endfor; ?>
		</div>
		<section class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
			<div class="h-5 w-48 animate-pulse rounded bg-slate-200 mb-4"></div>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
				<?php for ($index = 0; $index < 2; $index += 1): ?>
					<article class="rounded-xl border border-slate-200 p-4">
						<div class="h-4 w-28 animate-pulse rounded bg-slate-200"></div>
						<div class="mt-3 h-8 w-20 animate-pulse rounded bg-slate-200"></div>
					</article>
				<?php endfor; ?>
			</div>
		</section>
	</div>

	<div id="adminDashboardSummaryError" class="hidden mb-6 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm" aria-live="polite">
		<p class="font-medium">Dashboard summary could not be loaded.</p>
		<p class="mt-1">Retry to load the summary cards and headline metrics.</p>
		<button type="button" id="adminDashboardSummaryRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry summary</button>
	</div>

	<div id="adminDashboardSummaryContent" class="hidden"></div>

	<div id="adminDashboardSecondarySkeleton" class="space-y-6" aria-live="polite" role="status">
		<?php for ($section = 0; $section < 3; $section += 1): ?>
			<section class="bg-white border border-slate-200 rounded-2xl p-6">
				<div class="h-5 w-56 animate-pulse rounded bg-slate-200 mb-4"></div>
				<div class="h-56 animate-pulse rounded bg-slate-100"></div>
			</section>
		<?php endfor; ?>
	</div>

	<div id="adminDashboardSecondaryError" class="hidden rounded-lg border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm" aria-live="polite">
		<p class="font-medium">Dashboard widgets could not be loaded.</p>
		<p class="mt-1">Retry to load charts, filters, and secondary dashboard tables.</p>
		<button type="button" id="adminDashboardSecondaryRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry widgets</button>
	</div>

	<div id="adminDashboardSecondaryContent" class="hidden"></div>
</section>
<?php
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
