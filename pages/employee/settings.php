<?php
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

<!-- SETTINGS CONTAINER -->
<div class="bg-white border rounded-xl overflow-hidden flex min-h-[520px]">

  <!-- LEFT: SETTINGS NAV -->
  <aside class="w-64 border-r bg-gray-50 p-4 shrink-0">
    <nav class="space-y-1 text-sm">

      <button
        data-tab="account"
        class="settings-tab w-full flex items-center gap-3 px-3 py-2 rounded-lg
               bg-daGreen/10 text-daGreen font-medium">
        <span class="material-icons text-base">person</span>
        Account
      </button>

      <button
        data-tab="security"
        class="settings-tab w-full flex items-center gap-3 px-3 py-2 rounded-lg
               hover:bg-gray-100">
        <span class="material-icons text-base">security</span>
        Security
      </button>

      <button
        data-tab="notifications"
        class="settings-tab w-full flex items-center gap-3 px-3 py-2 rounded-lg
               hover:bg-gray-100">
        <span class="material-icons text-base">notifications</span>
        Notifications
      </button>

      <button
        data-tab="privacy"
        class="settings-tab w-full flex items-center gap-3 px-3 py-2 rounded-lg
               hover:bg-gray-100">
        <span class="material-icons text-base">privacy_tip</span>
        Privacy
      </button>

    </nav>
  </aside>

  <!-- RIGHT: SETTINGS CONTENT -->
  <section class="flex-1 p-8">

    <!-- ================= ACCOUNT ================= -->
    <section data-tab-content="account">

      <div class="mb-6">
        <h2 class="text-lg font-semibold">Account Information</h2>
        <p class="text-sm text-gray-500">
          Basic details associated with your account
        </p>
      </div>

      <div class="space-y-6">

        <div>
          <p class="text-sm text-gray-500">Full Name</p>
          <p class="font-medium">Juan Dela Cruz</p>
        </div>

        <div>
          <p class="text-sm text-gray-500">Email Address</p>
          <p class="font-medium">juan.delacruz@da.gov.ph</p>
        </div>

        <button
          class="inline-flex items-center gap-2 text-sm text-daGreen hover:underline">
          <span class="material-icons text-base">edit</span>
          Request profile update
        </button>

      </div>

    </section>

    <!-- ================= SECURITY ================= -->
    <section data-tab-content="security" class="hidden">

      <div class="mb-6">
        <h2 class="text-lg font-semibold">Security</h2>
        <p class="text-sm text-gray-500">
          Protect your account and manage active sessions
        </p>
      </div>

      <div class="space-y-6">

        <div class="flex justify-between items-center border-b pb-4">
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
            <p class="font-medium">Active Session</p>
            <p class="text-xs text-gray-500">
              This device · Manila, Philippines
            </p>
          </div>

          <button
            data-open-logout
            class="px-4 py-2 border rounded-lg text-sm text-red-600">
            Log out
          </button>
        </div>

      </div>

    </section>

    <!-- ================= NOTIFICATIONS ================= -->
    <section data-tab-content="notifications" class="hidden">

      <div class="mb-6">
        <h2 class="text-lg font-semibold">Notification Preferences</h2>
        <p class="text-sm text-gray-500">
          Choose how you receive system notifications
        </p>
      </div>

      <div class="space-y-4">

        <label class="flex justify-between items-center">
          <span>System Alerts</span>
          <input type="checkbox" checked>
        </label>

        <label class="flex justify-between items-center">
          <span>HR Announcements</span>
          <input type="checkbox" checked>
        </label>

        <label class="flex justify-between items-center">
          <span>Application & Evaluation Updates</span>
          <input type="checkbox">
        </label>

        <button
          class="mt-4 bg-daGreen text-white px-4 py-2 rounded-lg text-sm">
          Save Preferences
        </button>

      </div>

    </section>

    <!-- ================= PRIVACY ================= -->
    <section data-tab-content="privacy" class="hidden">

      <div class="mb-6">
        <h2 class="text-lg font-semibold">Privacy & Data Protection</h2>
        <p class="text-sm text-gray-500">
          Transparency on how your data is handled
        </p>
      </div>

      <div class="space-y-4 text-sm text-gray-700">

        <p>
          Your personal data is processed in compliance with the
          <strong>Data Privacy Act of 2012 (RA 10173)</strong>.
        </p>

        <p>
          Access to personal information is restricted to authorized
          personnel and is logged for audit purposes.
        </p>

        <a href="#" class="text-daGreen hover:underline">
          View data privacy policy
        </a>

      </div>

    </section>

  </section>

</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
