<?php
require_once __DIR__ . '/includes/recruitment/bootstrap.php';
require_once __DIR__ . '/includes/recruitment/actions.php';

$pageTitle = 'Recruitment | Staff';
$activePage = 'recruitment.php';
$breadcrumbs = ['Recruitment'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/staff/recruitment/index.js';

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$recruitmentPartial = (string)($_GET['partial'] ?? '');
if ($recruitmentPartial === 'recruitment-posting-view') {
    $recruitmentDataStage = 'posting-view';
    require_once __DIR__ . '/includes/recruitment/data.php';
    $statusCode = is_array($recruitmentPostingViewPayload ?? null) ? 200 : 404;
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo (string)json_encode(
        is_array($recruitmentPostingViewPayload ?? null)
            ? $recruitmentPostingViewPayload
            : ['error' => 'Recruitment posting details could not be loaded.'],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    exit;
}

if ($recruitmentPartial === 'recruitment-applicant-view') {
    $recruitmentDataStage = 'applicant-view';
    require_once __DIR__ . '/includes/recruitment/data.php';
    $statusCode = is_array($recruitmentApplicantViewPayload ?? null) ? 200 : 404;
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo (string)json_encode(
        is_array($recruitmentApplicantViewPayload ?? null)
            ? $recruitmentApplicantViewPayload
            : ['error' => 'Recruitment applicant details could not be loaded.'],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    exit;
}

if (in_array($recruitmentPartial, ['recruitment-summary', 'recruitment-listings', 'recruitment-secondary'], true)) {
    $recruitmentDataStage = match ($recruitmentPartial) {
        'recruitment-summary' => 'summary',
        'recruitment-listings' => 'listings',
        default => 'secondary',
    };
    $recruitmentContentSection = $recruitmentDataStage;
    require_once __DIR__ . '/includes/recruitment/data.php';
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    require __DIR__ . '/includes/recruitment/content.php';
    exit;
}

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Recruitment</h1>
    <p class="text-sm text-gray-500">Read-only view of division-scoped job postings and applicant pipeline details.</p>
</div>

<div id="recruitmentFlashState" class="hidden" data-state="<?= htmlspecialchars((string)$state, ENT_QUOTES, 'UTF-8') ?>" data-message="<?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>"></div>

<?php if ($state && $message): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Recruitment Modules</h2>
            <p class="text-sm text-gray-500 mt-1">Open detailed flows for applicant registration and tracking.</p>
        </div>
        <div class="flex gap-2">
            <a href="applicant-registration.php" class="px-4 py-2 rounded-md bg-green-700 text-white text-sm hover:bg-green-800">Applicant Registration</a>
            <a href="applicant-tracking.php" class="px-4 py-2 rounded-md border text-sm text-gray-700 hover:bg-gray-50">Applicant Tracking</a>
        </div>
    </header>
</section>

<section
    id="staffRecruitmentAsyncRegion"
    data-recruitment-summary-url="recruitment.php?partial=recruitment-summary"
    data-recruitment-listings-url="recruitment.php?partial=recruitment-listings"
    data-recruitment-secondary-url="recruitment.php?partial=recruitment-secondary"
    data-recruitment-posting-view-url="recruitment.php?partial=recruitment-posting-view"
    data-recruitment-applicant-view-url="recruitment.php?partial=recruitment-applicant-view"
>
    <div id="staffRecruitmentSummarySkeleton" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6" aria-live="polite" role="status">
        <?php for ($index = 0; $index < 4; $index += 1): ?>
            <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[120px]">
                <div class="h-4 w-28 rounded bg-slate-200 animate-pulse"></div>
                <div class="mt-4 h-8 w-16 rounded bg-slate-200 animate-pulse"></div>
                <div class="mt-3 h-3 w-full rounded bg-slate-100 animate-pulse"></div>
            </article>
        <?php endfor; ?>
    </div>
    <div id="staffRecruitmentSummaryError" class="hidden mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <p class="font-medium">Recruitment summary could not be loaded.</p>
        <button type="button" id="staffRecruitmentSummaryRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry summary</button>
    </div>
    <div id="staffRecruitmentSummaryContent" class="hidden"></div>

    <div id="staffRecruitmentListingsSkeleton" class="bg-white border border-slate-200 rounded-xl mb-6 p-6" aria-live="polite" role="status">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <div>
                <div class="h-5 w-40 rounded bg-slate-200 animate-pulse"></div>
                <div class="mt-2 h-4 w-64 rounded bg-slate-100 animate-pulse"></div>
            </div>
            <div class="h-9 w-28 rounded-full bg-slate-100 animate-pulse"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <div class="md:col-span-2 h-10 rounded bg-slate-100 animate-pulse"></div>
            <div class="h-10 rounded bg-slate-100 animate-pulse"></div>
        </div>
        <div class="space-y-3">
            <?php for ($index = 0; $index < 5; $index += 1): ?>
                <div class="h-14 rounded bg-slate-100 animate-pulse"></div>
            <?php endfor; ?>
        </div>
    </div>
    <div id="staffRecruitmentListingsError" class="hidden mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <p class="font-medium">Active job listings could not be loaded.</p>
        <button type="button" id="staffRecruitmentListingsRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry listings</button>
    </div>
    <div id="staffRecruitmentListingsContent" class="hidden"></div>

    <div id="staffRecruitmentSecondarySkeleton" class="space-y-6" aria-live="polite" role="status">
        <?php for ($section = 0; $section < 2; $section += 1): ?>
            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <div class="h-5 w-48 rounded bg-slate-200 animate-pulse"></div>
                <div class="mt-4 space-y-3">
                    <?php for ($row = 0; $row < 4; $row += 1): ?>
                        <div class="h-12 rounded bg-slate-100 animate-pulse"></div>
                    <?php endfor; ?>
                </div>
            </section>
        <?php endfor; ?>
    </div>
    <div id="staffRecruitmentSecondaryError" class="hidden rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <p class="font-medium">Recruitment secondary sections could not be loaded.</p>
        <button type="button" id="staffRecruitmentSecondaryRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry sections</button>
    </div>
    <div id="staffRecruitmentSecondaryContent" class="hidden"></div>
</section>


<div id="postingViewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4" data-csrf-token="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <div class="w-full max-w-5xl rounded-xl bg-white border shadow-lg max-h-[calc(100vh-2rem)] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">View Position</h3>
            <button type="button" id="postingViewModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6 space-y-5 text-sm">
            <div id="postingViewFeedback" class="hidden rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-500">Position</p>
                    <p id="postingViewPosition" class="font-medium text-gray-800">-</p>
                </div>
                <div>
                    <p class="text-gray-500">Division</p>
                    <p id="postingViewOffice" class="font-medium text-gray-800">-</p>
                </div>
                <div>
                    <p class="text-gray-500">Employment Type</p>
                    <p id="postingViewEmploymentType" class="font-medium text-gray-800">-</p>
                </div>
                <div>
                    <p class="text-gray-500">Status</p>
                    <p id="postingViewStatus" class="font-medium text-gray-800">-</p>
                </div>
                <div>
                    <p class="text-gray-500">Open Date</p>
                    <p id="postingViewOpenDate" class="font-medium text-gray-800">-</p>
                </div>
                <div>
                    <p class="text-gray-500">Deadline</p>
                    <p id="postingViewCloseDate" class="font-medium text-gray-800">-</p>
                </div>
            </div>
            <div>
                <p class="text-gray-500">Description</p>
                <p id="postingViewDescription" class="mt-1 text-gray-800 whitespace-pre-line">-</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-500">Qualifications</p>
                    <p id="postingViewQualifications" class="mt-1 text-gray-800 whitespace-pre-line">-</p>
                </div>
                <div>
                    <p class="text-gray-500">Responsibilities</p>
                    <p id="postingViewResponsibilities" class="mt-1 text-gray-800 whitespace-pre-line">-</p>
                </div>
            </div>
            <div>
                <p class="text-gray-500">Application Requirements</p>
                <ul id="postingViewRequirements" class="mt-2 list-disc pl-6 text-gray-800 space-y-1"></ul>
            </div>
            <div>
                <p class="text-gray-500 mb-2">Applicants</p>
                <div class="overflow-x-auto border rounded-lg">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="text-left px-3 py-2">Applicant</th>
                                <th class="text-left px-3 py-2">Applied Position</th>
                                <th class="text-left px-3 py-2">Date Submitted</th>
                                <th class="text-left px-3 py-2">Initial Screening</th>
                                <th class="text-left px-3 py-2">Basis</th>
                                <th class="text-left px-3 py-2">Action</th>
                            </tr>
                        </thead>
                        <tbody id="postingViewApplicantsBody" class="divide-y"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t bg-white flex justify-end">
            <button type="button" id="postingViewModalCancel" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Close</button>
        </div>
    </div>
</div>

<div id="staffApplicantDecisionModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-staff-applicant-modal-close="staffApplicantDecisionModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Applicant Profile</h3>
                <button type="button" data-staff-applicant-modal-close="staffApplicantDecisionModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <div class="flex-1 min-h-0 flex flex-col" id="staffApplicantDecisionForm">
                <input type="hidden" id="staffDecisionApplicationId" value="">
                <div class="flex-1 min-h-0 overflow-y-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase text-slate-500 tracking-wide">Selected Applicant</p>
                        <p id="staffApplicantProfileName" class="text-base font-semibold text-slate-800 mt-1">-</p>
                        <p id="staffApplicantProfileMeta" class="text-sm text-slate-600 mt-1">-</p>
                        <div id="staffApplicantEmployeeAction" class="mt-3"></div>
                    </div>
                    <div class="md:col-span-2 rounded-lg border border-slate-200 bg-blue-50 px-4 py-3 text-slate-700">
                        Staff access in this module is read-only. Use this modal to review applicant documents plus deferred feedback and interview history.
                    </div>
                    <div id="staffApplicantProfileStatus" class="md:col-span-2 hidden rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800"></div>
                    <div class="md:col-span-2">
                        <p class="text-slate-600">Submitted Documents</p>
                        <div class="mt-2 overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="text-left px-3 py-2">Document Type</th>
                                        <th class="text-left px-3 py-2">File Name</th>
                                        <th class="text-left px-3 py-2">Uploaded</th>
                                        <th class="text-left px-3 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="staffApplicantDocumentsBody" class="divide-y divide-slate-100">
                                    <tr><td class="px-3 py-3 text-slate-500" colspan="4">No document selected.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div>
                        <p class="text-slate-600">Feedback History</p>
                        <div class="mt-2 border border-slate-200 rounded-lg bg-white p-3">
                            <div id="staffApplicantFeedbackList" class="space-y-3 text-sm text-slate-700">
                                <p class="text-slate-500">No feedback selected.</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <p class="text-slate-600">Interview History</p>
                        <div class="mt-2 border border-slate-200 rounded-lg bg-white p-3">
                            <div id="staffApplicantInterviewList" class="space-y-3 text-sm text-slate-700">
                                <p class="text-slate-500">No interview history selected.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-staff-applicant-modal-close="staffApplicantDecisionModal" class="px-4 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
