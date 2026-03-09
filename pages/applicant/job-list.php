<?php
require_once __DIR__ . '/includes/job-list/bootstrap.php';
require_once __DIR__ . '/includes/job-list/actions.php';
require_once __DIR__ . '/includes/job-list/data.php';

$pageTitle = 'Job Listings | DA HRIS';
$activePage = 'job-list.php';
$breadcrumbs = ['Job Listings'];
$retryUrl = htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? 'job-list.php'), ENT_QUOTES, 'UTF-8');

ob_start();
?>

<?php if (!empty($message)): ?>
<section class="mb-6 rounded-xl border px-4 py-3 text-sm <?= ($state ?? '') === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
    <?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>
</section>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
<section class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <span><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></span>
        <a href="<?= $retryUrl ?>" class="inline-flex items-center justify-center rounded-md border border-amber-300 bg-white px-3 py-1.5 text-xs text-amber-800 hover:bg-amber-100">Retry</a>
    </div>
</section>
<?php endif; ?>

<?php
$hasActiveFilters = (($filters['q'] ?? '') !== '') || (($filters['office'] ?? '') !== '') || (($filters['employment_type'] ?? '') !== '');
$activeFilterCount = 0;
foreach (['q', 'office', 'employment_type'] as $filterKey) {
    if (($filters[$filterKey] ?? '') !== '') {
        $activeFilterCount++;
    }
}
?>

