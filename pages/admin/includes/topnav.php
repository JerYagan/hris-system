<?php
$breadcrumbs = $breadcrumbs ?? ['Dashboard'];
?>

<header id="topnav" class="h-16 admin-topbar sticky top-0 z-20 flex items-center justify-between px-5 transition-transform duration-300 ease-in-out bg-slate-900">
    <div class="flex items-center gap-3">
        <button id="sidebarToggle" class="admin-top-chip w-8 h-8 inline-flex items-center justify-center text-slate-300 hover:text-white focus:outline-none" aria-label="Toggle sidebar">
            <span class="material-symbols-outlined">menu</span>
        </button>

        <nav aria-label="Breadcrumb" class="leading-tight">
            <ol class="flex items-center gap-1.5 text-sm text-slate-400">
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <li class="flex items-center gap-1.5">
                        <?php if ($index > 0): ?>
                            <span class="material-symbols-outlined text-[13px] text-slate-500">chevron_right</span>
                        <?php endif; ?>
                        <span class="<?= $index === count($breadcrumbs) - 1 ? 'text-slate-100 font-semibold' : '' ?>">
                            <?= htmlspecialchars($crumb, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>
    </div>

    <div class="flex items-center gap-3">
        <div class="relative hidden lg:block">
            <span class="material-symbols-outlined absolute left-3 top-2 text-slate-400 text-sm">search</span>
            <input type="text" placeholder="Search..." class="admin-top-search pl-9 pr-3 py-1.5 w-56 text-sm placeholder:text-slate-500 focus:outline-none">
        </div>

        <div class="relative">
            <a href="notifications.php" class="admin-top-chip w-9 h-9 inline-flex items-center justify-center text-slate-300 hover:text-emerald-300">
                <span class="material-symbols-outlined">notifications</span>
                <span class="absolute -top-1 -right-1 min-w-4 h-4 bg-emerald-500 text-slate-900 text-[10px] rounded-full px-1 font-semibold leading-4 text-center">3</span>
            </a>
        </div>

        <div class="relative" id="profileDropdown">
            <button id="profileToggle" class="admin-top-chip flex items-center gap-2 px-2 py-1.5 focus:outline-none">
                <div class="w-8 h-8 rounded-lg bg-emerald-500 text-slate-900 flex items-center justify-center text-xs font-semibold">
                    AD
                </div>

                <div class="leading-tight hidden md:block text-left">
                    <p class="text-sm font-medium text-white">Admin User</p>
                    <p class="text-xs text-slate-300">Administrator</p>
                </div>

                <span class="material-symbols-outlined text-slate-300 text-sm">expand_more</span>
            </button>

            <div id="profileMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg hidden z-50 overflow-hidden">
                <a href="profile.php" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100 text-gray-700">
                    <span class="material-symbols-outlined text-sm">person</span>
                    My Profile
                </a>

                <a href="settings.php" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100 text-gray-700">
                    <span class="material-symbols-outlined text-sm">settings</span>
                    Settings
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
