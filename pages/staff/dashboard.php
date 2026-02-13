<?php
$pageTitle = 'Staff Dashboard | DA HRIS';
$activePage = 'dashboard.php';
$breadcrumbs = ['Dashboard'];

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Staff Dashboard</h1>
    <p class="text-sm text-gray-500">Overview of HR operations, recruitment pipeline, records, and reporting tasks.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
    <div class="bg-white border rounded-xl p-5">
        <p class="text-sm text-gray-500">Active Employees</p>
        <p class="text-2xl font-bold text-green-700 mt-1">214</p>
        <p class="text-xs text-gray-500 mt-1">Current active records</p>
    </div>

    <div class="bg-white border rounded-xl p-5">
        <p class="text-sm text-gray-500">Pending Applications</p>
        <p class="text-2xl font-bold text-blue-700 mt-1">18</p>
        <p class="text-xs text-gray-500 mt-1">For screening and interview</p>
    </div>

    <div class="bg-white border rounded-xl p-5">
        <p class="text-sm text-gray-500">Documents for Verification</p>
        <p class="text-2xl font-bold text-yellow-700 mt-1">12</p>
        <p class="text-xs text-gray-500 mt-1">Awaiting validation</p>
    </div>

    <div class="bg-white border rounded-xl p-5">
        <p class="text-sm text-gray-500">Payroll Status</p>
        <p class="text-2xl font-bold text-purple-700 mt-1">In Progress</p>
        <p class="text-xs text-gray-500 mt-1">February 2026 cycle</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <section class="bg-white border rounded-xl p-6 xl:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-800">Recruitment & HR Updates</h2>
            <a href="notifications.php" class="text-sm text-green-700 hover:underline">View all</a>
        </div>

        <ul class="space-y-4 text-sm">
            <li class="border-l-4 border-green-500 pl-4">
                <p class="font-medium text-gray-800">New vacancy posted for Training Specialist I</p>
                <p class="text-xs text-gray-500">Feb 13, 2026 · Recruitment Module</p>
            </li>
            <li class="border-l-4 border-yellow-500 pl-4">
                <p class="font-medium text-gray-800">5 applicants advanced to interview stage</p>
                <p class="text-xs text-gray-500">Feb 12, 2026 · Applicant Tracking</p>
            </li>
            <li class="border-l-4 border-blue-500 pl-4">
                <p class="font-medium text-gray-800">Attendance report generated successfully</p>
                <p class="text-xs text-gray-500">Feb 11, 2026 · Reports</p>
            </li>
        </ul>
    </section>

    <section class="bg-white border rounded-xl p-6">
        <h2 class="font-semibold text-gray-800 mb-4">My Tasks</h2>

        <ul class="space-y-3 text-sm">
            <li class="flex justify-between items-center gap-3">
                <span>Verify uploaded credentials</span>
                <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">Pending</span>
            </li>
            <li class="flex justify-between items-center gap-3">
                <span>Approve leave/overtime requests</span>
                <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-800">Due</span>
            </li>
            <li class="flex justify-between items-center gap-3">
                <span>Finalize payroll computation</span>
                <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800">Ongoing</span>
            </li>
        </ul>
    </section>

    <section class="bg-white border rounded-xl p-6 xl:col-span-3">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-800">Pending Approvals</h2>
            <a href="timekeeping.php" class="text-sm text-green-700 hover:underline">Open timekeeping</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium">Request</th>
                        <th class="text-left px-4 py-3 font-medium">Owner</th>
                        <th class="text-left px-4 py-3 font-medium">Module</th>
                        <th class="text-left px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr>
                        <td class="px-4 py-3">Overtime Approval</td>
                        <td class="px-4 py-3">Juan Dela Cruz</td>
                        <td class="px-4 py-3">Timekeeping</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">For Review</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Credential Verification</td>
                        <td class="px-4 py-3">Maria Santos</td>
                        <td class="px-4 py-3">Document Management</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Interview Assessment Submission</td>
                        <td class="px-4 py-3">Recruitment Panel</td>
                        <td class="px-4 py-3">Recruitment</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">In Progress</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <section class="bg-white border rounded-xl p-6">
        <h2 class="font-semibold text-gray-800 mb-4">Recent Activity</h2>

        <ul class="space-y-3 text-sm text-gray-700">
            <li>
                <p>Generated payroll summary export</p>
                <p class="text-xs text-gray-500">Feb 13, 2026 · 09:30 AM</p>
            </li>
            <li>
                <p>Updated employee status to On Leave</p>
                <p class="text-xs text-gray-500">Feb 12, 2026 · 03:15 PM</p>
            </li>
            <li>
                <p>Submitted final recommendation for applicant batch 02</p>
                <p class="text-xs text-gray-500">Feb 11, 2026 · 04:45 PM</p>
            </li>
        </ul>
    </section>

    <section class="bg-white border rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-800">HR Shortcuts</h2>
            <a href="reports.php" class="text-sm text-green-700 hover:underline">Generate reports</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <a href="personal-information.php" class="flex items-center gap-3 border rounded-lg p-3 hover:bg-gray-50">
                <span class="material-symbols-outlined text-green-700">badge</span>
                Employee Profiles
            </a>
            <a href="document-management.php" class="flex items-center gap-3 border rounded-lg p-3 hover:bg-gray-50">
                <span class="material-symbols-outlined text-blue-700">description</span>
                Document Management
            </a>
            <a href="recruitment.php" class="flex items-center gap-3 border rounded-lg p-3 hover:bg-gray-50">
                <span class="material-symbols-outlined text-purple-700">person_search</span>
                Recruitment
            </a>
            <a href="export/payroll.php" class="flex items-center gap-3 border rounded-lg p-3 hover:bg-gray-50">
                <span class="material-symbols-outlined text-yellow-700">download</span>
                Export
            </a>
        </div>
    </section>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
