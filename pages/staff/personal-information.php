<?php
require_once __DIR__ . '/includes/personal-information/bootstrap.php';
require_once __DIR__ . '/includes/personal-information/actions.php';
require_once __DIR__ . '/includes/personal-information/data.php';

$pageTitle = 'Personal Information | Staff';
$activePage = 'personal-information.php';
$breadcrumbs = ['Personal Information'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Personal Information</h1>
    <p class="text-sm text-gray-500">Manage employee profiles and status transitions within your office scope.</p>
</div>

<?php if ($state && $message): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($dataLoadError): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white border rounded-xl p-4">
        <p class="text-xs text-gray-500">Employees in Scope</p>
        <p class="text-2xl font-semibold text-gray-800 mt-1"><?= (int)($personalInfoMetrics['total'] ?? 0) ?></p>
    </div>
    <div class="bg-white border rounded-xl p-4">
        <p class="text-xs text-gray-500">Active</p>
        <p class="text-2xl font-semibold text-emerald-700 mt-1"><?= (int)($personalInfoMetrics['active'] ?? 0) ?></p>
    </div>
    <div class="bg-white border rounded-xl p-4">
        <p class="text-xs text-gray-500">On Leave / Separated</p>
        <p class="text-2xl font-semibold text-amber-700 mt-1"><?= (int)($personalInfoMetrics['on_leave'] ?? 0) + (int)($personalInfoMetrics['separated'] ?? 0) ?></p>
    </div>
</div>

<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Employee Profiles</h2>
        <p class="text-sm text-gray-500 mt-1">Use search and status filter to locate employee records and open profile/status actions.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="staffEmployeeSearch" class="text-sm text-gray-600">Search Requests</label>
            <input id="staffEmployeeSearch" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by name, position, office, email, mobile">
        </div>
        <div>
            <label for="staffEmployeeStatusFilter" class="text-sm text-gray-600">All Statuses</label>
            <select id="staffEmployeeStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="on_leave">On Leave</option>
                <option value="resigned">Resigned</option>
                <option value="retired">Retired</option>
                <option value="terminated">Terminated</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="staffEmployeeTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee Name</th>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Department</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Contact</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($employeeRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="6">No employee records found in your scope.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employeeRows as $row): ?>
                        <tr data-employee-row data-employee-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1">Hired <?= htmlspecialchars((string)($row['hire_date_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['position_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)($row['status_label'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p><?= htmlspecialchars((string)($row['personal_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['mobile_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        data-open-employee-profile-modal
                                        data-person-id="<?= htmlspecialchars((string)($row['person_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-employment-id="<?= htmlspecialchars((string)($row['employment_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-first-name="<?= htmlspecialchars((string)($row['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-middle-name="<?= htmlspecialchars((string)($row['middle_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-surname="<?= htmlspecialchars((string)($row['surname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-name-extension="<?= htmlspecialchars((string)($row['name_extension'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-personal-email="<?= htmlspecialchars((string)($row['personal_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-mobile-no="<?= htmlspecialchars((string)($row['mobile_no'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50"
                                    >
                                        Update Profile
                                    </button>
                                    <button
                                        type="button"
                                        data-open-status-modal
                                        data-person-id="<?= htmlspecialchars((string)($row['person_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-employment-id="<?= htmlspecialchars((string)($row['employment_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status-label="<?= htmlspecialchars((string)($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        class="px-3 py-1.5 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100"
                                    >
                                        Change Status
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="staffEmployeeFilterEmpty" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="6">No records match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<div id="employeeProfileModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-xl rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Update Employee Profile</h3>
            <button type="button" id="employeeProfileModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="employeeProfileForm" method="POST" action="personal-information.php" class="px-6 py-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <input type="hidden" name="form_action" value="update_employee_profile">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="person_id" id="profileModalPersonId" value="">
            <input type="hidden" name="employment_id" id="profileModalEmploymentId" value="">

            <div class="md:col-span-2">
                <label class="text-gray-600">Employee</label>
                <p id="profileModalEmployeeName" class="mt-1 text-sm font-medium text-gray-800">-</p>
            </div>
            <div>
                <label for="profileModalFirstName" class="text-gray-600">First Name</label>
                <input id="profileModalFirstName" name="first_name" type="text" class="w-full mt-1 border rounded-md px-3 py-2" required>
            </div>
            <div>
                <label for="profileModalMiddleName" class="text-gray-600">Middle Name</label>
                <input id="profileModalMiddleName" name="middle_name" type="text" class="w-full mt-1 border rounded-md px-3 py-2">
            </div>
            <div>
                <label for="profileModalSurname" class="text-gray-600">Surname</label>
                <input id="profileModalSurname" name="surname" type="text" class="w-full mt-1 border rounded-md px-3 py-2" required>
            </div>
            <div>
                <label for="profileModalNameExtension" class="text-gray-600">Name Extension</label>
                <input id="profileModalNameExtension" name="name_extension" type="text" class="w-full mt-1 border rounded-md px-3 py-2">
            </div>
            <div>
                <label for="profileModalPersonalEmail" class="text-gray-600">Personal Email</label>
                <input id="profileModalPersonalEmail" name="personal_email" type="email" class="w-full mt-1 border rounded-md px-3 py-2">
            </div>
            <div>
                <label for="profileModalMobileNo" class="text-gray-600">Mobile No.</label>
                <input id="profileModalMobileNo" name="mobile_no" type="text" class="w-full mt-1 border rounded-md px-3 py-2">
            </div>
            <div class="md:col-span-2 flex justify-end gap-3 mt-1">
                <button type="button" id="employeeProfileModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="employeeProfileSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Profile</button>
            </div>
        </form>
    </div>
</div>

<div id="employeeStatusModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Review Employment Status</h3>
            <button type="button" id="employeeStatusModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="employeeStatusForm" method="POST" action="personal-information.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="update_employee_status">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="person_id" id="statusModalPersonId" value="">
            <input type="hidden" name="employment_id" id="statusModalEmploymentId" value="">

            <div>
                <label class="text-gray-600">Employee</label>
                <p id="statusModalEmployeeName" class="mt-1 text-sm font-medium text-gray-800">-</p>
            </div>
            <div>
                <label class="text-gray-600">Current Status</label>
                <p id="statusModalCurrentStatus" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label for="statusModalNewStatus" class="text-gray-600">Decision</label>
                <select id="statusModalNewStatus" name="new_status" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select status</option>
                    <option value="active">Active</option>
                    <option value="on_leave">On Leave</option>
                    <option value="resigned">Resigned</option>
                    <option value="retired">Retired</option>
                    <option value="terminated">Terminated</option>
                </select>
            </div>
            <div>
                <label for="statusModalTransitionNote" class="text-gray-600">Notes</label>
                <textarea id="statusModalTransitionNote" name="transition_note" rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Add context for this status update."></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-1">
                <button type="button" id="employeeStatusModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="employeeStatusSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Decision</button>
            </div>
        </form>
    </div>
</div>

<script src="../../assets/js/staff/personal-information/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
