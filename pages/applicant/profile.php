<?php
require_once __DIR__ . '/includes/profile/bootstrap.php';
$_GET['partial'] = $_GET['partial'] ?? null;
$requestedPartial = (string)(cleanText($_GET['partial'] ?? null) ?? '');
$editMode = isset($_GET['edit']) && $_GET['edit'] === 'true';
$profileShouldLoadDeferredSections = $requestedPartial === 'secondary-sections';
require_once __DIR__ . '/includes/profile/actions.php';
require_once __DIR__ . '/includes/profile/data.php';

$csrfToken = ensureCsrfToken();
$profileSpouses = is_array($profileSpouses ?? null) ? $profileSpouses : [];
$profileEducations = is_array($profileEducations ?? null) ? $profileEducations : [];
$profileWorkExperiences = is_array($profileWorkExperiences ?? null) ? $profileWorkExperiences : [];
$uploadedFiles = is_array($uploadedFiles ?? null) ? $uploadedFiles : [];

$editableSpouseRows = !empty($profileSpouses) ? array_values($profileSpouses) : [[]];
$editableEducationRows = !empty($profileEducations) ? array_values($profileEducations) : [[]];
$editableWorkExperienceRows = !empty($profileWorkExperiences) ? array_values($profileWorkExperiences) : [[
    'position_title' => '',
    'office_company' => '',
    'inclusive_date_from' => '',
    'inclusive_date_to' => '',
    'achievements' => '',
]];

$profileNameForInitials = trim((string)($profileData['full_name'] ?? 'Applicant User'));
$profileNameParts = preg_split('/\s+/', $profileNameForInitials) ?: [];
$profileInitials = strtoupper(substr((string)($profileNameParts[0] ?? 'A'), 0, 1) . substr((string)($profileNameParts[count($profileNameParts) - 1] ?? 'P'), 0, 1));

$pageTitle = 'Profile | DA HRIS';
$activePage = 'profile.php';
$breadcrumbs = $editMode ? ['Profile', 'Edit'] : ['Profile'];
$retryUrl = htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? 'profile.php'), ENT_QUOTES, 'UTF-8');
$passwordModalTarget = (string)(cleanText($_GET['password_modal'] ?? null) ?? '');
$deferredSectionsUrl = 'profile.php?' . http_build_query([
    'partial' => 'secondary-sections',
    'edit' => $editMode ? 'true' : null,
]);

