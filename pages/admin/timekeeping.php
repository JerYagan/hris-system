<?php
$pageTitle = 'Timekeeping | Admin';
$activePage = 'timekeeping.php';
$breadcrumbs = ['Timekeeping'];

ob_start();
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Timekeeping</h1>
        <p class="text-sm text-slate-300 mt-2">Monitor attendance, process leave actions, and manage manual logs for payroll compliance.</p>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Monitor Attendance</h2>
        <p class="text-sm text-slate-500 mt-1">Track daily attendance status and identify late, absent, and incomplete records.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Time In</th>
                    <th class="text-left px-4 py-3">Time Out</th>
                    <th class="text-left px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3">Feb 14, 2026</td>
                    <td class="px-4 py-3">08:03 AM</td>
                    <td class="px-4 py-3">05:02 PM</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800">Present</span></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Mark Villanueva</td>
                    <td class="px-4 py-3">Feb 14, 2026</td>
                    <td class="px-4 py-3">08:45 AM</td>
                    <td class="px-4 py-3">05:10 PM</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800">Late</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Edit / Update Attendance (Approve or Reject with Reason)</h2>
        <p class="text-sm text-slate-500 mt-1">Review attendance correction requests and apply approved updates with decision basis.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Employee</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Ana Dela Cruz</option>
                <option>Mark Villanueva</option>
                <option>Lea Ramos</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Attendance Date</label>
            <input type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-slate-600">Requested Time In</label>
            <input type="time" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-slate-600">Requested Time Out</label>
            <input type="time" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-slate-600">Decision</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Approve</option>
                <option>Reject</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Reason</label>
            <input type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="State reason for approval or rejection">
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Submit Decision</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Archive Attendance</h2>
        <p class="text-sm text-slate-500 mt-1">Move finalized attendance cutoffs into archive to preserve historical records.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Payroll Cutoff</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Feb 01 - Feb 15, 2026</option>
                <option>Jan 16 - Jan 31, 2026</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Archive Date</label>
            <input type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Archive Cutoff</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">View List of Archive Attendance</h2>
        <p class="text-sm text-slate-500 mt-1">Review archived cutoffs and retrieve attendance references when needed.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Archive ID</th>
                    <th class="text-left px-4 py-3">Cutoff Period</th>
                    <th class="text-left px-4 py-3">Archived Date</th>
                    <th class="text-left px-4 py-3">Archived By</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">ATD-ARC-022</td>
                    <td class="px-4 py-3">Jan 16 - Jan 31, 2026</td>
                    <td class="px-4 py-3">Feb 01, 2026</td>
                    <td class="px-4 py-3">Admin User</td>
                    <td class="px-4 py-3"><button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">View</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Manage Leave Request</h2>
        <p class="text-sm text-slate-500 mt-1">Review submitted leave requests before final approval or rejection action.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Leave Type</th>
                    <th class="text-left px-4 py-3">Date Range</th>
                    <th class="text-left px-4 py-3">Reason</th>
                    <th class="text-left px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3">Vacation Leave</td>
                    <td class="px-4 py-3">Feb 20-22, 2026</td>
                    <td class="px-4 py-3">Family event</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800">Pending</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Approve / Reject Leave</h2>
        <p class="text-sm text-slate-500 mt-1">Apply final leave decision and record rationale for payroll and audit references.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Employee</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Ana Dela Cruz</option>
                <option>Mark Villanueva</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Decision</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Approve</option>
                <option>Reject</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Decision Notes</label>
            <textarea rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add approval condition or rejection reason"></textarea>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Leave Decision</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Manage Logs</h2>
        <p class="text-sm text-slate-500 mt-1">Maintain manual log entries and track correction requests for missing punches.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Employee</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Ana Dela Cruz</option>
                <option>Mark Villanueva</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Log Date</label>
            <input type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-slate-600">Log Type</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Manual Time In</option>
                <option>Manual Time Out</option>
                <option>Correction Entry</option>
            </select>
        </div>
        <div class="md:col-span-3">
            <label class="text-slate-600">Log Details</label>
            <textarea rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add log notes and reason for manual entry"></textarea>
        </div>
        <div class="md:col-span-3 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Clear</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Log</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Export Manual Logs</h2>
            <p class="text-sm text-slate-500 mt-1">Download manual attendance logs for payroll reconciliation and reporting.</p>
        </div>
        <button type="button" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Export Logs</button>
    </header>

    <div class="p-6 text-sm text-slate-600">
        Select date range and export format from your backend action handler to generate official manual log reports.
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
