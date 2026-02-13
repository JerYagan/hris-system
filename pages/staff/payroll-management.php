<?php
$pageTitle = 'Payroll Management | Staff';
$activePage = 'payroll-management.php';
$breadcrumbs = ['Payroll Management'];

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Payroll Management</h1>
    <p class="text-sm text-gray-500">Process payroll cycle, review salary adjustments, and generate payslips for employees.</p>
</div>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Compute Monthly Payroll</h2>
            <p class="text-sm text-gray-500 mt-1">Calculate payroll based on attendance, approved overtime, and salary rules.</p>
        </div>
        <a href="export/payroll.php" class="text-sm text-green-700 hover:underline">Export Payroll</a>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div>
            <label class="text-gray-600">Payroll Month</label>
            <input type="month" class="w-full mt-1 border rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-gray-600">Department</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>All Departments</option>
                <option>Human Resources</option>
                <option>Administration</option>
            </select>
        </div>
        <div>
            <label class="text-gray-600">Cycle Type</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Monthly</option>
                <option>Semi-Monthly</option>
            </select>
        </div>
        <div class="flex items-end">
            <button class="w-full px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Run Computation</button>
        </div>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Review Salary Adjustment</h2>
        <p class="text-sm text-gray-500 mt-1">Validate changes before final payroll release.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Current Salary</th>
                    <th class="text-left px-4 py-3">Adjustment</th>
                    <th class="text-left px-4 py-3">Reason</th>
                    <th class="text-left px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr>
                    <td class="px-4 py-3">Maria Santos</td>
                    <td class="px-4 py-3">₱28,500</td>
                    <td class="px-4 py-3 text-green-700">+ ₱1,500</td>
                    <td class="px-4 py-3">Step increment</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">For Review</span></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">John Cruz</td>
                    <td class="px-4 py-3">₱18,000</td>
                    <td class="px-4 py-3 text-red-700">- ₱800</td>
                    <td class="px-4 py-3">Leave without pay</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Reviewed</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Generate Payslips</h2>
        <p class="text-sm text-gray-500 mt-1">Produce payslips after payroll and adjustments are finalized.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div>
            <label class="text-gray-600">Payroll Month</label>
            <input type="month" class="w-full mt-1 border rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-gray-600">Employee Scope</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>All Employees</option>
                <option>By Department</option>
                <option>Single Employee</option>
            </select>
        </div>
        <div>
            <label class="text-gray-600">Format</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>PDF</option>
                <option>ZIP (PDF Batch)</option>
            </select>
        </div>
        <div class="flex items-end">
            <button class="w-full px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Generate Payslips</button>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
