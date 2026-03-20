<?php if (($trackingContentSection ?? 'postings') === 'postings'): ?>
<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Posting Queue</h2>
            <p class="text-sm text-gray-500 mt-1">Load a posting frame first, then narrow the applicant list to that posting only when you choose it.</p>
        </div>
        <button type="button" data-tracking-refresh="postings" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
            <span class="material-symbols-outlined text-base">refresh</span>
            Refresh Postings
        </button>
    </header>

    <?php if (!empty($dataLoadError)): ?>
        <div class="mx-6 mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Posting</th>
                    <th class="text-left px-4 py-3">Division</th>
                    <th class="text-left px-4 py-3">Open</th>
                    <th class="text-left px-4 py-3">Deadline</th>
                    <th class="text-left px-4 py-3">Applicants</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($trackingPostingRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="6">No active applicant-tracking postings were found in your scope.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($trackingPostingRows as $row): ?>
                        <tr class="<?= !empty($row['is_selected']) ? 'bg-emerald-50' : '' ?>">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['position_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['open_date_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['close_date_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <p><?= (int)($row['applications_total'] ?? 0) ?> total</p>
                                <p class="text-xs text-amber-700 mt-1"><?= (int)($row['applications_active'] ?? 0) ?> active</p>
                            </td>
                            <td class="px-4 py-3">
                                <button type="button" data-tracking-posting-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium <?= !empty($row['is_selected']) ? 'border-emerald-300 text-emerald-700 bg-emerald-50' : 'text-slate-700 hover:bg-slate-50' ?>">
                                    <span class="material-symbols-outlined text-base">list_alt</span>
                                    <?= !empty($row['is_selected']) ? 'Selected' : 'View Applicants' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="px-6 pb-6 flex items-center justify-between gap-3 text-sm text-slate-600">
        <p><?= htmlspecialchars((string)($trackingPostingPagination['label'] ?? 'Page 1'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="flex items-center gap-2">
            <button type="button" data-tracking-postings-page="<?= (int)($trackingPostingPagination['prev_page'] ?? 1) ?>" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" <?= !empty($trackingPostingPagination['has_prev']) ? '' : 'disabled' ?>>Previous</button>
            <button type="button" data-tracking-postings-page="<?= (int)($trackingPostingPagination['next_page'] ?? 1) ?>" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" <?= !empty($trackingPostingPagination['has_next']) ? '' : 'disabled' ?>>Next</button>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (($trackingContentSection ?? 'applicants') === 'applicants'): ?>
<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Applicant List</h2>
            <p class="text-sm text-gray-500 mt-1">Render the queue first. Documents, interview history, and feedback stay deferred until a profile is opened.</p>
        </div>
        <div class="flex items-center gap-2">
            <?php if (!empty($trackingApplicantFilters['posting_id'])): ?>
                <button type="button" id="trackingClearPostingFilter" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-100">
                    <span class="material-symbols-outlined text-base">close</span>
                    <?= htmlspecialchars((string)$trackingSelectedPostingTitle, ENT_QUOTES, 'UTF-8') ?>
                </button>
            <?php endif; ?>
            <button type="button" data-tracking-refresh="applicants" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                <span class="material-symbols-outlined text-base">refresh</span>
                Refresh Applicants
            </button>
        </div>
    </header>

    <?php if (!empty($dataLoadError)): ?>
        <div class="mx-6 mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form id="trackingApplicantsFilters" class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3" autocomplete="off">
        <input id="trackingApplicantsPageInput" type="hidden" name="tracking_page" value="<?= (int)($trackingApplicantPagination['page'] ?? 1) ?>">
        <input id="trackingApplicantsPostingInput" type="hidden" name="posting_id" value="<?= htmlspecialchars((string)($trackingApplicantFilters['posting_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <div class="md:col-span-2">
            <label for="trackingApplicantsSearchInput" class="text-sm text-gray-600">Search Applicants</label>
            <input id="trackingApplicantsSearchInput" name="search" type="search" value="<?= htmlspecialchars((string)($trackingApplicantFilters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search applicant, email, posting, or stage">
        </div>
        <div>
            <label for="trackingApplicantsStatusFilter" class="text-sm text-gray-600">Status Filter</label>
            <select id="trackingApplicantsStatusFilter" name="status" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <?php $trackingApplicantStatus = strtolower((string)($trackingApplicantFilters['status'] ?? '')); ?>
                <option value="" <?= $trackingApplicantStatus === '' ? 'selected' : '' ?>>All Statuses</option>
                <option value="submitted" <?= $trackingApplicantStatus === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                <option value="screening" <?= $trackingApplicantStatus === 'screening' ? 'selected' : '' ?>>Screening</option>
                <option value="shortlisted" <?= $trackingApplicantStatus === 'shortlisted' ? 'selected' : '' ?>>Shortlisted</option>
                <option value="interview" <?= $trackingApplicantStatus === 'interview' ? 'selected' : '' ?>>Interview</option>
                <option value="offer" <?= $trackingApplicantStatus === 'offer' ? 'selected' : '' ?>>Offer</option>
                <option value="hired" <?= $trackingApplicantStatus === 'hired' ? 'selected' : '' ?>>Hired</option>
                <option value="rejected" <?= $trackingApplicantStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="withdrawn" <?= $trackingApplicantStatus === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
            </select>
        </div>
    </form>

    <div class="px-6 pb-2">
        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
            Staff tracking is read-only. Add-as-employee and other follow-up actions stay outside this first-load applicant queue.
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Posting</th>
                    <th class="text-left px-4 py-3">Submitted</th>
                    <th class="text-left px-4 py-3">Current Stage</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($trackingApplicantRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="6">No applicant-tracking rows match the current scope and filters.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($trackingApplicantRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['applicant_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['applicant_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['posting_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['submitted_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700"><?= htmlspecialchars((string)($row['current_stage_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <button type="button" data-open-tracking-profile data-application-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                    <span class="material-symbols-outlined text-base">person_search</span>
                                    View Profile
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="px-6 pb-6 flex items-center justify-between gap-3 text-sm text-slate-600">
        <p><?= htmlspecialchars((string)($trackingApplicantPagination['label'] ?? 'Page 1'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="flex items-center gap-2">
            <button type="button" data-tracking-applicants-page="<?= (int)($trackingApplicantPagination['prev_page'] ?? 1) ?>" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" <?= !empty($trackingApplicantPagination['has_prev']) ? '' : 'disabled' ?>>Previous</button>
            <button type="button" data-tracking-applicants-page="<?= (int)($trackingApplicantPagination['next_page'] ?? 1) ?>" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" <?= !empty($trackingApplicantPagination['has_next']) ? '' : 'disabled' ?>>Next</button>
        </div>
    </div>
</section>
<?php endif; ?>