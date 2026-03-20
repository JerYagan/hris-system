<?php
require_once __DIR__ . '/includes/payroll-management/bootstrap.php';
require_once __DIR__ . '/includes/payroll-management/actions.php';

$pageTitle = 'Payroll Management | Admin';
$activePage = 'payroll-management.php';
$breadcrumbs = ['Payroll Management'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/admin/payroll-management/index.js';

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);
$renderPayrollTabs = static function (): string {
	$tabs = [
		['label' => 'Overview', 'icon' => 'monitoring', 'value' => 'summary'],
		['label' => 'Salary Setup', 'icon' => 'badge', 'value' => 'setup'],
		['label' => 'Approval Batches', 'icon' => 'fact_check', 'value' => 'batches'],
	];

	ob_start();
	?>
	<div class="flex flex-wrap items-center gap-2" role="tablist" aria-label="Payroll sections">
		<?php foreach ($tabs as $index => $tab): ?>
			<?php $isActive = $index === 0; ?>
			<button
				type="button"
				role="tab"
				data-payroll-tab="<?= htmlspecialchars($tab['value'], ENT_QUOTES, 'UTF-8') ?>"
				aria-selected="<?= $isActive ? 'true' : 'false' ?>"
				aria-controls="adminPayrollPanel<?= htmlspecialchars(ucfirst($tab['value']), ENT_QUOTES, 'UTF-8') ?>"
				class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-medium shadow-sm transition-colors <?= $isActive ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' ?>"
			>
				<span class="material-symbols-outlined text-[15px]"><?= htmlspecialchars($tab['icon'], ENT_QUOTES, 'UTF-8') ?></span>
				<?= htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8') ?>
			</button>
		<?php endforeach; ?>
	</div>
	<?php
	return (string)ob_get_clean();
};

$payrollPartial = (string)($_GET['partial'] ?? '');
if ($payrollPartial === 'payroll-payslip-detail') {
	$payrollManagementDataStage = 'payslip-detail';
	require_once __DIR__ . '/includes/payroll-management/data.php';
	$statusCode = is_array($adminPayslipBreakdownPayload ?? null) ? 200 : 404;
	http_response_code($statusCode);
	header('Content-Type: application/json; charset=UTF-8');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	echo (string)json_encode(
		is_array($adminPayslipBreakdownPayload ?? null)
			? $adminPayslipBreakdownPayload
			: ['error' => 'Payslip breakdown could not be loaded.'],
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	);
	exit;
}

if (in_array($payrollPartial, ['payroll-summary', 'payroll-setup', 'payroll-batches'], true)) {
	$payrollManagementDataStage = match ($payrollPartial) {
		'payroll-summary' => 'overview',
		'payroll-setup' => 'setup',
		'payroll-batches' => 'batches',
		default => 'summary',
	};
	$payrollContentSection = match ($payrollPartial) {
		'payroll-summary' => 'summary',
		'payroll-setup' => 'setup',
		default => $payrollManagementDataStage,
	};
	require_once __DIR__ . '/includes/payroll-management/data.php';
	header('Content-Type: text/html; charset=UTF-8');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	require __DIR__ . '/includes/payroll-management/content.php';
	exit;
}

ob_start();
?>
<div id="adminPayrollPageRoot" data-payroll-page-mode="tabbed" class="space-y-5">
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

<section class="rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-slate-100 px-6 py-6 shadow-sm">
	<div>
		<div>
			<p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Admin Payroll</p>
			<h1 class="mt-2 text-2xl font-semibold text-slate-900">Payroll Management</h1>
			<p class="mt-2 max-w-3xl text-sm text-slate-600">Keep the page focused on one workflow at a time. Open a tab to load its section, inspect QA timing in the console, and avoid dragging the rest of payroll tools into first render.</p>
		</div>
	</div>
</section>

<section
	id="adminPayrollAsyncRegion"
	data-payroll-summary-url="payroll-management.php?partial=payroll-summary"
	data-payroll-setup-url="payroll-management.php?partial=payroll-setup"
	data-payroll-batches-url="payroll-management.php?partial=payroll-batches"
	data-payroll-payslip-detail-url="payroll-management.php?partial=payroll-payslip-detail"
