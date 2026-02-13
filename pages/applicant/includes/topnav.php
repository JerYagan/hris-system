<?php
// Applicant Top Navigation
$breadcrumbs = $breadcrumbs ?? ['Dashboard'];
?>

<header id="topnav" class="bg-white border-b px-6 py-3 flex items-center justify-between transition-transform duration-200">

    <!-- LEFT: PAGE CONTEXT -->
    <div class="flex items-center gap-3">
        <button id="sidebarToggle" class="text-gray-600 hover:text-gray-900 focus:outline-none mt-1"
        aria-label="Toggle sidebar">
            <span class="material-symbols-outlined">menu</span>
        </button>

        <span class="material-symbols-outlined text-green-700">
            person_search
        </span>

        <nav aria-label="Breadcrumb" class="leading-tight text-sm">
            <ol class="flex items-center gap-1.5 text-gray-500">
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <li class="flex items-center gap-1.5">
                        <?php if ($index > 0): ?>
                            <span class="material-symbols-outlined text-xs text-gray-400">chevron_right</span>
                        <?php endif; ?>
                        <span class="<?= $index === count($breadcrumbs) - 1 ? 'text-gray-800 font-medium' : '' ?>">
                            <?= htmlspecialchars($crumb, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>
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
                 class="hidden absolute right-0 mt-2 w-52 bg-white border rounded-md shadow-sm text-sm z-50">

                <a href="dashboard.php"
                   class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 text-gray-700">
                    <span class="material-symbols-outlined text-sm">
                        dashboard
                    </span>
                    Dashboard
                </a>

                <a href="applications.php"
                   class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 text-gray-700">
                    <span class="material-symbols-outlined text-sm">
                        folder_shared
                    </span>
                    My Applications
                </a>

                <a href="job-list.php"
                   class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 text-gray-700">
                    <span class="material-symbols-outlined text-sm">
                        list_alt
                    </span>
                    Job Listings
                </a>

                <a href="apply.php"
                   class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 text-gray-700">
                    <span class="material-symbols-outlined text-sm">
                        edit_document
                    </span>
                    Apply
                </a>

                <div class="border-t"></div>

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
                   class="flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50 font-medium">
                    <span class="material-symbols-outlined text-sm">
                        logout
                    </span>
                    Logout
                </a>
            </div>
        </div>

    </div>
</header>

<script>
    (function () {
        const menuBtn = document.getElementById('applicantUserMenuBtn');
        const menu = document.getElementById('applicantUserMenu');

        if (!menuBtn || !menu) {
            return;
        }

        menuBtn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            menu.classList.toggle('hidden');
        });

        menu.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        document.addEventListener('click', function () {
            menu.classList.add('hidden');
        });
    })();
</script>
