<?php
$dashboardContentSection = (string)($dashboardContentSection ?? 'full');
$renderSummarySection = in_array($dashboardContentSection, ['full', 'summary'], true);
$renderSecondarySection = in_array($dashboardContentSection, ['full', 'secondary'], true);
?>

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm"><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($renderSummarySection): ?>
    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
        <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[130px] flex flex-col justify-between">
            <p class="text-xs uppercase text-slate-500 tracking-wide">Pending Time Adjustments</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['pending_time_adjustments'] ?></p>
            <p class="text-xs text-amber-700 mt-1">Awaiting review in timekeeping</p>
        </article>
        <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[130px] flex flex-col justify-between">
            <p class="text-xs uppercase text-slate-500 tracking-wide">Pending Recruitment Decision</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['pending_recruitment_decisions'] ?></p>
            <p class="text-xs text-blue-700 mt-1">Applicants needing decision updates</p>
        </article>
        <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[130px] flex flex-col justify-between">
            <p class="text-xs uppercase text-slate-500 tracking-wide">Pending Document for Verification</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['pending_documents'] ?></p>
            <p class="text-xs text-purple-700 mt-1">Submitted records awaiting verification</p>
        </article>
        <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[130px] flex flex-col justify-between">
            <p class="text-xs uppercase text-slate-500 tracking-wide">Total Employees</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['total_employees'] ?></p>
            <p class="text-xs text-emerald-700 mt-1">Current active employment records</p>
        </article>
        <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[130px] flex flex-col justify-between">
            <p class="text-xs uppercase text-slate-500 tracking-wide">Total Applicants to Review</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['total_applicants_to_review'] ?></p>
            <p class="text-xs text-amber-700 mt-1">Requires admin final screening action</p>
        </article>
        <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[130px] flex flex-col justify-between">
            <p class="text-xs uppercase text-slate-500 tracking-wide">Pending Support Tickets</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['pending_support_tickets'] ?></p>
            <p class="text-xs text-amber-700 mt-1">Open employee and applicant tickets awaiting resolution</p>
        </article>
    </section>

    <section class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-slate-800">Attendance Overview</h2>
            <a href="timekeeping.php" class="text-sm text-emerald-700 hover:underline">Open module</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
                <p class="text-xs uppercase text-emerald-700">Present Today</p>
                <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['present_today'] ?></p>
            </article>
            <article class="rounded-xl border border-slate-200 p-4 bg-rose-50">
                <p class="text-xs uppercase text-rose-700">Absent Today</p>
                <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['absent_today'] ?></p>
                <p class="text-xs text-rose-700 mt-1">As of today · Weekly absence rate: <?= number_format((float)$dashboardSummary['absence_rate_week'], 2) ?>%</p>
            </article>
        </div>
    </section>
<?php endif; ?>

<?php if ($renderSecondarySection): ?>
    <section class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-slate-800">Chart Update Schedule Settings</h2>
            <span class="text-xs text-slate-500">Used in chart labels and timestamps</span>
        </div>
        <form action="dashboard.php" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <input type="hidden" name="form_action" value="save_dashboard_chart_schedule">
            <div>
                <label class="text-slate-600" for="dashboardAttendanceChartTime">Attendance Chart Time</label>
                <input id="dashboardAttendanceChartTime" name="attendance_chart_time" type="time" value="<?= htmlspecialchars((string)$dashboardChartSchedule['attendance_time_input'], ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
            </div>
            <div>
                <label class="text-slate-600" for="dashboardRecruitmentChartTime">Recruitment Chart Time</label>
                <input id="dashboardRecruitmentChartTime" name="recruitment_chart_time" type="time" value="<?= htmlspecialchars((string)$dashboardChartSchedule['recruitment_time_input'], ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Schedule</button>
            </div>
        </form>
    </section>

    <?php require __DIR__ . '/view.php'; ?>
<?php endif; ?>