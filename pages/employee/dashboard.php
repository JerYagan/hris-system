<?php
/**
 * Employee Dashboard
 * DA-ATI HRIS
 */

$pageTitle   = 'Employee Dashboard | DA HRIS';
$activePage  = 'dashboard.php';
$breadcrumbs = ['Dashboard'];

/**
 * Capture page-specific content
 */
ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-6">
  <h1 class="text-2xl font-bold">Employee Dashboard</h1>
  <p class="text-sm text-gray-500">
    Overview of your requests, documents, and notifications
  </p>
</div>

<!-- QUICK ACTIONS -->
<div class="grid md:grid-cols-3 gap-6 mb-6">

  <a href="timekeeping.php"
     class="bg-white p-6 rounded-lg shadow
            hover:ring-2 hover:ring-daGreen transition">
    <span class="material-icons text-daGreen mb-2">add_circle</span>
    <h3 class="font-semibold">Create Leave Request</h3>
    <p class="text-sm text-gray-500 mt-1">
      Submit a new leave application
    </p>
  </a>

  <a href="document-management.php"
     class="bg-white p-6 rounded-lg shadow
            hover:ring-2 hover:ring-daGreen transition">
    <span class="material-icons text-daGreen mb-2">upload_file</span>
    <h3 class="font-semibold">Upload Documents</h3>
    <p class="text-sm text-gray-500 mt-1">
      Submit required documents
    </p>
  </a>

  <a href="reports.php"
     class="bg-white p-6 rounded-lg shadow
            hover:ring-2 hover:ring-daGreen transition">
    <span class="material-icons text-daGreen mb-2">notifications</span>
    <h3 class="font-semibold">View Notifications</h3>
    <p class="text-sm text-gray-500 mt-1">
      Check system updates
    </p>
  </a>

</div>

<!-- STATUS + CHART -->
<div class="grid md:grid-cols-2 gap-6 h-fit">

  <!-- REQUEST STATUS -->
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="font-semibold mb-4">Request Status</h2>

    <div class="space-y-3 text-sm">

      <div class="flex justify-between items-center
                  border rounded-lg px-4 py-3">
        <span>Leave Request</span>
        <span class="px-3 py-1 rounded-full
                     bg-pending text-yellow-800">
          Pending
        </span>
      </div>

      <div class="flex justify-between items-center
                  border rounded-lg px-4 py-3">
        <span>Medical Certificate</span>
        <span class="px-3 py-1 rounded-full
                     bg-approved text-green-800">
          Approved
        </span>
      </div>

      <div class="flex justify-between items-center
                  border rounded-lg px-4 py-3">
        <span>Performance Review</span>
        <span class="px-3 py-1 rounded-full
                     bg-rejected text-red-800">
          Rejected
        </span>
      </div>

    </div>
  </div>

  <!-- LEAVE CHART -->
  <div class="bg-white rounded-lg shadow p-6 flex-1">
    <h2 class="font-semibold mb-4">Leave Requests Overview</h2>
    <canvas id="leaveChart" height="80"></canvas>
  </div>

</div>

<!-- PAGE-SPECIFIC JS (SAFE TO KEEP HERE) -->
<script src="../../assets/js/script.js"></script>

<?php
/**
 * Inject content into the global layout
 */
$content = ob_get_clean();
include '../../includes/layout.php';
