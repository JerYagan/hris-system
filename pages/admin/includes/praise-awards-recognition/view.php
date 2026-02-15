<?php if ($state && $message): ?>
    <?php $alertClass = $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'; ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Create Award Categories</h2>
            <p class="text-sm text-slate-500 mt-1">Set up annual or periodic award tracks for employee recognition.</p>
        </div>
        <button type="button" id="praiseAwardNewCategoryBtn" data-modal-open="praiseAwardCategoryModal" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">New Category</button>
    </header>

    <div class="p-6 overflow-x-auto">
        <table id="praiseAwardCategoriesTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-3">Category</th><th class="text-left px-4 py-3">Code</th><th class="text-left px-4 py-3">Criteria</th><th class="text-left px-4 py-3">Status</th><th class="text-left px-4 py-3">Action</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($awardCategoryRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="5">No award categories found.</td></tr>
                <?php else: ?>
                    <?php foreach ($awardCategoryRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['award_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['award_code'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['criteria'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= (bool)$row['is_active'] ? 'Active' : 'Inactive' ?></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-praise-award-edit
                                    data-award-id="<?= htmlspecialchars((string)$row['award_id'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-award-name="<?= htmlspecialchars((string)$row['award_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-award-code="<?= htmlspecialchars((string)$row['award_code'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-award-description="<?= htmlspecialchars((string)$row['description'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-award-criteria="<?= htmlspecialchars((string)$row['criteria'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-award-active="<?= (bool)$row['is_active'] ? '1' : '0' ?>"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"
                                >
                                    <span class="material-symbols-outlined text-[15px]">edit_square</span>Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Approve Nominations</h2>
        <p class="text-sm text-slate-500 mt-1">Review submitted nominations and finalize candidate list.</p>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4"><div class="w-full md:w-1/2"><label class="text-sm text-slate-600">Search Nominations</label><input id="praiseNominationsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by nominee, category, nominator, cycle"></div></div>

    <div class="p-6 overflow-x-auto">
        <table id="praiseNominationsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-3">Nominee</th><th class="text-left px-4 py-3">Category</th><th class="text-left px-4 py-3">Cycle</th><th class="text-left px-4 py-3">Nominated By</th><th class="text-left px-4 py-3">Action</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($nominationApprovalRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="5">No pending nominations found.</td></tr>
                <?php else: ?>
                    <?php foreach ($nominationApprovalRows as $row): ?>
                        <tr data-praise-nomination-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['nominee'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['award_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['cycle_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['nominated_by'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><button type="button" data-praise-nomination-open data-nomination-id="<?= htmlspecialchars((string)$row['nomination_id'], ENT_QUOTES, 'UTF-8') ?>" data-nominee-name="<?= htmlspecialchars((string)$row['nominee'], ENT_QUOTES, 'UTF-8') ?>" data-award-name="<?= htmlspecialchars((string)$row['award_name'], ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"><span class="material-symbols-outlined text-[15px]">rate_review</span>Review</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div><h2 class="text-lg font-semibold text-slate-800">Publish Awardees</h2><p class="text-sm text-slate-500 mt-1">Release approved awardee list for official recognition announcements.</p></div>
    </header>

    <div class="p-6 overflow-x-auto">
        <table id="praisePublishTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-3">Awardee</th><th class="text-left px-4 py-3">Category</th><th class="text-left px-4 py-3">Cycle</th><th class="text-left px-4 py-3">Approved Date</th><th class="text-left px-4 py-3">Action</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($publishAwardeeRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="5">No approved awardees available for publishing.</td></tr>
                <?php else: ?>
                    <?php foreach ($publishAwardeeRows as $row): ?>
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

<div id="praiseAwardCategoryModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true"><div class="absolute inset-0 bg-slate-900/60" data-modal-close="praiseAwardCategoryModal"></div><div class="relative min-h-full flex items-center justify-center p-4"><div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl"><div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between"><h3 id="praiseAwardCategoryModalTitle" class="text-lg font-semibold text-slate-800">Create Award Category</h3><button type="button" data-modal-close="praiseAwardCategoryModal" class="text-slate-500 hover:text-slate-700">✕</button></div><form id="praiseAwardCategoryForm" action="praise-awards-recognition.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm"><input id="praiseAwardCategoryFormAction" type="hidden" name="form_action" value="create_award_category"><input id="praiseAwardCategoryId" type="hidden" name="award_id" value=""><div><label class="text-slate-600">Category Name</label><input id="praiseAwardCategoryName" type="text" name="award_name" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></div><div><label class="text-slate-600">Category Code</label><input id="praiseAwardCategoryCode" type="text" name="award_code" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div><div class="md:col-span-2"><label class="text-slate-600">Description</label><textarea id="praiseAwardCategoryDescription" name="description" rows="2" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></textarea></div><div class="md:col-span-2"><label class="text-slate-600">Criteria Notes</label><textarea id="praiseAwardCategoryCriteria" name="criteria" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></textarea></div><div><label class="text-slate-600">Status</label><select id="praiseAwardCategoryStatus" name="is_active" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"><option value="1">Active</option><option value="0">Inactive</option></select></div><div></div><div class="md:col-span-2 flex justify-end gap-3 mt-2"><button type="button" data-modal-close="praiseAwardCategoryModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button><button id="praiseAwardCategorySubmitBtn" type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Category</button></div></form></div></div></div>

<div id="praiseNominationModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true"><div class="absolute inset-0 bg-slate-900/60" data-modal-close="praiseNominationModal"></div><div class="relative min-h-full flex items-center justify-center p-4"><div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl"><div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between"><h3 class="text-lg font-semibold text-slate-800">Review Nomination</h3><button type="button" data-modal-close="praiseNominationModal" class="text-slate-500 hover:text-slate-700">✕</button></div><form action="praise-awards-recognition.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm"><input type="hidden" name="form_action" value="review_nomination"><input type="hidden" id="praiseNominationId" name="nomination_id" value=""><div><label class="text-slate-600">Nominee</label><input id="praiseNomineeName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div><div><label class="text-slate-600">Category</label><input id="praiseNominationAward" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div><div><label class="text-slate-600">Decision</label><select name="decision" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required><option value="approved">Approve</option><option value="rejected">Reject</option></select></div><div class="md:col-span-2 flex justify-end gap-3 mt-2"><button type="button" data-modal-close="praiseNominationModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button><button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Decision</button></div></form></div></div></div>

<div id="praisePublishModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true"><div class="absolute inset-0 bg-slate-900/60" data-modal-close="praisePublishModal"></div><div class="relative min-h-full flex items-center justify-center p-4"><div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl"><div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between"><h3 class="text-lg font-semibold text-slate-800">Publish Awardee</h3><button type="button" data-modal-close="praisePublishModal" class="text-slate-500 hover:text-slate-700">✕</button></div><form action="praise-awards-recognition.php" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm"><input type="hidden" name="form_action" value="publish_awardee"><input type="hidden" id="praisePublishNominationId" name="nomination_id" value=""><div><label class="text-slate-600">Awardee</label><input id="praisePublishAwardeeName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div><div><label class="text-slate-600">Award Category</label><input id="praisePublishAwardName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></div><div class="flex justify-end gap-3 mt-2"><button type="button" data-modal-close="praisePublishModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button><button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Publish Awardee</button></div></form></div></div></div>
