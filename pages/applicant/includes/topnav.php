<?php
// Applicant Top Navigation
?>

<header class="bg-white border-b px-6 py-3 flex items-center justify-between">

    <!-- LEFT: PAGE CONTEXT -->
    <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-green-700">
            person_search
        </span>

        <div class="leading-tight">
            <p class="text-sm font-medium text-gray-800">
                Recruitment Portal
            </p>
            <p class="text-xs text-gray-500">
                Applicant Module
            </p>
        </div>
    </div>

    <!-- RIGHT: ACTIONS -->
    <div class="flex items-center gap-4">

        <!-- Notifications -->
        <a href="notifications.php"
           class="relative text-gray-600 hover:text-green-700">
            <span class="material-symbols-outlined">
                notifications
            </span>

            <!-- Unread badge (static for now) -->
            <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-600 rounded-full"></span>
        </a>

        <!-- Divider -->
        <span class="h-6 w-px bg-gray-200"></span>

        <!-- User Menu -->
        <div class="relative">
            <button id="applicantUserMenuBtn"
                    class="flex items-center gap-2 text-sm text-gray-700 hover:text-gray-900 focus:outline-none">
                <span class="material-symbols-outlined">
                    account_circle
                </span>
                <span class="hidden sm:block">
                    Applicant
                </span>
                <span class="material-symbols-outlined text-base">
                    expand_more
                </span>
            </button>

            <!-- Dropdown -->
            <div id="applicantUserMenu"
                 class="hidden absolute right-0 mt-2 w-44 bg-white border rounded-md shadow-sm text-sm z-50">

                <a href="profile.php"
                   class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 text-gray-700">
                    <span class="material-symbols-outlined text-sm">
                        person
                    </span>
                    Profile
                </a>

                <a href="support.php"
                   class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 text-gray-700">
                    <span class="material-symbols-outlined text-sm">
                        help
                    </span>
                    Help & Support
                </a>

                <div class="border-t"></div>

                <a href="/pages/auth/login.php"
                   class="flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50">
                    <span class="material-symbols-outlined text-sm">
                        logout
                    </span>
                    Logout
                </a>
            </div>
        </div>

    </div>
</header>
