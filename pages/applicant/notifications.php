<?php
$pageTitle = 'Notifications | DA HRIS';
$activePage = 'notifications.php';
$breadcrumbs = ['Notifications'];

ob_start();
?>

<section class="mb-6 rounded-2xl border bg-white p-6 sm:p-7">
    <div class="rounded-2xl border bg-gradient-to-r from-green-50 via-white to-green-50 p-5 sm:p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <span class="material-symbols-outlined rounded-xl bg-green-700 p-2 text-3xl text-white">notifications</span>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Notification Inbox</h1>
                    <p class="mt-1 text-sm text-gray-600">Stay updated with changes to your applications and recruitment announcements.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="rounded-lg border bg-white px-4 py-3 text-center">
                    <p class="font-semibold text-gray-800">4</p>
                    <p class="text-xs text-gray-500">Recent Updates</p>
                </div>
                <div class="rounded-lg border bg-white px-4 py-3 text-center">
                    <p class="font-semibold text-gray-800">1</p>
                    <p class="text-xs text-gray-500">Unread</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mb-6 rounded-xl border bg-white p-4">
    <div class="flex flex-wrap items-center gap-2 text-sm">
        <span class="font-medium text-gray-600">Filter:</span>
        <button class="rounded-full bg-green-100 px-3 py-1 text-green-700">All</button>
        <button class="rounded-full border px-3 py-1 text-gray-600 hover:bg-gray-50">Unread</button>
        <button class="rounded-full border px-3 py-1 text-gray-600 hover:bg-gray-50">Application</button>
        <button class="rounded-full border px-3 py-1 text-gray-600 hover:bg-gray-50">System</button>
    </div>
</section>

<section class="rounded-xl border bg-white">
    <header class="border-b px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Recent Notifications</h2>
    </header>

    <div class="space-y-4 p-6">
        <article class="rounded-xl border border-green-200 bg-green-50 p-5">
            <div class="flex gap-3">
                <span class="material-symbols-outlined mt-1 text-green-700">assignment</span>
                <div class="flex-1">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <h3 class="font-medium text-gray-800">Application Submitted Successfully</h3>
                        <span class="text-xs text-gray-500">Feb 10, 2026 · 10:45 AM</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">Your application for <strong>Administrative Aide</strong> has been received and is now under initial review.</p>

                    <div class="mt-2 flex items-center gap-3 text-xs">
                        <span class="inline-flex items-center gap-1 font-medium text-green-700">
                            <span class="material-symbols-outlined text-xs">mark_email_unread</span>
                            Unread
                        </span>
                        <a href="applications.php" class="text-green-700 hover:underline">View application</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="rounded-xl border p-5">
            <div class="flex gap-3">
                <span class="material-symbols-outlined mt-1 text-blue-600">fact_check</span>
                <div class="flex-1">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <h3 class="font-medium text-gray-800">Application Under Review</h3>
                        <span class="text-xs text-gray-500">Feb 8, 2026 · 3:12 PM</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">HR is currently evaluating your submitted documents and qualifications.</p>
                </div>
            </div>
        </article>

        <article class="rounded-xl border p-5">
            <div class="flex gap-3">
                <span class="material-symbols-outlined mt-1 text-yellow-600">warning</span>
                <div class="flex-1">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <h3 class="font-medium text-gray-800">Incomplete Document Detected</h3>
                        <span class="text-xs text-gray-500">Feb 7, 2026 · 9:30 AM</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">One or more required documents are missing or unclear. Please review your submission.</p>
                    <a href="applications.php" class="mt-2 inline-flex items-center gap-1 text-sm text-green-700 hover:underline">
                        Review application
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            </div>
        </article>

        <article class="rounded-xl border p-5">
            <div class="flex gap-3">
                <span class="material-symbols-outlined mt-1 text-gray-500">info</span>
                <div class="flex-1">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <h3 class="font-medium text-gray-800">System Announcement</h3>
                        <span class="text-xs text-gray-500">Feb 6, 2026 · 1:00 PM</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">New job vacancies have been posted. You may browse available positions in the Job Listings page.</p>
                    <a href="job-list.php" class="mt-2 inline-flex items-center gap-1 text-sm text-green-700 hover:underline">
                        Browse listings
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            </div>
        </article>
    </div>

    <div class="border-t bg-gray-50 px-6 py-3 text-sm text-gray-600">Showing recent notifications</div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
