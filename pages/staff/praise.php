<?php
$pageTitle = 'PRAISE | Staff';
$activePage = 'praise.php';
$breadcrumbs = ['PRAISE'];

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Performance Management (PRAISE)</h1>
    <p class="text-sm text-gray-500">Manage scoring, award nominations, and evaluation report generation for employees.</p>
</div>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Input Performance Scores</h2>
        <p class="text-sm text-gray-500 mt-1">Record employee performance ratings per criteria.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
        <div>
            <label class="text-gray-600">Employee</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Select employee</option>
                <option>Maria Santos</option>
                <option>John Cruz</option>
            </select>
        </div>
        <div>
            <label class="text-gray-600">Evaluation Period</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Q1 2026</option>
                <option>Q2 2026</option>
            </select>
        </div>
        <div>
            <label class="text-gray-600">Productivity</label>
            <input type="number" min="1" max="5" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="1-5">
        </div>
        <div>
            <label class="text-gray-600">Quality</label>
            <input type="number" min="1" max="5" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="1-5">
        </div>

        <div class="md:col-span-2 lg:col-span-4">
            <label class="text-gray-600">Remarks</label>
            <textarea rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Evaluation remarks"></textarea>
        </div>

        <div class="md:col-span-2 lg:col-span-4 flex justify-end">
            <button type="submit" class="px-5 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Score</button>
        </div>
    </form>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Manage Nominations for Awards</h2>
        <p class="text-sm text-gray-500 mt-1">Track and review employees nominated for PRAISE awards.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Award Category</th>
                    <th class="text-left px-4 py-3">Nominator</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr>
                    <td class="px-4 py-3">Maria Santos</td>
                    <td class="px-4 py-3">Outstanding Staff</td>
                    <td class="px-4 py-3">HR Supervisor</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">For Review</span></td>
                    <td class="px-4 py-3"><button class="px-3 py-1.5 rounded-md border hover:bg-gray-50">Review</button></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">John Cruz</td>
                    <td class="px-4 py-3">Service Excellence</td>
                    <td class="px-4 py-3">Admin Head</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Approved</span></td>
                    <td class="px-4 py-3"><button class="px-3 py-1.5 rounded-md border hover:bg-gray-50">View</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Generate Evaluation Reports</h2>
            <p class="text-sm text-gray-500 mt-1">Export PRAISE summaries and evaluation results.</p>
        </div>
        <a href="export/performance.php" class="text-sm text-green-700 hover:underline">Export Performance</a>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div>
            <label class="text-gray-600">Report Type</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Evaluation Summary</option>
                <option>Nomination List</option>
                <option>Award Results</option>
            </select>
        </div>
        <div>
            <label class="text-gray-600">Period</label>
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
        <div class="flex items-end">
            <button class="w-full px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Generate Report</button>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
