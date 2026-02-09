<?php
$pageTitle = 'Dashboard | DA HRIS';
$activePage = 'dashboard.php';
$breadcrumbs = ['Dashboard'];

ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-6">
  <h1 class="text-2xl font-bold">Dashboard</h1>
  <p class="text-sm text-gray-500">
    Overview of your employment records and activities
  </p>
</div>

<!-- ================= SUMMARY CARDS ================= -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

  <div class="bg-white rounded-xl shadow p-5">
    <p class="text-sm text-gray-500">Attendance Today</p>

    <p class="text-2xl font-bold text-green-600 mt-1">
      Present
    </p>

    <p id="currentTime" class="text-xs text-gray-500 mt-1">
      Loading time...
    </p>

    <span class="inline-block mt-2 px-2 py-1 text-xs rounded-full
               bg-green-100 text-green-800">
      On Shift
    </span>
  </div>


  <!-- DOCUMENTS -->
  <div class="bg-white rounded-xl shadow p-5">
    <p class="text-sm text-gray-500">Pending Documents</p>
    <p class="text-2xl font-bold mt-1">2</p>
    <p class="text-xs text-gray-400 mt-1">Awaiting HR approval</p>
  </div>

  <!-- SUPPORT -->
  <div class="bg-white rounded-xl shadow p-5">
    <p class="text-sm text-gray-500">Open Support Tickets</p>
    <p class="text-2xl font-bold text-yellow-600 mt-1">1</p>
    <p class="text-xs text-gray-400 mt-1">Under review</p>
  </div>

  <!-- PRAISE -->
  <div class="bg-white rounded-xl shadow p-5">
    <p class="text-sm text-gray-500">PRAISE Status</p>
    <p class="text-2xl font-bold text-blue-600 mt-1">In Progress</p>
    <p class="text-xs text-gray-400 mt-1">Self-evaluation submitted</p>
  </div>

</div>

<!-- ================= MAIN GRID ================= -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- ANNOUNCEMENTS -->
  <section class="bg-white rounded-xl shadow p-6 lg:col-span-2">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-semibold">Recent Announcements</h2>
      <a href="notifications.php" class="text-sm text-blue-600 hover:underline">
        View all
      </a>
    </div>

    <ul class="space-y-4 text-sm">
      <li class="border-l-4 border-red-500 pl-4">
        <p class="font-medium">Scheduled System Maintenance</p>
        <p class="text-xs text-gray-500">Feb 15, 2026 · 10:00 PM</p>
      </li>

      <li class="border-l-4 border-green-500 pl-4">
        <p class="font-medium">Updated Leave Policy</p>
        <p class="text-xs text-gray-500">Effective immediately</p>
      </li>
    </ul>
  </section>

  <!-- MY TASKS -->
  <section class="bg-white rounded-xl shadow p-6">
    <h2 class="font-semibold mb-4">My Tasks</h2>

    <ul class="space-y-3 text-sm">
      <li class="flex justify-between items-center">
        <span>Complete PRAISE self-evaluation</span>
        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">
          Pending
        </span>
      </li>

      <li class="flex justify-between items-center">
        <span>Upload required documents</span>
        <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">
          Due
        </span>
      </li>
    </ul>
  </section>

  <!-- PENDING APPROVALS -->
  <section class="bg-white rounded-xl shadow p-6 lg:col-span-3">
    <h2 class="font-semibold mb-4">Pending Approvals</h2>

    <ul class="space-y-3 text-sm">
      <li class="flex justify-between items-center">
        <span>Leave Request – Juan Dela Cruz</span>
        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
          For Review
        </span>
      </li>

      <li class="flex justify-between items-center">
        <span>Document Submission – Maria Santos</span>
        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
          Pending
        </span>
      </li>
    </ul>

    <a href="#" class="inline-block mt-4 text-sm text-blue-600 hover:underline">
      Go to Approvals
    </a>
  </section>

</div>

<!-- ================= BOTTOM GRID ================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">

  <!-- RECENT ACTIVITY -->
  <section class="bg-white rounded-xl shadow p-6">
    <h2 class="font-semibold mb-4">Recent Activity</h2>

    <ul class="text-sm space-y-3">
      <li>
        <p>You submitted a support ticket</p>
        <p class="text-xs text-gray-500">Feb 11, 2026 · 4:10 PM</p>
      </li>
      <li>
        <p>Your document was approved by HR</p>
        <p class="text-xs text-gray-500">Feb 10, 2026 · 3:30 PM</p>
      </li>
      <li>
        <p>PRAISE self-evaluation submitted</p>
        <p class="text-xs text-gray-500">Feb 9, 2026 · 5:45 PM</p>
      </li>
    </ul>
  </section>

  <!-- QUICK LINKS -->
  <section class="bg-white rounded-xl shadow p-6">
    <h2 class="font-semibold mb-4">Quick Links</h2>

    <div class="grid grid-cols-2 gap-4 text-sm">

      <a href="timekeeping.php" class="flex items-center gap-3 border rounded-lg p-3 hover:bg-gray-50">
        <span class="material-icons text-blue-600">schedule</span>
        Timekeeping
      </a>

      <a href="document-management.php" class="flex items-center gap-3 border rounded-lg p-3 hover:bg-gray-50">
        <span class="material-icons text-green-600">description</span>
        Documents
      </a>

      <a href="support.php" class="flex items-center gap-3 border rounded-lg p-3 hover:bg-gray-50">
        <span class="material-icons text-purple-600">support_agent</span>
        Support
      </a>

      <a href="praise.php" class="flex items-center gap-3 border rounded-lg p-3 hover:bg-gray-50">
        <span class="material-icons text-yellow-600">emoji_events</span>
        PRAISE
      </a>

    </div>
  </section>


</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
