<?php
$pageTitle   = 'Personal Reports | DA HRIS';
$activePage  = 'personal-reports.php';
$breadcrumbs = ['Personal Reports'];

ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-6">
  <h1 class="text-2xl font-bold">Personal Reports</h1>
  <p class="text-sm text-gray-500">
    View and export your official employment-related reports
  </p>
</div>

<!-- GLOBAL FILTERS -->
<div class="bg-white border rounded-lg p-4 mb-6">
  <form class="flex flex-wrap gap-4 items-end text-sm">

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
        <option>Payroll</option>
        <option>Performance</option>
      </select>
    </div>

    <button
      type="submit"
      class="ml-auto bg-daGreen text-white px-4 py-2 rounded-lg">
      Apply Filters
    </button>

  </form>
</div>

<!-- ================= ATTENDANCE REPORT ================= -->
<section class="mb-10">

  <div class="flex justify-between items-center mb-3">
    <div>
      <h2 class="text-lg font-semibold">Attendance Summary Report</h2>
      <p class="text-sm text-gray-500">
        Monthly summary of attendance, absences, and late records
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
          <th class="px-4 py-3 text-left">Month</th>
          <th class="px-4 py-3 text-left">Days Present</th>
          <th class="px-4 py-3 text-left">Late</th>
          <th class="px-4 py-3 text-left">Absent</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <tr>
          <td class="px-4 py-3">January 2026</td>
          <td class="px-4 py-3">20</td>
          <td class="px-4 py-3">1</td>
          <td class="px-4 py-3">0</td>
        </tr>
        <tr>
          <td class="px-4 py-3">February 2026</td>
          <td class="px-4 py-3">19</td>
          <td class="px-4 py-3">0</td>
          <td class="px-4 py-3">1</td>
        </tr>
      </tbody>
    </table>
  </div>

</section>

<!-- ================= PAYROLL REPORT ================= -->
<section class="mb-10">

  <div class="flex justify-between items-center mb-3">
    <div>
      <h2 class="text-lg font-semibold">Payroll History Report</h2>
      <p class="text-sm text-gray-500">
        Record of issued payslips and salary details
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
          <th class="px-4 py-3 text-left">Pay Period</th>
          <th class="px-4 py-3 text-left">Gross Pay</th>
          <th class="px-4 py-3 text-left">Deductions</th>
          <th class="px-4 py-3 text-left">Net Pay</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <tr>
          <td class="px-4 py-3">January 2026</td>
          <td class="px-4 py-3">₱21,000.00</td>
          <td class="px-4 py-3">₱2,550.00</td>
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
      <h2 class="text-lg font-semibold">Performance Summary Report</h2>
      <p class="text-sm text-gray-500">
        PRAISE evaluations and performance ratings
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
          <th class="px-4 py-3 text-left">Evaluation Period</th>
          <th class="px-4 py-3 text-left">Rating</th>
          <th class="px-4 py-3 text-left">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <tr>
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
include './includes/layout.php';
