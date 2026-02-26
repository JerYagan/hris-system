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

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Applicants Registration Overview</h2>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase text-slate-500 tracking-wide">Registered Applicants</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$registeredApplicantsCount ?></p>
            <p class="text-xs text-slate-600 mt-1">Across active recruitment postings</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase text-emerald-700 tracking-wide">Hired</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$hiredCount ?></p>
            <p class="text-xs text-slate-600 mt-1">Ready for employee conversion in Applicant Tracking</p>
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
        <h2 class="text-lg font-semibold text-slate-800">Applicants Registration</h2>
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
                <option value="applied">Applied</option>
                <option value="verified">Verified</option>
                <option value="interview">Interview</option>
                <option value="evaluation">Evaluation</option>
                <option value="for approval">For Approval</option>
                <option value="hired">Hired</option>
                <option value="rejected">Rejected</option>
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
                        $profileDocuments = (array)($applicantProfileDataset[$applicationId]['documents'] ?? []);
                        $profileDocumentsJson = json_encode($profileDocuments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $searchText = strtolower(trim($fullName . ' ' . $email . ' ' . $position . ' ' . $submittedLabel . ' ' . $screeningLabel . ' ' . $basisLabel));
                        ?>
                        <tr class="hover:bg-slate-100 transition-colors" data-applicants-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-applicants-status="<?= htmlspecialchars(strtolower($screeningLabel), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <a href="applicant-profile.php?application_id=<?= htmlspecialchars($applicationId, ENT_QUOTES, 'UTF-8') ?>&source=admin-applicants" target="_blank" rel="noopener noreferrer" class="font-medium text-slate-800 hover:underline"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></a>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3" data-order="<?= htmlspecialchars($submittedAt !== '' ? date('Y-m-d', strtotime($submittedAt)) : '0000-00-00', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($submittedLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex px-2 py-1 text-xs rounded-full <?= htmlspecialchars($screeningClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($screeningLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($basisLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a
                                        href="applicant-profile.php?application_id=<?= htmlspecialchars($applicationId, ENT_QUOTES, 'UTF-8') ?>&source=admin-applicants"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"
                                    ><span class="material-symbols-outlined text-[16px]">person_search</span>View Profile</a>
                                    <button
                                        type="button"
                                        data-applicant-open
                                        data-application-id="<?= htmlspecialchars($applicationId, ENT_QUOTES, 'UTF-8') ?>"
                                        data-applicant-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>"
                                        data-applicant-email="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                        data-applicant-position="<?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?>"
                                        data-applicant-submitted="<?= htmlspecialchars($submittedLabel, ENT_QUOTES, 'UTF-8') ?>"
                                        data-applicant-screening="<?= htmlspecialchars($screeningLabel, ENT_QUOTES, 'UTF-8') ?>"
                                        data-applicant-documents="<?= htmlspecialchars((string)($profileDocumentsJson ?: '[]'), ENT_QUOTES, 'UTF-8') ?>"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 shadow-sm"
                                    ><span class="material-symbols-outlined text-[16px]">fact_check</span>Review Decision</button>
                                </div>
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
                        <p id="applicantProfileContact" class="text-xs text-slate-500 mt-2">-</p>
                        <p id="applicantProfileReference" class="text-xs text-slate-500 mt-1">-</p>
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
                        <p class="text-slate-600">Submitted Documents</p>
                        <div class="mt-2 overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="text-left px-3 py-2">Document Type</th>
                                        <th class="text-left px-3 py-2">File Name</th>
                                        <th class="text-left px-3 py-2">Uploaded</th>
                                        <th class="text-left px-3 py-2">Status</th>
                                        <th class="text-left px-3 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="applicantDocumentsBody" class="divide-y divide-slate-100">
                                    <tr><td class="px-3 py-3 text-slate-500" colspan="5">No document selected.</td></tr>
                                </tbody>
                            </table>
                        </div>
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

<script id="applicantProfileDataset" type="application/json"><?= htmlspecialchars(json_encode($applicantProfileDataset ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></script>
