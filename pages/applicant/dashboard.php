<?php

$pageTitle = 'Dashboard | DA HRIS';
$activePage = 'dashboard.php';
$breadcrumbs = ['Dashboard'];

ob_start();
?>

<!-- HERO -->
<section class="mb-6 rounded-2xl border bg-white p-6 lg:p-7">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-4">
            <div class="rounded-xl bg-green-50 p-3 text-green-700">
                <span class="material-symbols-outlined text-4xl">dashboard</span>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 lg:text-3xl">
                    Recruitment Dashboard
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Central hub for tracking applications, updates, and next recruitment steps.
                </p>
                <div class="mt-3 inline-flex items-center gap-1 rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800">
                    <span class="material-symbols-outlined text-xs">hourglass_top</span>
                    Under Review
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="applications.php" class="inline-flex items-center gap-2 rounded-md border border-green-700 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50">
                <span class="material-symbols-outlined text-sm">manage_search</span>
                View Details
            </a>
            <a href="job-list.php" class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                <span class="material-symbols-outlined text-sm">work</span>
                Browse Jobs
            </a>
        </div>
    </div>
</section>

<!-- KEY STATS -->
<section class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Position Applied</p>
        <p class="mt-2 text-base font-semibold text-gray-800">Administrative Aide</p>
    </div>
    <div class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Current Phase</p>
        <p class="mt-2 text-base font-semibold text-gray-800">Qualification Review</p>
    </div>
    <div class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Date Applied</p>
        <p class="mt-2 text-base font-semibold text-gray-800">Feb 5, 2026</p>
    </div>
    <div class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Latest Update</p>
        <p class="mt-2 text-base font-semibold text-gray-800">Feb 10, 2026</p>
    </div>
</section>

<!-- APPLICATION SUMMARY -->
<section class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="xl:col-span-2 rounded-xl border bg-white">
        <header class="flex items-center justify-between border-b px-6 py-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-green-700">assignment</span>
                <h2 class="text-lg font-semibold text-gray-800">Application Overview</h2>
            </div>
            <a href="applications.php" class="text-sm font-medium text-green-700 hover:underline">Open tracker</a>
        </header>

        <div class="p-6">
            <ol class="relative ml-2 border-l border-gray-200 pl-6">
                <li class="mb-7">
                    <span class="absolute -left-[11px] mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-green-700 text-white">
                        <span class="material-symbols-outlined text-[12px]">task_alt</span>
                    </span>
                    <h3 class="text-sm font-semibold text-gray-800">Application Submitted</h3>
                    <p class="mt-1 text-sm text-gray-600">Your application has been received by the HR team.</p>
                </li>
                <li class="mb-7">
                    <span class="absolute -left-[11px] mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-green-700 text-white">
                        <span class="material-symbols-outlined text-[12px]">fact_check</span>
                    </span>
                    <h3 class="text-sm font-semibold text-gray-800">Document & Qualification Review</h3>
                    <p class="mt-1 text-sm text-gray-600">Your submitted files and qualifications are under evaluation.</p>
                </li>
                <li>
                    <span class="absolute -left-[11px] mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-yellow-500 text-white">
                        <span class="material-symbols-outlined text-[12px]">hourglass_top</span>
                    </span>
                    <h3 class="text-sm font-semibold text-gray-800">Awaiting Final Decision</h3>
                    <p class="mt-1 text-sm text-gray-600">Wait for notification once HR finalizes the recruitment result.</p>
                </li>
            </ol>
        </div>
    </div>

    <aside class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h3 class="text-lg font-semibold text-gray-800">What to do next</h3>
        </header>
        <div class="space-y-4 p-6 text-sm">
            <div class="rounded-lg border bg-gray-50 p-4">
                <p class="font-medium text-gray-800">Keep contact details updated</p>
                <p class="mt-1 text-gray-600">Ensure your profile information is current for fast HR communication.</p>
            </div>
            <div class="rounded-lg border bg-gray-50 p-4">
                <p class="font-medium text-gray-800">Check notifications regularly</p>
                <p class="mt-1 text-gray-600">Important updates about your application appear in the inbox.</p>
            </div>
            <div class="rounded-lg border bg-gray-50 p-4">
                <p class="font-medium text-gray-800">Prepare requirements</p>
                <p class="mt-1 text-gray-600">Have supporting documents ready for onboarding instructions.</p>
            </div>
        </div>
    </aside>
</section>

