<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside
  id="sidebar"
  data-sidebar-mode="overlay"
  class="w-72 bg-white border-r py-6
         fixed inset-y-0 left-0 z-50
         transform transition-transform duration-200 ease-in-out
         -translate-x-full flex flex-col"
>
  <div class="px-4 shadow-md pb-4">
    <div class="flex items-start justify-between gap-3">
      <div class="flex min-w-0 items-center gap-3">
        <?php if (!empty($staffTopnavPhotoUrl)): ?>
          <img src="<?= htmlspecialchars((string)$staffTopnavPhotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Profile" class="h-11 w-11 rounded-2xl border border-gray-200 object-cover shadow-sm">
        <?php else: ?>
          <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-daGreen text-sm font-semibold text-white shadow-sm">
            <?= htmlspecialchars((string)($staffTopnavInitials ?? 'ST'), ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>
        <div class="min-w-0">
          <p class="truncate text-sm font-semibold text-gray-900"><?= htmlspecialchars((string)($staffTopnavDisplayName ?? 'Staff User'), ENT_QUOTES, 'UTF-8') ?></p>
          <p class="truncate text-xs text-gray-500"><?= htmlspecialchars((string)($staffTopnavRoleLabel ?? 'Staff'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
      </div>
      <button id="sidebarClose" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900" aria-label="Close sidebar">
        <span class="material-icons text-[20px]">menu</span>
      </button>
    </div>
  </div>

  <nav class="flex-1 text-sm overflow-y-auto py-4 px-4 space-y-6">

    <div>
      <p class="px-2 mb-2 text-xs font-semibold text-gray-400 uppercase">Overview</p>
      <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'dashboard.php' ? 'bg-green-100 font-medium' : 'hover:bg-gray-100' ?>">
        <span class="material-icons text-base">dashboard</span>
        <span class="sidebar-label">Dashboard</span>
      </a>
    </div>

    <div>
      <button data-collapse-toggle="employee-records" class="w-full flex items-center justify-between px-2 mb-2 text-xs font-semibold text-gray-400 uppercase">
        <span>People and Records</span>
        <span class="material-icons text-sm transition-transform">expand_more</span>
      </button>
      <div data-collapse-content="employee-records" class="space-y-1">
        <a href="personal-information.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'personal-information.php' ? 'bg-green-100 font-medium' : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">person</span>
          <span class="sidebar-label">Personal Information</span>
        </a>

        <a href="document-management.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'document-management.php' ? 'bg-green-100 font-medium' : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">description</span>
          <span class="sidebar-label">Document Management</span>
        </a>

      </div>
    </div>

    <div>
      <button data-collapse-toggle="time-pay" class="w-full flex items-center justify-between px-2 mb-2 text-xs font-semibold text-gray-400 uppercase">
        <span>Operations</span>
        <span class="material-icons text-sm transition-transform">expand_more</span>
      </button>
      <div data-collapse-content="time-pay" class="space-y-1">
        <a href="timekeeping.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'timekeeping.php' ? 'bg-green-100 font-medium' : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">schedule</span>
          <span class="sidebar-label">Timekeeping</span>
        </a>

        <a href="payroll-management.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'payroll-management.php' ? 'bg-green-100 font-medium' : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">payments</span>
          <span class="sidebar-label">Payroll Management</span>
        </a>

        <a href="learning-development.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'learning-development.php' ? 'bg-green-100 font-medium' : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">school</span>
          <span class="sidebar-label">Learning and Development</span>
        </a>
      </div>
    </div>

    <div>
      <button data-collapse-toggle="recruitment" class="w-full flex items-center justify-between px-2 mb-2 text-xs font-semibold text-gray-400 uppercase">
        <span>Recruitment</span>
        <span class="material-icons text-sm transition-transform">expand_more</span>
      </button>
      <div data-collapse-content="recruitment" class="space-y-1">
        <a href="recruitment.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'recruitment.php' && !in_array($currentPage, ['applicant-registration.php', 'applicant-tracking.php', 'evaluation.php'], true) ? 'bg-green-100 font-medium' : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">person_search</span>
          <span class="sidebar-label">Recruitment</span>
        </a>

        <a href="applicant-registration.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $currentPage === 'applicant-registration.php' ? 'bg-green-100 font-medium' : 'text-gray-600 hover:bg-gray-100' ?>">
          <span class="material-icons text-base">app_registration</span>
          <span class="sidebar-label">Applicant Registration</span>
        </a>

        <a href="applicant-tracking.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $currentPage === 'applicant-tracking.php' ? 'bg-green-100 font-medium' : 'text-gray-600 hover:bg-gray-100' ?>">
          <span class="material-icons text-base">track_changes</span>
          <span class="sidebar-label">Applicant Tracking</span>
        </a>

        <a href="evaluation.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $currentPage === 'evaluation.php' ? 'bg-green-100 font-medium' : 'text-gray-600 hover:bg-gray-100' ?>">
          <span class="material-icons text-base">fact_check</span>
          <span class="sidebar-label">Evaluation</span>
        </a>
      </div>
    </div>

    <div>
      <button data-collapse-toggle="workspace" class="w-full flex items-center justify-between px-2 mb-2 text-xs font-semibold text-gray-400 uppercase">
        <span>Workspace</span>
        <span class="material-icons text-sm transition-transform">expand_more</span>
      </button>
      <div data-collapse-content="workspace" class="space-y-1">
        <a href="reports.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'reports.php' ? 'bg-green-100 font-medium' : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">assessment</span>
          <span class="sidebar-label">Reports and Analytics</span>
        </a>

        <a href="support.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'support.php' ? 'bg-green-100 font-medium' : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">support_agent</span>
          <span class="sidebar-label">Support</span>
        </a>

        <a href="profile.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'profile.php' ? 'bg-green-100 font-medium' : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">person</span>
          <span class="sidebar-label">My Profile</span>
        </a>
      </div>
    </div>
  </nav>

  <div class="mt-auto border-t border-gray-200 px-4 pt-4">
    <a href="<?= htmlspecialchars(systemAppPath('/pages/auth/logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-rose-600 transition hover:bg-rose-50">
      <span class="material-icons text-base">logout</span>
      <span class="sidebar-label">Logout</span>
    </a>
  </div>
</aside>
