<?php
require_once __DIR__ . '/includes/employee-evaluation/bootstrap.php';
require_once __DIR__ . '/includes/employee-evaluation/actions.php';
require_once __DIR__ . '/includes/employee-evaluation/data.php';

$pageTitle = 'Employee Evaluation | Staff';
$activePage = 'employee-evaluation.php';
$breadcrumbs = ['PRAISE', 'Employee Evaluation'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
?>

<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Employee Evaluation</h1>
        <p class="text-sm text-gray-500">Evaluate employee performance, provide feedback, and forward records for admin approval.</p>
    </div>
    <button type="button" id="openEmployeeEvaluationModal" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md border border-green-200 bg-green-50 text-green-700 text-sm hover:bg-green-100">
        <span class="material-symbols-outlined text-base">add_task</span>
        New Evaluation
    </button>
</div>

<?php if ($state && $message): ?>
    <div id="employeeEvaluationFlash" data-state="<?= htmlspecialchars((string)$state, ENT_QUOTES, 'UTF-8') ?>" data-message="<?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>" class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
        <?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($dataLoadError): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total</p>
        <p class="text-2xl font-semibold text-slate-700 mt-1"><?= (int)($evaluationMetrics['total'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Submitted</p>
        <p class="text-2xl font-semibold text-amber-700 mt-1"><?= (int)($evaluationMetrics['submitted'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Reviewed</p>
        <p class="text-2xl font-semibold text-blue-700 mt-1"><?= (int)($evaluationMetrics['reviewed'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Approved</p>
        <p class="text-2xl font-semibold text-emerald-700 mt-1"><?= (int)($evaluationMetrics['approved'] ?? 0) ?></p>
    </article>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Submitted Employee Evaluations</h2>
        <p class="text-sm text-gray-500 mt-1">Track evaluation records and their admin approval progress.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
            <label for="employeeEvaluationSearchInput" class="text-sm text-gray-600">Search</label>
            <input id="employeeEvaluationSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by employee, cycle, status, or remarks">
        </div>
        <div>
            <label for="employeeEvaluationStatusFilter" class="text-sm text-gray-600">Status</label>
            <select id="employeeEvaluationStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="submitted">Submitted</option>
                <option value="reviewed">Reviewed</option>
                <option value="approved">Approved</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm" id="employeeEvaluationTable">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Cycle</th>
                    <th class="text-left px-4 py-3">Rating</th>
                    <th class="text-left px-4 py-3">Feedback</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Updated</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($evaluationRows)): ?>
                    <tr><td class="px-4 py-3 text-gray-500" colspan="6">No employee evaluations submitted yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($evaluationRows as $row): ?>
                        <tr data-employee-evaluation-row data-employee-evaluation-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employee-evaluation-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars((string)($row['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['cycle_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['rating_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['remarks'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Draft'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['updated_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="employeeEvaluationFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="6">No records match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="px-6 pb-6 flex items-center justify-between gap-3">
        <button type="button" id="employeeEvaluationPrevPage" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs rounded-md border hover:bg-gray-50 disabled:opacity-50" disabled>Previous</button>
        <p id="employeeEvaluationPageLabel" class="text-xs text-gray-500">Page 1 of 1</p>
        <button type="button" id="employeeEvaluationNextPage" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs rounded-md border hover:bg-gray-50 disabled:opacity-50" disabled>Next</button>
    </div>
</section>

<div id="employeeEvaluationModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-2xl rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Employee Performance Evaluation</h3>
            <button type="button" id="employeeEvaluationModalClose" class="text-gray-500 hover:text-gray-700"><span class="material-symbols-outlined">close</span></button>
        </div>

        <form id="employeeEvaluationForm" method="POST" action="employee-evaluation.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="submit_employee_evaluation">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div>
                <label for="employeeEvaluationEmployeeSelect" class="text-gray-600">Employee</label>
                <select id="employeeEvaluationEmployeeSelect" name="employee_person_id" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select employee</option>
                    <?php foreach ($employeeOptions as $option): ?>
                        <option value="<?= htmlspecialchars((string)($option['person_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($option['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="employeeEvaluationCycleSelect" class="text-gray-600">Evaluation Cycle</label>
                <select id="employeeEvaluationCycleSelect" name="cycle_id" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select cycle</option>
                    <?php foreach ($cycleOptions as $option): ?>
                        <option value="<?= htmlspecialchars((string)($option['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($option['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="employeeEvaluationRatingInput" class="text-gray-600">Final Rating (1.00 - 5.00)</label>
                <input id="employeeEvaluationRatingInput" type="number" name="final_rating" min="1" max="5" step="0.01" class="w-full mt-1 border rounded-md px-3 py-2" required>
            </div>

            <div>
                <label for="employeeEvaluationFeedbackInput" class="text-gray-600">Performance Feedback</label>
                <textarea id="employeeEvaluationFeedbackInput" name="feedback" rows="4" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Provide performance highlights, strengths, and areas for development." required></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" id="employeeEvaluationModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="employeeEvaluationSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Forward for Admin Approval</button>
            </div>
        </form>
    </div>
</div>

<script src="../../assets/js/staff/employee-evaluation/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
