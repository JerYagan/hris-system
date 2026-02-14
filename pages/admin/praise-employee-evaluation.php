<?php
$pageTitle = 'Employee Evaluation Overview | Admin';
$activePage = 'praise-employee-evaluation.php';
$breadcrumbs = ['PRAISE', 'Employee Evaluation Overview'];

ob_start();
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Employee Evaluation Overview</h1>
        <p class="text-sm text-slate-300 mt-2">Configure evaluation periods, review supervisor ratings, and monitor overall performance ratings.</p>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Set Evaluation Periods</h2>
        <p class="text-sm text-slate-500 mt-1">Define timeline windows for employee performance evaluation cycles.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Evaluation Cycle</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Q1 2026</option>
                <option>Q2 2026</option>
                <option>Annual 2026</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Start Date</label>
            <input type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-slate-600">End Date</label>
            <input type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
        </div>
        <div class="md:col-span-3 flex justify-end mt-1">
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Period</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Approve Supervisor Ratings</h2>
        <p class="text-sm text-slate-500 mt-1">Validate submitted ratings before finalizing performance results.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Supervisor</th>
                    <th class="text-left px-4 py-3">Rating</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3">Maria Santos</td>
                    <td class="px-4 py-3">4.5 / 5.0</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800">Pending Approval</span></td>
                    <td class="px-4 py-3"><button type="button" class="px-3 py-1.5 rounded-md bg-emerald-700 text-white hover:bg-emerald-800">Approve</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">View Overall Performance Ratings</h2>
        <p class="text-sm text-slate-500 mt-1">Check finalized performance outcomes for employee development planning.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase text-emerald-700">Outstanding</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">34</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase text-slate-600">Very Satisfactory</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">141</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase text-amber-700">Needs Coaching</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">12</p>
        </article>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
