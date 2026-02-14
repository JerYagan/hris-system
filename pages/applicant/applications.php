<?php
$pageTitle = 'Applications | DA HRIS';
$activePage = 'applications.php';
$breadcrumbs = ['My Applications'];

ob_start();
?>

<section class="mb-6 rounded-2xl border bg-white p-6 sm:p-7">
    <div class="rounded-2xl border bg-gradient-to-r from-green-50 via-white to-green-50 p-5 sm:p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <span class="material-symbols-outlined rounded-xl bg-green-700 p-2 text-3xl text-white">track_changes</span>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Application Tracking</h1>
                    <p class="mt-1 text-sm text-gray-600">Monitor your status, recent milestones, and upcoming recruitment decisions.</p>
                    <p class="mt-3 inline-flex rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800">Current Status: Under Evaluation</p>
                </div>
            </div>

            <a href="application-feedback.php" class="inline-flex items-center gap-2 rounded-md border border-green-700 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50">
                <span class="material-symbols-outlined text-sm">fact_check</span>
                Open Feedback Page
            </a>
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
        <p class="text-xs uppercase tracking-wide text-gray-500">Progress</p>
        <p class="mt-2 font-semibold text-gray-800">3 of 4 milestones</p>
    </article>
</section>

<section class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="xl:col-span-2 rounded-xl border bg-white">
        <header class="flex items-center justify-between border-b px-6 py-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-green-700">timeline</span>
                <h2 class="text-lg font-semibold text-gray-800">Application Progress</h2>
            </div>
            <a href="notifications.php" class="text-sm text-green-700 hover:underline">View notifications</a>
        </header>

        <div class="space-y-4 p-6">
            <article class="flex items-start gap-3 rounded-lg border bg-gray-50 p-4">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-green-700 text-white">
                    <span class="material-symbols-outlined text-[15px]">task_alt</span>
                </span>
                <div>
                    <h3 class="font-medium text-gray-800">Application Submitted</h3>
                    <p class="text-sm text-gray-600">Your application was successfully submitted.</p>
                    <p class="mt-1 text-xs text-gray-500">Feb 5, 2026</p>
                </div>
            </article>

            <article class="flex items-start gap-3 rounded-lg border bg-gray-50 p-4">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-green-700 text-white">
                    <span class="material-symbols-outlined text-[15px]">fact_check</span>
                </span>
                <div>
                    <h3 class="font-medium text-gray-800">Document & Qualification Review</h3>
                    <p class="text-sm text-gray-600">HR is reviewing your submitted documents and qualifications.</p>
                    <p class="mt-1 text-xs text-gray-500">Feb 7, 2026</p>
                </div>
            </article>

            <article class="flex items-start gap-3 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-yellow-500 text-white">
                    <span class="material-symbols-outlined text-[15px]">hourglass_top</span>
                </span>
                <div>
                    <h3 class="font-medium text-yellow-900">Under Evaluation</h3>
                    <p class="text-sm text-yellow-800">Your application is currently under evaluation by HR.</p>
                    <p class="mt-1 inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">In Progress</p>
                </div>
            </article>

            <article class="flex items-start gap-3 rounded-lg border bg-gray-50 p-4 opacity-60">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gray-300 text-white">
                    <span class="material-symbols-outlined text-[15px]">how_to_reg</span>
                </span>
                <div>
                    <h3 class="font-medium text-gray-700">Final Decision</h3>
                    <p class="text-sm text-gray-600">You will be notified once a decision has been made.</p>
                </div>
            </article>
        </div>
    </div>

    <aside class="space-y-6">
        <section class="rounded-xl border bg-white">
            <header class="border-b px-6 py-4">
                <h3 class="font-semibold text-gray-800">Next Steps</h3>
            </header>

            <div class="space-y-3 p-6 text-sm">
                <a href="profile.php" class="block rounded-lg border bg-gray-50 p-4 transition hover:border-green-600">
                    <p class="font-medium text-gray-800">Keep contact details updated</p>
                    <p class="mt-1 text-gray-600">Ensure HR can reach you quickly for final instructions.</p>
                </a>
                <a href="notifications.php" class="block rounded-lg border bg-gray-50 p-4 transition hover:border-green-600">
                    <p class="font-medium text-gray-800">Check notifications regularly</p>
                    <p class="mt-1 text-gray-600">All recruitment updates are posted in your inbox.</p>
                </a>
                <a href="support.php" class="block rounded-lg border bg-gray-50 p-4 transition hover:border-green-600">
                    <p class="font-medium text-gray-800">Need help?</p>
                    <p class="mt-1 text-gray-600">Contact HR for support on your application concerns.</p>
                </a>
            </div>
        </section>

        <section class="rounded-xl border bg-white p-6 text-sm text-gray-600">
            <p>
                Please ensure that your contact details are active.
                HR will notify you through the system once your application status changes.
            </p>
        </section>
    </aside>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
