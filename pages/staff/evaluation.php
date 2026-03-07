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
    <p class="text-sm text-gray-500">Read-only applicant qualification results and screening summaries.</p>
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

</section>

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
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($ruleEvaluationRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="6">No applicant records available for rule-based evaluation.</td>
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
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="ruleEvalFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="6">No applicant records match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<script src="../../assets/js/staff/evaluation/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