$renderDeferredProfileViewSections = static function () use ($profileEducations, $profileWorkExperiences): void {
?>
<section class="mb-6 rounded-xl border bg-white">
    <header class="border-b px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Educational Background</h2>
    </header>
    <div class="p-4 text-sm sm:p-6">
        <?php if (empty($profileEducations)): ?>
            <p class="text-gray-600">No education records added yet.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($profileEducations as $education): ?>
                    <article class="rounded-lg border bg-gray-50 p-4">
                        <p class="font-medium text-gray-800"><?= htmlspecialchars(ucwords((string)($education['education_level'] ?? '')), ENT_QUOTES, 'UTF-8') ?: 'Education Entry' ?></p>
                        <p class="mt-1 text-gray-600">School: <?= htmlspecialchars((string)($education['school_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-gray-600">Course / Degree: <?= htmlspecialchars((string)($education['course_degree'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-gray-600">Year Graduated: <?= htmlspecialchars((string)($education['year_graduated'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="mb-6 rounded-xl border bg-white">
    <header class="border-b px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Work Experience</h2>
    </header>
    <div class="p-4 text-sm sm:p-6">
        <?php if (empty($profileWorkExperiences)): ?>
            <p class="text-gray-600">No work experience records added yet.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($profileWorkExperiences as $workExperience): ?>
                    <?php
                        $startDateRaw = trim((string)($workExperience['inclusive_date_from'] ?? ''));
                        $endDateRaw = trim((string)($workExperience['inclusive_date_to'] ?? ''));
                        $startDateDisplay = $startDateRaw !== '' ? date('M j, Y', strtotime($startDateRaw)) : '-';
                        $endDateDisplay = $endDateRaw !== '' ? date('M j, Y', strtotime($endDateRaw)) : 'Present';
                    ?>
                    <article class="rounded-lg border bg-gray-50 p-4">
                        <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($workExperience['position_title'] ?? 'Work Experience Entry'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mt-1 text-gray-600">Company: <?= htmlspecialchars((string)($workExperience['office_company'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-gray-600">Inclusive Dates: <?= htmlspecialchars($startDateDisplay, ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($endDateDisplay, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-gray-600">Responsibilities: <?= htmlspecialchars((string)($workExperience['achievements'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php
};

$renderDeferredProfileEditSections = static function () use ($editableEducationRows, $editableWorkExperienceRows): void {
?>
<section class="rounded-xl border bg-white">
    <header class="border-b px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Educational Background</h2>
    </header>

    <div class="space-y-4 p-4 text-sm sm:p-6">
        <div id="educationRows" class="space-y-4">
        <?php foreach ($editableEducationRows as $educationIndex => $educationRow): ?>
            <div class="rounded-lg border bg-gray-50 p-4 education-row">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Education Entry</p>
                    <button type="button" class="education-remove rounded-md border px-2 py-1 text-xs text-gray-600 hover:bg-gray-100 <?= $educationIndex === 0 ? 'hidden' : '' ?>">Remove</button>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="text-gray-500">Level</label>
                    <?php $educationLevel = strtolower((string)($educationRow['education_level'] ?? '')); ?>
                    <select name="education_level[]" class="mt-1 w-full rounded-md border px-3 py-2">
                        <option value="">Select</option>
                        <?php foreach (['elementary' => 'Elementary', 'secondary' => 'Secondary', 'vocational' => 'Vocational', 'college' => 'College', 'graduate' => 'Graduate / Post Graduate'] as $levelValue => $levelLabel): ?>
                            <option value="<?= htmlspecialchars($levelValue, ENT_QUOTES, 'UTF-8') ?>" <?= $educationLevel === $levelValue ? 'selected' : '' ?>><?= htmlspecialchars($levelLabel, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-gray-500">School</label>
                    <input type="text" name="education_school_name[]" value="<?= htmlspecialchars((string)($educationRow['school_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                </div>
                <div>
                    <label class="text-gray-500">Course / Degree</label>
                    <input type="text" name="education_course_degree[]" value="<?= htmlspecialchars((string)($educationRow['course_degree'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                </div>
                <div>
                    <label class="text-gray-500">Year Graduated</label>
                    <input type="text" name="education_year_graduated[]" value="<?= htmlspecialchars((string)($educationRow['year_graduated'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2 js-profile-year-picker" placeholder="YYYY" inputmode="numeric">
                </div>
                <div>
                    <label class="text-gray-500">Period From</label>
                    <input type="text" name="education_period_from[]" value="<?= htmlspecialchars((string)($educationRow['period_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2 js-profile-year-picker" placeholder="YYYY" inputmode="numeric">
                </div>
                <div>
                    <label class="text-gray-500">Period To</label>
                    <input type="text" name="education_period_to[]" value="<?= htmlspecialchars((string)($educationRow['period_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2 js-profile-year-picker" placeholder="YYYY" inputmode="numeric">
                </div>
                <div>
                    <label class="text-gray-500">Highest Units</label>
                    <input type="text" name="education_units[]" value="<?= htmlspecialchars((string)($educationRow['highest_level_units'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                </div>
                <div>
                    <label class="text-gray-500">Honors</label>
                    <input type="text" name="education_honors[]" value="<?= htmlspecialchars((string)($educationRow['honors_received'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                </div>
            </div>
            </div>
        <?php endforeach; ?>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-gray-500">Start with one educational background and add more as needed.</p>
            <button type="button" id="addEducationRowBtn" class="inline-flex items-center gap-1 rounded-md border px-3 py-2 text-xs text-gray-700 hover:bg-gray-50">
                <span class="material-symbols-outlined text-sm">add</span>
                Add education entry
            </button>
        </div>
    </div>
</section>

<section class="rounded-xl border bg-white">
    <header class="border-b px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Work Experience</h2>
    </header>

    <div class="space-y-4 p-4 text-sm sm:p-6">
        <div id="workExperienceRows" class="space-y-4">
        <?php foreach ($editableWorkExperienceRows as $workIndex => $workRow): ?>
            <div class="rounded-lg border bg-gray-50 p-4 work-row">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Work Experience Entry</p>
                    <button type="button" class="work-remove rounded-md border px-2 py-1 text-xs text-gray-600 hover:bg-gray-100 <?= $workIndex === 0 ? 'hidden' : '' ?>">Remove</button>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="text-gray-500">Position Title</label>
                        <input type="text" name="work_position_title_entry[]" value="<?= htmlspecialchars((string)($workRow['position_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="text-gray-500">Company Name</label>
                        <input type="text" name="work_company_name_entry[]" value="<?= htmlspecialchars((string)($workRow['office_company'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="text-gray-500">Start Date</label>
                        <input type="text" name="work_start_date_entry[]" value="<?= htmlspecialchars((string)($workRow['inclusive_date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2 js-profile-work-date js-profile-work-start-date" placeholder="YYYY-MM-DD" inputmode="numeric">
                    </div>
                    <div>
                        <label class="text-gray-500">End Date</label>
                        <input type="text" name="work_end_date_entry[]" value="<?= htmlspecialchars((string)($workRow['inclusive_date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2 js-profile-work-date js-profile-work-end-date" placeholder="YYYY-MM-DD" inputmode="numeric">
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <label class="text-gray-500">Brief Description of Responsibilities</label>
                        <textarea name="work_responsibilities_entry[]" rows="3" class="mt-1 w-full rounded-md border px-3 py-2"><?= htmlspecialchars((string)($workRow['achievements'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
            <div>
                <label class="text-gray-500">Years of Experience (Auto-computed preview)</label>
                <input type="number" min="0" step="0.01" id="profileYearsExperiencePreview" class="mt-1 w-full rounded-md border px-3 py-2" readonly>
            </div>
            <button type="button" id="addWorkExperienceRowBtn" class="inline-flex items-center gap-1 rounded-md border px-3 py-2 text-xs text-gray-700 hover:bg-gray-50">
                <span class="material-symbols-outlined text-sm">add</span>
                Add work entry
            </button>
        </div>
    </div>
</section>
<?php
};

if ($requestedPartial === 'secondary-sections') {
    ob_start();
    if ($editMode) {
        $renderDeferredProfileEditSections();
    } else {
        $renderDeferredProfileViewSections();
    }
    echo ob_get_clean();
    return;
}

ob_start();
?>

<section class="mb-5 rounded-xl border bg-white p-4 sm:p-5">
    <div class="rounded-xl border bg-gradient-to-r from-green-50 via-white to-green-50 p-4 sm:p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-4">
                <span class="material-symbols-outlined rounded-lg bg-green-700 p-2 text-2xl text-white">account_circle</span>
                <div>
                    <h1 class="text-xl font-semibold text-gray-800">Account Profile</h1>
                    <p class="mt-1 text-sm text-gray-600">View and manage your personal account information.</p>
                </div>
            </div>

            <?php if (!$editMode): ?>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" data-modal-open="applicantPasswordRequestModal" class="inline-flex items-center gap-1 rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <span class="material-symbols-outlined text-sm">password</span>
                        Change Password
                    </button>
                    <?php if (!empty($passwordChangeStatus['is_pending'])): ?>
                        <button type="button" data-modal-open="applicantPasswordVerifyModal" class="inline-flex items-center gap-1 rounded-md border border-amber-300 px-4 py-2 text-sm text-amber-800 hover:bg-amber-50">
                            <span class="material-symbols-outlined text-sm">mark_email_read</span>
                            Verify Code
                        </button>
                    <?php endif; ?>
                    <a href="profile.php?edit=true" class="inline-flex items-center gap-1 rounded-md border border-green-700 px-4 py-2 text-sm text-green-700 hover:bg-green-50">
                        <span class="material-symbols-outlined text-sm">edit</span>
                        Update Information
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if (!empty($message)): ?>
<section class="mb-6 rounded-xl border px-4 py-3 text-sm <?= ($state ?? '') === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
    <?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>
</section>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
<section class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <span><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></span>
        <a href="<?= $retryUrl ?>" class="inline-flex items-center justify-center rounded-md border border-amber-300 bg-white px-3 py-1.5 text-xs text-amber-800 hover:bg-amber-100">Retry</a>
    </div>
</section>
<?php endif; ?>

<?php if (!$editMode): ?>
<section class="mb-8 grid grid-cols-1 gap-4 lg:grid-cols-2">
    <article class="rounded-xl border bg-white p-5">
        <div class="flex items-start gap-4">
            <?php if (!empty($profileData['profile_photo_public_url'])): ?>
                <img src="<?= htmlspecialchars((string)$profileData['profile_photo_public_url'], ENT_QUOTES, 'UTF-8') ?>" alt="Applicant profile photo" class="h-24 w-24 rounded-full border border-slate-200 object-cover">
            <?php else: ?>
                <div class="flex h-24 w-24 items-center justify-center rounded-full bg-slate-200 text-2xl font-semibold text-slate-700">
                    <?= htmlspecialchars($profileInitials, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div class="min-w-0 flex-1">
                <p class="text-xs uppercase tracking-wide text-gray-500">Full Name</p>
                <p class="mt-1 text-lg font-semibold text-gray-800"><?= htmlspecialchars((string)($profileData['full_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mt-3 text-xs uppercase tracking-wide text-gray-500">Email Address</p>
                <p class="mt-1 break-all font-semibold text-gray-800"><?= htmlspecialchars((string)($profileData['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <form action="profile.php" method="POST" enctype="multipart/form-data" class="mt-5" id="applicantProfilePhotoForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="upload_profile_photo">
            <input id="applicantProfilePhotoInput" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" required>

            <button type="button" data-trigger-file="applicantProfilePhotoInput" class="inline-flex items-center gap-2 rounded-md border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                <span class="material-symbols-outlined text-[18px]">upload</span>
                Select and Upload Photo
            </button>
            <span id="applicantProfilePhotoFilename" class="mt-2 block text-xs text-slate-500">No file selected.</span>
            <p class="mt-1 text-xs text-gray-500">Accepted: JPG, PNG, WEBP (max 3MB).</p>
        </form>
    </article>

    <article class="rounded-xl border bg-white p-5">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-600">Applicant Information</h2>
        <div class="mt-4 space-y-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Contact Number</p>
                <p class="mt-1 font-semibold text-gray-800"><?= htmlspecialchars((string)($profileData['mobile_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Address</p>
                <p class="mt-1 font-semibold text-gray-800"><?= htmlspecialchars((string)($profileData['current_address'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Training Hours Completed</p>
                <p class="mt-1 font-semibold text-gray-800"><?= htmlspecialchars(number_format((float)($profileData['training_hours_completed'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?> hour(s)</p>
            </div>
        </div>
    </article>
</section>

<section class="mb-6 rounded-xl border bg-white">
    <header class="border-b px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Family Background (Spouse)</h2>
    </header>
    <div class="p-4 text-sm sm:p-6">
        <?php if (empty($profileSpouses)): ?>
            <p class="text-gray-600">No spouse records added yet.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($profileSpouses as $spouse): ?>
                    <article class="rounded-lg border bg-gray-50 p-4">
                        <p class="font-medium text-gray-800">
                            <?= htmlspecialchars(trim((string)(($spouse['first_name'] ?? '') . ' ' . ($spouse['middle_name'] ?? '') . ' ' . ($spouse['surname'] ?? ''))), ENT_QUOTES, 'UTF-8') ?: 'Unnamed Spouse Entry' ?>
                        </p>
                        <p class="mt-1 text-gray-600">Occupation: <?= htmlspecialchars((string)($spouse['occupation'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-gray-600">Employer: <?= htmlspecialchars((string)($spouse['employer_business_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<div id="applicantDeferredSectionsHost" data-deferred-sections-url="<?= htmlspecialchars($deferredSectionsUrl, ENT_QUOTES, 'UTF-8') ?>" class="mb-8 space-y-6">
    <section class="rounded-xl border bg-white">
        <div class="space-y-3 p-4 sm:p-6">
            <div class="h-6 w-48 rounded bg-slate-200 applicant-skeleton-pulse"></div>
            <div class="h-24 rounded-lg bg-slate-100 applicant-skeleton-pulse"></div>
            <div class="h-24 rounded-lg bg-slate-100 applicant-skeleton-pulse"></div>
        </div>
    </section>
    <section class="rounded-xl border bg-white">
        <div class="space-y-3 p-4 sm:p-6">
            <div class="h-6 w-40 rounded bg-slate-200 applicant-skeleton-pulse"></div>
            <div class="h-24 rounded-lg bg-slate-100 applicant-skeleton-pulse"></div>
            <div class="h-24 rounded-lg bg-slate-100 applicant-skeleton-pulse"></div>
        </div>
    </section>
</div>

<section class="mb-8 rounded-xl border bg-white">
    <header class="flex flex-col gap-2 border-b px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-lg font-semibold text-gray-800">My Uploaded Files</h2>
        <span class="text-xs text-gray-500">Replace files as needed (max 5MB)</span>
    </header>
    <div class="p-4 text-sm sm:p-6">
        <?php if (empty($uploadedFiles)): ?>
            <p class="text-gray-600">No uploaded files found yet.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($uploadedFiles as $uploadedFile): ?>
                    <article class="rounded-lg border bg-gray-50 p-4">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($uploadedFile['file_name'] ?? 'document'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-600">
                                    <?= htmlspecialchars(strtoupper((string)($uploadedFile['document_type'] ?? 'other')), ENT_QUOTES, 'UTF-8') ?>
                                    • <?= htmlspecialchars((string)($uploadedFile['application_ref_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                    • <?= htmlspecialchars((string)($uploadedFile['job_title'] ?? 'Untitled Position'), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    Uploaded: <?= !empty($uploadedFile['uploaded_at']) ? htmlspecialchars(formatDateTimeForPhilippines((string)$uploadedFile['uploaded_at'], 'M j, Y g:i A') . ' PST', ENT_QUOTES, 'UTF-8') : '-' ?>
                                    • Size: <?= number_format(max(0, (int)($uploadedFile['file_size_bytes'] ?? 0)) / 1024, 1) ?> KB
                                </p>
                            </div>
                            <?php if (!empty($uploadedFile['view_url'])): ?>
                                <a href="<?= htmlspecialchars((string)$uploadedFile['view_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md border px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-100">
                                    <span class="material-symbols-outlined text-sm">download</span>
                                    View
                                </a>
                            <?php endif; ?>
                        </div>

                        <form action="profile.php" method="POST" enctype="multipart/form-data" class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-[1fr_auto]">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="replace_uploaded_file">
                            <input type="hidden" name="document_id" value="<?= htmlspecialchars((string)($uploadedFile['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="file" name="replacement_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required class="rounded-md border bg-white px-3 py-2 text-xs">
                            <button type="submit" class="inline-flex items-center justify-center gap-1 rounded-md bg-green-700 px-3 py-2 text-xs font-medium text-white hover:bg-green-800">
                                <span class="material-symbols-outlined text-sm">upload</span>
                                Replace File
                            </button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="mb-8 rounded-xl border bg-white">
    <header class="border-b px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Login Activity</h2>
        <p class="mt-1 text-sm text-slate-500">Recent authentication events for your account.</p>
    </header>

    <form method="GET" action="profile.php" class="px-6 pb-3 pt-4 grid grid-cols-1 gap-3 md:grid-cols-4 md:items-end md:gap-4">
        <div class="w-full">
            <label class="text-sm text-slate-600" for="applicantLoginSearch">Search Activity</label>
            <input id="applicantLoginSearch" name="login_search" value="<?= htmlspecialchars((string)$loginSearchQuery, ENT_QUOTES, 'UTF-8') ?>" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by event, provider, IP, or device">
        </div>
        <div class="w-full">
            <label class="text-sm text-slate-600" for="applicantLoginEventFilter">Event Type</label>
            <select id="applicantLoginEventFilter" name="login_event" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Events</option>
                <?php foreach ((array)$loginEventOptions as $eventOption): ?>
                    <option value="<?= htmlspecialchars((string)$eventOption, ENT_QUOTES, 'UTF-8') ?>" <?= (string)$loginEventFilter === (string)$eventOption ? 'selected' : '' ?>><?= htmlspecialchars((string)$eventOption, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="w-full">
            <label class="text-sm text-slate-600" for="applicantLoginDeviceFilter">Device</label>
            <select id="applicantLoginDeviceFilter" name="login_device" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Devices</option>
                <?php foreach ((array)$loginDeviceOptions as $deviceOption): ?>
                    <option value="<?= htmlspecialchars((string)$deviceOption, ENT_QUOTES, 'UTF-8') ?>" <?= (string)$loginDeviceFilter === (string)$deviceOption ? 'selected' : '' ?>><?= htmlspecialchars((string)$deviceOption, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2 md:justify-end">
            <button type="submit" class="mt-6 rounded-md bg-green-700 px-4 py-2 text-sm text-white hover:bg-green-800">Apply</button>
            <a href="profile.php" class="mt-6 rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Event</th>
                    <th class="text-left px-4 py-3">Provider</th>
                    <th class="text-left px-4 py-3">IP Address</th>
                    <th class="text-left px-4 py-3">Device</th>
                    <th class="text-left px-4 py-3">User Agent</th>
                    <th class="text-left px-4 py-3">Timestamp</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($loginHistoryRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="6">No login activity available.</td></tr>
                <?php else: ?>
                    <?php foreach ($loginHistoryRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['event_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['auth_provider'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['ip_address'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['device_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['user_agent'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (($loginTotalPages ?? 1) > 1): ?>
            <div class="mt-4 flex items-center justify-between text-sm text-slate-600">
                <p>Showing page <?= (int)($loginPage ?? 1) ?> of <?= (int)($loginTotalPages ?? 1) ?> (<?= (int)($loginHistoryTotal ?? 0) ?> total)</p>
                <div class="flex items-center gap-2">
                    <?php $baseQuery = ['login_search' => (string)$loginSearchQuery, 'login_event' => (string)$loginEventFilter, 'login_device' => (string)$loginDeviceFilter]; ?>
                    <?php if ((int)$loginPage > 1): ?>
                        <?php $prevQuery = $baseQuery; $prevQuery['login_page'] = (int)$loginPage - 1; ?>
                        <a class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50" href="profile.php?<?= htmlspecialchars(http_build_query($prevQuery), ENT_QUOTES, 'UTF-8') ?>">Previous</a>
                    <?php endif; ?>
                    <?php if ((int)$loginPage < (int)$loginTotalPages): ?>
                        <?php $nextQuery = $baseQuery; $nextQuery['login_page'] = (int)$loginPage + 1; ?>
                        <a class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50" href="profile.php?<?= htmlspecialchars(http_build_query($nextQuery), ENT_QUOTES, 'UTF-8') ?>">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<div id="applicantPhotoPreviewModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-close-photo-preview="applicantPhotoPreviewModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 class="text-base font-semibold text-slate-800">Profile Photo Preview</h3>
                <button type="button" data-close-photo-preview="applicantPhotoPreviewModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <div class="p-5">
                <img id="applicantProfilePhotoPreviewImage" src="" alt="Selected profile preview" class="hidden h-64 w-full rounded-lg border border-slate-200 object-contain">
                <p id="applicantProfilePhotoPreviewEmpty" class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">Choose a file first to preview it.</p>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" data-close-photo-preview="applicantPhotoPreviewModal" class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="button" id="applicantProfilePhotoConfirmUpload" class="inline-flex items-center gap-2 rounded-md bg-green-700 px-4 py-2 text-sm text-white hover:bg-green-800">
                        <span class="material-symbols-outlined text-sm">cloud_upload</span>
                        Confirm and Upload
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="applicantPasswordRequestModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="applicantPasswordRequestModal"></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-800">Change Password (Email Verification)</h3>
                <button type="button" data-modal-close="applicantPasswordRequestModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <form action="profile.php" method="POST" class="grid grid-cols-1 gap-4 p-6 text-sm" id="applicantPasswordRequestForm">
                <input type="hidden" name="action" value="request_password_change_code">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div>
                    <label class="text-slate-600">Current Password</label>
                    <div class="relative mt-1">
                        <input type="password" id="applicantCurrentPasswordInput" name="current_password" class="w-full rounded-md border border-slate-300 px-3 py-2 pr-12" required>
                        <button type="button" data-password-toggle="applicantCurrentPasswordInput" class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-slate-500 hover:text-slate-700" aria-label="Show current password">
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="text-slate-600">New Password</label>
                    <div class="relative mt-1">
                        <input type="password" id="applicantNewPasswordInput" name="new_password" class="w-full rounded-md border border-slate-300 px-3 py-2 pr-12" required>
                        <button type="button" data-password-toggle="applicantNewPasswordInput" class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-slate-500 hover:text-slate-700" aria-label="Show new password">
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                        </button>
                    </div>
                    <div class="mt-2">
                        <div class="h-2 w-full rounded-full bg-slate-200">
                            <div id="applicantPasswordStrengthBar" class="h-2 w-0 rounded-full bg-slate-300 transition-all duration-150"></div>
                        </div>
                        <p id="applicantPasswordStrengthText" class="mt-1 text-xs text-slate-500">Strength: Enter a new password</p>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Use at least 10 characters with uppercase, lowercase, number, and special character.</p>
                </div>

                <div>
                    <label class="text-slate-600">Confirm New Password</label>
                    <div class="relative mt-1">
                        <input type="password" id="applicantConfirmPasswordInput" name="confirm_new_password" class="w-full rounded-md border border-slate-300 px-3 py-2 pr-12" required>
                        <button type="button" data-password-toggle="applicantConfirmPasswordInput" class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-slate-500 hover:text-slate-700" aria-label="Show confirm password">
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                        </button>
                    </div>
                    <p id="applicantPasswordMatchIndicator" class="mt-2 text-xs text-slate-500">Enter and confirm your new password.</p>
                </div>

                <div class="mt-2 flex justify-end gap-3">
                    <button type="button" data-modal-close="applicantPasswordRequestModal" class="rounded-md border border-slate-300 px-4 py-2 text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-md bg-green-700 px-5 py-2 text-white hover:bg-green-800">Send Verification Code</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="applicantPasswordVerifyModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="applicantPasswordVerifyModal"></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-800">Verify Email Code</h3>
                <button type="button" data-modal-close="applicantPasswordVerifyModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <form action="profile.php" method="POST" class="grid grid-cols-1 gap-4 p-6 text-sm">
                <input type="hidden" name="action" value="confirm_password_change_code">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <?php if (!empty($passwordChangeStatus['is_pending'])): ?>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                        Verification code sent to <strong><?= htmlspecialchars((string)$passwordChangeStatus['email'], ENT_QUOTES, 'UTF-8') ?></strong>. Expires at <?= htmlspecialchars((string)$passwordChangeStatus['expires_at'], ENT_QUOTES, 'UTF-8') ?>.
                    </div>
                <?php else: ?>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                        No pending verification code was found. Send a new code first.
                    </div>
                <?php endif; ?>

                <?php if (!empty($passwordChangeStatus['is_pending']) && ($state ?? '') === 'error' && !empty($message)): ?>
                    <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                        <?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="text-slate-600">Verification Code</label>
                    <input type="text" name="verification_code" maxlength="6" pattern="[0-9]{6}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" placeholder="Enter 6-digit code" required>
                </div>

                <div class="mt-2 flex justify-between gap-3">
                    <button type="submit" name="action" value="cancel_password_change_code" class="rounded-md border border-slate-300 px-4 py-2 text-slate-700 hover:bg-slate-50">Cancel Pending Request</button>
                    <button type="submit" class="rounded-md bg-green-700 px-5 py-2 text-white hover:bg-green-800">Verify and Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script id="applicantPasswordFlowData" type="application/json"><?= htmlspecialchars(json_encode([
    'has_pending_code' => !empty($passwordChangeStatus['is_pending']),
    'state' => (string)($state ?? ''),
    'message' => (string)($message ?? ''),
    'target_modal' => $passwordModalTarget,
], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></script>

<?php else: ?>
<form action="profile.php?edit=true" method="POST" class="space-y-6">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="deferred_sections_ready" id="applicantDeferredSectionsReady" value="0">
    <section class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800">Update Personal Information</h2>
        </header>

        <div class="grid grid-cols-1 gap-6 p-4 text-sm md:grid-cols-2 sm:p-6">
            <div>
                <label class="text-gray-500">Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars((string)($profileData['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required class="mt-1 w-full rounded-md border px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars((string)($profileData['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required class="mt-1 w-full rounded-md border px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Contact Number</label>
                <input type="text" name="mobile_no" value="<?= htmlspecialchars((string)($profileData['mobile_no'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Address</label>
                <input type="text" name="current_address" value="<?= htmlspecialchars((string)(($profileData['current_address'] ?? '') === '-' ? '' : ($profileData['current_address'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Training Hours Completed</label>
                <input type="number" min="0" step="0.1" name="training_hours_completed" value="<?= htmlspecialchars((string)($profileData['training_hours_completed'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                <p class="mt-1 text-xs text-gray-500">Enter your accumulated training hours as reflected in your applicant profile.</p>
            </div>
        </div>
    </section>

    <section class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800">Family Background (Spouse)</h2>
        </header>

        <div class="space-y-4 p-4 text-sm sm:p-6">
            <div id="spouseRows" class="space-y-4">
            <?php foreach ($editableSpouseRows as $spouseIndex => $spouseRow): ?>
                <div class="rounded-lg border bg-gray-50 p-4 spouse-row">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Spouse Entry</p>
                        <button type="button" class="spouse-remove rounded-md border px-2 py-1 text-xs text-gray-600 hover:bg-gray-100 <?= $spouseIndex === 0 ? 'hidden' : '' ?>">Remove</button>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="text-gray-500">First Name</label>
                        <input type="text" name="spouse_first_name[]" value="<?= htmlspecialchars((string)($spouseRow['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="text-gray-500">Middle Name</label>
                        <input type="text" name="spouse_middle_name[]" value="<?= htmlspecialchars((string)($spouseRow['middle_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="text-gray-500">Surname</label>
                        <input type="text" name="spouse_surname[]" value="<?= htmlspecialchars((string)($spouseRow['surname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="text-gray-500">Extension</label>
                        <input type="text" name="spouse_extension_name[]" value="<?= htmlspecialchars((string)($spouseRow['extension_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="text-gray-500">Occupation</label>
                        <input type="text" name="spouse_occupation[]" value="<?= htmlspecialchars((string)($spouseRow['occupation'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="text-gray-500">Employer</label>
                        <input type="text" name="spouse_employer[]" value="<?= htmlspecialchars((string)($spouseRow['employer_business_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="text-gray-500">Business Address</label>
                        <input type="text" name="spouse_business_address[]" value="<?= htmlspecialchars((string)($spouseRow['business_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="text-gray-500">Telephone No.</label>
                        <input type="text" name="spouse_telephone_no[]" value="<?= htmlspecialchars((string)($spouseRow['telephone_no'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                    </div>
                </div>
                </div>
            <?php endforeach; ?>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-gray-500">You can keep one spouse entry or add more if needed.</p>
                <button type="button" id="addSpouseRowBtn" class="inline-flex items-center gap-1 rounded-md border px-3 py-2 text-xs text-gray-700 hover:bg-gray-50">
                    <span class="material-symbols-outlined text-sm">add</span>
                    Add spouse entry
                </button>
            </div>
        </div>
    </section>

    <div id="applicantDeferredSectionsHost" data-deferred-sections-url="<?= htmlspecialchars($deferredSectionsUrl, ENT_QUOTES, 'UTF-8') ?>" class="space-y-6">
        <section class="rounded-xl border bg-white">
            <div class="space-y-3 p-4 sm:p-6">
                <div class="h-6 w-48 rounded bg-slate-200 applicant-skeleton-pulse"></div>
                <div class="h-24 rounded-lg bg-slate-100 applicant-skeleton-pulse"></div>
                <div class="h-24 rounded-lg bg-slate-100 applicant-skeleton-pulse"></div>
            </div>
        </section>
        <section class="rounded-xl border bg-white">
            <div class="space-y-3 p-4 sm:p-6">
                <div class="h-6 w-40 rounded bg-slate-200 applicant-skeleton-pulse"></div>
                <div class="h-24 rounded-lg bg-slate-100 applicant-skeleton-pulse"></div>
                <div class="h-24 rounded-lg bg-slate-100 applicant-skeleton-pulse"></div>
            </div>
        </section>
    </div>

    <section class="rounded-xl border bg-white p-4 text-sm sm:p-6">
        <p class="mb-3 text-gray-600">Please review your changes carefully before submitting.</p>
        <p id="applicantDeferredSectionsNotice" class="mb-3 text-xs text-slate-500">Loading education and work experience sections...</p>

        <div class="flex flex-wrap justify-end gap-3">
            <a href="profile.php" class="rounded-md border px-4 py-2 text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" id="applicantProfileSaveButton" class="inline-flex items-center gap-2 rounded-md bg-green-700 px-5 py-2 text-sm font-medium text-white hover:bg-green-800 disabled:cursor-not-allowed disabled:opacity-60" disabled>
                <span class="material-symbols-outlined text-sm">save</span>
                Save Changes
            </button>
        </div>
    </section>
</form>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const openButtons = document.querySelectorAll('[data-modal-open]');
        const closeButtons = document.querySelectorAll('[data-modal-close]');

        openButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = button.getAttribute('data-modal-open');
                if (!targetId) {
                    return;
                }

                const modal = document.getElementById(targetId);
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.setAttribute('aria-hidden', 'false');
                }
            });
        });

        closeButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = button.getAttribute('data-modal-close');
                if (!targetId) {
                    return;
                }

                const modal = document.getElementById(targetId);
                if (modal) {
                    modal.classList.add('hidden');
                    modal.setAttribute('aria-hidden', 'true');
                }
            });
        });

        const applicantProfilePhotoForm = document.getElementById('applicantProfilePhotoForm');
        const applicantProfilePhotoInput = document.getElementById('applicantProfilePhotoInput');
        const applicantProfilePhotoFilename = document.getElementById('applicantProfilePhotoFilename');
        const photoPreviewImage = document.getElementById('applicantProfilePhotoPreviewImage');
        const photoPreviewEmpty = document.getElementById('applicantProfilePhotoPreviewEmpty');
        const photoPreviewModal = document.getElementById('applicantPhotoPreviewModal');
        const applicantProfilePhotoConfirmUpload = document.getElementById('applicantProfilePhotoConfirmUpload');
        document.querySelectorAll('[data-trigger-file="applicantProfilePhotoInput"]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (applicantProfilePhotoInput instanceof HTMLInputElement) {
                    applicantProfilePhotoInput.click();
                }
            });
        });
        if (applicantProfilePhotoInput instanceof HTMLInputElement && applicantProfilePhotoFilename) {
            applicantProfilePhotoInput.addEventListener('change', function () {
                const file = applicantProfilePhotoInput.files && applicantProfilePhotoInput.files[0] ? applicantProfilePhotoInput.files[0] : null;
                applicantProfilePhotoFilename.textContent = file ? file.name : 'No file selected.';

                if (!photoPreviewImage || !photoPreviewEmpty) {
                    return;
                }

                if (!file) {
                    photoPreviewImage.classList.add('hidden');
                    photoPreviewImage.removeAttribute('src');
                    photoPreviewEmpty.classList.remove('hidden');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function () {
                    photoPreviewImage.src = String(reader.result || '');
                    photoPreviewImage.classList.remove('hidden');
                    photoPreviewEmpty.classList.add('hidden');
                    if (photoPreviewModal) {
                        photoPreviewModal.classList.remove('hidden');
                    }
                };
                reader.readAsDataURL(file);
            });
        }

        if (applicantProfilePhotoConfirmUpload instanceof HTMLButtonElement) {
            applicantProfilePhotoConfirmUpload.addEventListener('click', function () {
                if (!(applicantProfilePhotoInput instanceof HTMLInputElement) || !applicantProfilePhotoInput.files || applicantProfilePhotoInput.files.length === 0) {
                    return;
                }

                if (applicantProfilePhotoForm instanceof HTMLFormElement) {
                    applicantProfilePhotoForm.submit();
                }
            });
        }

        document.querySelectorAll('[data-close-photo-preview="applicantPhotoPreviewModal"]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (photoPreviewModal) {
                    photoPreviewModal.classList.add('hidden');
                }
            });
        });

        const passwordInput = document.getElementById('applicantNewPasswordInput');
        const confirmPasswordInput = document.getElementById('applicantConfirmPasswordInput');
        const strengthBar = document.getElementById('applicantPasswordStrengthBar');
        const strengthText = document.getElementById('applicantPasswordStrengthText');
        const passwordMatchIndicator = document.getElementById('applicantPasswordMatchIndicator');
        const deferredSectionsHost = document.getElementById('applicantDeferredSectionsHost');
        const deferredSectionsReady = document.getElementById('applicantDeferredSectionsReady');
        const deferredSectionsNotice = document.getElementById('applicantDeferredSectionsNotice');
        const applicantProfileSaveButton = document.getElementById('applicantProfileSaveButton');

        window.initializeApplicantProfileFlatpickr = function (scope) {
            if (!window.flatpickr) {
                return;
            }

            const root = scope instanceof Element || scope instanceof Document ? scope : document;

            root.querySelectorAll('.js-profile-work-date:not([data-flatpickr-initialized="true"])').forEach(function (input) {
                window.flatpickr(input, {
                    dateFormat: 'Y-m-d',
                    allowInput: true,
                });
                input.setAttribute('data-flatpickr-initialized', 'true');
            });

            root.querySelectorAll('.js-profile-year-picker:not([data-flatpickr-initialized="true"])').forEach(function (input) {
                window.flatpickr(input, {
                    dateFormat: 'Y',
                    allowInput: true,
                    defaultDate: input.value || null,
                });
                input.setAttribute('data-flatpickr-initialized', 'true');
            });
        };

        const setDeferredSectionsState = function (isReady, noticeText, isError) {
            if (deferredSectionsReady instanceof HTMLInputElement) {
                deferredSectionsReady.value = isReady ? '1' : '0';
            }

            if (applicantProfileSaveButton instanceof HTMLButtonElement) {
                applicantProfileSaveButton.disabled = !isReady;
            }

            if (!deferredSectionsNotice) {
                return;
            }

            deferredSectionsNotice.textContent = noticeText;
            deferredSectionsNotice.classList.remove('text-slate-500', 'text-rose-600', 'text-emerald-700');
            deferredSectionsNotice.classList.add(isError ? 'text-rose-600' : (isReady ? 'text-emerald-700' : 'text-slate-500'));
        };

        const loadDeferredSections = function () {
            if (!deferredSectionsHost) {
                setDeferredSectionsState(true, 'Deferred sections are ready.', false);
                return;
            }

            const deferredUrl = deferredSectionsHost.getAttribute('data-deferred-sections-url');
            if (!deferredUrl) {
                setDeferredSectionsState(true, 'Deferred sections are ready.', false);
                return;
            }

            setDeferredSectionsState(false, 'Loading education and work experience sections...', false);

            fetch(deferredUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Failed to load deferred sections.');
                    }
                    return response.text();
                })
                .then(function (html) {
                    deferredSectionsHost.innerHTML = html;
                    if (typeof window.initializeApplicantProfileFlatpickr === 'function') {
                        window.initializeApplicantProfileFlatpickr(deferredSectionsHost);
                    }
                    if (typeof window.initializeApplicantProfileDeferredSections === 'function') {
                        window.initializeApplicantProfileDeferredSections(deferredSectionsHost);
                    }
                    setDeferredSectionsState(true, 'Education and work experience sections loaded.', false);
                })
                .catch(function () {
                    deferredSectionsHost.innerHTML = '<section class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><span>Failed to load education and work experience sections.</span><button type="button" id="retryApplicantDeferredSections" class="inline-flex items-center justify-center rounded-md border border-rose-300 bg-white px-3 py-1.5 text-xs text-rose-700 hover:bg-rose-100">Retry</button></div></section>';
                    const retryButton = document.getElementById('retryApplicantDeferredSections');
                    if (retryButton instanceof HTMLButtonElement) {
                        retryButton.addEventListener('click', loadDeferredSections);
                    }
                    setDeferredSectionsState(false, 'Education and work experience failed to load. Retry first before saving.', true);
                });
        };

        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                const inputId = button.getAttribute('data-password-toggle');
                if (!inputId) {
                    return;
                }

                const input = document.getElementById(inputId);
                if (!(input instanceof HTMLInputElement)) {
                    return;
                }

                const icon = button.querySelector('.material-symbols-outlined');
                const nextType = input.type === 'password' ? 'text' : 'password';
                input.type = nextType;

                if (icon) {
                    icon.textContent = nextType === 'password' ? 'visibility' : 'visibility_off';
                }
            });
        });

        const scorePassword = function (value) {
            let score = 0;
            if (value.length >= 10) score += 1;
            if (/[A-Z]/.test(value)) score += 1;
            if (/[a-z]/.test(value)) score += 1;
            if (/\d/.test(value)) score += 1;
            if (/[^a-zA-Z0-9]/.test(value)) score += 1;
            return score;
        };

        const applyStrengthUi = function (score) {
            if (!strengthBar || !strengthText) {
                return;
            }

            const widths = ['0%', '20%', '40%', '60%', '80%', '100%'];
            const labels = [
                'Strength: Enter a new password',
                'Strength: Very Weak',
                'Strength: Weak',
                'Strength: Fair',
                'Strength: Good',
                'Strength: Strong',
            ];
            const classes = ['bg-slate-300', 'bg-red-500', 'bg-orange-500', 'bg-amber-500', 'bg-lime-500', 'bg-emerald-600'];

            strengthBar.style.width = widths[score] || '0%';
            strengthBar.classList.remove('bg-slate-300', 'bg-red-500', 'bg-orange-500', 'bg-amber-500', 'bg-lime-500', 'bg-emerald-600');
            strengthBar.classList.add(classes[score] || 'bg-slate-300');
            strengthText.textContent = labels[score] || labels[0];
        };

        if (passwordInput) {
            applyStrengthUi(0);
            passwordInput.addEventListener('input', function () {
                const value = passwordInput.value || '';
                const score = value.length === 0 ? 0 : scorePassword(value);
                applyStrengthUi(score);
                updatePasswordMatchUi();
            });
        }

        const updatePasswordMatchUi = function () {
            if (!(passwordInput instanceof HTMLInputElement) || !(confirmPasswordInput instanceof HTMLInputElement) || !passwordMatchIndicator) {
                return;
            }

            const newPassword = passwordInput.value || '';
            const confirmPassword = confirmPasswordInput.value || '';

            passwordMatchIndicator.classList.remove('text-slate-500', 'text-emerald-700', 'text-rose-700');
            confirmPasswordInput.classList.remove('border-emerald-400', 'border-rose-400');

            if (newPassword === '' && confirmPassword === '') {
                passwordMatchIndicator.textContent = 'Enter and confirm your new password.';
                passwordMatchIndicator.classList.add('text-slate-500');
                return;
            }

            if (confirmPassword === '') {
                passwordMatchIndicator.textContent = 'Confirm your new password to check if it matches.';
                passwordMatchIndicator.classList.add('text-slate-500');
                return;
            }

            if (newPassword === confirmPassword) {
                passwordMatchIndicator.textContent = 'Passwords match.';
                passwordMatchIndicator.classList.add('text-emerald-700');
                confirmPasswordInput.classList.add('border-emerald-400');
                return;
            }

            passwordMatchIndicator.textContent = 'Passwords do not match.';
            passwordMatchIndicator.classList.add('text-rose-700');
            confirmPasswordInput.classList.add('border-rose-400');
        };

        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', updatePasswordMatchUi);
            updatePasswordMatchUi();
        }

        const passwordFlowDataNode = document.getElementById('applicantPasswordFlowData');
        let passwordFlowData = { has_pending_code: false, state: '', message: '', target_modal: '' };
        if (passwordFlowDataNode && passwordFlowDataNode.textContent) {
            try {
                passwordFlowData = JSON.parse(passwordFlowDataNode.textContent);
            } catch (_error) {
                passwordFlowData = { has_pending_code: false, state: '', message: '' };
            }
        }

        const requestModal = document.getElementById('applicantPasswordRequestModal');
        const verifyModal = document.getElementById('applicantPasswordVerifyModal');
        const hasPendingCode = Boolean(passwordFlowData.has_pending_code);
        const responseState = String(passwordFlowData.state || '');
        const responseMessage = String(passwordFlowData.message || '');
        const targetModal = String(passwordFlowData.target_modal || '');
        const lowerMessage = responseMessage.toLowerCase();
        const shouldAutoOpenVerify = targetModal === 'verify' || (hasPendingCode && (
            (responseState === 'success' && lowerMessage.includes('verification code sent'))
            || responseState === 'error'
        ));
        const shouldAutoOpenRequest = targetModal === 'request';

        if (shouldAutoOpenRequest && requestModal) {
            requestModal.classList.remove('hidden');
            requestModal.setAttribute('aria-hidden', 'false');
        }

        if (shouldAutoOpenVerify && verifyModal) {
            requestModal?.classList.add('hidden');
            verifyModal.classList.remove('hidden');
            verifyModal.setAttribute('aria-hidden', 'false');
        }

        if (typeof window.initializeApplicantProfileFlatpickr === 'function') {
            window.initializeApplicantProfileFlatpickr(document);
        }

        loadDeferredSections();
    });
</script>

<?php if ($editMode): ?>
<script>
    (function () {
        const spouseRows = document.getElementById('spouseRows');
        const addSpouseRowBtn = document.getElementById('addSpouseRowBtn');

        function refreshRemoveButtons(container, selector) {
            if (!container) {
                return;
            }

            const rows = Array.from(container.querySelectorAll(selector));
            rows.forEach(function (row, index) {
                let removeSelector = '.education-remove';
                if (selector === '.spouse-row') {
                    removeSelector = '.spouse-remove';
                } else if (selector === '.work-row') {
                    removeSelector = '.work-remove';
                }

                const removeBtn = row.querySelector(removeSelector);
                if (!removeBtn) {
                    return;
                }

                if (index === 0) {
                    removeBtn.classList.add('hidden');
                } else {
                    removeBtn.classList.remove('hidden');
                }
            });
        }

        if (spouseRows && addSpouseRowBtn) {
            addSpouseRowBtn.addEventListener('click', function () {
                const firstRow = spouseRows.querySelector('.spouse-row');
                if (!firstRow) {
                    return;
                }

                const clone = firstRow.cloneNode(true);
                clone.querySelectorAll('input').forEach(function (input) {
                    input.value = '';
                });

                spouseRows.appendChild(clone);
                refreshRemoveButtons(spouseRows, '.spouse-row');
            });

            spouseRows.addEventListener('click', function (event) {
                const removeBtn = event.target.closest('.spouse-remove');
                if (!removeBtn) {
                    return;
                }

                const rows = spouseRows.querySelectorAll('.spouse-row');
                if (rows.length <= 1) {
                    return;
                }

                removeBtn.closest('.spouse-row')?.remove();
                refreshRemoveButtons(spouseRows, '.spouse-row');
            });

            refreshRemoveButtons(spouseRows, '.spouse-row');
        }

        window.initializeApplicantProfileDeferredSections = function (scope) {
            const root = scope instanceof Element || scope instanceof Document ? scope : document;
            const educationRows = root.querySelector('#educationRows');
            const workExperienceRows = root.querySelector('#workExperienceRows');
            const addEducationRowBtn = root.querySelector('#addEducationRowBtn');
            const addWorkExperienceRowBtn = root.querySelector('#addWorkExperienceRowBtn');
            const profileYearsExperiencePreview = root.querySelector('#profileYearsExperiencePreview');

            const computeProfileExperienceYears = function () {
                if (!workExperienceRows || !(profileYearsExperiencePreview instanceof HTMLInputElement)) {
                    return;
                }

                const rows = workExperienceRows.querySelectorAll('.work-row');
                const msPerDay = 24 * 60 * 60 * 1000;
                let totalDays = 0;
                const today = new Date();

                rows.forEach(function (row) {
                    const startInput = row.querySelector('.js-profile-work-start-date');
                    const endInput = row.querySelector('.js-profile-work-end-date');

                    if (!(startInput instanceof HTMLInputElement) || !startInput.value) {
                        return;
                    }

                    const startDate = new Date(startInput.value + 'T00:00:00');
                    const endDate = endInput instanceof HTMLInputElement && endInput.value
                        ? new Date(endInput.value + 'T00:00:00')
                        : today;

                    if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime()) || endDate < startDate) {
                        return;
                    }

                    totalDays += Math.floor((endDate - startDate) / msPerDay) + 1;
                });

                profileYearsExperiencePreview.value = (Math.round((totalDays / 365) * 100) / 100).toFixed(2);
            };

            if (educationRows && addEducationRowBtn && !addEducationRowBtn.dataset.bound) {
                addEducationRowBtn.dataset.bound = 'true';
                addEducationRowBtn.addEventListener('click', function () {
                    const firstRow = educationRows.querySelector('.education-row');
                    if (!firstRow) {
                        return;
                    }

                    const clone = firstRow.cloneNode(true);
                    clone.querySelectorAll('input').forEach(function (input) {
                        input.value = '';
                        input.removeAttribute('data-flatpickr-initialized');
                    });
                    clone.querySelectorAll('select').forEach(function (select) {
                        select.selectedIndex = 0;
                    });

                    educationRows.appendChild(clone);
                    refreshRemoveButtons(educationRows, '.education-row');
                    if (typeof window.initializeApplicantProfileFlatpickr === 'function') {
                        window.initializeApplicantProfileFlatpickr(clone);
                    }
                });
            }

            if (educationRows && !educationRows.dataset.bound) {
                educationRows.dataset.bound = 'true';
                educationRows.addEventListener('click', function (event) {
                    const removeBtn = event.target.closest('.education-remove');
                    if (!removeBtn) {
                        return;
                    }

                    const rows = educationRows.querySelectorAll('.education-row');
                    if (rows.length <= 1) {
                        return;
                    }

                    removeBtn.closest('.education-row')?.remove();
                    refreshRemoveButtons(educationRows, '.education-row');
                });
            }

            if (educationRows) {
                refreshRemoveButtons(educationRows, '.education-row');
            }

            if (workExperienceRows && addWorkExperienceRowBtn && !addWorkExperienceRowBtn.dataset.bound) {
                addWorkExperienceRowBtn.dataset.bound = 'true';
                addWorkExperienceRowBtn.addEventListener('click', function () {
                    const firstRow = workExperienceRows.querySelector('.work-row');
                    if (!firstRow) {
                        return;
                    }

                    const clone = firstRow.cloneNode(true);
                    clone.querySelectorAll('input').forEach(function (input) {
                        input.value = '';
                        input.removeAttribute('data-flatpickr-initialized');
                    });
                    clone.querySelectorAll('textarea').forEach(function (textarea) {
                        textarea.value = '';
                    });

                    workExperienceRows.appendChild(clone);
                    refreshRemoveButtons(workExperienceRows, '.work-row');
                    if (typeof window.initializeApplicantProfileFlatpickr === 'function') {
                        window.initializeApplicantProfileFlatpickr(clone);
                    }
                    computeProfileExperienceYears();
                });
            }

            if (workExperienceRows && !workExperienceRows.dataset.bound) {
                workExperienceRows.dataset.bound = 'true';
                workExperienceRows.addEventListener('click', function (event) {
                    const removeBtn = event.target.closest('.work-remove');
                    if (!removeBtn) {
                        return;
                    }

                    const rows = workExperienceRows.querySelectorAll('.work-row');
                    if (rows.length <= 1) {
                        return;
                    }

                    removeBtn.closest('.work-row')?.remove();
                    refreshRemoveButtons(workExperienceRows, '.work-row');
                    computeProfileExperienceYears();
                });

                workExperienceRows.addEventListener('input', function (event) {
                    if (event.target.closest('.work-row')) {
                        computeProfileExperienceYears();
                    }
                });
            }

            if (workExperienceRows) {
                refreshRemoveButtons(workExperienceRows, '.work-row');
                computeProfileExperienceYears();
            }
        };

        if (typeof window.initializeApplicantProfileDeferredSections === 'function') {
            window.initializeApplicantProfileDeferredSections(document);
        }
    })();
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