<!-- QUICK ACTIONS -->
<section class="mb-8">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-sm font-semibold uppercase text-gray-600">Quick Actions</h3>
        <a href="support.php" class="text-sm text-green-700 hover:underline">Need help?</a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="job-list.php" class="group rounded-xl border bg-white p-5 transition hover:border-green-600 hover:bg-green-50/40">
            <div class="mb-3 inline-flex rounded-lg bg-green-50 p-2 text-green-700">
                <span class="material-symbols-outlined">list_alt</span>
            </div>
            <p class="font-medium text-gray-800">Job Listings</p>
            <p class="mt-1 text-sm text-gray-500">Browse available positions</p>
        </a>

        <a href="apply.php" class="group rounded-xl border bg-white p-5 transition hover:border-green-600 hover:bg-green-50/40">
            <div class="mb-3 inline-flex rounded-lg bg-green-50 p-2 text-green-700">
                <span class="material-symbols-outlined">edit_document</span>
            </div>
            <p class="font-medium text-gray-800">Submit Application</p>
            <p class="mt-1 text-sm text-gray-500">Apply for a job vacancy</p>
        </a>

        <a href="applications.php" class="group rounded-xl border bg-white p-5 transition hover:border-green-600 hover:bg-green-50/40">
            <div class="mb-3 inline-flex rounded-lg bg-green-50 p-2 text-green-700">
                <span class="material-symbols-outlined">folder_shared</span>
            </div>
            <p class="font-medium text-gray-800">Application Tracker</p>
            <p class="mt-1 text-sm text-gray-500">Monitor your progress</p>
        </a>

        <a href="notifications.php" class="group rounded-xl border bg-white p-5 transition hover:border-green-600 hover:bg-green-50/40">
            <div class="mb-3 inline-flex rounded-lg bg-green-50 p-2 text-green-700">
                <span class="material-symbols-outlined">notifications</span>
            </div>
            <p class="font-medium text-gray-800">Notifications</p>
            <p class="mt-1 text-sm text-gray-500">Read recruitment updates</p>
        </a>
    </div>
</section>

<!-- SYSTEM UPDATES -->
<section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <div class="rounded-xl border bg-white">
        <header class="flex items-center justify-between border-b px-6 py-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-green-700">campaign</span>
                <h3 class="font-semibold text-gray-800">Announcements</h3>
            </div>
            <a href="announcements.php" class="text-sm text-green-700 hover:underline">View all</a>
        </header>

        <div class="divide-y">
            <article class="p-5">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined mt-0.5 text-green-600">info</span>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="font-medium text-gray-800">New Job Vacancies Open</h4>
                            <span class="text-xs text-gray-500">Feb 10, 2026</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">
                            The Department of Agriculture has opened new job vacancies for the March recruitment intake.
                        </p>
                        <a href="job-list.php" class="mt-2 inline-flex items-center gap-1 text-sm text-green-700 hover:underline">
                            View job listings
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </article>

            <article class="p-5">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined mt-0.5 text-yellow-600">warning</span>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="font-medium text-gray-800">Document Submission Reminder</h4>
                            <span class="text-xs text-gray-500">Feb 7, 2026</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">
                            Ensure all required documents are complete, clear, and properly uploaded.
                        </p>
                    </div>
                </div>
            </article>
        </div>
    </div>


    <!-- NOTIFICATIONS -->
    <div class="bg-white border rounded-lg">

        <header class="px-6 py-4 border-b flex items-center gap-2">
            <span class="material-symbols-outlined text-green-700">
                notifications
            </span>
            <h3 class="font-semibold text-gray-800">
                Notifications
            </h3>
        </header>

        <div class="divide-y">
            <article class="bg-green-50 p-5">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined mt-0.5 text-green-700">assignment</span>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="font-medium text-gray-800">Application Submitted Successfully</h4>
                            <span class="text-xs text-gray-500">Feb 10, 2026</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">
                            Your application for <strong>Administrative Aide</strong> has been received and is now under initial review.
                        </p>
                        <span class="mt-2 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Unread</span>
                    </div>
                </div>
            </article>

            <article class="p-5">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined mt-0.5 text-blue-600">fact_check</span>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="font-medium text-gray-800">Application Under Review</h4>
                            <span class="text-xs text-gray-500">Feb 8, 2026</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">
                            HR is evaluating your submitted documents and qualifications.
                        </p>
                    </div>
                </div>
            </article>

            <article class="p-5">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined mt-0.5 text-yellow-600">warning</span>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="font-medium text-gray-800">Incomplete Document Detected</h4>
                            <span class="text-xs text-gray-500">Feb 7, 2026</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">
                            One or more required documents are missing or unclear. Please review your submission.
                        </p>
                        <a href="applications.php" class="mt-2 inline-flex items-center gap-1 text-sm text-green-700 hover:underline">
                            Review application
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </article>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
