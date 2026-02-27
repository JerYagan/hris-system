<?php
require_once __DIR__ . '/includes/personal-information/bootstrap.php';
require_once __DIR__ . '/includes/personal-information/actions.php';
require_once __DIR__ . '/includes/personal-information/data.php';

$pageTitle = 'Employee Profile | Admin';
$activePage = 'personal-information.php';
$breadcrumbs = ['Personal Information', 'Employee Profile'];

$personId = trim((string)(cleanText($_GET['person_id'] ?? null) ?? ''));
$source = strtolower(trim((string)(cleanText($_GET['source'] ?? null) ?? 'personal-information')));
$backLink = $source === 'personal-information' ? 'personal-information.php' : 'personal-information.php';

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$selectedEmployee = null;
foreach ($employeeTableRows as $row) {
    if ((string)($row['person_id'] ?? '') !== $personId) {
        continue;
    }
    $selectedEmployee = $row;
    break;
}

$educationRecords = [];
if (is_array($selectedEmployee['educational_backgrounds'] ?? null)) {
    foreach ((array)$selectedEmployee['educational_backgrounds'] as $levelKey => $educationRow) {
        if (!is_array($educationRow)) {
            continue;
        }
        $educationRecords[] = [
            'education_level' => (string)($educationRow['education_level'] ?? $levelKey),
            'school_name' => (string)($educationRow['school_name'] ?? ''),
            'degree_course' => (string)($educationRow['degree_course'] ?? ''),
            'attendance_from_year' => isset($educationRow['attendance_from_year']) ? (string)$educationRow['attendance_from_year'] : '',
            'attendance_to_year' => isset($educationRow['attendance_to_year']) ? (string)$educationRow['attendance_to_year'] : '',
            'highest_level_units_earned' => (string)($educationRow['highest_level_units_earned'] ?? ''),
            'year_graduated' => isset($educationRow['year_graduated']) ? (string)$educationRow['year_graduated'] : '',
            'scholarship_honors_received' => (string)($educationRow['scholarship_honors_received'] ?? ''),
        ];
    }
}

if (empty($educationRecords)) {
    $educationRecords[] = [
        'education_level' => 'elementary',
        'school_name' => '',
        'degree_course' => '',
        'attendance_from_year' => '',
        'attendance_to_year' => '',
        'highest_level_units_earned' => '',
        'year_graduated' => '',
        'scholarship_honors_received' => '',
    ];
}

$childrenRows = (array)($selectedEmployee['children'] ?? []);
if (empty($childrenRows)) {
    $childrenRows[] = [
        'full_name' => '',
        'birth_date' => '',
    ];
}

$workExperienceRows = array_values(array_filter((array)($selectedEmployee['work_experiences'] ?? []), static function ($row): bool {
    return is_array($row);
}));

$formatDateLabel = static function (?string $dateValue): string {
    $dateText = trim((string)$dateValue);
    if ($dateText === '') {
        return 'Not provided';
    }

    $timestamp = strtotime($dateText);
    if ($timestamp === false) {
        return $dateText;
    }

    return date('M d, Y', $timestamp);
};

$formatRangeLabel = static function (?string $fromValue, ?string $toValue): string {
    $from = trim((string)$fromValue);
    $to = trim((string)$toValue);

    if ($from === '' && $to === '') {
        return 'Date not provided';
    }

    if ($from === '') {
        return $to;
    }

    if ($to === '') {
        return $from . ' - Present';
    }

    return $from . ' - ' . $to;
};

$formatEducationLevelLabel = static function (?string $levelValue): string {
    $normalized = strtolower(trim((string)$levelValue));

    return match ($normalized) {
        'elementary' => 'Elementary',
        'secondary' => 'Secondary',
        'vocational_trade_course' => 'Vocational / Trade Course',
        'college' => 'College',
        'graduate_studies' => 'Graduate Studies',
        default => $normalized !== '' ? ucwords(str_replace('_', ' ', $normalized)) : 'Not provided',
    };
};

$profilePhotoUrl = trim((string)($selectedEmployee['profile_photo_url'] ?? ''));
$employmentStatusLabel = trim((string)($selectedEmployee['status_label'] ?? 'Inactive'));
$profileHighlights = [
    ['label' => 'Division', 'value' => (string)($selectedEmployee['department'] ?? 'Unassigned Division')],
    ['label' => 'Position', 'value' => (string)($selectedEmployee['position'] ?? 'Unassigned Position')],
    ['label' => 'Employee ID', 'value' => (string)($selectedEmployee['agency_employee_no'] ?? 'Not provided')],
    ['label' => 'Status', 'value' => $employmentStatusLabel],
];

