<?php
$statusPill = static function (string $status): array {
    return applicantStatusPill($status);
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

<section class="bg-white border border-slate-200 rounded-2xl p-4 mb-6 flex items-center justify-between gap-3">
    <div>
        <p class="text-sm font-medium text-slate-800">Need to manage interview movement and status progression?</p>
        <p class="text-xs text-slate-500 mt-1">Use Applicant Tracking for progress updates and Evaluation for rule-based recommendations.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="applicant-tracking.php" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Open Applicant Tracking</a>
        <a href="evaluation.php" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Open Evaluation</a>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Applicant Overview</h2>
        <p class="text-sm text-slate-500 mt-1">Admin review flow for applicant screening decisions and recommendation validation.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase text-slate-500 tracking-wide">Registered Applicants</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$registeredApplicantsCount ?></p>
            <p class="text-xs text-slate-600 mt-1">Across active recruitment postings</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase text-emerald-700 tracking-wide">Approved</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$approvedCount ?></p>
            <p class="text-xs text-slate-600 mt-1">Qualified for next recruitment stage</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase text-amber-700 tracking-wide">Pending Decision</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$pendingDecisionCount ?></p>
            <p class="text-xs text-slate-600 mt-1">Requires admin final screening action</p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">View Registered Applicants</h2>
        <p class="text-sm text-slate-500 mt-1">Review applicants by posting, submission date, and screening status.</p>
    </header>

    <div class="px-6 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Applicants</label>
            <input id="adminApplicantsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by applicant, email, or position">
        </div>
        <div class="w-full md:w-64">
            <label class="text-sm text-slate-600">Screening Filter</label>
            <select id="adminApplicantsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All</option>
                <option value="for review">For Review</option>
                <option value="verified">Verified</option>
                <option value="disqualified">Disqualified</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="adminApplicantsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Applied Position</th>
                    <th class="text-left px-4 py-3">Date Submitted</th>
                    <th class="text-left px-4 py-3">Initial Screening</th>
                    <th class="text-left px-4 py-3">Basis</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($applications)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="6">No applicant records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applications as $application): ?>
                        <?php
                        $applicationId = (string)($application['id'] ?? '');
                        $fullName = (string)($application['applicant']['full_name'] ?? 'Unknown Applicant');
                        $email = (string)($application['applicant']['email'] ?? '-');
                        $position = (string)($application['job']['title'] ?? '-');
                        $submittedAt = (string)($application['submitted_at'] ?? '');
                        $submittedLabel = $submittedAt !== '' ? date('M d, Y', strtotime($submittedAt)) : '-';
                        $statusValue = (string)($application['application_status'] ?? 'submitted');
                        [$screeningLabel, $screeningClass] = $statusPill($statusValue);
                        $basisLabel = (string)($basisMap[$applicationId] ?? '-');
                        $searchText = strtolower(trim($fullName . ' ' . $email . ' ' . $position . ' ' . $submittedLabel . ' ' . $screeningLabel . ' ' . $basisLabel));
                        ?>
                        <tr class="hover:bg-slate-100 transition-colors" data-applicants-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-applicants-status="<?= htmlspecialchars(strtolower($screeningLabel), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3" data-order="<?= htmlspecialchars($submittedAt !== '' ? date('Y-m-d', strtotime($submittedAt)) : '0000-00-00', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($submittedLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex px-2 py-1 text-xs rounded-full <?= htmlspecialchars($screeningClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($screeningLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($basisLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-applicant-open
                                    data-application-id="<?= htmlspecialchars($applicationId, ENT_QUOTES, 'UTF-8') ?>"
                                    data-applicant-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>"
                                    data-applicant-email="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                    data-applicant-position="<?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?>"
                                    data-applicant-submitted="<?= htmlspecialchars($submittedLabel, ENT_QUOTES, 'UTF-8') ?>"
                                    data-applicant-screening="<?= htmlspecialchars($screeningLabel, ENT_QUOTES, 'UTF-8') ?>"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"
                                ><span class="material-symbols-outlined text-[16px]">person_search</span>View Profile</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div id="applicantDecisionModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="applicantDecisionModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Applicant Profile</h3>
                <button type="button" data-modal-close="applicantDecisionModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <form action="applicants.php" method="post" class="flex-1 min-h-0 flex flex-col" id="applicantDecisionForm">
                <input type="hidden" name="form_action" value="save_applicant_decision">
                <input type="hidden" id="decisionApplicationId" name="application_id" value="" required>
                <div class="flex-1 min-h-0 overflow-y-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase text-slate-500 tracking-wide">Selected Applicant</p>
                        <p id="applicantProfileName" class="text-base font-semibold text-slate-800 mt-1">-</p>
                        <p id="applicantProfileMeta" class="text-sm text-slate-600 mt-1">-</p>
                    </div>
                    <div>
                        <label class="text-slate-600">Decision</label>
                        <select name="decision" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            <option value="approve_for_next_stage">Approve for Next Stage</option>
                            <option value="disqualify_application">Disqualify Application</option>
                            <option value="return_for_compliance">Return for Compliance</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-slate-600">Decision Date</label>
                        <input type="date" name="decision_date" value="<?= date('Y-m-d') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                    </div>
                    <div>
                        <label class="text-slate-600">Basis</label>
                        <select name="basis" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            <option value="Meets Minimum Qualification Standards">Meets Minimum Qualification Standards</option>
                            <option value="Incomplete Documentary Requirements">Incomplete Documentary Requirements</option>
                            <option value="Did Not Meet Required Eligibility">Did Not Meet Required Eligibility</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-slate-600">Admin Remarks</label>
                        <textarea name="remarks" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="State summary of findings and justification for screening decision"></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-modal-close="applicantDecisionModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Decision</button>
                </div>
            </form>
        </div>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">View System Recommendation</h2>
        <p class="text-sm text-slate-500 mt-1">Review automated recommendation outputs and compare with admin decision.</p>
    </header>

    <div class="px-6 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Recommendations</label>
            <input id="adminRecommendationSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by applicant, recommendation, decision, or basis">
        </div>
        <div class="w-full md:w-64">
            <label class="text-sm text-slate-600">Alignment Filter</label>
            <select id="adminRecommendationAlignmentFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All</option>
                <option value="Match">Match</option>
                <option value="Override">Override</option>
                <option value="Pending">Pending</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="adminRecommendationTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">System Recommendation</th>
                    <th class="text-left px-4 py-3">Confidence</th>
                    <th class="text-left px-4 py-3">Admin Decision</th>
                    <th class="text-left px-4 py-3">Basis</th>
                    <th class="text-left px-4 py-3">Alignment</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($recommendationRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="6">No recommendation rows available.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recommendationRows as $row): ?>
                        <?php $recommendationSearch = strtolower(trim((string)$row['applicant_name'] . ' ' . (string)$row['system_recommendation'] . ' ' . (string)$row['confidence'] . ' ' . (string)$row['admin_decision'] . ' ' . (string)($row['basis'] ?? '-') . ' ' . (string)$row['alignment'])); ?>
                        <tr class="hover:bg-slate-100 transition-colors" data-recommendation-search="<?= htmlspecialchars($recommendationSearch, ENT_QUOTES, 'UTF-8') ?>" data-recommendation-alignment="<?= htmlspecialchars(strtolower((string)$row['alignment']), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['applicant_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)$row['recommendation_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['system_recommendation'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['confidence'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['admin_decision'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['basis'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)$row['alignment_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['alignment'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
