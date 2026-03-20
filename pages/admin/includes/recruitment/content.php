<?php
$recruitmentContentSection = (string)($recruitmentContentSection ?? 'secondary');
?>

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($recruitmentContentSection === 'summary'): ?>
    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[120px]">
            <p class="text-xs uppercase tracking-wide text-slate-500">Active Postings</p>
            <p class="mt-3 text-2xl font-bold text-slate-800"><?= (int)($recruitmentSummary['active_postings'] ?? 0) ?></p>
            <p class="mt-2 text-xs text-slate-500">Hiring records still active in operations.</p>
        </article>
        <article class="bg-white border border-slate-200 rounded-xl p-4 min-h-[120px]">
            <p class="text-xs uppercase tracking-wide text-slate-500">Open Postings</p>
            <p class="mt-3 text-2xl font-bold text-slate-800"><?= (int)($recruitmentSummary['published_postings'] ?? 0) ?></p>
            <p class="mt-2 text-xs text-emerald-700">Published roles currently accepting applicants.</p>
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
<?php elseif ($recruitmentContentSection === 'listings'): ?>
    <section class="bg-white border border-slate-200 rounded-2xl mb-6">
        <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Job Listings</h2>
                <p class="text-sm text-slate-500 mt-1">Load the active postings table first and defer deeper applicant payloads to the secondary section.</p>
            </div>
            <button type="button" data-recruitment-secondary-action data-modal-open="recruitmentCreateJobModal" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed" disabled aria-disabled="true">New Job</button>
        </header>

        <div class="px-6 py-4 text-xs text-slate-600 bg-slate-50 border-b border-slate-200">
            <span class="font-semibold text-slate-700">Status guide:</span>
            Closed = posting no longer accepts applicants but is still an active record.
            Archived = posting is moved to historical records only.
        </div>

        <form id="recruitmentListingsFilters" class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3" autocomplete="off">
            <input id="recruitmentPageInput" type="hidden" name="recruitment_page" value="<?= (int)($recruitmentPagination['page'] ?? 1) ?>">
            <div class="md:col-span-2">
                <label for="recruitmentSearchInput" class="text-sm text-slate-600">Search Job Listings</label>
                <input id="recruitmentSearchInput" name="recruitment_search" type="search" value="<?= htmlspecialchars((string)($recruitmentPagination['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by position, division, employment type, or status">
            </div>
            <div>
                <label for="recruitmentStatusFilter" class="text-sm text-slate-600">Status</label>
                <select id="recruitmentStatusFilter" name="recruitment_status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Statuses</option>
                    <?php foreach ($activeRecruitmentStatusOptions as $statusValue => $statusLabel): ?>
                        <option value="<?= htmlspecialchars((string)$statusValue, ENT_QUOTES, 'UTF-8') ?>" <?= strtolower((string)($recruitmentPagination['status'] ?? '')) === strtolower((string)$statusValue) ? 'selected' : '' ?>><?= htmlspecialchars((string)$statusLabel, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <div class="p-6 overflow-x-auto">
            <table id="recruitmentPostingsTable" class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Position</th>
                        <th class="text-left px-4 py-3">Plantilla Number</th>
                        <th class="text-left px-4 py-3">Division</th>
                        <th class="text-left px-4 py-3">Employment Type</th>
                        <th class="text-left px-4 py-3">Applicants</th>
                        <th class="text-left px-4 py-3">Deadline</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Last Updated</th>
                        <th class="text-left px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($activeRecruitmentRows)): ?>
                        <tr>
                            <td class="px-4 py-3 text-slate-500" colspan="9"><?= htmlspecialchars((string)((($recruitmentPagination['search'] ?? '') !== '' || ($recruitmentPagination['status'] ?? '') !== '') ? 'No active job listings match your current search/filter criteria.' : 'No active job postings found.'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activeRecruitmentRows as $row): ?>
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars((string)$row['position_title'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)$row['plantilla_item_no'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)$row['office_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)$row['employment_type'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3">
                                    <p><?= (int)($row['applications_total'] ?? 0) ?> total</p>
                                    <p class="text-xs text-amber-700 mt-1"><?= (int)($row['applications_pending'] ?? 0) ?> pending</p>
                                </td>
                                <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)$row['close_date_label'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[110px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars((string)$row['status_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)$row['updated_label'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3">
                                    <div class="relative inline-block text-left" data-recruitment-action-menu data-admin-action-scope>
                                        <button type="button" data-admin-action-menu-toggle aria-haspopup="menu" aria-expanded="false" class="admin-action-button">
                                            <span class="admin-action-button-label">
                                                <span class="material-symbols-outlined">more_horiz</span>
                                                Actions
                                            </span>
                                            <span class="material-symbols-outlined admin-action-chevron">expand_more</span>
                                        </button>
                                        <div data-admin-action-menu role="menu" class="admin-action-menu hidden w-48">
                                            <button type="button" role="menuitem" class="admin-action-item" data-recruitment-job-view data-posting-id="<?= htmlspecialchars((string)$row['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                <span class="material-symbols-outlined">visibility</span>
                                                View
                                            </button>
                                            <button
                                                type="button"
                                                role="menuitem"
                                                class="admin-action-item"
                                                data-recruitment-secondary-action
                                                disabled
                                                aria-disabled="true"
                                                data-recruitment-job-edit
                                                data-posting-id="<?= htmlspecialchars((string)$row['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-title="<?= htmlspecialchars((string)$row['title'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-office-id="<?= htmlspecialchars((string)$row['office_id'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-position-id="<?= htmlspecialchars((string)$row['position_id'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-plantilla-item-no="<?= htmlspecialchars((string)$row['plantilla_item_no_raw'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-employment-type="<?= htmlspecialchars((string)$row['employment_type_raw'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-description="<?= htmlspecialchars((string)$row['description'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-qualifications="<?= htmlspecialchars((string)$row['qualifications'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-responsibilities="<?= htmlspecialchars((string)$row['responsibilities'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-eligibility-scope="<?= htmlspecialchars((string)($row['eligibility_scope'] ?? 'policy'), ENT_QUOTES, 'UTF-8') ?>"
                                                data-eligibility-option="<?= htmlspecialchars((string)($row['eligibility_option'] ?? 'csc_prc'), ENT_QUOTES, 'UTF-8') ?>"
                                                data-eligibility-requirement="<?= htmlspecialchars((string)($row['eligibility_requirement'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-minimum-education-level="<?= htmlspecialchars((string)($row['minimum_education_level'] ?? 'college'), ENT_QUOTES, 'UTF-8') ?>"
                                                data-minimum-education-years="<?= htmlspecialchars((string)($row['minimum_education_years'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"
                                                data-minimum-training-hours="<?= htmlspecialchars((string)($row['minimum_training_hours'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"
                                                data-minimum-experience-years="<?= htmlspecialchars((string)($row['minimum_experience_years'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"
                                                data-required-document-keys="<?= htmlspecialchars((string)implode(',', (array)($row['required_document_keys'] ?? [])), ENT_QUOTES, 'UTF-8') ?>"
                                                data-open-date="<?= htmlspecialchars((string)$row['open_date'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-close-date="<?= htmlspecialchars((string)$row['close_date'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-posting-status="<?= htmlspecialchars((string)$row['status_raw'], ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                                <span class="material-symbols-outlined">edit_square</span>
                                                Edit
                                            </button>
                                            <button type="button" role="menuitem" class="admin-action-item admin-action-item-danger" data-recruitment-secondary-action disabled aria-disabled="true" data-recruitment-job-archive data-posting-id="<?= htmlspecialchars((string)$row['id'], ENT_QUOTES, 'UTF-8') ?>" data-title="<?= htmlspecialchars((string)$row['position_title'], ENT_QUOTES, 'UTF-8') ?>">
                                                <span class="material-symbols-outlined">archive</span>
                                                Archive
                                            </button>
                                        </div>
                                    </div>
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
<?php else: ?>
    <?php
    $hideRecruitmentFlashState = true;
    $renderRecruitmentListingsSection = false;
    $renderRecruitmentArchivedSection = true;
    $renderRecruitmentDeadlinesSection = true;
    $renderRecruitmentModals = true;
    require __DIR__ . '/view.php';
    ?>
<?php endif; ?>