$summaryItems = [
    ['label' => 'Date of Birth', 'value' => $formatDateLabel((string)($selectedEmployee['date_of_birth'] ?? ''))],
    ['label' => 'Place of Birth', 'value' => (string)($selectedEmployee['place_of_birth'] ?? 'Not provided')],
    ['label' => 'Civil Status', 'value' => (string)($selectedEmployee['civil_status'] ?? 'Not provided')],
    ['label' => 'Sex', 'value' => (string)($selectedEmployee['sex_at_birth'] ?? 'Not provided')],
    ['label' => 'Citizenship', 'value' => (string)($selectedEmployee['citizenship'] ?? 'Not provided')],
    ['label' => 'Contact', 'value' => (string)($selectedEmployee['mobile'] ?? 'Not provided')],
    ['label' => 'Email', 'value' => (string)($selectedEmployee['email'] ?? 'Not provided')],
    ['label' => 'Address', 'value' => trim((string)($selectedEmployee['residential_barangay'] ?? '') . ', ' . (string)($selectedEmployee['residential_city_municipality'] ?? '') . ', ' . (string)($selectedEmployee['residential_province'] ?? ''))],
];

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

<?php if ($selectedEmployee === null): ?>
    <section class="bg-white border border-slate-200 rounded-2xl p-6">
        <h1 class="text-xl font-semibold text-slate-800">Employee Profile Not Found</h1>
        <p class="text-sm text-slate-500 mt-2">The selected employee record does not exist or is no longer accessible.</p>
        <a href="<?= htmlspecialchars($backLink, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex mt-4 items-center gap-2 px-4 py-2 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to Personal Information
        </a>
    </section>
