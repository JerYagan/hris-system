<?php
$pageTitle = 'Report and Analytics | Admin';
$activePage = 'report-analytics.php';
$breadcrumbs = ['Report and Analytics'];

ob_start();
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Report and Analytics</h1>
        <p class="text-sm text-slate-300 mt-2">View workforce insights, review attendance and payroll summaries, and export reports.</p>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Employee Statistics Dashboard</h2>
        <p class="text-sm text-slate-500 mt-1">High-level employee metrics for active workforce, departmental distribution, and status trends.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Employees</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">248</p>
            <p class="text-xs text-slate-500 mt-1">Current active masterlist</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Active</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">232</p>
            <p class="text-xs text-slate-500 mt-1">93.5% workforce utilization</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase tracking-wide text-amber-700">On Leave</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">10</p>
            <p class="text-xs text-slate-500 mt-1">Approved leave records this cycle</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-rose-50">
            <p class="text-xs uppercase tracking-wide text-rose-700">Inactive</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">16</p>
            <p class="text-xs text-slate-500 mt-1">For separation or archival</p>
        </article>
    </div>

    <div class="px-6 pb-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">Top Department Count</p>
            <p class="font-semibold text-slate-800 mt-2">Human Resource Division - 54 Employees</p>
            <p class="text-xs text-slate-500 mt-1">Highest concentration by organizational unit.</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">New Hires (Last 30 Days)</p>
            <p class="font-semibold text-slate-800 mt-2">8 New Employee Records</p>
            <p class="text-xs text-slate-500 mt-1">Based on approved onboarding entries.</p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Attendance and Payroll Summary</h2>
        <p class="text-sm text-slate-500 mt-1">Consolidated attendance and payroll figures for the current payroll cutoff.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Metric</th>
                    <th class="text-left px-4 py-3">Current Cutoff</th>
                    <th class="text-left px-4 py-3">Previous Cutoff</th>
                    <th class="text-left px-4 py-3">Variance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Attendance Compliance</td>
                    <td class="px-4 py-3">96.2%</td>
                    <td class="px-4 py-3">95.4%</td>
                    <td class="px-4 py-3"><span class="text-emerald-700">+0.8%</span></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Late Incidents</td>
                    <td class="px-4 py-3">31</td>
                    <td class="px-4 py-3">36</td>
                    <td class="px-4 py-3"><span class="text-emerald-700">-5</span></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Total Gross Payroll</td>
                    <td class="px-4 py-3">₱6,548,000</td>
                    <td class="px-4 py-3">₱6,492,400</td>
                    <td class="px-4 py-3"><span class="text-amber-700">+₱55,600</span></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Total Net Payroll</td>
                    <td class="px-4 py-3">₱5,882,300</td>
                    <td class="px-4 py-3">₱5,836,100</td>
                    <td class="px-4 py-3"><span class="text-amber-700">+₱46,200</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Exports Reports (PDF, Excel)</h2>
        <p class="text-sm text-slate-500 mt-1">Export summary or detailed reports for compliance, management review, and submission.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Report Type</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Employee Statistics</option>
                <option>Attendance Summary</option>
                <option>Payroll Summary</option>
                <option>Combined Report</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Coverage</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Current Cutoff</option>
                <option>Monthly</option>
                <option>Quarterly</option>
                <option>Custom Range</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Format</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>PDF</option>
                <option>Excel (.xlsx)</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Department Filter</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>All Departments</option>
                <option>Human Resource Division</option>
                <option>Management Information Systems</option>
                <option>Training Division</option>
            </select>
        </div>
        <div class="md:col-span-4 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Export Report</button>
        </div>
    </form>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
