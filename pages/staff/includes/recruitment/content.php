<?php
$recruitmentContentSection = (string)($recruitmentContentSection ?? 'full');
$renderSummary = in_array($recruitmentContentSection, ['full', 'summary'], true);
$renderListings = in_array($recruitmentContentSection, ['full', 'listings'], true);
$renderSecondary = in_array($recruitmentContentSection, ['full', 'secondary'], true);
?>

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($renderSummary): ?>
    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[120px]">
            <p class="text-xs uppercase tracking-wide text-slate-500">Active Postings</p>
            <p class="mt-3 text-2xl font-bold text-slate-800"><?= (int)($recruitmentSummary['active_postings'] ?? 0) ?></p>
            <p class="mt-2 text-xs text-slate-500">Visible to staff in the current scope.</p>
        </article>
        <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[120px]">
            <p class="text-xs uppercase tracking-wide text-slate-500">Published Postings</p>
            <p class="mt-3 text-2xl font-bold text-slate-800"><?= (int)($recruitmentSummary['published_postings'] ?? 0) ?></p>
            <p class="mt-2 text-xs text-emerald-700">Open postings currently accepting applicants.</p>
        </article>
        <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[120px]">
            <p class="text-xs uppercase tracking-wide text-slate-500">Pending Applications</p>
            <p class="mt-3 text-2xl font-bold text-slate-800"><?= (int)($recruitmentSummary['pending_applications'] ?? 0) ?></p>
            <p class="mt-2 text-xs text-amber-700">Submitted through offer stage across active postings.</p>
        </article>
        <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[120px]">
            <p class="text-xs uppercase tracking-wide text-slate-500">Upcoming Deadlines</p>
            <p class="mt-3 text-2xl font-bold text-slate-800"><?= (int)($recruitmentSummary['upcoming_deadlines'] ?? 0) ?></p>
            <p class="mt-2 text-xs text-rose-700">Deadlines due in the next 7 days.</p>
        </article>
    </section>
<?php endif; ?>

