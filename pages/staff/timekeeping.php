<?php
$pageTitle = 'Timekeeping | Staff';
$activePage = 'timekeeping.php';
$breadcrumbs = ['Timekeeping'];

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Timekeeping Module</h1>
    <p class="text-sm text-gray-500">Record daily attendance, process leave/overtime approvals, and generate attendance reports.</p>
</div>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Log Daily Attendance</h2>
        <p class="text-sm text-gray-500 mt-1">Capture staff attendance records for each workday.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div>
            <label class="text-gray-600">Employee</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Select employee</option>
                <option>Maria Santos</option>
                <option>John Cruz</option>
            </select>
        </div>
        <div>
            <label class="text-gray-600">Date</label>
            <input type="date" class="w-full mt-1 border rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-gray-600">Time In</label>
            <input type="time" class="w-full mt-1 border rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-gray-600">Time Out</label>
            <input type="time" class="w-full mt-1 border rounded-md px-3 py-2">
        </div>

        <div class="md:col-span-4 flex justify-end">
            <button type="submit" class="px-5 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Attendance</button>
        </div>
    </form>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Approve Leave / Overtime</h2>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Request Type</th>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr>
                    <td class="px-4 py-3">Maria Santos</td>
                    <td class="px-4 py-3">Leave</td>
                    <td class="px-4 py-3">Feb 14, 2026</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending</span></td>
                    <td class="px-4 py-3 flex gap-2">
                        <button class="px-3 py-1.5 rounded-md bg-green-700 text-white hover:bg-green-800">Approve</button>
                        <button class="px-3 py-1.5 rounded-md border text-gray-700 hover:bg-gray-50">Reject</button>
                    </td>
                </tr>
                <tr>
                    <td class="px-4 py-3">John Cruz</td>
                    <td class="px-4 py-3">Overtime</td>
                    <td class="px-4 py-3">Feb 13, 2026</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending</span></td>
                    <td class="px-4 py-3 flex gap-2">
                        <button class="px-3 py-1.5 rounded-md bg-green-700 text-white hover:bg-green-800">Approve</button>
                        <button class="px-3 py-1.5 rounded-md border text-gray-700 hover:bg-gray-50">Reject</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Generate Attendance Reports</h2>
            <p class="text-sm text-gray-500 mt-1">Export attendance summaries by date range and department.</p>
        </div>
        <a href="export/attendance.php" class="text-sm text-green-700 hover:underline">Export Attendance</a>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div>
            <label class="text-gray-600">From</label>
            <input type="date" class="w-full mt-1 border rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-gray-600">To</label>
            <input type="date" class="w-full mt-1 border rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-gray-600">Department</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>All Departments</option>
                <option>Human Resources</option>
                <option>Administration</option>
            </select>
        </div>
        <div class="flex items-end">
            <button class="w-full px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Generate Report</button>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
