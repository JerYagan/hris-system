<aside
  id="sidebar"
  class="w-64 bg-white border-r py-6
         fixed inset-y-0 left-0 z-40
         transform transition-transform duration-200 ease-in-out
         -translate-x-full flex flex-col"
>

  <!-- BRAND -->
  <div class="flex items-center space-x-4 px-4 shadow-md pb-4">
    <div>
      <img src="../../assets/images/icon.png" alt="" class="w-24">
    </div>
      <div>
        <h1 class="text-lg font-bold leading-tight">
          DA-ATI HRIS
        </h1>
        <p class="text-xs text-gray-500">
          Human Resource Information System
        </p>
      </div>
  </div>


  <!-- NAVIGATION (SCROLLABLE) -->
  <nav class="flex-1 space-y-6 text-sm overflow-y-auto py-4 px-4">

    <!-- OVERVIEW -->
    <div>
      <p class="px-2 mb-2 text-xs font-semibold text-gray-400 uppercase">
        Overview
      </p>

      <a href="dashboard.php"
         title="Dashboard"
         class="flex items-center gap-3 px-4 py-2 rounded-lg
         <?= $activePage === 'dashboard.php'
              ? 'bg-green-100 font-medium'
              : 'hover:bg-gray-100' ?>">
        <span class="material-icons text-base">dashboard</span>
        <span class="sidebar-label">Dashboard</span>
      </a>
    </div>

    <!-- EMPLOYEE RECORDS -->
    <div>
      <button
        data-collapse-toggle="employee-records"
        class="w-full flex items-center justify-between px-2 mb-2
               text-xs font-semibold text-gray-400 uppercase">

        <span>Employee Records</span>
        <span class="material-icons text-sm transition-transform">
          expand_more
        </span>
      </button>

      <div data-collapse-content="employee-records" class="space-y-1">
        <a href="personal-information.php"
           title="Personal Information"
           class="flex items-center gap-3 px-4 py-2 rounded-lg
           <?= $activePage === 'personal-information.php'
                ? 'bg-green-100 font-medium'
                : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">person</span>
          <span class="sidebar-label">Personal Information</span>
        </a>

        <a href="document-management.php"
           title="Document Management"
           class="flex items-center gap-3 px-4 py-2 rounded-lg
           <?= $activePage === 'document-management.php'
                ? 'bg-green-100 font-medium'
                : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">description</span>
          <span class="sidebar-label">Document Management</span>
        </a>
      </div>
    </div>

    <!-- TIME & PAY -->
    <div>
      <button
        data-collapse-toggle="time-pay"
        class="w-full flex items-center justify-between px-2 mb-2
               text-xs font-semibold text-gray-400 uppercase">

        <span>Time & Pay</span>
        <span class="material-icons text-sm transition-transform">
          expand_more
        </span>
      </button>

      <div data-collapse-content="time-pay" class="space-y-1">
        <a href="timekeeping.php"
           title="Timekeeping"
           class="flex items-center gap-3 px-4 py-2 rounded-lg
           <?= $activePage === 'timekeeping.php'
                ? 'bg-green-100 font-medium'
                : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">schedule</span>
          <span class="sidebar-label">Timekeeping</span>
        </a>

        <a href="payroll.php"
           title="Payroll"
           class="flex items-center gap-3 px-4 py-2 rounded-lg
           <?= $activePage === 'payroll.php'
                ? 'bg-green-100 font-medium'
                : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">payments</span>
          <span class="sidebar-label">Payroll</span>
        </a>
      </div>
    </div>

    <!-- PERFORMANCE -->
    <div>
      <button
        data-collapse-toggle="performance"
        class="w-full flex items-center justify-between px-2 mb-2
               text-xs font-semibold text-gray-400 uppercase">

        <span>Performance</span>
        <span class="material-icons text-sm transition-transform">
          expand_more
        </span>
      </button>

      <div data-collapse-content="performance" class="space-y-1">
        <a href="praise.php"
           title="PRAISE"
           class="flex items-center gap-3 px-4 py-2 rounded-lg
           <?= $activePage === 'praise.php'
                ? 'bg-green-100 font-medium'
                : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">emoji_events</span>
          <span class="sidebar-label">PRAISE</span>
        </a>

        <a href="learning-and-development.php"
           title="Learning and Development"
           class="flex items-center gap-3 px-4 py-2 rounded-lg
           <?= $activePage === 'learning-and-development.php'
                ? 'bg-green-100 font-medium'
                : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">school</span>
          <span class="sidebar-label">Learning and Development</span>
        </a>

        <a href="personal-reports.php"
           title="Personal Reports"
           class="flex items-center gap-3 px-4 py-2 rounded-lg
           <?= $activePage === 'personal-reports.php'
                ? 'bg-green-100 font-medium'
                : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">assessment</span>
          <span class="sidebar-label">Personal Reports</span>
        </a>
      </div>
    </div>

    <!-- MISCELLANEOUS -->
    <div>
      <button
        data-collapse-toggle="misc"
        class="w-full flex items-center justify-between px-2 mb-2
               text-xs font-semibold text-gray-400 uppercase">

        <span>Miscellaneous</span>
        <span class="material-icons text-sm transition-transform">
          expand_more
        </span>
      </button>

      <div data-collapse-content="misc" class="space-y-1">

        <!-- NOTIFICATIONS (WITH BADGE) -->
        <a href="notifications.php"
           title="Notifications"
            class="flex items-center justify-between px-4 py-2 rounded-lg <?= $activePage === 'notifications.php' ? 'bg-green-100 font-medium' : 'hover:bg-gray-100' ?>">

          <div class="flex items-center gap-3">
            <span class="material-icons text-base">notifications</span>
            <span class="sidebar-label">Notifications</span>
          </div>

          <?php if (($employeeUnreadNotificationCount ?? 0) > 0): ?>
            <span class="text-xs bg-red-600 text-white px-2 py-0.5 rounded-full">
              <?= htmlspecialchars((string)($employeeUnreadNotificationBadge ?? '0'), ENT_QUOTES, 'UTF-8') ?>
            </span>
          <?php endif; ?>
        </a>

        <a href="support.php"
           title="Support"
           class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'support.php' ? 'bg-green-100 font-medium' : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">help_outline</span>
          <span class="sidebar-label">Support</span>
        </a>

        <a href="settings.php"
          title="Settings"
          class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'settings.php' ? 'bg-green-100 font-medium' : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">settings</span>
          <span class="sidebar-label">Settings</span>
        </a>

      </div>
    </div>

  </nav>

</aside>
