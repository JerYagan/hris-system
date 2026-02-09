<?php
ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-8 flex items-start gap-4">
    <span class="material-symbols-outlined text-green-700 text-4xl">
        dashboard
    </span>
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">
            Recruitment Dashboard
        </h1>
        <p class="text-sm text-gray-500">
            Central hub for managing your job applications and recruitment updates.
        </p>
    </div>
</div>

<!-- APPLICATION OVERVIEW -->
<section class="bg-white border rounded-lg mb-8">

    <!-- Header -->
    <header class="px-6 py-4 border-b flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-green-700">
                assignment
            </span>
            <h2 class="text-lg font-semibold text-gray-800">
                Application Overview
            </h2>
        </div>

        <!-- Status Badge (Prominent) -->
        <span
            class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
            <span class="material-symbols-outlined text-xs">
                hourglass_top
            </span>
            Under Review
        </span>
    </header>

    <!-- Content -->
    <div class="p-6 grid grid-cols-1 lg:grid-cols-5 gap-6 items-center">

        <!-- Position -->
        <div class="flex gap-3 lg:col-span-2">
            <span class="material-symbols-outlined text-gray-400">
                work
            </span>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">
                    Position Applied For
                </p>
                <p class="font-medium text-gray-800">
                    Administrative Aide
                </p>
            </div>
        </div>

        <!-- Current Phase -->
        <div class="flex gap-3">
            <span class="material-symbols-outlined text-gray-400">
                fact_check
            </span>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">
                    Current Phase
                </p>
                <p class="text-gray-700">
                    Document & Qualification Review
                </p>
            </div>
        </div>

        <!-- Next Step -->
        <div class="flex gap-3">
            <span class="material-symbols-outlined text-gray-400">
                timeline
            </span>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">
                    Next Step
                </p>
                <p class="text-gray-700">
                    Wait for HR evaluation result
                </p>
            </div>
        </div>

        <!-- Action -->
        <div class="flex justify-start lg:justify-end">
            <a href="applications.php"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md border border-green-700 text-green-700 hover:bg-green-50">
                <span class="material-symbols-outlined text-sm">
                    manage_search
                </span>
                View Details
            </a>
        </div>

    </div>

    <!-- Subtle Footer Hint -->
    <div class="px-6 py-3 border-t bg-gray-50 text-sm text-gray-600">
        This application is currently being processed by the Human Resources Office.
    </div>

</section>


<!-- QUICK ACTIONS -->
<section class="mb-8">
    <h3 class="text-sm font-semibold text-gray-600 uppercase mb-4">
        Quick Actions
    </h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <a href="job-list.php" class="bg-white border rounded-lg p-5 hover:border-green-600 transition">
            <div class="flex gap-3">
                <span class="material-symbols-outlined text-green-700">
                    list_alt
                </span>
                <div>
                    <p class="font-medium text-gray-800">Job Listings</p>
                    <p class="text-sm text-gray-500 mt-1">
                        View available positions
                    </p>
                </div>
            </div>
        </a>

        <a href="apply.php" class="bg-white border rounded-lg p-5 hover:border-green-600 transition">
            <div class="flex gap-3">
                <span class="material-symbols-outlined text-green-700">
                    edit_document
                </span>
                <div>
                    <p class="font-medium text-gray-800">Submit Application</p>
                    <p class="text-sm text-gray-500 mt-1">
                        Apply for a job vacancy
                    </p>
                </div>
            </div>
        </a>

        <a href="applications.php" class="bg-white border rounded-lg p-5 hover:border-green-600 transition">
            <div class="flex gap-3">
                <span class="material-symbols-outlined text-green-700">
                    folder_shared
                </span>
                <div>
                    <p class="font-medium text-gray-800">Application Management</p>
                    <p class="text-sm text-gray-500 mt-1">
                        Track submitted applications
                    </p>
                </div>
            </div>
        </a>

        <a href="notifications.php" class="bg-white border rounded-lg p-5 hover:border-green-600 transition">
            <div class="flex gap-3">
                <span class="material-symbols-outlined text-green-700">
                    notifications
                </span>
                <div>
                    <p class="font-medium text-gray-800">Notifications</p>
                    <p class="text-sm text-gray-500 mt-1">
                        View recruitment updates
                    </p>
                </div>
            </div>
        </a>

    </div>
