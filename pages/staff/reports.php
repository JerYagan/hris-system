<?php
$pageTitle = 'Reports | Staff';
$activePage = 'reports.php';
$breadcrumbs = ['Reports'];

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Report and Analytics</h1>
    <p class="text-sm text-gray-500">Generate staff-facing HR analytics, attendance/payroll summaries, and export-ready reports.</p>
</div>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Employee Statistics Dashboard</h2>
        <p class="text-sm text-gray-500 mt-1">Quick view of employee distribution and workforce metrics.</p>
    </header>

    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
        <div class="rounded-lg border p-4">
            <p class="text-xs uppercase text-gray-500">Total Employees</p>
            <p class="text-2xl font-semibold text-gray-800 mt-2">214</p>
        </div>
        <div class="rounded-lg border p-4">
            <p class="text-xs uppercase text-gray-500">Active</p>
            <p class="text-2xl font-semibold text-green-700 mt-2">198</p>
        </div>
        <div class="rounded-lg border p-4">
            <p class="text-xs uppercase text-gray-500">On Leave</p>
            <p class="text-2xl font-semibold text-yellow-700 mt-2">12</p>
        </div>
        <div class="rounded-lg border p-4">
            <p class="text-xs uppercase text-gray-500">Resigned</p>
            <p class="text-2xl font-semibold text-red-700 mt-2">4</p>
        </div>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Attendance and Payroll Summary</h2>
        <p class="text-sm text-gray-500 mt-1">Review attendance performance and payroll release status by period.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
        <div class="rounded-lg border p-4">
            <h3 class="font-medium text-gray-800 mb-2">Attendance Summary (Feb 2026)</h3>
            <ul class="space-y-1 text-gray-600">
                <li>Average attendance rate: 96%</li>
                <li>Late incidents: 18</li>
                <li>Approved leave entries: 34</li>
            </ul>
        </div>

        <div class="rounded-lg border p-4">
            <h3 class="font-medium text-gray-800 mb-2">Payroll Summary (Feb 2026)</h3>
            <ul class="space-y-1 text-gray-600">
                <li>Payroll cycle: Completed</li>
                <li>Total gross payout: ₱4,285,000</li>
                <li>Payslips generated: 214</li>
            </ul>
        </div>
    </div>
</section>

<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Exports Reports (PDF, Excel)</h2>
            <p class="text-sm text-gray-500 mt-1">Generate and download attendance, payroll, and performance exports.</p>
        </div>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div>
            <label class="text-gray-600">Report Type</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Attendance</option>
                <option>Payroll</option>
                <option>Performance</option>
            </select>
        </div>
        <div>
            <label class="text-gray-600">Date From</label>
            <input type="date" class="w-full mt-1 border rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-gray-600">Date To</label>
            <input type="date" class="w-full mt-1 border rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-gray-600">Format</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>PDF</option>
                <option>Excel</option>
            </select>
        </div>

        <div class="md:col-span-4 flex flex-wrap gap-2 justify-end">
            <a href="export/attendance.php" class="px-4 py-2 border rounded-md hover:bg-gray-50">Attendance Export</a>
            <a href="export/payroll.php" class="px-4 py-2 border rounded-md hover:bg-gray-50">Payroll Export</a>
            <a href="export/performance.php" class="px-4 py-2 border rounded-md hover:bg-gray-50">Performance Export</a>
            <button class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Generate</button>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
