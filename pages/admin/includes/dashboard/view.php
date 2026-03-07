<?php if ($state && $message): ?>
    <?php $alertClass = $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'; ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm"><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

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
        <p class="text-xs uppercase text-slate-500 tracking-wide">On Leave</p>
        <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['on_leave_today'] ?></p>
        <p class="text-xs text-amber-700 mt-1">Employees tagged on leave today</p>
    </article>
    <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[130px] flex flex-col justify-between">
        <p class="text-xs uppercase text-slate-500 tracking-wide">Pending Leave Requests</p>
        <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['pending_leave_requests'] ?></p>
        <p class="text-xs text-amber-700 mt-1">Awaiting admin action</p>
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
            <p class="text-2xl font-bold text-slate-800 mt-2\"><?= (int)$dashboardSummary['absent_today'] ?></p>
            <p class="text-xs text-rose-700 mt-1">As of today · Weekly absence rate: <?= number_format((float)$dashboardSummary['absence_rate_week'], 2) ?>%</p>
        </article>
    </div>
</section>

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

<section class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-slate-800"><?= htmlspecialchars((string)$plantillaChart['title'], ENT_QUOTES, 'UTF-8') ?></h2>
            <a href="report-analytics.php" class="text-sm text-emerald-700 hover:underline">Open plantilla</a>
        </div>
        <p class="text-xs text-slate-500 mb-3"><?= htmlspecialchars((string)$plantillaChart['subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
        <div class="h-56 mb-4">
            <canvas
                data-chart-type="doughnut"
                data-chart-label="Plantilla"
                data-chart-labels='<?= htmlspecialchars((string)json_encode($plantillaChart['labels'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>'
                data-chart-values='<?= htmlspecialchars((string)json_encode($plantillaChart['values'], JSON_NUMERIC_CHECK), ENT_QUOTES, 'UTF-8') ?>'
                data-chart-colors='["#0f172a", "#10b981"]'
            ></canvas>
        </div>
        <div class="space-y-2 text-sm">
            <p class="flex justify-between"><span>Approved Positions</span><span class="font-semibold text-slate-800"><?= (int)$dashboardSummary['approved_positions'] ?></span></p>
            <p class="flex justify-between"><span>Filled Positions</span><span class="font-semibold text-slate-800"><?= (int)$dashboardSummary['filled_positions'] ?></span></p>
            <p class="flex justify-between"><span>Vacant Positions</span><span class="font-semibold text-slate-800"><?= (int)$dashboardSummary['vacant_positions'] ?></span></p>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-slate-800">Employees per Division</h2>
            <a href="report-analytics.php" class="text-sm text-emerald-700 hover:underline">View full report</a>
        </div>

        <div class="mb-3">
            <label class="text-sm text-slate-600" for="dashboardDepartmentSearch">Search Division</label>
            <input id="dashboardDepartmentSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search division name">
        </div>

        <div class="overflow-x-auto">
            <table id="dashboardDepartmentTable" class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Division</th>
                        <th class="text-left px-4 py-3">Headcount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($departmentRows)): ?>
                        <tr><td class="px-4 py-3 text-slate-500" colspan="2">No division headcount records available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($departmentRows as $row): ?>
                            <tr data-dashboard-department-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>">
                                <td class="px-4 py-3"><?= htmlspecialchars((string)$row['department'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 font-semibold text-slate-800"><?= (int)$row['headcount'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3 flex items-center justify-between gap-3 text-sm text-slate-600">
            <p id="dashboardDepartmentPaginationMeta">Showing 0 to 0 of 0 entries</p>
            <div class="inline-flex items-center gap-2">
                <button id="dashboardDepartmentPrevPage" type="button" class="px-3 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                <button id="dashboardDepartmentNextPage" type="button" class="px-3 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
            </div>
        </div>
    </div>
</section>

<section class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <div class="flex items-start justify-between mb-4 gap-4">
            <div>
                <h2 class="font-semibold text-slate-800"><?= htmlspecialchars((string)$attendanceStatusChart['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)$attendanceStatusChart['subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <span class="text-xs text-slate-500">Updated <?= htmlspecialchars((string)$attendanceStatusChart['updated_at'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="h-72">
            <canvas
                data-chart-type="doughnut"
                data-chart-label="Attendance"
                data-chart-labels='<?= htmlspecialchars((string)json_encode($attendanceStatusChart['labels'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>'
                data-chart-values='<?= htmlspecialchars((string)json_encode($attendanceStatusChart['values'], JSON_NUMERIC_CHECK), ENT_QUOTES, 'UTF-8') ?>'
                data-chart-colors='["#10b981", "#f59e0b", "#ef4444", "#3b82f6", "#8b5cf6", "#64748b"]'
            ></canvas>
        </div>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <div class="flex items-start justify-between mb-4 gap-4">
            <div>
                <h2 class="font-semibold text-slate-800\"><?= htmlspecialchars((string)$pipelineChart['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="text-xs text-slate-500 mt-1\"><?= htmlspecialchars((string)$pipelineChart['subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <span class="text-xs text-slate-500">Updated <?= htmlspecialchars((string)$pipelineChart['updated_at'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="h-72">
            <canvas
                data-chart-type="bar"
                data-chart-label="Applicants"
                data-chart-labels='<?= htmlspecialchars((string)json_encode($pipelineChart['labels'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>'
                data-chart-values='<?= htmlspecialchars((string)json_encode($pipelineChart['values'], JSON_NUMERIC_CHECK), ENT_QUOTES, 'UTF-8') ?>'
                data-chart-colors='["#0f172a", "#10b981", "#3b82f6", "#f59e0b"]'
            ></canvas>
        </div>
    </div>
</section>

