<?php
require_once __DIR__ . '/includes/recruitment/bootstrap.php';
require_once __DIR__ . '/includes/recruitment/actions.php';

$pageTitle = 'Recruitment | Admin';
$activePage = 'recruitment.php';
$breadcrumbs = ['Recruitment'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/admin/recruitment/index.js';

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$recruitmentPartial = (string)($_GET['partial'] ?? '');
if ($recruitmentPartial === 'recruitment-posting-view') {
	$recruitmentDataStage = 'posting-view';
	require_once __DIR__ . '/includes/recruitment/data.php';
	$statusCode = is_array($recruitmentPostingViewPayload ?? null) ? 200 : 404;
	http_response_code($statusCode);
	header('Content-Type: application/json; charset=UTF-8');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	echo (string)json_encode(
		is_array($recruitmentPostingViewPayload ?? null)
			? $recruitmentPostingViewPayload
			: ['error' => 'Recruitment posting details could not be loaded.'],
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	);
	exit;
}

if (in_array($recruitmentPartial, ['recruitment-summary', 'recruitment-listings', 'recruitment-secondary'], true)) {
	$recruitmentDataStage = match ($recruitmentPartial) {
		'recruitment-summary' => 'summary',
		'recruitment-listings' => 'listings',
		default => 'secondary',
	};
	$recruitmentContentSection = $recruitmentDataStage;
	require_once __DIR__ . '/includes/recruitment/data.php';
	header('Content-Type: text/html; charset=UTF-8');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	require __DIR__ . '/includes/recruitment/content.php';
	exit;
}

ob_start();
?>
<div id="recruitmentFlashState" class="hidden" data-state="<?= htmlspecialchars((string)$state, ENT_QUOTES, 'UTF-8') ?>" data-message="<?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>"></div>

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

<section class="bg-white border border-slate-200 rounded-2xl mb-6 px-6 py-5">
	<div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
		<div>
			<h1 class="text-2xl font-semibold text-slate-900">Recruitment</h1>
			<p class="mt-2 text-sm text-slate-500 max-w-3xl">Load hiring signals and the active posting list first, then defer archived records, deadline monitoring, applicant detail payloads, and admin management tools.</p>
		</div>
		<div class="flex flex-wrap gap-2">
			<a href="applicants.php" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Open Applicants Registration</a>
			<a href="evaluation.php" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Open Evaluation</a>
		</div>
	</div>
</section>

<section
	id="adminRecruitmentAsyncRegion"
	data-recruitment-summary-url="recruitment.php?partial=recruitment-summary"
	data-recruitment-listings-url="recruitment.php?partial=recruitment-listings"
	data-recruitment-secondary-url="recruitment.php?partial=recruitment-secondary"
	data-recruitment-posting-view-url="recruitment.php?partial=recruitment-posting-view"
>
	<div id="adminRecruitmentSummarySkeleton" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6" aria-live="polite" role="status">
		<?php for ($index = 0; $index < 4; $index += 1): ?>
			<article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[120px]">
				<div class="h-4 w-28 rounded bg-slate-200 animate-pulse"></div>
				<div class="mt-4 h-8 w-16 rounded bg-slate-200 animate-pulse"></div>
				<div class="mt-3 h-3 w-full rounded bg-slate-100 animate-pulse"></div>
			</article>
		<?php endfor; ?>
	</div>
	<div id="adminRecruitmentSummaryError" class="hidden mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
		<p class="font-medium">Recruitment summary could not be loaded.</p>
		<button type="button" id="adminRecruitmentSummaryRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry summary</button>
	</div>
	<div id="adminRecruitmentSummaryContent" class="hidden"></div>

	<div id="adminRecruitmentListingsSkeleton" class="bg-white border border-slate-200 rounded-2xl mb-6 p-6" aria-live="polite" role="status">
		<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
			<div>
				<div class="h-5 w-40 rounded bg-slate-200 animate-pulse"></div>
				<div class="mt-2 h-4 w-64 rounded bg-slate-100 animate-pulse"></div>
			</div>
			<div class="h-9 w-24 rounded bg-slate-100 animate-pulse"></div>
		</div>
		<div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
			<div class="md:col-span-2 h-10 rounded bg-slate-100 animate-pulse"></div>
			<div class="h-10 rounded bg-slate-100 animate-pulse"></div>
		</div>
		<div class="space-y-3">
			<?php for ($index = 0; $index < 5; $index += 1): ?>
				<div class="h-14 rounded bg-slate-100 animate-pulse"></div>
			<?php endfor; ?>
		</div>
	</div>
	<div id="adminRecruitmentListingsError" class="hidden mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
		<p class="font-medium">Active job listings could not be loaded.</p>
		<button type="button" id="adminRecruitmentListingsRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry listings</button>
	</div>
	<div id="adminRecruitmentListingsContent" class="hidden"></div>

	<div id="adminRecruitmentSecondarySkeleton" class="space-y-6" aria-live="polite" role="status">
		<?php for ($section = 0; $section < 3; $section += 1): ?>
			<section class="bg-white border border-slate-200 rounded-xl p-6">
				<div class="h-5 w-48 rounded bg-slate-200 animate-pulse"></div>
				<div class="mt-4 space-y-3">
					<?php for ($row = 0; $row < 4; $row += 1): ?>
						<div class="h-12 rounded bg-slate-100 animate-pulse"></div>
					<?php endfor; ?>
				</div>
			</section>
		<?php endfor; ?>
	</div>
	<div id="adminRecruitmentSecondaryError" class="hidden rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
		<p class="font-medium">Recruitment secondary sections could not be loaded.</p>
		<button type="button" id="adminRecruitmentSecondaryRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry sections</button>
	</div>
	<div id="adminRecruitmentSecondaryContent" class="hidden"></div>
</section>
<?php
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
