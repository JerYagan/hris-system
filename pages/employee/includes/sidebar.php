<aside
  id="sidebar"
  data-sidebar-mode="overlay"
  class="w-72 bg-white border-r py-6
         fixed inset-y-0 left-0 z-50
         transform transition-transform duration-200 ease-in-out
         -translate-x-full flex flex-col"
>

  <!-- BRAND -->
  <div class="px-4 shadow-md pb-4">
    <div class="flex items-start justify-between gap-3">
      <div class="flex min-w-0 items-center gap-3">
        <?php if (!empty($employeeTopnavPhotoUrl)): ?>
          <img src="<?= htmlspecialchars((string)$employeeTopnavPhotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Profile" class="h-11 w-11 rounded-2xl border border-gray-200 object-cover shadow-sm">
        <?php else: ?>
          <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-daGreen text-sm font-semibold text-white shadow-sm">
            <?= htmlspecialchars((string)($employeeTopnavInitials ?? 'EM'), ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>
        <div class="min-w-0">
          <p class="truncate text-sm font-semibold text-gray-900"><?= htmlspecialchars((string)($employeeTopnavDisplayName ?? 'Employee'), ENT_QUOTES, 'UTF-8') ?></p>
          <p class="truncate text-xs text-gray-500"><?= htmlspecialchars((string)($employeeTopnavRoleLabel ?? 'Employee'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
      </div>
      <button id="sidebarClose" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900" aria-label="Close sidebar">
        <span class="material-icons text-[20px]">menu</span>
      </button>
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

    <!-- MY RECORDS -->
    <div>
      <button
        data-collapse-toggle="employee-records"
        class="w-full flex items-center justify-between px-2 mb-2
               text-xs font-semibold text-gray-400 uppercase">

        <span>My Records</span>
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
           title="My Payslip"
           class="flex items-center gap-3 px-4 py-2 rounded-lg
           <?= $activePage === 'payroll.php'
                ? 'bg-green-100 font-medium'
                : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">payments</span>
          <span class="sidebar-label">My Payslip</span>
        </a>
      </div>
    </div>

    <!-- GROWTH AND REPORTS -->
    <div>
      <button
        data-collapse-toggle="performance"
        class="w-full flex items-center justify-between px-2 mb-2
               text-xs font-semibold text-gray-400 uppercase">

        <span>Growth and Reports</span>
        <span class="material-icons text-sm transition-transform">
          expand_more
        </span>
      </button>

      <div data-collapse-content="performance" class="space-y-1">
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
           title="My reports"
           class="flex items-center gap-3 px-4 py-2 rounded-lg
           <?= $activePage === 'personal-reports.php'
                ? 'bg-green-100 font-medium'
                : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">assessment</span>
          <span class="sidebar-label">My reports</span>
        </a>
      </div>
    </div>

    <!-- HELP AND SUPPORT -->
    <div>
      <button
        data-collapse-toggle="misc"
        class="w-full flex items-center justify-between px-2 mb-2
               text-xs font-semibold text-gray-400 uppercase">

        <span>Help and Support</span>
        <span class="material-icons text-sm transition-transform">
          expand_more
        </span>
      </button>

      <div data-collapse-content="misc" class="space-y-1">

        <a href="support.php"
           title="Support"
           class="flex items-center gap-3 px-4 py-2 rounded-lg <?= $activePage === 'support.php' ? 'bg-green-100 font-medium' : 'hover:bg-gray-100' ?>">
          <span class="material-icons text-base">help_outline</span>
          <span class="sidebar-label">Support</span>
        </a>

      </div>
    </div>

  </nav>

</aside>
