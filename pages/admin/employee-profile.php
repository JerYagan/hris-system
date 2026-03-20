<?php
require_once __DIR__ . '/includes/personal-information/bootstrap.php';
require_once __DIR__ . '/includes/personal-information/actions.php';

$personId = trim((string)(cleanText($_GET['person_id'] ?? null) ?? ''));
$source = strtolower(trim((string)(cleanText($_GET['source'] ?? null) ?? 'personal-information')));
$backLink = $source === 'personal-information-profiles' ? 'personal-information-profiles.php' : 'personal-information.php';
$employeeProfilePartial = strtolower(trim((string)(cleanText($_GET['partial'] ?? null) ?? '')));
$employeeProfileTab = strtolower(trim((string)(cleanText($_GET['tab'] ?? null) ?? 'personal')));
$employeeProfileTab = in_array($employeeProfileTab, ['personal', 'family', 'education'], true) ? $employeeProfileTab : 'personal';

$personalInfoSelectedPersonId = $personId;
$personalInfoProfileRequestedTab = $employeeProfileTab;
$personalInfoDataStage = $employeeProfilePartial === 'tab' ? 'employee-profile-tab' : 'employee-profile-shell';

require_once __DIR__ . '/includes/personal-information/data.php';

$pageTitle = 'Employee Profile | Admin';
$activePage = 'personal-information.php';
$breadcrumbs = ['Personal Information', 'Employee Profile'];

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

$childrenRows = (array)($selectedEmployee['children'] ?? []);

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

