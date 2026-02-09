<?php
// Determine active page
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="w-64 bg-white border-r flex flex-col">

    <!-- BRAND / SYSTEM -->
    <div class="px-6 py-5 border-b">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-green-700">
                account_balance
            </span>
            <div>
                <p class="text-sm font-semibold text-gray-800">
                    Department of Agriculture
                </p>
                <p class="text-xs text-gray-500">
                    Recruitment Portal
                </p>
            </div>
        </div>
    </div>

    <!-- NAVIGATION -->
    <nav class="flex-1 px-3 py-4 text-sm space-y-1">

        <!-- Dashboard -->
        <a href="dashboard.php"
           class="flex items-center gap-3 px-3 py-2 rounded-md
           <?= $currentPage === 'dashboard.php'
                ? 'bg-green-50 text-green-700 font-medium'
                : 'text-gray-700 hover:bg-gray-50'; ?>">
            <span class="material-symbols-outlined text-base">
                dashboard
            </span>
            Dashboard
        </a>

        <!-- Job Listings -->
        <a href="job-list.php"
           class="flex items-center gap-3 px-3 py-2 rounded-md
           <?= $currentPage === 'job-list.php'
                ? 'bg-green-50 text-green-700 font-medium'
                : 'text-gray-700 hover:bg-gray-50'; ?>">
            <span class="material-symbols-outlined text-base">
                list_alt
            </span>
            Job Listings
        </a>

        <!-- Submit Application -->
        <a href="apply.php"
           class="flex items-center gap-3 px-3 py-2 rounded-md
           <?= $currentPage === 'apply.php'
                ? 'bg-green-50 text-green-700 font-medium'
                : 'text-gray-700 hover:bg-gray-50'; ?>">
            <span class="material-symbols-outlined text-base">
                edit_document
            </span>
            Submit Application
        </a>

        <!-- My Applications -->
        <a href="applications.php"
           class="flex items-center gap-3 px-3 py-2 rounded-md
           <?= $currentPage === 'applications.php'
                ? 'bg-green-50 text-green-700 font-medium'
                : 'text-gray-700 hover:bg-gray-50'; ?>">
            <span class="material-symbols-outlined text-base">
                folder_shared
            </span>
            My Applications
        </a>

        <!-- Notifications -->
        <a href="notifications.php"
           class="flex items-center gap-3 px-3 py-2 rounded-md
           <?= $currentPage === 'notifications.php'
                ? 'bg-green-50 text-green-700 font-medium'
                : 'text-gray-700 hover:bg-gray-50'; ?>">
            <span class="material-symbols-outlined text-base">
                notifications
            </span>
            Notifications
        </a>

        <!-- Divider -->
        <div class="my-3 border-t"></div>

        <!-- Account Profile -->
        <a href="profile.php"
           class="flex items-center gap-3 px-3 py-2 rounded-md
           <?= $currentPage === 'profile.php'
                ? 'bg-green-50 text-green-700 font-medium'
                : 'text-gray-700 hover:bg-gray-50'; ?>">
            <span class="material-symbols-outlined text-base">
                person
            </span>
            Account Profile
        </a>

        <!-- Help & Support -->
        <a href="support.php"
           class="flex items-center gap-3 px-3 py-2 rounded-md
           <?= $currentPage === 'support.php'
                ? 'bg-green-50 text-green-700 font-medium'
                : 'text-gray-700 hover:bg-gray-50'; ?>">
            <span class="material-symbols-outlined text-base">
                help
            </span>
            Help & Support
        </a>

    </nav>

    <!-- FOOTER / ROLE -->
    <div class="px-6 py-4 border-t text-xs text-gray-500">
        Logged in as <span class="font-medium text-gray-700">Applicant</span>
    </div>

</aside>
