<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$recruitmentPages = ['recruitment.php', 'applicants.php', 'applicant-tracking.php', 'evaluation.php'];
$isRecruitmentSection = in_array($activePage, $recruitmentPages, true);
$documentPages = ['document-management.php', 'document-category-management.php'];
$isDocumentSection = in_array($activePage, $documentPages, true);
?>

<aside
    id="sidebar"
    class="w-72 admin-sidebar py-4
                 fixed inset-y-0 left-0 z-40
                 transform transition-all duration-200 ease-in-out
                 -translate-x-full flex flex-col bg-slate-900"
>
    <div class="px-4 pb-4">
        <div class="admin-brand-wrap flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <div class="admin-brand-text">
                    <h1 class="text-sm font-semibold text-slate-100 leading-tight tracking-wide">Admin Panel</h1>
                    <p class="mt-1 text-xs text-slate-400">ATI HRIS Portal</p>
                </div>
            </div>
            <button id="sidebarClose" type="button" class="admin-top-chip w-8 h-8 inline-flex items-center justify-center text-slate-300 hover:text-white focus:outline-none" aria-label="Close sidebar">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </div>
    </div>

    <nav class="flex-1 text-sm overflow-y-auto py-4 px-3 space-y-6">
        <div>
            <button type="button" class="admin-category-toggle" data-cat-toggle="overview" aria-expanded="true">
                <span class="admin-section-label text-[10px] font-semibold uppercase tracking-wider">Overview</span>
                <span class="material-symbols-outlined admin-category-chevron text-[16px]">expand_more</span>
            </button>
            <div class="mt-2 space-y-1" data-cat-content="overview">
                <a href="dashboard.php" class="admin-nav-link <?= $activePage === 'dashboard.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">dashboard</span>
                    <span class="admin-link-label">Admin Dashboard</span>
                </a>
            </div>
        </div>

        <div>
            <button type="button" class="admin-category-toggle" data-cat-toggle="people-records" aria-expanded="true">
                <span class="admin-section-label text-[10px] font-semibold uppercase tracking-wider">People and Records</span>
                <span class="material-symbols-outlined admin-category-chevron text-[16px]">expand_more</span>
            </button>
            <div class="mt-2 space-y-1" data-cat-content="people-records">
                <a href="personal-information.php" class="admin-nav-link <?= $activePage === 'personal-information.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">badge</span>
                    <span class="admin-link-label">Personal Information</span>
                </a>
                <a href="document-management.php" class="admin-nav-link <?= $activePage === 'document-management.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">folder_open</span>
                    <span class="admin-link-label">Document Management</span>
                </a>
                <a href="document-category-management.php" class="admin-nav-link <?= $activePage === 'document-category-management.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">folder_managed</span>
                    <span class="admin-link-label">Category Management</span>
                </a>
                <a href="user-management.php" class="admin-nav-link <?= $activePage === 'user-management.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">group</span>
                    <span class="admin-link-label">User Management</span>
                </a>
            </div>
        </div>

        <div>
            <button type="button" class="admin-category-toggle" data-cat-toggle="recruitment" aria-expanded="true">
                <span class="admin-section-label text-[10px] font-semibold uppercase tracking-wider">Recruitment</span>
                <span class="material-symbols-outlined admin-category-chevron text-[16px]">expand_more</span>
            </button>
            <div class="mt-2 space-y-1" data-cat-content="recruitment">
                <a href="recruitment.php" class="admin-nav-link <?= $activePage === 'recruitment.php' || ($isRecruitmentSection && $activePage === 'recruitment.php') ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">person_search</span>
                    <span class="admin-link-label">Job Postings</span>
                </a>
                <a href="applicants.php" class="admin-nav-link <?= $activePage === 'applicants.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">badge</span>
                    <span class="admin-link-label">Applicant Registration</span>
                </a>
                <a href="applicant-tracking.php" class="admin-nav-link <?= $activePage === 'applicant-tracking.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">track_changes</span>
                    <span class="admin-link-label">Applicant Tracking</span>
                </a>
                <a href="evaluation.php" class="admin-nav-link <?= $activePage === 'evaluation.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">rule</span>
                    <span class="admin-link-label">Evaluation</span>
                </a>
            </div>
        </div>

        <div>
            <button type="button" class="admin-category-toggle" data-cat-toggle="operations" aria-expanded="true">
                <span class="admin-section-label text-[10px] font-semibold uppercase tracking-wider">Operations</span>
                <span class="material-symbols-outlined admin-category-chevron text-[16px]">expand_more</span>
            </button>
            <div class="mt-2 space-y-1" data-cat-content="operations">
                <a href="timekeeping.php" class="admin-nav-link <?= $activePage === 'timekeeping.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">schedule</span>
                    <span class="admin-link-label">Timekeeping</span>
                </a>
                <a href="payroll-management.php" class="admin-nav-link <?= $activePage === 'payroll-management.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">payments</span>
                    <span class="admin-link-label">Payroll Management</span>
                </a>
                <a href="report-analytics.php" class="admin-nav-link <?= $activePage === 'report-analytics.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">analytics</span>
                    <span class="admin-link-label">Reports and Analytics</span>
                </a>
                <a href="learning-and-development.php" class="admin-nav-link <?= $activePage === 'learning-and-development.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">school</span>
                    <span class="admin-link-label">Learning and Development</span>
                </a>
            </div>
        </div>

        <div>
            <button type="button" class="admin-category-toggle" data-cat-toggle="system" aria-expanded="true">
                <span class="admin-section-label text-[10px] font-semibold uppercase tracking-wider">System and Account</span>
                <span class="material-symbols-outlined admin-category-chevron text-[16px]">expand_more</span>
            </button>
            <div class="mt-2 space-y-1" data-cat-content="system">
                <a href="settings.php" class="admin-nav-link <?= $activePage === 'settings.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">settings</span>
                    <span class="admin-link-label">Settings</span>
                </a>

                <a href="support.php" class="admin-nav-link <?= $activePage === 'support.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">support_agent</span>
                    <span class="admin-link-label">Support</span>
                </a>

                <a href="profile.php" class="admin-nav-link <?= $activePage === 'profile.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">person</span>
                    <span class="admin-link-label">My Profile</span>
                </a>

                <a href="<?= htmlspecialchars(systemAppPath('/pages/auth/logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="admin-nav-link text-rose-300 hover:text-rose-200">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">logout</span>
                    <span class="admin-link-label">Logout</span>
                </a>
            </div>
        </div>
    </nav>
</aside>