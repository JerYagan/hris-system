<?php
$pageTitle   = 'Reports | DA HRIS';
$activePage  = 'reports.php';
$breadcrumbs = ['Reports'];

require_once '../../includes/auth-guard.php';

ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-6">
  <h1 class="text-2xl font-bold">Reports</h1>
  <p class="text-sm text-gray-500">
    Generate and export official HR, payroll, and attendance reports
  </p>
</div>

<!-- GLOBAL FILTERS -->
<div class="bg-white border rounded-lg p-4 mb-8">
  <form class="flex flex-wrap gap-4 items-end text-sm">

    <div>
      <label class="block text-gray-600 mb-1">Employee</label>
      <input
        type="text"
        placeholder="Search by name or ID"
        class="border rounded-lg px-3 py-2 w-56">
    </div>

    <div>
      <label class="block text-gray-600 mb-1">From</label>
      <input type="date" class="border rounded-lg px-3 py-2">
    </div>

    <div>
      <label class="block text-gray-600 mb-1">To</label>
      <input type="date" class="border rounded-lg px-3 py-2">
    </div>

    <div>
      <label class="block text-gray-600 mb-1">Report Type</label>
      <select class="border rounded-lg px-3 py-2">
        <option>All Reports</option>
        <option>Attendance</option>
        <option>Leave</option>
        <option>Payroll</option>
        <option>Performance</option>
      </select>
    </div>

    <button
      type="submit"
      class="ml-auto bg-daGreen text-white px-4 py-2 rounded-lg">
      Generate
    </button>

  </form>
</div>

<!-- ================= ATTENDANCE REPORT ================= -->
<section class="mb-12">

  <div class="flex justify-between items-center mb-3">
    <div>
      <h2 class="text-lg font-semibold">Attendance Report</h2>
      <p class="text-sm text-gray-500">
        Daily and monthly attendance records of employees
      </p>
    </div>

    <div class="flex gap-2">
      <button class="border px-3 py-2 rounded-lg text-sm">
        Export PDF
      </button>
      <button class="border px-3 py-2 rounded-lg text-sm">
        Export Excel
      </button>
    </div>
  </div>

  <div class="bg-white border rounded-lg overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-4 py-3 text-left">Employee</th>
          <th class="px-4 py-3 text-left">Period</th>
          <th class="px-4 py-3 text-left">Present</th>
          <th class="px-4 py-3 text-left">Late</th>
          <th class="px-4 py-3 text-left">Absent</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <tr>
          <td class="px-4 py-3">Juan Dela Cruz</td>
          <td class="px-4 py-3">Jan 2026</td>
          <td class="px-4 py-3">20</td>
          <td class="px-4 py-3">1</td>
          <td class="px-4 py-3">0</td>
        </tr>
      </tbody>
    </table>
  </div>

</section>

<!-- ================= LEAVE & TIMEKEEPING ================= -->
<section class="mb-12">

  <div class="flex justify-between items-center mb-3">
    <div>
      <h2 class="text-lg font-semibold">Leave & Timekeeping Report</h2>
      <p class="text-sm text-gray-500">
        Filed leaves, overtime, and timekeeping summaries
      </p>
    </div>

    <div class="flex gap-2">
      <button class="border px-3 py-2 rounded-lg text-sm">
        Export PDF
      </button>
      <button class="border px-3 py-2 rounded-lg text-sm">
        Export Excel
      </button>
    </div>
  </div>

  <div class="bg-white border rounded-lg overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-4 py-3 text-left">Employee</th>
          <th class="px-4 py-3 text-left">Type</th>
          <th class="px-4 py-3 text-left">Days / Hours</th>
          <th class="px-4 py-3 text-left">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <tr>
          <td class="px-4 py-3">Maria Santos</td>
          <td class="px-4 py-3">Vacation Leave</td>
          <td class="px-4 py-3">3 days</td>
          <td class="px-4 py-3">
            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
              Approved
            </span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

</section>

<!-- ================= PAYROLL REPORT ================= -->
<section class="mb-12">

  <div class="flex justify-between items-center mb-3">
    <div>
      <h2 class="text-lg font-semibold">Payroll Report</h2>
      <p class="text-sm text-gray-500">
        Salary, deductions, and net pay summary
      </p>
    </div>

    <div class="flex gap-2">
      <button class="border px-3 py-2 rounded-lg text-sm">
        Export PDF
      </button>
      <button class="border px-3 py-2 rounded-lg text-sm">
        Export Excel
      </button>
    </div>
  </div>

  <div class="bg-white border rounded-lg overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-4 py-3 text-left">Employee</th>
          <th class="px-4 py-3 text-left">Pay Period</th>
          <th class="px-4 py-3 text-left">Gross Pay</th>
          <th class="px-4 py-3 text-left">Net Pay</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <tr>
          <td class="px-4 py-3">Juan Dela Cruz</td>
          <td class="px-4 py-3">Jan 2026</td>
          <td class="px-4 py-3">₱21,000.00</td>
          <td class="px-4 py-3 font-medium">₱18,450.00</td>
        </tr>
      </tbody>
    </table>
  </div>

</section>

<!-- ================= PERFORMANCE REPORT ================= -->
<section>

  <div class="flex justify-between items-center mb-3">
    <div>
      <h2 class="text-lg font-semibold">Performance Report</h2>
      <p class="text-sm text-gray-500">
        PRAISE ratings and evaluation results
      </p>
    </div>

    <div class="flex gap-2">
      <button class="border px-3 py-2 rounded-lg text-sm">
        Export PDF
      </button>
    </div>
  </div>

  <div class="bg-white border rounded-lg overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-4 py-3 text-left">Employee</th>
          <th class="px-4 py-3 text-left">Evaluation Period</th>
          <th class="px-4 py-3 text-left">Rating</th>
          <th class="px-4 py-3 text-left">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <tr>
          <td class="px-4 py-3">Juan Dela Cruz</td>
          <td class="px-4 py-3">2025 Annual</td>
          <td class="px-4 py-3">Very Satisfactory</td>
          <td class="px-4 py-3">
            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
              Finalized
            </span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

</section>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
