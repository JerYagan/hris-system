<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside
  id="sidebar"
  class="w-64 bg-white border-r py-6
         fixed inset-y-0 left-0 z-40
         transform transition-transform duration-200 ease-in-out
         -translate-x-full flex flex-col"
>
  <div class="flex items-center space-x-4 px-4 shadow-md pb-4">
    <div>
      <img src="../../assets/images/icon.png" alt="DA-ATI HRIS" class="w-24">
    </div>
      <div>
        <h1 class="text-lg font-bold leading-tight">DA-ATI HRIS</h1>
        <p class="text-xs text-gray-500">Human Resource Information System</p>
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
        <span>Employee Records</span>
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
        <span>Time & Pay</span>
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
      </div>
    </div>

    <div>
      <button data-collapse-toggle="performance" class="w-full flex items-center justify-between px-2 mb-2 text-xs font-semibold text-gray-400 uppercase">
        <span>Performance</span>
        <span class="material-icons text-sm transition-transform">expand_more</span>
      </button>
      <div data-collapse-content="performance" class="space-y-1">
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

        <a href="applicant-registration.php" class="flex items-center gap-3 px-4 py-2 rounded-lg pl-10 <?= $currentPage === 'applicant-registration.php' ? 'bg-green-100 font-medium' : 'text-gray-600 hover:bg-gray-100' ?>">
          <span class="material-icons text-base">app_registration</span>
          <span class="sidebar-label">Applicant Registration</span>
        </a>

        <a href="applicant-tracking.php" class="flex items-center gap-3 px-4 py-2 rounded-lg pl-10 <?= $currentPage === 'applicant-tracking.php' ? 'bg-green-100 font-medium' : 'text-gray-600 hover:bg-gray-100' ?>">
          <span class="material-icons text-base">track_changes</span>
          <span class="sidebar-label">Applicant Tracking</span>
        </a>

        <a href="evaluation.php" class="flex items-center gap-3 px-4 py-2 rounded-lg pl-10 <?= $currentPage === 'evaluation.php' ? 'bg-green-100 font-medium' : 'text-gray-600 hover:bg-gray-100' ?>">
          <span class="material-icons text-base">fact_check</span>
          <span class="sidebar-label">Evaluation</span>
        </a>
      </div>
    </div>

    <div>
      <button data-collapse-toggle="misc" class="w-full flex items-center justify-between px-2 mb-2 text-xs font-semibold text-gray-400 uppercase">
        <span>Miscellaneous</span>
        <span class="material-icons text-sm transition-transform">expand_more</span>
      </button>
      <div data-collapse-content="misc" class="space-y-1">
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
</aside>
