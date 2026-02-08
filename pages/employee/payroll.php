<?php
/**
 * Employee Payroll
 * DA-ATI HRIS
 */

$pageTitle = 'Payroll | DA HRIS';
$activePage = 'payroll.php';
$breadcrumbs = ['Payroll'];

ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-6">
  <h1 class="text-2xl font-bold">Payroll</h1>
  <p class="text-sm text-gray-500">
    View salary breakdowns and payslip history
  </p>
</div>

<!-- SUMMARY CARDS -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

  <div class="bg-white p-5 rounded-lg shadow border">
    <p class="text-sm text-gray-500">Latest Net Pay</p>
    <h2 class="text-2xl font-bold mt-2">₱ 18,450.00</h2>
    <p class="text-xs text-gray-400 mt-1">January 2026</p>
  </div>

  <div class="bg-white p-5 rounded-lg shadow border">
    <p class="text-sm text-gray-500">Gross Pay</p>
    <h2 class="text-2xl font-bold mt-2">₱ 21,000.00</h2>
    <p class="text-xs text-gray-400 mt-1">Before deductions</p>
  </div>

  <div class="bg-white p-5 rounded-lg shadow border">
    <p class="text-sm text-gray-500">Total Deductions</p>
    <h2 class="text-2xl font-bold mt-2 text-red-600">₱ 2,550.00</h2>
    <p class="text-xs text-gray-400 mt-1">GSIS, PhilHealth, Pag-IBIG</p>
  </div>

</div>

<!-- PAYSLIP HISTORY -->
<div class="bg-white rounded-lg shadow border">

  <div class="flex items-center justify-between px-6 py-4 border-b">
    <h2 class="text-lg font-bold">
      Payslip <span class="text-daGreen">History</span>
    </h2>

    <select class="border rounded-md px-3 py-2 text-sm">
      <option>All Months</option>
      <option>2026</option>
      <option>2025</option>
    </select>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-6 py-3 text-left">Pay Period</th>
          <th class="px-6 py-3 text-left">Gross Pay</th>
          <th class="px-6 py-3 text-left">Deductions</th>
          <th class="px-6 py-3 text-left">Net Pay</th>
          <th class="px-6 py-3 text-left">Status</th>
          <th class="px-6 py-3 text-right">Actions</th>
        </tr>
      </thead>

      <tbody class="divide-y">

        <!-- ROW -->
        <tr class="hover:bg-gray-50">
          <td class="px-6 py-4">January 2026</td>
          <td class="px-6 py-4">₱ 21,000.00</td>
          <td class="px-6 py-4">₱ 2,550.00</td>
          <td class="px-6 py-4 font-medium">₱ 18,450.00</td>
          <td class="px-6 py-4">
            <span class="px-2 py-1 text-xs rounded-full bg-approved text-green-800">
              Approved
            </span>
          </td>
          <td class="px-6 py-4 text-right space-x-2">
            <button data-open-payslip class="text-blue-600 hover:underline text-sm">
              View
            </button>
            <button class="text-gray-600 hover:underline text-sm">
              Download
            </button>
          </td>
        </tr>

        <!-- ROW -->
        <tr class="hover:bg-gray-50">
          <td class="px-6 py-4">December 2025</td>
          <td class="px-6 py-4">₱ 21,000.00</td>
          <td class="px-6 py-4">₱ 2,550.00</td>
          <td class="px-6 py-4 font-medium">₱ 18,450.00</td>
          <td class="px-6 py-4">
            <span class="px-2 py-1 text-xs rounded-full bg-pending text-yellow-800">
              Pending
            </span>
          </td>
          <td class="px-6 py-4 text-right space-x-2">
            <button data-open-payslip class="text-blue-600 hover:underline text-sm">
              View
            </button>
            <button class="text-gray-400 cursor-not-allowed text-sm">
              Download
            </button>
          </td>
        </tr>

      </tbody>
    </table>
  </div>
</div>

<!-- ================= PAYSLIP MODAL ================= -->
<div id="payslipModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">

  <div class="bg-white w-full max-w-3xl md:max-w-4xl rounded-lg shadow-lg
            max-h-[85vh] flex flex-col scroll-smooth">


    <!-- HEADER -->
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h2 class="text-lg font-semibold">Payslip Details</h2>
      <button data-close-payslip>
        <span class="material-icons">close</span>
      </button>
    </div>

    <!-- BODY -->
    <div class="px-6 py-5 space-y-6 text-sm overflow-y-auto">

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <p class="text-gray-500">Employee Name</p>
          <p class="font-medium">Juan Dela Cruz</p>
        </div>
        <div>
          <p class="text-gray-500">Employee ID</p>
          <p class="font-medium">DA-EMP-00123</p>
        </div>
        <div>
          <p class="text-gray-500">Pay Period</p>
          <p class="font-medium">January 2026</p>
        </div>
        <div>
          <p class="text-gray-500">Status</p>
          <span class="inline-block px-2 py-1 rounded-full bg-approved text-green-800 text-xs">
            Approved
          </span>
        </div>
      </div>

      <div>
        <h3 class="font-semibold mb-2">Earnings</h3>
        <div class="border rounded-md divide-y">
          <div class="flex justify-between px-4 py-2">
            <span>Basic Salary</span>
            <span>₱ 18,000.00</span>
          </div>
          <div class="flex justify-between px-4 py-2">
            <span>Allowances</span>
            <span>₱ 3,000.00</span>
          </div>
        </div>
      </div>

      <div>
        <h3 class="font-semibold mb-2">Deductions</h3>
        <div class="border rounded-md divide-y">
          <div class="flex justify-between px-4 py-2">
            <span>GSIS</span>
            <span class="text-red-600">₱ 1,200.00</span>
          </div>
          <div class="flex justify-between px-4 py-2">
            <span>PhilHealth</span>
            <span class="text-red-600">₱ 900.00</span>
          </div>
          <div class="flex justify-between px-4 py-2">
            <span>Pag-IBIG</span>
            <span class="text-red-600">₱ 450.00</span>
          </div>
        </div>
      </div>

      <div class="border-t pt-4 grid md:grid-cols-3 gap-4">
        <div>
          <p class="text-gray-500">Gross Pay</p>
          <p class="font-semibold">₱ 21,000.00</p>
        </div>
        <div>
          <p class="text-gray-500">Total Deductions</p>
          <p class="font-semibold text-red-600">₱ 2,550.00</p>
        </div>
        <div>
          <p class="text-gray-500">Net Pay</p>
          <p class="font-semibold text-green-700">₱ 18,450.00</p>
        </div>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
      <button data-close-payslip class="px-4 py-2 border rounded-md text-sm">
        Close
      </button>
      <button class="px-4 py-2 bg-daGreen text-white rounded-md text-sm">
        Download PDF
      </button>
    </div>

  </div>
</div>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
