<?php

$pageTitle = 'Dashboard | DA HRIS';
$activePage = 'dashboard.php';
$breadcrumbs = ['Dashboard'];

ob_start();
?>

<!-- HERO -->
<section class="mb-6 rounded-2xl border bg-white p-6 sm:p-7">
    <div class="overflow-hidden rounded-2xl border bg-gradient-to-r from-green-50 via-white to-green-50 p-5 sm:p-7">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <div class="inline-flex rounded-2xl bg-green-700 p-3 text-white">
                    <span class="material-symbols-outlined text-3xl">emoji_people</span>
                </div>
                <div>
                    <p class="text-sm text-green-700 font-medium">Welcome back, Applicant</p>
                    <h1 class="mt-1 text-2xl font-semibold text-gray-800 sm:text-3xl">Your Recruitment Journey</h1>
                    <p class="mt-2 max-w-2xl text-sm text-gray-600">
                        Track your application, discover opportunities, and stay updated with HR announcements in one place.
                    </p>
                    <div class="mt-4 inline-flex items-center gap-1 rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800">
                        <span class="material-symbols-outlined text-xs">hourglass_top</span>
                        Current status: Under Review
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 text-sm sm:min-w-[260px]">
                <div class="rounded-xl border bg-white px-4 py-3 text-center">
                    <p class="font-semibold text-gray-800">1</p>
                    <p class="text-xs text-gray-500">Active Application</p>
                </div>
                <div class="rounded-xl border bg-white px-4 py-3 text-center">
                    <p class="font-semibold text-gray-800">12</p>
                    <p class="text-xs text-gray-500">Open Jobs</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SNAPSHOT CARDS -->
<section class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Position Applied</p>
        <p class="mt-2 font-semibold text-gray-800">Administrative Aide</p>
    </article>
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Current Stage</p>
        <p class="mt-2 font-semibold text-gray-800">Qualification Review</p>
    </article>
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Date Applied</p>
        <p class="mt-2 font-semibold text-gray-800">Feb 5, 2026</p>
    </article>
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Latest Update</p>
        <p class="mt-2 font-semibold text-gray-800">Feb 10, 2026</p>
    </article>
</section>

<!-- PROGRESS + CHECKLIST -->
<section class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="xl:col-span-2 rounded-xl border bg-white">
        <header class="flex items-center justify-between border-b px-6 py-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-green-700">route</span>
                <h2 class="text-lg font-semibold text-gray-800">Application Progress</h2>
            </div>
            <a href="applications.php" class="text-sm font-medium text-green-700 hover:underline">Open full tracker</a>
        </header>

        <div class="p-6">
            <ol class="space-y-4">
                <li class="flex items-start gap-3 rounded-lg border bg-gray-50 p-4">
                    <span class="mt-0.5 inline-flex h-7 w-7 items-center justify-center rounded-full bg-green-700 text-white">
                        <span class="material-symbols-outlined text-[15px]">task_alt</span>
                    </span>
                    <div>
                        <p class="font-medium text-gray-800">Application Submitted</p>
                        <p class="text-sm text-gray-600">Your documents were successfully sent to HR.</p>
                    </div>
                </li>

                <li class="flex items-start gap-3 rounded-lg border bg-gray-50 p-4">
                    <span class="mt-0.5 inline-flex h-7 w-7 items-center justify-center rounded-full bg-green-700 text-white">
                        <span class="material-symbols-outlined text-[15px]">fact_check</span>
                    </span>
                    <div>
                        <p class="font-medium text-gray-800">Document & Qualification Review</p>
                        <p class="text-sm text-gray-600">Your credentials are currently being assessed.</p>
                    </div>
                </li>

                <li class="flex items-start gap-3 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                    <span class="mt-0.5 inline-flex h-7 w-7 items-center justify-center rounded-full bg-yellow-500 text-white">
                        <span class="material-symbols-outlined text-[15px]">hourglass_top</span>
                    </span>
                    <div>
                        <p class="font-medium text-yellow-900">Awaiting Final Decision</p>
                        <p class="text-sm text-yellow-800">You’ll receive a notification once HR finalizes results.</p>
                    </div>
                </li>
            </ol>
        </div>
    </div>

    <aside class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h3 class="text-lg font-semibold text-gray-800">Your Checklist</h3>
        </header>
        <div class="space-y-3 p-6 text-sm">
            <a href="profile.php" class="block rounded-lg border bg-gray-50 p-4 transition hover:border-green-600">
                <p class="font-medium text-gray-800">Update profile details</p>
                <p class="mt-1 text-gray-600">Keep your contact info accurate for HR notices.</p>
            </a>
            <a href="notifications.php" class="block rounded-lg border bg-gray-50 p-4 transition hover:border-green-600">
                <p class="font-medium text-gray-800">Check notifications</p>
                <p class="mt-1 text-gray-600">Review unread updates from your application inbox.</p>
            </a>
            <a href="support.php" class="block rounded-lg border bg-gray-50 p-4 transition hover:border-green-600">
                <p class="font-medium text-gray-800">Need assistance?</p>
                <p class="mt-1 text-gray-600">Contact HR or browse FAQs for quick help.</p>
            </a>
        </div>
    </aside>
