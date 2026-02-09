<?php
/**
 * Employee Timekeeping
 * DA-ATI HRIS
 */

$pageTitle   = 'Timekeeping | DA HRIS';
$activePage  = 'timekeeping.php';
$breadcrumbs = ['Timekeeping'];

ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-6">
  <h1 class="text-2xl font-bold">Timekeeping</h1>
  <p class="text-sm text-gray-500">
    Manage your attendance, leave, and overtime requests
  </p>
</div>

<!-- ATTENDANCE OVERVIEW -->
<section class="bg-white rounded-xl shadow p-6 mb-6">
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold">
      Attendance <span class="text-daGreen">Overview</span>
    </h2>

    <div class="flex gap-2">
      <button
        data-open-leave
        class="bg-daGreen text-white px-5 py-2 rounded-lg text-sm font-medium hover:opacity-90">
        Create Leave Request
      </button>

      <button
        data-open-overtime
        class="border px-5 py-2 rounded-lg text-sm font-medium">
        File Overtime
      </button>
    </div>
  </div>

  <div class="grid md:grid-cols-4 gap-4 text-sm">
    <div>
      <label class="text-gray-500">Employee Name</label>
      <input disabled value="Employee One" class="w-full mt-1 p-2 bg-gray-100 rounded-lg">
    </div>

    <div>
      <label class="text-gray-500">Employee ID</label>
      <input disabled value="EMP-2024-001" class="w-full mt-1 p-2 bg-gray-100 rounded-lg">
    </div>

    <div>
      <label class="text-gray-500">Month</label>
      <input disabled value="March 2025" class="w-full mt-1 p-2 bg-gray-100 rounded-lg">
    </div>

    <div>
      <label class="text-gray-500">Total Working Days</label>
      <input disabled value="22" class="w-full mt-1 p-2 bg-gray-100 rounded-lg">
    </div>
  </div>
</section>

<!-- MONTHLY ATTENDANCE -->
<section class="bg-white rounded-xl shadow p-6 mb-6">
  <h2 class="text-lg font-bold mb-4">
    Monthly <span class="text-daGreen">Attendance Records</span>
  </h2>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b text-gray-500">
          <th class="text-left py-3">Date</th>
          <th class="text-left py-3">Time In</th>
          <th class="text-left py-3">Time Out</th>
          <th class="text-left py-3">Hours</th>
          <th class="text-left py-3">Status</th>
        </tr>
      </thead>

      <tbody>
        <tr class="border-b">
          <td class="py-3">2025-03-01</td>
          <td class="py-3">08:01 AM</td>
          <td class="py-3">05:02 PM</td>
          <td class="py-3">8.0</td>
          <td class="py-3">
            <span class="px-3 py-1 rounded-full bg-approved text-green-800">
              Present
            </span>
          </td>
        </tr>

        <tr class="border-b">
          <td class="py-3">2025-03-02</td>
          <td class="py-3">—</td>
          <td class="py-3">—</td>
          <td class="py-3">0</td>
          <td class="py-3">
            <span class="px-3 py-1 rounded-full bg-pending text-yellow-800">
              Leave
            </span>
          </td>
        </tr>

        <tr>
          <td class="py-3">2025-03-03</td>
          <td class="py-3">08:10 AM</td>
          <td class="py-3">06:30 PM</td>
          <td class="py-3">9.5</td>
          <td class="py-3">
            <span class="px-3 py-1 rounded-full bg-approved text-green-800">
              Overtime
            </span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<!-- LEAVE REQUEST HISTORY -->
<section class="bg-white rounded-xl shadow p-6">
  <h2 class="text-lg font-bold mb-4">
    Leave <span class="text-daGreen">Requests</span>
  </h2>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b text-gray-500">
          <th class="text-left py-3">Type</th>
          <th class="text-left py-3">From</th>
          <th class="text-left py-3">To</th>
          <th class="text-left py-3">Reason</th>
          <th class="text-left py-3">Status</th>
        </tr>
      </thead>

      <tbody>
        <tr class="border-b">
          <td class="py-3">Annual Leave</td>
          <td class="py-3">2025-02-12</td>
          <td class="py-3">2025-02-16</td>
          <td class="py-3">Family vacation</td>
          <td class="py-3">
            <span class="px-3 py-1 rounded-full bg-approved text-green-800">
              Approved
            </span>
          </td>
        </tr>

        <tr>
          <td class="py-3">Sick Leave</td>
          <td class="py-3">2025-03-01</td>
          <td class="py-3">2025-03-03</td>
          <td class="py-3">Medical reasons</td>
          <td class="py-3">
            <span class="px-3 py-1 rounded-full bg-pending text-yellow-800">
              Pending
            </span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<!-- ================= MODALS ================= -->

<!-- LEAVE REQUEST MODAL -->
<div
  id="leaveModal"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">

  <div class="bg-white w-full max-w-lg rounded-xl shadow-lg
              max-h-[90vh] flex flex-col">

    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 class="text-lg font-semibold">Create Leave Request</h2>
      <button data-close-leave>
        <span class="material-icons">close</span>
      </button>
    </div>

    <div class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <select class="w-full border rounded-lg p-2">
        <option>Annual Leave</option>
        <option>Sick Leave</option>
        <option>Vacation Leave</option>
      </select>

      <div class="grid grid-cols-2 gap-3">
        <input type="date" class="border rounded-lg p-2">
        <input type="date" class="border rounded-lg p-2">
      </div>

      <textarea
        class="w-full border rounded-lg p-2"
        rows="3"
        placeholder="Reason for leave"></textarea>
    </div>

    <div class="px-6 py-4 border-t flex justify-end gap-3 shrink-0">
      <button data-close-leave class="border px-4 py-2 rounded-lg text-sm">
        Cancel
      </button>
      <button class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm">
        Submit
      </button>
    </div>

  </div>
</div>

<!-- OVERTIME MODAL -->
<div
  id="overtimeModal"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">

  <div class="bg-white w-full max-w-lg rounded-xl shadow-lg
              max-h-[90vh] flex flex-col">

    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 class="text-lg font-semibold">Overtime Request</h2>
      <button data-close-overtime>
        <span class="material-icons">close</span>
      </button>
    </div>

    <div class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <input type="date" class="w-full border rounded-lg p-2">
      <input type="number" class="w-full border rounded-lg p-2" placeholder="Overtime Hours">
      <textarea
        class="w-full border rounded-lg p-2"
        rows="3"
        placeholder="Reason"></textarea>
    </div>

    <div class="px-6 py-4 border-t flex justify-end gap-3 shrink-0">
      <button data-close-overtime class="border px-4 py-2 rounded-lg text-sm">
        Cancel
      </button>
      <button class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm">
        Submit
      </button>
    </div>

  </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
