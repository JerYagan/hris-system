<?php
$percentDeltaClass = static function (float $value): string {
    if ($value > 0) {
        return 'text-emerald-700';
    }
    if ($value < 0) {
        return 'text-rose-700';
    }
    return 'text-slate-600';
};

$money = static function (float $amount): string {
    return '₱' . number_format($amount, 2);
};

$attendanceVariance = round($attendanceComplianceCurrent - $attendanceCompliancePrevious, 1);
$lateVariance = (int)$attendanceCurrent['late'] - (int)$attendancePrevious['late'];
$grossVariance = (float)$payrollCurrent['gross'] - (float)$payrollPrevious['gross'];
$netVariance = (float)$payrollCurrent['net'] - (float)$payrollPrevious['net'];

$employeeStatusPill = static function (string $status): string {
    $normalized = strtolower(trim($status));
    if ($normalized === 'active') {
        return 'bg-emerald-100 text-emerald-800';
    }
    if ($normalized === 'on leave') {
        return 'bg-amber-100 text-amber-800';
    }
    if (in_array($normalized, ['inactive', 'terminated', 'resigned', 'retired'], true)) {
        return 'bg-rose-100 text-rose-800';
    }

    return 'bg-slate-100 text-slate-700';
};
?>

<?php if ($state && $message): ?>
    <?php
    $alertClass = $state === 'success'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
        : 'border-red-200 bg-red-50 text-red-700';
    $icon = $state === 'success' ? 'check_circle' : 'error';
    ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm flex gap-2 <?= htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8') ?>">
        <span class="material-symbols-outlined text-base"><?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?></span>
        <span><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm flex gap-2">
        <span class="material-symbols-outlined text-base">error</span>
        <span><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($reportAnalyticsChartPayloadJson)): ?>
    <script id="reportAnalyticsChartPayload" type="application/json"><?= $reportAnalyticsChartPayloadJson ?></script>
