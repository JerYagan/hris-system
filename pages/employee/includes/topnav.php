<header id="topnav" class="sticky top-0 z-30 border-b bg-white/95 backdrop-blur transition-transform duration-300 ease-in-out">

  <div class="px-6 h-16 flex items-center justify-between gap-4">
    <div class="flex items-center gap-3 min-w-0">
      <button id="sidebarToggle" class="text-gray-600 hover:text-gray-900 focus:outline-none mt-1"
        aria-label="Toggle sidebar">
        <span class="material-icons">menu</span>
      </button>

      <div class="font-semibold truncate">
        Human Resource Information System
      </div>
    </div>

    <div class="flex items-center gap-4">
      <a href="notifications.php"
         class="relative rounded-md p-1 text-gray-600 transition hover:bg-gray-100 hover:text-daGreen"
         aria-label="Notifications">
        <span class="material-icons">notifications</span>
        <?php if (($employeeUnreadNotificationCount ?? 0) > 0): ?>
          <span class="absolute -right-1 -top-1 inline-flex min-h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white">
            <?= htmlspecialchars((string)($employeeUnreadNotificationBadge ?? '0'), ENT_QUOTES, 'UTF-8') ?>
          </span>
        <?php endif; ?>
      </a>

      <span class="hidden sm:block h-6 w-px bg-gray-200"></span>

      <div class="relative" id="profileDropdown">
        <button id="profileToggle" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-gray-700 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none">
          <?php if (!empty($employeeTopnavPhotoUrl)): ?>
            <img src="<?= htmlspecialchars((string)$employeeTopnavPhotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Profile" class="h-8 w-8 rounded-full object-cover border">
          <?php else: ?>
            <div class="w-8 h-8 rounded-full bg-daGreen text-white flex items-center justify-center text-xs font-semibold">
              <?= htmlspecialchars((string)($employeeTopnavInitials ?? 'EM'), ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>

          <div class="leading-tight hidden md:block text-left">
            <p class="text-sm font-medium"><?= htmlspecialchars((string)($employeeTopnavDisplayName ?? 'Employee'), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-gray-500"><?= htmlspecialchars((string)($employeeTopnavRoleLabel ?? 'Employee'), ENT_QUOTES, 'UTF-8') ?></p>
          </div>

          <span class="material-icons text-gray-500 text-sm">expand_more</span>
        </button>

        <div id="profileMenu" class="absolute right-0 mt-2 w-56 rounded-xl border bg-white p-2 shadow-sm hidden z-50">
          <a href="personal-information.php" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
            <span class="material-icons text-sm">person</span>
            My Profile
          </a>
          <a href="settings.php" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
            <span class="material-icons text-sm">settings</span>
            Settings
          </a>

          <div class="my-1 border-t"></div>

          <a href="/hris-system/pages/auth/logout.php" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50 font-medium">
            <span class="material-icons text-sm">logout</span>
            Logout
          </a>
        </div>
      </div>
    </div>
  </div>
</header>