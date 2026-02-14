<?php
$pageTitle = 'Learning and Development | Admin';
$activePage = 'learning-and-development.php';
$breadcrumbs = ['Learning and Development'];

ob_start();
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Learning and Development</h1>
        <p class="text-sm text-slate-300 mt-2">Manage employee training records, schedules, attendance, and learning insights.</p>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Employee Training Records</h2>
        <p class="text-sm text-slate-500 mt-1">Track completed trainings, certifications, and development credits per employee.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Latest Training</th>
                    <th class="text-left px-4 py-3">Completion Date</th>
                    <th class="text-left px-4 py-3">Hours Earned</th>
                    <th class="text-left px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3">Records Management Workshop</td>
                    <td class="px-4 py-3">Feb 10, 2026</td>
                    <td class="px-4 py-3">8 hrs</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800">Completed</span></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Mark Villanueva</td>
                    <td class="px-4 py-3">Cybersecurity Awareness Training</td>
                    <td class="px-4 py-3">Feb 08, 2026</td>
                    <td class="px-4 py-3">6 hrs</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Validated</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Training Schedules</h2>
        <p class="text-sm text-slate-500 mt-1">Plan and assign upcoming training sessions across departments.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Training Title</label>
            <input type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter training title">
        </div>
        <div>
            <label class="text-slate-600">Schedule Date</label>
            <input type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-slate-600">Department</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Human Resource Division</option>
                <option>Management Information Systems</option>
                <option>Training Division</option>
            </select>
        </div>
        <div class="md:col-span-3 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Schedule</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Attendance Tracker</h2>
        <p class="text-sm text-slate-500 mt-1">Record and monitor employee attendance per training session.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Training</th>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Attendance</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Records Management Workshop</td>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3">Feb 10, 2026</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800">Present</span></td>
                    <td class="px-4 py-3"><button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">Update</button></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Cybersecurity Awareness Training</td>
                    <td class="px-4 py-3">Mark Villanueva</td>
                    <td class="px-4 py-3">Feb 08, 2026</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800">Late</span></td>
                    <td class="px-4 py-3"><button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">Update</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Reports and Analytics</h2>
        <p class="text-sm text-slate-500 mt-1">Review participation metrics and training completion insights.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase text-emerald-700">Completed Trainings</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">74</p>
            <p class="text-xs text-slate-600 mt-1">For current quarter</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase text-slate-600">Average Attendance</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">91%</p>
            <p class="text-xs text-slate-600 mt-1">Across all sessions</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase text-amber-700">Pending Validations</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">12</p>
            <p class="text-xs text-slate-600 mt-1">Training records awaiting admin review</p>
        </article>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
