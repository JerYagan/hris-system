<?php if (($registrationContentSection ?? 'list') === 'list'): ?>
<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">View Registered Applicants</h2>
        <p class="text-sm text-gray-500 mt-1">Review applicant intake records one page at a time, then open profile and document detail only when needed.</p>
    </header>

    <?php if (!empty($dataLoadError)): ?>
        <div class="mx-6 mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="registrationSearchInput" class="text-sm text-gray-600">Search Applicants</label>
            <input id="registrationSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by applicant, email, or position" value="<?= htmlspecialchars((string)($registrationFilters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
            <label for="registrationStatusFilter" class="text-sm text-gray-600">Screening Filter</label>
            <select id="registrationStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <?php $registrationStatusValue = strtolower((string)($registrationFilters['status'] ?? '')); ?>
                <option value="" <?= $registrationStatusValue === '' ? 'selected' : '' ?>>All</option>
                <option value="submitted" <?= $registrationStatusValue === 'submitted' ? 'selected' : '' ?>>Applied</option>
                <option value="screening" <?= $registrationStatusValue === 'screening' ? 'selected' : '' ?>>Verified</option>
                <option value="interview" <?= $registrationStatusValue === 'interview' ? 'selected' : '' ?>>Interview</option>
                <option value="shortlisted" <?= $registrationStatusValue === 'shortlisted' ? 'selected' : '' ?>>Evaluation</option>
                <option value="offer" <?= $registrationStatusValue === 'offer' ? 'selected' : '' ?>>For Approval</option>
                <option value="hired" <?= $registrationStatusValue === 'hired' ? 'selected' : '' ?>>Hired</option>
                <option value="rejected" <?= $registrationStatusValue === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="registrationTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Applied Position</th>
                    <th class="text-left px-4 py-3">Date Submitted</th>
                    <th class="text-left px-4 py-3">Initial Screening</th>
                    <th class="text-left px-4 py-3">Basis</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($registrationRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="6">No registration records found in your division scope.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registrationRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['applicant_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['applicant_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['posting_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['submitted_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['basis'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-open-registration-modal
                                    data-application-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"
                                >
                                    <span class="material-symbols-outlined text-[16px]">person_search</span>View Profile
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="registrationPaginationInfo" class="text-xs text-slate-500"><?= htmlspecialchars((string)$registrationPaginationLabel, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="flex items-center gap-2">
            <button type="button" id="registrationPrevPage" data-registration-page="<?= $registrationHasPreviousPage ? (int)($registrationCurrentPage - 1) : 0 ?>" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 <?= $registrationHasPreviousPage ? '' : 'opacity-60 cursor-not-allowed' ?>" <?= $registrationHasPreviousPage ? '' : 'disabled' ?>>Previous</button>
            <button type="button" id="registrationNextPage" data-registration-page="<?= $registrationHasNextPage ? (int)($registrationCurrentPage + 1) : 0 ?>" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 <?= $registrationHasNextPage ? '' : 'opacity-60 cursor-not-allowed' ?>" <?= $registrationHasNextPage ? '' : 'disabled' ?>>Next</button>
        </div>
    </div>
</section>
<?php endif; ?>