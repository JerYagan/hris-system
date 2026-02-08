<?php
/**
 * Settings
 * DA-ATI HRIS
 */

$pageTitle   = 'Settings | DA HRIS';
$activePage  = 'settings.php';
$breadcrumbs = ['Settings'];

ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-6">
  <h1 class="text-2xl font-bold">Settings</h1>
  <p class="text-sm text-gray-500">
    Manage your account preferences, security, and notifications
  </p>
</div>

<!-- SETTINGS GRID -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- PROFILE SETTINGS -->
  <section class="bg-white rounded-xl shadow p-6">
    <div class="flex items-center gap-3 mb-4">
      <span class="material-icons text-green-600">person</span>
      <h2 class="font-semibold">Profile</h2>
    </div>

    <div class="space-y-3 text-sm">
      <div>
        <p class="text-gray-500">Name</p>
        <p class="font-medium">Employee One</p>
      </div>

      <div>
        <p class="text-gray-500">Email</p>
        <p class="font-medium">employee.one@da.gov.ph</p>
      </div>

      <button
        class="mt-3 px-4 py-2 border rounded-lg text-sm">
        Edit Profile
      </button>
    </div>
  </section>

  <!-- SECURITY SETTINGS -->
  <section class="bg-white rounded-xl shadow p-6">
    <div class="flex items-center gap-3 mb-4">
      <span class="material-icons text-blue-600">security</span>
      <h2 class="font-semibold">Security</h2>
    </div>

    <div class="space-y-4 text-sm">
      <div class="flex justify-between items-center">
        <div>
          <p class="font-medium">Password</p>
          <p class="text-xs text-gray-500">
            Last changed 3 months ago
          </p>
        </div>

        <button
          data-open-password
          class="px-4 py-2 border rounded-lg text-sm">
          Change
        </button>
      </div>

      <div class="flex justify-between items-center">
        <div>
          <p class="font-medium">Active Sessions</p>
          <p class="text-xs text-gray-500">
            This device · Manila, PH
          </p>
        </div>

        <button
          data-open-logout
          class="px-4 py-2 border rounded-lg text-sm text-red-600">
          Logout
        </button>
      </div>
    </div>
  </section>

  <!-- NOTIFICATION SETTINGS -->
  <section class="bg-white rounded-xl shadow p-6">
    <div class="flex items-center gap-3 mb-4">
      <span class="material-icons text-purple-600">notifications</span>
      <h2 class="font-semibold">Notifications</h2>
    </div>

    <div class="space-y-4 text-sm">
      <label class="flex justify-between items-center">
        <span>System Alerts</span>
        <input type="checkbox" checked>
      </label>

      <label class="flex justify-between items-center">
        <span>HR Announcements</span>
        <input type="checkbox" checked>
      </label>

      <label class="flex justify-between items-center">
        <span>Application Updates</span>
        <input type="checkbox">
      </label>

      <button
        class="mt-3 px-4 py-2 bg-daGreen text-white rounded-lg text-sm">
        Save Preferences
      </button>
    </div>
  </section>

</div>

<!-- ================= MODALS ================= -->

<!-- CHANGE PASSWORD MODAL -->
<div id="passwordModal"
     class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">

  <div class="bg-white w-full max-w-md rounded-xl shadow-lg
              max-h-[90vh] flex flex-col">

    <div class="px-6 py-4 border-b flex justify-between items-center">
      <h2 class="text-lg font-semibold">Change Password</h2>
      <button data-close-password>
        <span class="material-icons">close</span>
      </button>
    </div>

    <div class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <input
        type="password"
        placeholder="Current Password"
        class="w-full border rounded-lg p-2">

      <input
        type="password"
        placeholder="New Password"
        class="w-full border rounded-lg p-2">

      <input
        type="password"
        placeholder="Confirm New Password"
        class="w-full border rounded-lg p-2">
    </div>

    <div class="px-6 py-4 border-t flex justify-end gap-3">
      <button data-close-password class="border px-4 py-2 rounded-lg text-sm">
        Cancel
      </button>
      <button class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm">
        Update Password
      </button>
    </div>
  </div>
</div>

<!-- LOGOUT CONFIRMATION MODAL -->
<div id="logoutModal"
     class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">

  <div class="bg-white w-full max-w-sm rounded-xl shadow-lg flex flex-col">

    <div class="px-6 py-4 border-b">
      <h2 class="text-lg font-semibold text-red-600">
        Confirm Logout
      </h2>
    </div>

    <div class="px-6 py-5 text-sm">
      Are you sure you want to log out from this session?
    </div>

    <div class="px-6 py-4 border-t flex justify-end gap-3">
      <button data-close-logout class="border px-4 py-2 rounded-lg text-sm">
        Cancel
      </button>
      <a href="/logout.php"
         class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm">
        Logout
      </a>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
