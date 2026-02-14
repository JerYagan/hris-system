<?php
$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    if ($key === 'active') {
        return ['Active', 'bg-emerald-100 text-emerald-800'];
    }
    return ['Inactive', 'bg-amber-100 text-amber-800'];
};
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Personal Information</h1>
        <p class="text-sm text-slate-300 mt-2">Manage employee records, profile lifecycle, assignments, and status updates.</p>
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
        <h2 class="text-lg font-semibold text-slate-800">Search Employee with Filter</h2>
        <p class="text-sm text-slate-500 mt-1">Locate records quickly using name, department, and status filters.</p>
    </header>

    <form action="personal-information.php" method="GET" class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div class="md:col-span-2">
            <label class="text-slate-600">Keyword</label>
            <input name="keyword" type="search" value="<?= htmlspecialchars($filterKeywordInput, ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Search by employee ID or name">
        </div>
        <div>
            <label class="text-slate-600">Department</label>
            <select name="department" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="">All Departments</option>
                <?php foreach ($departmentFilterOptions as $departmentName): ?>
                    <option value="<?= htmlspecialchars((string)$departmentName, ENT_QUOTES, 'UTF-8') ?>" <?= strcasecmp((string)$departmentName, $filterDepartment) === 0 ? 'selected' : '' ?>><?= htmlspecialchars((string)$departmentName, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Status</label>
            <select name="status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="">All Statuses</option>
                <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="md:col-span-4 flex justify-end gap-3 mt-1">
            <a href="personal-information.php" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50 text-center">Clear</a>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Search</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Personal Information</h2>
        <p class="text-sm text-slate-500 mt-1">Maintain complete and updated personal details for all employee profiles.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Profiles</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$totalProfiles, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-600 mt-1">Across all departments</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Complete Records</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$completeRecords, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-600 mt-1">Profiles with contact and assignment data</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase tracking-wide text-amber-700">Needs Update</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$needsUpdateCount, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-600 mt-1">Missing key profile details</p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Employee Profiles</h2>
        <p class="text-sm text-slate-500 mt-1">Current status distribution for managed employee records.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">Active Employees</p>
            <p class="text-xl font-semibold text-slate-800 mt-2"><?= htmlspecialchars((string)$activeEmployees, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Profiles currently tagged as active</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">Inactive Employees</p>
            <p class="text-xl font-semibold text-slate-800 mt-2"><?= htmlspecialchars((string)$inactiveEmployees, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Resigned/retired/other inactive statuses</p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">View All Employee Records</h2>
        <p class="text-sm text-slate-500 mt-1">Review all employee profiles with position, department, and current status.</p>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Records</label>
            <input id="personalInfoRecordsSearchInput" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by employee ID, name, department, or position">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Department Filter</label>
            <select id="personalInfoRecordsDepartmentFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Departments</option>
                <?php foreach ($departmentFilterOptions as $departmentName): ?>
                    <option value="<?= htmlspecialchars((string)$departmentName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$departmentName, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="personalInfoRecordsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="personalInfoEmployeesTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee ID</th>
                    <th class="text-left px-4 py-3">Full Name</th>
                    <th class="text-left px-4 py-3">Department</th>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($employeeTableRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="6">No employee records found<?= ($filterKeyword !== '' || $filterDepartment !== '' || $filterStatus !== '') ? ' for current filters' : '' ?>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employeeTableRows as $row): ?>
                        <?php [$statusLabel, $statusClass] = $statusPill((string)$row['status_raw']); ?>
                        <tr data-profile-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>" data-profile-department="<?= htmlspecialchars((string)$row['department'], ENT_QUOTES, 'UTF-8') ?>" data-profile-status="<?= htmlspecialchars((string)$statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['employee_code'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['department'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['position'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[90px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><button type="button" data-person-profile-open data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>" class="px-3 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Open</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Profile Actions</h2>
        <p class="text-sm text-slate-500 mt-1">Open modal actions for profile management, assignment, and status updates.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50 flex flex-col gap-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Add / Edit / Archive Profile</h3>
                <p class="text-xs text-slate-500 mt-1">Create, update, or archive employee profile records.</p>
            </div>
            <button type="button" data-modal-open="personalInfoProfileModal" class="mt-auto px-4 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Open Action</button>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50 flex flex-col gap-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Assign Department and Position</h3>
                <p class="text-xs text-slate-500 mt-1">Update office and position assignments for an employee.</p>
            </div>
            <button type="button" data-modal-open="personalInfoAssignmentModal" class="mt-auto px-4 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Open Action</button>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50 flex flex-col gap-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Manage Employee Status</h3>
                <p class="text-xs text-slate-500 mt-1">Activate or deactivate employee employment status with remarks.</p>
            </div>
            <button type="button" data-modal-open="personalInfoStatusModal" class="mt-auto px-4 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Open Action</button>
        </article>
    </div>
</section>

<div id="personalInfoProfileModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoProfileModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Add / Edit / Archive Employee Profile</h3>
                <button type="button" data-modal-close="personalInfoProfileModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="personal-information.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="save_profile">
                <div>
                    <label class="text-slate-600">Employee Name</label>
                    <input id="profileEmployeeName" name="employee_name" type="text" list="profileEmployeeList" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter full name" required>
                    <input name="person_id" type="hidden" id="profilePersonId" value="">
                    <datalist id="profileEmployeeList">
                        <?php foreach ($employeesForSelect as $employee): ?>
                            <option value="<?= htmlspecialchars((string)$employee['name'], ENT_QUOTES, 'UTF-8') ?>" data-person-id="<?= htmlspecialchars((string)$employee['person_id'], ENT_QUOTES, 'UTF-8') ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div>
                    <label class="text-slate-600">Action</label>
                    <select name="profile_action" id="profileAction" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="add">Add Profile</option>
                        <option value="edit">Edit Profile</option>
                        <option value="archive">Archive Profile</option>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Email Address</label>
                    <input name="email" type="email" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter email">
                </div>
                <div>
                    <label class="text-slate-600">Contact Number</label>
                    <input name="mobile_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter mobile number">
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Profile Notes</label>
                    <textarea name="profile_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add profile changes, archive reason, or admin notes"></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="personalInfoProfileModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="personalInfoAssignmentModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoAssignmentModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Assign Department and Position</h3>
                <button type="button" data-modal-close="personalInfoAssignmentModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="personal-information.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <input type="hidden" name="form_action" value="assign_department_position">
                <div>
                    <label class="text-slate-600">Employee</label>
                    <select name="person_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="">Select employee</option>
                        <?php foreach ($employeesForSelect as $employee): ?>
                            <option value="<?= htmlspecialchars((string)$employee['person_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$employee['name'] . ' (' . (string)$employee['employee_code'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Department</label>
                    <select name="office_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="">Select department</option>
                        <?php foreach ($officeRows as $office): ?>
                            <option value="<?= htmlspecialchars((string)($office['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($office['office_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Position</label>
                    <select name="position_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="">Select position</option>
                        <?php foreach ($positionRows as $position): ?>
                            <option value="<?= htmlspecialchars((string)($position['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($position['position_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-3 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="personalInfoAssignmentModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="personalInfoStatusModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoStatusModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Manage Employee Status</h3>
                <button type="button" data-modal-close="personalInfoStatusModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="personal-information.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="update_employee_status">
                <div>
                    <label class="text-slate-600">Employee</label>
                    <select name="person_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="">Select employee</option>
                        <?php foreach ($employeesForSelect as $employee): ?>
                            <option value="<?= htmlspecialchars((string)$employee['person_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$employee['name'] . ' (' . (string)$employee['employee_code'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">New Status</label>
                    <select name="new_status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Status Specification</label>
                    <textarea name="status_specification" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Indicate reason (resigned, retired, on leave, reassigned, etc.)"></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="personalInfoStatusModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
