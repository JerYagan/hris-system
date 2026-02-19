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
    <p class="text-sm text-gray-500">Review performance cycles and process evaluation decisions within your office scope.</p>
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

<section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Open Cycles</p>
        <p class="text-2xl font-semibold text-blue-700 mt-1"><?= (int)($evaluationMetrics['open_cycles'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Pending Reviews</p>
        <p class="text-2xl font-semibold text-amber-700 mt-1"><?= (int)($evaluationMetrics['pending_reviews'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Reviewed Records</p>
        <p class="text-2xl font-semibold text-violet-700 mt-1"><?= (int)($evaluationMetrics['reviewed_records'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Approved Records</p>
        <p class="text-2xl font-semibold text-emerald-700 mt-1"><?= (int)($evaluationMetrics['approved_records'] ?? 0) ?></p>
    </article>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Performance Cycles</h2>
        <p class="text-sm text-gray-500 mt-1">Current cycle windows and lifecycle status.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table id="evaluationCyclesTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Cycle</th>
                    <th class="text-left px-4 py-3">Period</th>
                    <th class="text-left px-4 py-3">Evaluations</th>
                    <th class="text-left px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($evaluationCycleRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="4">No performance cycles found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($evaluationCycleRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars((string)($row['cycle_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['period_range'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= (int)($row['evaluation_count'] ?? 0) ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Draft'), ENT_QUOTES, 'UTF-8') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Performance Evaluations</h2>
        <p class="text-sm text-gray-500 mt-1">Search records and apply decision transitions using review-safe workflow.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="evaluationSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="evaluationSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by employee, cycle, evaluator, or status">
        </div>
        <div>
            <label for="evaluationStatusFilter" class="text-sm text-gray-600">All Statuses</label>
            <select id="evaluationStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="submitted">Submitted</option>
                <option value="reviewed">Reviewed</option>
                <option value="approved">Approved</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="evaluationDecisionTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Cycle</th>
                    <th class="text-left px-4 py-3">Evaluator</th>
                    <th class="text-left px-4 py-3">Rating</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Updated</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($evaluationDecisionRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="7">No performance evaluations found in your scope.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($evaluationDecisionRows as $row): ?>
                        <tr data-evaluation-row data-evaluation-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-evaluation-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['remarks'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['cycle_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['evaluator_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['rating_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Draft'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['updated_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-open-evaluation-modal
                                    data-evaluation-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-employee-name="<?= htmlspecialchars((string)($row['employee_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-cycle-name="<?= htmlspecialchars((string)($row['cycle_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status-label="<?= htmlspecialchars((string)($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    class="px-3 py-1.5 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100"
                                >
                                    Review
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="evaluationFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="7">No evaluation records match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<div id="evaluationReviewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Review Performance Evaluation</h3>
            <button type="button" id="evaluationModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal"><span class="material-symbols-outlined">close</span></button>
        </div>

        <form id="evaluationReviewForm" method="POST" action="evaluation.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="review_performance_evaluation">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="evaluation_id" id="evaluationIdInput" value="">

            <div>
                <label class="text-gray-600">Employee</label>
                <p id="evaluationEmployeeLabel" class="mt-1 text-sm font-medium text-gray-800">-</p>
            </div>
            <div>
                <label class="text-gray-600">Cycle</label>
                <p id="evaluationCycleLabel" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label class="text-gray-600">Current Status</label>
                <p id="evaluationCurrentStatusLabel" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label for="evaluationDecisionSelect" class="text-gray-600">Decision</label>
                <select id="evaluationDecisionSelect" name="decision" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select decision</option>
                    <option value="submitted">Submitted</option>
                    <option value="reviewed">Reviewed</option>
                    <option value="approved">Approved</option>
                </select>
            </div>
            <div>
                <label for="evaluationRemarksInput" class="text-gray-600">Remarks</label>
                <textarea id="evaluationRemarksInput" name="remarks" rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Add evaluation review notes."></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" id="evaluationModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="evaluationSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Decision</button>
            </div>
        </form>
    </div>
</div>

<script src="../../assets/js/staff/evaluation/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
