<?php
require_once __DIR__ . '/includes/job-view/bootstrap.php';
require_once __DIR__ . '/includes/job-view/actions.php';
require_once __DIR__ . '/includes/job-view/data.php';

$pageTitle = 'Job Details | DA HRIS';
$activePage = 'job-list.php';
$breadcrumbs = ['Job Listings', 'Job Details'];
$retryUrl = htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? 'job-view.php'), ENT_QUOTES, 'UTF-8');

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

<?php if ($jobNotFound): ?>
<section class="mb-6 rounded-xl border bg-white p-10 text-center">
    <span class="material-symbols-outlined text-4xl text-gray-400">search_off</span>
    <h2 class="mt-3 text-xl font-semibold text-gray-800">Job posting not found</h2>
    <p class="mt-1 text-sm text-gray-600">The job posting may have been removed or is no longer published.</p>
    <a href="job-list.php" class="mt-5 inline-flex items-center gap-1 rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        Back to Listings
    </a>
</section>
<?php else: ?>

<section class="mb-5 rounded-xl border bg-white p-4 sm:p-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <a href="job-list.php" class="mb-3 inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-800">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Back to listings
            </a>

            <div class="flex items-start gap-4">
                <span class="material-symbols-outlined rounded-lg bg-green-700 p-2 text-2xl text-white">work</span>
                <div>
                    <h1 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars((string)$jobData['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars((string)$jobData['office_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 font-medium text-green-700"><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)$jobData['employment_type'])), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="inline-flex rounded-full border bg-white px-2.5 py-1 text-gray-600">Salary Grade <?= htmlspecialchars((string)$jobData['salary_grade'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="inline-flex rounded-full border bg-white px-2.5 py-1 text-gray-600">Plantilla <?= htmlspecialchars((string)($jobData['plantilla_item_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border bg-gray-50 px-4 py-3 text-sm">
            <p class="text-gray-500">Application Deadline</p>
            <p class="font-semibold text-gray-800"><?= htmlspecialchars((string)$jobData['deadline_display'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($daysRemaining !== null): ?>
                <?php if ($daysRemaining >= 0): ?>
                    <p class="mt-1 inline-flex rounded-full bg-yellow-50 px-2.5 py-1 text-xs font-medium text-yellow-700"><?= (int)$daysRemaining ?> days remaining</p>
                <?php else: ?>
                    <p class="mt-1 inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">Deadline passed</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="xl:col-span-2 space-y-6">
        <section class="rounded-xl border bg-white">
            <header class="flex items-center gap-2 border-b px-4 py-4 sm:px-6">
                <span class="material-symbols-outlined text-green-700">description</span>
                <h2 class="text-lg font-semibold text-gray-800">Job Description</h2>
            </header>

            <div class="space-y-3 p-4 text-sm text-gray-700 sm:p-6">
                <p><?= nl2br(htmlspecialchars((string)($jobData['description'] !== '' ? $jobData['description'] : 'No description provided for this position.'), ENT_QUOTES, 'UTF-8')) ?></p>
            </div>
        </section>

        <section class="rounded-xl border bg-white">
            <header class="flex items-center gap-2 border-b px-4 py-4 sm:px-6">
                <span class="material-symbols-outlined text-green-700">checklist</span>
                <h2 class="text-lg font-semibold text-gray-800">Qualifications</h2>
            </header>

            <div class="grid grid-cols-1 gap-6 p-4 text-sm md:grid-cols-2 sm:p-6">
                <div>
                    <p class="mb-2 font-medium text-gray-800">Minimum Requirements</p>
                    <?php if (!empty($qualificationList)): ?>
                        <ul class="list-disc space-y-1 pl-5 text-gray-700">
                            <?php foreach ($qualificationList as $qualification): ?>
                                <li><?= htmlspecialchars((string)$qualification, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-gray-600">No qualification details were provided.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <p class="mb-2 font-medium text-gray-800">Responsibilities</p>
                    <?php if (!empty($responsibilityList)): ?>
                        <ul class="list-disc space-y-1 pl-5 text-gray-700">
                            <?php foreach ($responsibilityList as $responsibility): ?>
                                <li><?= htmlspecialchars((string)$responsibility, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-gray-600">No responsibilities were listed for this posting.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="rounded-xl border bg-white">
            <header class="flex items-center gap-2 border-b px-4 py-4 sm:px-6">
                <span class="material-symbols-outlined text-green-700">folder</span>
                <h2 class="text-lg font-semibold text-gray-800">Required Documents</h2>
            </header>

            <div class="p-4 text-sm text-gray-700 sm:p-6">
                <?php if (!empty($jobData['required_documents'])): ?>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <?php foreach ((array)$jobData['required_documents'] as $requiredDocument): ?>
                            <?php
                            $docLabel = (string)($requiredDocument['label'] ?? 'Required Document');
                            $docRequired = (bool)($requiredDocument['required'] ?? true);
                            ?>
                            <article class="rounded-lg border p-3 <?= $docRequired ? 'border-rose-200 bg-rose-50/50' : 'bg-gray-50' ?>">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="font-medium text-gray-800"><?= htmlspecialchars($docLabel, ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php if ($docRequired): ?>
                                        <span class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700">Required</span>
                                    <?php else: ?>
                                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Optional</span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">No document requirements were configured for this posting.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="rounded-xl border bg-white">
            <header class="border-b px-4 py-4 sm:px-6">
                <h3 class="font-semibold text-gray-800">Position Snapshot</h3>
            </header>
            <div class="space-y-3 p-4 text-sm sm:p-6">
                <div>
                    <p class="text-gray-500">Office / Department</p>
                    <p class="font-medium text-gray-800"><?= htmlspecialchars((string)$jobData['office_name'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Plantilla Item No.</p>
                    <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($jobData['plantilla_item_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Deadline</p>
                    <p class="font-medium text-gray-800"><?= htmlspecialchars((string)$jobData['deadline_display'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <?php if (!empty($jobData['csc_reference_url']) && preg_match('/^https?:\/\//i', (string)$jobData['csc_reference_url'])): ?>
                    <div>
                        <p class="text-gray-500">CSC Reference</p>
                        <a href="<?= htmlspecialchars((string)$jobData['csc_reference_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 font-medium text-green-700 hover:underline">
                            Open posting format
                            <span class="material-symbols-outlined text-sm">open_in_new</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="rounded-xl border bg-white">
            <div class="p-4 sm:p-6">
                <p class="text-sm text-gray-600">Please ensure all required documents are complete before submission.</p>
                <div class="mt-4 flex flex-col gap-2">
                    <?php if ($alreadyApplied): ?>
                        <span class="inline-flex items-center justify-center gap-2 rounded-md border border-green-300 bg-green-50 px-4 py-2 text-sm font-medium text-green-700">
                            <span class="material-symbols-outlined text-sm">check_circle</span>
                            Already Applied
                        </span>
                    <?php elseif ($isDeadlinePassed): ?>
                        <span class="inline-flex items-center justify-center gap-2 rounded-md border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700">
                            <span class="material-symbols-outlined text-sm">schedule</span>
                            Deadline Passed
                        </span>
                    <?php elseif ($canApply): ?>
                        <a href="apply.php?job_id=<?= urlencode((string)$jobData['id']) ?>" class="inline-flex items-center justify-center gap-2 rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
                            <span class="material-symbols-outlined text-sm">edit_document</span>
                            Apply for this Position
                        </a>
                    <?php endif; ?>
                    <a href="job-list.php" class="inline-flex items-center justify-center gap-1 rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <span class="material-symbols-outlined text-sm">arrow_back</span>
                        Back to Listings
                    </a>
                </div>
            </div>
        </section>
    </aside>
</section>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
