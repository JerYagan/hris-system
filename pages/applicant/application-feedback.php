<?php
require_once __DIR__ . '/includes/application-feedback/bootstrap.php';
require_once __DIR__ . '/includes/application-feedback/actions.php';
require_once __DIR__ . '/includes/application-feedback/data.php';

$pageTitle = 'Application Feedback | DA HRIS';
$activePage = 'application-feedback.php';
$breadcrumbs = ['Application Feedback'];
$retryUrl = htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? 'application-feedback.php'), ENT_QUOTES, 'UTF-8');

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
        <div class="flex items-start gap-4">
            <span class="material-symbols-outlined rounded-xl bg-green-700 p-2 text-3xl text-white">fact_check</span>
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">Application Feedback</h1>
                <p class="mt-1 text-sm text-gray-600">Official decision details and guidance for your submitted application.</p>
            </div>
        </div>
    </div>
</section>

<section class="mb-6 rounded-xl border bg-white p-5">
    <form method="GET" action="application-feedback.php" class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
        <div class="sm:col-span-2 lg:col-span-3">
            <label class="mb-1 block text-gray-500">Application</label>
            <select name="application_id" class="w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-1 focus:ring-green-600">
                <?php foreach ($applications as $application): ?>
                    <option value="<?= htmlspecialchars((string)$application['id'], ENT_QUOTES, 'UTF-8') ?>" <?= ($selectedApplication && (string)$selectedApplication['id'] === (string)$application['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$application['job_title'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string)$application['reference_no'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex items-end sm:col-span-2 lg:col-span-1">
            <button type="submit" class="w-full rounded-md bg-green-700 px-4 py-2 text-white hover:bg-green-800">Load Feedback</button>
        </div>
    </form>
</section>

<?php if ($isFilterEmpty): ?>
<section class="mb-6 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <span>No feedback matched the selected application filter. Showing your most recent application instead.</span>
        <a href="application-feedback.php" class="inline-flex items-center justify-center rounded-md border border-blue-300 bg-white px-3 py-1.5 text-xs text-blue-800 hover:bg-blue-100">Clear filter</a>
    </div>
</section>
<?php endif; ?>

<?php if (empty($applications)): ?>
    <section class="mb-8 rounded-xl border bg-white p-8 text-center">
        <span class="material-symbols-outlined text-4xl text-gray-400">inbox</span>
        <h2 class="mt-3 text-xl font-semibold text-gray-800">No applications yet</h2>
        <p class="mt-1 text-sm text-gray-600">Submit an application first to view official feedback.</p>
        <a href="job-list.php" class="mt-4 inline-flex items-center gap-1 rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Browse Jobs
        </a>
    </section>
<?php else: ?>

<section class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Position</p>
        <p class="mt-2 font-semibold text-gray-800"><?= htmlspecialchars((string)($selectedApplication['job_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    </article>
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Office</p>
        <p class="mt-2 font-semibold text-gray-800"><?= htmlspecialchars((string)($selectedApplication['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    </article>
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Date Applied</p>
        <p class="mt-2 font-semibold text-gray-800"><?= !empty($selectedApplication['submitted_at']) ? htmlspecialchars(date('F j, Y', strtotime((string)$selectedApplication['submitted_at'])), ENT_QUOTES, 'UTF-8') : '-' ?></p>
    </article>
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Decision</p>
        <p class="mt-2 font-semibold text-gray-800 capitalize"><?= htmlspecialchars($applicationStatus, ENT_QUOTES, 'UTF-8') ?></p>
    </article>
</section>

<?php if ($applicationStatus === 'accepted'): ?>
    <section class="mb-8 rounded-xl border <?= htmlspecialchars($decisionPanelClass, ENT_QUOTES, 'UTF-8') ?>">
        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:gap-4 sm:p-6">
            <span class="material-symbols-outlined text-3xl text-green-700"><?= htmlspecialchars($decisionIcon, ENT_QUOTES, 'UTF-8') ?></span>
            <div>
                <h2 class="text-xl font-semibold text-green-800"><?= htmlspecialchars($decisionTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="mt-1 text-sm text-green-700"><?= htmlspecialchars($decisionMessage, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <div class="space-y-2 px-4 pb-4 text-sm text-green-800 sm:px-6 sm:pb-6">
            <p><?= htmlspecialchars($decisionBody, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </section>
<?php elseif ($applicationStatus === 'rejected'): ?>
    <section class="mb-8 rounded-xl border <?= htmlspecialchars($decisionPanelClass, ENT_QUOTES, 'UTF-8') ?>">
        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:gap-4 sm:p-6">
            <span class="material-symbols-outlined text-3xl text-red-700"><?= htmlspecialchars($decisionIcon, ENT_QUOTES, 'UTF-8') ?></span>
            <div>
                <h2 class="text-xl font-semibold text-red-800"><?= htmlspecialchars($decisionTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="mt-1 text-sm text-red-700"><?= htmlspecialchars($decisionMessage, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <div class="space-y-2 px-4 pb-4 text-sm text-red-800 sm:px-6 sm:pb-6">
            <p><?= htmlspecialchars($decisionBody, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </section>
<?php else: ?>
    <section class="mb-8 rounded-xl border <?= htmlspecialchars($decisionPanelClass, ENT_QUOTES, 'UTF-8') ?>">
        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:gap-4 sm:p-6">
            <span class="material-symbols-outlined text-3xl text-yellow-700"><?= htmlspecialchars($decisionIcon, ENT_QUOTES, 'UTF-8') ?></span>
            <div>
                <h2 class="text-xl font-semibold text-yellow-900"><?= htmlspecialchars($decisionTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="mt-1 text-sm text-yellow-800"><?= htmlspecialchars($decisionMessage, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <div class="space-y-2 px-4 pb-4 text-sm text-yellow-900 sm:px-6 sm:pb-6">
            <p><?= htmlspecialchars($decisionBody, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </section>
<?php endif; ?>

<section class="mb-8 rounded-xl border bg-white">
    <header class="flex items-center gap-2 border-b px-6 py-4">
        <span class="material-symbols-outlined text-green-700">notes</span>
        <h2 class="text-lg font-semibold text-gray-800">Remarks</h2>
    </header>

    <div class="p-6 text-sm text-gray-700">
        <p><?= htmlspecialchars((string)$remarks, ENT_QUOTES, 'UTF-8') ?></p>
        <?php if ($feedbackRecord !== null && !empty($feedbackRecord['provided_at'])): ?>
            <p class="mt-2 text-xs text-gray-500">Posted: <?= htmlspecialchars(date('M j, Y g:i A', strtotime((string)$feedbackRecord['provided_at'])), ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>
</section>

<div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:justify-end">
    <a href="job-list.php" class="inline-flex items-center justify-center gap-1 rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
        View Other Jobs
    </a>

    <a href="applications.php" class="inline-flex items-center justify-center gap-2 rounded-md bg-green-700 px-5 py-2 text-sm font-medium text-white hover:bg-green-800">
        View Application Status
    </a>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
