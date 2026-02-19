<?php
$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    if ($key === 'active') {
        return ['Active', 'bg-emerald-100 text-emerald-800'];
    }
    return ['Inactive', 'bg-amber-100 text-amber-800'];
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

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Employee Overview</h2>
        <p class="text-sm text-slate-500 mt-1">Quick summary of employee records, completion, and active status.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-5 gap-4 text-sm">
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
        <h2 class="text-lg font-semibold text-slate-800">Create Staff Account</h2>
        <p class="text-sm text-slate-500 mt-1">Create login credentials for an existing employee profile and assign a staff role.</p>
    </header>

    <form action="personal-information.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <input type="hidden" name="form_action" value="create_staff_account">

        <div class="md:col-span-3">
            <label class="text-slate-600">Employee Profile</label>
            <select name="person_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                <option value="">Select employee profile</option>
                <?php foreach ($staffAccountCandidates as $candidate): ?>
                    <option value="<?= htmlspecialchars((string)$candidate['person_id'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string)$candidate['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)$candidate['employee_code'], ENT_QUOTES, 'UTF-8') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($staffAccountCandidates)): ?>
                <p class="mt-2 text-xs text-amber-700">All current employee profiles already have linked user accounts.</p>
            <?php endif; ?>
        </div>

        <div>
            <label class="text-slate-600">Login Email</label>
            <input name="email" type="email" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="staff@agency.gov.ph" required>
        </div>
        <div>
            <label class="text-slate-600">Password</label>
            <input name="password" type="password" minlength="8" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Minimum 8 characters" required>
        </div>
        <div>
            <label class="text-slate-600">Staff Role</label>
            <select name="role_key" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                <option value="staff" selected>Staff</option>
                <option value="hr_officer">HR Officer</option>
                <option value="supervisor">Supervisor</option>
            </select>
        </div>

        <div>
            <label class="text-slate-600">Office Scope</label>
            <select name="office_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                <option value="">Select office</option>
                <?php foreach ($officeRows as $office): ?>
                    <option value="<?= htmlspecialchars((string)($office['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string)($office['office_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Notes (Optional)</label>
            <input name="account_notes" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Reason or remarks for account provisioning">
        </div>

        <div class="md:col-span-3 flex justify-end">
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Create Staff Account</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Profile Actions</h2>
        <p class="text-sm text-slate-500 mt-1">Manage profile updates and quickly filter employee records before selecting an action.</p>
    </header>

    <div class="p-6 space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
            <button type="button" data-person-profile-add class="px-4 py-2.5 rounded-md bg-slate-900 text-white hover:bg-slate-800">Add Employee</button>
            <a href="personal-information.php" class="px-4 py-2.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 text-center">Reset Filters</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
            <div class="md:col-span-2">
                <label class="text-slate-600">Search Employee Records</label>
                <input id="personalInfoRecordsSearchInput" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Search by employee ID, name, email, department, or position">
            </div>
            <div>
                <label class="text-slate-600">Department Filter</label>
                <select id="personalInfoRecordsDepartmentFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                    <option value="">All Departments</option>
                    <?php foreach ($departmentFilterOptions as $departmentName): ?>
                        <option value="<?= htmlspecialchars((string)$departmentName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$departmentName, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-slate-600">Status Filter</label>
                <select id="personalInfoRecordsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                    <option value="">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Employee Management Table</h2>
        <p class="text-sm text-slate-500 mt-1">Search and filter employee records, then select an action from the dropdown.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table id="personalInfoEmployeesTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee ID</th>
                    <th class="text-left px-4 py-3">Full Name</th>
                    <th class="text-left px-4 py-3">Email</th>
                    <th class="text-left px-4 py-3">Department</th>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($employeeTableRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="7">No employee records found<?= ($filterKeyword !== '' || $filterDepartment !== '' || $filterStatus !== '') ? ' for current filters' : '' ?>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employeeTableRows as $row): ?>
                        <?php [$statusLabel, $statusClass] = $statusPill((string)$row['status_raw']); ?>
                        <?php $childrenJson = htmlspecialchars((string)json_encode($row['children'] ?? [], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
                        <?php $educationRows = (array)($row['educational_backgrounds'] ?? []); ?>
                        <?php $educationJson = htmlspecialchars((string)json_encode($educationRows, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
                        <tr data-profile-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>" data-profile-department="<?= htmlspecialchars((string)$row['department'], ENT_QUOTES, 'UTF-8') ?>" data-profile-status="<?= htmlspecialchars((string)$statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['employee_code'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['email'] !== '' ? $row['email'] : '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['department'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['position'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[90px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <div data-person-action-scope class="relative inline-block text-left w-full max-w-[240px]">
                                    <button type="button" data-person-action-menu-toggle class="w-full inline-flex items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300">
                                        <span class="inline-flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[18px]">bolt</span>
                                            Actions
                                        </span>
                                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                                    </button>

                                    <div data-person-action-menu class="hidden absolute right-0 z-20 mt-2 w-72 origin-top-right rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                                        <button type="button" data-action-menu-item data-action-target="edit-profile" class="w-full text-left px-3 py-2.5 rounded-lg text-sm text-slate-700 hover:bg-slate-50 inline-flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[18px] text-slate-500">edit</span>
                                            Edit Employee Profile
                                        </button>
                                        <button type="button" data-action-menu-item data-action-target="assign" class="w-full text-left px-3 py-2.5 rounded-lg text-sm text-slate-700 hover:bg-slate-50 inline-flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[18px] text-slate-500">person_add</span>
                                            Assign Department and Position
                                        </button>
                                        <button type="button" data-action-menu-item data-action-target="manage-status" class="w-full text-left px-3 py-2.5 rounded-lg text-sm text-slate-700 hover:bg-slate-50 inline-flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[18px] text-slate-500">manage_accounts</span>
                                            Manage Employee Status
                                        </button>
                                        <div class="my-1 h-px bg-slate-200"></div>
                                        <button type="button" data-action-menu-item data-action-target="archive" class="w-full text-left px-3 py-2.5 rounded-lg text-sm text-rose-700 hover:bg-rose-50 inline-flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[18px] text-rose-600">archive</span>
                                            Archive Employee Profile
                                        </button>
                                    </div>

                                    <button type="button" hidden data-action-trigger="edit-profile" data-person-profile-open data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>" data-first-name="<?= htmlspecialchars((string)$row['first_name'], ENT_QUOTES, 'UTF-8') ?>" data-middle-name="<?= htmlspecialchars((string)$row['middle_name'], ENT_QUOTES, 'UTF-8') ?>" data-surname="<?= htmlspecialchars((string)$row['surname'], ENT_QUOTES, 'UTF-8') ?>" data-name-extension="<?= htmlspecialchars((string)$row['name_extension'], ENT_QUOTES, 'UTF-8') ?>" data-date-of-birth="<?= htmlspecialchars((string)$row['date_of_birth'], ENT_QUOTES, 'UTF-8') ?>" data-place-of-birth="<?= htmlspecialchars((string)$row['place_of_birth'], ENT_QUOTES, 'UTF-8') ?>" data-sex-at-birth="<?= htmlspecialchars((string)$row['sex_at_birth'], ENT_QUOTES, 'UTF-8') ?>" data-civil-status="<?= htmlspecialchars((string)$row['civil_status'], ENT_QUOTES, 'UTF-8') ?>" data-height-m="<?= htmlspecialchars((string)$row['height_m'], ENT_QUOTES, 'UTF-8') ?>" data-weight-kg="<?= htmlspecialchars((string)$row['weight_kg'], ENT_QUOTES, 'UTF-8') ?>" data-blood-type="<?= htmlspecialchars((string)$row['blood_type'], ENT_QUOTES, 'UTF-8') ?>" data-citizenship="<?= htmlspecialchars((string)$row['citizenship'], ENT_QUOTES, 'UTF-8') ?>" data-dual-citizenship-country="<?= htmlspecialchars((string)$row['dual_citizenship_country'], ENT_QUOTES, 'UTF-8') ?>" data-telephone-no="<?= htmlspecialchars((string)$row['telephone_no'], ENT_QUOTES, 'UTF-8') ?>" data-email="<?= htmlspecialchars((string)$row['email'], ENT_QUOTES, 'UTF-8') ?>" data-mobile="<?= htmlspecialchars((string)$row['mobile'], ENT_QUOTES, 'UTF-8') ?>" data-agency-employee-no="<?= htmlspecialchars((string)$row['agency_employee_no'], ENT_QUOTES, 'UTF-8') ?>" data-residential-house-no="<?= htmlspecialchars((string)$row['residential_house_no'], ENT_QUOTES, 'UTF-8') ?>" data-residential-street="<?= htmlspecialchars((string)$row['residential_street'], ENT_QUOTES, 'UTF-8') ?>" data-residential-subdivision="<?= htmlspecialchars((string)$row['residential_subdivision'], ENT_QUOTES, 'UTF-8') ?>" data-residential-barangay="<?= htmlspecialchars((string)$row['residential_barangay'], ENT_QUOTES, 'UTF-8') ?>" data-residential-city-municipality="<?= htmlspecialchars((string)$row['residential_city_municipality'], ENT_QUOTES, 'UTF-8') ?>" data-residential-province="<?= htmlspecialchars((string)$row['residential_province'], ENT_QUOTES, 'UTF-8') ?>" data-residential-zip-code="<?= htmlspecialchars((string)$row['residential_zip_code'], ENT_QUOTES, 'UTF-8') ?>" data-permanent-house-no="<?= htmlspecialchars((string)$row['permanent_house_no'], ENT_QUOTES, 'UTF-8') ?>" data-permanent-street="<?= htmlspecialchars((string)$row['permanent_street'], ENT_QUOTES, 'UTF-8') ?>" data-permanent-subdivision="<?= htmlspecialchars((string)$row['permanent_subdivision'], ENT_QUOTES, 'UTF-8') ?>" data-permanent-barangay="<?= htmlspecialchars((string)$row['permanent_barangay'], ENT_QUOTES, 'UTF-8') ?>" data-permanent-city-municipality="<?= htmlspecialchars((string)$row['permanent_city_municipality'], ENT_QUOTES, 'UTF-8') ?>" data-permanent-province="<?= htmlspecialchars((string)$row['permanent_province'], ENT_QUOTES, 'UTF-8') ?>" data-permanent-zip-code="<?= htmlspecialchars((string)$row['permanent_zip_code'], ENT_QUOTES, 'UTF-8') ?>" data-umid-no="<?= htmlspecialchars((string)$row['umid_no'], ENT_QUOTES, 'UTF-8') ?>" data-pagibig-no="<?= htmlspecialchars((string)$row['pagibig_no'], ENT_QUOTES, 'UTF-8') ?>" data-philhealth-no="<?= htmlspecialchars((string)$row['philhealth_no'], ENT_QUOTES, 'UTF-8') ?>" data-psn-no="<?= htmlspecialchars((string)$row['psn_no'], ENT_QUOTES, 'UTF-8') ?>" data-tin-no="<?= htmlspecialchars((string)$row['tin_no'], ENT_QUOTES, 'UTF-8') ?>"></button>
                                    <button type="button" hidden data-action-trigger="assign" data-person-assignment-open data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>"></button>
                                    <button type="button" hidden data-action-trigger="manage-status" data-person-status-open data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>"></button>
                                    <button type="button" hidden data-action-trigger="archive" data-person-profile-archive data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>"></button>

                                    <button type="button" hidden data-person-family-open data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>" data-spouse-surname="<?= htmlspecialchars((string)$row['spouse_surname'], ENT_QUOTES, 'UTF-8') ?>" data-spouse-first-name="<?= htmlspecialchars((string)$row['spouse_first_name'], ENT_QUOTES, 'UTF-8') ?>" data-spouse-middle-name="<?= htmlspecialchars((string)$row['spouse_middle_name'], ENT_QUOTES, 'UTF-8') ?>" data-spouse-extension-name="<?= htmlspecialchars((string)$row['spouse_extension_name'], ENT_QUOTES, 'UTF-8') ?>" data-spouse-occupation="<?= htmlspecialchars((string)$row['spouse_occupation'], ENT_QUOTES, 'UTF-8') ?>" data-spouse-employer-business-name="<?= htmlspecialchars((string)$row['spouse_employer_business_name'], ENT_QUOTES, 'UTF-8') ?>" data-spouse-business-address="<?= htmlspecialchars((string)$row['spouse_business_address'], ENT_QUOTES, 'UTF-8') ?>" data-spouse-telephone-no="<?= htmlspecialchars((string)$row['spouse_telephone_no'], ENT_QUOTES, 'UTF-8') ?>" data-father-surname="<?= htmlspecialchars((string)$row['father_surname'], ENT_QUOTES, 'UTF-8') ?>" data-father-first-name="<?= htmlspecialchars((string)$row['father_first_name'], ENT_QUOTES, 'UTF-8') ?>" data-father-middle-name="<?= htmlspecialchars((string)$row['father_middle_name'], ENT_QUOTES, 'UTF-8') ?>" data-father-extension-name="<?= htmlspecialchars((string)$row['father_extension_name'], ENT_QUOTES, 'UTF-8') ?>" data-mother-surname="<?= htmlspecialchars((string)$row['mother_surname'], ENT_QUOTES, 'UTF-8') ?>" data-mother-first-name="<?= htmlspecialchars((string)$row['mother_first_name'], ENT_QUOTES, 'UTF-8') ?>" data-mother-middle-name="<?= htmlspecialchars((string)$row['mother_middle_name'], ENT_QUOTES, 'UTF-8') ?>" data-mother-extension-name="<?= htmlspecialchars((string)$row['mother_extension_name'], ENT_QUOTES, 'UTF-8') ?>" data-children="<?= $childrenJson ?>"></button>
                                    <button type="button" hidden data-person-education-open data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>" data-educational-backgrounds="<?= $educationJson ?>"></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<form id="personalInfoArchiveForm" action="personal-information.php" method="POST" class="hidden">
    <input type="hidden" name="form_action" value="save_profile">
    <input type="hidden" name="profile_action" value="archive">
    <input type="hidden" name="person_id" id="personalInfoArchivePersonId" value="">
    <input type="hidden" name="employee_name" id="personalInfoArchiveEmployeeName" value="">
    <input type="hidden" name="profile_notes" value="Archived by admin from employee records table.">
</form>

<form id="personalInfoEligibilityDeleteForm" action="personal-information.php" method="POST" class="hidden">
    <input type="hidden" name="form_action" value="save_civil_service_eligibility">
    <input type="hidden" name="eligibility_action" value="delete">
    <input type="hidden" id="personalInfoEligibilityDeletePersonId" name="person_id" value="">
    <input type="hidden" id="personalInfoEligibilityDeleteId" name="eligibility_id" value="">
</form>

<form id="personalInfoWorkExperienceDeleteForm" action="personal-information.php" method="POST" class="hidden">
    <input type="hidden" name="form_action" value="save_work_experience">
    <input type="hidden" name="work_experience_action" value="delete">
    <input type="hidden" id="personalInfoWorkExperienceDeletePersonId" name="person_id" value="">
    <input type="hidden" id="personalInfoWorkExperienceDeleteId" name="work_experience_id" value="">
</form>

<div id="personalInfoProfileModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoProfileModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-6xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="border-b border-slate-200">
                <div class="px-6 py-3 bg-slate-50/70 flex items-center gap-3">
                    <div class="w-full flex items-stretch">
                        <button type="button" data-pds-tab-target="section_i" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-slate-900 text-slate-900 bg-white">Personal Information</button>
                        <button type="button" data-pds-tab-target="section_ii" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 bg-white">Family Background</button>
                        <button type="button" data-pds-tab-target="section_iii" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 bg-white">Educational Background</button>
                    </div>
                    <button type="button" data-modal-close="personalInfoProfileModal" class="text-slate-500 hover:text-slate-700">✕</button>
                </div>
                <p id="personalInfoProfileEmployeeLabel" class="px-6 py-2 text-sm text-slate-500">Selected employee</p>
            </div>
            <form action="personal-information.php" method="POST" class="flex-1 min-h-0 flex flex-col" id="personalInfoProfileForm">
                <input type="hidden" name="form_action" value="save_profile">
                <input type="hidden" name="profile_action" id="profileAction" value="edit">
                <input name="person_id" type="hidden" id="profilePersonId" value="">
                <div class="flex-1 min-h-0 overflow-y-auto p-6 space-y-6 text-sm">
                    <section class="space-y-3">
                        <h4 class="text-sm font-semibold text-slate-700">Basic Identity</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-slate-600">First Name</label>
                                <input id="profileFirstName" name="first_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter first name" required>
                            </div>
                            <div>
                                <label class="text-slate-600">Middle Name</label>
                                <input id="profileMiddleName" name="middle_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter middle name">
                            </div>
                            <div>
                                <label class="text-slate-600">Last Name</label>
                                <input id="profileSurname" name="surname" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter last name" required>
                            </div>
                            <div>
                                <label class="text-slate-600">Name Extension</label>
                                <input id="profileNameExtension" name="name_extension" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Jr., Sr., III">
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Demographics</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-slate-600">Date of Birth</label>
                                <input id="profileDateOfBirth" name="date_of_birth" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Place of Birth</label>
                                <input id="profilePlaceOfBirth" name="place_of_birth" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="City/Municipality, Province">
                            </div>
                            <div>
                                <label class="text-slate-600">Sex at Birth</label>
                                <select id="profileSexAtBirth" name="sex_at_birth" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                                    <option value="">Select sex</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-slate-600">Civil Status</label>
                                <input id="profileCivilStatus" name="civil_status" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Single, Married, etc.">
                            </div>
                            <div>
                                <label class="text-slate-600">Height (m)</label>
                                <input id="profileHeightM" name="height_m" type="number" min="0" step="0.01" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="0.00">
                            </div>
                            <div>
                                <label class="text-slate-600">Weight (kg)</label>
                                <input id="profileWeightKg" name="weight_kg" type="number" min="0" step="0.01" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="0.00">
                            </div>
                            <div>
                                <label class="text-slate-600">Blood Type</label>
                                <input id="profileBloodType" name="blood_type" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="A+, O-, etc.">
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Citizenship</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-slate-600">Citizenship</label>
                                <input id="profileCitizenship" name="citizenship" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Filipino">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Dual Citizenship Country</label>
                                <input id="profileDualCitizenshipCountry" name="dual_citizenship_country" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Leave empty if not eligible">
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Residential Address</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-slate-600">House/Block/Lot No.</label>
                                <input id="profileResidentialHouseNo" name="residential_house_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="House no.">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Street</label>
                                <input id="profileResidentialStreet" name="residential_street" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Street">
                            </div>
                            <div>
                                <label class="text-slate-600">Barangay</label>
                                <input id="profileResidentialBarangay" name="residential_barangay" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Barangay">
                            </div>
                            <div>
                                <label class="text-slate-600">Subdivision/Village</label>
                                <input id="profileResidentialSubdivision" name="residential_subdivision" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Subdivision/Village">
                            </div>
                            <div>
                                <label class="text-slate-600">City/Municipality</label>
                                <input id="profileResidentialCity" name="residential_city_municipality" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="City/Municipality">
                            </div>
                            <div>
                                <label class="text-slate-600">Province</label>
                                <input id="profileResidentialProvince" name="residential_province" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Province">
                            </div>
                            <div>
                                <label class="text-slate-600">ZIP Code</label>
                                <input id="profileResidentialZipCode" name="residential_zip_code" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="ZIP code">
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Permanent Address</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-slate-600">House/Block/Lot No.</label>
                                <input id="profilePermanentHouseNo" name="permanent_house_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="House no.">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Street</label>
                                <input id="profilePermanentStreet" name="permanent_street" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Street">
                            </div>
                            <div>
                                <label class="text-slate-600">Barangay</label>
                                <input id="profilePermanentBarangay" name="permanent_barangay" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Barangay">
                            </div>
                            <div>
                                <label class="text-slate-600">Subdivision/Village</label>
                                <input id="profilePermanentSubdivision" name="permanent_subdivision" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Subdivision/Village">
                            </div>
                            <div>
                                <label class="text-slate-600">City/Municipality</label>
                                <input id="profilePermanentCity" name="permanent_city_municipality" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="City/Municipality">
                            </div>
                            <div>
                                <label class="text-slate-600">Province</label>
                                <input id="profilePermanentProvince" name="permanent_province" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Province">
                            </div>
                            <div>
                                <label class="text-slate-600">ZIP Code</label>
                                <input id="profilePermanentZipCode" name="permanent_zip_code" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="ZIP code">
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Government IDs</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-slate-600">UMID ID No.</label>
                                <input id="profileUmidNo" name="umid_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="UMID number">
                            </div>
                            <div>
                                <label class="text-slate-600">PAG-IBIG ID No.</label>
                                <input id="profilePagibigNo" name="pagibig_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="PAG-IBIG number">
                            </div>
                            <div>
                                <label class="text-slate-600">PHILHEALTH No.</label>
                                <input id="profilePhilhealthNo" name="philhealth_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="PhilHealth number">
                            </div>
                            <div>
                                <label class="text-slate-600">PhilSys Number (PSN)</label>
                                <input id="profilePsnNo" name="psn_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="PSN">
                            </div>
                            <div>
                                <label class="text-slate-600">TIN No.</label>
                                <input id="profileTinNo" name="tin_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="TIN number">
                            </div>
                            <div>
                                <label class="text-slate-600">Agency Employee No.</label>
                                <input id="profileAgencyEmployeeNo" name="agency_employee_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="DA-EMP-0001">
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Contact Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-slate-600">Telephone Number</label>
                                <input id="profileTelephoneNo" name="telephone_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter telephone number">
                            </div>
                            <div>
                                <label class="text-slate-600">Mobile Number</label>
                                <input id="profileMobile" name="mobile_no" type="text" pattern="^\+?[0-9][0-9\s-]{6,19}$" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter mobile number">
                            </div>
                            <div>
                                <label class="text-slate-600">Email Address</label>
                                <input id="profileEmail" name="email" type="email" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter email">
                            </div>
                        </div>
                    </section>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-modal-close="personalInfoProfileModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button id="personalInfoProfileSubmit" type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="personalInfoFamilyModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoFamilyModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-6xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="border-b border-slate-200">
                <div class="px-6 py-3 bg-slate-50/70 flex items-center gap-3">
                    <div class="w-full flex items-stretch">
                        <button type="button" data-pds-tab-target="section_i" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 bg-white">Personal Information</button>
                        <button type="button" data-pds-tab-target="section_ii" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-slate-900 text-slate-900 bg-white">Family Background</button>
                        <button type="button" data-pds-tab-target="section_iii" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 bg-white">Educational Background</button>
                    </div>
                    <button type="button" data-modal-close="personalInfoFamilyModal" class="text-slate-500 hover:text-slate-700">✕</button>
                </div>
                <p id="personalInfoFamilyEmployeeLabel" class="px-6 py-2 text-sm text-slate-500">Selected employee</p>
            </div>

            <form action="personal-information.php" method="POST" class="flex-1 min-h-0 flex flex-col" id="personalInfoFamilyForm">
                <input type="hidden" name="form_action" value="save_family_background">
                <input name="person_id" type="hidden" id="familyPersonId" value="">

                <div class="flex-1 min-h-0 overflow-y-auto p-6 space-y-6 text-sm">
                    <section class="space-y-3">
                        <h4 class="text-sm font-semibold text-slate-700">Spouse Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-slate-600">Spouse Surname</label>
                                <input id="familySpouseSurname" name="spouse_surname" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Surname">
                            </div>
                            <div>
                                <label class="text-slate-600">Spouse First Name</label>
                                <input id="familySpouseFirstName" name="spouse_first_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="First name">
                            </div>
                            <div>
                                <label class="text-slate-600">Spouse Middle Name</label>
                                <input id="familySpouseMiddleName" name="spouse_middle_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Middle name">
                            </div>
                            <div>
                                <label class="text-slate-600">Name Extension</label>
                                <input id="familySpouseExtensionName" name="spouse_extension_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Jr., Sr.">
                            </div>
                            <div>
                                <label class="text-slate-600">Occupation</label>
                                <input id="familySpouseOccupation" name="spouse_occupation" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Occupation">
                            </div>
                            <div>
                                <label class="text-slate-600">Employer/Business Name</label>
                                <input id="familySpouseEmployerBusinessName" name="spouse_employer_business_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Employer/Business name">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Business Address</label>
                                <input id="familySpouseBusinessAddress" name="spouse_business_address" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Business address">
                            </div>
                            <div>
                                <label class="text-slate-600">Telephone No.</label>
                                <input id="familySpouseTelephoneNo" name="spouse_telephone_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Telephone number">
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Children</h4>
                        <div class="flex justify-end">
                            <button type="button" id="personalInfoFamilyAddChildButton" class="px-3 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Add Child</button>
                        </div>
                        <div class="overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-sm" id="personalInfoFamilyChildrenTable">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="text-left px-3 py-2">Full Name</th>
                                        <th class="text-left px-3 py-2">Date of Birth</th>
                                        <th class="text-left px-3 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="personalInfoFamilyChildrenTableBody">
                                    <tr>
                                        <td class="px-3 py-2"><input name="children_full_name[]" type="text" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="Child full name"></td>
                                        <td class="px-3 py-2"><input name="children_birth_date[]" type="date" class="w-full border border-slate-300 rounded-md px-3 py-2"></td>
                                        <td class="px-3 py-2"><button type="button" data-family-child-remove class="px-2.5 py-1 rounded-md border border-rose-200 text-rose-700 hover:bg-rose-50">Remove</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <template id="personalInfoFamilyChildRowTemplate">
                            <tr>
                                <td class="px-3 py-2"><input name="children_full_name[]" type="text" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="Child full name"></td>
                                <td class="px-3 py-2"><input name="children_birth_date[]" type="date" class="w-full border border-slate-300 rounded-md px-3 py-2"></td>
                                <td class="px-3 py-2"><button type="button" data-family-child-remove class="px-2.5 py-1 rounded-md border border-rose-200 text-rose-700 hover:bg-rose-50">Remove</button></td>
                            </tr>
                        </template>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Father's Name</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-slate-600">Surname</label>
                                <input id="familyFatherSurname" name="father_surname" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Surname">
                            </div>
                            <div>
                                <label class="text-slate-600">First Name</label>
                                <input id="familyFatherFirstName" name="father_first_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="First name">
                            </div>
                            <div>
                                <label class="text-slate-600">Middle Name</label>
                                <input id="familyFatherMiddleName" name="father_middle_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Middle name">
                            </div>
                            <div>
                                <label class="text-slate-600">Name Extension</label>
                                <input id="familyFatherExtensionName" name="father_extension_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Jr., Sr.">
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Mother's Maiden Name</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-slate-600">Surname</label>
                                <input id="familyMotherSurname" name="mother_surname" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Surname">
                            </div>
                            <div>
                                <label class="text-slate-600">First Name</label>
                                <input id="familyMotherFirstName" name="mother_first_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="First name">
                            </div>
                            <div>
                                <label class="text-slate-600">Middle Name</label>
                                <input id="familyMotherMiddleName" name="mother_middle_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Middle name">
                            </div>
                            <div>
                                <label class="text-slate-600">Name Extension</label>
                                <input id="familyMotherExtensionName" name="mother_extension_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Jr., Sr.">
                            </div>
                        </div>
                    </section>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-modal-close="personalInfoFamilyModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button id="personalInfoFamilySubmit" type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Section II</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="personalInfoEducationModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoEducationModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-7xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="border-b border-slate-200">
                <div class="px-6 py-3 bg-slate-50/70 flex items-center gap-3">
                    <div class="w-full flex items-stretch">
                        <button type="button" data-pds-tab-target="section_i" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 bg-white">Personal Information</button>
                        <button type="button" data-pds-tab-target="section_ii" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 bg-white">Family Background</button>
                        <button type="button" data-pds-tab-target="section_iii" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-slate-900 text-slate-900 bg-white">Educational Background</button>
                    </div>
                    <button type="button" data-modal-close="personalInfoEducationModal" class="text-slate-500 hover:text-slate-700">✕</button>
                </div>
                <p id="personalInfoEducationEmployeeLabel" class="px-6 py-2 text-sm text-slate-500">Selected employee</p>
            </div>

            <form id="personalInfoEducationForm" action="personal-information.php" method="POST" class="flex-1 min-h-0 flex flex-col">
                <input type="hidden" name="form_action" value="save_educational_background">
                <input type="hidden" name="person_id" id="educationPersonId" value="">

                <div class="flex-1 min-h-0 overflow-y-auto p-6 text-sm space-y-3">
                    <p class="text-xs text-slate-500">Complete each level row as applicable. Leave fields blank when not applicable.</p>
                    <div class="overflow-x-auto border border-slate-200 rounded-lg">
                        <table class="w-full text-sm min-w-[1180px]">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr class="border-b border-slate-200">
                                    <th class="text-left px-3 py-2 w-[180px]" rowspan="2">Level</th>
                                    <th class="text-left px-3 py-2" colspan="2">Institution & Program</th>
                                    <th class="text-left px-3 py-2" colspan="2">Period of Attendance</th>
                                    <th class="text-left px-3 py-2" colspan="3">Completion Details</th>
                                </tr>
                                <tr>
                                    <th class="text-left px-3 py-2 min-w-[180px]">School Name</th>
                                    <th class="text-left px-3 py-2 min-w-[180px]">Degree/Course</th>
                                    <th class="text-left px-3 py-2 w-[100px]">From</th>
                                    <th class="text-left px-3 py-2 w-[100px]">To</th>
                                    <th class="text-left px-3 py-2 min-w-[180px]">Highest Level / Units Earned</th>
                                    <th class="text-left px-3 py-2 w-[120px]">Year Graduated</th>
                                    <th class="text-left px-3 py-2 min-w-[200px]">Scholarship / Academic Honors</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php
                                $educationLevels = [
                                    'elementary' => 'Elementary',
                                    'secondary' => 'Secondary',
                                    'vocational_trade_course' => 'Vocational / Trade Course',
                                    'college' => 'College',
                                    'graduate_studies' => 'Graduate Studies',
                                ];
                                ?>
                                <?php foreach ($educationLevels as $educationLevelKey => $educationLevelLabel): ?>
                                    <tr class="align-top">
                                        <td class="px-3 py-3 font-medium text-slate-700 bg-slate-50/60">
                                            <?= htmlspecialchars($educationLevelLabel, ENT_QUOTES, 'UTF-8') ?>
                                            <input type="hidden" name="education_level[]" value="<?= htmlspecialchars($educationLevelKey, ENT_QUOTES, 'UTF-8') ?>">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="text" name="school_name[]" data-education-level="<?= htmlspecialchars($educationLevelKey, ENT_QUOTES, 'UTF-8') ?>" data-education-field="school_name" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="Write in full">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="text" name="degree_course[]" data-education-level="<?= htmlspecialchars($educationLevelKey, ENT_QUOTES, 'UTF-8') ?>" data-education-field="degree_course" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="Write in full">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="number" name="attendance_from_year[]" data-education-level="<?= htmlspecialchars($educationLevelKey, ENT_QUOTES, 'UTF-8') ?>" data-education-field="attendance_from_year" min="1900" max="2100" step="1" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="YYYY">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="number" name="attendance_to_year[]" data-education-level="<?= htmlspecialchars($educationLevelKey, ENT_QUOTES, 'UTF-8') ?>" data-education-field="attendance_to_year" min="1900" max="2100" step="1" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="YYYY">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="text" name="highest_level_units_earned[]" data-education-level="<?= htmlspecialchars($educationLevelKey, ENT_QUOTES, 'UTF-8') ?>" data-education-field="highest_level_units_earned" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="If not graduated">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="number" name="year_graduated[]" data-education-level="<?= htmlspecialchars($educationLevelKey, ENT_QUOTES, 'UTF-8') ?>" data-education-field="year_graduated" min="1900" max="2100" step="1" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="YYYY">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="text" name="scholarship_honors_received[]" data-education-level="<?= htmlspecialchars($educationLevelKey, ENT_QUOTES, 'UTF-8') ?>" data-education-field="scholarship_honors_received" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="Scholarship / Honors">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-modal-close="personalInfoEducationModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Section III</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="personalInfoEligibilityModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoEligibilityModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-4xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Civil Service Eligibility</h3>
                    <p id="personalInfoEligibilityEmployeeLabel" class="text-sm text-slate-500 mt-1">Selected employee</p>
                </div>
                <button type="button" data-modal-close="personalInfoEligibilityModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <form id="personalInfoEligibilityForm" action="personal-information.php" method="POST" class="flex-1 min-h-0 flex flex-col">
                <input type="hidden" name="form_action" value="save_civil_service_eligibility">
                <input type="hidden" name="eligibility_action" id="personalInfoEligibilityAction" value="add">
                <input type="hidden" name="person_id" id="personalInfoEligibilityPersonId" value="">
                <input type="hidden" name="eligibility_id" id="personalInfoEligibilityId" value="">

                <div class="flex-1 min-h-0 overflow-y-auto p-6 space-y-6 text-sm">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="text-slate-600">Career Service / RA 1080 / Board / Other</label>
                            <input id="personalInfoEligibilityName" name="eligibility_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter eligibility name" required>
                        </div>
                        <div>
                            <label class="text-slate-600">Rating</label>
                            <input id="personalInfoEligibilityRating" name="rating" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="e.g. 83.50%">
                        </div>
                        <div>
                            <label class="text-slate-600">Date of Examination</label>
                            <input id="personalInfoEligibilityExamDate" name="exam_date" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="text-slate-600">Place of Examination</label>
                            <input id="personalInfoEligibilityExamPlace" name="exam_place" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="City / Province">
                        </div>
                        <div>
                            <label class="text-slate-600">License Number</label>
                            <input id="personalInfoEligibilityLicenseNo" name="license_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter license number">
                        </div>
                        <div>
                            <label class="text-slate-600">License Validity</label>
                            <input id="personalInfoEligibilityLicenseValidity" name="license_validity" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="text-slate-600">Sequence</label>
                            <input id="personalInfoEligibilitySequence" name="sequence_no" type="number" min="1" step="1" value="1" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-slate-700">Saved Eligibility Records</h4>
                            <button type="button" id="personalInfoEligibilityResetButton" class="px-3 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Clear Form</button>
                        </div>
                        <div class="overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-sm" id="personalInfoEligibilityTable">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="text-left px-3 py-2">Eligibility</th>
                                        <th class="text-left px-3 py-2">Exam Date</th>
                                        <th class="text-left px-3 py-2">License</th>
                                        <th class="text-left px-3 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="personalInfoEligibilityTableBody">
                                    <tr>
                                        <td colspan="4" class="px-3 py-3 text-slate-500">No records loaded.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-modal-close="personalInfoEligibilityModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" id="personalInfoEligibilitySubmit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Eligibility</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="personalInfoWorkExperienceModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoWorkExperienceModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-5xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Work Experience</h3>
                    <p id="personalInfoWorkExperienceEmployeeLabel" class="text-sm text-slate-500 mt-1">Selected employee</p>
                </div>
                <button type="button" data-modal-close="personalInfoWorkExperienceModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <form id="personalInfoWorkExperienceForm" action="personal-information.php" method="POST" class="flex-1 min-h-0 flex flex-col">
                <input type="hidden" name="form_action" value="save_work_experience">
                <input type="hidden" name="work_experience_action" id="personalInfoWorkExperienceAction" value="add">
                <input type="hidden" name="person_id" id="personalInfoWorkExperiencePersonId" value="">
                <input type="hidden" name="work_experience_id" id="personalInfoWorkExperienceId" value="">

                <div class="flex-1 min-h-0 overflow-y-auto p-6 space-y-6 text-sm">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-slate-600">Inclusive Date (From)</label>
                            <input id="personalInfoWorkExperienceDateFrom" name="inclusive_date_from" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-slate-600">Inclusive Date (To)</label>
                            <input id="personalInfoWorkExperienceDateTo" name="inclusive_date_to" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="text-slate-600">Position Title</label>
                            <input id="personalInfoWorkExperiencePosition" name="position_title" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter position title" required>
                        </div>
                        <div>
                            <label class="text-slate-600">Office / Company</label>
                            <input id="personalInfoWorkExperienceOffice" name="office_company" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter office/company" required>
                        </div>
                        <div>
                            <label class="text-slate-600">Monthly Salary</label>
                            <input id="personalInfoWorkExperienceSalary" name="monthly_salary" type="number" min="0" step="0.01" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="0.00">
                        </div>
                        <div>
                            <label class="text-slate-600">Salary Grade / Step</label>
                            <input id="personalInfoWorkExperienceSalaryGrade" name="salary_grade_step" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="SG-11 / Step 1">
                        </div>
                        <div>
                            <label class="text-slate-600">Appointment Status</label>
                            <input id="personalInfoWorkExperienceAppointmentStatus" name="appointment_status" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Permanent / Contractual">
                        </div>
                        <div>
                            <label class="text-slate-600">Government Service</label>
                            <select id="personalInfoWorkExperienceGovernment" name="is_government_service" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                                <option value="">Not Specified</option>
                                <option value="true">Yes</option>
                                <option value="false">No</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-slate-600">Sequence</label>
                            <input id="personalInfoWorkExperienceSequence" name="sequence_no" type="number" min="1" step="1" value="1" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                        </div>
                        <div class="md:col-span-3">
                            <label class="text-slate-600">Separation Reason</label>
                            <input id="personalInfoWorkExperienceSeparationReason" name="separation_reason" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter separation reason (if any)">
                        </div>
                        <div class="md:col-span-3">
                            <label class="text-slate-600">Achievements</label>
                            <textarea id="personalInfoWorkExperienceAchievements" name="achievements" rows="2" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Key achievements"></textarea>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-slate-700">Saved Work Experience Records</h4>
                            <button type="button" id="personalInfoWorkExperienceResetButton" class="px-3 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Clear Form</button>
                        </div>
                        <div class="overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-sm" id="personalInfoWorkExperienceTable">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="text-left px-3 py-2">Position</th>
                                        <th class="text-left px-3 py-2">Inclusive Dates</th>
                                        <th class="text-left px-3 py-2">Office / Salary</th>
                                        <th class="text-left px-3 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="personalInfoWorkExperienceTableBody">
                                    <tr>
                                        <td colspan="4" class="px-3 py-3 text-slate-500">No records loaded.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-modal-close="personalInfoWorkExperienceModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" id="personalInfoWorkExperienceSubmit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Work Experience</button>
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
                <input type="hidden" id="personalInfoAssignmentPersonId" name="person_id" value="">
                <div>
                    <label class="text-slate-600">Employee</label>
                    <input id="personalInfoAssignmentEmployeeDisplay" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
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
                <input type="hidden" id="personalInfoStatusPersonId" name="person_id" value="">
                <div>
                    <label class="text-slate-600">Employee</label>
                    <input id="personalInfoStatusEmployeeDisplay" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
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
