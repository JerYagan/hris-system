<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$recruitmentPages = ['recruitment.php', 'applicants.php', 'applicant-tracking.php', 'evaluation.php'];
$isRecruitmentSection = in_array($activePage, $recruitmentPages, true);
$documentPages = ['document-management.php', 'personal-information.php'];
$isDocumentSection = in_array($activePage, $documentPages, true);
$praisePages = ['praise.php', 'praise-employee-evaluation.php', 'praise-awards-recognition.php', 'praise-reports-analytics.php'];
$isPraiseSection = in_array($activePage, $praisePages, true);
?>

<aside
    id="sidebar"
    class="w-72 admin-sidebar py-4
                 fixed inset-y-0 left-0 z-40
                 transform transition-all duration-200 ease-in-out
                 -translate-x-full flex flex-col bg-slate-900"
>
    <div class="px-4 pb-4">
        <div class="admin-brand-wrap flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-slate-900/90 flex items-center justify-center shrink-0">
                    <img src="../../assets/images/icon.png" alt="DA-ATI HRIS" class="w-6 h-6 object-contain">
                </div>
                <div class="admin-brand-text">
                    <h1 class="text-sm font-semibold text-slate-100 leading-tight tracking-wide">Admin Panel</h1>
                    <p class="text-xs text-slate-400">DA-ATI HRIS</p>
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
                <a href="notifications.php" class="admin-nav-link <?= $activePage === 'notifications.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">notifications</span>
                    <span class="admin-link-label flex-1">Notifications</span>
                    <span class="admin-link-badge">3</span>
                </a>
            </div>
        </div>

        <div>
            <button type="button" class="admin-category-toggle" data-cat-toggle="core-modules" aria-expanded="true">
                <span class="admin-section-label text-[10px] font-semibold uppercase tracking-wider">Core Modules</span>
                <span class="material-symbols-outlined admin-category-chevron text-[16px]">expand_more</span>
            </button>
            <div class="mt-2 space-y-1" data-cat-content="core-modules">
                <div class="space-y-1">
                    <a href="recruitment.php" class="admin-nav-link <?= $isRecruitmentSection ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                        <span class="admin-nav-icon material-symbols-outlined text-[18px]">person_search</span>
                        <span class="admin-link-label">Recruitment</span>
                    </a>
                    <a href="applicants.php" class="admin-nav-link ml-7 <?= $activePage === 'applicants.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                        <span class="admin-nav-icon material-symbols-outlined text-[18px]">badge</span>
                        <span class="admin-link-label text-[13px]">Applicants</span>
                    </a>
                    <a href="applicant-tracking.php" class="admin-nav-link ml-7 <?= $activePage === 'applicant-tracking.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                        <span class="admin-nav-icon material-symbols-outlined text-[18px]">track_changes</span>
                        <span class="admin-link-label text-[13px]">Applicant Tracking</span>
                    </a>
                    <a href="evaluation.php" class="admin-nav-link ml-7 <?= $activePage === 'evaluation.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                        <span class="admin-nav-icon material-symbols-outlined text-[18px]">rule</span>
                        <span class="admin-link-label text-[13px]">Evaluation</span>
                    </a>
                </div>
                <div class="space-y-1">
                    <a href="document-management.php" class="admin-nav-link <?= $isDocumentSection ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                        <span class="admin-nav-icon material-symbols-outlined text-[18px]">folder_open</span>
                        <span class="admin-link-label">Document Management</span>
                    </a>
                    <a href="personal-information.php" class="admin-nav-link ml-7 <?= $activePage === 'personal-information.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                        <span class="admin-nav-icon material-symbols-outlined text-[18px]">badge</span>
                        <span class="admin-link-label text-[13px]">Personal Information</span>
                    </a>
                </div>
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
                    <span class="admin-link-label">Report and Analytics</span>
                </a>
            </div>
        </div>

        <div>
            <button type="button" class="admin-category-toggle" data-cat-toggle="administration" aria-expanded="true">
                <span class="admin-section-label text-[10px] font-semibold uppercase tracking-wider">Administration</span>
                <span class="material-symbols-outlined admin-category-chevron text-[16px]">expand_more</span>
            </button>
            <div class="mt-2 space-y-1" data-cat-content="administration">
                <div class="space-y-1">
                    <a href="praise.php" class="admin-nav-link <?= $isPraiseSection ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                        <span class="admin-nav-icon material-symbols-outlined text-[18px]">emoji_events</span>
                        <span class="admin-link-label">PRAISE</span>
                    </a>
                    <a href="praise-employee-evaluation.php" class="admin-nav-link ml-7 <?= $activePage === 'praise-employee-evaluation.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                        <span class="admin-nav-icon material-symbols-outlined text-[18px]">grading</span>
                        <span class="admin-link-label text-[13px]">Employee Evaluation</span>
                    </a>
                    <a href="praise-awards-recognition.php" class="admin-nav-link ml-7 <?= $activePage === 'praise-awards-recognition.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                        <span class="admin-nav-icon material-symbols-outlined text-[18px]">workspace_premium</span>
                        <span class="admin-link-label text-[13px]">Awards and Recognition</span>
                    </a>
                    <a href="praise-reports-analytics.php" class="admin-nav-link ml-7 <?= $activePage === 'praise-reports-analytics.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                        <span class="admin-nav-icon material-symbols-outlined text-[18px]">monitoring</span>
                        <span class="admin-link-label text-[13px]">PRAISE Reports</span>
                    </a>
                </div>
                <a href="learning-and-development.php" class="admin-nav-link <?= $activePage === 'learning-and-development.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">school</span>
                    <span class="admin-link-label">Learning and Development</span>
                </a>
                <a href="user-management.php" class="admin-nav-link <?= $activePage === 'user-management.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">group</span>
                    <span class="admin-link-label">User Management</span>
                </a>
            </div>
        </div>

        <div>
            <button type="button" class="admin-category-toggle" data-cat-toggle="account" aria-expanded="true">
                <span class="admin-section-label text-[10px] font-semibold uppercase tracking-wider">Account</span>
                <span class="material-symbols-outlined admin-category-chevron text-[16px]">expand_more</span>
            </button>
            <div class="mt-2 space-y-1" data-cat-content="account">
                <a href="profile.php" class="admin-nav-link <?= $activePage === 'profile.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">person</span>
                    <span class="admin-link-label">My Profile</span>
                </a>

                <a href="settings.php" class="admin-nav-link <?= $activePage === 'settings.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">settings</span>
                    <span class="admin-link-label">Settings</span>
                </a>

                <a href="create-announcement.php" class="admin-nav-link <?= $activePage === 'create-announcement.php' ? 'active text-emerald-100 font-medium' : 'text-slate-300' ?>">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">campaign</span>
                    <span class="admin-link-label">Create Announcement</span>
                </a>

                <a href="/hris-system/pages/auth/logout.php" class="admin-nav-link text-rose-300 hover:text-rose-200">
                    <span class="admin-nav-icon material-symbols-outlined text-[18px]">logout</span>
                    <span class="admin-link-label">Logout</span>
                </a>
            </div>
        </div>
    </nav>
</aside>