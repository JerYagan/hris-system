<?php
require_once __DIR__ . '/includes/profile/bootstrap.php';
require_once __DIR__ . '/includes/profile/actions.php';
require_once __DIR__ . '/includes/profile/data.php';

$csrfToken = ensureCsrfToken();

$editMode = isset($_GET['edit']) && $_GET['edit'] === 'true';
$profileSpouses = is_array($profileSpouses ?? null) ? $profileSpouses : [];
$profileEducations = is_array($profileEducations ?? null) ? $profileEducations : [];
$uploadedFiles = is_array($uploadedFiles ?? null) ? $uploadedFiles : [];

$editableSpouseRows = !empty($profileSpouses) ? array_values($profileSpouses) : [[]];
$editableEducationRows = !empty($profileEducations) ? array_values($profileEducations) : [[]];

$pageTitle = 'Profile | DA HRIS';
$activePage = 'profile.php';
$breadcrumbs = $editMode ? ['Profile', 'Edit'] : ['Profile'];
$retryUrl = htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? 'profile.php'), ENT_QUOTES, 'UTF-8');

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
                <a href="profile.php?edit=true" class="inline-flex items-center gap-1 rounded-md border border-green-700 px-4 py-2 text-sm text-green-700 hover:bg-green-50">
                    <span class="material-symbols-outlined text-sm">edit</span>
                    Update Information
                </a>
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
<section class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Full Name</p>
        <p class="mt-2 font-semibold text-gray-800"><?= htmlspecialchars((string)($profileData['full_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    </article>

    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Email Address</p>
        <p class="mt-2 font-semibold text-gray-800"><?= htmlspecialchars((string)($profileData['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    </article>

    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Contact Number</p>
        <p class="mt-2 font-semibold text-gray-800"><?= htmlspecialchars((string)($profileData['mobile_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    </article>

    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Address</p>
        <p class="mt-2 font-semibold text-gray-800"><?= htmlspecialchars((string)($profileData['current_address'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
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
                                    Uploaded: <?= !empty($uploadedFile['uploaded_at']) ? htmlspecialchars(date('M j, Y g:i A', strtotime((string)$uploadedFile['uploaded_at'])), ENT_QUOTES, 'UTF-8') : '-' ?>
                                    • Size: <?= number_format(max(0, (int)($uploadedFile['file_size_bytes'] ?? 0)) / 1024, 1) ?> KB
                                </p>
                            </div>
                            <?php if (!empty($uploadedFile['file_url']) && preg_match('/^https?:\/\//i', (string)$uploadedFile['file_url'])): ?>
                                <a href="<?= htmlspecialchars((string)$uploadedFile['file_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md border px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-100">
                                    <span class="material-symbols-outlined text-sm">download</span>
                                    Open Current File
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

<?php else: ?>
<form action="profile.php?edit=true" method="POST" class="space-y-6">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
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
                        <input type="text" name="education_year_graduated[]" value="<?= htmlspecialchars((string)($educationRow['year_graduated'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="text-gray-500">Period From</label>
                        <input type="text" name="education_period_from[]" value="<?= htmlspecialchars((string)($educationRow['period_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="text-gray-500">Period To</label>
                        <input type="text" name="education_period_to[]" value="<?= htmlspecialchars((string)($educationRow['period_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
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

    <section class="rounded-xl border bg-white p-4 text-sm sm:p-6">
        <p class="mb-3 text-gray-600">Please review your changes carefully before submitting.</p>

        <div class="flex flex-wrap justify-end gap-3">
            <a href="profile.php" class="rounded-md border px-4 py-2 text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-green-700 px-5 py-2 text-sm font-medium text-white hover:bg-green-800">
                <span class="material-symbols-outlined text-sm">save</span>
                Save Changes
            </button>
        </div>
    </section>
</form>
<?php endif; ?>

<?php if ($editMode): ?>
<script>
    (function () {
        const spouseRows = document.getElementById('spouseRows');
        const educationRows = document.getElementById('educationRows');
        const addSpouseRowBtn = document.getElementById('addSpouseRowBtn');
        const addEducationRowBtn = document.getElementById('addEducationRowBtn');

        function refreshRemoveButtons(container, selector) {
            if (!container) {
                return;
            }

            const rows = Array.from(container.querySelectorAll(selector));
            rows.forEach(function (row, index) {
                const removeBtn = row.querySelector(selector === '.spouse-row' ? '.spouse-remove' : '.education-remove');
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

        if (educationRows && addEducationRowBtn) {
            addEducationRowBtn.addEventListener('click', function () {
                const firstRow = educationRows.querySelector('.education-row');
                if (!firstRow) {
                    return;
                }

                const clone = firstRow.cloneNode(true);
                clone.querySelectorAll('input').forEach(function (input) {
                    input.value = '';
                });
                clone.querySelectorAll('select').forEach(function (select) {
                    select.selectedIndex = 0;
                });

                educationRows.appendChild(clone);
                refreshRemoveButtons(educationRows, '.education-row');
            });

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

            refreshRemoveButtons(educationRows, '.education-row');
        }
    })();
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
