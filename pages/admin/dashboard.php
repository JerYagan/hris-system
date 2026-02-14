<?php
$pageTitle = 'Admin Dashboard | DA HRIS';
$activePage = 'dashboard.php';
$breadcrumbs = ['Admin Dashboard'];

ob_start();
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin Module</p>
        <h1 class="text-2xl font-bold mt-1">Admin Operations Dashboard</h1>
        <p class="text-sm text-slate-300 mt-2">Operational overview of hiring workload, pending approvals, exports, and report activity.</p>
    </div>
</div>

<section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <article class="bg-white border border-slate-200 rounded-xl p-4">
        <p class="text-xs uppercase text-slate-500 tracking-wide">Attendance Alerts</p>
        <p class="text-2xl font-bold text-slate-800 mt-2">4</p>
        <p class="text-xs text-amber-700 mt-1">Flagged in attendance overview</p>
    </article>
    <article class="bg-white border border-slate-200 rounded-xl p-4">
        <p class="text-xs uppercase text-slate-500 tracking-wide">Pending Leave Requests</p>
        <p class="text-2xl font-bold text-slate-800 mt-2">7</p>
        <p class="text-xs text-amber-700 mt-1">Awaiting admin action</p>
    </article>
    <article class="bg-white border border-slate-200 rounded-xl p-4">
        <p class="text-xs uppercase text-slate-500 tracking-wide">Draft Announcements</p>
        <p class="text-2xl font-bold text-slate-800 mt-2">2</p>
        <p class="text-xs text-blue-700 mt-1">Ready for publishing</p>
    </article>
    <article class="bg-white border border-slate-200 rounded-xl p-4">
        <p class="text-xs uppercase text-slate-500 tracking-wide">Unread Notifications</p>
        <p class="text-2xl font-bold text-slate-800 mt-2">11</p>
        <p class="text-xs text-purple-700 mt-1">Includes 4 high-priority items</p>
    </article>
</section>

<section class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-slate-800">Attendance Overview</h2>
        <a href="attendance-overview.php" class="text-sm text-emerald-700 hover:underline">Open module</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase text-emerald-700">Present Today</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">226</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-rose-50">
            <p class="text-xs uppercase text-rose-700">Absent</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">8</p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-slate-800">Pending Leave Requests</h2>
        <a href="pending-leave-requests.php" class="text-sm text-emerald-700 hover:underline">Review all</a>
    </div>
    <ul class="space-y-3 text-sm">
        <li class="border rounded-lg p-3 flex items-center justify-between">
            <span class="text-slate-700">Ana Dela Cruz - Vacation Leave (3 days)</span>
            <span class="text-xs font-semibold text-amber-700">Pending</span>
        </li>
        <li class="border rounded-lg p-3 flex items-center justify-between">
            <span class="text-slate-700">Mark Villanueva - Sick Leave (1 day)</span>
            <span class="text-xs font-semibold text-amber-700">Pending</span>
        </li>
    </ul>
</section>

<section class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-slate-800">Create New Announcement</h2>
        <a href="create-announcement.php" class="text-sm text-emerald-700 hover:underline">Open form</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">Latest Draft</p>
            <p class="font-medium text-slate-800 mt-2">Payroll Release Reminder</p>
            <p class="text-xs text-slate-500 mt-1">Saved today at 09:25 AM</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">Publish Queue</p>
            <p class="font-medium text-slate-800 mt-2">2 announcements pending</p>
            <p class="text-xs text-slate-500 mt-1">Awaiting final admin confirmation</p>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-slate-800">View Notifications</h2>
        <a href="notifications.php" class="text-sm text-emerald-700 hover:underline">Open all notifications</a>
    </div>
    <div class="space-y-3 text-sm">
        <article class="border rounded-xl p-4 bg-amber-50 border-amber-200">
            <p class="font-medium text-slate-800">7 leave requests pending approval</p>
        </article>
        <article class="border rounded-xl p-4 bg-blue-50 border-blue-200">
            <p class="font-medium text-slate-800">Attendance anomalies detected in 2 departments</p>
        </article>
    </div>
</section>

<section class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-slate-800">Plantilla</h2>
            <a href="plantilla.php" class="text-sm text-emerald-700 hover:underline">Open plantilla</a>
        </div>
        <div class="space-y-2 text-sm">
            <p class="flex justify-between"><span>Approved Positions</span><span class="font-semibold text-slate-800">312</span></p>
            <p class="flex justify-between"><span>Filled Positions</span><span class="font-semibold text-slate-800">248</span></p>
            <p class="flex justify-between"><span>Vacant Positions</span><span class="font-semibold text-slate-800">64</span></p>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-slate-800">Employees per Department</h2>
            <a href="employees-per-department.php" class="text-sm text-emerald-700 hover:underline">View full report</a>
        </div>
        <ul class="space-y-2 text-sm">
            <li class="flex justify-between"><span>Human Resource Division</span><span class="font-semibold text-slate-800">54</span></li>
            <li class="flex justify-between"><span>Training Division</span><span class="font-semibold text-slate-800">48</span></li>
            <li class="flex justify-between"><span>Management Information Systems</span><span class="font-semibold text-slate-800">37</span></li>
            <li class="flex justify-between"><span>Administration</span><span class="font-semibold text-slate-800">33</span></li>
        </ul>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl p-6 mt-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-slate-800">Recruitment Pipeline Chart</h2>
        <span class="text-xs text-slate-500">Updated Feb 14, 2026</span>
    </div>
    <div class="h-72">
        <canvas
            data-chart-type="bar"
            data-chart-label="Applicants"
            data-chart-labels='["Registered", "Screened", "Shortlisted", "Hired"]'
            data-chart-values='[46, 30, 14, 5]'
            data-chart-colors='["#0f172a", "#10b981", "#3b82f6", "#f59e0b"]'
        ></canvas>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
