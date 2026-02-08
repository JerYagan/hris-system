<?php
/**
 * Employee Personal Reports
 * DA-ATI HRIS
 */

$pageTitle = 'Personal Reports | DA HRIS';
$activePage = 'personal-reports.php';
$breadcrumbs = ['Personal Reports'];

ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-6">
  <h1 class="text-2xl font-bold">Personal Reports</h1>
  <p class="text-sm text-gray-500">
    View and generate your personal attendance, payroll, and performance reports.
  </p>
</div>

<!-- REPORT CARDS -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

  <!-- ATTENDANCE SUMMARY -->
  <div class="bg-white rounded-xl shadow p-6">
    <div class="flex items-center gap-3 mb-3">
      <span class="material-icons text-daGreen">event_available</span>
      <h2 class="font-semibold">Attendance Summary Report</h2>
    </div>

    <p class="text-sm text-gray-600 mb-4">
      View total days present, absences, tardiness, and attendance trends.
    </p>

    <ul class="text-sm mb-4 space-y-1 list-disc list-inside">
      <li>Total Days Worked</li>
      <li>Leave Records</li>
      <li>Late & Undertime Summary</li>
    </ul>

    <div class="flex gap-2">
      <button data-open-attendance-report class="px-4 py-2 bg-daGreen text-white rounded-lg text-sm">
        View Report
      </button>
      <button class="px-4 py-2 border rounded-lg text-sm">
        Export
      </button>
    </div>
  </div>

  <!-- PAYROLL HISTORY -->
  <div class="bg-white rounded-xl shadow p-6">
    <div class="flex items-center gap-3 mb-3">
      <span class="material-icons text-blue-600">payments</span>
      <h2 class="font-semibold">Payroll History Report</h2>
    </div>

    <p class="text-sm text-gray-600 mb-4">
      Review salary history, deductions, and net pay per pay period.
    </p>

    <ul class="text-sm mb-4 space-y-1 list-disc list-inside">
      <li>Monthly Salary Records</li>
      <li>Government Deductions</li>
      <li>Payslip Archive</li>
    </ul>

    <div class="flex gap-2">
      <button data-open-payroll-report class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">
        View Report
      </button>
      <button
        data-export="attendance"
        class="px-4 py-2 border rounded-lg text-sm">
        Export
      </button>
    </div>
  </div>

  <!-- EMPLOYEE RESULT SUMMARY -->
  <div class="bg-white rounded-xl shadow p-6">
    <div class="flex items-center gap-3 mb-3">
      <span class="material-icons text-purple-600">assessment</span>
      <h2 class="font-semibold">Employee Result Summary</h2>
    </div>

    <p class="text-sm text-gray-600 mb-4">
      Consolidated performance results based on PRAISE evaluations.
    </p>

    <ul class="text-sm mb-4 space-y-1 list-disc list-inside">
      <li>Self-Evaluation Score</li>
      <li>Supervisor Rating</li>
      <li>Award Eligibility</li>
    </ul>

    <div class="flex gap-2">
      <button data-open-performance-report class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm">
        View Report
      </button>
      <button
        data-export="attendance"
        class="px-4 py-2 border rounded-lg text-sm">
        Export
      </button>
    </div>
  </div>

</div>

<!-- NOTE -->
<div class="mt-8 bg-white rounded-xl shadow p-5 text-sm text-gray-600">
  Reports are system-generated and read-only. Any discrepancies should be
  reported to the Human Resource Office for verification.
</div>

<!-- ================= REPORT MODALS ================= -->

<!-- ATTENDANCE REPORT MODAL -->
<div id="attendanceReportModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">


  <div class="bg-white w-full max-w-2xl rounded-xl shadow-lg
              max-h-[90vh] flex flex-col">

    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 class="text-lg font-semibold">Attendance Summary</h2>
      <button data-close-attendance-report>
        <span class="material-icons">close</span>
      </button>
    </div>

    <div class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <div class="mt-4">
        <canvas id="attendanceChart" height="120"></canvas>
      </div>
      <p><strong>Total Days Worked:</strong> 22</p>
      <p><strong>Total Leaves:</strong> 2</p>
      <p><strong>Late Instances:</strong> 1</p>
      <p><strong>Undertime:</strong> 0</p>
    </div>

    <div class="px-6 py-4 border-t flex justify-end shrink-0">
      <button data-close-attendance-report class="border px-4 py-2 rounded-lg text-sm">
        Close
      </button>
    </div>

  </div>
</div>

<!-- PAYROLL REPORT MODAL -->
<div id="payrollReportModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">

  <div class="bg-white w-full max-w-2xl rounded-xl shadow-lg
              max-h-[90vh] flex flex-col">

    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 class="text-lg font-semibold">Payroll History</h2>
      <button data-close-payroll-report>
        <span class="material-icons">close</span>
      </button>
    </div>

    <div class="px-6 py-5 space-y-3 text-sm overflow-y-auto">
      <div class="mt-4">
        <canvas id="payrollChart" height="120"></canvas>
      </div>
      <p>January 2026 – Net Pay: ₱18,450.00</p>
      <p>December 2025 – Net Pay: ₱18,450.00</p>
      <p>November 2025 – Net Pay: ₱18,450.00</p>
    </div>

    <div class="px-6 py-4 border-t flex justify-end shrink-0">
      <button data-close-payroll-report class="border px-4 py-2 rounded-lg text-sm">
        Close
      </button>
    </div>

  </div>
</div>

<!-- PERFORMANCE REPORT MODAL -->
<div id="performanceReportModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">

  <div class="bg-white w-full max-w-2xl rounded-xl shadow-lg
              max-h-[90vh] flex flex-col">

    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 class="text-lg font-semibold">Employee Result Summary</h2>
      <button data-close-performance-report>
        <span class="material-icons">close</span>
      </button>
    </div>

    <div class="px-6 py-5 space-y-3 text-sm overflow-y-auto">
      <div class="mt-4">
        <canvas id="performanceChart" height="80"></canvas>
      </div>
      <p><strong>Self-Evaluation:</strong> 4.5 / 5</p>
      <p><strong>Supervisor Rating:</strong> 4.7 / 5</p>
      <p><strong>Award Eligibility:</strong> Qualified</p>
    </div>

    <div class="px-6 py-4 border-t flex justify-end shrink-0">
      <button data-close-performance-report class="border px-4 py-2 rounded-lg text-sm">
        Close
      </button>
    </div>

  </div>
</div>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