<?php endif; ?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Employee Statistics Dashboard</h2>
        <p class="text-sm text-slate-500 mt-1">High-level workforce metrics based on current employment records.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Employees</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$totalEmployees, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Current employment records</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Active</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$activeCount, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Employment status: active</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase tracking-wide text-amber-700">On Leave</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$onLeaveCount, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Employment status: on_leave</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-rose-50">
            <p class="text-xs uppercase tracking-wide text-rose-700">Inactive</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$inactiveCount, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Non-active status records</p>
        </article>
    </div>

    <div class="px-6 pb-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">Top Division Count</p>
            <p class="font-semibold text-slate-800 mt-2"><?= htmlspecialchars($topDepartmentLabel, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Highest concentration by organizational unit.</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">New Hires (Last 30 Days)</p>
            <p class="font-semibold text-slate-800 mt-2"><?= htmlspecialchars((string)$newHiresLast30Days, ENT_QUOTES, 'UTF-8') ?> New Employee Record<?= $newHiresLast30Days === 1 ? '' : 's' ?></p>
            <p class="text-xs text-slate-500 mt-1">Based on `hire_date` in current records.</p>
        </article>
    </div>

    <div class="px-6 pb-6 grid grid-cols-1 xl:grid-cols-2 gap-4">
        <article class="rounded-xl border border-slate-200 p-4">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Workforce Status Mix</h3>
                    <p class="text-xs text-slate-500 mt-1">Current active, leave, and inactive employee distribution.</p>
                </div>
            </div>
            <div class="relative h-72">
                <canvas id="reportEmployeeStatusChart"></canvas>
            </div>
        </article>
        <article class="rounded-xl border border-slate-200 p-4">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Largest Divisions</h3>
                    <p class="text-xs text-slate-500 mt-1">Top headcount concentrations based on current employment records.</p>
                </div>
            </div>
            <div class="relative h-72">
                <canvas id="reportDivisionHeadcountChart"></canvas>
            </div>
        </article>
    </div>

    <div class="px-6 pb-3 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600" for="reportEmployeesSearch">Search Employees</label>
            <input id="reportEmployeesSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by name, division, status, or employee ID">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600" for="reportEmployeesDepartmentFilter">Division</label>
            <select id="reportEmployeesDepartmentFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Divisions</option>
                <?php foreach ($employeeDepartmentFilters as $departmentName): ?>
                    <option value="<?= htmlspecialchars((string)$departmentName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$departmentName, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600" for="reportEmployeesStatusFilter">Status</label>
            <select id="reportEmployeesStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <?php foreach ($employeeStatusFilters as $statusName): ?>
                    <option value="<?= htmlspecialchars((string)$statusName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$statusName, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="p-6 pt-3 overflow-x-auto">
        <table id="reportEmployeesTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Employee ID</th>
                    <th class="text-left px-4 py-3">Division</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Hire Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($employeeRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="5">No employee records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employeeRows as $row): ?>
                        <tr
                            data-report-employee-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>"
                            data-report-employee-status="<?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>"
                            data-report-employee-department="<?= htmlspecialchars((string)$row['department'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars((string)$row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($row['person_id'] !== '' ? $row['person_id'] : '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['department'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[96px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($employeeStatusPill((string)$row['status_label']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['hire_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="reportEmployeesFilterEmpty" class="hidden">
                    <td class="px-4 py-3 text-slate-500" colspan="5">No employee records match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>

        <div id="reportEmployeesPagination" class="mt-4 flex items-center justify-between gap-3 text-sm text-slate-600">
            <p id="reportEmployeesPaginationInfo">Showing 0 to 0 of 0 entries</p>
            <div class="flex items-center gap-2">
                <button type="button" id="reportEmployeesPrev" class="px-3 py-1.5 border border-slate-300 rounded-md hover:bg-slate-50 disabled:opacity-50">Previous</button>
                <span id="reportEmployeesPageLabel">Page 1 of 1</span>
                <button type="button" id="reportEmployeesNext" class="px-3 py-1.5 border border-slate-300 rounded-md hover:bg-slate-50 disabled:opacity-50">Next</button>
            </div>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Employee Demographics by Division</h2>
        <p class="text-sm text-slate-500 mt-1">Detailed demographic distribution of active employees per division.</p>
    </header>

    <div class="px-6 pt-6">
        <article class="rounded-xl border border-slate-200 p-4">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Division Demographics Snapshot</h3>
                    <p class="text-xs text-slate-500 mt-1">Top divisions by active headcount, grouped by reported sex at birth.</p>
                </div>
            </div>
            <div class="relative h-80">
                <canvas id="reportDemographicsChart"></canvas>
            </div>
        </article>
    </div>

    <div class="px-6 pt-4">
        <label class="text-sm text-slate-600" for="reportDemographicsSearch">Search</label>
        <input id="reportDemographicsSearch" type="search" class="w-full md:w-1/2 mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search division, totals, gender counts, or average age">
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="reportDemographicsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Division</th>
                    <th class="text-left px-4 py-3">Total</th>
                    <th class="text-left px-4 py-3">Male</th>
                    <th class="text-left px-4 py-3">Female</th>
                    <th class="text-left px-4 py-3">Unspecified</th>
                    <th class="text-left px-4 py-3">Average Age</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($demographicsByDivisionRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="6">No demographic rows available.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($demographicsByDivisionRows as $row): ?>
                        <tr data-report-demographics-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['division'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['total'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['male'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['female'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['unspecified'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars(number_format((float)$row['average_age'], 1), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="reportDemographicsFilterEmpty" class="hidden">
                    <td class="px-4 py-3 text-slate-500" colspan="6">No demographic rows match your search.</td>
                </tr>
            </tbody>
        </table>

        <div id="reportDemographicsPagination" class="mt-4 flex items-center justify-between gap-3 text-sm text-slate-600">
            <p id="reportDemographicsPaginationInfo">Showing 0 to 0 of 0 entries</p>
            <div class="flex items-center gap-2">
                <button type="button" id="reportDemographicsPrev" class="px-3 py-1.5 border border-slate-300 rounded-md hover:bg-slate-50 disabled:opacity-50">Previous</button>
                <span id="reportDemographicsPageLabel">Page 1 of 1</span>
                <button type="button" id="reportDemographicsNext" class="px-3 py-1.5 border border-slate-300 rounded-md hover:bg-slate-50 disabled:opacity-50">Next</button>
            </div>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Turnover and Training Effectiveness by Division</h2>
        <p class="text-sm text-slate-500 mt-1">Division-level view of headcount movement and training completion trends.</p>
    </header>

    <div class="px-6 pt-6">
        <article class="rounded-xl border border-slate-200 p-4">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Turnover and Training Trendline</h3>
                    <p class="text-xs text-slate-500 mt-1">Hires and separations over the last 365 days, with turnover and training completion rates.</p>
                </div>
            </div>
            <div class="relative h-80">
                <canvas id="reportTurnoverTrainingChart"></canvas>
            </div>
        </article>
    </div>

    <div class="px-6 pt-4">
        <label class="text-sm text-slate-600" for="reportTurnoverSearch">Search</label>
        <input id="reportTurnoverSearch" type="search" class="w-full md:w-1/2 mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search division, headcount, turnover, or training rates">
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="reportTurnoverTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Division</th>
                    <th class="text-left px-4 py-3">Headcount</th>
                    <th class="text-left px-4 py-3">Hires (365d)</th>
                    <th class="text-left px-4 py-3">Separations (365d)</th>
                    <th class="text-left px-4 py-3">Turnover Rate</th>
                    <th class="text-left px-4 py-3">Training Completion</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($turnoverTrainingRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="6">No turnover/training rows available.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($turnoverTrainingRows as $row): ?>
                        <tr data-report-turnover-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['division'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['headcount'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['hires_365'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['separations_365'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars(number_format((float)$row['turnover_rate'], 1), ENT_QUOTES, 'UTF-8') ?>%</td>
                            <td class="px-4 py-3"><?= htmlspecialchars(number_format((float)$row['training_completion_rate'], 1), ENT_QUOTES, 'UTF-8') ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="reportTurnoverFilterEmpty" class="hidden">
                    <td class="px-4 py-3 text-slate-500" colspan="6">No turnover/training rows match your search.</td>
                </tr>
            </tbody>
        </table>

        <div id="reportTurnoverPagination" class="mt-4 flex items-center justify-between gap-3 text-sm text-slate-600">
            <p id="reportTurnoverPaginationInfo">Showing 0 to 0 of 0 entries</p>
            <div class="flex items-center gap-2">
                <button type="button" id="reportTurnoverPrev" class="px-3 py-1.5 border border-slate-300 rounded-md hover:bg-slate-50 disabled:opacity-50">Previous</button>
                <span id="reportTurnoverPageLabel">Page 1 of 1</span>
                <button type="button" id="reportTurnoverNext" class="px-3 py-1.5 border border-slate-300 rounded-md hover:bg-slate-50 disabled:opacity-50">Next</button>
            </div>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Attendance and Payroll Summary</h2>
        <p class="text-sm text-slate-500 mt-1">Rolling 30-day comparison against the previous 30-day window.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Metric</th>
                    <th class="text-left px-4 py-3">Current Window</th>
                    <th class="text-left px-4 py-3">Previous Window</th>
                    <th class="text-left px-4 py-3">Variance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Attendance Compliance</td>
                    <td class="px-4 py-3"><?= htmlspecialchars(number_format($attendanceComplianceCurrent, 1), ENT_QUOTES, 'UTF-8') ?>%</td>
                    <td class="px-4 py-3"><?= htmlspecialchars(number_format($attendanceCompliancePrevious, 1), ENT_QUOTES, 'UTF-8') ?>%</td>
                    <td class="px-4 py-3"><span class="<?= htmlspecialchars($percentDeltaClass($attendanceVariance), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(($attendanceVariance >= 0 ? '+' : '') . number_format($attendanceVariance, 1), ENT_QUOTES, 'UTF-8') ?>%</span></td>
                </tr>
                <?php if (!(bool)($noLatePolicyApproved ?? false)): ?>
                    <tr>
                        <td class="px-4 py-3">Late Incidents</td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string)$attendanceCurrent['late'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string)$attendancePrevious['late'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3"><span class="<?= htmlspecialchars($percentDeltaClass((float)(-$lateVariance)), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(($lateVariance >= 0 ? '+' : '') . (string)$lateVariance, ENT_QUOTES, 'UTF-8') ?></span></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td class="px-4 py-3">Total Gross Payroll</td>
                    <td class="px-4 py-3"><?= htmlspecialchars($money((float)$payrollCurrent['gross']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($money((float)$payrollPrevious['gross']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3"><span class="<?= htmlspecialchars($percentDeltaClass($grossVariance), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(($grossVariance >= 0 ? '+' : '') . $money($grossVariance), ENT_QUOTES, 'UTF-8') ?></span></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Total Net Payroll</td>
                    <td class="px-4 py-3"><?= htmlspecialchars($money((float)$payrollCurrent['net']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($money((float)$payrollPrevious['net']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3"><span class="<?= htmlspecialchars($percentDeltaClass($netVariance), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(($netVariance >= 0 ? '+' : '') . $money($netVariance), ENT_QUOTES, 'UTF-8') ?></span></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Cross-Module KPI Snapshot</h2>
        <p class="text-sm text-slate-500 mt-1">Administrative visibility across attendance, payroll, recruitment, documents, performance, and audit logs.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase tracking-wide text-slate-500">Attendance Logs (60 Days)</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)($crossModuleKpis['attendance_logs'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Payroll Items (60 Days)</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)($crossModuleKpis['payroll_items'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-indigo-50">
            <p class="text-xs uppercase tracking-wide text-indigo-700">Recruitment (Hired)</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)($crossModuleKpis['recruitment_hired'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Submitted: <?= htmlspecialchars((string)($crossModuleKpis['recruitment_submitted'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase tracking-wide text-amber-700">Documents Pending</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)($crossModuleKpis['documents_pending'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Total docs: <?= htmlspecialchars((string)($crossModuleKpis['documents_total'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-violet-50">
            <p class="text-xs uppercase tracking-wide text-violet-700">Performance Completed</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)($crossModuleKpis['performance_completed'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-rose-50">
            <p class="text-xs uppercase tracking-wide text-rose-700">Audit Logs (30 Days)</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)($crossModuleKpis['audit_logs_30_days'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Advanced Policy-Based Analytics</h2>
        <p class="text-sm text-slate-500 mt-1">Admin-focused metrics for demographics, turnover, training effectiveness, and role-based system activity.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-sky-50">
            <p class="text-xs uppercase tracking-wide text-sky-700">Employee Demographics</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)(($advancedAdminAnalytics['demographics_male'] ?? 0) + ($advancedAdminAnalytics['demographics_female'] ?? 0) + ($advancedAdminAnalytics['demographics_unspecified'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Male: <?= htmlspecialchars((string)($advancedAdminAnalytics['demographics_male'] ?? 0), ENT_QUOTES, 'UTF-8') ?> · Female: <?= htmlspecialchars((string)($advancedAdminAnalytics['demographics_female'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-rose-50">
            <p class="text-xs uppercase tracking-wide text-rose-700">Turnover Rate (12 Months)</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars(number_format((float)($advancedAdminAnalytics['turnover_rate_annual'] ?? 0), 1), ENT_QUOTES, 'UTF-8') ?>%</p>
            <p class="text-xs text-slate-500 mt-1">Separations: <?= htmlspecialchars((string)($advancedAdminAnalytics['separations_annual'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Training Effectiveness</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars(number_format((float)($advancedAdminAnalytics['training_completion_rate'] ?? 0), 1), ENT_QUOTES, 'UTF-8') ?>%</p>
            <p class="text-xs text-slate-500 mt-1">Completed: <?= htmlspecialchars((string)($advancedAdminAnalytics['training_completed'] ?? 0), ENT_QUOTES, 'UTF-8') ?> · Failed: <?= htmlspecialchars((string)($advancedAdminAnalytics['training_failed'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-violet-50">
            <p class="text-xs uppercase tracking-wide text-violet-700">Admin/Staff Activity (30 Days)</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)(($advancedAdminAnalytics['admin_activity_30_days'] ?? 0) + ($advancedAdminAnalytics['staff_activity_30_days'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Admin: <?= htmlspecialchars((string)($advancedAdminAnalytics['admin_activity_30_days'] ?? 0), ENT_QUOTES, 'UTF-8') ?> · Staff: <?= htmlspecialchars((string)($advancedAdminAnalytics['staff_activity_30_days'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
    </div>

    <div class="px-6 pb-6">
        <article class="rounded-xl border border-slate-200 p-4">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Module Activity Mix</h3>
                    <p class="text-xs text-slate-500 mt-1">Top recent modules by admin and staff activity over the last 30 days.</p>
                </div>
            </div>
            <div class="relative h-80">
                <canvas id="reportActivityByModuleChart"></canvas>
            </div>
        </article>
    </div>

    <div class="px-6 pb-6 overflow-x-auto">
        <div class="pb-4 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
            <div class="md:col-span-2">
                <label class="text-sm text-slate-600" for="reportActivitiesSearch">Search Activity</label>
                <input id="reportActivitiesSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Search date, module, action, role, or actor email">
            </div>
            <div>
                <label class="text-sm text-slate-600" for="reportActivitiesRoleFilter">Role</label>
                <select id="reportActivitiesRoleFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                    <option value="">All Roles</option>
                    <?php foreach ($activityRoleFilters as $roleName): ?>
                        <option value="<?= htmlspecialchars((string)$roleName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$roleName, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-sm text-slate-600" for="reportActivitiesModuleFilter">Module</label>
                <select id="reportActivitiesModuleFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                    <option value="">All Modules</option>
                    <?php foreach ($activityModuleFilters as $moduleName): ?>
                        <option value="<?= htmlspecialchars((string)$moduleName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$moduleName, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <table id="reportActivitiesTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Timestamp</th>
                    <th class="text-left px-4 py-3">Module</th>
                    <th class="text-left px-4 py-3">Action</th>
                    <th class="text-left px-4 py-3">Role</th>
                    <th class="text-left px-4 py-3">Actor</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($activityLogRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="5">No activity rows available for the selected policy window.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activityLogRows as $activityRow): ?>
                        <tr
                            data-report-activities-search="<?= htmlspecialchars((string)$activityRow['search_text'], ENT_QUOTES, 'UTF-8') ?>"
                            data-report-activities-role="<?= htmlspecialchars((string)$activityRow['role_label'], ENT_QUOTES, 'UTF-8') ?>"
                            data-report-activities-module="<?= htmlspecialchars((string)$activityRow['module_label'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($activityRow['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($activityRow['module_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($activityRow['action_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($activityRow['role_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($activityRow['actor_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="reportActivitiesFilterEmpty" class="hidden">
                    <td class="px-4 py-3 text-slate-500" colspan="5">No activity rows match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>

        <div id="reportActivitiesPagination" class="mt-4 flex items-center justify-between gap-3 text-sm text-slate-600">
            <p id="reportActivitiesPaginationInfo">Showing 0 to 0 of 0 entries</p>
            <div class="flex items-center gap-2">
                <button type="button" id="reportActivitiesPrev" class="px-3 py-1.5 border border-slate-300 rounded-md hover:bg-slate-50 disabled:opacity-50">Previous</button>
                <span id="reportActivitiesPageLabel">Page 1 of 1</span>
                <button type="button" id="reportActivitiesNext" class="px-3 py-1.5 border border-slate-300 rounded-md hover:bg-slate-50 disabled:opacity-50">Next</button>
            </div>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Export Reports</h2>
        <p class="text-sm text-slate-500 mt-1">Generate and download report files while logging export actions for audit.</p>
    </header>

    <form id="reportExportForm" action="report-analytics.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <input type="hidden" name="form_action" value="export_report">
        <div>
            <label class="text-slate-600">Report Type</label>
            <select name="report_type" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                <option value="attendance">Attendance</option>
                <option value="payroll">Payroll</option>
                <option value="performance">Performance</option>
                <option value="documents">Documents</option>
                <option value="recruitment">Recruitment</option>
                <option value="audit_logs">Audit Logs</option>
                <option value="employee_demographics">Employee Demographics</option>
                <option value="turnover_rates">Turnover Rates</option>
                <option value="training_effectiveness">Training Effectiveness</option>
                <option value="activity_summary">Admin and Staff Activity Summary</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Coverage</label>
            <select id="reportCoverageSelect" name="coverage" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                <option value="current_cutoff">Current Cutoff</option>
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="custom_range">Custom Range</option>
            </select>
        </div>
        <div id="reportCustomDateRange" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
            <div>
                <label class="text-slate-600">Start Date</label>
                <input id="reportCustomStartDate" type="date" name="custom_start_date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label class="text-slate-600">End Date</label>
                <input id="reportCustomEndDate" type="date" name="custom_end_date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
            </div>
        </div>
        <div>
            <label class="text-slate-600">Format</label>
            <select name="file_format" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                <option value="pdf">PDF</option>
                <option value="xlsx">Excel (.xlsx)</option>
                <option value="csv">CSV</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Division Filter</label>
            <select name="department_filter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="all">All Divisions</option>
                <?php foreach ($departmentsForFilter as $departmentName): ?>
                    <option value="<?= htmlspecialchars((string)$departmentName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$departmentName, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="md:col-span-4 flex justify-end gap-3 mt-2">
            <button type="reset" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Export & Download</button>
        </div>
    </form>
</section>
