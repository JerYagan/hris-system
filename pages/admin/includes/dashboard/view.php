<section class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <div class="flex items-start justify-between gap-4 mb-4">
        <div>
            <h2 class="font-semibold text-slate-800">Announcement Broadcasts</h2>
            <p class="text-sm text-slate-500 mt-1">Dashboard figures mirror published announcement activity from Create Announcement.</p>
        </div>
        <a href="create-announcement.php" class="text-sm text-emerald-700 hover:underline">Open module</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase tracking-wide text-slate-600">Published Broadcasts</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['published_announcements'] ?></p>
            <p class="text-xs text-slate-500 mt-1">Recent publish actions recorded</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase tracking-wide text-emerald-700">In-App Delivered</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['announcement_in_app_delivered'] ?></p>
            <p class="text-xs text-slate-500 mt-1">Notification rows inserted from published broadcasts</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-blue-50">
            <p class="text-xs uppercase tracking-wide text-blue-700">Email Delivered</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$dashboardSummary['announcement_email_delivered'] ?></p>
            <p class="text-xs text-slate-500 mt-1">SMTP sends reported as successful</p>
        </article>
    </div>

    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Latest Published Announcement</p>
                <h3 class="text-lg font-semibold text-slate-800 mt-1"><?= htmlspecialchars((string)$dashboardSummary['latest_announcement_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)$dashboardSummary['latest_announcement_timestamp'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-200 px-3 py-1 text-xs font-medium text-slate-700"><?= htmlspecialchars((string)$dashboardSummary['latest_announcement_channel'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <p class="mt-3 text-sm leading-6 text-slate-700"><?= htmlspecialchars((string)($dashboardSummary['latest_announcement_body'] !== '' ? $dashboardSummary['latest_announcement_body'] : 'Open Create Announcement to publish and populate this summary.'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="mt-3 text-xs text-slate-500">Targets reached in the latest publish: <?= (int)$dashboardSummary['latest_announcement_targets'] ?></p>
    </div>

    <p class="mt-4 text-xs text-slate-500">Drafting, publishing, delivery counts, and latest-broadcast summaries now come from the same published announcement activity source.</p>
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

        <form id="dashboardDepartmentFilters" class="mb-3" autocomplete="off">
            <input id="dashboardDepartmentPage" type="hidden" name="department_page" value="<?= (int)($dashboardDepartmentPagination['page'] ?? 1) ?>">
            <label class="text-sm text-slate-600" for="dashboardDepartmentSearch">Search Division</label>
            <input id="dashboardDepartmentSearch" name="department_search" type="search" value="<?= htmlspecialchars((string)($dashboardDepartmentPagination['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search division name">
        </form>

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
                        <tr><td class="px-4 py-3 text-slate-500" colspan="2"><?= htmlspecialchars((string)(($dashboardDepartmentPagination['search'] ?? '') !== '' ? 'No division headcount records matched the current search.' : 'No division headcount records available.'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($departmentRows as $row): ?>
                            <tr>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)$row['department'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 font-semibold text-slate-800"><?= (int)$row['headcount'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3 flex items-center justify-between gap-3 text-sm text-slate-600">
            <p id="dashboardDepartmentPaginationMeta">Showing <?= (int)($dashboardDepartmentPagination['from'] ?? 0) ?> to <?= (int)($dashboardDepartmentPagination['to'] ?? 0) ?> of <?= (int)($dashboardDepartmentPagination['total_rows'] ?? 0) ?> entries</p>
            <div class="inline-flex items-center gap-2">
                <button id="dashboardDepartmentPrevPage" type="button" data-dashboard-secondary-page="<?= (int)($dashboardDepartmentPagination['prev_page'] ?? 1) ?>" class="px-3 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed" <?= !empty($dashboardDepartmentPagination['has_prev']) ? '' : 'disabled' ?>>Previous</button>
                <button id="dashboardDepartmentNextPage" type="button" data-dashboard-secondary-page="<?= (int)($dashboardDepartmentPagination['next_page'] ?? 1) ?>" class="px-3 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed" <?= !empty($dashboardDepartmentPagination['has_next']) ? '' : 'disabled' ?>>Next</button>
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
                <h2 class="font-semibold text-slate-800"><?= htmlspecialchars((string)$pipelineChart['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)$pipelineChart['subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
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

