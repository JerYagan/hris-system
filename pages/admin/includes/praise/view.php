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

<?php
$ratingsDecisionRows = array_values(array_filter($ratingsToApproveRows, static function (array $row): bool {
    return strtolower((string)($row['status_raw'] ?? '')) !== 'approved';
}));

$ratingsDecisionPreview = array_slice($ratingsDecisionRows, 0, 5);
$nominationsPreview = array_slice($nominationApprovalRows, 0, 5);
$publishPreview = array_slice($publishAwardeeRows, 0, 5);
?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">PRAISE Command Center</h2>
            <p class="text-sm text-slate-500 mt-1">Quick decisions and queues only. Use module pages for full records and reports.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="praise-employee-evaluation.php" class="px-3 py-2 rounded-md border border-slate-300 text-sm text-slate-700 hover:bg-slate-50">Evaluation Module</a>
            <a href="praise-awards-recognition.php" class="px-3 py-2 rounded-md border border-slate-300 text-sm text-slate-700 hover:bg-slate-50">Awards Module</a>
            <a href="praise-reports-analytics.php" class="px-3 py-2 rounded-md border border-slate-300 text-sm text-slate-700 hover:bg-slate-50">Reports Module</a>
        </div>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase text-amber-700">Ratings Awaiting Decision</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)count($ratingsDecisionRows) ?></p>
            <button type="button" data-modal-open="praiseCycleModal" class="mt-3 px-3 py-1.5 rounded-md border border-slate-300 bg-white text-xs text-slate-700 hover:bg-slate-50">New Cycle</button>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-blue-50">
            <p class="text-xs uppercase text-blue-700">Pending Nominations</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)count($nominationApprovalRows) ?></p>
            <button type="button" id="praiseAwardNewCategoryBtn" data-modal-open="praiseAwardCategoryModal" class="mt-3 px-3 py-1.5 rounded-md border border-slate-300 bg-white text-xs text-slate-700 hover:bg-slate-50">New Category</button>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase text-emerald-700">Ready to Publish</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)count($publishAwardeeRows) ?></p>
            <p class="text-xs text-slate-600 mt-1">Approved awardees awaiting announcement.</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase text-slate-600">Performance Snapshot</p>
            <div class="mt-2 space-y-1 text-sm text-slate-700">
                <p><strong><?= (int)$outstandingCount ?></strong> Outstanding</p>
                <p><strong><?= (int)$verySatisfactoryCount ?></strong> Very Satisfactory</p>
                <p><strong><?= (int)$needsCoachingCount ?></strong> Needs Coaching</p>
            </div>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Needs Action</h2>
            <p class="text-sm text-slate-500 mt-1">Top pending queues only (first 5 items each).</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="praise-employee-evaluation.php" class="text-sm text-slate-700 underline-offset-2 hover:underline">View All Ratings</a>
            <span class="text-slate-300">|</span>
            <a href="praise-awards-recognition.php" class="text-sm text-slate-700 underline-offset-2 hover:underline">View All Nominations</a>
        </div>
    </header>

    <div class="p-6 grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="rounded-xl border border-slate-200 overflow-x-auto">
            <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-800">Ratings Awaiting Decision</h3>
                <span class="text-xs text-slate-500"><?= (int)count($ratingsDecisionRows) ?> item(s)</span>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-2.5">Employee</th>
                        <th class="text-left px-4 py-2.5">Cycle</th>
                        <th class="text-left px-4 py-2.5">Status</th>
                        <th class="text-left px-4 py-2.5">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($ratingsDecisionPreview)): ?>
                        <tr><td class="px-4 py-3 text-slate-500" colspan="4">No rating decisions pending.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ratingsDecisionPreview as $row): ?>
                            <tr>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)$row['employee'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)$row['cycle_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)$row['status_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="px-4 py-3"><button type="button" data-praise-rating-open data-evaluation-id="<?= htmlspecialchars((string)$row['evaluation_id'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['employee'], ENT_QUOTES, 'UTF-8') ?>" data-cycle-name="<?= htmlspecialchars((string)$row['cycle_name'], ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars((string)$row['status_raw'], ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"><span class="material-symbols-outlined text-[15px]">rate_review</span>Review</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="rounded-xl border border-slate-200 overflow-x-auto">
            <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-800">Nominations Awaiting Review</h3>
                <span class="text-xs text-slate-500"><?= (int)count($nominationApprovalRows) ?> item(s)</span>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-2.5">Nominee</th>
                        <th class="text-left px-4 py-2.5">Category</th>
                        <th class="text-left px-4 py-2.5">Cycle</th>
                        <th class="text-left px-4 py-2.5">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($nominationsPreview)): ?>
                        <tr><td class="px-4 py-3 text-slate-500" colspan="4">No nominations awaiting review.</td></tr>
                    <?php else: ?>
                        <?php foreach ($nominationsPreview as $row): ?>
                            <tr>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)$row['nominee'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)$row['award_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)$row['cycle_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><button type="button" data-praise-nomination-open data-nomination-id="<?= htmlspecialchars((string)$row['nomination_id'], ENT_QUOTES, 'UTF-8') ?>" data-nominee-name="<?= htmlspecialchars((string)$row['nominee'], ENT_QUOTES, 'UTF-8') ?>" data-award-name="<?= htmlspecialchars((string)$row['award_name'], ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"><span class="material-symbols-outlined text-[15px]">rate_review</span>Review</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Ready to Publish</h2>
            <p class="text-sm text-slate-500 mt-1">Approved nominations ready for announcement (first 5).</p>
        </div>
        <a href="praise-awards-recognition.php" class="text-sm text-slate-700 underline-offset-2 hover:underline">View All Publish Queue</a>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Awardee</th>
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Cycle</th>
                    <th class="text-left px-4 py-3">Approved Date</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($publishPreview)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="5">No approved awardees ready for publishing.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($publishPreview as $row): ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['nominee'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['award_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['cycle_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['published_date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><button type="button" data-praise-publish-open data-nomination-id="<?= htmlspecialchars((string)$row['nomination_id'], ENT_QUOTES, 'UTF-8') ?>" data-awardee-name="<?= htmlspecialchars((string)$row['nominee'], ENT_QUOTES, 'UTF-8') ?>" data-award-name="<?= htmlspecialchars((string)$row['award_name'], ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"><span class="material-symbols-outlined text-[15px]">campaign</span>Publish</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Analytics Snapshot</h2>
        <p class="text-sm text-slate-500 mt-1">Quick reference only. Full analysis lives in Reports & Analytics.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase text-slate-600">Latest Cycle Summary</p>
            <?php $latestSummary = $performanceSummaryRows[0] ?? null; ?>
            <?php if (is_array($latestSummary)): ?>
                <p class="mt-2 text-slate-700"><strong>Period:</strong> <?= htmlspecialchars((string)$latestSummary['period'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-slate-700"><strong>Evaluated:</strong> <?= htmlspecialchars((string)$latestSummary['evaluated_employees'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-slate-700"><strong>Average:</strong> <?= htmlspecialchars((string)$latestSummary['average_rating'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
                <p class="mt-2 text-slate-500">No cycle summary available.</p>
            <?php endif; ?>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase text-slate-600">Recent Recognition</p>
            <?php $latestRecognition = $recognitionHistoryRows[0] ?? null; ?>
            <?php if (is_array($latestRecognition)): ?>
                <p class="mt-2 text-slate-700"><strong>Awardee:</strong> <?= htmlspecialchars((string)$latestRecognition['awardee'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-slate-700"><strong>Category:</strong> <?= htmlspecialchars((string)$latestRecognition['award_name'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-slate-700"><strong>Published:</strong> <?= htmlspecialchars((string)$latestRecognition['published_date'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
                <p class="mt-2 text-slate-500">No recognition history yet.</p>
            <?php endif; ?>
        </article>
    </div>
</section>

<div id="praiseCycleModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="praiseCycleModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Set Evaluation Period</h3>
                <button type="button" data-modal-close="praiseCycleModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="praise.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="create_evaluation_cycle">
                <div class="md:col-span-2">
                    <label class="text-slate-600">Evaluation Cycle</label>
                    <input type="text" name="cycle_name" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="e.g. Q1 2026" required>
                </div>
                <div>
                    <label class="text-slate-600">Start Date</label>
                    <input type="date" name="period_start" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                </div>
                <div>
                    <label class="text-slate-600">End Date</label>
                    <input type="date" name="period_end" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="praiseCycleModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Period</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="praiseRatingModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="praiseRatingModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 id="praiseRatingModalTitle" class="text-lg font-semibold text-slate-800">Approve Supervisor Rating</h3>
                <button type="button" data-modal-close="praiseRatingModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form id="praiseRatingForm" action="praise.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="approve_supervisor_rating">
                <input type="hidden" id="praiseEvaluationId" name="evaluation_id" value="">
                <div>
                    <label class="text-slate-600">Employee</label>
                    <input id="praiseEvaluationEmployee" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Cycle</label>
                    <input id="praiseEvaluationCycle" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Decision</label>
                    <select id="praiseEvaluationDecision" name="decision" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="approved">Approve</option>
                        <option value="reviewed">Return for Review</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <p id="praiseRatingDecisionHint" class="text-xs text-slate-500">Approving finalizes this supervisor rating for reporting.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Remarks</label>
                    <textarea id="praiseEvaluationRemarks" name="remarks" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Optional note for the employee file."></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="praiseRatingModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button id="praiseRatingSubmitBtn" type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Decision</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="praiseAwardCategoryModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="praiseAwardCategoryModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 id="praiseAwardCategoryModalTitle" class="text-lg font-semibold text-slate-800">Create Award Category</h3>
                <button type="button" data-modal-close="praiseAwardCategoryModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form id="praiseAwardCategoryForm" action="praise.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input id="praiseAwardCategoryFormAction" type="hidden" name="form_action" value="create_award_category">
                <input id="praiseAwardCategoryId" type="hidden" name="award_id" value="">
                <div>
                    <label class="text-slate-600">Category Name</label>
                    <input id="praiseAwardCategoryName" type="text" name="award_name" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="e.g. Employee of the Quarter" required>
                </div>
                <div>
                    <label class="text-slate-600">Category Code</label>
                    <input id="praiseAwardCategoryCode" type="text" name="award_code" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Optional auto-generated if blank">
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Description</label>
                    <textarea id="praiseAwardCategoryDescription" name="description" rows="2" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Short description"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Criteria Notes</label>
                    <textarea id="praiseAwardCategoryCriteria" name="criteria" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Define eligibility and selection criteria"></textarea>
                </div>
                <div>
                    <label class="text-slate-600">Status</label>
                    <select id="praiseAwardCategoryStatus" name="is_active" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="praiseAwardCategoryModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button id="praiseAwardCategorySubmitBtn" type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="praiseNominationModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="praiseNominationModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Review Nomination</h3>
                <button type="button" data-modal-close="praiseNominationModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="praise.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="review_nomination">
                <input type="hidden" id="praiseNominationId" name="nomination_id" value="">
                <div>
                    <label class="text-slate-600">Nominee</label>
                    <input id="praiseNomineeName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Category</label>
                    <input id="praiseNominationAward" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Decision</label>
                    <select name="decision" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="approved">Approve</option>
                        <option value="rejected">Reject</option>
                    </select>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="praiseNominationModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Decision</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="praisePublishModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="praisePublishModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Publish Awardee</h3>
                <button type="button" data-modal-close="praisePublishModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="praise.php" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm">
                <input type="hidden" name="form_action" value="publish_awardee">
                <input type="hidden" id="praisePublishNominationId" name="nomination_id" value="">
                <div>
                    <label class="text-slate-600">Awardee</label>
                    <input id="praisePublishAwardeeName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Award Category</label>
                    <input id="praisePublishAwardName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-amber-800 text-xs">
                    Publishing triggers in-app notification to the selected awardee and logs the publication event.
                </div>
                <div class="flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="praisePublishModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Publish Awardee</button>
                </div>
            </form>
        </div>
    </div>
</div>
