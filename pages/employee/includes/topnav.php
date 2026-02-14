<header id="topnav" class="h-16 bg-white border-b
         flex items-center justify-between px-6
         sticky top-0 z-30
         transition-transform duration-300 ease-in-out">

  <div class="flex items-center gap-3">
    <button id="sidebarToggle" class="text-gray-600 hover:text-gray-900 focus:outline-none mt-1"
      aria-label="Toggle sidebar">
      <span class="material-icons">menu</span>
    </button>

    <div class="font-semibold">
      Human Resource Information System
    </div>
  </div>

  <div class="flex items-center gap-6">
    <div class="relative">
      <span class="material-icons absolute left-3 top-2 text-gray-400 text-sm">search</span>
      <input type="text" placeholder="Search..." class="pl-9 pr-4 py-1.5 border rounded-lg text-sm">
    </div>

    <div class="relative">
      <a href="../employee/notifications.php">
        <span class="material-icons">notifications</span>
        <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full px-1">
          3
        </span>
      </a>
    </div>

    <!-- PROFILE DROPDOWN -->
    <div class="relative" id="profileDropdown">

      <!-- TRIGGER -->
      <button id="profileToggle" class="flex items-center gap-2 focus:outline-none">

        <div class="w-8 h-8 rounded-full bg-green-600 text-white
             flex items-center justify-center text-xs font-semibold">
          EO
        </div>

        <div class="leading-tight hidden md:block text-left">
          <p class="text-sm font-medium">Employee One</p>
          <p class="text-xs text-gray-500">Employee</p>
        </div>

        <span class="material-icons text-gray-500 text-sm">
          expand_more
        </span>
      </button>

      <!-- DROPDOWN MENU -->
      <div id="profileMenu" class="absolute right-0 mt-2 w-48 bg-white border rounded-lg shadow-lg
           hidden z-50">

        <a href="personal-information.php" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100">
          <span class="material-icons text-sm">person</span>
          My Profile
        </a>

        <a href="change-password.php" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100">
          <span class="material-icons text-sm">lock</span>
          Change Password
        </a>

        <div class="border-t my-1"></div>

        <a href="/hris-system/pages/auth/logout.php" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
          <span class="material-icons text-sm">logout</span>
          Logout
        </a>

      </div>
    </div>

  </div>
</header>