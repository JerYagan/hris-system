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
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Report and Analytics</h1>
        <p class="text-sm text-slate-300 mt-2">View workforce insights, review attendance and payroll summaries, and queue report exports.</p>
    </div>
</div>

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
            <p class="text-xs uppercase text-slate-500">Top Department Count</p>
            <p class="font-semibold text-slate-800 mt-2"><?= htmlspecialchars($topDepartmentLabel, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Highest concentration by organizational unit.</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">New Hires (Last 30 Days)</p>
            <p class="font-semibold text-slate-800 mt-2"><?= htmlspecialchars((string)$newHiresLast30Days, ENT_QUOTES, 'UTF-8') ?> New Employee Record<?= $newHiresLast30Days === 1 ? '' : 's' ?></p>
            <p class="text-xs text-slate-500 mt-1">Based on `hire_date` in current records.</p>
        </article>
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
                <tr>
                    <td class="px-4 py-3">Late Incidents</td>
                    <td class="px-4 py-3"><?= htmlspecialchars((string)$attendanceCurrent['late'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars((string)$attendancePrevious['late'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3"><span class="<?= htmlspecialchars($percentDeltaClass((float)(-$lateVariance)), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(($lateVariance >= 0 ? '+' : '') . (string)$lateVariance, ENT_QUOTES, 'UTF-8') ?></span></td>
                </tr>
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

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Export Reports</h2>
        <p class="text-sm text-slate-500 mt-1">Generate and download report files while logging export actions for audit.</p>
    </header>

    <form action="report-analytics.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <input type="hidden" name="form_action" value="export_report">
        <div>
            <label class="text-slate-600">Report Type</label>
            <select name="report_type" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                <option value="attendance">Attendance</option>
                <option value="payroll">Payroll</option>
                <option value="performance">Performance</option>
                <option value="documents">Documents</option>
                <option value="recruitment">Recruitment</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Coverage</label>
            <select name="coverage" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                <option value="current_cutoff">Current Cutoff</option>
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="custom_range">Custom Range</option>
            </select>
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
            <label class="text-slate-600">Department Filter</label>
            <select name="department_filter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="all">All Departments</option>
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
