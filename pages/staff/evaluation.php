<?php
require_once __DIR__ . '/includes/evaluation/bootstrap.php';
require_once __DIR__ . '/includes/evaluation/actions.php';
require_once __DIR__ . '/includes/evaluation/data.php';

$pageTitle = 'Evaluation | Staff';
$activePage = 'evaluation.php';
$breadcrumbs = ['Evaluation'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Evaluation</h1>
    <p class="text-sm text-gray-500">Rule-based applicant qualification screening with dynamic per-position criteria.</p>
</div>

<?php if ($state && $message): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($dataLoadError): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Rule-Based Applicant Evaluation</h2>
        <p class="text-sm text-gray-500 mt-1">IF eligibility, education, training, and experience criteria are met, applicant is qualified for evaluation.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <article class="rounded-xl border bg-emerald-50 px-4 py-3">
            <p class="text-xs text-emerald-700 uppercase tracking-wide">Qualified</p>
            <p class="text-2xl font-semibold text-emerald-800 mt-1"><?= (int)($ruleEvaluationSummary['qualified'] ?? 0) ?></p>
        </article>
        <article class="rounded-xl border bg-rose-50 px-4 py-3">
            <p class="text-xs text-rose-700 uppercase tracking-wide">Not Qualified</p>
            <p class="text-2xl font-semibold text-rose-800 mt-1"><?= (int)($ruleEvaluationSummary['not_qualified'] ?? 0) ?></p>
        </article>
        <article class="rounded-xl border bg-slate-50 px-4 py-3">
            <p class="text-xs text-slate-700 uppercase tracking-wide">Total Screened</p>
            <p class="text-2xl font-semibold text-slate-800 mt-1"><?= (int)($ruleEvaluationSummary['total'] ?? 0) ?></p>
        </article>
    </div>

    <div class="px-6 pb-6">
        <form method="POST" action="evaluation.php" class="inline-block">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action" value="run_rule_based_evaluation">
            <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Run Rule-Based Evaluation</button>
        </form>
    </div>
</section>

<?php if (strtolower((string)($staffRoleKey ?? '')) === 'admin'): ?>
    <section class="bg-white border rounded-xl mb-6">
        <header class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">Manage Criteria by Position Title</h2>
            <p class="text-sm text-gray-500 mt-1">Admin can update dynamic screening requirements for each position title.</p>
        </header>

        <form method="POST" action="evaluation.php" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action" value="save_rule_based_criteria">

            <div>
                <label for="rulePositionTitle" class="text-sm text-gray-600">Position Title</label>
                <input list="rulePositionTitleList" id="rulePositionTitle" name="position_title" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" value="<?= htmlspecialchars((string)$ruleSelectedPositionTitle, ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. HR Officer II" required>
                <datalist id="rulePositionTitleList">
                    <?php foreach ((array)$rulePositionTitles as $title): ?>
                        <option value="<?= htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div>
                <label for="requiredEligibility" class="text-sm text-gray-600">Required Eligibility</label>
                <input id="requiredEligibility" name="required_eligibility" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" value="<?= htmlspecialchars((string)($ruleSelectedCriteria['eligibility'] ?? 'career service sub professional'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Career Service Sub Professional" required>
            </div>

            <div>
                <label for="requiredEducationYears" class="text-sm text-gray-600">Required Education (Years)</label>
                <input id="requiredEducationYears" type="number" step="0.5" min="0" max="20" name="required_education_years" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" value="<?= htmlspecialchars((string)($ruleSelectedCriteria['minimum_education_years'] ?? 2), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div>
                <label for="requiredTrainingHours" class="text-sm text-gray-600">Required Training (Hours)</label>
                <input id="requiredTrainingHours" type="number" step="0.5" min="0" max="1000" name="required_training_hours" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" value="<?= htmlspecialchars((string)($ruleSelectedCriteria['minimum_training_hours'] ?? 4), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div>
                <label for="requiredExperienceYears" class="text-sm text-gray-600">Required Experience (Years)</label>
                <input id="requiredExperienceYears" type="number" step="0.5" min="0" max="60" name="required_experience_years" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" value="<?= htmlspecialchars((string)($ruleSelectedCriteria['minimum_experience_years'] ?? 1), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div>
                <label for="requiredThreshold" class="text-sm text-gray-600">Qualification Threshold</label>
                <input id="requiredThreshold" type="number" step="1" min="0" max="100" name="threshold" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" value="<?= htmlspecialchars((string)($ruleSelectedCriteria['threshold'] ?? 75), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800 text-sm">Save Criteria</button>
            </div>
        </form>
    </section>
<?php endif; ?>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Applicant Qualification Results</h2>
        <p class="text-sm text-gray-500 mt-1">Auto-result is based on encoded values; uploaded files are supporting verification documents.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
            <label for="ruleEvalPositionFilter" class="text-sm text-gray-600">Position Title</label>
            <select id="ruleEvalPositionFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Positions</option>
                <?php foreach ((array)$rulePositionTitles as $title): ?>
                    <option value="<?= htmlspecialchars(strtolower((string)$title), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="ruleEvalStatusFilter" class="text-sm text-gray-600">Qualification Status</label>
            <select id="ruleEvalStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Results</option>
                <option value="qualified">Qualified</option>
                <option value="not_qualified">Not Qualified</option>
            </select>
        </div>
        <div>
            <label for="ruleEvalSearchInput" class="text-sm text-gray-600">Search</label>
            <input id="ruleEvalSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by applicant, email, or position">
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm" id="ruleEvaluationTable">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Application Status</th>
                    <th class="text-left px-4 py-3">Input vs Required</th>
                    <th class="text-left px-4 py-3">Score Breakdown</th>
                    <th class="text-left px-4 py-3">Result</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($ruleEvaluationRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="7">No applicant records available for rule-based evaluation.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ruleEvaluationRows as $row): ?>
                        <tr
                            data-rule-eval-row
                            data-rule-eval-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-rule-eval-position="<?= htmlspecialchars(strtolower((string)($row['position_title'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                            data-rule-eval-status="<?= !empty($row['qualified']) ? 'qualified' : 'not_qualified' ?>"
                        >
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['applicant_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['applicant_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars((string)($row['application_ref_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['position_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-xs text-gray-700">
                                <p>Eligibility: <?= htmlspecialchars((string)($row['eligibility_input'] ?? 'n/a'), ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars((string)($row['required_eligibility'] ?? 'n/a'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p>Education: <?= htmlspecialchars((string)($row['education_years_input'] ?? 0), ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars((string)($row['required_education_years'] ?? 2), ENT_QUOTES, 'UTF-8') ?> yrs</p>
                                <p>Training: <?= htmlspecialchars((string)($row['training_hours_input'] ?? 0), ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars((string)($row['required_training_hours'] ?? 4), ENT_QUOTES, 'UTF-8') ?> hrs</p>
                                <p>Experience: <?= htmlspecialchars((string)($row['experience_years_input'] ?? 0), ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars((string)($row['required_experience_years'] ?? 1), ENT_QUOTES, 'UTF-8') ?> yrs</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-700">
                                <p>Eligibility: <?= (int)($row['eligibility_score'] ?? 0) ?>%</p>
                                <p>Education: <?= (int)($row['education_score'] ?? 0) ?>%</p>
                                <p>Training: <?= (int)($row['training_score'] ?? 0) ?>%</p>
                                <p>Experience: <?= (int)($row['experience_score'] ?? 0) ?>%</p>
                                <p class="font-semibold mt-1">Total: <?= (int)($row['total_score'] ?? 0) ?>% (Threshold: <?= (int)($row['threshold'] ?? 75) ?>%)</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full bg-slate-100 text-slate-700"><?= htmlspecialchars((string)($row['application_status_label'] ?? 'Submitted'), ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Not Qualified'), ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <?php if (!empty($row['can_submit_final_evaluation'])): ?>
                                    <button
                                        type="button"
                                        data-open-applicant-final-eval
                                        data-application-id="<?= htmlspecialchars((string)($row['application_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-applicant-name="<?= htmlspecialchars((string)($row['applicant_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-position-title="<?= htmlspecialchars((string)($row['position_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status-label="<?= htmlspecialchars((string)($row['application_status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-interview-result="<?= htmlspecialchars((string)($row['latest_interview_result'] ?? 'pending'), ENT_QUOTES, 'UTF-8') ?>"
                                        data-interview-score="<?= htmlspecialchars((string)($row['latest_interview_score'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-feedback-decision="<?= htmlspecialchars((string)($row['latest_feedback_decision'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-feedback-text="<?= htmlspecialchars((string)($row['latest_feedback_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-indigo-300 bg-indigo-50 text-indigo-700 hover:bg-indigo-100"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">fact_check</span>Evaluate
                                    </button>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-1 text-[11px] rounded-full bg-slate-100 text-slate-600">Interview/Offer only</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="ruleEvalFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="7">No applicant records match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<div id="applicantFinalEvalModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4">
    <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h3 class="text-lg font-semibold text-slate-800">Final Applicant Evaluation</h3>
            <button type="button" id="applicantFinalEvalClose" class="text-slate-500 hover:text-slate-700">✕</button>
        </div>

        <form id="applicantFinalEvalForm" method="POST" action="evaluation.php" class="grid grid-cols-1 gap-4 p-6 text-sm md:grid-cols-2">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="form_action" value="submit_applicant_final_evaluation">
            <input type="hidden" name="application_id" id="appFinalEvalApplicationId" value="">

            <div class="md:col-span-2">
                <label class="text-slate-600">Applicant</label>
                <input type="text" id="appFinalEvalApplicantName" class="mt-1 w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2" readonly>
            </div>

            <div>
                <label class="text-slate-600">Position</label>
                <input type="text" id="appFinalEvalPositionTitle" class="mt-1 w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2" readonly>
            </div>

            <div>
                <label class="text-slate-600">Current Status</label>
                <input type="text" id="appFinalEvalCurrentStatus" class="mt-1 w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2" readonly>
            </div>

            <div>
                <label for="appFinalEvalInterviewResult" class="text-slate-600">Interview Result</label>
                <select id="appFinalEvalInterviewResult" name="interview_result" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
                    <option value="pending">Pending</option>
                    <option value="pass">Pass</option>
                    <option value="fail">Fail</option>
                </select>
            </div>

            <div>
                <label for="appFinalEvalInterviewScore" class="text-slate-600">Interview Score</label>
                <input id="appFinalEvalInterviewScore" type="number" name="interview_score" min="0" max="100" step="0.01" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" placeholder="Optional">
            </div>

            <div class="md:col-span-2">
                <label for="appFinalEvalRecommendation" class="text-slate-600">Final Recommendation</label>
                <select id="appFinalEvalRecommendation" name="final_recommendation" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
                    <option value="recommend_for_approval">Recommend for Admin Approval</option>
                    <option value="not_recommended">Not Recommended (Needs Admin Review)</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label for="appFinalEvalRemarks" class="text-slate-600">HR Remarks</label>
                <textarea id="appFinalEvalRemarks" name="hr_remarks" rows="4" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" placeholder="Enter HR remarks based on interview results and final evaluation." required></textarea>
            </div>

            <div class="md:col-span-2 flex justify-end gap-3">
                <button type="button" id="applicantFinalEvalCancel" class="rounded-md border border-slate-300 px-4 py-2 text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" id="appFinalEvalSubmit" class="inline-flex items-center gap-1.5 rounded-md bg-indigo-700 px-5 py-2 text-white hover:bg-indigo-800">
                    <span class="material-symbols-outlined text-[16px]">send</span>Submit for Admin Approval
                </button>
            </div>
        </form>
    </div>
</div>

<script src="../../assets/js/staff/evaluation/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
