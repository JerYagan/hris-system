<?php
require_once __DIR__ . '/includes/praise/bootstrap.php';
require_once __DIR__ . '/includes/praise/actions.php';
require_once __DIR__ . '/includes/praise/data.php';

$pageTitle = 'PRAISE | Staff';
$activePage = 'praise.php';
$breadcrumbs = ['PRAISE'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">PRAISE</h1>
    <p class="text-sm text-gray-500">Review award categories and process nomination decisions with scope-safe controls.</p>
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
        <p class="text-xs text-gray-500 uppercase tracking-wide">Active Awards</p>
        <p class="text-2xl font-semibold text-blue-700 mt-1"><?= (int)($praiseMetrics['active_awards'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Pending Nominations</p>
        <p class="text-2xl font-semibold text-amber-700 mt-1"><?= (int)($praiseMetrics['pending_nominations'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Approved Nominations</p>
        <p class="text-2xl font-semibold text-emerald-700 mt-1"><?= (int)($praiseMetrics['approved_nominations'] ?? 0) ?></p>
    </article>
    <article class="rounded-xl border bg-white px-4 py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Rejected Nominations</p>
        <p class="text-2xl font-semibold text-rose-700 mt-1"><?= (int)($praiseMetrics['rejected_nominations'] ?? 0) ?></p>
    </article>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Award Categories</h2>
        <p class="text-sm text-gray-500 mt-1">Reference list of available PRAISE awards.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table id="praiseAwardsTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Award</th>
                    <th class="text-left px-4 py-3">Code</th>
                    <th class="text-left px-4 py-3">Criteria</th>
                    <th class="text-left px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($praiseAwardRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="4">No award categories found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($praiseAwardRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['award_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['description'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['award_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['criteria'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Inactive'), ENT_QUOTES, 'UTF-8') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Nomination Queue</h2>
        <p class="text-sm text-gray-500 mt-1">Search nomination records and apply decision workflow with confirmation prompts.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="praiseNominationSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="praiseNominationSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by nominee, award, cycle, nominator, or status">
        </div>
        <div>
            <label for="praiseNominationStatusFilter" class="text-sm text-gray-600">All Statuses</label>
            <select id="praiseNominationStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="praiseNominationsTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Nominee</th>
                    <th class="text-left px-4 py-3">Award</th>
                    <th class="text-left px-4 py-3">Cycle</th>
                    <th class="text-left px-4 py-3">Nominated By</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Submitted</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($praiseNominationRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="7">No nominations found in your scope.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($praiseNominationRows as $row): ?>
                        <tr data-praise-nomination-row data-praise-nomination-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-praise-nomination-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['nominee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['justification'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['award_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['cycle_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['nominated_by'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['submitted_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-open-praise-nomination-modal
                                    data-nomination-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-nominee-name="<?= htmlspecialchars((string)($row['nominee_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-award-name="<?= htmlspecialchars((string)($row['award_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
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
                <tr id="praiseNominationFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="7">No nominations match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<div id="praiseNominationReviewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Review Nomination</h3>
            <button type="button" id="praiseNominationModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal"><span class="material-symbols-outlined">close</span></button>
        </div>

        <form id="praiseNominationReviewForm" method="POST" action="praise.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="review_praise_nomination">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="nomination_id" id="praiseNominationIdInput" value="">

            <div>
                <label class="text-gray-600">Nominee</label>
                <p id="praiseNomineeLabel" class="mt-1 text-sm font-medium text-gray-800">-</p>
            </div>
            <div>
                <label class="text-gray-600">Award</label>
                <p id="praiseAwardLabel" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label class="text-gray-600">Current Status</label>
                <p id="praiseNominationCurrentStatusLabel" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label for="praiseNominationDecisionSelect" class="text-gray-600">Decision</label>
                <select id="praiseNominationDecisionSelect" name="decision" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select decision</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <label for="praiseNominationRemarksInput" class="text-gray-600">Remarks</label>
                <textarea id="praiseNominationRemarksInput" name="remarks" rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Add nomination review notes."></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" id="praiseNominationModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="praiseNominationSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Decision</button>
            </div>
        </form>
    </div>
</div>

<script src="../../assets/js/staff/praise/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
