<?php
/**
 * Notifications & Support
 * DA-ATI HRIS
 */

$pageTitle   = 'Support & Notifications | DA HRIS';
$activePage  = 'support.php';
$breadcrumbs = ['Support & Notifications'];

ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-6">
  <h1 class="text-2xl font-bold">Notifications & Support</h1>
  <p class="text-sm text-gray-500">
    View system announcements, application updates, and submit support requests.
  </p>
</div>

<!-- SUPPORT SECTIONS -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

  <!-- SYSTEM ALERTS -->
  <div class="bg-white rounded-xl shadow p-6 flex flex-col">
    <div class="flex items-center gap-3 mb-3">
      <span class="material-icons text-red-600">campaign</span>
      <h2 class="font-semibold">System Alerts</h2>
    </div>

    <p class="text-sm text-gray-600 mb-4">
      Important system-wide notices and HR announcements.
    </p>

    <ul class="text-sm space-y-2 mb-4">
      <li class="border-l-4 border-red-500 pl-3">
        <strong>System Maintenance</strong><br>
        <span class="text-xs text-gray-500">
          Scheduled on Feb 15, 2026
        </span>
      </li>
      <li class="border-l-4 border-green-500 pl-3">
        <strong>New Leave Policy</strong><br>
        <span class="text-xs text-gray-500">
          Effective immediately
        </span>
      </li>
    </ul>

    <button
      data-open-alerts
      class="mt-auto w-full px-4 py-2 border rounded-lg text-sm">
      View All Announcements
    </button>
  </div>

  <!-- SUPPORT TICKETS -->
  <div class="bg-white rounded-xl shadow p-6 flex flex-col">
    <div class="flex items-center gap-3 mb-3">
      <span class="material-icons text-blue-600">support_agent</span>
      <h2 class="font-semibold">Support Tickets</h2>
    </div>

    <p class="text-sm text-gray-600 mb-4">
      Submit issues, questions, or feedback to the HR or IT office.
    </p>

    <ul class="text-sm space-y-2 mb-4">
      <li>
        <span class="px-2 py-1 text-xs rounded-full bg-pending text-yellow-800">
          Open
        </span>
        <span class="ml-2">Unable to upload document</span>
      </li>
      <li>
        <span class="px-2 py-1 text-xs rounded-full bg-approved text-green-800">
          Resolved
        </span>
        <span class="ml-2">Incorrect personal info</span>
      </li>
    </ul>

    <button
      data-open-ticket
      class="mt-auto w-full px-4 py-2 bg-daGreen text-white rounded-lg text-sm">
      Submit Support Ticket
    </button>
  </div>

  <!-- APPLICATION UPDATES -->
  <div class="bg-white rounded-xl shadow p-6 flex flex-col">
    <div class="flex items-center gap-3 mb-3">
      <span class="material-icons text-purple-600">update</span>
      <h2 class="font-semibold">Application Updates</h2>
    </div>

    <p class="text-sm text-gray-600 mb-4">
      Updates related to evaluations, promotions, or applications.
    </p>

    <ul class="text-sm space-y-2 mb-4">
      <li>
        <strong>PRAISE Evaluation</strong><br>
        <span class="text-xs text-gray-500">
          Status: Under Review
        </span>
      </li>
      <li>
        <strong>Internal Recruitment</strong><br>
        <span class="text-xs text-gray-500">
          Status: Shortlisted
        </span>
      </li>
    </ul>

    <button
      data-open-updates
      class="mt-auto w-full px-4 py-2 border rounded-lg text-sm">
      View All Updates
    </button>
  </div>

</div>

<!-- ================= MODALS ================= -->

<!-- ANNOUNCEMENTS MODAL -->
<div id="alertsModal"
     class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
  <div class="bg-white w-full max-w-xl rounded-xl shadow-lg
              max-h-[90vh] flex flex-col">

    <div class="px-6 py-4 border-b flex justify-between items-center">
      <h2 class="text-lg font-semibold">HR Announcements</h2>
      <button data-close-alerts>
        <span class="material-icons">close</span>
      </button>
    </div>

    <div class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <div class="border-l-4 border-red-500 pl-4">
        <strong>System Maintenance</strong>
        <p class="text-gray-600 text-xs">
          The system will be unavailable on Feb 15, 2026.
        </p>
      </div>
      <div class="border-l-4 border-green-500 pl-4">
        <strong>New Leave Policy</strong>
        <p class="text-gray-600 text-xs">
          Updated leave guidelines are now in effect.
        </p>
      </div>
    </div>

    <div class="px-6 py-4 border-t flex justify-end">
      <button data-close-alerts class="border px-4 py-2 rounded-lg text-sm">
        Close
      </button>
    </div>
  </div>
</div>

<!-- SUPPORT TICKET MODAL -->
<div id="ticketModal"
     class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
  <div class="bg-white w-full max-w-lg rounded-xl shadow-lg
              max-h-[90vh] flex flex-col">

    <div class="px-6 py-4 border-b flex justify-between items-center">
      <h2 class="text-lg font-semibold">Submit Support Ticket</h2>
      <button data-close-ticket>
        <span class="material-icons">close</span>
      </button>
    </div>

    <div class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <input class="w-full border rounded-lg p-2" placeholder="Subject">
      <select class="w-full border rounded-lg p-2">
        <option>HR Concern</option>
        <option>IT Support</option>
        <option>System Bug</option>
      </select>
      <textarea class="w-full border rounded-lg p-2" rows="4"
                placeholder="Describe your concern"></textarea>
    </div>

    <div class="px-6 py-4 border-t flex justify-end gap-3">
      <button data-close-ticket class="border px-4 py-2 rounded-lg text-sm">
        Cancel
      </button>
      <button class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm">
        Submit
      </button>
    </div>
  </div>
</div>

<!-- UPDATES MODAL -->
<div id="updatesModal"
     class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
  <div class="bg-white w-full max-w-lg rounded-xl shadow-lg
              max-h-[90vh] flex flex-col">

    <div class="px-6 py-4 border-b flex justify-between items-center">
      <h2 class="text-lg font-semibold">Application Updates</h2>
      <button data-close-updates>
        <span class="material-icons">close</span>
      </button>
    </div>

    <div class="px-6 py-5 space-y-3 text-sm overflow-y-auto">
      <p><strong>PRAISE Evaluation:</strong> Under Review</p>
      <p><strong>Internal Recruitment:</strong> Shortlisted</p>
    </div>

    <div class="px-6 py-4 border-t flex justify-end">
      <button data-close-updates class="border px-4 py-2 rounded-lg text-sm">
        Close
      </button>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
