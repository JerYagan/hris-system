<?php
require_once __DIR__ . '/includes/evaluation/bootstrap.php';
require_once __DIR__ . '/includes/evaluation/actions.php';

$evaluationPartial = trim((string)($_GET['partial'] ?? ''));

if ($evaluationPartial === 'list') {
    $evaluationDataStage = 'list';
    require_once __DIR__ . '/includes/evaluation/data.php';
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    $evaluationContentSection = 'list';
    require __DIR__ . '/includes/evaluation/content.php';
    exit;
}

if ($evaluationPartial === 'detail') {
    $evaluationDataStage = 'detail';
    $ruleEvaluationDetailApplicationId = trim((string)($_GET['application_id'] ?? ''));
    require_once __DIR__ . '/includes/evaluation/data.php';
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    if ($dataLoadError !== null || !is_array($ruleEvaluationDetailPayload)) {
        http_response_code(400);
        echo json_encode([
            'error' => $dataLoadError ?: 'Evaluation detail could not be loaded.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode($ruleEvaluationDetailPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$ruleEvaluationSummary = [
    'qualified' => null,
    'not_qualified' => null,
    'total' => null,
];
$dataLoadError = null;

$pageTitle = 'Evaluation | Staff';
$activePage = 'evaluation.php';
$breadcrumbs = ['Evaluation'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/staff/evaluation/index.js';

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Evaluation</h1>
    <p class="text-sm text-gray-500">Read-only applicant qualification summary with deferred scoring, recommendation, and evidence detail.</p>
</div>

<div id="evaluationFlashState" class="hidden" data-state="<?= htmlspecialchars((string)($state ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-message="<?= htmlspecialchars((string)($message ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Rule-Based Applicant Evaluation</h2>
        <p class="text-sm text-gray-500 mt-1">IF eligibility, education, training, and experience criteria are met, applicant is qualified for evaluation.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <article class="rounded-xl border bg-emerald-50 px-4 py-3">
            <p class="text-xs text-emerald-700 uppercase tracking-wide">Qualified</p>
            <p id="staffEvaluationQualifiedCount" class="text-2xl font-semibold text-emerald-800 mt-1">--</p>
            <p id="staffEvaluationSummaryState" class="mt-2 text-xs text-emerald-700">Loading deferred evaluation summary...</p>
        </article>
        <article class="rounded-xl border bg-rose-50 px-4 py-3">
            <p class="text-xs text-rose-700 uppercase tracking-wide">Not Qualified</p>
            <p id="staffEvaluationNotQualifiedCount" class="text-2xl font-semibold text-rose-800 mt-1">--</p>
        </article>
        <article class="rounded-xl border bg-slate-50 px-4 py-3">
            <p class="text-xs text-slate-700 uppercase tracking-wide">Total Screened</p>
            <p id="staffEvaluationTotalCount" class="text-2xl font-semibold text-slate-800 mt-1">--</p>
        </article>
    </div>

</section>

<section
    id="staffEvaluationAsyncRegion"
    class="mb-6"
    data-evaluation-list-url="evaluation.php?partial=list"
    data-evaluation-detail-url="evaluation.php?partial=detail"
>
    <div id="staffEvaluationListSkeleton" class="space-y-4" aria-live="polite" role="status">
        <section class="bg-white border rounded-xl">
            <header class="px-6 py-4 border-b">
                <div class="h-5 w-48 animate-pulse rounded bg-slate-200"></div>
                <div class="mt-2 h-4 w-80 animate-pulse rounded bg-slate-200"></div>
            </header>
            <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="h-10 animate-pulse rounded bg-slate-100"></div>
                <div class="h-10 animate-pulse rounded bg-slate-100"></div>
                <div class="h-10 animate-pulse rounded bg-slate-100"></div>
            </div>
            <div class="p-6 space-y-3">
                <?php for ($index = 0; $index < 6; $index += 1): ?>
                    <div class="h-14 animate-pulse rounded bg-slate-100"></div>
                <?php endfor; ?>
            </div>
        </section>
    </div>

    <div id="staffEvaluationListError" class="hidden rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" aria-live="polite">
        <p class="font-medium">Candidate scoring queue could not be loaded.</p>
        <button type="button" id="staffEvaluationListRetry" class="mt-3 inline-flex items-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">Retry queue</button>
    </div>

    <div id="staffEvaluationListContent" class="hidden"></div>
</section>

<div id="ruleEvaluationDetailModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-evaluation-modal-close="ruleEvaluationDetailModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-5xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Candidate Score Breakdown</h3>
                    <p class="text-sm text-slate-500 mt-1">Detailed scoring, recommendation notes, and evidence signals load only for the selected candidate.</p>
                </div>
                <button type="button" data-evaluation-modal-close="ruleEvaluationDetailModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase text-slate-500 tracking-wide">Selected Candidate</p>
                    <p id="ruleEvaluationDetailApplicantName" class="text-base font-semibold text-slate-800 mt-1">-</p>
                    <p id="ruleEvaluationDetailMeta" class="text-sm text-slate-600 mt-1">-</p>
                    <p id="ruleEvaluationDetailContact" class="text-xs text-slate-500 mt-2">-</p>
                </div>

                <div class="md:col-span-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-slate-700">
                    Detailed recommendation and evidence views are deferred until this modal is opened. Rule execution and approval actions remain outside this page.
                </div>

                <div>
                    <p class="font-medium text-slate-800">Requirement vs Candidate</p>
                    <div id="ruleEvaluationCriteriaList" class="mt-3 space-y-3 text-slate-700">
                        <p class="text-slate-500">No candidate selected.</p>
                    </div>
                </div>

                <div>
                    <p class="font-medium text-slate-800">Score Breakdown</p>
                    <div id="ruleEvaluationScoreList" class="mt-3 space-y-3 text-slate-700">
                        <p class="text-slate-500">No candidate selected.</p>
                    </div>
                </div>

                <div>
                    <p class="font-medium text-slate-800">Recommendation Detail</p>
                    <div id="ruleEvaluationRecommendation" class="mt-3 rounded-lg border border-slate-200 bg-white p-3 text-slate-700">
                        <p class="text-slate-500">No recommendation detail loaded.</p>
                    </div>
                </div>

                <div>
                    <p class="font-medium text-slate-800">Evidence Signals</p>
                    <div id="ruleEvaluationEvidence" class="mt-3 rounded-lg border border-slate-200 bg-white p-3 text-slate-700">
                        <p class="text-slate-500">No evidence loaded.</p>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <p class="font-medium text-slate-800">Latest Interview</p>
                    <div id="ruleEvaluationInterview" class="mt-3 rounded-lg border border-slate-200 bg-white p-3 text-slate-700">
                        <p class="text-slate-500">No interview detail loaded.</p>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                <button type="button" data-evaluation-modal-close="ruleEvaluationDetailModal" class="px-4 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
