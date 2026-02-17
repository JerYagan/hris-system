<?php
require_once __DIR__ . '/includes/applications/bootstrap.php';
require_once __DIR__ . '/includes/applications/actions.php';
require_once __DIR__ . '/includes/applications/data.php';

$pageTitle = 'Applications | DA HRIS';
$activePage = 'applications.php';
$breadcrumbs = ['My Applications'];
$retryUrl = htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? 'applications.php'), ENT_QUOTES, 'UTF-8');

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

<section class="mb-5 rounded-xl border bg-white p-4 sm:p-5">
    <div class="rounded-xl border bg-gradient-to-r from-green-50 via-white to-green-50 p-4 sm:p-5">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <span class="material-symbols-outlined rounded-xl bg-green-700 p-2 text-3xl text-white">track_changes</span>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Application Tracking</h1>
                    <p class="mt-1 text-sm text-gray-600">Monitor your status, recent milestones, and upcoming recruitment decisions.</p>
                    <?php if ($selectedApplication !== null): ?>
                        <?php $selectedMeta = (array)($statusMeta[strtolower((string)($selectedApplication['status'] ?? ''))] ?? ['label' => 'Pending', 'badge' => 'bg-gray-100 text-gray-700']); ?>
                        <p class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-medium <?= htmlspecialchars((string)$selectedMeta['badge'], ENT_QUOTES, 'UTF-8') ?>">
                            Current Status: <?= htmlspecialchars((string)$selectedMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    <?php else: ?>
                        <p class="mt-3 inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">No submitted applications yet</p>
                    <?php endif; ?>
                </div>
            </div>

            <a href="application-feedback.php<?= $selectedApplication ? '?application_id=' . urlencode((string)$selectedApplication['id']) : '' ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-green-700 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50 sm:w-auto">
                <span class="material-symbols-outlined text-sm">fact_check</span>
                Open Feedback Page
            </a>
        </div>
    </div>
</section>

<section class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Total Applications</p>
        <p class="mt-2 font-semibold text-gray-800"><?= (int)$applicationStats['total'] ?></p>
    </article>
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Submitted</p>
        <p class="mt-2 font-semibold text-gray-800"><?= (int)$applicationStats['submitted'] ?></p>
    </article>
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">In Progress</p>
        <p class="mt-2 font-semibold text-gray-800"><?= (int)$applicationStats['in_progress'] ?></p>
    </article>
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Finalized</p>
        <p class="mt-2 font-semibold text-gray-800"><?= (int)$applicationStats['finalized'] ?></p>
    </article>
</section>

<section class="mb-6 rounded-xl border bg-white p-5">
    <form method="GET" action="applications.php" class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="mb-1 block text-gray-500">Status Filter</label>
            <select name="status" class="w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-1 focus:ring-green-600">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All</option>
                <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="finalized" <?= $statusFilter === 'finalized' ? 'selected' : '' ?>>Finalized</option>
                <option value="submitted" <?= $statusFilter === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                <option value="screening" <?= $statusFilter === 'screening' ? 'selected' : '' ?>>Screening</option>
                <option value="shortlisted" <?= $statusFilter === 'shortlisted' ? 'selected' : '' ?>>Shortlisted</option>
                <option value="interview" <?= $statusFilter === 'interview' ? 'selected' : '' ?>>Interview</option>
                <option value="offer" <?= $statusFilter === 'offer' ? 'selected' : '' ?>>Offer</option>
                <option value="hired" <?= $statusFilter === 'hired' ? 'selected' : '' ?>>Hired</option>
                <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="withdrawn" <?= $statusFilter === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
            </select>
        </div>
        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-3 lg:justify-end">
            <button type="submit" class="flex-1 rounded-md bg-green-700 px-4 py-2 text-white hover:bg-green-800 sm:flex-none">Apply Filter</button>
            <a href="applications.php" class="flex-1 rounded-md border px-4 py-2 text-center text-gray-700 hover:bg-gray-50 sm:flex-none">Clear</a>
        </div>
    </form>
</section>

<section class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="xl:col-span-2 rounded-xl border bg-white">
        <header class="flex items-center justify-between border-b px-6 py-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-green-700">list_alt</span>
                <h2 class="text-lg font-semibold text-gray-800">My Applications</h2>
            </div>
            <a href="notifications.php" class="text-sm text-green-700 hover:underline">View notifications</a>
        </header>

        <?php if (empty($applications)): ?>
            <div class="p-8 text-center">
                <span class="material-symbols-outlined text-4xl text-gray-400">inbox</span>
                <h3 class="mt-3 text-lg font-semibold text-gray-800">No applications found</h3>
                <p class="mt-1 text-sm text-gray-600">
                    <?= $statusFilter !== 'all' ? 'No applications match your selected filter.' : 'You have not submitted an application yet.' ?>
                </p>
                <a href="job-list.php" class="mt-4 inline-flex items-center rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Browse Jobs</a>
            </div>
        <?php else: ?>
            <div class="divide-y">
                <?php foreach ($applications as $application): ?>
                    <?php $meta = (array)($statusMeta[strtolower((string)$application['status'])] ?? ['label' => 'Pending', 'badge' => 'bg-gray-100 text-gray-700']); ?>
                    <a href="applications.php?<?= htmlspecialchars(http_build_query(['status' => $statusFilter, 'application_id' => (string)$application['id']]), ENT_QUOTES, 'UTF-8') ?>" class="block p-5 hover:bg-gray-50 <?= ($selectedApplication && (string)$selectedApplication['id'] === (string)$application['id']) ? 'bg-green-50' : '' ?>">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-gray-800"><?= htmlspecialchars((string)$application['job_title'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars((string)$application['office_name'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mt-1 text-xs text-gray-500">Ref: <?= htmlspecialchars((string)$application['reference_no'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div class="sm:text-right">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium <?= htmlspecialchars((string)$meta['badge'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)$meta['label'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <p class="mt-1 text-xs text-gray-500">Applied: <?= htmlspecialchars(date('M j, Y', strtotime((string)($application['submitted_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <aside class="space-y-6">
        <section class="rounded-xl border bg-white">
            <header class="border-b px-6 py-4">
                <h3 class="font-semibold text-gray-800">Selected Application</h3>
            </header>

            <?php if ($selectedApplication === null): ?>
                <div class="p-6 text-sm text-gray-600">No application selected.</div>
            <?php else: ?>
                <?php $selectedMeta = (array)($statusMeta[strtolower((string)$selectedApplication['status'])] ?? ['label' => 'Pending', 'badge' => 'bg-gray-100 text-gray-700']); ?>
                <div class="space-y-3 p-6 text-sm">
                    <div>
                        <p class="text-gray-500">Position</p>
                        <p class="font-medium text-gray-800"><?= htmlspecialchars((string)$selectedApplication['job_title'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Office</p>
                        <p class="font-medium text-gray-800"><?= htmlspecialchars((string)$selectedApplication['office_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Reference</p>
                        <p class="font-medium text-gray-800"><?= htmlspecialchars((string)$selectedApplication['reference_no'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Status</p>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium <?= htmlspecialchars((string)$selectedMeta['badge'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string)$selectedMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-2">
                        <a href="application-feedback.php?application_id=<?= urlencode((string)$selectedApplication['id']) ?>" class="inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <span class="material-symbols-outlined text-sm">fact_check</span>
                            View Feedback
                        </a>
                        <a href="notifications.php" class="inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Notifications</a>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="rounded-xl border bg-white">
            <header class="flex items-center gap-2 border-b px-6 py-4">
                <span class="material-symbols-outlined text-green-700">timeline</span>
                <h3 class="font-semibold text-gray-800">Status Timeline</h3>
            </header>
            <div class="space-y-3 p-6">
                <?php if (empty($applicationTimeline)): ?>
                    <p class="text-sm text-gray-600">No timeline entries yet.</p>
                <?php else: ?>
                    <?php foreach ($applicationTimeline as $event): ?>
                        <?php $timelineMeta = (array)($statusMeta[strtolower((string)$event['status'])] ?? ['label' => 'Pending', 'timeline' => 'bg-gray-400 text-white']); ?>
                        <article class="flex items-start gap-3 rounded-lg border bg-gray-50 p-4">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full <?= htmlspecialchars((string)$timelineMeta['timeline'], ENT_QUOTES, 'UTF-8') ?>">
                                <span class="material-symbols-outlined text-[15px]">task_alt</span>
                            </span>
                            <div>
                                <h4 class="font-medium text-gray-800"><?= htmlspecialchars((string)$timelineMeta['label'], ENT_QUOTES, 'UTF-8') ?></h4>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars((string)($event['notes'] ?? 'Status updated.'), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php if (!empty($event['created_at'])): ?>
                                    <p class="mt-1 text-xs text-gray-500"><?= htmlspecialchars(date('M j, Y g:i A', strtotime((string)$event['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