$renderEmployeeProfileFamilySection = static function (array $employeeRow, array $childRows): string {
    ob_start();
    ?>
    <section class="space-y-6">
        <section class="space-y-3">
            <h4 class="text-sm font-semibold text-slate-700">Spouse Information</h4>
            <dl class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                <?php foreach ([
                    'Spouse Surname' => (string)($employeeRow['spouse_surname'] ?? ''),
                    'Spouse First Name' => (string)($employeeRow['spouse_first_name'] ?? ''),
                    'Spouse Middle Name' => (string)($employeeRow['spouse_middle_name'] ?? ''),
                    'Extension Name' => (string)($employeeRow['spouse_extension_name'] ?? ''),
                    'Occupation' => (string)($employeeRow['spouse_occupation'] ?? ''),
                    'Employer/Business Name' => (string)($employeeRow['spouse_employer_business_name'] ?? ''),
                    'Business Address' => (string)($employeeRow['spouse_business_address'] ?? ''),
                    'Telephone No.' => (string)($employeeRow['spouse_telephone_no'] ?? ''),
                ] as $label => $value): ?>
                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                        <dt class="text-xs uppercase tracking-wide text-slate-500"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></dt>
                        <dd class="mt-1 text-slate-700"><?= htmlspecialchars(trim($value) !== '' ? $value : 'Not provided', ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </section>

        <section class="space-y-3 border-t border-slate-200 pt-4">
            <h4 class="text-sm font-semibold text-slate-700">Parents</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="rounded-lg border border-slate-200 bg-white p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Father</p>
                    <p class="mt-2 text-slate-700"><?= htmlspecialchars(trim(implode(' ', array_filter([
                        (string)($employeeRow['father_first_name'] ?? ''),
                        (string)($employeeRow['father_middle_name'] ?? ''),
                        (string)($employeeRow['father_surname'] ?? ''),
                        (string)($employeeRow['father_extension_name'] ?? ''),
                    ], static fn ($part): bool => trim((string)$part) !== ''))) ?: 'Not provided', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Mother</p>
                    <p class="mt-2 text-slate-700"><?= htmlspecialchars(trim(implode(' ', array_filter([
                        (string)($employeeRow['mother_first_name'] ?? ''),
                        (string)($employeeRow['mother_middle_name'] ?? ''),
                        (string)($employeeRow['mother_surname'] ?? ''),
                        (string)($employeeRow['mother_extension_name'] ?? ''),
                    ], static fn ($part): bool => trim((string)$part) !== ''))) ?: 'Not provided', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        </section>

        <section class="space-y-3 border-t border-slate-200 pt-4">
            <h4 class="text-sm font-semibold text-slate-700">Children</h4>
            <?php if (empty($childRows)): ?>
                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">No child records provided.</div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($childRows as $childRow): ?>
                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                            <p class="font-medium text-slate-800"><?= htmlspecialchars((string)($childRow['full_name'] ?? 'Unnamed Child'), ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="mt-1 text-xs text-slate-500"><?= htmlspecialchars(trim((string)($childRow['birth_date'] ?? '')) !== '' ? (string)$childRow['birth_date'] : 'Birth date not provided', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </section>
    <?php
    return (string)ob_get_clean();
};

$renderEmployeeProfileEducationSection = static function (array $educationRows, callable $formatEducationLevelLabel, callable $formatRangeLabel): string {
    ob_start();
    ?>
    <section class="space-y-3">
        <h4 class="text-sm font-semibold text-slate-700">Educational Background</h4>
        <?php if (empty($educationRows)): ?>
            <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">No educational background records provided.</div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($educationRows as $educationRow): ?>
                    <div class="rounded-lg border border-slate-200 bg-white p-4">
                        <p class="font-medium text-slate-800"><?= htmlspecialchars($formatEducationLevelLabel((string)($educationRow['education_level'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string)($educationRow['school_name'] ?? 'School not provided'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mt-1 text-xs text-slate-500"><?= htmlspecialchars($formatRangeLabel((string)($educationRow['attendance_from_year'] ?? ''), (string)($educationRow['attendance_to_year'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mt-1 text-xs text-slate-500"><?= htmlspecialchars(trim((string)($educationRow['degree_course'] ?? '')) !== '' ? (string)$educationRow['degree_course'] : 'Degree/Course not provided', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
    return (string)ob_get_clean();
};

if ($employeeProfilePartial === 'tab') {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    if ($selectedEmployee === null) {
        echo '<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Employee profile tab content could not be loaded.</div>';
        exit;
    }

    if ($employeeProfileTab === 'family') {
        echo $renderEmployeeProfileFamilySection((array)$selectedEmployee, $childrenRows);
        exit;
    }

    if ($employeeProfileTab === 'education') {
        echo $renderEmployeeProfileEducationSection($educationRecords, $formatEducationLevelLabel, $formatRangeLabel);
        exit;
    }

    echo '';
    exit;
}

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
                <p class="text-sm text-slate-500 mt-1">Review employee profile information and request changes through the tracked approval workflow instead of editing records directly.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-2 text-xs font-medium text-amber-800">Read-only review</span>
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
                    <h3 class="text-sm font-semibold text-slate-700 mb-3">Deferred Profile Tabs</h3>
                    <p class="text-sm text-slate-600">Family Background and Educational Background are loaded only when their tabs are opened.</p>
                    <p class="mt-2 text-xs text-slate-500">This keeps the initial employee profile shell focused on the visible personal section first.</p>
                </div>
            </div>
        </div>

        <form action="employee-profile.php?person_id=<?= rawurlencode((string)$selectedEmployee['person_id']) ?>&source=<?= rawurlencode($source) ?>" method="POST" id="adminEmployeeProfileForm" class="min-h-0 flex flex-col">
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
                    <div
                        data-profile-lazy-panel="family"
                        data-profile-tab-url="employee-profile.php?person_id=<?= rawurlencode((string)$selectedEmployee['person_id']) ?>&source=<?= rawurlencode($source) ?>&partial=tab&tab=family"
                        class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500"
                    >
                        Family background will load when this tab is opened.
                    </div>
                </section>

                <section data-profile-section="education" class="space-y-6 hidden">
                    <div
                        data-profile-lazy-panel="education"
                        data-profile-tab-url="employee-profile.php?person_id=<?= rawurlencode((string)$selectedEmployee['person_id']) ?>&source=<?= rawurlencode($source) ?>&partial=tab&tab=education"
                        class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500"
                    >
                        Educational background will load when this tab is opened.
                    </div>
                </section>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex items-center justify-between gap-3">
                <div class="flex gap-2">
                    <button type="button" data-profile-prev class="border px-4 py-2 rounded-lg text-sm">Previous</button>
                    <button type="button" data-profile-next class="border px-4 py-2 rounded-lg text-sm">Next</button>
                </div>
                <span class="text-xs text-slate-500">Direct edits are disabled to preserve the approval audit trail.</span>
            </div>
        </form>
    </section>

    <script>
        (function () {
            const profileForm = document.getElementById('adminEmployeeProfileForm');
            const tabButtons = Array.from(document.querySelectorAll('[data-profile-tab-target]'));
            const sections = {
                personal: document.querySelector('[data-profile-section="personal"]'),
                family: document.querySelector('[data-profile-section="family"]'),
                education: document.querySelector('[data-profile-section="education"]')
            };
            const tabOrder = ['personal', 'family', 'education'];
            const loadedProfileTabs = new Set(['personal']);

            const applyEditMode = (enabled) => {
                if (!profileForm) return;

                profileForm.dataset.editMode = enabled ? 'edit' : 'view';

                const controls = Array.from(profileForm.querySelectorAll('input, select, textarea, button'));
                controls.forEach((control) => {
                    if (!(control instanceof HTMLElement)) {
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

            const loadProfileTab = async (key) => {
                if (key === 'personal' || loadedProfileTabs.has(key)) {
                    return;
                }

                const panel = document.querySelector(`[data-profile-lazy-panel="${key}"]`);
                if (!(panel instanceof HTMLElement)) {
                    return;
                }

                const url = panel.getAttribute('data-profile-tab-url') || '';
                if (url === '') {
                    return;
                }

                panel.innerHTML = 'Loading section...';

                try {
                    const response = await fetch(url, {
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error(`Profile tab request failed with status ${response.status}`);
                    }

                    panel.innerHTML = await response.text();
                    loadedProfileTabs.add(key);
                } catch (error) {
                    console.error(error);
                    panel.innerHTML = '<div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">This section could not be loaded. Try opening the tab again.</div>';
                }
            };

            const setActiveTab = async (key) => {
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

                await loadProfileTab(key);
            };

            tabButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const key = button.getAttribute('data-profile-tab-target') || 'personal';
                    setActiveTab(key).catch(console.error);
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
                    setActiveTab(tabOrder[index - 1]).catch(console.error);
                }
            });

            nextButton?.addEventListener('click', () => {
                const current = getActiveTab();
                const index = tabOrder.indexOf(current);
                if (index >= 0 && index < tabOrder.length - 1) {
                    setActiveTab(tabOrder[index + 1]).catch(console.error);
                }
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

            profileForm?.addEventListener('submit', (event) => {
                if (profileForm.dataset.editMode === 'edit') {
                    return;
                }

                event.preventDefault();
            });

            setActiveTab('personal').catch(console.error);
            applyEditMode(false);
        })();
    </script>
<?php endif; ?>
<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
