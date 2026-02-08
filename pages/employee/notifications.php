<?php
/**
 * Notifications
 * DA-ATI HRIS
 */

$pageTitle   = 'Notifications | DA HRIS';
$activePage  = 'notifications.php';
$breadcrumbs = ['Notifications'];

ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-6">
  <h1 class="text-2xl font-bold">Notifications</h1>
  <p class="text-sm text-gray-500">
    System alerts, HR announcements, and application updates
  </p>
</div>

<!-- FILTER BAR -->
<div class="bg-white rounded-xl shadow p-4 mb-6 flex flex-wrap gap-3 text-sm">
  <select class="border rounded-lg px-3 py-2">
    <option>All Categories</option>
    <option>System Alerts</option>
    <option>HR Announcements</option>
    <option>Application Updates</option>
  </select>

  <select class="border rounded-lg px-3 py-2">
    <option>All Status</option>
    <option>Unread</option>
    <option>Read</option>
  </select>
</div>

<!-- NOTIFICATION LIST -->
<div class="bg-white rounded-xl shadow divide-y">

  <!-- UNREAD NOTIFICATION -->
  <button
    data-open-notification
    class="w-full text-left px-6 py-4 hover:bg-gray-50
           flex items-start gap-4 bg-green-50">

    <span class="material-icons text-red-600 mt-1">
      campaign
    </span>

    <div class="flex-1">
      <p class="font-medium">
        Scheduled System Maintenance
      </p>
      <p class="text-sm text-gray-600">
        The HRIS system will be unavailable on Feb 15, 2026.
      </p>
      <p class="text-xs text-gray-500 mt-1">
        System Alert · Feb 10, 2026 · 9:30 AM
      </p>
    </div>

    <span class="w-2 h-2 bg-green-600 rounded-full mt-2"></span>
  </button>

  <!-- READ NOTIFICATION -->
  <button
    data-open-notification
    class="w-full text-left px-6 py-4 hover:bg-gray-50
           flex items-start gap-4">

    <span class="material-icons text-green-600 mt-1">
      announcement
    </span>

    <div class="flex-1">
      <p class="font-medium">
        Updated Leave Policy
      </p>
      <p class="text-sm text-gray-600">
        Please review the updated leave policy effective immediately.
      </p>
      <p class="text-xs text-gray-500 mt-1">
        HR Announcement · Feb 8, 2026 · 3:15 PM
      </p>
    </div>
  </button>

  <!-- APPLICATION UPDATE -->
  <button
    data-open-notification
    class="w-full text-left px-6 py-4 hover:bg-gray-50
           flex items-start gap-4">

    <span class="material-icons text-purple-600 mt-1">
      update
    </span>

    <div class="flex-1">
      <p class="font-medium">
        PRAISE Evaluation Status Update
      </p>
      <p class="text-sm text-gray-600">
        Your PRAISE evaluation is currently under review.
      </p>
      <p class="text-xs text-gray-500 mt-1">
        Application Update · Feb 7, 2026 · 1:00 PM
      </p>
    </div>
  </button>

</div>

<!-- ================= MODAL ================= -->

<!-- NOTIFICATION DETAILS MODAL -->
<div id="notificationModal"
     class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">

  <div class="bg-white w-full max-w-xl rounded-xl shadow-lg
              max-h-[90vh] flex flex-col">

    <!-- HEADER -->
    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 class="text-lg font-semibold">Notification Details</h2>
      <button data-close-notification>
        <span class="material-icons">close</span>
      </button>
    </div>

    <!-- BODY -->
    <div class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <p class="text-xs text-gray-500">
        System Alert · Feb 10, 2026 · 9:30 AM
      </p>

      <p class="font-semibold">
        Scheduled System Maintenance
      </p>

      <p class="text-gray-700">
        Please be advised that the DA-ATI HRIS system will undergo
        scheduled maintenance on February 15, 2026 from 10:00 PM
        to 12:00 AM. During this time, system access will be unavailable.
      </p>

      <p class="text-gray-700">
        We apologize for any inconvenience this may cause and
        appreciate your understanding.
      </p>
    </div>

    <!-- FOOTER -->
    <div class="px-6 py-4 border-t flex justify-end shrink-0">
      <button
        data-close-notification
        class="border px-4 py-2 rounded-lg text-sm">
        Close
      </button>
    </div>

  </div>
</div>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
