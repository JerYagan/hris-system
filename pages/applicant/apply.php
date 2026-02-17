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
                <label class="text-gray-500">Office / Department</label>
                <input type="text" value="<?= htmlspecialchars((string)$jobData['office_name'], ENT_QUOTES, 'UTF-8') ?>" readonly class="mt-1 w-full rounded-md border bg-gray-100 px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Plantilla Item No.</label>
                <input type="text" value="<?= htmlspecialchars((string)($jobData['plantilla_item_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>" readonly class="mt-1 w-full rounded-md border bg-gray-100 px-3 py-2">
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
            <h2 class="text-lg font-semibold text-gray-800">Educational Background</h2>
        </header>

        <div class="grid grid-cols-1 gap-6 p-4 text-sm md:grid-cols-3 sm:p-6">
            <div>
                <label class="text-gray-500">Highest Educational Attainment</label>
                <select name="education_attainment" class="mt-1 w-full rounded-md border px-3 py-2">
                    <option value="">Select</option>
                    <?php foreach ($educationOptions as $option): ?>
                        <option value="<?= htmlspecialchars((string)$option, ENT_QUOTES, 'UTF-8') ?>" <?= (($educationFormDefaults['education_attainment'] ?? '') === $option) ? 'selected' : '' ?>><?= htmlspecialchars((string)$option, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="text-gray-500">Course / Strand</label>
                <input type="text" name="course_strand" value="<?= htmlspecialchars((string)($educationFormDefaults['course_strand'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">School / Institution</label>
                <input type="text" name="school_institution" value="<?= htmlspecialchars((string)($educationFormDefaults['school_institution'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>
        </div>
    </section>

    <section class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800">Work Experience</h2>
        </header>

        <div class="grid grid-cols-1 gap-6 p-4 text-sm md:grid-cols-3 sm:p-6">
            <div>
                <label class="text-gray-500">Most Recent Position</label>
                <input type="text" name="recent_position" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Company / Organization</label>
                <input type="text" name="company_organization" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Years of Experience</label>
                <input type="number" min="0" step="0.1" name="years_experience" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>
        </div>
    </section>

    <section class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800">Additional Qualifications</h2>
        </header>

        <div class="p-4 text-sm sm:p-6">
            <label class="text-gray-500">Certifications / Trainings (if any)</label>
            <textarea rows="3" name="certifications_trainings" class="mt-1 w-full rounded-md border px-3 py-2"></textarea>
        </div>
    </section>

    <section class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800">Required Documents</h2>
        </header>

        <div class="grid grid-cols-1 gap-6 p-4 text-sm md:grid-cols-2 sm:p-6">
            <?php foreach (($requiredDocumentConfig ?? []) as $documentConfig): ?>
                <?php
                $inputKey = (string)($documentConfig['key'] ?? '');
                if ($inputKey === '') {
                    continue;
                }
                $label = (string)($documentConfig['label'] ?? 'Required Document');
                $isRequired = (bool)($documentConfig['required'] ?? true);
                ?>
                <div>
                    <label class="text-gray-500"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?><?= $isRequired ? ' *' : '' ?></label>
                    <input
                        type="file"
                        name="<?= htmlspecialchars($inputKey, ENT_QUOTES, 'UTF-8') ?>"
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                        <?= $isRequired ? 'required' : '' ?>
                        class="mt-1 w-full rounded-md border px-3 py-2"
                    >
                </div>
            <?php endforeach; ?>
        </div>
    </section>

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
