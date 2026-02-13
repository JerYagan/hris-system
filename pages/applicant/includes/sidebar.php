<?php
$activePage = basename($_SERVER['PHP_SELF']);
?>

<aside
  id="sidebar"
  class="w-64 bg-white border-r py-6
         fixed inset-y-0 left-0 z-40
         transform transition-transform duration-200 ease-in-out
         -translate-x-full flex flex-col"
>

  <!-- BRAND -->
  <div class="px-4 pb-4 border-b">
    <div class="flex items-center gap-3">
      <img src="../../assets/images/icon.png" alt="DA-ATI HRIS" class="w-10 h-10 object-contain">
      <div>
        <h1 class="text-sm font-semibold text-gray-800 leading-tight">DA-ATI HRIS</h1>
        <p class="text-xs text-gray-500">Applicant Portal</p>
      </div>
    </div>
  </div>

  <!-- APPLICANT NAVIGATION -->
  <nav class="flex-1 text-sm overflow-y-auto py-4 px-4 space-y-2">
    <a href="dashboard.php"
       title="Dashboard"
       class="flex items-center gap-3 px-4 py-2.5 rounded-lg <?= $activePage === 'dashboard.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-100' ?>">
      <span class="material-symbols-outlined text-[18px]">dashboard</span>
      <span>Dashboard</span>
    </a>

    <a href="job-list.php"
       title="Job Listings"
       class="flex items-center gap-3 px-4 py-2.5 rounded-lg <?= $activePage === 'job-list.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-100' ?>">
      <span class="material-symbols-outlined text-[18px]">list_alt</span>
      <span>Job Listings</span>
    </a>

    <a href="applications.php"
       title="My Applications"
       class="flex items-center gap-3 px-4 py-2.5 rounded-lg <?= $activePage === 'applications.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-100' ?>">
      <span class="material-symbols-outlined text-[18px]">folder_shared</span>
      <span>My Applications</span>
    </a>

    <a href="notifications.php"
       title="Notifications"
       class="flex items-center gap-3 px-4 py-2.5 rounded-lg <?= $activePage === 'notifications.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-100' ?>">
      <span class="material-symbols-outlined text-[18px]">notifications</span>
      <span>Notifications</span>
    </a>

    <a href="profile.php"
       title="Account Profile"
       class="flex items-center gap-3 px-4 py-2.5 rounded-lg <?= $activePage === 'profile.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-100' ?>">
      <span class="material-symbols-outlined text-[18px]">person</span>
      <span>Account Profile</span>
    </a>

    <a href="support.php"
       title="Help & Support"
       class="flex items-center gap-3 px-4 py-2.5 rounded-lg <?= $activePage === 'support.php' ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-100' ?>">
      <span class="material-symbols-outlined text-[18px]">help</span>
      <span>Help & Support</span>
    </a>
  </nav>

</aside>
