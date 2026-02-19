<?php
require_once __DIR__ . '/includes/reports/bootstrap.php';
require_once __DIR__ . '/includes/reports/actions.php';
require_once __DIR__ . '/includes/reports/data.php';

$pageTitle = 'Reports | Staff';
$activePage = 'reports.php';
$breadcrumbs = ['Reports'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Reports and Exports</h1>
    <p class="text-sm text-gray-500">Generate office-scoped HR analytics and export attendance, payroll, performance, document, and recruitment reports.</p>
</div>

<?php if ($state && $message): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

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
            <p class="text-xs text-slate-500 mt-1">Based on hire_date in current records.</p>
        </article>
    </div>

    <div class="px-6 pb-3 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600" for="staffReportEmployeesSearch">Search Employees</label>
            <input id="staffReportEmployeesSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by name, department, status, or employee ID">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600" for="staffReportEmployeesDepartmentFilter">Department</label>
            <select id="staffReportEmployeesDepartmentFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Departments</option>
                <?php foreach ($employeeDepartmentFilters as $departmentName): ?>
                    <option value="<?= htmlspecialchars((string)$departmentName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$departmentName, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600" for="staffReportEmployeesStatusFilter">Status</label>
            <select id="staffReportEmployeesStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <?php foreach ($employeeStatusFilters as $statusName): ?>
                    <option value="<?= htmlspecialchars((string)$statusName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$statusName, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="p-6 pt-3 overflow-x-auto">
        <table id="staffReportEmployeesTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Employee ID</th>
                    <th class="text-left px-4 py-3">Department</th>
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
                        <tr data-staff-report-row data-staff-report-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>" data-staff-report-status="<?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>" data-staff-report-department="<?= htmlspecialchars((string)$row['department'], ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars((string)$row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['department'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[96px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($employeeStatusPill((string)$row['status_label']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['hire_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="staffReportEmployeesFilterEmpty" class="hidden">
                    <td class="px-4 py-3 text-slate-500" colspan="5">No employee records match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
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
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Export Reports</h2>
            <p class="text-sm text-gray-500 mt-1">Generate and download scoped report files with export activity logs.</p>
        </div>
    </header>

    <form id="staffReportExportForm" action="reports.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <input type="hidden" name="form_action" value="export_report">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <div>
            <label class="text-gray-600">Report Type</label>
            <select name="report_type" class="w-full mt-1 border rounded-md px-3 py-2" required>
                <option value="attendance">Attendance</option>
                <option value="payroll">Payroll</option>
                <option value="performance">Performance</option>
                <option value="documents">Documents</option>
                <option value="recruitment">Recruitment</option>
            </select>
        </div>
        <div>
            <label class="text-gray-600">Coverage</label>
            <select id="staffReportCoverageSelect" name="coverage" class="w-full mt-1 border rounded-md px-3 py-2" required>
                <option value="current_cutoff">Current Cutoff</option>
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="custom_range">Custom Range</option>
            </select>
        </div>
        <div id="staffReportCustomDateRange" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
            <div>
                <label class="text-gray-600">Date From</label>
                <input id="staffReportStartDate" type="date" name="custom_start_date" class="w-full mt-1 border rounded-md px-3 py-2">
            </div>
            <div>
                <label class="text-gray-600">Date To</label>
                <input id="staffReportEndDate" type="date" name="custom_end_date" class="w-full mt-1 border rounded-md px-3 py-2">
            </div>
        </div>
        <div>
            <label class="text-gray-600">Format</label>
            <select name="file_format" class="w-full mt-1 border rounded-md px-3 py-2" required>
                <option value="pdf">PDF</option>
                <option value="xlsx">Excel (.xlsx)</option>
                <option value="csv">CSV</option>
            </select>
        </div>
        <div>
            <label class="text-gray-600">Department Filter</label>
            <select name="department_filter" class="w-full mt-1 border rounded-md px-3 py-2">
                <option value="all">All Departments</option>
                <?php foreach ($departmentsForFilter as $departmentName): ?>
                    <option value="<?= htmlspecialchars((string)$departmentName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$departmentName, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="md:col-span-4 flex flex-wrap gap-2 justify-end">
            <button type="reset" class="px-4 py-2 border rounded-md hover:bg-gray-50">Reset</button>
            <button id="staffReportExportSubmit" type="submit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Generate Export</button>
        </div>
    </form>
</section>

<script src="../../assets/js/staff/reports/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