<section class="mb-5 rounded-xl border bg-white p-4 sm:p-5">
    <div class="rounded-xl border bg-gradient-to-r from-green-50 via-white to-green-50 p-4 sm:p-5">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <span class="material-symbols-outlined rounded-lg bg-green-700 p-2 text-2xl text-white">work_outline</span>
                <div>
                    <p class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">Recruitment Openings</p>
                    <h1 class="mt-2 text-xl font-semibold text-gray-800">Find Your Next Opportunity</h1>
                    <p class="mt-1 text-sm text-gray-600">Browse vacancies, compare deadlines, and open full job details before applying.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="rounded-lg border bg-white px-4 py-3 text-center">
                    <p class="font-semibold text-gray-800"><?= (int)$openPositionsTotal ?></p>
                    <p class="text-xs text-gray-500">Open Positions</p>
                </div>
                <div class="rounded-lg border bg-white px-4 py-3 text-center">
                    <p class="font-semibold text-gray-800"><?= (int)$closingThisWeekTotal ?></p>
                    <p class="text-xs text-gray-500">Closing This Week</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="rounded-xl border bg-white">
    <header class="flex flex-col gap-2 border-b px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-green-700">list_alt</span>
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Available Positions</h2>
                <p class="text-xs text-gray-500">Search and narrow results before opening a posting.</p>
            </div>
        </div>
        <p class="text-xs text-gray-500">Updated today</p>
    </header>

    <div class="border-b bg-gray-50/80 px-4 py-4 sm:px-6">
        <form method="GET" action="job-list.php" class="space-y-3">
            <input type="hidden" name="page" value="1">
            <input type="hidden" name="page_size" value="<?= (int)$pageSize ?>">

            <div class="grid grid-cols-1 gap-3 text-sm lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_minmax(0,1fr)_auto_auto]">
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Search Position</label>
                    <input type="text" name="q" value="<?= htmlspecialchars((string)($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by job title or keyword" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-gray-700 focus:border-green-600 focus:outline-none focus:ring-1 focus:ring-green-600">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Division</label>
                    <select name="office" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-gray-700 focus:border-green-600 focus:outline-none focus:ring-1 focus:ring-green-600">
                        <option value="">All</option>
                        <?php foreach ($officeOptions as $officeOption): ?>
                            <?php
                            $optionId = (string)($officeOption['id'] ?? '');
                            $optionName = (string)($officeOption['office_name'] ?? 'Unknown Division');
                            ?>
                            <option value="<?= htmlspecialchars($optionId, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['office'] ?? '') === $optionId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($optionName, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Employment Type</label>
                    <select name="employment_type" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-gray-700 focus:border-green-600 focus:outline-none focus:ring-1 focus:ring-green-600">
                        <option value="">All</option>
                        <?php foreach ($employmentTypeOptions as $employmentType): ?>
                            <option value="<?= htmlspecialchars((string)$employmentType, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['employment_type'] ?? '') === $employmentType ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)$employmentType)), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-lg bg-green-700 px-4 py-2.5 font-medium text-white hover:bg-green-800">Apply Filters</button>
                </div>

                <div class="flex items-end">
                    <a href="job-list.php" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 font-medium text-gray-700 hover:bg-gray-100">Reset</a>
                </div>
            </div>

            <div class="flex flex-col gap-2 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
                <p>
                    Showing <?= count($jobs) ?> of <?= (int)$jobsTotal ?> open positions
                    <?php if ($hasActiveFilters): ?>
                        with <?= (int)$activeFilterCount ?> active <?= $activeFilterCount === 1 ? 'filter' : 'filters' ?>
                    <?php endif; ?>
                </p>
                <?php if ($hasActiveFilters): ?>
                    <div class="flex flex-wrap gap-2">
                        <?php if (($filters['q'] ?? '') !== ''): ?>
                            <span class="inline-flex items-center rounded-full bg-white px-2.5 py-1 text-gray-600 ring-1 ring-gray-200">Keyword: <?= htmlspecialchars((string)$filters['q'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php if (($filters['employment_type'] ?? '') !== ''): ?>
                            <span class="inline-flex items-center rounded-full bg-white px-2.5 py-1 text-gray-600 ring-1 ring-gray-200">Type: <?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)$filters['employment_type'])), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (empty($jobs)): ?>
        <div class="p-8 text-center">
            <span class="material-symbols-outlined text-4xl text-gray-400">work_off</span>
            <h3 class="mt-3 text-lg font-semibold text-gray-800">No job vacancies found</h3>
            <p class="mt-1 text-sm text-gray-600">
                <?= (($filters['q'] ?? '') !== '' || ($filters['office'] ?? '') !== '' || ($filters['employment_type'] ?? '') !== '')
                    ? 'No results matched your current filters. Try clearing or broadening your search.'
                    : 'There are currently no published job postings available.' ?>
            </p>
            <?php if (($filters['q'] ?? '') !== '' || ($filters['office'] ?? '') !== '' || ($filters['employment_type'] ?? '') !== ''): ?>
                <a href="job-list.php" class="mt-4 inline-flex items-center rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    Clear filters
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-4 p-6 lg:grid-cols-2">
            <?php foreach ($jobs as $job): ?>
                <?php
                $badgeClass = 'bg-blue-50 text-blue-700';
                $employmentTypeLower = strtolower((string)($job['employment_type'] ?? ''));
                if (in_array($employmentTypeLower, ['contractual', 'contract'], true)) {
                    $badgeClass = 'bg-green-100 text-green-700';
                } elseif (in_array($employmentTypeLower, ['regular', 'permanent'], true)) {
                    $badgeClass = 'bg-blue-50 text-blue-700';
                } elseif (in_array($employmentTypeLower, ['job_order', 'job order'], true)) {
                    $badgeClass = 'bg-yellow-50 text-yellow-700';
                }
                ?>
                <article class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-green-200 hover:shadow-md">
                    <div class="flex-1">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="text-xl font-semibold leading-tight text-gray-900"><?= htmlspecialchars((string)($job['title'] ?? 'Untitled Position'), ENT_QUOTES, 'UTF-8') ?></h3>
                                <p class="mt-2 inline-flex rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-700">
                                    Plantilla Item No.: <?= htmlspecialchars((string)($job['plantilla_item_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                            <?php if (!empty($job['employment_type'])): ?>
                                <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold <?= $badgeClass ?>">
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)$job['employment_type'])), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="mt-3 text-sm font-medium text-gray-700"><?= htmlspecialchars((string)($job['office_name'] ?? 'Division not specified'), ENT_QUOTES, 'UTF-8') ?></p>

                        <div class="mt-4 flex flex-wrap gap-2 text-xs text-gray-600">
                            <span class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1">
                                <span class="material-symbols-outlined text-sm">schedule</span>
                                Deadline: <?= htmlspecialchars((string)($job['deadline_display'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1">
                                <span class="material-symbols-outlined text-sm">location_on</span>
                                <?= htmlspecialchars((string)($job['office_name'] ?? 'Division not specified'), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if (!empty($job['csc_reference_url']) && preg_match('/^https?:\/\//i', (string)$job['csc_reference_url'])): ?>
                                <a href="<?= htmlspecialchars((string)$job['csc_reference_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 hover:bg-white">
                                    <span class="material-symbols-outlined text-sm">open_in_new</span>
                                    CSC format reference
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="<?= htmlspecialchars((string)($job['detail_url'] ?? 'job-view.php'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <span class="material-symbols-outlined text-sm">visibility</span>
                            View Details
                        </a>

                        <?php if (!empty($job['already_applied'])): ?>
                            <span class="inline-flex items-center gap-1 rounded-lg border border-green-300 bg-green-50 px-4 py-2 text-sm font-medium text-green-700">
                                <span class="material-symbols-outlined text-sm">check_circle</span>
                                Already Applied
                            </span>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars((string)($job['apply_url'] ?? 'apply.php'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1 rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
                                <span class="material-symbols-outlined text-sm">edit_document</span>
                                Apply Now
                            </a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="border-t bg-gray-50 px-4 py-3 text-sm text-gray-600 sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p>Showing <?= count($jobs) ?> of <?= (int)$jobsTotal ?> available job vacancies</p>
                <div class="flex flex-wrap items-center gap-2">
                    <?php
                    $queryBase = [
                        'q' => $filters['q'] ?? '',
                        'office' => $filters['office'] ?? '',
                        'employment_type' => $filters['employment_type'] ?? '',
                        'page_size' => $pageSize,
                    ];
                    $prevUrl = 'job-list.php?' . http_build_query(array_merge($queryBase, ['page' => max(1, $page - 1)]));
                    $nextUrl = 'job-list.php?' . http_build_query(array_merge($queryBase, ['page' => $page + 1]));
                    ?>
                    <span class="text-xs text-gray-500">Page <?= (int)$page ?> of <?= (int)$totalPages ?></span>
                    <?php if ($hasPrevPage): ?>
                        <a href="<?= htmlspecialchars($prevUrl, ENT_QUOTES, 'UTF-8') ?>" class="rounded-md border px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-100">Previous</a>
                    <?php else: ?>
                        <span class="rounded-md border px-3 py-1.5 text-xs text-gray-400">Previous</span>
                    <?php endif; ?>
                    <?php if ($hasNextPage): ?>
                        <a href="<?= htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8') ?>" class="rounded-md border px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-100">Next</a>
                    <?php else: ?>
                        <span class="rounded-md border px-3 py-1.5 text-xs text-gray-400">Next</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