<?php else: ?>
    <section class="bg-white border border-slate-200 rounded-2xl mb-6">
        <div class="px-6 py-4 flex items-center justify-between gap-3 border-b border-slate-200">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Employee Profile</h2>
                <p class="text-sm text-slate-500 mt-1">Review profile information first, then click Edit Profile to modify fields.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" id="adminEmployeeEnableEdit" class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800 text-sm">
                    <span class="material-symbols-outlined text-[18px]">edit</span>
                    Edit Profile
                </button>
                <button type="button" id="adminEmployeeCancelEdit" class="hidden inline-flex items-center gap-2 px-3 py-2 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                    Cancel Edit
                </button>
                <a href="<?= htmlspecialchars($backLink, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-2 px-3 py-2 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    Back
                </a>
            </div>
        </div>

        <div class="px-6 py-5 border-b border-slate-200 bg-slate-50/70">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <?php if ($profilePhotoUrl !== ''): ?>
                        <img src="<?= htmlspecialchars($profilePhotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Profile photo" class="h-16 w-16 rounded-full object-cover border border-slate-200">
                    <?php else: ?>
                        <div class="h-16 w-16 rounded-full bg-slate-200 text-slate-700 font-semibold text-xl grid place-items-center">
                            <?= htmlspecialchars(substr((string)($selectedEmployee['first_name'] ?? 'E'), 0, 1) . substr((string)($selectedEmployee['surname'] ?? 'M'), 0, 1), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-lg font-semibold text-slate-800"><?= htmlspecialchars((string)($selectedEmployee['full_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-sm text-slate-600 mt-1">Comprehensive profile view for admin review and approval decisions.</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 w-full md:w-auto">
                    <?php foreach ($profileHighlights as $highlight): ?>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 min-w-[130px]">
                            <p class="text-[11px] uppercase tracking-wide text-slate-500"><?= htmlspecialchars((string)$highlight['label'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-sm font-medium text-slate-800 mt-1"><?= htmlspecialchars((string)$highlight['value'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div class="rounded-lg border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-slate-700 mb-3">Personal Snapshot</h3>
                    <dl class="space-y-2">
                        <?php foreach ($summaryItems as $summaryItem): ?>
                            <?php $value = trim((string)($summaryItem['value'] ?? '')); ?>
                            <div class="grid grid-cols-12 gap-2">
                                <dt class="col-span-5 text-xs text-slate-500"><?= htmlspecialchars((string)$summaryItem['label'], ENT_QUOTES, 'UTF-8') ?></dt>
                                <dd class="col-span-7 text-sm text-slate-700"><?= htmlspecialchars($value !== '' ? $value : 'Not provided', ENT_QUOTES, 'UTF-8') ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-slate-700 mb-3">Employment History</h3>
                    <?php if (empty($workExperienceRows)): ?>
                        <p class="text-sm text-slate-500">No employment history records yet.</p>
                    <?php else: ?>
                        <div class="space-y-3 max-h-56 overflow-auto pr-1">
                            <?php foreach ($workExperienceRows as $experienceRow): ?>
                                <div class="border border-slate-200 rounded-lg p-3">
                                    <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars((string)($experienceRow['position_title'] ?? 'Position not provided'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs text-slate-600 mt-1"><?= htmlspecialchars((string)($experienceRow['office_company'] ?? 'Company/Office not provided'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($formatRangeLabel((string)($experienceRow['inclusive_date_from'] ?? ''), (string)($experienceRow['inclusive_date_to'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-slate-700 mb-3">Educational Background</h3>
                    <?php if (empty($educationRecords)): ?>
                        <p class="text-sm text-slate-500">No educational background records yet.</p>
                    <?php else: ?>
                        <div class="space-y-3 max-h-56 overflow-auto pr-1">
                            <?php foreach ($educationRecords as $educationRow): ?>
                                <div class="border border-slate-200 rounded-lg p-3">
                                    <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($formatEducationLevelLabel((string)($educationRow['education_level'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs text-slate-600 mt-1"><?= htmlspecialchars((string)($educationRow['school_name'] ?? 'School not provided'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($formatRangeLabel((string)($educationRow['attendance_from_year'] ?? ''), (string)($educationRow['attendance_to_year'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <form action="employee-profile.php?person_id=<?= rawurlencode((string)$selectedEmployee['person_id']) ?>&source=personal-information" method="POST" id="adminEmployeeProfileForm" class="min-h-0 flex flex-col">
            <input type="hidden" name="form_action" value="save_profile">
            <input type="hidden" name="profile_action" value="edit">
            <input type="hidden" name="person_id" value="<?= htmlspecialchars((string)$selectedEmployee['person_id'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="px-6 pt-4 border-b border-slate-200">
                <div class="grid grid-cols-3 text-sm border rounded-lg overflow-hidden">
                    <button type="button" data-profile-tab-target="personal" class="profile-tab px-3 py-2 bg-slate-50 border-b-2 border-daGreen text-daGreen font-medium">I. Personal Information</button>
                    <button type="button" data-profile-tab-target="family" class="profile-tab px-3 py-2 bg-white border-b-2 border-transparent text-slate-600">II. Family Background</button>
                    <button type="button" data-profile-tab-target="education" class="profile-tab px-3 py-2 bg-white border-b-2 border-transparent text-slate-600">III. Educational Background</button>
                </div>
                <p class="pt-2 pb-4 text-sm text-slate-500"><?= htmlspecialchars((string)$selectedEmployee['full_name'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div class="p-6 space-y-6 text-sm">
                <section data-profile-section="personal" class="space-y-6">
                    <section class="space-y-3">
                        <h4 class="text-sm font-semibold text-slate-700">Basic Identity</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div><label class="text-slate-600">First Name</label><input name="first_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['first_name'], ENT_QUOTES, 'UTF-8') ?>" required></div>
                            <div><label class="text-slate-600">Middle Name</label><input name="middle_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['middle_name'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Last Name</label><input name="surname" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['surname'], ENT_QUOTES, 'UTF-8') ?>" required></div>
                            <div><label class="text-slate-600">Name Extension</label><input name="name_extension" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['name_extension'], ENT_QUOTES, 'UTF-8') ?>"></div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Demographics</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div><label class="text-slate-600">Date of Birth</label><input name="date_of_birth" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['date_of_birth'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="md:col-span-2"><label class="text-slate-600">Place of Birth</label><input name="place_of_birth" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['place_of_birth'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div>
                                <label class="text-slate-600">Sex at Birth</label>
                                <select name="sex_at_birth" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                                    <option value="">Select sex</option>
                                    <option value="male" <?= strtolower((string)$selectedEmployee['sex_at_birth']) === 'male' ? 'selected' : '' ?>>Male</option>
                                    <option value="female" <?= strtolower((string)$selectedEmployee['sex_at_birth']) === 'female' ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                            <div><label class="text-slate-600">Civil Status</label><input name="civil_status" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['civil_status'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Height (m)</label><input name="height_m" type="number" min="0" step="0.01" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['height_m'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Weight (kg)</label><input name="weight_kg" type="number" min="0" step="0.01" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['weight_kg'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Blood Type</label><input name="blood_type" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['blood_type'], ENT_QUOTES, 'UTF-8') ?>"></div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Citizenship</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><label class="text-slate-600">Citizenship</label><input name="citizenship" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['citizenship'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="md:col-span-2"><label class="text-slate-600">Dual Citizenship Country</label><input name="dual_citizenship_country" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['dual_citizenship_country'], ENT_QUOTES, 'UTF-8') ?>"></div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Residential Address</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div><label class="text-slate-600">House No.</label><input name="residential_house_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['residential_house_no'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="md:col-span-2"><label class="text-slate-600">Street</label><input name="residential_street" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['residential_street'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Barangay</label><input name="residential_barangay" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['residential_barangay'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Subdivision</label><input name="residential_subdivision" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['residential_subdivision'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">City/Municipality</label><input name="residential_city_municipality" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['residential_city_municipality'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Province</label><input name="residential_province" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['residential_province'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">ZIP Code</label><input name="residential_zip_code" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['residential_zip_code'], ENT_QUOTES, 'UTF-8') ?>"></div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Permanent Address</h4>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input id="copyResidentialAddress" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Same as residential address</span>
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div><label class="text-slate-600">House No.</label><input name="permanent_house_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['permanent_house_no'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="md:col-span-2"><label class="text-slate-600">Street</label><input name="permanent_street" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['permanent_street'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Barangay</label><input name="permanent_barangay" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['permanent_barangay'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Subdivision</label><input name="permanent_subdivision" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['permanent_subdivision'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">City/Municipality</label><input name="permanent_city_municipality" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['permanent_city_municipality'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Province</label><input name="permanent_province" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['permanent_province'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">ZIP Code</label><input name="permanent_zip_code" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['permanent_zip_code'], ENT_QUOTES, 'UTF-8') ?>"></div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Government IDs</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><label class="text-slate-600">UMID ID No.</label><input name="umid_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['umid_no'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">PAG-IBIG ID No.</label><input name="pagibig_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['pagibig_no'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">PHILHEALTH No.</label><input name="philhealth_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['philhealth_no'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">PhilSys Number (PSN)</label><input name="psn_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['psn_no'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">TIN No.</label><input name="tin_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['tin_no'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Agency Employee No.</label><input name="agency_employee_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['agency_employee_no'], ENT_QUOTES, 'UTF-8') ?>"></div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Contact Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><label class="text-slate-600">Telephone Number</label><input name="telephone_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['telephone_no'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Mobile Number</label><input name="mobile_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['mobile'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Email Address</label><input name="email" type="email" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['email'], ENT_QUOTES, 'UTF-8') ?>"></div>
                        </div>
                    </section>
                </section>

                <section data-profile-section="family" class="space-y-6 hidden">
                    <section class="space-y-3">
                        <h4 class="text-sm font-semibold text-slate-700">Spouse Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div><label class="text-slate-600">Spouse Surname</label><input name="spouse_surname" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['spouse_surname'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Spouse First Name</label><input name="spouse_first_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['spouse_first_name'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Spouse Middle Name</label><input name="spouse_middle_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['spouse_middle_name'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Extension Name</label><input name="spouse_extension_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['spouse_extension_name'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Occupation</label><input name="spouse_occupation" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['spouse_occupation'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Employer/Business Name</label><input name="spouse_employer_business_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['spouse_employer_business_name'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="md:col-span-2"><label class="text-slate-600">Business Address</label><input name="spouse_business_address" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['spouse_business_address'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Telephone No.</label><input name="spouse_telephone_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['spouse_telephone_no'], ENT_QUOTES, 'UTF-8') ?>"></div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Father Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div><label class="text-slate-600">Surname</label><input name="father_surname" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['father_surname'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">First Name</label><input name="father_first_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['father_first_name'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Middle Name</label><input name="father_middle_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['father_middle_name'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Extension Name</label><input name="father_extension_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['father_extension_name'], ENT_QUOTES, 'UTF-8') ?>"></div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Mother Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div><label class="text-slate-600">Maiden Surname</label><input name="mother_surname" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['mother_surname'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">First Name</label><input name="mother_first_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['mother_first_name'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Middle Name</label><input name="mother_middle_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['mother_middle_name'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div><label class="text-slate-600">Extension Name</label><input name="mother_extension_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)$selectedEmployee['mother_extension_name'], ENT_QUOTES, 'UTF-8') ?>"></div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-slate-700">Children</h4>
                            <button type="button" id="addChildRowButton" class="border px-3 py-1 rounded-lg text-sm">Add Child</button>
                        </div>
                        <div id="childrenRows" class="space-y-2">
                            <?php foreach ($childrenRows as $childRow): ?>
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 child-row">
                                    <div class="md:col-span-8"><input name="children_full_name[]" placeholder="Child Full Name" class="border rounded-lg p-2 w-full" value="<?= htmlspecialchars((string)($childRow['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                                    <div class="md:col-span-3"><input type="date" name="children_birth_date[]" class="border rounded-lg p-2 w-full" value="<?= htmlspecialchars((string)($childRow['birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                                    <div class="md:col-span-1"><button type="button" data-remove-child-row class="border rounded-lg px-2 py-2 w-full">×</button></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </section>

                <section data-profile-section="education" class="space-y-6 hidden">
                    <section class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-slate-700">Educational Background</h4>
                            <button type="button" id="addEducationRowButton" class="border px-3 py-1 rounded-lg text-sm">Add Education Row</button>
                        </div>
                        <div id="educationRows" class="space-y-3">
                            <?php foreach ($educationRecords as $educationRow): ?>
                                <div class="border rounded-lg p-3 education-row">
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
                                        <div class="md:col-span-2">
                                            <select name="education_level[]" class="border rounded-lg p-2 w-full" data-education-field="education_level">
                                                <?php $level = strtolower(trim((string)($educationRow['education_level'] ?? 'elementary'))); ?>
                                                <option value="elementary" <?= $level === 'elementary' ? 'selected' : '' ?>>Elementary</option>
                                                <option value="secondary" <?= $level === 'secondary' ? 'selected' : '' ?>>Secondary</option>
                                                <option value="vocational_trade_course" <?= $level === 'vocational_trade_course' ? 'selected' : '' ?>>Vocational / Trade Course</option>
                                                <option value="college" <?= $level === 'college' ? 'selected' : '' ?>>College</option>
                                                <option value="graduate_studies" <?= $level === 'graduate_studies' ? 'selected' : '' ?>>Graduate Studies</option>
                                            </select>
                                        </div>
                                        <div class="md:col-span-4"><input name="education_school_name[]" data-education-field="school_name" placeholder="Name of School" class="border rounded-lg p-2 w-full" value="<?= htmlspecialchars((string)($educationRow['school_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                                        <div class="md:col-span-3"><input name="education_course_degree[]" data-education-field="degree_course" placeholder="Basic Education / Degree / Course" class="border rounded-lg p-2 w-full" value="<?= htmlspecialchars((string)($educationRow['degree_course'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                                        <div class="md:col-span-1"><input name="education_period_from[]" data-education-field="attendance_from_year" placeholder="From" pattern="^\d{4}$" class="border rounded-lg p-2 w-full" value="<?= htmlspecialchars((string)($educationRow['attendance_from_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                                        <div class="md:col-span-1"><input name="education_period_to[]" data-education-field="attendance_to_year" placeholder="To" pattern="^\d{4}$" class="border rounded-lg p-2 w-full" value="<?= htmlspecialchars((string)($educationRow['attendance_to_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                                        <div class="md:col-span-1"><button type="button" data-remove-education-row class="border rounded-lg px-2 py-2 w-full">×</button></div>
                                        <div class="md:col-span-4"><input name="education_highest_level_units[]" data-education-field="highest_level_units_earned" placeholder="Highest Level / Units Earned" class="border rounded-lg p-2 w-full" value="<?= htmlspecialchars((string)($educationRow['highest_level_units_earned'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                                        <div class="md:col-span-3"><input name="education_year_graduated[]" data-education-field="year_graduated" placeholder="Year Graduated" pattern="^\d{4}$" class="border rounded-lg p-2 w-full" value="<?= htmlspecialchars((string)($educationRow['year_graduated'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                                        <div class="md:col-span-5"><input name="education_honors_received[]" data-education-field="scholarship_honors_received" placeholder="Scholarship / Academic Honors Received" class="border rounded-lg p-2 w-full" value="<?= htmlspecialchars((string)($educationRow['scholarship_honors_received'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </section>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex items-center justify-between gap-3">
                <div class="flex gap-2">
                    <button type="button" data-profile-prev class="border px-4 py-2 rounded-lg text-sm">Previous</button>
                    <button type="button" data-profile-next class="border px-4 py-2 rounded-lg text-sm">Next</button>
                </div>
                <button id="adminEmployeeProfileSubmit" type="submit" class="hidden px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Profile</button>
            </div>
        </form>
    </section>

    <template id="childRowTemplate">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-2 child-row">
            <div class="md:col-span-8"><input name="children_full_name[]" placeholder="Child Full Name" class="border rounded-lg p-2 w-full"></div>
            <div class="md:col-span-3"><input type="date" name="children_birth_date[]" class="border rounded-lg p-2 w-full"></div>
            <div class="md:col-span-1"><button type="button" data-remove-child-row class="border rounded-lg px-2 py-2 w-full">×</button></div>
        </div>
    </template>

    <template id="educationRowTemplate">
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

    <script>
        (function () {
            const profileForm = document.getElementById('adminEmployeeProfileForm');
            const enableEditButton = document.getElementById('adminEmployeeEnableEdit');
            const cancelEditButton = document.getElementById('adminEmployeeCancelEdit');
            const submitButton = document.getElementById('adminEmployeeProfileSubmit');
            const tabButtons = Array.from(document.querySelectorAll('[data-profile-tab-target]'));
            const sections = {
                personal: document.querySelector('[data-profile-section="personal"]'),
                family: document.querySelector('[data-profile-section="family"]'),
                education: document.querySelector('[data-profile-section="education"]')
            };
            const tabOrder = ['personal', 'family', 'education'];

            const applyEditMode = (enabled) => {
                if (!profileForm) return;

                profileForm.dataset.editMode = enabled ? 'edit' : 'view';
                submitButton?.classList.toggle('hidden', !enabled);
                enableEditButton?.classList.toggle('hidden', enabled);
                cancelEditButton?.classList.toggle('hidden', !enabled);

                const controls = Array.from(profileForm.querySelectorAll('input, select, textarea, button'));
                controls.forEach((control) => {
                    if (!(control instanceof HTMLElement)) {
                        return;
                    }

                    if (control === submitButton || control === enableEditButton || control === cancelEditButton) {
                        return;
                    }

                    if (control.matches('input[type="hidden"]')) {
                        return;
                    }

                    if (control.hasAttribute('data-profile-tab-target') || control.hasAttribute('data-profile-prev') || control.hasAttribute('data-profile-next')) {
                        control.toggleAttribute('disabled', false);
                        return;
                    }

                    if (control instanceof HTMLButtonElement) {
                        control.disabled = !enabled;
                        control.classList.toggle('opacity-50', !enabled);
                        control.classList.toggle('cursor-not-allowed', !enabled);
                        return;
                    }

                    if (control instanceof HTMLInputElement || control instanceof HTMLSelectElement || control instanceof HTMLTextAreaElement) {
                        control.disabled = !enabled;
                        control.classList.toggle('bg-slate-50', !enabled);
                    }
                });
            };

            const setActiveTab = (key) => {
                tabButtons.forEach((button) => {
                    const isActive = button.getAttribute('data-profile-tab-target') === key;
                    button.classList.toggle('bg-slate-50', isActive);
                    button.classList.toggle('border-daGreen', isActive);
                    button.classList.toggle('text-daGreen', isActive);
                    button.classList.toggle('font-medium', isActive);
                    button.classList.toggle('bg-white', !isActive);
                    button.classList.toggle('border-transparent', !isActive);
                    button.classList.toggle('text-slate-600', !isActive);
                });

                Object.entries(sections).forEach(([sectionKey, sectionNode]) => {
                    if (!sectionNode) return;
                    sectionNode.classList.toggle('hidden', sectionKey !== key);
                });
            };

            tabButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const key = button.getAttribute('data-profile-tab-target') || 'personal';
                    setActiveTab(key);
                });
            });

            const previousButton = document.querySelector('[data-profile-prev]');
            const nextButton = document.querySelector('[data-profile-next]');

            const getActiveTab = () => {
                const active = tabButtons.find((button) => button.classList.contains('border-daGreen'));
                return active ? (active.getAttribute('data-profile-tab-target') || 'personal') : 'personal';
            };

            previousButton?.addEventListener('click', () => {
                const current = getActiveTab();
                const index = tabOrder.indexOf(current);
                if (index > 0) {
                    setActiveTab(tabOrder[index - 1]);
                }
            });

            nextButton?.addEventListener('click', () => {
                const current = getActiveTab();
                const index = tabOrder.indexOf(current);
                if (index >= 0 && index < tabOrder.length - 1) {
                    setActiveTab(tabOrder[index + 1]);
                }
            });

            const childrenRows = document.getElementById('childrenRows');
            const childRowTemplate = document.getElementById('childRowTemplate');
            const addChildRowButton = document.getElementById('addChildRowButton');

            const ensureAtLeastOneChildRow = () => {
                if (!childrenRows) return;
                const rows = childrenRows.querySelectorAll('.child-row');
                if (rows.length === 0 && childRowTemplate) {
                    childrenRows.appendChild(childRowTemplate.content.firstElementChild.cloneNode(true));
                }
            };

            addChildRowButton?.addEventListener('click', () => {
                if (!childrenRows || !childRowTemplate) return;
                childrenRows.appendChild(childRowTemplate.content.firstElementChild.cloneNode(true));
            });

            childrenRows?.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof Element)) return;
                const removeButton = target.closest('[data-remove-child-row]');
                if (!removeButton) return;
                const row = removeButton.closest('.child-row');
                row?.remove();
                ensureAtLeastOneChildRow();
            });

            const educationRows = document.getElementById('educationRows');
            const educationRowTemplate = document.getElementById('educationRowTemplate');
            const addEducationRowButton = document.getElementById('addEducationRowButton');

            const ensureAtLeastOneEducationRow = () => {
                if (!educationRows) return;
                const rows = educationRows.querySelectorAll('.education-row');
                if (rows.length === 0 && educationRowTemplate) {
                    educationRows.appendChild(educationRowTemplate.content.firstElementChild.cloneNode(true));
                }
            };

            addEducationRowButton?.addEventListener('click', () => {
                if (!educationRows || !educationRowTemplate) return;
                educationRows.appendChild(educationRowTemplate.content.firstElementChild.cloneNode(true));
            });

            educationRows?.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof Element)) return;
                const removeButton = target.closest('[data-remove-education-row]');
                if (!removeButton) return;
                const row = removeButton.closest('.education-row');
                row?.remove();
                ensureAtLeastOneEducationRow();
            });

            const copyResidentialAddress = document.getElementById('copyResidentialAddress');
            const field = (name) => document.querySelector(`[name="${name}"]`);
            copyResidentialAddress?.addEventListener('change', () => {
                if (!(copyResidentialAddress instanceof HTMLInputElement) || !copyResidentialAddress.checked) return;

                const mappings = [
                    ['residential_house_no', 'permanent_house_no'],
                    ['residential_street', 'permanent_street'],
                    ['residential_subdivision', 'permanent_subdivision'],
                    ['residential_barangay', 'permanent_barangay'],
                    ['residential_city_municipality', 'permanent_city_municipality'],
                    ['residential_province', 'permanent_province'],
                    ['residential_zip_code', 'permanent_zip_code']
                ];

                mappings.forEach(([fromName, toName]) => {
                    const from = field(fromName);
                    const to = field(toName);
                    if (from instanceof HTMLInputElement && to instanceof HTMLInputElement) {
                        to.value = from.value;
                    }
                });
            });

            enableEditButton?.addEventListener('click', () => {
                applyEditMode(true);
            });

            cancelEditButton?.addEventListener('click', () => {
                window.location.reload();
            });

            profileForm?.addEventListener('submit', (event) => {
                if (profileForm.dataset.editMode === 'edit') {
                    return;
                }

                event.preventDefault();
            });

            setActiveTab('personal');
            ensureAtLeastOneChildRow();
            ensureAtLeastOneEducationRow();
            applyEditMode(false);
        })();
    </script>
<?php endif; ?>
<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