<?php if ($renderListings): ?>
    <section class="bg-white border rounded-xl mb-6">
        <header class="px-6 py-4 border-b flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Job Listings</h2>
                <p class="text-sm text-gray-500 mt-1">Active admin-posted job listings shown in read-only view.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" data-recruitment-refresh="listings" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    <span class="material-symbols-outlined text-base">refresh</span>
                    Refresh Table
                </button>
            </div>
        </header>

        <form id="recruitmentListingsFilters" class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3" autocomplete="off">
            <input id="recruitmentPageInput" type="hidden" name="recruitment_page" value="<?= (int)($recruitmentPagination['page'] ?? 1) ?>">
            <div class="md:col-span-2">
                <label for="recruitmentSearchInput" class="text-sm text-gray-600">Search Requests</label>
                <input id="recruitmentSearchInput" name="recruitment_search" type="search" value="<?= htmlspecialchars((string)($recruitmentPagination['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by posting title, division, position, or status">
            </div>
            <div>
                <label for="recruitmentStatusFilter" class="text-sm text-gray-600">All Statuses</label>
                <select id="recruitmentStatusFilter" name="recruitment_status" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                    <option value="">All Statuses</option>
                    <?php foreach ($activeRecruitmentStatusOptions as $statusValue => $statusLabel): ?>
                        <option value="<?= htmlspecialchars((string)$statusValue, ENT_QUOTES, 'UTF-8') ?>" <?= strtolower((string)($recruitmentPagination['status'] ?? '')) === strtolower((string)$statusValue) ? 'selected' : '' ?>><?= htmlspecialchars((string)$statusLabel, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <div class="p-6 overflow-x-auto">
            <table id="recruitmentTable" class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-4 py-3">Position</th>
                        <th class="text-left px-4 py-3">Division</th>
                        <th class="text-left px-4 py-3">Employment Type</th>
                        <th class="text-left px-4 py-3">Open Date</th>
                        <th class="text-left px-4 py-3">Deadline</th>
                        <th class="text-left px-4 py-3">Applications</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($activeRecruitmentRows)): ?>
                        <tr>
                            <td class="px-4 py-3 text-gray-500" colspan="8"><?= htmlspecialchars((string)((($recruitmentPagination['search'] ?? '') !== '' || ($recruitmentPagination['status'] ?? '') !== '') ? 'No active job listings match your current search/filter criteria.' : 'No active admin-posted job listings found.'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activeRecruitmentRows as $row): ?>
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['position_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                </td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($row['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($row['employment_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($row['open_date_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($row['close_date_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3">
                                    <p><?= (int)($row['applications_total'] ?? 0) ?> total</p>
                                    <p class="text-xs text-amber-700 mt-1"><?= (int)($row['applications_pending'] ?? 0) ?> pending</p>
                                </td>
                                <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="px-4 py-3">
                                    <button type="button" data-open-posting-view-modal data-posting-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        <span class="material-symbols-outlined text-base">visibility</span>
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="px-6 pb-6 flex items-center justify-between gap-3 text-sm text-slate-600">
            <p id="recruitmentPaginationInfo">Showing <?= (int)($recruitmentPagination['from'] ?? 0) ?> to <?= (int)($recruitmentPagination['to'] ?? 0) ?> of <?= (int)($recruitmentPagination['total_rows'] ?? 0) ?> entries</p>
            <div class="flex items-center gap-2">
                <button type="button" id="recruitmentPrevPage" data-recruitment-page="<?= (int)($recruitmentPagination['prev_page'] ?? 1) ?>" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" <?= !empty($recruitmentPagination['has_prev']) ? '' : 'disabled' ?>>Previous</button>
                <p id="recruitmentPageLabel" class="text-xs text-slate-500">Page <?= (int)($recruitmentPagination['page'] ?? 1) ?> of <?= (int)($recruitmentPagination['total_pages'] ?? 1) ?></p>
                <button type="button" id="recruitmentNextPage" data-recruitment-page="<?= (int)($recruitmentPagination['next_page'] ?? 1) ?>" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" <?= !empty($recruitmentPagination['has_next']) ? '' : 'disabled' ?>>Next</button>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($renderSecondary): ?>
    <section class="bg-slate-50 border border-slate-300 rounded-xl mb-6">
        <header class="px-6 py-4 border-b border-slate-300 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Archived Job Postings</h2>
                <p class="text-sm text-slate-600 mt-1">Archived postings are separated from active listings.</p>
            </div>
            <button type="button" data-recruitment-refresh="secondary" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                <span class="material-symbols-outlined text-base">refresh</span>
                Refresh Table
            </button>
        </header>

        <div class="p-6 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="text-left px-4 py-3">Position</th>
                        <th class="text-left px-4 py-3">Division</th>
                        <th class="text-left px-4 py-3">Employment Type</th>
                        <th class="text-left px-4 py-3">Archived Deadline</th>
                        <th class="text-left px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($archivedRecruitmentRows)): ?>
                        <tr>
                            <td class="px-4 py-3 text-slate-500" colspan="5">No archived job postings found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($archivedRecruitmentRows as $row): ?>
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-800"><?= htmlspecialchars((string)($row['position_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)($row['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                </td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($row['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($row['employment_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($row['close_date_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-200 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Archived'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="bg-white border rounded-xl mb-6">
        <header class="px-6 py-4 border-b flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">View Application Deadlines</h2>
                <p class="text-sm text-gray-500 mt-1">Track active job postings and prioritize upcoming application deadlines.</p>
            </div>
            <button type="button" data-recruitment-refresh="secondary" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                <span class="material-symbols-outlined text-base">refresh</span>
                Refresh Table
            </button>
        </header>

        <div class="p-6 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-4 py-3">Job Posting</th>
                        <th class="text-left px-4 py-3">Division</th>
                        <th class="text-left px-4 py-3">Deadline</th>
                        <th class="text-left px-4 py-3">Days Remaining</th>
                        <th class="text-left px-4 py-3">Priority</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($applicationDeadlineRows)): ?>
                        <tr>
                            <td class="px-4 py-3 text-gray-500" colspan="5">No active posting deadlines found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applicationDeadlineRows as $row): ?>
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['position_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                </td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($row['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($row['close_date_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= (int)($row['days_remaining'] ?? 0) ?> day(s)</td>
                                <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['priority_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['priority_label'] ?? 'Scheduled'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>