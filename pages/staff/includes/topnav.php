<?php
$breadcrumbs = $breadcrumbs ?? ['Dashboard'];
?>

<header id="topnav" class="h-20 bg-white border-b flex items-center justify-between px-6 transition-transform duration-300 ease-in-out">
    <div class="flex items-center gap-3">
        <button id="sidebarToggle" class="text-gray-600 hover:text-gray-900 focus:outline-none mt-1" aria-label="Toggle sidebar">
            <span class="material-symbols-outlined">menu</span>
        </button>

        <div class="leading-tight">
            <div class="font-semibold text-gray-800">Human Resource Information System</div>

            <nav aria-label="Breadcrumb" class="hidden sm:block mt-0.5">
                <ol class="flex items-center gap-1 text-xs text-gray-500">
                    <?php foreach ($breadcrumbs as $index => $crumb): ?>
                        <li class="flex items-center gap-1">
                            <?php if ($index > 0): ?>
                                <span class="material-symbols-outlined text-[12px] text-gray-400">chevron_right</span>
                            <?php endif; ?>
                            <span class="<?= $index === count($breadcrumbs) - 1 ? 'text-gray-700 font-medium' : '' ?>">
                                <?= htmlspecialchars($crumb, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        </div>
    </div>

    <div class="flex items-center gap-6">
        <div class="relative hidden md:block">
            <span class="material-symbols-outlined absolute left-3 top-2 text-gray-400 text-sm">search</span>
            <input type="text" placeholder="Search..." class="pl-9 pr-4 py-1.5 border rounded-lg text-sm">
        </div>

        <div class="relative">
            <a href="notifications.php" class="text-gray-600 hover:text-green-700">
                <span class="material-symbols-outlined">notifications</span>
                <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] rounded-full px-1">3</span>
            </a>
        </div>

        <div class="relative" id="profileDropdown">
            <button id="profileToggle" class="flex items-center gap-2 focus:outline-none">
                <div class="w-8 h-8 rounded-full bg-green-600 text-white flex items-center justify-center text-xs font-semibold">
                    ST
                </div>

                <div class="leading-tight hidden md:block text-left">
                    <p class="text-sm font-medium text-gray-800">Staff User</p>
                    <p class="text-xs text-gray-500">Staff</p>
                </div>

                <span class="material-symbols-outlined text-gray-500 text-sm">expand_more</span>
            </button>

            <div id="profileMenu" class="absolute right-0 mt-2 w-48 bg-white border rounded-lg shadow-lg hidden z-50">
                <a href="profile.php" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100 text-gray-700">
                    <span class="material-symbols-outlined text-sm">person</span>
                    My Profile
                </a>

                <a href="reports.php" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100 text-gray-700">
                    <span class="material-symbols-outlined text-sm">analytics</span>
                    Reports
                </a>

                <div class="border-t my-1"></div>

                <a href="/hris-system/pages/auth/logout.php" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                    <span class="material-symbols-outlined text-sm">logout</span>
                    Logout
                </a>
            </div>
        </div>
    </div>
</header>