</section>

<!-- ACTIONS + UPDATES -->
<section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <div class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h3 class="font-semibold text-gray-800">Quick Actions</h3>
        </header>

        <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">
            <a href="job-list.php" class="group rounded-xl border bg-white p-4 transition hover:border-green-600 hover:bg-green-50/40">
                <div class="mb-2 inline-flex rounded-lg bg-green-50 p-2 text-green-700">
                    <span class="material-symbols-outlined">list_alt</span>
                </div>
                <p class="font-medium text-gray-800">Browse Jobs</p>
                <p class="mt-1 text-sm text-gray-500">See available vacancies</p>
            </a>

            <a href="apply.php" class="group rounded-xl border bg-white p-4 transition hover:border-green-600 hover:bg-green-50/40">
                <div class="mb-2 inline-flex rounded-lg bg-green-50 p-2 text-green-700">
                    <span class="material-symbols-outlined">edit_document</span>
                </div>
                <p class="font-medium text-gray-800">Submit Application</p>
                <p class="mt-1 text-sm text-gray-500">Apply for a new position</p>
            </a>

            <a href="applications.php" class="group rounded-xl border bg-white p-4 transition hover:border-green-600 hover:bg-green-50/40">
                <div class="mb-2 inline-flex rounded-lg bg-green-50 p-2 text-green-700">
                    <span class="material-symbols-outlined">folder_shared</span>
                </div>
                <p class="font-medium text-gray-800">Track Application</p>
                <p class="mt-1 text-sm text-gray-500">View status and milestones</p>
            </a>

            <a href="notifications.php" class="group rounded-xl border bg-white p-4 transition hover:border-green-600 hover:bg-green-50/40">
                <div class="mb-2 inline-flex rounded-lg bg-green-50 p-2 text-green-700">
                    <span class="material-symbols-outlined">notifications</span>
                </div>
                <p class="font-medium text-gray-800">Open Notifications</p>
                <p class="mt-1 text-sm text-gray-500">Read important updates</p>
            </a>
        </div>
    </div>

    <div class="rounded-xl border bg-white">
        <header class="flex items-center justify-between border-b px-6 py-4">
            <h3 class="font-semibold text-gray-800">Latest Updates</h3>
            <a href="notifications.php" class="text-sm text-green-700 hover:underline">View inbox</a>
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
                        <p class="mt-1 text-sm text-gray-600">Your application for <strong>Administrative Aide</strong> is now in review.</p>
                    </div>
                </div>
            </article>

            <article class="p-5">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined mt-0.5 text-blue-600">campaign</span>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="font-medium text-gray-800">New vacancies are available</h4>
                            <span class="text-xs text-gray-500">Feb 10, 2026</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">More opportunities were posted for this recruitment cycle.</p>
                        <a href="job-list.php" class="mt-2 inline-flex items-center gap-1 text-sm text-green-700 hover:underline">
                            Check listings
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
                            <h4 class="font-medium text-gray-800">Document quality reminder</h4>
                            <span class="text-xs text-gray-500">Feb 7, 2026</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">Upload clear and complete documents to avoid delays.</p>
                    </div>
                </div>
            </article>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
