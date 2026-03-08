<?php
$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    if ($key === 'active') {
        return ['Active', 'bg-emerald-100 text-emerald-800'];
    }
    return ['Inactive', 'bg-amber-100 text-amber-800'];
};

$rolePill = static function (string $roleKey): array {
    $normalized = strtolower(trim($roleKey));
    if ($normalized === 'staff') {
        return ['Staff', 'bg-amber-100 text-amber-800'];
    }
    return ['Employee', 'bg-emerald-100 text-emerald-800'];
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

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm flex gap-2">
        <span class="material-symbols-outlined text-base">error</span>
        <span><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <div class="px-6 py-4 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Personal Information Workspace</h2>
            <p class="text-sm text-slate-500 mt-1">Use quick actions to manage employee profiles without leaving this page.</p>
        </div>
        <button type="button" data-modal-open="personalInfoEditProfileModal" class="inline-flex items-center gap-2 px-4 py-2 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">
            <span class="material-symbols-outlined text-[18px]">edit</span>
            Edit Profile
        </button>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Employee Overview</h2>
        <p class="text-sm text-slate-500 mt-1">Quick summary of employee records, completion, and active status.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-5 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Profiles</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$totalProfiles, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-600 mt-1">Across all divisions</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Complete Records</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$completeRecords, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-600 mt-1">Profiles with contact and assignment data</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase tracking-wide text-amber-700">Needs Update</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$needsUpdateCount, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-600 mt-1">Missing key profile details</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">Active Employees</p>
            <p class="text-xl font-semibold text-slate-800 mt-2"><?= htmlspecialchars((string)$activeEmployees, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Profiles currently tagged as active</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">Inactive Employees</p>
            <p class="text-xl font-semibold text-slate-800 mt-2"><?= htmlspecialchars((string)$inactiveEmployees, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Resigned/retired/other inactive statuses</p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Pending Staff Review</h2>
            <p class="text-sm text-slate-500 mt-1">Staff-submitted employee profile recommendations awaiting admin final review.</p>
        </div>
        <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 text-xs px-2.5 py-1 font-medium">Pending</span>
    </header>

    <div class="px-6 pt-5 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
        <div class="md:col-span-2">
            <label class="text-slate-600">Search Recommendations</label>
            <input id="pendingProfileSearchInput" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Search by employee, staff email, or summary">
        </div>
        <div>
            <label class="text-slate-600">Status Filter</label>
            <select id="pendingProfileStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="">All Statuses</option>
                <option value="Pending Admin Action">Pending Admin Action</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Submitted Date</label>
            <input id="pendingProfileDateFilter" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="YYYY-MM-DD">
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="personalInfoPendingReviewTable" data-simple-table="true" class="w-full text-sm table-fixed">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3 w-[24%]">Employee</th>
                    <th class="text-left px-4 py-3 w-[20%]">Submitted By</th>
                    <th class="text-left px-4 py-3 w-[20%]">Submitted On</th>
                    <th class="text-left px-4 py-3 w-[18%]">Status</th>
                    <th class="text-left px-4 py-3 w-[18%]">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($recommendationHistoryRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="5">No pending staff profile recommendations found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recommendationHistoryRows as $recommendationRow): ?>
                        <?php
                            $recommendationDetailsJson = htmlspecialchars((string)json_encode($recommendationRow['proposed_changes'] ?? [], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                            $submittedDateValue = htmlspecialchars((string)($recommendationRow['submitted_at_date'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $statusLabelValue = (string)($recommendationRow['status_label'] ?? 'Pending Admin Action');
                        ?>
                        <tr
                            data-pending-review-row
                            data-review-search="<?= htmlspecialchars(strtolower((string)($recommendationRow['search_text'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                            data-review-status="<?= htmlspecialchars(strtolower($statusLabelValue), ENT_QUOTES, 'UTF-8') ?>"
                            data-review-date="<?= $submittedDateValue ?>"
                        >
                            <td class="px-4 py-3 font-medium text-slate-700 align-top break-words"><?= htmlspecialchars((string)($recommendationRow['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600 align-top break-words"><?= htmlspecialchars((string)($recommendationRow['submitted_by'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600 align-top"><?= htmlspecialchars((string)($recommendationRow['submitted_at_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs <?= htmlspecialchars((string)($recommendationRow['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)($recommendationRow['status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-pending-review-open
                                    data-recommendation-log-id="<?= htmlspecialchars((string)($recommendationRow['recommendation_log_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-person-id="<?= htmlspecialchars((string)($recommendationRow['person_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-employee-name="<?= htmlspecialchars((string)($recommendationRow['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                    data-submitted-by="<?= htmlspecialchars((string)($recommendationRow['submitted_by'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                    data-submitted-at="<?= htmlspecialchars((string)($recommendationRow['submitted_at_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                    data-summary="<?= htmlspecialchars((string)($recommendationRow['summary'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                    data-details="<?= $recommendationDetailsJson ?>"
                                    class="px-3 py-1.5 text-xs rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"
                                >
                                    Review
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="pendingProfileFilterEmpty" class="hidden">
                    <td class="px-4 py-3 text-slate-500" colspan="5">No recommendation records match your current search/filter selection.</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="pendingProfilePaginationInfo" class="text-xs text-slate-500">Page 1 of 1</p>
        <div class="flex items-center gap-2">
            <button type="button" id="pendingProfilePrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <button type="button" id="pendingProfileNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Pending Spouse Entry Requests</h2>
            <p class="text-sm text-slate-500 mt-1">Employee-submitted additional spouse entries awaiting admin decision.</p>
        </div>
        <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 text-xs px-2.5 py-1 font-medium">Pending</span>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm table-fixed">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3 w-[18%]">Employee</th>
                    <th class="text-left px-4 py-3 w-[16%]">Requested Spouse</th>
                    <th class="text-left px-4 py-3 w-[14%]">Submitted By</th>
                    <th class="text-left px-4 py-3 w-[14%]">Submitted On</th>
                    <th class="text-left px-4 py-3 w-[14%]">Attachment</th>
                    <th class="text-left px-4 py-3 w-[24%]">Decision</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($spouseRequestRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="6">No pending spouse entry requests found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($spouseRequestRows as $requestRow): ?>
                        <tr>
                            <td class="px-4 py-3 align-top font-medium text-slate-700"><?= htmlspecialchars((string)($requestRow['employee_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 align-top">
                                <p class="text-slate-700"><?= htmlspecialchars((string)($requestRow['spouse_name'] ?? 'Spouse Request'), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php if (!empty($requestRow['request_notes'])): ?>
                                    <p class="text-xs text-slate-500 mt-1">Notes: <?= htmlspecialchars((string)$requestRow['request_notes'], ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 align-top text-slate-600"><?= htmlspecialchars((string)($requestRow['submitted_by'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 align-top text-slate-600"><?= htmlspecialchars((string)($requestRow['submitted_at_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 align-top">
                                <?php if (!empty($requestRow['attachment_url'])): ?>
                                    <a href="<?= htmlspecialchars((string)$requestRow['attachment_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="text-daGreen hover:underline text-xs">
                                        <?= htmlspecialchars((string)($requestRow['attachment_name'] ?? 'View Attachment'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-xs text-slate-500">No attachment</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <form method="POST" action="personal-information.php" class="space-y-2">
                                    <input type="hidden" name="form_action" value="review_spouse_request">
                                    <input type="hidden" name="request_log_id" value="<?= htmlspecialchars((string)($requestRow['request_log_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="person_id" value="<?= htmlspecialchars((string)($requestRow['person_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <textarea name="remarks" rows="2" class="w-full border border-slate-300 rounded-md px-2 py-1.5 text-xs" placeholder="Optional remarks (required for rejection)"></textarea>
                                    <div class="flex gap-2">
                                        <button type="submit" name="decision" value="approve" class="px-3 py-1.5 text-xs rounded-md bg-emerald-600 text-white hover:bg-emerald-700">Approve</button>
                                        <button type="submit" name="decision" value="reject" class="px-3 py-1.5 text-xs rounded-md bg-rose-600 text-white hover:bg-rose-700">Reject</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<form id="personalInfoRecommendationReviewForm" action="personal-information.php" method="POST" class="hidden">
    <input type="hidden" name="form_action" value="review_profile_recommendation">
    <input type="hidden" id="recommendationReviewLogId" name="recommendation_log_id" value="">
    <input type="hidden" id="recommendationReviewPersonId" name="person_id" value="">
    <input type="hidden" id="recommendationReviewDecision" name="decision" value="">
    <input type="hidden" id="recommendationReviewRemarks" name="remarks" value="">
</form>

<div id="personalInfoRecommendationReviewModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoRecommendationReviewModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-white rounded-2xl border border-slate-200 shadow-xl max-h-[calc(100vh-2rem)] overflow-y-auto">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Review Staff Recommendation</h3>
                <button type="button" data-modal-close="personalInfoRecommendationReviewModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <div class="px-6 py-5 space-y-4 text-sm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-slate-600">Employee</label>
                        <input id="recommendationReviewEmployee" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                    </div>
                    <div>
                        <label class="text-slate-600">Submitted By</label>
                        <input id="recommendationReviewSubmittedBy" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                    </div>
                </div>
                <div>
                    <label class="text-slate-600">Submitted On</label>
                    <input id="recommendationReviewSubmittedAt" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Summary</label>
                    <textarea id="recommendationReviewSummary" rows="2" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></textarea>
                </div>
                <div>
                    <label class="text-slate-600">Proposed Changes</label>
                    <div id="recommendationReviewDetails" class="mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50 text-slate-700 max-h-48 overflow-y-auto"></div>
                </div>
                <div>
                    <label class="text-slate-600">Decision</label>
                    <select id="recommendationReviewDecisionSelect" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-white">
                        <option value="approve">Approve and apply recommended changes</option>
                        <option value="reject">Reject recommendation</option>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Remarks</label>
                    <textarea id="recommendationReviewRemarksInput" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Remarks for approval/rejection"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" data-modal-close="personalInfoRecommendationReviewModal" class="px-4 py-2 text-sm rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="button" id="recommendationReviewSubmit" class="px-4 py-2 text-sm rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Decision</button>
            </div>
        </div>
    </div>
</div>

<div id="personalInfoEditProfileModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoEditProfileModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-white rounded-2xl border border-slate-200 shadow-xl max-h-[calc(100vh-2rem)] overflow-y-auto">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Edit Profile</h3>
                    <p class="text-sm text-slate-500 mt-1">Select an employee below to open the full PDS profile editor.</p>
                </div>
                <button type="button" data-modal-close="personalInfoEditProfileModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <div class="p-6 space-y-4 text-sm">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <label class="text-slate-600">Search Employee</label>
                        <input id="quickEditProfileSearchInput" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Search by employee code or employee name">
                    </div>
                    <div class="flex items-end">
                        <button type="button" data-person-profile-add class="w-full px-4 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Add Employee</button>
                    </div>
                </div>

                <div id="quickEditProfileList" class="border border-slate-200 rounded-lg divide-y divide-slate-100 max-h-80 overflow-y-auto">
                    <?php foreach ($employeeTableRows as $quickRow): ?>
                        <button
                            type="button"
                            data-quick-edit-profile-option
                            data-person-id="<?= htmlspecialchars((string)$quickRow['person_id'], ENT_QUOTES, 'UTF-8') ?>"
                            data-search-text="<?= htmlspecialchars(strtolower((string)($quickRow['employee_code'] . ' ' . $quickRow['full_name'])), ENT_QUOTES, 'UTF-8') ?>"
                            class="w-full text-left px-4 py-3 hover:bg-slate-50"
                        >
                            <p class="font-medium text-slate-800"><?= htmlspecialchars((string)$quickRow['full_name'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)$quickRow['employee_code'], ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars((string)$quickRow['department'], ENT_QUOTES, 'UTF-8') ?></p>
                        </button>
                    <?php endforeach; ?>
                    <p id="quickEditProfileEmpty" class="hidden px-4 py-4 text-slate-500">No employee record matches your search.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Employee Management Table</h2>
        <p class="text-sm text-slate-500 mt-1">Search and filter employee records, then select an action from the dropdown.</p>
    </header>

    <div class="px-6 pt-5 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
        <div class="md:col-span-2">
            <label class="text-slate-600">Search Employee Records</label>
            <input id="personalInfoRecordsSearchInput" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Search by employee ID, name, email, division, or position">
        </div>
        <div>
            <label class="text-slate-600">Division Filter</label>
            <select id="personalInfoRecordsDepartmentFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="">All Divisions</option>
                <?php foreach ($departmentFilterOptions as $departmentName): ?>
                    <option value="<?= htmlspecialchars((string)$departmentName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$departmentName, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Status Filter</label>
            <select id="personalInfoRecordsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="">All Statuses</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
        </div>
        <div class="md:col-span-4 flex justify-end">
            <button type="button" id="personalInfoResetFilters" class="px-3 py-2 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Reset Filters</button>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="personalInfoEmployeesTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee ID</th>
                    <th class="text-left px-4 py-3">Full Name</th>
                    <th class="text-left px-4 py-3">Email</th>
                    <th class="text-left px-4 py-3">Division</th>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($employeeTableRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="7">No employee records found<?= ($filterKeyword !== '' || $filterDepartment !== '' || $filterStatus !== '') ? ' for current filters' : '' ?>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employeeTableRows as $row): ?>
                        <?php [$statusLabel, $statusClass] = $statusPill((string)$row['status_raw']); ?>
                        <?php $childrenJson = htmlspecialchars((string)json_encode($row['children'] ?? [], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
                        <?php $educationRows = (array)($row['educational_backgrounds'] ?? []); ?>
                        <?php $educationJson = htmlspecialchars((string)json_encode($educationRows, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
                        <tr data-profile-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>" data-profile-department="<?= htmlspecialchars((string)$row['department'], ENT_QUOTES, 'UTF-8') ?>" data-profile-status="<?= htmlspecialchars((string)$statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['employee_code'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['email'] !== '' ? $row['email'] : '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['department'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['position'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[90px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <div data-person-action-scope data-admin-action-scope class="relative inline-block text-left">
                                    <button type="button" data-admin-action-menu-toggle aria-haspopup="menu" aria-expanded="false" class="admin-action-button w-full min-w-[160px]">
                                        <span class="admin-action-button-label">
                                            <span class="material-symbols-outlined">more_horiz</span>
                                            Actions
                                        </span>
                                        <span class="material-symbols-outlined admin-action-chevron">expand_more</span>
                                    </button>

                                    <div data-person-action-menu data-admin-action-menu role="menu" class="admin-action-menu hidden w-72">
                                        <button type="button" data-action-menu-item data-action-target="edit-profile" role="menuitem" class="admin-action-item">
                                            <span class="material-symbols-outlined">edit</span>
                                            Edit Employee Profile
                                        </button>
                                        <button type="button" data-action-menu-item data-action-target="assign" role="menuitem" class="admin-action-item">
                                            <span class="material-symbols-outlined">person_add</span>
                                            Assign Division and Position
                                        </button>
                                        <button type="button" data-action-menu-item data-action-target="manage-status" role="menuitem" class="admin-action-item">
                                            <span class="material-symbols-outlined">manage_accounts</span>
                                            Manage Employee Status
                                        </button>
                                        <button type="button" data-action-menu-item data-action-target="merge-duplicate" role="menuitem" class="admin-action-item admin-action-item-warning">
                                            <span class="material-symbols-outlined">merge_type</span>
                                            Merge/Delete Duplicate Profile
                                        </button>
                                        <div class="admin-action-divider"></div>
                                        <button type="button" data-action-menu-item data-action-target="archive" role="menuitem" class="admin-action-item admin-action-item-danger">
                                            <span class="material-symbols-outlined">archive</span>
                                            Archive Employee Profile
                                        </button>
                                    </div>

                                    <button type="button" hidden data-action-trigger="edit-profile" data-person-profile-open data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>" data-first-name="<?= htmlspecialchars((string)$row['first_name'], ENT_QUOTES, 'UTF-8') ?>" data-middle-name="<?= htmlspecialchars((string)$row['middle_name'], ENT_QUOTES, 'UTF-8') ?>" data-surname="<?= htmlspecialchars((string)$row['surname'], ENT_QUOTES, 'UTF-8') ?>" data-name-extension="<?= htmlspecialchars((string)$row['name_extension'], ENT_QUOTES, 'UTF-8') ?>" data-date-of-birth="<?= htmlspecialchars((string)$row['date_of_birth'], ENT_QUOTES, 'UTF-8') ?>" data-place-of-birth="<?= htmlspecialchars((string)$row['place_of_birth'], ENT_QUOTES, 'UTF-8') ?>" data-sex-at-birth="<?= htmlspecialchars((string)$row['sex_at_birth'], ENT_QUOTES, 'UTF-8') ?>" data-civil-status="<?= htmlspecialchars((string)$row['civil_status'], ENT_QUOTES, 'UTF-8') ?>" data-height-m="<?= htmlspecialchars((string)$row['height_m'], ENT_QUOTES, 'UTF-8') ?>" data-weight-kg="<?= htmlspecialchars((string)$row['weight_kg'], ENT_QUOTES, 'UTF-8') ?>" data-blood-type="<?= htmlspecialchars((string)$row['blood_type'], ENT_QUOTES, 'UTF-8') ?>" data-citizenship="<?= htmlspecialchars((string)$row['citizenship'], ENT_QUOTES, 'UTF-8') ?>" data-dual-citizenship-country="<?= htmlspecialchars((string)$row['dual_citizenship_country'], ENT_QUOTES, 'UTF-8') ?>" data-telephone-no="<?= htmlspecialchars((string)$row['telephone_no'], ENT_QUOTES, 'UTF-8') ?>" data-email="<?= htmlspecialchars((string)$row['email'], ENT_QUOTES, 'UTF-8') ?>" data-mobile="<?= htmlspecialchars((string)$row['mobile'], ENT_QUOTES, 'UTF-8') ?>" data-agency-employee-no="<?= htmlspecialchars((string)$row['agency_employee_no'], ENT_QUOTES, 'UTF-8') ?>" data-residential-house-no="<?= htmlspecialchars((string)$row['residential_house_no'], ENT_QUOTES, 'UTF-8') ?>" data-residential-street="<?= htmlspecialchars((string)$row['residential_street'], ENT_QUOTES, 'UTF-8') ?>" data-residential-subdivision="<?= htmlspecialchars((string)$row['residential_subdivision'], ENT_QUOTES, 'UTF-8') ?>" data-residential-barangay="<?= htmlspecialchars((string)$row['residential_barangay'], ENT_QUOTES, 'UTF-8') ?>" data-residential-city-municipality="<?= htmlspecialchars((string)$row['residential_city_municipality'], ENT_QUOTES, 'UTF-8') ?>" data-residential-province="<?= htmlspecialchars((string)$row['residential_province'], ENT_QUOTES, 'UTF-8') ?>" data-residential-zip-code="<?= htmlspecialchars((string)$row['residential_zip_code'], ENT_QUOTES, 'UTF-8') ?>" data-permanent-house-no="<?= htmlspecialchars((string)$row['permanent_house_no'], ENT_QUOTES, 'UTF-8') ?>" data-permanent-street="<?= htmlspecialchars((string)$row['permanent_street'], ENT_QUOTES, 'UTF-8') ?>" data-permanent-subdivision="<?= htmlspecialchars((string)$row['permanent_subdivision'], ENT_QUOTES, 'UTF-8') ?>" data-permanent-barangay="<?= htmlspecialchars((string)$row['permanent_barangay'], ENT_QUOTES, 'UTF-8') ?>" data-permanent-city-municipality="<?= htmlspecialchars((string)$row['permanent_city_municipality'], ENT_QUOTES, 'UTF-8') ?>" data-permanent-province="<?= htmlspecialchars((string)$row['permanent_province'], ENT_QUOTES, 'UTF-8') ?>" data-permanent-zip-code="<?= htmlspecialchars((string)$row['permanent_zip_code'], ENT_QUOTES, 'UTF-8') ?>" data-umid-no="<?= htmlspecialchars((string)$row['umid_no'], ENT_QUOTES, 'UTF-8') ?>" data-pagibig-no="<?= htmlspecialchars((string)$row['pagibig_no'], ENT_QUOTES, 'UTF-8') ?>" data-philhealth-no="<?= htmlspecialchars((string)$row['philhealth_no'], ENT_QUOTES, 'UTF-8') ?>" data-psn-no="<?= htmlspecialchars((string)$row['psn_no'], ENT_QUOTES, 'UTF-8') ?>" data-tin-no="<?= htmlspecialchars((string)$row['tin_no'], ENT_QUOTES, 'UTF-8') ?>"></button>
                                    <button type="button" hidden data-action-trigger="assign" data-person-assignment-open data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>"></button>
                                    <button type="button" hidden data-action-trigger="manage-status" data-person-status-open data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>"></button>
                                    <button type="button" hidden data-action-trigger="merge-duplicate" data-person-merge-open data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>"></button>
                                    <button type="button" hidden data-action-trigger="archive" data-person-profile-archive data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>"></button>

                                    <button type="button" hidden data-person-family-open data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>" data-spouse-surname="<?= htmlspecialchars((string)$row['spouse_surname'], ENT_QUOTES, 'UTF-8') ?>" data-spouse-first-name="<?= htmlspecialchars((string)$row['spouse_first_name'], ENT_QUOTES, 'UTF-8') ?>" data-spouse-middle-name="<?= htmlspecialchars((string)$row['spouse_middle_name'], ENT_QUOTES, 'UTF-8') ?>" data-spouse-extension-name="<?= htmlspecialchars((string)$row['spouse_extension_name'], ENT_QUOTES, 'UTF-8') ?>" data-spouse-occupation="<?= htmlspecialchars((string)$row['spouse_occupation'], ENT_QUOTES, 'UTF-8') ?>" data-spouse-employer-business-name="<?= htmlspecialchars((string)$row['spouse_employer_business_name'], ENT_QUOTES, 'UTF-8') ?>" data-spouse-business-address="<?= htmlspecialchars((string)$row['spouse_business_address'], ENT_QUOTES, 'UTF-8') ?>" data-spouse-telephone-no="<?= htmlspecialchars((string)$row['spouse_telephone_no'], ENT_QUOTES, 'UTF-8') ?>" data-father-surname="<?= htmlspecialchars((string)$row['father_surname'], ENT_QUOTES, 'UTF-8') ?>" data-father-first-name="<?= htmlspecialchars((string)$row['father_first_name'], ENT_QUOTES, 'UTF-8') ?>" data-father-middle-name="<?= htmlspecialchars((string)$row['father_middle_name'], ENT_QUOTES, 'UTF-8') ?>" data-father-extension-name="<?= htmlspecialchars((string)$row['father_extension_name'], ENT_QUOTES, 'UTF-8') ?>" data-mother-surname="<?= htmlspecialchars((string)$row['mother_surname'], ENT_QUOTES, 'UTF-8') ?>" data-mother-first-name="<?= htmlspecialchars((string)$row['mother_first_name'], ENT_QUOTES, 'UTF-8') ?>" data-mother-middle-name="<?= htmlspecialchars((string)$row['mother_middle_name'], ENT_QUOTES, 'UTF-8') ?>" data-mother-extension-name="<?= htmlspecialchars((string)$row['mother_extension_name'], ENT_QUOTES, 'UTF-8') ?>" data-children="<?= $childrenJson ?>"></button>
                                    <button type="button" hidden data-person-education-open data-person-id="<?= htmlspecialchars((string)$row['person_id'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['full_name'], ENT_QUOTES, 'UTF-8') ?>" data-educational-backgrounds="<?= $educationJson ?>"></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="personalInfoFilterEmpty" class="hidden">
                    <td class="px-4 py-3 text-slate-500" colspan="7">No records match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="personalInfoPaginationInfo" class="text-xs text-slate-500">Page 1 of 1</p>
        <div class="flex items-center gap-2">
            <button type="button" id="personalInfoPrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <button type="button" id="personalInfoNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Staff & Employee Profiles</h2>
        <p class="text-sm text-slate-500 mt-1">Select a profile card to open a full employee profile page.</p>
    </header>

    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php if (empty($employeeTableRows)): ?>
            <div class="col-span-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                No staff or employee profiles available.
            </div>
        <?php else: ?>
            <?php foreach ($employeeTableRows as $cardRow): ?>
                <?php
                    $profileUrl = 'employee-profile.php?person_id=' . rawurlencode((string)($cardRow['person_id'] ?? '')) . '&source=personal-information';
                    $fullName = trim((string)($cardRow['full_name'] ?? ''));
                    $initialsSource = preg_replace('/\s+/', ' ', $fullName);
                    $nameParts = array_values(array_filter(explode(' ', (string)$initialsSource), static fn(string $part): bool => $part !== ''));
                    $initials = '';
                    if (!empty($nameParts)) {
                        $initials = strtoupper(substr((string)$nameParts[0], 0, 1));
                        if (count($nameParts) > 1) {
                            $initials .= strtoupper(substr((string)$nameParts[count($nameParts) - 1], 0, 1));
                        }
                    }
                    if ($initials === '') {
                        $initials = 'NA';
                    }
                    [$roleText, $roleClass] = $rolePill((string)($cardRow['role_key'] ?? ''));
                    $photoUrl = trim((string)($cardRow['profile_photo_url'] ?? ''));
                ?>
                <a href="<?= htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') ?>" class="group rounded-xl border border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm transition p-4 flex items-start gap-3">
                    <div class="shrink-0">
                        <?php if ($photoUrl !== ''): ?>
                            <img src="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" class="w-12 h-12 rounded-full object-cover border border-slate-200">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-full bg-slate-200 text-slate-700 text-sm font-semibold flex items-center justify-center border border-slate-300">
                                <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs text-slate-500 mt-0.5 truncate"><?= htmlspecialchars((string)($cardRow['employee_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs text-slate-600 mt-2 truncate"><?= htmlspecialchars((string)($cardRow['position'] ?? 'Unassigned Position'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs text-slate-500 truncate"><?= htmlspecialchars((string)($cardRow['department'] ?? 'Unassigned Division'), ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] <?= htmlspecialchars($roleClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($roleText, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="text-[11px] text-slate-500 group-hover:text-slate-700">View profile →</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<form id="personalInfoArchiveForm" action="personal-information.php" method="POST" class="hidden">
    <input type="hidden" name="form_action" value="save_profile">
    <input type="hidden" name="profile_action" value="archive">
    <input type="hidden" name="person_id" id="personalInfoArchivePersonId" value="">
    <input type="hidden" name="employee_name" id="personalInfoArchiveEmployeeName" value="">
    <input type="hidden" name="profile_notes" value="Archived by admin from employee records table.">
</form>

<form id="personalInfoMergeForm" action="personal-information.php" method="POST" class="hidden">
    <input type="hidden" name="form_action" value="resolve_duplicate_profile">
    <input type="hidden" name="source_person_id" id="personalInfoMergeSourcePersonId" value="">
    <input type="hidden" name="source_employee_name" id="personalInfoMergeSourceEmployeeName" value="">
    <input type="hidden" name="target_person_id" id="personalInfoMergeTargetPersonId" value="">
    <input type="hidden" name="resolution_mode" id="personalInfoMergeResolutionMode" value="merge">
    <input type="hidden" name="resolution_notes" id="personalInfoMergeResolutionNotes" value="">
</form>

<form id="personalInfoEligibilityDeleteForm" action="personal-information.php" method="POST" class="hidden">
    <input type="hidden" name="form_action" value="save_civil_service_eligibility">
    <input type="hidden" name="eligibility_action" value="delete">
    <input type="hidden" id="personalInfoEligibilityDeletePersonId" name="person_id" value="">
    <input type="hidden" id="personalInfoEligibilityDeleteId" name="eligibility_id" value="">
</form>

<form id="personalInfoWorkExperienceDeleteForm" action="personal-information.php" method="POST" class="hidden">
    <input type="hidden" name="form_action" value="save_work_experience">
    <input type="hidden" name="work_experience_action" value="delete">
    <input type="hidden" id="personalInfoWorkExperienceDeletePersonId" name="person_id" value="">
    <input type="hidden" id="personalInfoWorkExperienceDeleteId" name="work_experience_id" value="">
</form>

<div id="personalInfoProfileModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoProfileModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-6xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="border-b border-slate-200">
                <div class="px-6 py-3 bg-slate-50/70 flex items-center gap-3">
                    <div class="w-full flex items-stretch">
                        <button type="button" data-pds-tab-target="section_i" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-slate-900 text-slate-900 bg-white">Personal Information</button>
                        <button type="button" data-pds-tab-target="section_ii" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 bg-white">Family Background</button>
                        <button type="button" data-pds-tab-target="section_iii" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 bg-white">Educational Background</button>
                    </div>
                    <button type="button" data-modal-close="personalInfoProfileModal" class="text-slate-500 hover:text-slate-700">✕</button>
                </div>
                <p id="personalInfoProfileEmployeeLabel" class="px-6 py-2 text-sm text-slate-500">Selected employee</p>
            </div>
            <form action="personal-information.php" method="POST" class="flex-1 min-h-0 flex flex-col" id="personalInfoProfileForm">
                <input type="hidden" name="form_action" value="save_profile">
                <input type="hidden" name="profile_action" id="profileAction" value="edit">
                <input name="person_id" type="hidden" id="profilePersonId" value="">
                <div class="flex-1 min-h-0 overflow-y-auto p-6 space-y-6 text-sm">
                    <section class="space-y-3">
                        <h4 class="text-sm font-semibold text-slate-700">Basic Identity</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-slate-600">First Name</label>
                                <input id="profileFirstName" name="first_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter first name" required>
                            </div>
                            <div>
                                <label class="text-slate-600">Middle Name</label>
                                <input id="profileMiddleName" name="middle_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter middle name">
                            </div>
                            <div>
                                <label class="text-slate-600">Last Name</label>
                                <input id="profileSurname" name="surname" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter last name" required>
                            </div>
                            <div>
                                <label class="text-slate-600">Name Extension</label>
                                <input id="profileNameExtension" name="name_extension" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Jr., Sr., III">
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Demographics</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-slate-600">Date of Birth</label>
                                <input id="profileDateOfBirth" name="date_of_birth" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Place of Birth</label>
                                <div class="relative mt-1">
                                    <input id="profilePlaceOfBirth" name="place_of_birth" type="text" autocomplete="off" data-modern-search="placeOfBirth" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" placeholder="City/Municipality, Province">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                                </div>
                            </div>
                            <div>
                                <label class="text-slate-600">Sex at Birth</label>
                                <select id="profileSexAtBirth" name="sex_at_birth" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                                    <option value="">Select sex</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-slate-600">Civil Status</label>
                                <div class="relative mt-1">
                                    <input id="profileCivilStatus" name="civil_status" type="text" autocomplete="off" data-modern-search="civilStatus" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" placeholder="Single, Married, etc.">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                                </div>
                            </div>
                            <div>
                                <label class="text-slate-600">Height (m)</label>
                                <input id="profileHeightM" name="height_m" type="number" min="0" step="0.01" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="0.00">
                            </div>
                            <div>
                                <label class="text-slate-600">Weight (kg)</label>
                                <input id="profileWeightKg" name="weight_kg" type="number" min="0" step="0.01" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="0.00">
                            </div>
                            <div>
                                <label class="text-slate-600">Blood Type</label>
                                <div class="relative mt-1">
                                    <input id="profileBloodType" name="blood_type" type="text" autocomplete="off" data-modern-search="bloodType" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" placeholder="A+, O-, etc.">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Citizenship</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-slate-600">Citizenship</label>
                                <input id="profileCitizenship" name="citizenship" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Filipino">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Dual Citizenship Country</label>
                                <input id="profileDualCitizenshipCountry" name="dual_citizenship_country" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Leave empty if not eligible">
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Residential Address</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-slate-600">House/Block/Lot No.</label>
                                <input id="profileResidentialHouseNo" name="residential_house_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="House no.">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Street</label>
                                <input id="profileResidentialStreet" name="residential_street" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Street">
                            </div>
                            <div>
                                <label class="text-slate-600">Barangay</label>
                                <div class="relative mt-1">
                                    <input id="profileResidentialBarangay" name="residential_barangay" type="text" autocomplete="off" data-modern-search="barangay" data-address-group="residential" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" placeholder="Barangay">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                                </div>
                            </div>
                            <div>
                                <label class="text-slate-600">Subdivision/Village</label>
                                <input id="profileResidentialSubdivision" name="residential_subdivision" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Subdivision/Village">
                            </div>
                            <div>
                                <label class="text-slate-600">City/Municipality</label>
                                <div class="relative mt-1">
                                    <input id="profileResidentialCity" name="residential_city_municipality" type="text" autocomplete="off" data-modern-search="city" data-address-group="residential" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" placeholder="City/Municipality">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                                </div>
                            </div>
                            <div>
                                <label class="text-slate-600">Province</label>
                                <div class="relative mt-1">
                                    <input id="profileResidentialProvince" name="residential_province" type="text" autocomplete="off" data-modern-search="province" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" placeholder="Province">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                                </div>
                            </div>
                            <div>
                                <label class="text-slate-600">ZIP Code</label>
                                <div class="relative mt-1">
                                    <input id="profileResidentialZipCode" name="residential_zip_code" type="text" autocomplete="off" data-modern-search="zip" data-address-group="residential" inputmode="numeric" pattern="^\d{4}$" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" placeholder="ZIP code">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Permanent Address</h4>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input id="profileSameAsPermanentAddress" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Same as residential address</span>
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-slate-600">House/Block/Lot No.</label>
                                <input id="profilePermanentHouseNo" name="permanent_house_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="House no.">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Street</label>
                                <input id="profilePermanentStreet" name="permanent_street" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Street">
                            </div>
                            <div>
                                <label class="text-slate-600">Barangay</label>
                                <div class="relative mt-1">
                                    <input id="profilePermanentBarangay" name="permanent_barangay" type="text" autocomplete="off" data-modern-search="barangay" data-address-group="permanent" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" placeholder="Barangay">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                                </div>
                            </div>
                            <div>
                                <label class="text-slate-600">Subdivision/Village</label>
                                <input id="profilePermanentSubdivision" name="permanent_subdivision" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Subdivision/Village">
                            </div>
                            <div>
                                <label class="text-slate-600">City/Municipality</label>
                                <div class="relative mt-1">
                                    <input id="profilePermanentCity" name="permanent_city_municipality" type="text" autocomplete="off" data-modern-search="city" data-address-group="permanent" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" placeholder="City/Municipality">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                                </div>
                            </div>
                            <div>
                                <label class="text-slate-600">Province</label>
                                <div class="relative mt-1">
                                    <input id="profilePermanentProvince" name="permanent_province" type="text" autocomplete="off" data-modern-search="province" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" placeholder="Province">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                                </div>
                            </div>
                            <div>
                                <label class="text-slate-600">ZIP Code</label>
                                <div class="relative mt-1">
                                    <input id="profilePermanentZipCode" name="permanent_zip_code" type="text" autocomplete="off" data-modern-search="zip" data-address-group="permanent" inputmode="numeric" pattern="^\d{4}$" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" placeholder="ZIP code">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Government IDs</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-slate-600">UMID ID No.</label>
                                <input id="profileUmidNo" name="umid_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="UMID number">
                            </div>
                            <div>
                                <label class="text-slate-600">PAG-IBIG ID No.</label>
                                <input id="profilePagibigNo" name="pagibig_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="PAG-IBIG number">
                            </div>
                            <div>
                                <label class="text-slate-600">PHILHEALTH No.</label>
                                <input id="profilePhilhealthNo" name="philhealth_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="PhilHealth number">
                            </div>
                            <div>
                                <label class="text-slate-600">PhilSys Number (PSN)</label>
                                <input id="profilePsnNo" name="psn_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="PSN">
                            </div>
                            <div>
                                <label class="text-slate-600">TIN No.</label>
                                <input id="profileTinNo" name="tin_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="TIN number">
                            </div>
                            <div>
                                <label class="text-slate-600">Agency Employee No.</label>
                                <input id="profileAgencyEmployeeNo" name="agency_employee_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="DA-EMP-0001">
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Contact Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-slate-600">Telephone Number</label>
                                <input id="profileTelephoneNo" name="telephone_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter telephone number">
                            </div>
                            <div>
                                <label class="text-slate-600">Mobile Number</label>
                                <input id="profileMobile" name="mobile_no" type="text" pattern="^\+?[0-9][0-9\s-]{6,19}$" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter mobile number">
                            </div>
                            <div>
                                <label class="text-slate-600">Email Address</label>
                                <input id="profileEmail" name="email" type="email" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter email">
                            </div>
                        </div>
                    </section>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-modal-close="personalInfoProfileModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button id="personalInfoProfileSubmit" type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="personalInfoMergeModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoMergeModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Resolve Duplicate Employee Profile</h3>
                <button type="button" data-modal-close="personalInfoMergeModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Duplicate Profile (source)</label>
                    <input id="personalInfoMergeSourceLabel" type="text" class="w-full border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Keep Profile (target for merge)</label>
                    <select id="personalInfoMergeTargetSelect" class="w-full border border-slate-300 rounded-md px-3 py-2 bg-white">
                        <option value="">Select employee profile to keep...</option>
                        <?php foreach ($employeeTableRows as $mergeTargetRow): ?>
                            <option value="<?= htmlspecialchars((string)$mergeTargetRow['person_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$mergeTargetRow['full_name'] . ' • ' . (string)$mergeTargetRow['employee_code'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Resolution</label>
                    <select id="personalInfoMergeResolutionSelect" class="w-full border border-slate-300 rounded-md px-3 py-2 bg-white">
                        <option value="merge">Merge source data into target, then archive source</option>
                        <option value="delete">Archive duplicate source only (no merge)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                    <textarea id="personalInfoMergeNotesInput" rows="3" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="Reason for merge/delete duplicate"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" data-modal-close="personalInfoMergeModal" class="px-4 py-2 text-sm rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="button" id="personalInfoMergeSubmit" class="px-4 py-2 text-sm rounded-md bg-amber-600 text-white hover:bg-amber-700">Submit Resolution</button>
            </div>
        </div>
    </div>
</div>

<script id="adminPersonalInfoLookupData" type="application/json"><?= (string)json_encode([
    'placeOfBirthOptions' => $placeOfBirthOptions,
    'civilStatusOptions' => $civilStatusOptions,
    'bloodTypeOptions' => $bloodTypeOptions,
    'addressCityOptions' => $addressCityOptions,
    'addressProvinceOptions' => $addressProvinceOptions,
    'addressBarangayOptions' => $addressBarangayOptions,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<div id="personalInfoFamilyModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoFamilyModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-6xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="border-b border-slate-200">
                <div class="px-6 py-3 bg-slate-50/70 flex items-center gap-3">
                    <div class="w-full flex items-stretch">
                        <button type="button" data-pds-tab-target="section_i" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 bg-white">Personal Information</button>
                        <button type="button" data-pds-tab-target="section_ii" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-slate-900 text-slate-900 bg-white">Family Background</button>
                        <button type="button" data-pds-tab-target="section_iii" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 bg-white">Educational Background</button>
                    </div>
                    <button type="button" data-modal-close="personalInfoFamilyModal" class="text-slate-500 hover:text-slate-700">✕</button>
                </div>
                <p id="personalInfoFamilyEmployeeLabel" class="px-6 py-2 text-sm text-slate-500">Selected employee</p>
            </div>

            <form action="personal-information.php" method="POST" class="flex-1 min-h-0 flex flex-col" id="personalInfoFamilyForm">
                <input type="hidden" name="form_action" value="save_family_background">
                <input name="person_id" type="hidden" id="familyPersonId" value="">

                <div class="flex-1 min-h-0 overflow-y-auto p-6 space-y-6 text-sm">
                    <section class="space-y-3">
                        <h4 class="text-sm font-semibold text-slate-700">Spouse Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-slate-600">Spouse Surname</label>
                                <input id="familySpouseSurname" name="spouse_surname" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Surname">
                            </div>
                            <div>
                                <label class="text-slate-600">Spouse First Name</label>
                                <input id="familySpouseFirstName" name="spouse_first_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="First name">
                            </div>
                            <div>
                                <label class="text-slate-600">Spouse Middle Name</label>
                                <input id="familySpouseMiddleName" name="spouse_middle_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Middle name">
                            </div>
                            <div>
                                <label class="text-slate-600">Name Extension</label>
                                <input id="familySpouseExtensionName" name="spouse_extension_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Jr., Sr.">
                            </div>
                            <div>
                                <label class="text-slate-600">Occupation</label>
                                <input id="familySpouseOccupation" name="spouse_occupation" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Occupation">
                            </div>
                            <div>
                                <label class="text-slate-600">Employer/Business Name</label>
                                <input id="familySpouseEmployerBusinessName" name="spouse_employer_business_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Employer/Business name">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Business Address</label>
                                <input id="familySpouseBusinessAddress" name="spouse_business_address" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Business address">
                            </div>
                            <div>
                                <label class="text-slate-600">Telephone No.</label>
                                <input id="familySpouseTelephoneNo" name="spouse_telephone_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Telephone number">
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Children</h4>
                        <div class="flex justify-end">
                            <button type="button" id="personalInfoFamilyAddChildButton" class="px-3 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Add Child</button>
                        </div>
                        <div class="overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-sm" id="personalInfoFamilyChildrenTable">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="text-left px-3 py-2">Full Name</th>
                                        <th class="text-left px-3 py-2">Date of Birth</th>
                                        <th class="text-left px-3 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="personalInfoFamilyChildrenTableBody">
                                    <tr>
                                        <td class="px-3 py-2"><input name="children_full_name[]" type="text" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="Child full name"></td>
                                        <td class="px-3 py-2"><input name="children_birth_date[]" type="date" class="w-full border border-slate-300 rounded-md px-3 py-2"></td>
                                        <td class="px-3 py-2"><button type="button" data-family-child-remove class="px-2.5 py-1 rounded-md border border-rose-200 text-rose-700 hover:bg-rose-50">Remove</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <template id="personalInfoFamilyChildRowTemplate">
                            <tr>
                                <td class="px-3 py-2"><input name="children_full_name[]" type="text" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="Child full name"></td>
                                <td class="px-3 py-2"><input name="children_birth_date[]" type="date" class="w-full border border-slate-300 rounded-md px-3 py-2"></td>
                                <td class="px-3 py-2"><button type="button" data-family-child-remove class="px-2.5 py-1 rounded-md border border-rose-200 text-rose-700 hover:bg-rose-50">Remove</button></td>
                            </tr>
                        </template>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Father's Name</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-slate-600">Surname</label>
                                <input id="familyFatherSurname" name="father_surname" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Surname">
                            </div>
                            <div>
                                <label class="text-slate-600">First Name</label>
                                <input id="familyFatherFirstName" name="father_first_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="First name">
                            </div>
                            <div>
                                <label class="text-slate-600">Middle Name</label>
                                <input id="familyFatherMiddleName" name="father_middle_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Middle name">
                            </div>
                            <div>
                                <label class="text-slate-600">Name Extension</label>
                                <input id="familyFatherExtensionName" name="father_extension_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Jr., Sr.">
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3 border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-700">Mother's Maiden Name</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-slate-600">Surname</label>
                                <input id="familyMotherSurname" name="mother_surname" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Surname">
                            </div>
                            <div>
                                <label class="text-slate-600">First Name</label>
                                <input id="familyMotherFirstName" name="mother_first_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="First name">
                            </div>
                            <div>
                                <label class="text-slate-600">Middle Name</label>
                                <input id="familyMotherMiddleName" name="mother_middle_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Middle name">
                            </div>
                            <div>
                                <label class="text-slate-600">Name Extension</label>
                                <input id="familyMotherExtensionName" name="mother_extension_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Jr., Sr.">
                            </div>
                        </div>
                    </section>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-modal-close="personalInfoFamilyModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button id="personalInfoFamilySubmit" type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Section II</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="personalInfoEducationModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoEducationModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-7xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="border-b border-slate-200">
                <div class="px-6 py-3 bg-slate-50/70 flex items-center gap-3">
                    <div class="w-full flex items-stretch">
                        <button type="button" data-pds-tab-target="section_i" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 bg-white">Personal Information</button>
                        <button type="button" data-pds-tab-target="section_ii" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 bg-white">Family Background</button>
                        <button type="button" data-pds-tab-target="section_iii" class="flex-1 px-4 py-2.5 text-sm font-medium text-center border-b-2 border-slate-900 text-slate-900 bg-white">Educational Background</button>
                    </div>
                    <button type="button" data-modal-close="personalInfoEducationModal" class="text-slate-500 hover:text-slate-700">✕</button>
                </div>
                <p id="personalInfoEducationEmployeeLabel" class="px-6 py-2 text-sm text-slate-500">Selected employee</p>
            </div>

            <form id="personalInfoEducationForm" action="personal-information.php" method="POST" class="flex-1 min-h-0 flex flex-col">
                <input type="hidden" name="form_action" value="save_educational_background">
                <input type="hidden" name="person_id" id="educationPersonId" value="">

                <div class="flex-1 min-h-0 overflow-y-auto p-6 text-sm space-y-3">
                    <p class="text-xs text-slate-500">Complete each level row as applicable. Leave fields blank when not applicable.</p>
                    <div class="overflow-x-auto border border-slate-200 rounded-lg">
                        <table class="w-full text-sm min-w-[1180px]">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr class="border-b border-slate-200">
                                    <th class="text-left px-3 py-2 w-[180px]" rowspan="2">Level</th>
                                    <th class="text-left px-3 py-2" colspan="2">Institution & Program</th>
                                    <th class="text-left px-3 py-2" colspan="2">Period of Attendance</th>
                                    <th class="text-left px-3 py-2" colspan="3">Completion Details</th>
                                </tr>
                                <tr>
                                    <th class="text-left px-3 py-2 min-w-[180px]">School Name</th>
                                    <th class="text-left px-3 py-2 min-w-[180px]">Degree/Course</th>
                                    <th class="text-left px-3 py-2 w-[100px]">From</th>
                                    <th class="text-left px-3 py-2 w-[100px]">To</th>
                                    <th class="text-left px-3 py-2 min-w-[180px]">Highest Level / Units Earned</th>
                                    <th class="text-left px-3 py-2 w-[120px]">Year Graduated</th>
                                    <th class="text-left px-3 py-2 min-w-[200px]">Scholarship / Academic Honors</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php
                                $educationLevels = [
                                    'elementary' => 'Elementary',
                                    'secondary' => 'Secondary',
                                    'vocational_trade_course' => 'Vocational / Trade Course',
                                    'college' => 'College',
                                    'graduate_studies' => 'Graduate Studies',
                                ];
                                ?>
                                <?php foreach ($educationLevels as $educationLevelKey => $educationLevelLabel): ?>
                                    <tr class="align-top">
                                        <td class="px-3 py-3 font-medium text-slate-700 bg-slate-50/60">
                                            <?= htmlspecialchars($educationLevelLabel, ENT_QUOTES, 'UTF-8') ?>
                                            <input type="hidden" name="education_level[]" value="<?= htmlspecialchars($educationLevelKey, ENT_QUOTES, 'UTF-8') ?>">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="text" name="school_name[]" data-education-level="<?= htmlspecialchars($educationLevelKey, ENT_QUOTES, 'UTF-8') ?>" data-education-field="school_name" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="Write in full">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="text" name="degree_course[]" data-education-level="<?= htmlspecialchars($educationLevelKey, ENT_QUOTES, 'UTF-8') ?>" data-education-field="degree_course" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="Write in full">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="number" name="attendance_from_year[]" data-education-level="<?= htmlspecialchars($educationLevelKey, ENT_QUOTES, 'UTF-8') ?>" data-education-field="attendance_from_year" min="1900" max="2100" step="1" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="YYYY">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="number" name="attendance_to_year[]" data-education-level="<?= htmlspecialchars($educationLevelKey, ENT_QUOTES, 'UTF-8') ?>" data-education-field="attendance_to_year" min="1900" max="2100" step="1" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="YYYY">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="text" name="highest_level_units_earned[]" data-education-level="<?= htmlspecialchars($educationLevelKey, ENT_QUOTES, 'UTF-8') ?>" data-education-field="highest_level_units_earned" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="If not graduated">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="number" name="year_graduated[]" data-education-level="<?= htmlspecialchars($educationLevelKey, ENT_QUOTES, 'UTF-8') ?>" data-education-field="year_graduated" min="1900" max="2100" step="1" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="YYYY">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <input type="text" name="scholarship_honors_received[]" data-education-level="<?= htmlspecialchars($educationLevelKey, ENT_QUOTES, 'UTF-8') ?>" data-education-field="scholarship_honors_received" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="Scholarship / Honors">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-modal-close="personalInfoEducationModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Section III</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="personalInfoEligibilityModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoEligibilityModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-4xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Civil Service Eligibility</h3>
                    <p id="personalInfoEligibilityEmployeeLabel" class="text-sm text-slate-500 mt-1">Selected employee</p>
                </div>
                <button type="button" data-modal-close="personalInfoEligibilityModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <form id="personalInfoEligibilityForm" action="personal-information.php" method="POST" class="flex-1 min-h-0 flex flex-col">
                <input type="hidden" name="form_action" value="save_civil_service_eligibility">
                <input type="hidden" name="eligibility_action" id="personalInfoEligibilityAction" value="add">
                <input type="hidden" name="person_id" id="personalInfoEligibilityPersonId" value="">
                <input type="hidden" name="eligibility_id" id="personalInfoEligibilityId" value="">

                <div class="flex-1 min-h-0 overflow-y-auto p-6 space-y-6 text-sm">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="text-slate-600">Career Service / RA 1080 / Board / Other</label>
                            <input id="personalInfoEligibilityName" name="eligibility_name" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter eligibility name" required>
                        </div>
                        <div>
                            <label class="text-slate-600">Rating</label>
                            <input id="personalInfoEligibilityRating" name="rating" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="e.g. 83.50%">
                        </div>
                        <div>
                            <label class="text-slate-600">Date of Examination</label>
                            <input id="personalInfoEligibilityExamDate" name="exam_date" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="text-slate-600">Place of Examination</label>
                            <input id="personalInfoEligibilityExamPlace" name="exam_place" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="City / Province">
                        </div>
                        <div>
                            <label class="text-slate-600">License Number</label>
                            <input id="personalInfoEligibilityLicenseNo" name="license_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter license number">
                        </div>
                        <div>
                            <label class="text-slate-600">License Validity</label>
                            <input id="personalInfoEligibilityLicenseValidity" name="license_validity" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="text-slate-600">Sequence</label>
                            <input id="personalInfoEligibilitySequence" name="sequence_no" type="number" min="1" step="1" value="1" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-slate-700">Saved Eligibility Records</h4>
                            <button type="button" id="personalInfoEligibilityResetButton" class="px-3 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Clear Form</button>
                        </div>
                        <div class="overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-sm" id="personalInfoEligibilityTable">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="text-left px-3 py-2">Eligibility</th>
                                        <th class="text-left px-3 py-2">Exam Date</th>
                                        <th class="text-left px-3 py-2">License</th>
                                        <th class="text-left px-3 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="personalInfoEligibilityTableBody">
                                    <tr>
                                        <td colspan="4" class="px-3 py-3 text-slate-500">No records loaded.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-modal-close="personalInfoEligibilityModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" id="personalInfoEligibilitySubmit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Eligibility</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="personalInfoWorkExperienceModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoWorkExperienceModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-5xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Work Experience</h3>
                    <p id="personalInfoWorkExperienceEmployeeLabel" class="text-sm text-slate-500 mt-1">Selected employee</p>
                </div>
                <button type="button" data-modal-close="personalInfoWorkExperienceModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <form id="personalInfoWorkExperienceForm" action="personal-information.php" method="POST" class="flex-1 min-h-0 flex flex-col">
                <input type="hidden" name="form_action" value="save_work_experience">
                <input type="hidden" name="work_experience_action" id="personalInfoWorkExperienceAction" value="add">
                <input type="hidden" name="person_id" id="personalInfoWorkExperiencePersonId" value="">
                <input type="hidden" name="work_experience_id" id="personalInfoWorkExperienceId" value="">

                <div class="flex-1 min-h-0 overflow-y-auto p-6 space-y-6 text-sm">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-slate-600">Inclusive Date (From)</label>
                            <input id="personalInfoWorkExperienceDateFrom" name="inclusive_date_from" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        </div>
                        <div>
                            <label class="text-slate-600">Inclusive Date (To)</label>
                            <input id="personalInfoWorkExperienceDateTo" name="inclusive_date_to" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="text-slate-600">Position Title</label>
                            <input id="personalInfoWorkExperiencePosition" name="position_title" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter position title" required>
                        </div>
                        <div>
                            <label class="text-slate-600">Office / Company</label>
                            <input id="personalInfoWorkExperienceOffice" name="office_company" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter office/company" required>
                        </div>
                        <div>
                            <label class="text-slate-600">Monthly Salary</label>
                            <input id="personalInfoWorkExperienceSalary" name="monthly_salary" type="number" min="0" step="0.01" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="0.00">
                        </div>
                        <div>
                            <label class="text-slate-600">Salary Grade / Step</label>
                            <input id="personalInfoWorkExperienceSalaryGrade" name="salary_grade_step" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="SG-11 / Step 1">
                        </div>
                        <div>
                            <label class="text-slate-600">Appointment Status</label>
                            <input id="personalInfoWorkExperienceAppointmentStatus" name="appointment_status" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Permanent / Contractual">
                        </div>
                        <div>
                            <label class="text-slate-600">Government Service</label>
                            <select id="personalInfoWorkExperienceGovernment" name="is_government_service" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                                <option value="">Not Specified</option>
                                <option value="true">Yes</option>
                                <option value="false">No</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-slate-600">Sequence</label>
                            <input id="personalInfoWorkExperienceSequence" name="sequence_no" type="number" min="1" step="1" value="1" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                        </div>
                        <div class="md:col-span-3">
                            <label class="text-slate-600">Separation Reason</label>
                            <input id="personalInfoWorkExperienceSeparationReason" name="separation_reason" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter separation reason (if any)">
                        </div>
                        <div class="md:col-span-3">
                            <label class="text-slate-600">Achievements</label>
                            <textarea id="personalInfoWorkExperienceAchievements" name="achievements" rows="2" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Key achievements"></textarea>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-slate-700">Saved Work Experience Records</h4>
                            <button type="button" id="personalInfoWorkExperienceResetButton" class="px-3 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Clear Form</button>
                        </div>
                        <div class="overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-sm" id="personalInfoWorkExperienceTable">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="text-left px-3 py-2">Position</th>
                                        <th class="text-left px-3 py-2">Inclusive Dates</th>
                                        <th class="text-left px-3 py-2">Office / Salary</th>
                                        <th class="text-left px-3 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="personalInfoWorkExperienceTableBody">
                                    <tr>
                                        <td colspan="4" class="px-3 py-3 text-slate-500">No records loaded.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-modal-close="personalInfoWorkExperienceModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" id="personalInfoWorkExperienceSubmit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Work Experience</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="personalInfoAssignmentModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoAssignmentModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Assign Division and Position</h3>
                <button type="button" data-modal-close="personalInfoAssignmentModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="personal-information.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <input type="hidden" name="form_action" value="assign_department_position">
                <input type="hidden" id="personalInfoAssignmentPersonId" name="person_id" value="">
                <div>
                    <label class="text-slate-600">Employee</label>
                    <input id="personalInfoAssignmentEmployeeDisplay" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Division</label>
                    <select name="office_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="">Select division</option>
                        <?php foreach ($officeRows as $office): ?>
                            <option value="<?= htmlspecialchars((string)($office['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($office['office_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Position</label>
                    <select name="position_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="">Select position</option>
                        <?php foreach ($positionRows as $position): ?>
                            <option value="<?= htmlspecialchars((string)($position['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($position['position_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-3 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="personalInfoAssignmentModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="personalInfoStatusModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="personalInfoStatusModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Manage Employee Status</h3>
                <button type="button" data-modal-close="personalInfoStatusModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="personal-information.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="update_employee_status">
                <input type="hidden" id="personalInfoStatusPersonId" name="person_id" value="">
                <div>
                    <label class="text-slate-600">Employee</label>
                    <input id="personalInfoStatusEmployeeDisplay" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">New Status</label>
                    <select name="new_status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Status Specification</label>
                    <textarea name="status_specification" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Indicate reason (resigned, retired, on leave, reassigned, etc.)"></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="personalInfoStatusModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
