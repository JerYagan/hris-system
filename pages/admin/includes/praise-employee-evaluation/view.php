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

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Set Evaluation Periods</h2>
            <p class="text-sm text-slate-500 mt-1">Define timeline windows for employee performance evaluation cycles.</p>
        </div>
        <button type="button" data-modal-open="praiseCycleModal" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">New Cycle</button>
    </header>

    <div class="p-6 overflow-x-auto">
        <table id="praiseCyclesTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Cycle</th>
                    <th class="text-left px-4 py-3">Start Date</th>
                    <th class="text-left px-4 py-3">End Date</th>
                    <th class="text-left px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($cycles)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="4">No evaluation cycles found.</td></tr>
                <?php else: ?>
                    <?php foreach ($cycles as $cycle): ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($cycle['cycle_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= !empty($cycle['period_start']) ? htmlspecialchars(date('M d, Y', strtotime((string)$cycle['period_start'])), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                            <td class="px-4 py-3"><?= !empty($cycle['period_end']) ? htmlspecialchars(date('M d, Y', strtotime((string)$cycle['period_end'])), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars(ucfirst((string)($cycle['status'] ?? 'draft')), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Approve Supervisor Ratings</h2>
        <p class="text-sm text-slate-500 mt-1">Validate submitted ratings before finalizing performance results.</p>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Ratings</label>
            <input id="praiseRatingsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by employee, supervisor, cycle, or rating">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="praiseRatingsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="Pending Approval">Pending Approval</option>
                <option value="Returned for Review">Returned for Review</option>
                <option value="Approved">Approved</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="praiseRatingsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Supervisor</th>
                    <th class="text-left px-4 py-3">Cycle</th>
                    <th class="text-left px-4 py-3">Rating</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($ratingsToApproveRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="6">No pending supervisor ratings found.</td></tr>
                <?php else: ?>
                    <?php foreach ($ratingsToApproveRows as $row): ?>
                        <tr data-praise-rating-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>" data-praise-rating-status="<?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['employee'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['supervisor'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['cycle_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['rating_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)$row['status_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><button type="button" data-praise-rating-open data-evaluation-id="<?= htmlspecialchars((string)$row['evaluation_id'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['employee'], ENT_QUOTES, 'UTF-8') ?>" data-cycle-name="<?= htmlspecialchars((string)$row['cycle_name'], ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars((string)$row['status_raw'], ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"><span class="material-symbols-outlined text-[15px]">rate_review</span>Review</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Approved Employees per Quarter</h2>
        <p class="text-sm text-slate-500 mt-1">Quarterly summary of employees with approved evaluation results.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table id="praiseApprovedQuarterTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Quarter</th>
                    <th class="text-left px-4 py-3">Approved Employees</th>
                    <th class="text-left px-4 py-3">Average Rating</th>
                    <th class="text-left px-4 py-3">Latest Cycle</th>
                    <th class="text-left px-4 py-3">Employee Preview</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($approvedPerQuarterRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="5">No approved evaluation records available for quarter summary.</td></tr>
                <?php else: ?>
                    <?php foreach ($approvedPerQuarterRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars((string)$row['quarter_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['approved_count'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['average_rating_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['latest_cycle'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)$row['employee_preview'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">View Overall Performance Ratings</h2>
        <p class="text-sm text-slate-500 mt-1">Check finalized performance outcomes for employee development planning.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm mb-3">
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50"><p class="text-xs uppercase text-emerald-700">Outstanding</p><p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$outstandingCount ?></p></article>
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50"><p class="text-xs uppercase text-slate-600">Very Satisfactory</p><p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$verySatisfactoryCount ?></p></article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50"><p class="text-xs uppercase text-amber-700">Needs Coaching</p><p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$needsCoachingCount ?></p></article>
    </div>

    <div class="p-6 pt-0 overflow-x-auto">
        <table id="praiseOverallRatingsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-3">Employee</th><th class="text-left px-4 py-3">Cycle</th><th class="text-left px-4 py-3">Final Rating</th><th class="text-left px-4 py-3">Band</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($overallRatingRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="4">No approved performance ratings yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($overallRatingRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['employee'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['cycle_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['rating_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['band'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div id="praiseCycleModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="praiseCycleModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between"><h3 class="text-lg font-semibold text-slate-800">Set Evaluation Period</h3><button type="button" data-modal-close="praiseCycleModal" class="text-slate-500 hover:text-slate-700">✕</button></div>
            <form action="praise-employee-evaluation.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="create_evaluation_cycle">
                <div class="md:col-span-2"><label class="text-slate-600">Evaluation Cycle</label><input type="text" name="cycle_name" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></div>
                <div><label class="text-slate-600">Start Date</label><input type="date" name="period_start" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></div>
                <div><label class="text-slate-600">End Date</label><input type="date" name="period_end" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2"><button type="button" data-modal-close="praiseCycleModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button><button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Period</button></div>
            </form>
        </div>
    </div>
</div>

<div id="praiseRatingModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="praiseRatingModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between"><h3 id="praiseRatingModalTitle" class="text-lg font-semibold text-slate-800">Approve Supervisor Rating</h3><button type="button" data-modal-close="praiseRatingModal" class="text-slate-500 hover:text-slate-700">✕</button></div>
            <form id="praiseRatingForm" action="praise-employee-evaluation.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="approve_supervisor_rating">
                <input type="hidden" id="praiseEvaluationId" name="evaluation_id" value="">
                <div><label class="text-slate-600">Employee</label><input id="praiseEvaluationEmployee" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Cycle</label><input id="praiseEvaluationCycle" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div>
                <div><label class="text-slate-600">Decision</label><select id="praiseEvaluationDecision" name="decision" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required><option value="approved">Approve</option><option value="reviewed">Return for Review</option></select></div>
                <div class="md:col-span-2"><p id="praiseRatingDecisionHint" class="text-xs text-slate-500">Approving finalizes this supervisor rating for reporting.</p></div>
                <div class="md:col-span-2"><label class="text-slate-600">Remarks</label><textarea id="praiseEvaluationRemarks" name="remarks" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Optional note for the employee file."></textarea></div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2"><button type="button" data-modal-close="praiseRatingModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button><button id="praiseRatingSubmitBtn" type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Decision</button></div>
            </form>
        </div>
    </div>
</div>
