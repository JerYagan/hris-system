<?php
$ruleResultPill = static function (string $result): array {
    $key = strtolower(trim($result));
    if ($key === 'qualified for evaluation') {
        return ['Qualified for Evaluation', 'bg-emerald-100 text-emerald-800'];
    }

    return ['Not Qualified', 'bg-rose-100 text-rose-800'];
};

$pipelineStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'submitted' => ['Applied', 'bg-blue-100 text-blue-800'],
        'screening' => ['Verified', 'bg-indigo-100 text-indigo-800'],
        'interview' => ['Interview', 'bg-amber-100 text-amber-800'],
        'shortlisted' => ['Evaluation', 'bg-violet-100 text-violet-800'],
        'offer' => ['For Approval', 'bg-cyan-100 text-cyan-800'],
        'hired' => ['Hired', 'bg-emerald-100 text-emerald-800'],
        'rejected', 'withdrawn' => ['Rejected', 'bg-rose-100 text-rose-800'],
        default => ['Applied', 'bg-slate-100 text-slate-700'],
    };
};

$globalEligibilityOption = evaluationRequirementToEligibilityOption((string)($criteria['eligibility'] ?? 'csc_prc'));
$positionCriteriaJson = htmlspecialchars(json_encode($positionCriteriaOverrides, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: '{}', ENT_QUOTES, 'UTF-8');
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

<?php if ($dataLoadError): ?>
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm flex gap-2">
        <span class="material-symbols-outlined text-base">error</span>
        <span><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Generate System Recommendations</h2>
            <p class="text-sm text-slate-500 mt-1">Produce recommendation output from the current rule set and applicant data.</p>
            <?php if ($lastRecommendationAt !== ''): ?>
                <p class="text-xs text-slate-400 mt-1">Last generated: <?= htmlspecialchars($lastRecommendationAt, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
        <form action="evaluation.php" method="POST">
            <input type="hidden" name="form_action" value="generate_system_recommendations">
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Generate Recommendations</button>
        </form>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase text-emerald-700">Qualified for Evaluation</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)($recommendationSummary['qualified'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-600 mt-1">Met configured criteria and threshold</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-rose-50">
            <p class="text-xs uppercase text-rose-700">Not Qualified</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)($recommendationSummary['not_qualified'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-600 mt-1">Did not satisfy active criteria</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase text-slate-600">Total Evaluated</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)($recommendationSummary['total'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-600 mt-1">Current run scope</p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Criteria Configuration</h2>
        <p class="text-sm text-slate-500 mt-1">Choose whether to maintain one global criteria set or define position-specific overrides.</p>
    </header>
    <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        <article class="rounded-xl border border-slate-200 p-4">
            <h3 class="text-sm font-semibold text-slate-800">Configure Global Criteria</h3>
            <p class="text-xs text-slate-500 mt-1">Applies to all postings unless overridden per position.</p>
            <button type="button" data-modal-open="evaluationGlobalCriteriaModal" class="mt-4 inline-flex items-center px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Open Global Criteria</button>
        </article>
        <article class="rounded-xl border border-slate-200 p-4">
            <h3 class="text-sm font-semibold text-slate-800">Configure Per-Position Criteria</h3>
            <p class="text-xs text-slate-500 mt-1">Set eligibility and minimum thresholds for selected job positions.</p>
            <button type="button" data-modal-open="evaluationPositionCriteriaModal" class="mt-4 inline-flex items-center px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Open Per-Position Criteria</button>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Run Rule-Based Evaluation</h2>
            <p class="text-sm text-slate-500 mt-1">Apply current criteria and inspect results per applicant.</p>
            <?php if ($lastRunAt !== ''): ?>
                <p class="text-xs text-slate-400 mt-1">Last run: <?= htmlspecialchars($lastRunAt, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
        <form action="evaluation.php" method="POST">
            <input type="hidden" name="form_action" value="run_rule_evaluation">
            <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Run Evaluation</button>
        </form>
    </header>

    <div class="px-6 pt-5 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
        <div class="md:col-span-2">
            <label for="evaluationSearch" class="text-slate-600">Search</label>
            <input id="evaluationSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Search applicant, reference number, or job title">
        </div>
        <div>
            <label for="evaluationResultFilter" class="text-slate-600">Rule Result</label>
            <select id="evaluationResultFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-white">
                <option value="">All Results</option>
                <option value="qualified for evaluation">Qualified for Evaluation</option>
                <option value="fail">Not Qualified</option>
            </select>
        </div>
    </div>

    <div class="px-6 pt-3 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
        <div class="md:col-span-1">
            <label for="evaluationHiredFilter" class="text-slate-600">Hired Applicants</label>
            <select id="evaluationHiredFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-white">
                <option value="hide" selected>Hide Hired</option>
                <option value="show">Show Hired</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Pipeline Status</th>
                    <th class="text-left px-4 py-3">Eligibility</th>
                    <th class="text-left px-4 py-3">Education</th>
                    <th class="text-left px-4 py-3">Training</th>
                    <th class="text-left px-4 py-3">Experience</th>
                    <th class="text-left px-4 py-3">Score</th>
                    <th class="text-left px-4 py-3">Rule Result</th>
                </tr>
            </thead>
            <tbody id="evaluationResultsBody" class="divide-y divide-slate-100">
                <?php if (empty($evaluationRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="8">No applications available for evaluation.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($evaluationRows as $row): ?>
                        <?php [$resultLabel, $resultClass] = $ruleResultPill((string)($row['rule_result'] ?? 'Fail')); ?>
                        <?php [$pipelineLabel, $pipelineClass] = $pipelineStatusPill((string)($row['application_status'] ?? 'submitted')); ?>
                        <tr
                            data-evaluation-row
                            data-search="<?= htmlspecialchars(strtolower(trim((string)($row['applicant_name'] ?? '') . ' ' . (string)($row['job_title'] ?? '') . ' ' . (string)($row['application_ref_no'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>"
                            data-result="<?= htmlspecialchars(strtolower(trim((string)($row['rule_result'] ?? 'fail'))), ENT_QUOTES, 'UTF-8') ?>"
                            data-status="<?= htmlspecialchars(strtolower(trim((string)($row['application_status'] ?? 'submitted'))), ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800"><?= htmlspecialchars((string)($row['applicant_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars((string)($row['job_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars((string)($row['application_ref_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars($pipelineClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($pipelineLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <?php if (!empty($row['eligibility_required'])): ?>
                                    <p class="text-slate-800"><?= htmlspecialchars((string)($row['eligibility_input'] ?? 'n/a'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs text-slate-500 mt-1">Required: <?= htmlspecialchars((string)($row['required_eligibility_label'] ?? 'CSC/PRC'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs <?= !empty($row['eligibility_meets']) ? 'text-emerald-700' : 'text-rose-700' ?>"><?= !empty($row['eligibility_meets']) ? 'Meets required eligibility' : 'Does not match CSC/PRC requirement' ?></p>
                                <?php else: ?>
                                    <p class="text-slate-800">Not Required</p>
                                    <p class="text-xs text-slate-500">Eligibility excluded for this posting</p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 text-xs rounded-full <?= !empty($row['education_meets']) ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                                    <?= !empty($row['education_meets']) ? 'Meets' : 'Below Minimum' ?>
                                </span>
                                <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)($row['education_level_label'] ?? 'Not Provided'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 text-xs rounded-full <?= !empty($row['training_meets']) ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                                    <?= !empty($row['training_meets']) ? 'Meets' : 'Below Minimum' ?>
                                </span>
                                <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)($row['training_hours'] ?? 0), ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars((string)($row['required_training_hours'] ?? 4), ENT_QUOTES, 'UTF-8') ?> hr(s)</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-slate-800"><?= htmlspecialchars((string)($row['experience_years'] ?? 0), ENT_QUOTES, 'UTF-8') ?> yr(s)</p>
                                <p class="text-xs <?= !empty($row['experience_meets']) ? 'text-emerald-700' : 'text-rose-700' ?>">
                                    <?= !empty($row['experience_meets']) ? 'Meets threshold' : 'Below threshold' ?>
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-slate-800 font-medium"><?= htmlspecialchars((string)($row['total_score'] ?? 0), ENT_QUOTES, 'UTF-8') ?> / 100</p>
                                <p class="text-xs text-slate-500">Threshold: <?= htmlspecialchars((string)($row['threshold'] ?? 75), ENT_QUOTES, 'UTF-8') ?>%</p>
                            </td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars($resultClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($resultLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="evaluationNoMatchesRow" class="hidden">
                    <td class="px-4 py-3 text-slate-500" colspan="8">No matching applications found.</td>
                </tr>
            </tbody>
        </table>

        <div class="mt-4 flex items-center justify-between gap-3 text-sm">
            <p id="evaluationPaginationMeta" class="text-slate-500">Showing 0 to 0 of 0 entries</p>
            <div class="flex items-center gap-2">
                <button type="button" id="evaluationPagePrev" class="px-3 py-1.5 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50 disabled:opacity-50">Previous</button>
                <span id="evaluationPageIndicator" class="text-slate-600">Page 1 of 1</span>
                <button type="button" id="evaluationPageNext" class="px-3 py-1.5 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50 disabled:opacity-50">Next</button>
            </div>
        </div>
    </div>
</section>

<div id="evaluationGlobalCriteriaModal" data-modal class="fixed inset-0 z-50 hidden overflow-y-auto" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="evaluationGlobalCriteriaModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Configure Global Evaluation Criteria</h3>
                    <p class="text-sm text-slate-500 mt-1">Applies system-wide unless overridden by position criteria.</p>
                </div>
                <button type="button" data-modal-close="evaluationGlobalCriteriaModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="evaluation.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="save_evaluation_criteria">

                <div>
                    <label class="text-slate-600">Required Eligibility</label>
                    <select name="required_eligibility" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-white" required>
                        <option value="none" <?= $globalEligibilityOption === 'none' ? 'selected' : '' ?>>None</option>
                        <option value="csc" <?= $globalEligibilityOption === 'csc' ? 'selected' : '' ?>>CSC</option>
                        <option value="prc" <?= $globalEligibilityOption === 'prc' ? 'selected' : '' ?>>PRC</option>
                        <option value="csc_prc" <?= $globalEligibilityOption === 'csc_prc' ? 'selected' : '' ?>>CSC or PRC</option>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Minimum Education Level</label>
                    <select name="minimum_education_level" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-white" required>
                        <?php $globalEducationLevel = evaluationNormalizeEducationLevel((string)($criteria['minimum_education_level'] ?? 'vocational')); ?>
                        <option value="elementary" <?= $globalEducationLevel === 'elementary' ? 'selected' : '' ?>>Elementary</option>
                        <option value="secondary" <?= $globalEducationLevel === 'secondary' ? 'selected' : '' ?>>Secondary</option>
                        <option value="vocational" <?= $globalEducationLevel === 'vocational' ? 'selected' : '' ?>>Vocational/Trade Course</option>
                        <option value="college" <?= $globalEducationLevel === 'college' ? 'selected' : '' ?>>College</option>
                        <option value="graduate" <?= $globalEducationLevel === 'graduate' ? 'selected' : '' ?>>Graduate Studies</option>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Minimum Training (Hours)</label>
                    <input name="minimum_training_hours" type="number" min="0" max="1000" step="0.5" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)($criteria['minimum_training_hours'] ?? 4), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div>
                    <label class="text-slate-600">Minimum Experience (Years)</label>
                    <input name="minimum_experience_years" type="number" min="0" max="60" step="0.5" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)($criteria['minimum_experience_years'] ?? 1), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div>
                    <label class="text-slate-600">Scoring Threshold (%)</label>
                    <input name="threshold" type="number" min="0" max="100" step="1" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)($criteria['threshold'] ?? 75), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Rule Notes</label>
                    <textarea name="rule_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add conditions for pass/fail, tie-breaking, and manual override scenarios"><?= htmlspecialchars((string)($criteria['rule_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="evaluationGlobalCriteriaModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Global Criteria</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="evaluationPositionCriteriaModal" data-modal class="fixed inset-0 z-50 hidden overflow-y-auto" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="evaluationPositionCriteriaModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Configure Per-Position Criteria</h3>
                    <p class="text-sm text-slate-500 mt-1">Saved criteria are automatically used by rule evaluation for postings under the selected position.</p>
                </div>
                <button type="button" data-modal-close="evaluationPositionCriteriaModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="evaluation.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm" id="evaluationPositionCriteriaForm">
                <input type="hidden" name="form_action" value="save_position_criteria">

                <div class="md:col-span-2">
                    <label for="evaluationPositionSelect" class="text-slate-600">Position</label>
                    <select id="evaluationPositionSelect" name="position_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-white" required>
                        <option value="">Select a position</option>
                        <?php foreach ($positionOptions as $position): ?>
                            <?php
                            $positionId = (string)($position['id'] ?? '');
                            $positionTitle = (string)($position['position_title'] ?? 'Untitled Position');
                            ?>
                            <option value="<?= htmlspecialchars($positionId, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($positionTitle, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="text-slate-600">Required Eligibility</label>
                    <select id="evaluationPositionEligibility" name="position_required_eligibility" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-white" required>
                        <option value="none">None</option>
                        <option value="csc">CSC</option>
                        <option value="prc">PRC</option>
                        <option value="csc_prc" selected>CSC or PRC</option>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Minimum Education Level</label>
                    <?php $positionDefaultEducationLevel = evaluationNormalizeEducationLevel((string)($criteria['minimum_education_level'] ?? 'vocational')); ?>
                    <select id="evaluationPositionEducation" name="position_minimum_education_level" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-white" required>
                        <option value="elementary" <?= $positionDefaultEducationLevel === 'elementary' ? 'selected' : '' ?>>Elementary</option>
                        <option value="secondary" <?= $positionDefaultEducationLevel === 'secondary' ? 'selected' : '' ?>>Secondary</option>
                        <option value="vocational" <?= $positionDefaultEducationLevel === 'vocational' ? 'selected' : '' ?>>Vocational/Trade Course</option>
                        <option value="college" <?= $positionDefaultEducationLevel === 'college' ? 'selected' : '' ?>>College</option>
                        <option value="graduate" <?= $positionDefaultEducationLevel === 'graduate' ? 'selected' : '' ?>>Graduate Studies</option>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Minimum Training (Hours)</label>
                    <input id="evaluationPositionTraining" name="position_minimum_training_hours" type="number" min="0" max="1000" step="0.5" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)($criteria['minimum_training_hours'] ?? 4), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div>
                    <label class="text-slate-600">Minimum Experience (Years)</label>
                    <input id="evaluationPositionExperience" name="position_minimum_experience_years" type="number" min="0" max="60" step="0.5" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)($criteria['minimum_experience_years'] ?? 1), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="evaluationPositionCriteriaModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Position Criteria</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script id="evaluationPositionCriteriaData" type="application/json"><?= $positionCriteriaJson ?></script>