>
	<div class="bg-white px-4 pb-4 pt-4 md:px-5 rounded-3xl">
		<div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
			<div>
				<h2 class="text-lg font-semibold text-slate-900">Payroll Sections</h2>
				<p class="mt-1 text-sm text-slate-500">Overview, salary setup, and approval review are now split into separate payroll tabs.</p>
			</div>
			<?= $renderPayrollTabs() ?>
		</div>
	</div>

	<div id="adminPayrollPanelSummary" data-payroll-tab-panel="summary" role="tabpanel" class="pt-5">
	<div id="adminPayrollSummarySkeleton" class="space-y-4" aria-live="polite" role="status">
		<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
			<?php for ($index = 0; $index < 4; $index += 1): ?>
				<article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[132px]">
					<div class="h-4 w-32 animate-pulse rounded bg-slate-200"></div>
					<div class="mt-4 h-8 w-24 animate-pulse rounded bg-slate-200"></div>
					<div class="mt-4 h-3 w-full animate-pulse rounded bg-slate-200"></div>
				</article>
			<?php endfor; ?>
		</div>
		<section class="bg-white border border-slate-200 rounded-2xl p-6">
			<div class="h-5 w-48 animate-pulse rounded bg-slate-200 mb-4"></div>
			<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
				<?php for ($index = 0; $index < 3; $index += 1): ?>
					<article class="rounded-xl border border-slate-200 p-4">
						<div class="h-4 w-24 animate-pulse rounded bg-slate-200"></div>
						<div class="mt-3 h-7 w-20 animate-pulse rounded bg-slate-200"></div>
					</article>
				<?php endfor; ?>
			</div>
		</section>
	</div>

	<div id="adminPayrollSummaryError" class="hidden rounded-lg border border-amber-200 bg-amber-50 text-amber-700 py-3 text-sm" aria-live="polite">
		<p class="font-medium">Payroll summary could not be loaded.</p>
		<p class="mt-1">Retry to load the current cutoff overview and payroll status cards.</p>
		<button type="button" id="adminPayrollSummaryRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry summary</button>
	</div>

	<div id="adminPayrollSummaryContent" class="hidden"></div>
	</div>

	<div id="adminPayrollPanelSetup" data-payroll-tab-panel="setup" role="tabpanel" class="hidden pt-5">
		<div id="adminPayrollSetupSkeleton" class="space-y-6" aria-live="polite" role="status">
			<?php for ($section = 0; $section < 3; $section += 1): ?>
				<section class="bg-white border border-slate-200 rounded-2xl p-6">
					<div class="h-5 w-48 animate-pulse rounded bg-slate-200 mb-4"></div>
					<div class="h-56 animate-pulse rounded bg-slate-100"></div>
				</section>
			<?php endfor; ?>
		</div>

		<div id="adminPayrollSetupError" class="hidden rounded-lg border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm" aria-live="polite">
			<p class="font-medium">Salary setup tools could not be loaded.</p>
			<p class="mt-1">Retry to load payroll source sync, employee salary setup, and salary setup logs.</p>
			<button type="button" id="adminPayrollSetupRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry setup</button>
		</div>

		<div id="adminPayrollSetupContent" class="hidden"></div>
	</div>

	<div id="adminPayrollPanelBatches" data-payroll-tab-panel="batches" role="tabpanel" class="hidden pt-5">
		<div id="adminPayrollBatchesSkeleton" class="bg-white border border-slate-200 rounded-2xl p-6" aria-live="polite" role="status">
			<div class="h-5 w-44 animate-pulse rounded bg-slate-200 mb-4"></div>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
				<div class="h-10 animate-pulse rounded bg-slate-200"></div>
				<div class="h-10 animate-pulse rounded bg-slate-200"></div>
			</div>
			<div class="h-72 animate-pulse rounded bg-slate-100"></div>
		</div>

		<div id="adminPayrollBatchesError" class="hidden rounded-lg border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm" aria-live="polite">
			<p class="font-medium">Payroll batches could not be loaded.</p>
			<p class="mt-1">Retry to load the current payroll approval queue.</p>
			<button type="button" id="adminPayrollBatchesRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry batches</button>
		</div>

		<div id="adminPayrollBatchesContent" class="hidden"></div>
	</div>
</section>
</div>
<?php
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