</section>

<!-- SYSTEM UPDATES -->
<section class="flex items-start space-x-6">

    <!-- ANNOUNCEMENTS -->
    <div class="bg-white border rounded-lg">

        <header class="px-6 py-4 border-b flex items-center gap-2">
            <span class="material-symbols-outlined text-green-700">
                campaign
            </span>
            <h3 class="font-semibold text-gray-800">
                Announcements
            </h3>
        </header>

        <div class="divide-y">

            <!-- Announcement Item -->
            <div class="p-5 flex gap-4">
                <div class="flex-shrink-0 mt-1">
                    <span class="material-symbols-outlined text-green-600">
                        info
                    </span>
                </div>

                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <h4 class="font-medium text-gray-800">
                            New Job Vacancies Open
                        </h4>
                        <span class="text-xs text-gray-500">
                            Feb 10, 2026
                        </span>
                    </div>

                    <p class="text-sm text-gray-600 mt-1">
                        The Department of Agriculture has opened new job vacancies
                        for the March recruitment intake.
                    </p>

                    <a href="job-list.php"
                        class="inline-flex items-center gap-1 text-sm text-green-700 hover:underline mt-2">
                        View job listings
                        <span class="material-symbols-outlined text-sm">
                            arrow_forward
                        </span>
                    </a>
                </div>
            </div>

            <!-- Announcement Item -->
            <div class="p-5 flex gap-4">
                <div class="flex-shrink-0 mt-1">
                    <span class="material-symbols-outlined text-yellow-600">
                        warning
                    </span>
                </div>

                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <h4 class="font-medium text-gray-800">
                            Document Submission Reminder
                        </h4>
                        <span class="text-xs text-gray-500">
                            Feb 7, 2026
                        </span>
                    </div>

                    <p class="text-sm text-gray-600 mt-1">
                        Applicants are reminded to ensure that all required documents
                        are complete, clear, and properly uploaded.
                    </p>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="px-6 py-3 border-t bg-gray-50 text-right">
            <a href="announcements.php" class="text-sm text-green-700 hover:underline">
                View all announcements
            </a>
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

            <!-- UNREAD -->
            <div class="p-5 flex gap-4 bg-green-50">
                <span class="material-symbols-outlined text-green-700 mt-1">
                    assignment
                </span>

                <div class="flex-1">
                    <div class="flex justify-between items-start">
                        <h4 class="font-medium text-gray-800">
                            Application Submitted Successfully
                        </h4>
                        <span class="text-xs text-gray-500">
                            Feb 10, 2026
                        </span>
                    </div>

                    <p class="text-sm text-gray-600 mt-1">
                        Your application for <strong>Administrative Aide</strong> has
                        been received and is now under initial review.
                    </p>

                    <span class="inline-block mt-2 text-xs font-medium text-green-700">
                        Unread
                    </span>
                </div>
            </div>

            <!-- READ -->
            <div class="p-5 flex gap-4">
                <span class="material-symbols-outlined text-blue-600 mt-1">
                    fact_check
                </span>

                <div class="flex-1">
                    <div class="flex justify-between items-start">
                        <h4 class="font-medium text-gray-800">
                            Application Under Review
                        </h4>
                        <span class="text-xs text-gray-500">
                            Feb 8, 2026
                        </span>
                    </div>

                    <p class="text-sm text-gray-600 mt-1">
                        HR is currently evaluating your submitted documents and qualifications.
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
                        <h4 class="font-medium text-gray-800">
                            Incomplete Document Detected
                        </h4>
                        <span class="text-xs text-gray-500">
                            Feb 7, 2026
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

        </div>

        <!-- FOOTER -->
        <div class="px-6 py-3 border-t bg-gray-50 flex justify-between items-center text-sm">
            <span class="text-gray-500">
                Showing latest notifications
            </span>
            <a href="notifications.php" class="text-green-700 hover:underline">
                View all
            </a>
        </div>

    </div>


</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
