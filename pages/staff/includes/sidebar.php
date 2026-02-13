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
  <div class="px-4 pb-4 border-b">
    <div class="flex items-center gap-3">
      <img src="../../assets/images/icon.png" alt="DA-ATI HRIS" class="w-10 h-10 object-contain">
      <div>
        <h1 class="text-sm font-semibold text-gray-800 leading-tight">DA-ATI HRIS</h1>
        <p class="text-xs text-gray-500">Staff Portal</p>
      </div>
    </div>
  </div>

  <nav class="flex-1 text-sm overflow-y-auto py-4 px-4 space-y-6">

    <div>
      <p class="px-2 mb-2 text-xs font-semibold text-gray-400 uppercase">Overview</p>
      <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'dashboard.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-100' ?>">
        <span class="material-symbols-outlined text-[18px]">dashboard</span>
        <span>Dashboard</span>
      </a>
    </div>

    <div>
      <p class="px-2 mb-2 text-xs font-semibold text-gray-400 uppercase">Employee Records</p>
      <div class="space-y-1">
        <a href="personal-information.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'personal-information.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-100' ?>">
          <span class="material-symbols-outlined text-[18px]">person</span>
          <span>Personal Information</span>
        </a>

        <a href="document-management.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'document-management.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-100' ?>">
          <span class="material-symbols-outlined text-[18px]">description</span>
          <span>Document Management</span>
        </a>
      </div>
    </div>

    <div>
      <p class="px-2 mb-2 text-xs font-semibold text-gray-400 uppercase">Time & Pay</p>
      <div class="space-y-1">
        <a href="timekeeping.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'timekeeping.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-100' ?>">
          <span class="material-symbols-outlined text-[18px]">schedule</span>
          <span>Timekeeping</span>
        </a>

        <a href="payroll-management.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'payroll-management.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-100' ?>">
          <span class="material-symbols-outlined text-[18px]">payments</span>
          <span>Payroll Management</span>
        </a>
      </div>
    </div>

    <div>
      <p class="px-2 mb-2 text-xs font-semibold text-gray-400 uppercase">Performance</p>
      <div class="space-y-1">
        <a href="praise.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'praise.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-100' ?>">
          <span class="material-symbols-outlined text-[18px]">emoji_events</span>
          <span>PRAISE</span>
        </a>
      </div>
    </div>

    <div>
      <p class="px-2 mb-2 text-xs font-semibold text-gray-400 uppercase">Recruitment</p>
      <div class="space-y-1">
        <a href="recruitment.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'recruitment.php' && !in_array($currentPage, ['applicant-registration.php', 'applicant-tracking.php', 'evaluation.php'], true) ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-100' ?>">
          <span class="material-symbols-outlined text-[18px]">person_search</span>
          <span>Recruitment</span>
        </a>

        <a href="applicant-registration.php" class="flex items-center gap-3 px-4 py-2 rounded-lg pl-10 <?= $currentPage === 'applicant-registration.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-600 hover:bg-gray-100' ?>">
          <span class="material-symbols-outlined text-[16px]">app_registration</span>
          <span>Applicant Registration</span>
        </a>

        <a href="applicant-tracking.php" class="flex items-center gap-3 px-4 py-2 rounded-lg pl-10 <?= $currentPage === 'applicant-tracking.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-600 hover:bg-gray-100' ?>">
          <span class="material-symbols-outlined text-[16px]">track_changes</span>
          <span>Applicant Tracking</span>
        </a>

        <a href="evaluation.php" class="flex items-center gap-3 px-4 py-2 rounded-lg pl-10 <?= $currentPage === 'evaluation.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-600 hover:bg-gray-100' ?>">
          <span class="material-symbols-outlined text-[16px]">fact_check</span>
          <span>Evaluation</span>
        </a>
      </div>
    </div>

    <div>
      <p class="px-2 mb-2 text-xs font-semibold text-gray-400 uppercase">Reports</p>
      <div class="space-y-1">
        <a href="reports.php" class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'reports.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-100' ?>">
          <span class="material-symbols-outlined text-[18px]">analytics</span>
          <span>Reports</span>
        </a>

        <a href="notifications.php" class="flex items-center justify-between px-4 py-2 rounded-lg <?= $activePage === 'notifications.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-100' ?>">
          <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-[18px]">notifications</span>
            <span>Notifications</span>
          </div>
          <span class="text-xs bg-red-600 text-white px-2 py-0.5 rounded-full">3</span>
        </a>
      </div>
    </div>
  </nav>
</aside>
