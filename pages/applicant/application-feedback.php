<?php
$pageTitle = 'Application Feedback | DA HRIS';
$activePage = 'application-feedback.php';
$breadcrumbs = ['Application Feedback'];

$applicationStatus = 'accepted'; // accepted | rejected | pending

ob_start();
?>

<section class="mb-6 rounded-2xl border bg-white p-6 sm:p-7">
    <div class="rounded-2xl border bg-gradient-to-r from-green-50 via-white to-green-50 p-5 sm:p-6">
        <div class="flex items-start gap-4">
            <span class="material-symbols-outlined rounded-xl bg-green-700 p-2 text-3xl text-white">fact_check</span>
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">Application Feedback</h1>
                <p class="mt-1 text-sm text-gray-600">Official decision details and guidance for your submitted application.</p>
            </div>
        </div>
    </div>
</section>

<section class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Position</p>
        <p class="mt-2 font-semibold text-gray-800">Administrative Aide</p>
    </article>
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Office</p>
        <p class="mt-2 font-semibold text-gray-800">Agricultural Training Institute</p>
    </article>
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Date Applied</p>
        <p class="mt-2 font-semibold text-gray-800">February 5, 2026</p>
    </article>
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Decision</p>
        <p class="mt-2 font-semibold text-gray-800 capitalize"><?= htmlspecialchars($applicationStatus, ENT_QUOTES, 'UTF-8') ?></p>
    </article>
</section>

<?php if ($applicationStatus === 'accepted'): ?>
    <section class="mb-8 rounded-xl border border-green-200 bg-green-50">
        <div class="flex gap-4 p-6">
            <span class="material-symbols-outlined text-3xl text-green-700">task_alt</span>
            <div>
                <h2 class="text-xl font-semibold text-green-800">Congratulations!</h2>
                <p class="mt-1 text-sm text-green-700">You have been <strong>ACCEPTED</strong> for the position.</p>
            </div>
        </div>

        <div class="space-y-2 px-6 pb-6 text-sm text-green-800">
            <p>
                After careful evaluation of your qualifications and submitted documents,
                we are pleased to inform you that you have been selected for the position
                of <strong>Administrative Aide</strong>.
            </p>
            <p>
                Further instructions regarding onboarding and requirements will be communicated through this system.
            </p>
        </div>
    </section>
<?php elseif ($applicationStatus === 'rejected'): ?>
    <section class="mb-8 rounded-xl border border-red-200 bg-red-50">
        <div class="flex gap-4 p-6">
            <span class="material-symbols-outlined text-3xl text-red-700">cancel</span>
            <div>
                <h2 class="text-xl font-semibold text-red-800">Application Not Successful</h2>
                <p class="mt-1 text-sm text-red-700">We regret to inform you that your application was not selected.</p>
            </div>
        </div>

        <div class="space-y-2 px-6 pb-6 text-sm text-red-800">
            <p>
                After careful assessment, your application did not meet the current requirements for this position.
            </p>
            <p>
                We encourage you to apply for future vacancies that match your qualifications.
            </p>
        </div>
    </section>
<?php else: ?>
    <section class="mb-8 rounded-xl border border-yellow-200 bg-yellow-50">
        <div class="flex gap-4 p-6">
            <span class="material-symbols-outlined text-3xl text-yellow-700">hourglass_top</span>
            <div>
                <h2 class="text-xl font-semibold text-yellow-900">Decision Pending</h2>
                <p class="mt-1 text-sm text-yellow-800">Your application is still under final review by HR.</p>
            </div>
        </div>

        <div class="space-y-2 px-6 pb-6 text-sm text-yellow-900">
            <p>Thank you for your patience. A final decision will be posted in your applicant portal soon.</p>
        </div>
    </section>
<?php endif; ?>

<section class="mb-8 rounded-xl border bg-white">
    <header class="flex items-center gap-2 border-b px-6 py-4">
        <span class="material-symbols-outlined text-green-700">notes</span>
        <h2 class="text-lg font-semibold text-gray-800">Remarks</h2>
    </header>

    <div class="p-6 text-sm text-gray-700">
        <p>
            Decision was based on the overall evaluation of qualifications,
            experience, and submitted requirements.
        </p>
    </div>
</section>

<div class="flex flex-wrap justify-end gap-3">
    <a href="job-list.php" class="inline-flex items-center gap-1 rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
        View Other Jobs
    </a>

    <a href="applications.php" class="inline-flex items-center gap-2 rounded-md bg-green-700 px-5 py-2 text-sm font-medium text-white hover:bg-green-800">
        View Application Status
    </a>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
