<?php
require_once __DIR__ . '/includes/personal-information/bootstrap.php';
require_once __DIR__ . '/includes/personal-information/actions.php';
require_once __DIR__ . '/includes/personal-information/data.php';

$pageTitle = 'Personal Information | Staff';
$activePage = 'personal-information.php';
$breadcrumbs = ['Personal Information'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/staff/personal-information/index.js';

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
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
        <p class="text-sm text-slate-500 mt-1">Quick summary of division-scoped employee records, completion, and active status.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Profiles</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$totalProfiles, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-600 mt-1">Within your division scope</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Complete Records</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$completeRecords, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-600 mt-1">Profiles with assignment details</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">Active Employees</p>
            <p class="text-xl font-semibold text-slate-800 mt-2"><?= htmlspecialchars((string)$activeEmployees, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Employment status tagged active</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">Inactive Employees</p>
            <p class="text-xl font-semibold text-slate-800 mt-2"><?= htmlspecialchars((string)$inactiveEmployees, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Employment status tagged inactive</p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Pending Admin Approval</h2>
            <p class="text-sm text-slate-500 mt-1">Employee-submitted personal-information requests within your scope that are awaiting admin review.</p>
        </div>
        <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 text-xs px-2.5 py-1 font-medium">
            Pending
        </span>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
        <div class="md:col-span-3">
            <label for="personalInfoPendingSearchInput" class="text-slate-600">Search Requests</label>
            <input id="personalInfoPendingSearchInput" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Search by request ID, employee, submitted by, or summary">
        </div>
        <div>
            <label for="personalInfoPendingTypeFilter" class="text-slate-600">Recommendation Type</label>
            <select id="personalInfoPendingTypeFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="">All Types</option>
                <option value="profile update request">Profile Update Request</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="personalInfoPendingTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Request ID</th>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Submitted By</th>
                    <th class="text-left px-4 py-3">Submitted On</th>
                    <th class="text-left px-4 py-3">Tracker</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($pendingAdminApprovalRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="8">No pending employee requests found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pendingAdminApprovalRows as $recommendationRow): ?>
                        <tr
                            data-pending-row
                            data-pending-search="<?= htmlspecialchars((string)($recommendationRow['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-pending-type="<?= htmlspecialchars(strtolower((string)($recommendationRow['recommendation_type'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                            data-review-title="<?= htmlspecialchars((string)($recommendationRow['review_title'] ?? 'Recommendation Details'), ENT_QUOTES, 'UTF-8') ?>"
                            data-review-summary="<?= htmlspecialchars((string)($recommendationRow['summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-review-content="<?= htmlspecialchars((string)($recommendationRow['review_content'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-review-action="<?= htmlspecialchars((string)($recommendationRow['recommendation_action'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-review-person-id="<?= htmlspecialchars((string)($recommendationRow['person_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-review-pairs="<?= htmlspecialchars((string)json_encode($recommendationRow['review_pairs'] ?? [], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($recommendationRow['request_id'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 font-medium text-slate-700"><?= htmlspecialchars((string)($recommendationRow['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($recommendationRow['recommendation_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($recommendationRow['submitted_by'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($recommendationRow['submitted_at_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600">
                                <div class="space-y-1">
                                    <p><span class="font-medium text-slate-700">Due:</span> <?= htmlspecialchars((string)($recommendationRow['due_at_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p><span class="font-medium text-slate-700">Reminder:</span> <?= htmlspecialchars((string)($recommendationRow['reminder_at_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs <?= htmlspecialchars((string)($recommendationRow['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)($recommendationRow['status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                <button type="button" data-pending-review-open class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">
                                    <span class="material-symbols-outlined text-[16px]">preview</span>
                                    Review Changes
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="personalInfoPendingFilterEmpty" class="hidden">
                    <td class="px-4 py-3 text-slate-500" colspan="8">No pending requests match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="personalInfoPendingPaginationInfo" class="text-xs text-slate-500">Page 1 of 1</p>
        <div class="flex items-center gap-2">
            <button type="button" id="personalInfoPendingPrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <button type="button" id="personalInfoPendingNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Employee Management Table</h2>
        <p class="text-sm text-slate-500 mt-1">Search and filter employee records, then select an action from the dropdown.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div class="md:col-span-2">
            <label class="text-slate-600">Search Employee Records</label>
            <input id="personalInfoRecordsSearchInput" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Search by employee ID, name, email, division, or position">
        </div>
        <div>
            <label class="text-slate-600">Division Filter</label>
            <select id="personalInfoRecordsDepartmentFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="">All Divisions</option>
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

    <div class="p-6 overflow-x-auto overflow-y-visible relative">
        <table id="personalInfoEmployeesTable" class="w-full text-[13px] leading-5">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee ID</th>
                    <th class="text-left px-4 py-3">Full Name</th>
                    <th class="text-left px-4 py-3">Email</th>
                    <th class="text-left px-4 py-3">Division</th>
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
                        <?php $childrenJson = htmlspecialchars((string)json_encode($row['children'] ?? [], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
                        <?php $educationJson = htmlspecialchars((string)json_encode($row['educational_backgrounds'] ?? [], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
                        <tr
                            data-profile-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>"
                            data-profile-department="<?= htmlspecialchars((string)$row['department'], ENT_QUOTES, 'UTF-8') ?>"
                            data-profile-status="<?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['employee_code'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <div class="space-y-1">
                                    <p><?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php if (!empty($row['has_contractual_application'])): ?>
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-medium text-amber-800" title="<?= htmlspecialchars((string)($row['contractual_application_job_title'] ?? 'Contractual Job'), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars((string)($row['contractual_application_label'] ?? 'Applied to Contractual Job'), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['email'] !== '' ? $row['email'] : '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['department'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['position'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center justify-center min-w-[90px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars((string)$row['status_class'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div data-person-action-scope class="relative inline-block text-left w-full max-w-[240px]">
                                    <button type="button" data-person-action-menu-toggle aria-haspopup="menu" aria-expanded="false" class="admin-action-button w-full">
                                        <span class="admin-action-button-label">
                                            <span class="material-symbols-outlined">more_horiz</span>
                                            Actions
                                        </span>
                                        <span class="material-symbols-outlined admin-action-chevron">expand_more</span>
                                    </button>

                                    <div data-person-action-menu role="menu" class="admin-action-menu hidden w-72">
                                        <button type="button" data-action-menu-item data-action-target="assign" role="menuitem" class="admin-action-item">
                                            <span class="material-symbols-outlined">person_add</span>
                                            Assign Division and Position
                                        </button>
                                        <button type="button" data-action-menu-item data-action-target="manage-status" role="menuitem" class="admin-action-item">
                                            <span class="material-symbols-outlined">manage_accounts</span>
                                            Manage Employee Status
                                        </button>
                                    </div>

                                    <button
                                        type="button"
                                        hidden
                                        data-action-trigger="edit-profile"
                                        data-person-profile-open
                                        data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-employment-id="<?= htmlspecialchars((string)$row['employment_id'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-first-name="<?= htmlspecialchars((string)$row['first_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-middle-name="<?= htmlspecialchars((string)$row['middle_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-surname="<?= htmlspecialchars((string)$row['surname'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-name-extension="<?= htmlspecialchars((string)$row['name_extension'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-date-of-birth="<?= htmlspecialchars((string)$row['date_of_birth'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-place-of-birth="<?= htmlspecialchars((string)$row['place_of_birth'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-sex-at-birth="<?= htmlspecialchars((string)$row['sex_at_birth'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-civil-status="<?= htmlspecialchars((string)$row['civil_status'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-height-m="<?= htmlspecialchars((string)$row['height_m'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-weight-kg="<?= htmlspecialchars((string)$row['weight_kg'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-blood-type="<?= htmlspecialchars((string)$row['blood_type'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-citizenship="<?= htmlspecialchars((string)$row['citizenship'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-dual-citizenship-country="<?= htmlspecialchars((string)$row['dual_citizenship_country'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-telephone-no="<?= htmlspecialchars((string)$row['telephone_no'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-email="<?= htmlspecialchars((string)$row['email'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-mobile="<?= htmlspecialchars((string)$row['mobile'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-agency-employee-no="<?= htmlspecialchars((string)$row['agency_employee_no'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-residential-house-no="<?= htmlspecialchars((string)$row['residential_house_no'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-residential-street="<?= htmlspecialchars((string)$row['residential_street'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-residential-subdivision="<?= htmlspecialchars((string)$row['residential_subdivision'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-residential-barangay="<?= htmlspecialchars((string)$row['residential_barangay'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-residential-city-municipality="<?= htmlspecialchars((string)$row['residential_city_municipality'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-residential-province="<?= htmlspecialchars((string)$row['residential_province'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-residential-zip-code="<?= htmlspecialchars((string)$row['residential_zip_code'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-permanent-house-no="<?= htmlspecialchars((string)$row['permanent_house_no'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-permanent-street="<?= htmlspecialchars((string)$row['permanent_street'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-permanent-subdivision="<?= htmlspecialchars((string)$row['permanent_subdivision'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-permanent-barangay="<?= htmlspecialchars((string)$row['permanent_barangay'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-permanent-city-municipality="<?= htmlspecialchars((string)$row['permanent_city_municipality'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-permanent-province="<?= htmlspecialchars((string)$row['permanent_province'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-permanent-zip-code="<?= htmlspecialchars((string)$row['permanent_zip_code'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-umid-no="<?= htmlspecialchars((string)$row['umid_no'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-pagibig-no="<?= htmlspecialchars((string)$row['pagibig_no'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-philhealth-no="<?= htmlspecialchars((string)$row['philhealth_no'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-psn-no="<?= htmlspecialchars((string)$row['psn_no'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-tin-no="<?= htmlspecialchars((string)$row['tin_no'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-spouse-surname="<?= htmlspecialchars((string)$row['spouse_surname'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-spouse-first-name="<?= htmlspecialchars((string)$row['spouse_first_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-spouse-middle-name="<?= htmlspecialchars((string)$row['spouse_middle_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-spouse-extension-name="<?= htmlspecialchars((string)$row['spouse_extension_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-spouse-occupation="<?= htmlspecialchars((string)$row['spouse_occupation'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-spouse-employer-business-name="<?= htmlspecialchars((string)$row['spouse_employer_business_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-spouse-business-address="<?= htmlspecialchars((string)$row['spouse_business_address'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-spouse-telephone-no="<?= htmlspecialchars((string)$row['spouse_telephone_no'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-father-surname="<?= htmlspecialchars((string)$row['father_surname'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-father-first-name="<?= htmlspecialchars((string)$row['father_first_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-father-middle-name="<?= htmlspecialchars((string)$row['father_middle_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-father-extension-name="<?= htmlspecialchars((string)$row['father_extension_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-mother-surname="<?= htmlspecialchars((string)$row['mother_surname'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-mother-first-name="<?= htmlspecialchars((string)$row['mother_first_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-mother-middle-name="<?= htmlspecialchars((string)$row['mother_middle_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-mother-extension-name="<?= htmlspecialchars((string)$row['mother_extension_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-children="<?= $childrenJson ?>"
                                        data-educational-backgrounds="<?= $educationJson ?>"
                                    ></button>
                                    <button
                                        type="button"
                                        hidden
                                        data-action-trigger="assign"
                                        data-person-assignment-open
                                        data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-employment-id="<?= htmlspecialchars((string)$row['employment_id'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-office-id="<?= htmlspecialchars((string)$row['office_id'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-position-id="<?= htmlspecialchars((string)$row['position_id'], ENT_QUOTES, 'UTF-8') ?>"
                                    ></button>
                                    <button
                                        type="button"
                                        hidden
                                        data-action-trigger="manage-status"
                                        data-person-status-open
                                        data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-employment-id="<?= htmlspecialchars((string)$row['employment_id'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status="<?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>"
                                    ></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="personalInfoFilterEmpty" class="hidden">
                    <td class="px-4 py-3 text-slate-500" colspan="7">No records match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="personalInfoPaginationInfo" class="text-xs text-slate-500">Page 1 of 1</p>
        <div class="flex items-center gap-2">
            <button type="button" id="personalInfoPrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <button type="button" id="personalInfoNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<div id="personalInfoProfileModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoProfileModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-6xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="border-b border-slate-200">
                <div class="px-6 py-4 flex items-center justify-between mb-1">
                    <h3 class="text-lg font-semibold text-slate-800">Recommend Profile Update</h3>
                    <button type="button" data-modal-close="personalInfoProfileModal" class="text-slate-500 hover:text-slate-700">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="px-6 pb-4">
                    <div class="grid grid-cols-3 text-sm border rounded-lg overflow-hidden">
                        <button type="button" data-profile-tab-target="personal" class="profile-tab px-3 py-2 bg-slate-50 border-b-2 border-daGreen text-daGreen font-medium">I. Personal Information</button>
                        <button type="button" data-profile-tab-target="family" class="profile-tab px-3 py-2 bg-white border-b-2 border-transparent text-slate-600">II. Family Background</button>
                        <button type="button" data-profile-tab-target="education" class="profile-tab px-3 py-2 bg-white border-b-2 border-transparent text-slate-600">III. Educational Background</button>
                    </div>
                    <p id="personalInfoProfileEmployeeLabel" class="pt-2 text-sm text-slate-500">Selected employee</p>
                </div>
            </div>

            <form action="personal-information.php" method="POST" class="flex-1 min-h-0 flex flex-col" id="personalInfoProfileForm">
                <input type="hidden" name="form_action" value="save_profile">
                <input type="hidden" name="profile_action" id="profileAction" value="edit">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input name="person_id" type="hidden" id="profilePersonId" value="">
                <input name="employment_id" type="hidden" id="profileEmploymentId" value="">

                <div class="flex-1 min-h-0 overflow-y-auto p-6 space-y-6 text-sm">
                    <section data-profile-section="personal" class="space-y-6">
                    <section class="space-y-3">
                        <h4 class="text-sm font-semibold text-slate-700">Basic Identity</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-slate-600">First Name</label>
                                <input id="profileFirstName" name="first_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            </div>
                            <div>
                                <label class="text-slate-600">Middle Name</label>
                                <input id="profileMiddleName" name="middle_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                            </div>
                            <div>
                                <label class="text-slate-600">Last Name</label>
                                <input id="profileSurname" name="surname" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
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
                                <div class="relative mt-1">
                                    <input id="profilePlaceOfBirth" name="place_of_birth" type="text" list="profilePlaceOfBirthList" autocomplete="off" data-modern-search="place_of_birth" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400">
                                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                                    </span>
                                </div>
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
                                <div class="relative mt-1">
                                    <input id="profileCivilStatus" name="civil_status" type="text" list="profileCivilStatusList" autocomplete="off" data-modern-search="civil_status" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400">
                                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="text-slate-600">Height (m)</label>
                                <input id="profileHeightM" name="height_m" type="number" min="0" step="0.01" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                            </div>
                            <div>
                                <label class="text-slate-600">Weight (kg)</label>
                                <input id="profileWeightKg" name="weight_kg" type="number" min="0" step="0.01" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                            </div>
                            <div>
                                <label class="text-slate-600">Blood Type</label>
                                <div class="relative mt-1">
                                    <input id="profileBloodType" name="blood_type" type="text" list="profileBloodTypeList" autocomplete="off" data-modern-search="blood_type" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400">
                                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Citizenship</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-slate-600">Citizenship</label>
                                <input id="profileCitizenship" name="citizenship" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Dual Citizenship Country</label>
                                <input id="profileDualCitizenshipCountry" name="dual_citizenship_country" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Residential Address</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div><label class="text-slate-600">House No.</label><input id="profileResidentialHouseNo" name="residential_house_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                            <div class="md:col-span-2"><label class="text-slate-600">Street</label><input id="profileResidentialStreet" name="residential_street" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                            <div>
                                <label class="text-slate-600">Barangay</label>
                                <div class="relative mt-1">
                                    <input id="profileResidentialBarangay" name="residential_barangay" type="text" list="profileResidentialBarangayList" autocomplete="off" data-address-role="barangay" data-address-group="residential" data-modern-search="residential_barangay" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400">
                                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                                    </span>
                                </div>
                            </div>
                            <div><label class="text-slate-600">Subdivision</label><input id="profileResidentialSubdivision" name="residential_subdivision" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                            <div>
                                <label class="text-slate-600">City/Municipality</label>
                                <div class="relative mt-1">
                                    <input id="profileResidentialCity" name="residential_city_municipality" type="text" list="profileCityList" autocomplete="off" data-address-role="city" data-address-group="residential" data-modern-search="residential_city" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400">
                                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="text-slate-600">Province</label>
                                <div class="relative mt-1">
                                    <input id="profileResidentialProvince" name="residential_province" type="text" list="profileProvinceList" autocomplete="off" data-modern-search="residential_province" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400">
                                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                                    </span>
                                </div>
                            </div>
                            <div><label class="text-slate-600">ZIP Code</label><input id="profileResidentialZipCode" name="residential_zip_code" type="text" autocomplete="off" inputmode="numeric" pattern="^\d{4}$" data-address-role="zip" data-address-group="residential" data-modern-search="residential_zip" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Permanent Address</h4>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input id="profileSameAsPermanentAddress" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Same as residential address</span>
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div><label class="text-slate-600">House No.</label><input id="profilePermanentHouseNo" name="permanent_house_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                            <div class="md:col-span-2"><label class="text-slate-600">Street</label><input id="profilePermanentStreet" name="permanent_street" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                            <div>
                                <label class="text-slate-600">Barangay</label>
                                <div class="relative mt-1">
                                    <input id="profilePermanentBarangay" name="permanent_barangay" type="text" list="profilePermanentBarangayList" autocomplete="off" data-address-role="barangay" data-address-group="permanent" data-modern-search="permanent_barangay" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400">
                                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                                    </span>
                                </div>
                            </div>
                            <div><label class="text-slate-600">Subdivision</label><input id="profilePermanentSubdivision" name="permanent_subdivision" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                            <div>
                                <label class="text-slate-600">City/Municipality</label>
                                <div class="relative mt-1">
                                    <input id="profilePermanentCity" name="permanent_city_municipality" type="text" list="profileCityList" autocomplete="off" data-address-role="city" data-address-group="permanent" data-modern-search="permanent_city" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400">
                                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="text-slate-600">Province</label>
                                <div class="relative mt-1">
                                    <input id="profilePermanentProvince" name="permanent_province" type="text" list="profileProvinceList" autocomplete="off" data-modern-search="permanent_province" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400">
                                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                                    </span>
                                </div>
                            </div>
                            <div><label class="text-slate-600">ZIP Code</label><input id="profilePermanentZipCode" name="permanent_zip_code" type="text" autocomplete="off" inputmode="numeric" pattern="^\d{4}$" data-address-role="zip" data-address-group="permanent" data-modern-search="permanent_zip" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Government IDs</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><label class="text-slate-600">UMID ID No.</label><input id="profileUmidNo" name="umid_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                            <div><label class="text-slate-600">PAG-IBIG ID No.</label><input id="profilePagibigNo" name="pagibig_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                            <div><label class="text-slate-600">PHILHEALTH No.</label><input id="profilePhilhealthNo" name="philhealth_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                            <div><label class="text-slate-600">PhilSys Number (PSN)</label><input id="profilePsnNo" name="psn_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                            <div><label class="text-slate-600">TIN No.</label><input id="profileTinNo" name="tin_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                            <div><label class="text-slate-600">Agency Employee No.</label><input id="profileAgencyEmployeeNo" name="agency_employee_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Contact Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><label class="text-slate-600">Telephone Number</label><input id="profileTelephoneNo" name="telephone_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                            <div><label class="text-slate-600">Mobile Number</label><input id="profileMobile" name="mobile_no" type="text" pattern="^\+?[0-9][0-9\s-]{6,19}$" required class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                            <div><label class="text-slate-600">Email Address</label><input id="profileEmail" name="email" type="email" required class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                        </div>
                    </section>

                    <section class="border-t border-slate-200 pt-4">
                        <label class="text-slate-600">Recommendation Notes (optional)</label>
                        <textarea id="profileRecommendationNotes" name="profile_recommendation_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add context for admin review."></textarea>
                    </section>
                    </section>

                    <section data-profile-section="family" class="space-y-6 hidden">
                        <section class="space-y-3">
                            <h4 class="text-sm font-semibold text-slate-700">Spouse Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div><label class="text-slate-600">Spouse Surname</label><input id="profileSpouseSurname" name="spouse_surname" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                                <div><label class="text-slate-600">Spouse First Name</label><input id="profileSpouseFirstName" name="spouse_first_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                                <div><label class="text-slate-600">Spouse Middle Name</label><input id="profileSpouseMiddleName" name="spouse_middle_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                                <div><label class="text-slate-600">Extension Name</label><input id="profileSpouseExtensionName" name="spouse_extension_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                                <div><label class="text-slate-600">Occupation</label><input id="profileSpouseOccupation" name="spouse_occupation" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                                <div><label class="text-slate-600">Employer/Business Name</label><input id="profileSpouseEmployerBusinessName" name="spouse_employer_business_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                                <div class="md:col-span-2"><label class="text-slate-600">Business Address</label><input id="profileSpouseBusinessAddress" name="spouse_business_address" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                                <div><label class="text-slate-600">Telephone No.</label><input id="profileSpouseTelephoneNo" name="spouse_telephone_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                            </div>
                        </section>

                        <section class="space-y-3 border-t border-slate-200 pt-4">
                            <h4 class="text-sm font-semibold text-slate-700">Father Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div><label class="text-slate-600">Surname</label><input id="profileFatherSurname" name="father_surname" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                                <div><label class="text-slate-600">First Name</label><input id="profileFatherFirstName" name="father_first_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                                <div><label class="text-slate-600">Middle Name</label><input id="profileFatherMiddleName" name="father_middle_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                                <div><label class="text-slate-600">Extension Name</label><input id="profileFatherExtensionName" name="father_extension_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                            </div>
                        </section>

                        <section class="space-y-3 border-t border-slate-200 pt-4">
                            <h4 class="text-sm font-semibold text-slate-700">Mother Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div><label class="text-slate-600">Maiden Surname</label><input id="profileMotherSurname" name="mother_surname" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                                <div><label class="text-slate-600">First Name</label><input id="profileMotherFirstName" name="mother_first_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                                <div><label class="text-slate-600">Middle Name</label><input id="profileMotherMiddleName" name="mother_middle_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                                <div><label class="text-slate-600">Extension Name</label><input id="profileMotherExtensionName" name="mother_extension_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                            </div>
                        </section>

                        <section class="space-y-3 border-t border-slate-200 pt-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-semibold text-slate-700">Children</h4>
                                <button type="button" data-add-child-row class="border px-3 py-1 rounded-lg text-sm">Add Child</button>
                            </div>
                            <div id="childrenRows" class="space-y-2">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 child-row">
                                    <div class="md:col-span-8"><input name="children_full_name[]" data-child-name placeholder="Child Full Name" class="border rounded-lg p-2 w-full"></div>
                                    <div class="md:col-span-3"><input type="date" name="children_birth_date[]" data-child-birth-date class="border rounded-lg p-2 w-full"></div>
                                    <div class="md:col-span-1"><button type="button" data-remove-child-row class="border rounded-lg px-2 py-2 w-full">×</button></div>
                                </div>
                            </div>
                        </section>
                    </section>

                    <section data-profile-section="education" class="space-y-6 hidden">
                        <section class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-semibold text-slate-700">Educational Background</h4>
                                <button type="button" data-add-education-row class="border px-3 py-1 rounded-lg text-sm">Add Education Row</button>
                            </div>
                            <div id="educationRows" class="space-y-3">
                                <div class="border rounded-lg p-3 education-row">
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
                                        <div class="md:col-span-2">
                                            <select name="education_level[]" class="border rounded-lg p-2 w-full" data-education-field="education_level">
                                                <option value="elementary">Elementary</option>
                                                <option value="secondary">Secondary</option>
                                                <option value="vocational_trade_course">Vocational / Trade Course</option>
                                                <option value="college">College</option>
                                                <option value="graduate_studies">Graduate Studies</option>
                                            </select>
                                        </div>
                                        <div class="md:col-span-4"><input name="education_school_name[]" data-education-field="school_name" placeholder="Name of School" class="border rounded-lg p-2 w-full"></div>
                                        <div class="md:col-span-3"><input name="education_course_degree[]" data-education-field="degree_course" placeholder="Basic Education / Degree / Course" class="border rounded-lg p-2 w-full"></div>
                                        <div class="md:col-span-1"><input name="education_period_from[]" data-education-field="attendance_from_year" placeholder="From" pattern="^\d{4}$" class="border rounded-lg p-2 w-full"></div>
                                        <div class="md:col-span-1"><input name="education_period_to[]" data-education-field="attendance_to_year" placeholder="To" pattern="^\d{4}$" class="border rounded-lg p-2 w-full"></div>
                                        <div class="md:col-span-1"><button type="button" data-remove-education-row class="border rounded-lg px-2 py-2 w-full">×</button></div>
                                        <div class="md:col-span-4"><input name="education_highest_level_units[]" data-education-field="highest_level_units_earned" placeholder="Highest Level / Units Earned" class="border rounded-lg p-2 w-full"></div>
                                        <div class="md:col-span-3"><input name="education_year_graduated[]" data-education-field="year_graduated" placeholder="Year Graduated" pattern="^\d{4}$" class="border rounded-lg p-2 w-full"></div>
                                        <div class="md:col-span-5"><input name="education_honors_received[]" data-education-field="scholarship_honors_received" placeholder="Scholarship / Academic Honors Received" class="border rounded-lg p-2 w-full"></div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </section>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex items-center justify-between gap-3">
                    <div class="flex gap-2">
                        <button type="button" data-profile-prev class="border px-4 py-2 rounded-lg text-sm">Previous</button>
                        <button type="button" data-profile-next class="border px-4 py-2 rounded-lg text-sm">Next</button>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" data-modal-close="personalInfoProfileModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button id="personalInfoProfileSubmit" type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Submit Recommendation</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="staffChildRowTemplate">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 child-row">
        <div class="md:col-span-8"><input name="children_full_name[]" data-child-name placeholder="Child Full Name" class="border rounded-lg p-2 w-full"></div>
        <div class="md:col-span-3"><input type="date" name="children_birth_date[]" data-child-birth-date class="border rounded-lg p-2 w-full"></div>
        <div class="md:col-span-1"><button type="button" data-remove-child-row class="border rounded-lg px-2 py-2 w-full">×</button></div>
    </div>
</template>

<template id="staffEducationRowTemplate">
    <div class="border rounded-lg p-3 education-row">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
            <div class="md:col-span-2">
                <select name="education_level[]" class="border rounded-lg p-2 w-full" data-education-field="education_level">
                    <option value="elementary">Elementary</option>
                    <option value="secondary">Secondary</option>
                    <option value="vocational_trade_course">Vocational / Trade Course</option>
                    <option value="college">College</option>
                    <option value="graduate_studies">Graduate Studies</option>
                </select>
            </div>
            <div class="md:col-span-4"><input name="education_school_name[]" data-education-field="school_name" placeholder="Name of School" class="border rounded-lg p-2 w-full"></div>
            <div class="md:col-span-3"><input name="education_course_degree[]" data-education-field="degree_course" placeholder="Basic Education / Degree / Course" class="border rounded-lg p-2 w-full"></div>
            <div class="md:col-span-1"><input name="education_period_from[]" data-education-field="attendance_from_year" placeholder="From" pattern="^\d{4}$" class="border rounded-lg p-2 w-full"></div>
            <div class="md:col-span-1"><input name="education_period_to[]" data-education-field="attendance_to_year" placeholder="To" pattern="^\d{4}$" class="border rounded-lg p-2 w-full"></div>
            <div class="md:col-span-1"><button type="button" data-remove-education-row class="border rounded-lg px-2 py-2 w-full">×</button></div>
            <div class="md:col-span-4"><input name="education_highest_level_units[]" data-education-field="highest_level_units_earned" placeholder="Highest Level / Units Earned" class="border rounded-lg p-2 w-full"></div>
            <div class="md:col-span-3"><input name="education_year_graduated[]" data-education-field="year_graduated" placeholder="Year Graduated" pattern="^\d{4}$" class="border rounded-lg p-2 w-full"></div>
            <div class="md:col-span-5"><input name="education_honors_received[]" data-education-field="scholarship_honors_received" placeholder="Scholarship / Academic Honors Received" class="border rounded-lg p-2 w-full"></div>
        </div>
    </div>
</template>

<datalist id="profilePlaceOfBirthList">
    <?php foreach ($placeOfBirthOptions as $placeOfBirthOption): ?>
        <option value="<?= htmlspecialchars((string)$placeOfBirthOption, ENT_QUOTES, 'UTF-8') ?>"></option>
    <?php endforeach; ?>
</datalist>

<datalist id="profileCivilStatusList">
    <?php foreach ($civilStatusOptions as $civilStatusOption): ?>
        <option value="<?= htmlspecialchars((string)$civilStatusOption, ENT_QUOTES, 'UTF-8') ?>"></option>
    <?php endforeach; ?>
</datalist>

<datalist id="profileBloodTypeList">
    <?php foreach ($bloodTypeOptions as $bloodTypeOption): ?>
        <option value="<?= htmlspecialchars((string)$bloodTypeOption, ENT_QUOTES, 'UTF-8') ?>"></option>
    <?php endforeach; ?>
</datalist>

<datalist id="profileCityList">
    <?php foreach ($addressCityOptions as $addressCityOption): ?>
        <option value="<?= htmlspecialchars((string)$addressCityOption, ENT_QUOTES, 'UTF-8') ?>"></option>
    <?php endforeach; ?>
</datalist>

<datalist id="profileProvinceList">
    <?php foreach ($addressProvinceOptions as $addressProvinceOption): ?>
        <option value="<?= htmlspecialchars((string)$addressProvinceOption, ENT_QUOTES, 'UTF-8') ?>"></option>
    <?php endforeach; ?>
</datalist>

<datalist id="profileResidentialBarangayList">
    <?php foreach ($addressBarangayOptions as $addressBarangayOption): ?>
        <option value="<?= htmlspecialchars((string)$addressBarangayOption, ENT_QUOTES, 'UTF-8') ?>"></option>
    <?php endforeach; ?>
</datalist>
<datalist id="profilePermanentBarangayList">
    <?php foreach ($addressBarangayOptions as $addressBarangayOption): ?>
        <option value="<?= htmlspecialchars((string)$addressBarangayOption, ENT_QUOTES, 'UTF-8') ?>"></option>
    <?php endforeach; ?>
</datalist>

<script id="staffAddressLookupData" type="application/json"><?= (string)json_encode([
    'barangayByCity' => $addressBarangaysByCity,
    'zipByCityBarangay' => $addressZipByCityBarangay,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<div id="personalInfoPendingReviewModal" data-modal class="fixed inset-0 z-[60] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoPendingReviewModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-5xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 id="personalInfoPendingReviewTitle" class="text-lg font-semibold text-slate-800">Review Recommendation</h3>
                    <p id="personalInfoPendingReviewSummary" class="text-sm text-slate-500 mt-1">Review proposed changes before submitting to admin.</p>
                </div>
                <button type="button" data-modal-close="personalInfoPendingReviewModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <div class="p-6 overflow-y-auto space-y-4">
                <div class="rounded-xl border border-slate-200 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="text-left px-4 py-3 w-[28%]">Field</th>
                                <th class="text-left px-4 py-3 w-[36%]">Current Value</th>
                                <th class="text-left px-4 py-3 w-[36%]">Proposed Value</th>
                            </tr>
                        </thead>
                        <tbody id="personalInfoPendingReviewPairs" class="divide-y divide-slate-100">
                            <tr>
                                <td class="px-4 py-3 text-slate-500" colspan="3">No field-level changes available.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div>
                    <label for="personalInfoPendingReviewRemarks" class="text-slate-600 text-sm">Remarks for resubmission/edit (optional)</label>
                    <textarea id="personalInfoPendingReviewRemarks" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add remarks before editing and re-submitting recommendation."></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 bg-white flex justify-end gap-3">
                <button type="button" data-modal-close="personalInfoPendingReviewModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Close</button>
                <button type="button" id="personalInfoPendingEditRecommendationBtn" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Edit Recommendation</button>
            </div>
        </div>
    </div>
</div>

<div id="personalInfoAssignmentModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoAssignmentModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Recommend Division and Position</h3>
                <button type="button" data-modal-close="personalInfoAssignmentModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form id="personalInfoAssignmentForm" action="personal-information.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <input type="hidden" name="form_action" value="assign_department_position">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="personalInfoAssignmentPersonId" name="person_id" value="">
                <input type="hidden" id="personalInfoAssignmentEmploymentId" name="employment_id" value="">

                <div>
                    <label class="text-slate-600">Employee</label>
                    <input id="personalInfoAssignmentEmployeeDisplay" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Division</label>
                    <select id="personalInfoAssignmentOffice" name="office_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="">Select division</option>
                        <?php foreach ($assignmentOfficeOptions as $office): ?>
                            <option value="<?= htmlspecialchars((string)$office['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$office['office_name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Position</label>
                    <select id="personalInfoAssignmentPosition" name="position_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="">Select position</option>
                        <?php foreach ($assignmentPositionOptions as $position): ?>
                            <option value="<?= htmlspecialchars((string)$position['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$position['position_title'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="text-slate-600">Recommendation Notes</label>
                    <textarea id="personalInfoAssignmentRecommendationNotes" name="recommendation_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="State why this division/position change is being recommended." required></textarea>
                </div>
                <div class="md:col-span-3 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="personalInfoAssignmentModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" id="personalInfoAssignmentSubmit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Submit Recommendation</button>
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
                <h3 class="text-lg font-semibold text-slate-800">Recommend Employee Status</h3>
                <button type="button" data-modal-close="personalInfoStatusModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form id="personalInfoStatusForm" action="personal-information.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="update_employee_status">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="personalInfoStatusPersonId" name="person_id" value="">
                <input type="hidden" id="personalInfoStatusEmploymentId" name="employment_id" value="">

                <div>
                    <label class="text-slate-600">Employee</label>
                    <input id="personalInfoStatusEmployeeDisplay" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Current Status</label>
                    <input id="personalInfoStatusCurrentDisplay" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Recommended Status</label>
                    <select id="personalInfoStatusNewStatus" name="new_status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="active">Recommend Active</option>
                        <option value="inactive">Recommend Inactive</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Recommendation Notes</label>
                    <textarea id="personalInfoStatusSpecification" name="status_specification" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Indicate recommendation context (resigned, retired, on leave, reassigned, etc.)"></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="personalInfoStatusModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" id="personalInfoStatusSubmit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Submit Recommendation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
