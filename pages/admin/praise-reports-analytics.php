<?php
$pageTitle = 'PRAISE Reports and Analytics | Admin';
$activePage = 'praise-reports-analytics.php';
$breadcrumbs = ['PRAISE', 'Reports and Analytics'];

ob_start();
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">PRAISE Reports and Analytics</h1>
        <p class="text-sm text-slate-300 mt-2">Review performance summary outputs and recognition history records.</p>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Performance Summary Reports</h2>
        <p class="text-sm text-slate-500 mt-1">Analyze consolidated performance scores and trend indicators by period.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Period</th>
                    <th class="text-left px-4 py-3">Evaluated Employees</th>
                    <th class="text-left px-4 py-3">Average Rating</th>
                    <th class="text-left px-4 py-3">Top Category</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Q1 2026</td>
                    <td class="px-4 py-3">232</td>
                    <td class="px-4 py-3">4.12 / 5.00</td>
                    <td class="px-4 py-3">Very Satisfactory</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Recognition History</h2>
        <p class="text-sm text-slate-500 mt-1">View timeline of previous awardees and historical recognition records.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Award Cycle</th>
                    <th class="text-left px-4 py-3">Award Category</th>
                    <th class="text-left px-4 py-3">Awardee</th>
                    <th class="text-left px-4 py-3">Published Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Q4 2025</td>
                    <td class="px-4 py-3">Employee of the Quarter</td>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3">Jan 10, 2026</td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Annual 2025</td>
                    <td class="px-4 py-3">Excellence in Service</td>
                    <td class="px-4 py-3">Mark Villanueva</td>
                    <td class="px-4 py-3">Dec 20, 2025</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
