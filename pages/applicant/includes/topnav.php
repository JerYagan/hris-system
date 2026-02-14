<?php
// Applicant Top Navigation
$breadcrumbs = $breadcrumbs ?? ['Dashboard'];
$activePage = $activePage ?? basename($_SERVER['PHP_SELF']);

$primaryLinks = [
    ['href' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'dashboard'],
    ['href' => 'job-list.php', 'label' => 'Jobs', 'icon' => 'list_alt'],
    ['href' => 'applications.php', 'label' => 'Applications', 'icon' => 'folder_shared'],
];

$recruitmentLinks = [
    ['href' => 'apply.php', 'label' => 'Submit Application', 'icon' => 'edit_document'],
    ['href' => 'application-feedback.php', 'label' => 'Application Feedback', 'icon' => 'fact_check'],
    ['href' => 'notifications.php', 'label' => 'Notifications', 'icon' => 'notifications'],
];

$accountLinks = [
    ['href' => 'profile.php', 'label' => 'Profile', 'icon' => 'person'],
    ['href' => 'support.php', 'label' => 'Support', 'icon' => 'help'],
];

$allMobileLinks = array_merge($primaryLinks, $recruitmentLinks, $accountLinks);
?>

<header id="topnav" class="sticky top-0 z-40 border-b bg-white/95 backdrop-blur transition-transform duration-200">

    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-[68px] items-center justify-between gap-4">

            <!-- LEFT: BRAND + NAV -->
            <div class="flex min-w-0 items-center gap-3 lg:gap-5">
                <a href="dashboard.php" class="flex items-center gap-2.5 shrink-0">
                    <img src="/hris-system/assets/images/icon.png" alt="DA-ATI HRIS" class="h-9 w-9 rounded-lg border object-contain p-1">
                    <div class="hidden sm:block leading-tight">
                        <p class="text-xs text-gray-500">Applicant Portal</p>
                        <p class="text-sm font-semibold text-gray-800">DA-ATI HRIS</p>
                    </div>
                </a>

                <nav class="hidden lg:flex items-center gap-1" aria-label="Primary navigation">
                    <?php foreach ($primaryLinks as $link): ?>
                        <?php $isActive = $activePage === $link['href']; ?>
                        <a href="<?= $link['href'] ?>"
                           class="inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-sm transition <?= $isActive ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-800' ?>">
                            <span class="material-symbols-outlined text-[18px]"><?= $link['icon'] ?></span>
                            <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>

                    <div class="relative">
                        <button id="applicantRecruitmentMenuBtn"
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-sm text-gray-600 transition hover:bg-gray-100 hover:text-gray-800"
                                aria-label="Open recruitment menu">
                            <span class="material-symbols-outlined text-[18px]">work_history</span>
                            Recruitment
                            <span class="material-symbols-outlined text-[18px]">expand_more</span>
                        </button>

                        <div id="applicantRecruitmentMenu" class="hidden absolute left-0 mt-2 w-64 rounded-xl border bg-white p-2 shadow-sm z-50">
                            <?php foreach ($recruitmentLinks as $link): ?>
                                <?php $isActive = $activePage === $link['href']; ?>
                                <a href="<?= $link['href'] ?>"
                                   class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm <?= $isActive ? 'bg-green-50 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-50' ?>">
                                    <span class="material-symbols-outlined text-sm"><?= $link['icon'] ?></span>
                                    <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </nav>
            </div>

            <!-- RIGHT: ACTIONS -->
            <div class="flex items-center gap-3">

                <button id="applicantMobileMenuBtn"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md border text-gray-700 hover:bg-gray-50 lg:hidden"
                        aria-label="Open navigation menu">
                    <span class="material-symbols-outlined text-[20px]">menu</span>
                </button>

                <a href="notifications.php"
                   class="relative rounded-md p-1 text-gray-600 transition hover:bg-gray-100 hover:text-green-700"
                   aria-label="Notifications">
                    <span class="material-symbols-outlined">notifications</span>
                    <span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-red-600"></span>
                </a>

                <span class="hidden sm:block h-6 w-px bg-gray-200"></span>

                <div class="relative">
                    <button id="applicantUserMenuBtn"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-gray-700 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none">
                        <span class="material-symbols-outlined">account_circle</span>
                        <span class="hidden sm:block">Applicant</span>
                        <span class="material-symbols-outlined text-base">expand_more</span>
                    </button>

                    <div id="applicantUserMenu"
                         class="hidden absolute right-0 mt-2 w-56 rounded-xl border bg-white p-2 shadow-sm text-sm z-50">

                        <?php foreach ($accountLinks as $link): ?>
                            <?php $isActive = $activePage === $link['href']; ?>
                            <a href="<?= $link['href'] ?>"
                               class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $isActive ? 'bg-green-50 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-50' ?>">
                                <span class="material-symbols-outlined text-sm"><?= $link['icon'] ?></span>
                                <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endforeach; ?>

                        <div class="my-1 border-t"></div>

                        <a href="/hris-system/pages/auth/login.php"
                           class="flex items-center gap-2 rounded-lg px-3 py-2 text-red-600 hover:bg-red-50 font-medium">
                            <span class="material-symbols-outlined text-sm">logout</span>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="applicantMobileMenu" class="hidden border-t bg-white lg:hidden">
        <div class="mx-auto w-full max-w-7xl space-y-4 px-4 py-4 sm:px-6">
            <section>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Main</h3>
                <div class="space-y-1">
                    <?php foreach ($primaryLinks as $link): ?>
                        <?php $isActive = $activePage === $link['href']; ?>
                        <a href="<?= $link['href'] ?>"
                           class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm <?= $isActive ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <span class="material-symbols-outlined text-[18px]"><?= $link['icon'] ?></span>
                            <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Recruitment</h3>
                <div class="space-y-1">
                    <?php foreach ($recruitmentLinks as $link): ?>
                        <?php $isActive = $activePage === $link['href']; ?>
                        <a href="<?= $link['href'] ?>"
                           class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm <?= $isActive ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <span class="material-symbols-outlined text-[18px]"><?= $link['icon'] ?></span>
                            <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Account</h3>
                <div class="space-y-1">
                    <?php foreach ($accountLinks as $link): ?>
                        <?php $isActive = $activePage === $link['href']; ?>
                        <a href="<?= $link['href'] ?>"
                           class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm <?= $isActive ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <span class="material-symbols-outlined text-[18px]"><?= $link['icon'] ?></span>
                            <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>
