<?php if (($evaluationContentSection ?? 'list') === 'list'): ?>
<section class="bg-white border rounded-xl">
    <div
        hidden
        data-evaluation-summary
        data-qualified="<?= (int)($ruleEvaluationSummary['qualified'] ?? 0) ?>"
        data-not-qualified="<?= (int)($ruleEvaluationSummary['not_qualified'] ?? 0) ?>"
        data-total="<?= (int)($ruleEvaluationSummary['total'] ?? 0) ?>"
    ></div>
    <header class="px-6 py-4 border-b flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Candidate Scoring Queue</h2>
            <p class="text-sm text-gray-500 mt-1">Summary loads first. Score breakdown, recommendation detail, and evidence signals stay deferred until you open a candidate.</p>
        </div>
        <button type="button" data-evaluation-refresh="list" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
            <span class="material-symbols-outlined text-base">refresh</span>
            Refresh Queue
        </button>
    </header>

    <?php if (!empty($dataLoadError)): ?>
        <div class="mx-6 mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form id="ruleEvaluationFilters" class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3" autocomplete="off">
        <input id="ruleEvaluationPageInput" type="hidden" name="rule_page" value="<?= (int)($ruleEvaluationPagination['page'] ?? 1) ?>">
        <div>
            <label for="ruleEvalPositionFilter" class="text-sm text-gray-600">Position Title</label>
            <select id="ruleEvalPositionFilter" name="position_title" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Positions</option>
                <?php foreach ((array)$ruleEvaluationPositionTitles as $title): ?>
                    <?php $selected = strcasecmp((string)$title, (string)($ruleEvaluationFilters['position'] ?? '')) === 0; ?>
                    <option value="<?= htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') ?>" <?= $selected ? 'selected' : '' ?>><?= htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="ruleEvalStatusFilter" class="text-sm text-gray-600">Qualification Status</label>
            <?php $ruleStatus = strtolower((string)($ruleEvaluationFilters['status'] ?? '')); ?>
            <select id="ruleEvalStatusFilter" name="rule_status" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="" <?= $ruleStatus === '' ? 'selected' : '' ?>>All Results</option>
                <option value="qualified" <?= $ruleStatus === 'qualified' ? 'selected' : '' ?>>Qualified</option>
                <option value="not_qualified" <?= $ruleStatus === 'not_qualified' ? 'selected' : '' ?>>Not Qualified</option>
            </select>
        </div>
        <div>
            <label for="ruleEvalSearchInput" class="text-sm text-gray-600">Search</label>
            <input id="ruleEvalSearchInput" name="rule_search" type="search" value="<?= htmlspecialchars((string)($ruleEvaluationFilters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by applicant, email, or position">
        </div>
    </form>

    <div class="px-6 pb-2">
        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
            Staff evaluation is read-only. Rule execution and approval workflows remain outside this initial evaluation shell.
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Application Status</th>
                    <th class="text-left px-4 py-3">Submitted</th>
                    <th class="text-left px-4 py-3">Result</th>
                    <th class="text-left px-4 py-3">Score</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($ruleEvaluationRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="7">No applicant records match the current evaluation scope and filters.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ruleEvaluationRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['applicant_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['applicant_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars((string)($row['application_ref_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['position_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-slate-100 text-slate-700"><?= htmlspecialchars((string)($row['application_status_label'] ?? 'Submitted'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['submitted_at_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Not Qualified'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3 text-slate-700 font-medium"><?= (int)($row['total_score'] ?? 0) ?>% <span class="text-xs font-normal text-slate-500">of <?= (int)($row['threshold'] ?? 75) ?>%</span></td>
                            <td class="px-4 py-3">
                                <button type="button" data-open-rule-evaluation-detail data-application-id="<?= htmlspecialchars((string)($row['application_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                    <span class="material-symbols-outlined text-base">insights</span>
                                    View Breakdown
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="px-6 pb-6 flex items-center justify-between gap-3 text-sm text-slate-600">
        <p><?= htmlspecialchars((string)($ruleEvaluationPagination['label'] ?? 'Page 1'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="flex items-center gap-2">
            <button type="button" data-rule-evaluation-page="<?= (int)($ruleEvaluationPagination['prev_page'] ?? 1) ?>" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" <?= !empty($ruleEvaluationPagination['has_prev']) ? '' : 'disabled' ?>>Previous</button>
            <button type="button" data-rule-evaluation-page="<?= (int)($ruleEvaluationPagination['next_page'] ?? 1) ?>" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" <?= !empty($ruleEvaluationPagination['has_next']) ? '' : 'disabled' ?>>Next</button>
        </div>
    </div>
</section>
<?php endif; ?>
