<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside id="sidebar" class="sidebar w-72 bg-white border-r flex flex-col transition-all duration-300">

    <!-- SYSTEM HEADER -->
    <div class="relative px-6 py-5 border-b">
        <p class="text-sm font-semibold text-gray-800 sidebar-text">
            DA-ATI HRIS
        </p>
        <p class="text-xs text-gray-500 sidebar-text">
            Recruitment Portal
        </p>

        <button id="sidebarToggle"
            class="absolute top-4 right-4 p-1 rounded hover:bg-gray-100">
            <span class="material-symbols-outlined text-gray-600">
                menu
            </span>
        </button>
    </div>


    <!-- NAVIGATION -->
    <nav class="flex-1 px-4 py-4 text-sm space-y-6 overflow-y-auto">

        <!-- OVERVIEW -->
        <div>
            <p class="mb-2 text-xs font-semibold text-gray-400 uppercase sidebar-text">
                Overview
            </p>

            <a href="dashboard.php"
                class="flex items-center gap-3 px-3 py-2 rounded-md
               <?= $currentPage === 'dashboard.php'
                    ? 'bg-green-50 text-green-700 font-medium'
                    : 'text-gray-700 hover:bg-gray-50'; ?>">
                <span class="material-symbols-outlined text-base">
                    dashboard
                </span>
                <span class="sidebar-text transition-opacity duration-200">
                    Recruitment Dashboard
                </span>
            </a>

            <a href="notifications.php"
                class="flex items-center justify-between px-3 py-2 rounded-md
               <?= $currentPage === 'notifications.php'
                    ? 'bg-green-50 text-green-700 font-medium'
                    : 'text-gray-700 hover:bg-gray-50'; ?>">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-base">
                        notifications
                    </span>
                    <span class="sidebar-text transition-opacity duration-200">
                    Notifications
                    </span>
                </div>

                <!-- optional badge -->
                <span class="text-xs bg-red-500 text-white px-2 py-0.5 rounded-full">
                    3
                </span>
            </a>
        </div>

        <!-- JOB APPLICATION -->
        <div>
            <p class="mb-2 text-xs font-semibold text-gray-400 uppercase sidebar-text">
                Job Application
            </p>

            <a href="job-list.php"
                class="flex items-center gap-3 px-3 py-2 rounded-md
               <?= in_array($currentPage, ['job-list.php', 'job-view.php'])
                    ? 'bg-green-50 text-green-700 font-medium'
                    : 'text-gray-700 hover:bg-gray-50'; ?>">
                <span class="material-symbols-outlined text-base">
                    work_outline
                </span>
                <span class="sidebar-text transition-opacity duration-200">
                    Job Listings
                </span>
            </a>

            <a href="apply.php"
                class="flex items-center gap-3 px-3 py-2 rounded-md
               <?= $currentPage === 'apply.php'
                    ? 'bg-green-50 text-green-700 font-medium'
                    : 'text-gray-700 hover:bg-gray-50'; ?>">
                <span class="material-symbols-outlined text-base">
                    edit_document
                </span>
                <span class="sidebar-text transition-opacity duration-200">
                    Submit Application
                </span>
            </a>
        </div>

        <!-- APPLICATION STATUS -->
        <div>
            <p class="mb-2 text-xs font-semibold text-gray-400 uppercase sidebar-text">
                Application Status
            </p>

            <a href="applications.php"
                class="flex items-center gap-3 px-3 py-2 rounded-md
               <?= $currentPage === 'applications.php'
                    ? 'bg-green-50 text-green-700 font-medium'
                    : 'text-gray-700 hover:bg-gray-50'; ?>">
                <span class="material-symbols-outlined text-base">
                    timeline
                </span>
                <span class="sidebar-text transition-opacity duration-200">
                Application Tracking
                </span>
            </a>

            <a href="application-feedback.php"
                class="flex items-center gap-3 px-3 py-2 rounded-md
               <?= $currentPage === 'application-feedback.php'
                    ? 'bg-green-50 text-green-700 font-medium'
                    : 'text-gray-700 hover:bg-gray-50'; ?>">
                <span class="material-symbols-outlined text-base">
                    fact_check
                </span>
                <span class="sidebar-text transition-opacity duration-200">
                    Evaluation Feedback
                </span>
            </a>
        </div>

        <!-- ACCOUNT -->
        <div>
            <p class="mb-2 text-xs font-semibold text-gray-400 uppercase sidebar-text">
                Account
            </p>

            <a href="profile.php"
                class="flex items-center gap-3 px-3 py-2 rounded-md
               <?= $currentPage === 'profile.php'
                    ? 'bg-green-50 text-green-700 font-medium'
                    : 'text-gray-700 hover:bg-gray-50'; ?>">
                <span class="material-symbols-outlined text-base">
                    account_circle
                </span>
                <span class="sidebar-text transition-opacity duration-200">
                    Account Profile
                </span>
            </a>
        </div>

        <!-- SUPPORT -->
        <div>
            <p class="mb-2 text-xs font-semibold text-gray-400 uppercase sidebar-text">
                Support
            </p>

            <a href="support.php"
                class="flex items-center gap-3 px-3 py-2 rounded-md
               <?= $currentPage === 'support.php'
                    ? 'bg-green-50 text-green-700 font-medium'
                    : 'text-gray-700 hover:bg-gray-50'; ?>">
                <span class="material-symbols-outlined text-base">
                    help
                </span>
                <span class="sidebar-text transition-opacity duration-200">
                    Help & Support
                </span>
            </a>
        </div>

    </nav>

</aside>