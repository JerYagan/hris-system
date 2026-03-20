<?php
require_once __DIR__ . '/includes/applicant-tracking/bootstrap.php';
require_once __DIR__ . '/includes/applicant-tracking/actions.php';

$pageTitle = 'Applicant Tracking | Staff';
$activePage = 'recruitment.php';
$breadcrumbs = ['Recruitment', 'Applicant Tracking'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/staff/applicant-tracking/index.js';

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$applicantTrackingPartial = trim((string)($_GET['partial'] ?? ''));

if ($applicantTrackingPartial === 'tracking-postings') {
    $trackingDataStage = 'postings';
    require_once __DIR__ . '/includes/applicant-tracking/data.php';
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    $trackingContentSection = 'postings';
    require __DIR__ . '/includes/applicant-tracking/content.php';
    exit;
}

if ($applicantTrackingPartial === 'tracking-applicants') {
    $trackingDataStage = 'applicants';
    require_once __DIR__ . '/includes/applicant-tracking/data.php';
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    $trackingContentSection = 'applicants';
    require __DIR__ . '/includes/applicant-tracking/content.php';
    exit;
}

if ($applicantTrackingPartial === 'tracking-detail') {
    $trackingDataStage = 'detail';
    $trackingSelectedApplicationId = trim((string)($_GET['application_id'] ?? ''));
    require_once __DIR__ . '/includes/applicant-tracking/data.php';
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    if ($dataLoadError !== null || !is_array($trackingDetailPayload)) {
        http_response_code(400);
        echo json_encode([
            'error' => $dataLoadError ?: 'Applicant tracking detail could not be loaded.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode($trackingDetailPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Applicant Tracking</h1>
    <p class="text-sm text-gray-500">Read-only tracking shell with deferred posting queues, applicant lists, and profile history.</p>
</div>

<div id="trackingFlashState" class="hidden" data-state="<?= htmlspecialchars((string)($state ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-message="<?= htmlspecialchars((string)($message ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>

<section
    id="staffApplicantTrackingAsyncRegion"
    data-tracking-postings-url="applicant-tracking.php?partial=tracking-postings"
    data-tracking-applicants-url="applicant-tracking.php?partial=tracking-applicants"
    data-tracking-detail-url="applicant-tracking.php?partial=tracking-detail"
>
    <div class="space-y-6">
        <div>
            <div id="staffApplicantTrackingPostingsSkeleton" class="space-y-4" aria-live="polite" role="status">
                <section class="bg-white border rounded-xl">
                    <header class="px-6 py-4 border-b">
                        <div class="h-5 w-40 animate-pulse rounded bg-slate-200"></div>
                        <div class="mt-2 h-4 w-64 animate-pulse rounded bg-slate-200"></div>
                    </header>
                    <div class="p-6 space-y-3">
                        <?php for ($index = 0; $index < 5; $index += 1): ?>
                            <div class="h-14 animate-pulse rounded bg-slate-100"></div>
                        <?php endfor; ?>
                    </div>
                </section>
            </div>

            <div id="staffApplicantTrackingPostingsError" class="hidden rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" aria-live="polite">
                <p class="font-medium">Posting queue could not be loaded.</p>
                <button type="button" id="staffApplicantTrackingPostingsRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry postings</button>
            </div>

            <div id="staffApplicantTrackingPostingsContent" class="hidden"></div>
        </div>

        <div>
            <div id="staffApplicantTrackingApplicantsSkeleton" class="space-y-4" aria-live="polite" role="status">
                <section class="bg-white border rounded-xl">
                    <header class="px-6 py-4 border-b">
                        <div class="h-5 w-44 animate-pulse rounded bg-slate-200"></div>
                        <div class="mt-2 h-4 w-80 animate-pulse rounded bg-slate-200"></div>
                    </header>
                    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="md:col-span-2 h-10 animate-pulse rounded bg-slate-100"></div>
                        <div class="h-10 animate-pulse rounded bg-slate-100"></div>
                    </div>
                    <div class="p-6 space-y-3">
                        <?php for ($index = 0; $index < 6; $index += 1): ?>
                            <div class="h-14 animate-pulse rounded bg-slate-100"></div>
                        <?php endfor; ?>
                    </div>
                </section>
            </div>

            <div id="staffApplicantTrackingApplicantsError" class="hidden rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" aria-live="polite">
                <p class="font-medium">Applicant list could not be loaded.</p>
                <button type="button" id="staffApplicantTrackingApplicantsRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry applicants</button>
            </div>

            <div id="staffApplicantTrackingApplicantsContent" class="hidden"></div>
        </div>
    </div>
</section>

<div id="trackingProfileModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-tracking-modal-close="trackingProfileModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-5xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Applicant Profile</h3>
                <button type="button" data-tracking-modal-close="trackingProfileModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <div class="flex-1 min-h-0 flex flex-col" id="trackingProfileModalForm">
                <input type="hidden" id="trackingProfileApplicationId" value="">
                <div class="flex-1 min-h-0 overflow-y-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase text-slate-500 tracking-wide">Selected Applicant</p>
                        <p id="trackingProfileApplicantName" class="text-base font-semibold text-slate-800 mt-1">-</p>
                        <p id="trackingProfileApplicantMeta" class="text-sm text-slate-600 mt-1">-</p>
                        <p id="trackingProfileApplicantContact" class="text-xs text-slate-500 mt-2">-</p>
                        <p id="trackingProfileApplicationRef" class="text-xs text-slate-500 mt-1">-</p>
                    </div>

                    <div class="md:col-span-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-slate-700">
                        Staff tracking is read-only. Applicant documents, interview history, and feedback load only after a profile is opened, and add-as-employee follow-up actions stay outside this initial page payload.
                    </div>

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
                                <tbody id="trackingProfileDocumentsBody" class="divide-y divide-slate-100">
                                    <tr><td class="px-3 py-3 text-slate-500" colspan="4">No document selected.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <p class="text-slate-600">Feedback History</p>
                        <div class="mt-2 border border-slate-200 rounded-lg bg-white p-3">
                            <div id="trackingProfileFeedbackList" class="space-y-3 text-sm text-slate-700">
                                <p class="text-slate-500">No feedback selected.</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="text-slate-600">Interview History</p>
                        <div class="mt-2 border border-slate-200 rounded-lg bg-white p-3">
                            <div id="trackingProfileInterviewList" class="space-y-3 text-sm text-slate-700">
                                <p class="text-slate-500">No interview history selected.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-tracking-modal-close="trackingProfileModal" class="px-4 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
