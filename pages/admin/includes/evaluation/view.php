<?php
$ruleResultPill = static function (string $result): array {
    $key = strtolower(trim($result));
    if ($key === 'pass') {
        return ['Pass', 'bg-emerald-100 text-emerald-800'];
    }
    if ($key === 'conditional') {
        return ['Conditional', 'bg-amber-100 text-amber-800'];
    }

    return ['Fail', 'bg-rose-100 text-rose-800'];
};

$educationRequirementLabel = static function (string $value): string {
    $key = strtolower(trim($value));
    if ($key === 'any_bachelor') {
        return 'Any Bachelor\'s Degree';
    }
    if ($key === 'masters_preferred') {
        return 'Master\'s Degree Preferred';
    }

    return 'Bachelor\'s Degree (Related Field)';
};
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

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Evaluation (Rule-Based Algorithm)</h1>
        <p class="text-sm text-slate-300 mt-2">Configure criteria, run rule-based evaluation, and generate system recommendations.</p>
        <p class="text-xs text-slate-400 mt-2">Experience is estimated from available application signals (resume/portfolio/supporting documents) until structured experience fields are added.</p>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Configure Evaluation Criteria</h2>
        <p class="text-sm text-slate-500 mt-1">Define scoring thresholds for education, experience, exam score, and interview rating.</p>
    </header>

    <form action="evaluation.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <input type="hidden" name="form_action" value="save_evaluation_criteria">

        <div>
            <label class="text-slate-600">Education Requirement</label>
            <select name="education_requirement" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="related_bachelor" <?= ($criteria['education_requirement'] ?? '') === 'related_bachelor' ? 'selected' : '' ?>>Bachelor's Degree (Related Field)</option>
                <option value="any_bachelor" <?= ($criteria['education_requirement'] ?? '') === 'any_bachelor' ? 'selected' : '' ?>>Any Bachelor's Degree</option>
                <option value="masters_preferred" <?= ($criteria['education_requirement'] ?? '') === 'masters_preferred' ? 'selected' : '' ?>>Master's Degree Preferred</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Minimum Relevant Experience (Years)</label>
            <input name="minimum_experience_years" type="number" min="0" max="30" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)($criteria['minimum_experience_years'] ?? 2), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
            <label class="text-slate-600">Minimum Exam Score (%)</label>
            <input name="minimum_exam_score" type="number" min="0" max="100" step="0.01" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)($criteria['minimum_exam_score'] ?? 75), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
            <label class="text-slate-600">Minimum Interview Rating (1-5)</label>
            <input name="minimum_interview_rating" type="number" min="1" max="5" step="0.01" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)($criteria['minimum_interview_rating'] ?? 3.5), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Rule Notes</label>
            <textarea name="rule_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add conditions for pass/fail, tie-breaking, and manual override scenarios"><?= htmlspecialchars((string)($criteria['rule_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <a href="evaluation.php" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</a>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Criteria</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Run Rule-Based Evaluation</h2>
            <p class="text-sm text-slate-500 mt-1">Apply criteria to applicants and compute qualification outcomes.</p>
            <?php if ($lastRunAt !== ''): ?>
                <p class="text-xs text-slate-400 mt-1">Last run: <?= htmlspecialchars($lastRunAt, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
        <form action="evaluation.php" method="POST">
            <input type="hidden" name="form_action" value="run_rule_evaluation">
            <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Run Evaluation</button>
        </form>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Education</th>
                    <th class="text-left px-4 py-3">Experience</th>
                    <th class="text-left px-4 py-3">Exam</th>
                    <th class="text-left px-4 py-3">Interview</th>
                    <th class="text-left px-4 py-3">Rule Result</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($evaluationRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="6">No applications available for evaluation.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($evaluationRows as $row): ?>
                        <?php [$resultLabel, $resultClass] = $ruleResultPill((string)($row['rule_result'] ?? 'Fail')); ?>
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800"><?= htmlspecialchars((string)($row['applicant_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars((string)($row['job_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars((string)($row['application_ref_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 text-xs rounded-full <?= !empty($row['education_meets']) ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                                    <?= htmlspecialchars((string)($row['education_text'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-slate-800"><?= htmlspecialchars((string)($row['experience_years'] ?? 0), ENT_QUOTES, 'UTF-8') ?> yr(s)</p>
                                <p class="text-xs <?= !empty($row['experience_meets']) ? 'text-emerald-700' : 'text-rose-700' ?>">
                                    <?= !empty($row['experience_meets']) ? 'Meets threshold' : 'Below threshold' ?>
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <?php if (isset($row['exam_score']) && is_numeric($row['exam_score'])): ?>
                                    <p class="text-slate-800"><?= htmlspecialchars(number_format((float)$row['exam_score'], 2), ENT_QUOTES, 'UTF-8') ?>%</p>
                                    <p class="text-xs <?= !empty($row['exam_meets']) ? 'text-emerald-700' : 'text-rose-700' ?>"><?= !empty($row['exam_meets']) ? 'Meets threshold' : 'Below threshold' ?></p>
                                <?php else: ?>
                                    <p class="text-slate-500">No score</p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if (isset($row['interview_rating']) && is_numeric($row['interview_rating'])): ?>
                                    <p class="text-slate-800"><?= htmlspecialchars(number_format((float)$row['interview_rating'], 2), ENT_QUOTES, 'UTF-8') ?>/5</p>
                                    <p class="text-xs <?= !empty($row['interview_meets']) ? 'text-emerald-700' : 'text-rose-700' ?>"><?= !empty($row['interview_meets']) ? 'Meets threshold' : 'Below threshold' ?></p>
                                <?php else: ?>
                                    <p class="text-slate-500">No rating</p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars($resultClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($resultLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Generate System Recommendations</h2>
        <p class="text-sm text-slate-500 mt-1">Produce recommendation output based on the latest rule evaluation run.</p>
        <?php if ($lastRecommendationAt !== ''): ?>
            <p class="text-xs text-slate-400 mt-1">Last generated: <?= htmlspecialchars($lastRecommendationAt, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm mb-2">
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase text-emerald-700">Recommended for Shortlist</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)($recommendationSummary['shortlist'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-600 mt-1">Passed all active rule thresholds</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase text-amber-700">Needs Manual Review</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)($recommendationSummary['manual_review'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-600 mt-1">Borderline based on one criterion</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-rose-50">
            <p class="text-xs uppercase text-rose-700">Not Recommended</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)($recommendationSummary['not_recommended'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-600 mt-1">Did not satisfy required criteria</p>
        </article>
    </div>

    <div class="px-6 pb-6 flex justify-between items-center gap-3">
        <p class="text-xs text-slate-500">Current criteria: <?= htmlspecialchars($educationRequirementLabel((string)($criteria['education_requirement'] ?? 'related_bachelor')), ENT_QUOTES, 'UTF-8') ?>, min <?= htmlspecialchars((string)($criteria['minimum_experience_years'] ?? 2), ENT_QUOTES, 'UTF-8') ?> yr(s), exam <?= htmlspecialchars((string)($criteria['minimum_exam_score'] ?? 75), ENT_QUOTES, 'UTF-8') ?>%, interview <?= htmlspecialchars((string)($criteria['minimum_interview_rating'] ?? 3.5), ENT_QUOTES, 'UTF-8') ?>/5.</p>
        <form action="evaluation.php" method="POST">
            <input type="hidden" name="form_action" value="generate_system_recommendations">
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Generate Recommendations</button>
        </form>
    </div>
</section>
