<?php
require_once __DIR__ . '/includes/apply/bootstrap.php';
require_once __DIR__ . '/includes/apply/actions.php';
require_once __DIR__ . '/includes/apply/data.php';

$csrfToken = ensureCsrfToken();

$pageTitle = 'Submit Application | DA HRIS';
$activePage = 'apply.php';
$breadcrumbs = ['Submit Application'];
$retryUrl = htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? 'apply.php'), ENT_QUOTES, 'UTF-8');

ob_start();
?>

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

<?php if ($jobNotAvailable): ?>
<section class="mb-6 rounded-xl border bg-white p-8 text-center">
    <span class="material-symbols-outlined text-4xl text-gray-400">assignment_late</span>
    <h2 class="mt-3 text-xl font-semibold text-gray-800">Job posting unavailable</h2>
    <p class="mt-1 text-sm text-gray-600">Please choose another open position from the listings.</p>
    <a href="job-list.php" class="mt-4 inline-flex items-center gap-1 rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        Back to Jobs
    </a>
</section>
<?php elseif (!empty($existingApplication)): ?>
<section class="mb-6 rounded-xl border border-green-200 bg-green-50 p-6">
    <h2 class="text-lg font-semibold text-green-800">Application already submitted</h2>
    <p class="mt-1 text-sm text-green-700">
        You already applied for this position.
        Reference: <strong><?= htmlspecialchars((string)($existingApplication['application_ref_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
    </p>
    <div class="mt-4 flex flex-wrap gap-2">
        <a href="applications.php" class="inline-flex items-center gap-1 rounded-md border border-green-300 bg-white px-4 py-2 text-sm text-green-800 hover:bg-green-100">
            <span class="material-symbols-outlined text-sm">list_alt</span>
            View My Applications
        </a>
        <a href="job-list.php" class="inline-flex items-center gap-1 rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Back to Jobs
        </a>
    </div>
</section>
<?php else: ?>

<section class="mb-5 rounded-xl border bg-white p-4 sm:p-5">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <span class="material-symbols-outlined rounded-lg bg-green-700 p-2 text-2xl text-white">edit_document</span>
            <div>
                <h1 class="text-xl font-semibold text-gray-800">Job Application Form</h1>
                <p class="text-sm text-gray-500">Complete the details below to submit your application.</p>
            </div>
        </div>

        <a href="job-list.php" class="inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Back to Jobs
        </a>
    </div>
</section>

<form action="apply.php?job_id=<?= urlencode((string)$jobData['id']) ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
    <input type="hidden" name="job_id" value="<?= htmlspecialchars((string)$jobData['id'], ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <section class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800">Position Information</h2>
        </header>

        <div class="grid grid-cols-1 gap-6 p-6 text-sm md:grid-cols-2">
            <div>
                <label class="text-gray-500">Position Applied For</label>
                <input type="text" value="<?= htmlspecialchars((string)$jobData['title'], ENT_QUOTES, 'UTF-8') ?>" readonly class="mt-1 w-full rounded-md border bg-gray-100 px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Division</label>
                <input type="text" value="<?= htmlspecialchars((string)$jobData['office_name'], ENT_QUOTES, 'UTF-8') ?>" readonly class="mt-1 w-full rounded-md border bg-gray-100 px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Plantilla Item No.</label>
                <input type="text" value="<?= htmlspecialchars((string)($jobData['plantilla_item_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>" readonly class="mt-1 w-full rounded-md border bg-gray-100 px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Employment Type</label>
                <input type="text" value="<?= htmlspecialchars((string)($jobData['employment_type_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>" readonly class="mt-1 w-full rounded-md border bg-gray-100 px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Work Location</label>
                <input type="text" value="<?= htmlspecialchars((string)($jobData['work_location'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>" readonly class="mt-1 w-full rounded-md border bg-gray-100 px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">CSC Reference Format</label>
                <?php if (!empty($jobData['csc_reference_url']) && preg_match('/^https?:\/\//i', (string)$jobData['csc_reference_url'])): ?>
                    <a href="<?= htmlspecialchars((string)$jobData['csc_reference_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex w-full items-center justify-center gap-1 rounded-md border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        View CSC-style posting format
                        <span class="material-symbols-outlined text-sm">open_in_new</span>
                    </a>
                <?php else: ?>
                    <input type="text" value="No CSC reference link configured for this posting" readonly class="mt-1 w-full rounded-md border bg-gray-100 px-3 py-2 text-gray-500">
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800">PDS (CSC Form 212 Revised 2025)</h2>
        </header>

        <div class="grid grid-cols-1 gap-4 p-4 text-sm md:grid-cols-2 sm:p-6">
            <div class="rounded-lg border bg-gray-50 p-4">
                <p class="font-medium text-gray-800">Prepare your PDS copy</p>
                <p class="mt-1 text-gray-600">Review your updated profile details, then download your PDS copy in CSC Form 212 Revised 2025-style format.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                <a href="<?= htmlspecialchars((string)$pdsReferenceSheetUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex w-full items-center justify-center gap-1 rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 sm:w-auto">
                    <span class="material-symbols-outlined text-sm">description</span>
                    Open PDS Reference
                </a>
                <a href="download-pds.php?job_id=<?= urlencode((string)$jobData['id']) ?>" class="inline-flex w-full items-center justify-center gap-1 rounded-md border border-green-700 px-4 py-2 text-sm text-green-700 hover:bg-green-50 sm:w-auto">
                    <span class="material-symbols-outlined text-sm">download</span>
                    Download PDS (CSC Form 212 Revised 2025)
                </a>
            </div>
        </div>
    </section>

    <section class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800">Qualification Criteria Checklist</h2>
        </header>

        <div
            id="qualificationCriteriaPanel"
            class="p-4 text-sm sm:p-6"
            data-min-education-years="<?= htmlspecialchars((string)($jobData['criteria']['minimum_education_years'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"
            data-min-experience-years="<?= htmlspecialchars((string)($jobData['criteria']['minimum_experience_years'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"
            data-min-training-hours="<?= htmlspecialchars((string)($jobData['criteria']['minimum_training_hours'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"
            data-eligibility-required="<?= !empty($jobData['criteria']['eligibility_required']) ? '1' : '0' ?>"
            data-has-existing-eligibility-proof="<?= !empty($uploadedEligibilityDocument) ? '1' : '0' ?>"
            data-has-existing-training-proof="<?= !empty($trainingFormDefaults['has_training_proof']) ? '1' : '0' ?>"
        >
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <ul class="space-y-2">
                    <?php foreach (($criteriaChecklistItems ?? []) as $criteriaItem): ?>
                        <?php
                        $item = (array)$criteriaItem;
                        $itemMet = (bool)($item['met'] ?? false);
                        $itemKey = (string)($item['key'] ?? '');
                        ?>
                        <li class="rounded-md border px-3 py-2 <?= $itemMet ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>" data-criterion-row="<?= htmlspecialchars($itemKey, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-semibold"><?= htmlspecialchars((string)($item['label'] ?? 'Criteria'), ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium <?= $itemMet ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>" data-criterion-status="<?= htmlspecialchars($itemKey, ENT_QUOTES, 'UTF-8') ?>"><?= $itemMet ? 'Met' : 'Missing' ?></span>
                            </div>
                            <p class="mt-1 text-xs" data-criterion-message="<?= htmlspecialchars($itemKey, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($item['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php if (!empty($profileCompletionPrompt)): ?>
                <div class="mt-3 rounded-md border border-amber-300 bg-amber-50 px-3 py-3 text-xs text-amber-900">
                    <p class="font-medium">Profile completion needed for accurate auto-evaluation</p>
                    <p class="mt-1"><?= htmlspecialchars((string)$profileCompletionPrompt, ENT_QUOTES, 'UTF-8') ?></p>
                    <a href="profile.php?edit=true" class="mt-2 inline-flex items-center gap-1 rounded-md border border-amber-300 bg-white px-2.5 py-1 text-xs font-medium text-amber-800 hover:bg-amber-100">
                        <span class="material-symbols-outlined text-sm">person_edit</span>
                        Complete Profile
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="rounded-xl border bg-white">
        <header class="flex flex-col gap-3 border-b px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Educational Background</h2>
            <button type="button" id="addEducationRow" class="inline-flex items-center gap-1 rounded-md border px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                <span class="material-symbols-outlined text-sm">add</span>
                Add Education Entry
            </button>
        </header>

        <div id="educationRowsContainer" class="space-y-3 p-4 text-sm sm:p-6">
            <?php foreach (($applyEducationEntries ?? []) as $educationEntry): ?>
                <?php $educationRow = (array)$educationEntry; ?>
                <article class="js-education-row rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label class="text-gray-600">Education Level</label>
                            <select name="education_level_entry[]" class="mt-1 w-full rounded-md border px-3 py-2 js-education-level">
                                <option value="">Select Level</option>
                                <?php foreach (($educationLevelOptions ?? []) as $option): ?>
                                    <option value="<?= htmlspecialchars((string)($option['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= ((string)($educationRow['education_level'] ?? '') === (string)($option['value'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars((string)($option['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="text-gray-600">School</label>
                            <input type="text" name="education_school_name_entry[]" value="<?= htmlspecialchars((string)($educationRow['school_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                        </div>
                        <div>
                            <label class="text-gray-600">Course / Degree</label>
                            <input type="text" name="education_course_degree_entry[]" value="<?= htmlspecialchars((string)($educationRow['course_degree'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2 js-education-course-degree">
                            <p class="js-education-course-degree-note mt-1 hidden text-xs text-slate-500">Not applicable for elementary and secondary education</p>
                        </div>
                        <div>
                            <label class="text-gray-600">Year Graduated</label>
                            <input type="text" name="education_year_graduated_entry[]" value="<?= htmlspecialchars((string)($educationRow['year_graduated'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2" placeholder="e.g. 2024">
                        </div>
                    </div>
                    <div class="mt-3 flex justify-end">
                        <button type="button" class="js-remove-education-row inline-flex items-center gap-1 rounded-md border border-rose-300 bg-white px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50">
                            <span class="material-symbols-outlined text-sm">delete</span>
                            Remove
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="rounded-xl border bg-white">
        <header class="flex flex-col gap-3 border-b px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Work Experience</h2>
            <button type="button" id="addWorkRow" class="inline-flex items-center gap-1 rounded-md border px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                <span class="material-symbols-outlined text-sm">add</span>
                Add Work Entry
            </button>
        </header>

        <div id="workRowsContainer" class="space-y-3 p-4 text-sm sm:p-6">
            <?php foreach (($applyWorkExperienceEntries ?? []) as $workEntry): ?>
                <?php $workRow = (array)$workEntry; ?>
                <article class="js-work-row rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-gray-600">Position Title</label>
                            <input type="text" name="work_position_title_entry[]" value="<?= htmlspecialchars((string)($workRow['position_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                        </div>
                        <div>
                            <label class="text-gray-600">Company Name</label>
                            <input type="text" name="work_company_name_entry[]" value="<?= htmlspecialchars((string)($workRow['company_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                        </div>
                        <div>
                            <label class="text-gray-600">Start Date</label>
                            <input type="date" name="work_start_date_entry[]" value="<?= htmlspecialchars((string)($workRow['start_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2 js-work-start-date">
                        </div>
                        <div>
                            <label class="text-gray-600">End Date</label>
                            <input type="date" name="work_end_date_entry[]" value="<?= htmlspecialchars((string)($workRow['end_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2 js-work-end-date">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-gray-600">Brief Description of Responsibilities</label>
                            <textarea rows="2" name="work_responsibilities_entry[]" class="mt-1 w-full rounded-md border px-3 py-2"><?= htmlspecialchars((string)($workRow['responsibilities'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>
                    <div class="mt-3 flex justify-end">
                        <button type="button" class="js-remove-work-row inline-flex items-center gap-1 rounded-md border border-rose-300 bg-white px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50">
                            <span class="material-symbols-outlined text-sm">delete</span>
                            Remove
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>

            <div>
                <label class="text-gray-500">Years of Experience (Auto-computed preview)</label>
                <input type="number" min="0" step="0.1" name="years_experience" value="<?= htmlspecialchars((string)($profileQualificationSnapshot['experience_years_estimate'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2" id="yearsExperiencePreview" readonly>
            </div>
        </div>
    </section>

    <section class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800">Additional Qualifications</h2>
        </header>

        <div class="grid grid-cols-1 gap-4 p-4 text-sm sm:p-6 md:grid-cols-2">
            <div>
                <label class="text-gray-500">Completed Training Hours</label>
                <input type="number" min="0" step="0.1" name="training_hours_completed" id="trainingHoursCompletedInput" value="<?= htmlspecialchars((string)($trainingFormDefaults['training_hours_completed'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
                <p class="mt-1 text-xs text-gray-500">Enter your completed training hours to match this posting's requirement.</p>
            </div>

            <div>
                <label class="text-gray-500">Training Certificate / Proof</label>
                <input
                    id="training_certificate_proof"
                    type="file"
                    name="training_certificate_proof"
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="sr-only js-training-proof-input"
                >
                <label for="training_certificate_proof" class="mt-1 inline-flex cursor-pointer items-center justify-center gap-1 rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100">
                    <span class="material-symbols-outlined text-sm">upload_file</span>
                    Upload Training Proof
                </label>
                <?php if (!empty($trainingFormDefaults['has_training_proof']) && !empty($trainingFormDefaults['training_proof_file_name'])): ?>
                    <p class="mt-1 text-xs text-emerald-700">Existing proof detected: <?= htmlspecialchars((string)$trainingFormDefaults['training_proof_file_name'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <p id="trainingProofFileName" class="mt-1 text-xs text-gray-500">No training proof selected yet.</p>
                <p class="mt-1 text-xs text-gray-500">Accepted: PDF/JPG/PNG. Filename should indicate training/seminar/workshop/certificate.</p>
                <p class="mt-1 text-xs text-amber-700">Draft persistence keeps your entered form values, but browser security still requires re-selecting files after refresh.</p>
            </div>

            <div class="md:col-span-2">
                <label class="text-gray-500">Certifications / Trainings Notes (optional)</label>
                <textarea rows="3" name="certifications_trainings" class="mt-1 w-full rounded-md border px-3 py-2"></textarea>
            </div>
        </div>
    </section>

    <section class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800">Required Documents</h2>
        </header>

        <div class="mx-4 mt-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm sm:mx-6">
            <p class="font-medium text-slate-800">Checklist Progress</p>
            <p class="mt-1 text-slate-700" id="requiredDocumentsProgressSummary" data-total-required="<?= (int)($requiredDocumentSummary['total_required'] ?? 0) ?>">
                <span id="requiredDocumentsProgressCount"><?= (int)($requiredDocumentSummary['fulfilled_required'] ?? 0) ?></span> / <span id="requiredDocumentsProgressTotal"><?= (int)($requiredDocumentSummary['total_required'] ?? 0) ?></span> required documents already uploaded.
                <?php if ((int)($requiredDocumentSummary['missing_required'] ?? 0) > 0): ?>
                    <span id="requiredDocumentsProgressState" class="text-rose-700">Missing <?= (int)$requiredDocumentSummary['missing_required'] ?> required item(s).</span>
                <?php else: ?>
                    <span id="requiredDocumentsProgressState" class="text-emerald-700">All required checklist items are currently fulfilled.</span>
                <?php endif; ?>
            </p>
        </div>

        <div class="mx-4 mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 sm:mx-6">
            <p class="font-medium">Accepted Valid Government IDs</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-amber-900/90">
                <li>Philippine National ID (PhilSys)</li>
                <li>Passport</li>
                <li>Driver’s License</li>
                <li>UMID</li>
                <li>PRC ID</li>
                <li>Voter’s ID or Voter’s Certification</li>
                <li>Postal ID</li>
                <li>SSS ID</li>
                <li>GSIS eCard</li>
                <li>Senior Citizen ID</li>
            </ul>
            <p class="mt-2 text-xs text-amber-800">Upload policy: PDF, DOC, DOCX, JPG, or PNG only, max 5MB per file. Files are validated by extension and detected MIME type.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 p-4 text-sm sm:p-6">
            <?php foreach (($requiredDocumentChecklist ?? []) as $documentConfig): ?>
                <?php
                $inputKey = (string)($documentConfig['key'] ?? '');
                if ($inputKey === '') {
                    continue;
                }
                $label = (string)($documentConfig['label'] ?? 'Required Document');
                $isRequired = (bool)($documentConfig['required'] ?? true);
                $isFulfilled = (bool)($documentConfig['fulfilled'] ?? false);
                $uploadedFileName = cleanText($documentConfig['uploaded_file_name'] ?? null);
                $uploadedAt = cleanText($documentConfig['uploaded_at'] ?? null);
                $uploadedAtDisplay = $uploadedAt ? date('M j, Y g:i A', strtotime($uploadedAt)) : null;
                ?>
                <article data-document-row="<?= htmlspecialchars($inputKey, ENT_QUOTES, 'UTF-8') ?>" data-document-required="<?= $isRequired ? '1' : '0' ?>" class="rounded-xl border px-4 py-4 <?= $isFulfilled ? 'border-emerald-200 bg-emerald-50/70' : ($isRequired ? 'border-rose-200 bg-rose-50/60' : 'border-slate-200 bg-slate-50') ?>">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-900"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                <?php if ($isRequired): ?>
                                    <span class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700">Required</span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-700">Optional</span>
                                <?php endif; ?>
                                <?php if ($isFulfilled): ?>
                                    <span data-document-status="<?= htmlspecialchars($inputKey, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Fulfilled</span>
                                <?php else: ?>
                                    <span data-document-status="<?= htmlspecialchars($inputKey, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Missing</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($isFulfilled && $uploadedFileName !== null): ?>
                                <p class="mt-2 text-xs text-slate-600">
                                    Existing file: <span class="font-medium text-slate-700"><?= htmlspecialchars($uploadedFileName, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($uploadedAtDisplay !== null): ?>
                                        <span class="text-slate-500">(uploaded <?= htmlspecialchars($uploadedAtDisplay, ENT_QUOTES, 'UTF-8') ?>)</span>
                                    <?php endif; ?>
                                </p>
                                <p class="mt-1 text-xs text-slate-500">Upload a new file only if you want to replace this requirement for this application.</p>
                            <?php else: ?>
                                <p class="mt-2 text-xs text-slate-600">No previous upload detected for this checklist item yet.</p>
                            <?php endif; ?>
                        </div>

                        <div class="sm:text-right">
                            <input
                                id="upload_<?= htmlspecialchars($inputKey, ENT_QUOTES, 'UTF-8') ?>"
                                type="file"
                                name="<?= htmlspecialchars($inputKey, ENT_QUOTES, 'UTF-8') ?>"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                class="sr-only js-checklist-upload-input"
                                data-file-target="selected_<?= htmlspecialchars($inputKey, ENT_QUOTES, 'UTF-8') ?>"
                                data-doc-key="<?= htmlspecialchars($inputKey, ENT_QUOTES, 'UTF-8') ?>"
                            >
                            <label for="upload_<?= htmlspecialchars($inputKey, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex cursor-pointer items-center justify-center gap-1 rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                <span class="material-symbols-outlined text-sm">upload_file</span>
                                <?= $isFulfilled ? 'Replace File' : 'Upload File' ?>
                            </label>
                            <p id="selected_<?= htmlspecialchars($inputKey, ENT_QUOTES, 'UTF-8') ?>" class="mt-2 text-xs text-slate-500" data-document-upload-note="<?= htmlspecialchars($inputKey, ENT_QUOTES, 'UTF-8') ?>">No new file selected.</p>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (empty($requiredDocumentChecklist)): ?>
                <p class="text-sm text-slate-600">No document checklist is configured for this posting yet.</p>
            <?php endif; ?>
        </div>
    </section>

    <template id="educationRowTemplate">
        <article class="js-education-row rounded-lg border border-slate-200 bg-slate-50 p-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label class="text-gray-600">Education Level</label>
                    <select name="education_level_entry[]" class="mt-1 w-full rounded-md border px-3 py-2 js-education-level">
                        <option value="">Select Level</option>
                        <?php foreach (($educationLevelOptions ?? []) as $option): ?>
                            <option value="<?= htmlspecialchars((string)($option['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($option['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-gray-600">School</label>
                    <input type="text" name="education_school_name_entry[]" class="mt-1 w-full rounded-md border px-3 py-2">
                </div>
                <div>
                    <label class="text-gray-600">Course / Degree</label>
                    <input type="text" name="education_course_degree_entry[]" class="mt-1 w-full rounded-md border px-3 py-2 js-education-course-degree">
                    <p class="js-education-course-degree-note mt-1 hidden text-xs text-slate-500">Not applicable for elementary, secondary, and college education.</p>
                </div>
                <div>
                    <label class="text-gray-600">Year Graduated</label>
                    <input type="text" name="education_year_graduated_entry[]" class="mt-1 w-full rounded-md border px-3 py-2" placeholder="e.g. 2024">
                </div>
            </div>
            <div class="mt-3 flex justify-end">
                <button type="button" class="js-remove-education-row inline-flex items-center gap-1 rounded-md border border-rose-300 bg-white px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50">
                    <span class="material-symbols-outlined text-sm">delete</span>
                    Remove
                </button>
            </div>
        </article>
    </template>

    <template id="workRowTemplate">
        <article class="js-work-row rounded-lg border border-slate-200 bg-slate-50 p-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="text-gray-600">Position Title</label>
                    <input type="text" name="work_position_title_entry[]" class="mt-1 w-full rounded-md border px-3 py-2">
                </div>
                <div>
                    <label class="text-gray-600">Company Name</label>
                    <input type="text" name="work_company_name_entry[]" class="mt-1 w-full rounded-md border px-3 py-2">
                </div>
                <div>
                    <label class="text-gray-600">Start Date</label>
                    <input type="date" name="work_start_date_entry[]" class="mt-1 w-full rounded-md border px-3 py-2 js-work-start-date">
                </div>
                <div>
                    <label class="text-gray-600">End Date</label>
                    <input type="date" name="work_end_date_entry[]" class="mt-1 w-full rounded-md border px-3 py-2 js-work-end-date">
                </div>
                <div class="md:col-span-2">
                    <label class="text-gray-600">Brief Description of Responsibilities</label>
                    <textarea rows="2" name="work_responsibilities_entry[]" class="mt-1 w-full rounded-md border px-3 py-2"></textarea>
                </div>
            </div>
            <div class="mt-3 flex justify-end">
                <button type="button" class="js-remove-work-row inline-flex items-center gap-1 rounded-md border border-rose-300 bg-white px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50">
                    <span class="material-symbols-outlined text-sm">delete</span>
                    Remove
                </button>
            </div>
        </article>
    </template>

    <script>
    (function () {
        function isValidEligibilityFileName(fileName) {
            var normalized = (fileName || '').toLowerCase();
            return normalized.indexOf('csc') !== -1
                || normalized.indexOf('prc') !== -1
                || normalized.indexOf('eligibility') !== -1;
        }

        function refreshChecklistProgress() {
            var summary = document.getElementById('requiredDocumentsProgressSummary');
            var countNode = document.getElementById('requiredDocumentsProgressCount');
            var totalNode = document.getElementById('requiredDocumentsProgressTotal');
            var stateNode = document.getElementById('requiredDocumentsProgressState');
            if (!summary || !countNode || !totalNode || !stateNode) {
                return;
            }

            var requiredRows = Array.prototype.slice.call(document.querySelectorAll('[data-document-row][data-document-required="1"]'));
            var totalRequired = requiredRows.length;
            var fulfilledRequired = requiredRows.reduce(function (count, row) {
                var badge = row.querySelector('[data-document-status]');
                var badgeText = badge ? (badge.textContent || '').trim().toLowerCase() : '';
                return count + (badgeText === 'fulfilled' ? 1 : 0);
            }, 0);
            var missingRequired = Math.max(0, totalRequired - fulfilledRequired);

            countNode.textContent = String(fulfilledRequired);
            totalNode.textContent = String(totalRequired);

            stateNode.classList.remove('text-rose-700', 'text-emerald-700');
            if (missingRequired > 0) {
                stateNode.textContent = 'Missing ' + String(missingRequired) + ' required item(s).';
                stateNode.classList.add('text-rose-700');
            } else {
                stateNode.textContent = 'All required checklist items are currently fulfilled.';
                stateNode.classList.add('text-emerald-700');
            }
        }

        document.querySelectorAll('.js-checklist-upload-input').forEach(function (input) {
            input.addEventListener('change', function () {
                var targetId = input.getAttribute('data-file-target');
                if (!targetId) {
                    updateRecommendation();
                    return;
                }
                var target = document.getElementById(targetId);
                if (!target) {
                    updateRecommendation();
                    return;
                }
                var fileName = input.files && input.files.length > 0 ? input.files[0].name : 'No new file selected.';
                target.textContent = fileName;

                var docKey = input.getAttribute('data-doc-key') || '';
                var statusBadge = docKey ? document.querySelector('[data-document-status="' + docKey + '"]') : null;
                var row = docKey ? document.querySelector('[data-document-row="' + docKey + '"]') : null;

                if (!statusBadge || !row || !(input.files && input.files.length > 0)) {
                    updateRecommendation();
                    return;
                }

                var isEligibilityKey = docKey === 'eligibility_document';
                var isValidEligibility = !isEligibilityKey
                    || isValidEligibilityFileName(fileName);

                statusBadge.classList.remove('bg-amber-100', 'text-amber-700', 'bg-emerald-100', 'text-emerald-700', 'bg-rose-100', 'text-rose-700');
                row.classList.remove('border-rose-200', 'bg-rose-50/60', 'border-emerald-200', 'bg-emerald-50/70');

                if (isValidEligibility) {
                    statusBadge.textContent = 'Fulfilled';
                    statusBadge.classList.add('bg-emerald-100', 'text-emerald-700');
                    row.classList.add('border-emerald-200', 'bg-emerald-50/70');
                } else {
                    statusBadge.textContent = 'Missing';
                    statusBadge.classList.add('bg-rose-100', 'text-rose-700');
                    row.classList.add('border-rose-200', 'bg-rose-50/60');
                    target.textContent = 'Selected file must indicate CSC or PRC eligibility in the filename.';
                }

                refreshChecklistProgress();
                updateRecommendation();
            });
        });

        refreshChecklistProgress();

        var educationContainer = document.getElementById('educationRowsContainer');
        var workContainer = document.getElementById('workRowsContainer');
        var educationTemplate = document.getElementById('educationRowTemplate');
        var workTemplate = document.getElementById('workRowTemplate');
        var addEducationButton = document.getElementById('addEducationRow');
        var addWorkButton = document.getElementById('addWorkRow');
        var panel = document.getElementById('qualificationCriteriaPanel');
        var recommendationMessage = document.getElementById('systemRecommendationMessage');
        var yearsPreview = document.getElementById('yearsExperiencePreview');
        var trainingHoursInput = document.getElementById('trainingHoursCompletedInput');
        var trainingProofInput = document.getElementById('training_certificate_proof');
        var trainingProofFileName = document.getElementById('trainingProofFileName');
        var eligibilityDocumentInput = document.querySelector('.js-checklist-upload-input[data-doc-key="eligibility_document"]');

        var minEducationYears = parseFloat(panel ? panel.getAttribute('data-min-education-years') || '0' : '0');
        var minExperienceYears = parseFloat(panel ? panel.getAttribute('data-min-experience-years') || '0' : '0');
        var minTrainingHours = parseFloat(panel ? panel.getAttribute('data-min-training-hours') || '0' : '0');
        var eligibilityRequired = (panel ? panel.getAttribute('data-eligibility-required') : '0') === '1';
        var hasExistingEligibilityProof = (panel ? panel.getAttribute('data-has-existing-eligibility-proof') : '0') === '1';
        var hasExistingTrainingProof = (panel ? panel.getAttribute('data-has-existing-training-proof') : '0') === '1';
        var applicationForm = document.querySelector('form[action^="apply.php"]');
        var draftStorageKey = 'applicantApplyDraft:' + (panel ? panel.getAttribute('data-min-training-hours') : 'generic') + ':' + ((applicationForm && applicationForm.querySelector('input[name="job_id"]')) ? applicationForm.querySelector('input[name="job_id"]').value : '');

        function appendTemplate(container, template) {
            if (!container || !template || !template.content) {
                return;
            }
            container.appendChild(template.content.cloneNode(true));
            initializeFlatpickr(container);
            syncAllEducationRows(container);
        }

        function initializeFlatpickr(scope) {
            if (!window.flatpickr) {
                return;
            }

            var root = scope && scope.querySelectorAll ? scope : document;
            root.querySelectorAll('input[type="date"]:not([data-flatpickr-initialized="true"])').forEach(function (input) {
                window.flatpickr(input, {
                    dateFormat: 'Y-m-d',
                    allowInput: false
                });
                input.setAttribute('data-flatpickr-initialized', 'true');
            });
        }

        function syncEducationRowState(row) {
            if (!(row instanceof Element)) {
                return;
            }

            var levelInput = row.querySelector('.js-education-level');
            var courseInput = row.querySelector('.js-education-course-degree');
            var note = row.querySelector('.js-education-course-degree-note');
            if (!(levelInput instanceof HTMLSelectElement) || !(courseInput instanceof HTMLInputElement)) {
                return;
            }

            var isNotApplicable = ['elementary', 'secondary'].indexOf(levelInput.value) !== -1;
            if (isNotApplicable) {
                courseInput.value = '';
                courseInput.disabled = true;
                courseInput.tabIndex = -1;
                courseInput.classList.add('bg-slate-100', 'text-slate-400', 'pointer-events-none');
                courseInput.setAttribute('aria-disabled', 'true');
                if (note instanceof HTMLElement) {
                    note.classList.remove('hidden');
                }
                return;
            }

            courseInput.disabled = false;
            courseInput.tabIndex = 0;
            courseInput.classList.remove('bg-slate-100', 'text-slate-400', 'pointer-events-none');
            courseInput.removeAttribute('aria-disabled');
            if (note instanceof HTMLElement) {
                note.classList.add('hidden');
            }
        }

        function syncAllEducationRows(scope) {
            var root = scope && scope.querySelectorAll ? scope : document;
            root.querySelectorAll('.js-education-row').forEach(function (row) {
                syncEducationRowState(row);
            });
        }

        function ensureRowsCount(container, rowSelector, template, targetCount) {
            if (!container || !template) {
                return;
            }
            while (container.querySelectorAll(rowSelector).length < targetCount) {
                appendTemplate(container, template);
            }
        }

        function readDraftState() {
            if (!applicationForm) {
                return null;
            }

            try {
                var raw = window.localStorage.getItem(draftStorageKey);
                if (!raw) {
                    return null;
                }
                var parsed = JSON.parse(raw);
                return parsed && typeof parsed === 'object' ? parsed : null;
            } catch (error) {
                return null;
            }
        }

        function writeDraftState() {
            if (!applicationForm) {
                return;
            }

            var state = {
                scalars: {},
                arrays: {},
                checks: {},
                fileHints: {}
            };

            applicationForm.querySelectorAll('input, select, textarea').forEach(function (field) {
                if (!field.name || field.disabled) {
                    return;
                }

                var name = field.name;
                var type = (field.type || '').toLowerCase();

                if (type === 'file') {
                    if (field.files && field.files.length > 0) {
                        state.fileHints[name] = field.files[0].name;
                    }
                    return;
                }

                if (type === 'submit' || type === 'button' || type === 'hidden' || type === 'password') {
                    return;
                }

                if (name.slice(-2) === '[]') {
                    if (!Array.isArray(state.arrays[name])) {
                        state.arrays[name] = [];
                    }
                    state.arrays[name].push(field.value);
                    return;
                }

                if (type === 'checkbox' || type === 'radio') {
                    state.checks[name] = !!field.checked;
                    return;
                }

                state.scalars[name] = field.value;
            });

            try {
                window.localStorage.setItem(draftStorageKey, JSON.stringify(state));
            } catch (error) {
            }
        }

        function applyDraftState() {
            var draft = readDraftState();
            if (!draft || !applicationForm) {
                return;
            }

            var educationArrayNames = [
                'education_level_entry[]',
                'education_school_name_entry[]',
                'education_course_degree_entry[]',
                'education_year_graduated_entry[]'
            ];
            var workArrayNames = [
                'work_position_title_entry[]',
                'work_company_name_entry[]',
                'work_start_date_entry[]',
                'work_end_date_entry[]',
                'work_responsibilities_entry[]'
            ];

            var educationMax = 0;
            educationArrayNames.forEach(function (name) {
                var values = Array.isArray(draft.arrays && draft.arrays[name]) ? draft.arrays[name] : [];
                if (values.length > educationMax) {
                    educationMax = values.length;
                }
            });

            var workMax = 0;
            workArrayNames.forEach(function (name) {
                var values = Array.isArray(draft.arrays && draft.arrays[name]) ? draft.arrays[name] : [];
                if (values.length > workMax) {
                    workMax = values.length;
                }
            });

            if (educationMax > 0) {
                ensureRowsCount(educationContainer, '.js-education-row', educationTemplate, educationMax);
            }
            if (workMax > 0) {
                ensureRowsCount(workContainer, '.js-work-row', workTemplate, workMax);
            }

            Object.keys(draft.scalars || {}).forEach(function (name) {
                applicationForm.querySelectorAll('[name="' + name.replace(/"/g, '\\"') + '"]').forEach(function (field) {
                    if (field && (field.type || '').toLowerCase() !== 'file') {
                        field.value = draft.scalars[name];
                    }
                });
            });

            Object.keys(draft.checks || {}).forEach(function (name) {
                applicationForm.querySelectorAll('[name="' + name.replace(/"/g, '\\"') + '"]').forEach(function (field) {
                    var type = (field.type || '').toLowerCase();
                    if (type === 'checkbox' || type === 'radio') {
                        field.checked = !!draft.checks[name];
                    }
                });
            });

            Object.keys(draft.arrays || {}).forEach(function (name) {
                var values = Array.isArray(draft.arrays[name]) ? draft.arrays[name] : [];
                if (values.length === 0) {
                    return;
                }

                var fields = applicationForm.querySelectorAll('[name="' + name.replace(/"/g, '\\"') + '"]');
                values.forEach(function (value, index) {
                    if (fields[index]) {
                        fields[index].value = value;
                    }
                });
            });

            if (draft.fileHints && draft.fileHints.training_certificate_proof && trainingProofFileName) {
                trainingProofFileName.textContent = 'Previously selected: ' + draft.fileHints.training_certificate_proof + ' (please re-select file before submit).';
            }

            document.querySelectorAll('.js-checklist-upload-input').forEach(function (input) {
                var targetId = input.getAttribute('data-file-target');
                if (!targetId || !draft.fileHints || !draft.fileHints[input.name]) {
                    return;
                }

                var target = document.getElementById(targetId);
                if (target) {
                    target.textContent = 'Previously selected: ' + draft.fileHints[input.name] + ' (please re-select file before submit).';
                }
            });
        }

        function ensureAtLeastOneRow(selector, container, template) {
            if (!container) {
                return;
            }
            if (container.querySelectorAll(selector).length === 0) {
                appendTemplate(container, template);
            }
        }

        function yearsFromEducationLevel(level) {
            var key = (level || '').toLowerCase();
            if (key === 'graduate') return 6;
            if (key === 'college') return 4;
            if (key === 'vocational') return 2;
            return 0;
        }

        function computeEducationYears() {
            var levels = educationContainer ? educationContainer.querySelectorAll('.js-education-level') : [];
            var highest = 0;
            levels.forEach(function (el) {
                highest = Math.max(highest, yearsFromEducationLevel(el.value));
            });
            return highest;
        }

        function computeExperienceYears() {
            if (!workContainer) {
                return 0;
            }
            var rows = workContainer.querySelectorAll('.js-work-row');
            var msPerDay = 24 * 60 * 60 * 1000;
            var totalDays = 0;
            var today = new Date();

            rows.forEach(function (row) {
                var startInput = row.querySelector('.js-work-start-date');
                var endInput = row.querySelector('.js-work-end-date');
                if (!startInput || !startInput.value) {
                    return;
                }

                var startDate = new Date(startInput.value + 'T00:00:00');
                var endDate = endInput && endInput.value ? new Date(endInput.value + 'T00:00:00') : today;
                if (isNaN(startDate.getTime()) || isNaN(endDate.getTime()) || endDate < startDate) {
                    return;
                }

                totalDays += Math.floor((endDate - startDate) / msPerDay) + 1;
            });

            return Math.round((totalDays / 365) * 100) / 100;
        }

        function setCriterionStatus(key, ok, message) {
            var row = document.querySelector('[data-criterion-row="' + key + '"]');
            if (!row) {
                return;
            }

            row.classList.remove('border-slate-200', 'bg-slate-50', 'border-rose-200', 'bg-rose-50', 'text-rose-800', 'border-emerald-200', 'bg-emerald-50', 'text-emerald-800');
            row.classList.add(ok ? 'border-emerald-200' : 'border-rose-200');
            row.classList.add(ok ? 'bg-emerald-50' : 'bg-rose-50');

            var statusTarget = row.querySelector('[data-criterion-status="' + key + '"]');
            if (statusTarget) {
                statusTarget.textContent = ok ? 'Met' : 'Missing';
                statusTarget.classList.remove('bg-rose-100', 'text-rose-700', 'bg-emerald-100', 'text-emerald-700');
                statusTarget.classList.add(ok ? 'bg-emerald-100' : 'bg-rose-100');
                statusTarget.classList.add(ok ? 'text-emerald-700' : 'text-rose-700');
            }

            var messageTarget = row.querySelector('[data-criterion-message="' + key + '"]');
            if (messageTarget) {
                messageTarget.textContent = message;
                messageTarget.classList.remove('text-slate-700', 'text-rose-800', 'text-emerald-800');
                messageTarget.classList.add(ok ? 'text-emerald-800' : 'text-rose-800');
            }
        }

        function updateRecommendation() {
            var educationYears = computeEducationYears();
            var experienceYears = computeExperienceYears();
            var trainingHours = trainingHoursInput ? parseFloat(trainingHoursInput.value || '0') : 0;
            if (isNaN(trainingHours) || trainingHours < 0) {
                trainingHours = 0;
            }
            var selectedTrainingFile = trainingProofInput && trainingProofInput.files && trainingProofInput.files.length > 0
                ? trainingProofInput.files[0].name
                : '';
            var hasSelectedTrainingProof = selectedTrainingFile !== '';
            var trainingProofOk = minTrainingHours <= 0
                ? true
                : (hasExistingTrainingProof || hasSelectedTrainingProof);
            var selectedEligibilityFileName = eligibilityDocumentInput && eligibilityDocumentInput.files && eligibilityDocumentInput.files.length > 0
                ? eligibilityDocumentInput.files[0].name
                : '';
            var hasSelectedEligibilityProof = selectedEligibilityFileName !== '';
            var eligibilityOk = !eligibilityRequired
                ? true
                : (hasExistingEligibilityProof || (hasSelectedEligibilityProof && isValidEligibilityFileName(selectedEligibilityFileName)));
            if (yearsPreview) {
                yearsPreview.value = experienceYears.toFixed(2);
            }

            var educationOk = educationYears >= minEducationYears;
            var experienceOk = experienceYears >= minExperienceYears;
            var trainingOk = minTrainingHours <= 0
                ? true
                : (trainingHours >= minTrainingHours && trainingProofOk);

            setCriterionStatus('education', educationOk, educationOk
                ? 'You meet the education requirement based on current entries.'
                : 'You are missing the education requirement based on current entries.');

            setCriterionStatus('experience', experienceOk, experienceOk
                ? (minExperienceYears <= 0
                    ? 'No work experience minimum for this posting.'
                    : 'You meet the experience requirement based on current entries.')
                : 'You are missing the required years of experience based on current entries.');

            setCriterionStatus('training', trainingOk, trainingOk
                ? 'You meet the training requirement based on submitted hours and proof.'
                : 'You are missing required training hours or a training certificate/proof document.');

            setCriterionStatus('eligibility', eligibilityOk, eligibilityOk
                ? (eligibilityRequired
                    ? 'Eligibility requirement appears supported by your CSC/PRC document upload.'
                    : 'No eligibility requirement for this posting.')
                : 'Eligibility appears required; upload a valid CSC/PRC document in the checklist below.');

            if (!recommendationMessage) {
                return;
            }

            var missing = [];
            if (!eligibilityOk) missing.push('Eligibility');
            if (!educationOk) missing.push('Education');
            if (!experienceOk) missing.push('Experience');
            if (!trainingOk) missing.push('Training');

            recommendationMessage.classList.remove('border-amber-200', 'bg-amber-50', 'text-amber-800', 'border-emerald-200', 'bg-emerald-50', 'text-emerald-800');

            if (missing.length === 0) {
                recommendationMessage.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-800');
                recommendationMessage.innerHTML = '<span class="font-semibold">System Recommendation:</span> Eligibility, education, experience, and training entries currently meet the configured thresholds. Final validation remains subject to document verification.';
            } else {
                recommendationMessage.classList.add('border-amber-200', 'bg-amber-50', 'text-amber-800');
                recommendationMessage.innerHTML = '<span class="font-semibold">System Recommendation:</span> You meet some criteria but are missing: ' + missing.join(', ') + '.';
            }
        }

        if (trainingProofInput) {
            trainingProofInput.addEventListener('change', function () {
                var selectedName = trainingProofInput.files && trainingProofInput.files.length > 0
                    ? trainingProofInput.files[0].name
                    : 'No training proof selected yet.';

                if (trainingProofFileName) {
                    trainingProofFileName.textContent = selectedName;
                }

                updateRecommendation();
            });
        }

        if (trainingHoursInput) {
            trainingHoursInput.addEventListener('input', function () {
                updateRecommendation();
            });
        }

        if (addEducationButton) {
            addEducationButton.addEventListener('click', function () {
                appendTemplate(educationContainer, educationTemplate);
                updateRecommendation();
            });
        }

        if (addWorkButton) {
            addWorkButton.addEventListener('click', function () {
                appendTemplate(workContainer, workTemplate);
                updateRecommendation();
            });
        }

        document.addEventListener('click', function (event) {
            var removeEducation = event.target.closest('.js-remove-education-row');
            if (removeEducation) {
                var row = removeEducation.closest('.js-education-row');
                if (row) {
                    row.remove();
                    ensureAtLeastOneRow('.js-education-row', educationContainer, educationTemplate);
                    updateRecommendation();
                }
            }

            var removeWork = event.target.closest('.js-remove-work-row');
            if (removeWork) {
                var workRow = removeWork.closest('.js-work-row');
                if (workRow) {
                    workRow.remove();
                    ensureAtLeastOneRow('.js-work-row', workContainer, workTemplate);
                    updateRecommendation();
                }
            }
        });

        document.addEventListener('input', function (event) {
            if (event.target.closest('#educationRowsContainer') || event.target.closest('#workRowsContainer')) {
                updateRecommendation();
            }

            if (event.target.closest('form[action^="apply.php"]')) {
                writeDraftState();
            }
        });

        document.addEventListener('change', function (event) {
            if (event.target.closest('#educationRowsContainer') && event.target.classList.contains('js-education-level')) {
                var educationRow = event.target.closest('.js-education-row');
                if (educationRow) {
                    syncEducationRowState(educationRow);
                }
                updateRecommendation();
            }

            if (event.target.closest('form[action^="apply.php"]')) {
                writeDraftState();
            }
        });

        if (applicationForm) {
            applicationForm.addEventListener('submit', function () {
                try {
                    window.localStorage.removeItem(draftStorageKey);
                } catch (error) {
                }
            });
        }

        initializeFlatpickr(document);
        applyDraftState();
        syncAllEducationRows(document);

        updateRecommendation();
    })();
    </script>

    <section class="rounded-xl border bg-white">
        <div class="p-4 text-sm sm:p-6">
            <label class="flex items-start gap-2">
                <input type="checkbox" name="consent_declaration" value="1" required class="mt-1">
                <span class="text-gray-700">
                    I hereby certify that the information provided is true and correct
                    to the best of my knowledge. I understand that any false information
                    may result in disqualification.
                </span>
            </label>
        </div>
    </section>

    <section class="md:sticky md:bottom-0 md:z-20 rounded-xl border bg-white/95 p-4 backdrop-blur">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a href="job-list.php" class="inline-flex items-center justify-center gap-1 rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                Cancel
            </a>

            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-green-700 px-5 py-2 text-sm font-medium text-white hover:bg-green-800">
                <span class="material-symbols-outlined text-sm">send</span>
                Submit Application
            </button>
        </div>
    </section>
</form>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
