<?php
require_once __DIR__ . '/includes/applicant-registration/bootstrap.php';
require_once __DIR__ . '/includes/applicant-registration/actions.php';

$pageTitle = 'Applicant Registration | Staff';
$activePage = 'recruitment.php';
$breadcrumbs = ['Recruitment', 'Applicant Registration'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/staff/applicant-registration/index.js';

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$applicantRegistrationPartial = trim((string)($_GET['partial'] ?? ''));

if ($applicantRegistrationPartial === 'registration-list') {
    $applicantRegistrationDataStage = 'list';
    require_once __DIR__ . '/includes/applicant-registration/data.php';
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    $registrationContentSection = 'list';
    require __DIR__ . '/includes/applicant-registration/content.php';
    exit;
}

if ($applicantRegistrationPartial === 'registration-detail') {
    $applicantRegistrationDataStage = 'detail';
    $registrationDetailApplicationId = trim((string)($_GET['application_id'] ?? ''));
    require_once __DIR__ . '/includes/applicant-registration/data.php';
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    if ($dataLoadError !== null || !is_array($registrationDetailPayload)) {
        http_response_code(400);
        echo json_encode([
            'error' => $dataLoadError ?: 'Applicant registration detail could not be loaded.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode($registrationDetailPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Applicant Registration</h1>
    <p class="text-sm text-gray-500">Read-only applicant registration review for profile and document checking.</p>
</div>

<?php if ($state && $message): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section
    id="staffApplicantRegistrationAsyncRegion"
    data-registration-list-url="applicant-registration.php?partial=registration-list"
    data-registration-detail-url="applicant-registration.php?partial=registration-detail"
>
    <div id="staffApplicantRegistrationListSkeleton" class="space-y-6" aria-live="polite" role="status">
        <section class="bg-white border rounded-xl mb-6">
            <header class="px-6 py-4 border-b">
                <div class="h-5 w-48 animate-pulse rounded bg-slate-200"></div>
                <div class="mt-2 h-4 w-96 animate-pulse rounded bg-slate-200"></div>
            </header>
            <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="md:col-span-2 h-10 animate-pulse rounded bg-slate-100"></div>
                <div class="h-10 animate-pulse rounded bg-slate-100"></div>
            </div>
            <div class="p-6 space-y-3">
                <?php for ($index = 0; $index < 5; $index += 1): ?>
                    <div class="h-14 animate-pulse rounded bg-slate-100"></div>
                <?php endfor; ?>
            </div>
        </section>
    </div>

    <div id="staffApplicantRegistrationListError" class="hidden mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" aria-live="polite">
        <p class="font-medium">Applicant registration list could not be loaded.</p>
        <p class="mt-1">Retry to load the first-page intake queue.</p>
        <button type="button" id="staffApplicantRegistrationListRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry list</button>
    </div>

    <div id="staffApplicantRegistrationListContent" class="hidden"></div>
</section>

<div id="registrationModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-registration-modal-close="registrationModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Applicant Profile</h3>
                <button type="button" data-registration-modal-close="registrationModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <div id="registrationForm" class="flex-1 min-h-0 flex flex-col">
                <input type="hidden" id="registrationApplicationId" value="">

                <div class="flex-1 min-h-0 overflow-y-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase text-slate-500 tracking-wide">Selected Applicant</p>
                        <p id="registrationApplicantName" class="text-base font-semibold text-slate-800 mt-1">-</p>
                        <p id="registrationApplicantMeta" class="text-sm text-slate-600 mt-1">-</p>
                        <p id="registrationApplicantContact" class="text-xs text-slate-500 mt-2">-</p>
                        <p id="registrationApplicationRef" class="text-xs text-slate-500 mt-1">-</p>
                    </div>

                    <div class="md:col-span-2 rounded-lg border border-slate-200 bg-blue-50 px-4 py-3 text-slate-700">
                        Staff access is read-only in applicant registration. Review the applicant profile and submitted documents only.
                    </div>

                    <div class="md:col-span-2">
                        <p class="text-slate-600">Submitted Documents</p>
                        <div class="mt-2 overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="text-left px-3 py-2">File Name</th>
                                        <th class="text-left px-3 py-2">Uploaded</th>
                                        <th class="text-left px-3 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="registrationDocumentsBody" class="divide-y divide-slate-100">
                                    <tr><td class="px-3 py-3 text-slate-500" colspan="3">No document selected.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-registration-modal-close="registrationModal" class="px-4 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