</header>

<script>
    (function () {
        const mobileMenuBtn = document.getElementById('applicantMobileMenuBtn');
        const mobileMenu = document.getElementById('applicantMobileMenu');
        const menuBtn = document.getElementById('applicantUserMenuBtn');
        const menu = document.getElementById('applicantUserMenu');
        const recruitmentBtn = document.getElementById('applicantRecruitmentMenuBtn');
        const recruitmentMenu = document.getElementById('applicantRecruitmentMenu');

        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                mobileMenu.classList.toggle('hidden');
            });
        }

        if (menuBtn && menu) {
            menuBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                menu.classList.toggle('hidden');
                if (recruitmentMenu) {
                    recruitmentMenu.classList.add('hidden');
                }
            });

            menu.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        }

        if (recruitmentBtn && recruitmentMenu) {
            recruitmentBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                recruitmentMenu.classList.toggle('hidden');
                if (menu) {
                    menu.classList.add('hidden');
                }
            });

            recruitmentMenu.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        }

        document.addEventListener('click', function () {
            if (menu) {
                menu.classList.add('hidden');
            }
            if (recruitmentMenu) {
                recruitmentMenu.classList.add('hidden');
            }
            if (mobileMenu) {
                mobileMenu.classList.add('hidden');
            }
        });
    })();
</script>
