<?php
$pageTitle = 'Applicant Tracking | Admin';
$activePage = 'applicant-tracking.php';
$breadcrumbs = ['Recruitment', 'Applicant Tracking'];

ob_start();
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Applicant Tracking</h1>
        <p class="text-sm text-slate-300 mt-2">Monitor application progress, schedule interviews, and update final applicant status.</p>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Monitor Application Progress</h2>
            <p class="text-sm text-slate-500 mt-1">Track each applicant from screening to final hiring decision.</p>
        </div>
        <a href="applicants.php" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Back to Applicants</a>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Position Applied</th>
                    <th class="text-left px-4 py-3">Current Stage</th>
                    <th class="text-left px-4 py-3">Assigned Officer</th>
                    <th class="text-left px-4 py-3">Last Updated</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3">Administrative Aide VI</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Initial Interview Scheduled</span></td>
                    <td class="px-4 py-3">Maria Santos</td>
                    <td class="px-4 py-3">Feb 14, 2026</td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Mark Villanueva</td>
                    <td class="px-4 py-3">IT Officer I</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800">For Reschedule Confirmation</span></td>
                    <td class="px-4 py-3">John Reyes</td>
                    <td class="px-4 py-3">Feb 13, 2026</td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Lea Ramos</td>
                    <td class="px-4 py-3">Training Specialist I</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800">Panel Interview Completed</span></td>
                    <td class="px-4 py-3">Aileen Cruz</td>
                    <td class="px-4 py-3">Feb 12, 2026</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Schedule / Reschedule Interviews</h2>
        <p class="text-sm text-slate-500 mt-1">Set interview schedules and record any approved rescheduling requests.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Applicant</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Ana Dela Cruz</option>
                <option>Mark Villanueva</option>
                <option>Lea Ramos</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Interview Type</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Initial Interview</option>
                <option>Panel Interview</option>
                <option>Final Interview</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Interview Date</label>
            <input type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-slate-600">Interview Time</label>
            <input type="time" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-slate-600">Interview Mode</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>On-site</option>
                <option>Online</option>
                <option>Hybrid</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Action</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Schedule Interview</option>
                <option>Reschedule Interview</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Scheduling Notes</label>
            <textarea rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add schedule instructions, panel details, or reschedule reason"></textarea>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Schedule</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Update Application Status (Shortlisted / Hired / Rejected)</h2>
        <p class="text-sm text-slate-500 mt-1">Apply final applicant outcome and keep recruitment records up to date.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Applicant</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Ana Dela Cruz</option>
                <option>Mark Villanueva</option>
                <option>Lea Ramos</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">New Application Status</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Shortlisted</option>
                <option>Hired</option>
                <option>Rejected</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Effective Date</label>
            <input type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-slate-600">Notification Status</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Pending Notification</option>
                <option>Notified via System</option>
                <option>Notified via Email</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Remarks</label>
            <textarea rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add decision rationale or post-interview remarks"></textarea>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Clear</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Update Status</button>
        </div>
    </form>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
