<?php
ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-8 flex items-start gap-4">
    <span class="material-symbols-outlined text-green-700 text-4xl">
        notifications
    </span>
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">
            Notifications
        </h1>
        <p class="text-sm text-gray-500">
            System-generated updates related to your applications.
        </p>
    </div>
</div>

<!-- FILTER BAR (UI ONLY FOR NOW) -->
<section class="bg-white border rounded-lg mb-6">
    <div class="p-4 flex flex-wrap gap-3 text-sm">
        <span class="font-medium text-gray-600">Filter:</span>

        <button class="px-3 py-1 rounded-full bg-green-100 text-green-700">
            All
        </button>
        <button class="px-3 py-1 rounded-full border text-gray-600 hover:bg-gray-50">
            Unread
        </button>
        <button class="px-3 py-1 rounded-full border text-gray-600 hover:bg-gray-50">
            Application
        </button>
        <button class="px-3 py-1 rounded-full border text-gray-600 hover:bg-gray-50">
            System
        </button>
    </div>
</section>

<!-- NOTIFICATIONS LIST -->
<section class="bg-white border rounded-lg">

    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">
            Notification Inbox
        </h2>
    </header>

    <div class="divide-y">

        <!-- UNREAD - APPLICATION -->
        <div class="p-5 flex gap-4 bg-green-50">
            <span class="material-symbols-outlined text-green-700 mt-1">
                assignment
            </span>

            <div class="flex-1">
                <div class="flex justify-between items-start">
                    <h3 class="font-medium text-gray-800">
                        Application Submitted Successfully
                    </h3>
                    <span class="text-xs text-gray-500">
                        Feb 10, 2026 · 10:45 AM
                    </span>
                </div>

                <p class="text-sm text-gray-600 mt-1">
                    Your application for <strong>Administrative Aide</strong>
                    has been received and is now under initial review.
                </p>

                <div class="mt-2 flex items-center gap-3 text-xs">
                    <span class="inline-flex items-center gap-1 text-green-700 font-medium">
                        <span class="material-symbols-outlined text-xs">
                            mark_email_unread
                        </span>
                        Unread
                    </span>

                    <a href="applications.php"
                       class="text-green-700 hover:underline">
                        View application
                    </a>
                </div>
            </div>
        </div>

        <!-- READ - REVIEW -->
        <div class="p-5 flex gap-4">
            <span class="material-symbols-outlined text-blue-600 mt-1">
                fact_check
            </span>

            <div class="flex-1">
                <div class="flex justify-between items-start">
                    <h3 class="font-medium text-gray-800">
                        Application Under Review
                    </h3>
                    <span class="text-xs text-gray-500">
                        Feb 8, 2026 · 3:12 PM
                    </span>
                </div>

                <p class="text-sm text-gray-600 mt-1">
                    HR is currently evaluating your submitted documents and
                    qualifications.
                </p>
            </div>
        </div>

        <!-- WARNING -->
        <div class="p-5 flex gap-4">
            <span class="material-symbols-outlined text-yellow-600 mt-1">
                warning
            </span>

            <div class="flex-1">
                <div class="flex justify-between items-start">
                    <h3 class="font-medium text-gray-800">
                        Incomplete Document Detected
                    </h3>
                    <span class="text-xs text-gray-500">
                        Feb 7, 2026 · 9:30 AM
                    </span>
                </div>

                <p class="text-sm text-gray-600 mt-1">
                    One or more required documents are missing or unclear.
                    Please review your submission.
                </p>

                <a href="applications.php"
                   class="inline-flex items-center gap-1 text-sm text-green-700 hover:underline mt-2">
                    Review application
                    <span class="material-symbols-outlined text-sm">
                        arrow_forward
                    </span>
                </a>
            </div>
        </div>

        <!-- SYSTEM -->
        <div class="p-5 flex gap-4">
            <span class="material-symbols-outlined text-gray-500 mt-1">
                info
            </span>

            <div class="flex-1">
                <div class="flex justify-between items-start">
                    <h3 class="font-medium text-gray-800">
                        System Announcement
                    </h3>
                    <span class="text-xs text-gray-500">
                        Feb 6, 2026 · 1:00 PM
                    </span>
                </div>

                <p class="text-sm text-gray-600 mt-1">
                    New job vacancies have been posted. You may browse
                    available positions in the Job Listings page.
                </p>
            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <div class="px-6 py-3 border-t bg-gray-50 text-sm text-gray-600">
        Showing recent notifications
    </div>

</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
