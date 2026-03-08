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
            <h2 class="text-lg font-semibold text-slate-800">Job Listings</h2>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" data-modal-open="recruitmentCreateJobModal" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">New Job</button>
            <a href="applicants.php" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Open Applicants Registration</a>
            <a href="evaluation.php" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Open Evaluation</a>
        </div>
    </header>

    <div class="px-6 py-4 text-xs text-slate-600 bg-slate-50 border-b border-slate-200">
        <span class="font-semibold text-slate-700">Status guide:</span>
        Closed = posting no longer accepts applicants but is still an active record.
        Archived = posting is moved to historical records only.
    </div>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600" for="recruitmentPostingsSearch">Search Job Listings</label>
            <input id="recruitmentPostingsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by position, division, employment type, or status">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600" for="recruitmentPostingsStatusFilter">Status</label>
            <select id="recruitmentPostingsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="Open">Open</option>
                <option value="Draft">Draft</option>
                <option value="Closed">Closed</option>
            </select>
        </div>
    </div>

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
                        <td class="px-4 py-3 text-slate-500" colspan="9">No active job postings found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activeRecruitmentRows as $row): ?>
                        <tr
                            data-recruitment-postings-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>"
                            data-recruitment-postings-status="<?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars((string)$row['position_title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['plantilla_item_no'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['office_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['employment_type'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= (int)$row['applications_total'] ?></td>
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
                                        <button
                                            type="button"
                                            role="menuitem"
                                            class="admin-action-item"
                                            data-recruitment-job-view
                                            data-posting-id="<?= htmlspecialchars((string)$row['id'], ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            <span class="material-symbols-outlined">visibility</span>
                                            View
                                        </button>
                                        <button
                                            type="button"
                                            role="menuitem"
                                            class="admin-action-item"
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
                                        <button
                                            type="button"
                                            role="menuitem"
                                            class="admin-action-item admin-action-item-danger"
                                            data-recruitment-job-archive
                                            data-posting-id="<?= htmlspecialchars((string)$row['id'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-title="<?= htmlspecialchars((string)$row['position_title'], ENT_QUOTES, 'UTF-8') ?>"
                                        >
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
</section>

<section class="bg-slate-50 border border-slate-300 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-300">
        <h2 class="text-lg font-semibold text-slate-800">Archived Job Postings</h2>
        <p class="text-sm text-slate-600 mt-1">Archived postings are separated from active hiring operations.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-100 text-slate-700">
                <tr>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Plantilla Number</th>
                    <th class="text-left px-4 py-3">Division</th>
                    <th class="text-left px-4 py-3">Employment Type</th>
                    <th class="text-left px-4 py-3">Applicants</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Archived Deadline</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (empty($archivedRecruitmentRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="7">No archived job postings found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($archivedRecruitmentRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars((string)$row['position_title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['plantilla_item_no'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['office_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['employment_type'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= (int)$row['applications_total'] ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[110px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars((string)$row['status_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['close_date_label'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Job Deadlines</h2>
        <p class="text-sm text-slate-500 mt-1">Track all open postings and prioritize upcoming deadlines.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Job Posting</th>
                    <th class="text-left px-4 py-3">Division</th>
                    <th class="text-left px-4 py-3">Deadline</th>
                    <th class="text-left px-4 py-3">Days Remaining</th>
                    <th class="text-left px-4 py-3">Priority</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($applicationDeadlineRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="5">No active posting deadlines found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applicationDeadlineRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800"><?= htmlspecialchars((string)$row['position_title'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)$row['title'], ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['office_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['close_date_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= (int)$row['days_remaining'] ?> day(s)</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)$row['priority_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['priority_label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div id="recruitmentPostingViewModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="recruitmentPostingViewModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-5xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">View Position</h3>
                <button type="button" id="recruitmentViewModalClose" data-modal-close="recruitmentPostingViewModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <div class="flex-1 min-h-0 overflow-y-auto p-6 text-sm space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-slate-500">Position</p>
                        <p id="recruitmentViewPosition" class="font-medium text-slate-800">-</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Plantilla Number</p>
                        <p id="recruitmentViewPlantillaItemNo" class="font-medium text-slate-800">-</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Division</p>
                        <p id="recruitmentViewOffice" class="font-medium text-slate-800">-</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Employment Type</p>
                        <p id="recruitmentViewEmploymentType" class="font-medium text-slate-800">-</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Status</p>
                        <p id="recruitmentViewStatus" class="font-medium text-slate-800">-</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Open Date</p>
                        <p id="recruitmentViewOpenDate" class="font-medium text-slate-800">-</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Deadline</p>
                        <p id="recruitmentViewCloseDate" class="font-medium text-slate-800">-</p>
                    </div>
                </div>
                <div>
                    <p class="text-slate-500">Description</p>
                    <p id="recruitmentViewDescription" class="mt-1 text-slate-800 whitespace-pre-line">-</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-slate-500">Qualifications</p>
                        <p id="recruitmentViewQualifications" class="mt-1 text-slate-800 whitespace-pre-line">-</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Responsibilities</p>
                        <p id="recruitmentViewResponsibilities" class="mt-1 text-slate-800 whitespace-pre-line">-</p>
                    </div>
                </div>
                <div>
                    <p class="text-slate-500">Application Requirements</p>
                    <ul id="recruitmentViewRequirements" class="mt-2 list-disc pl-6 text-slate-800 space-y-1"></ul>
                </div>
                <div>
                    <p class="text-slate-500">Qualification Criteria (4 Required)</p>
                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Eligibility</p>
                            <p id="recruitmentViewCriteriaEligibility" class="mt-1 text-sm font-medium text-slate-800">-</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Education</p>
                            <p id="recruitmentViewCriteriaEducation" class="mt-1 text-sm font-medium text-slate-800">-</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Training</p>
                            <p id="recruitmentViewCriteriaTraining" class="mt-1 text-sm font-medium text-slate-800">-</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Experience</p>
                            <p id="recruitmentViewCriteriaExperience" class="mt-1 text-sm font-medium text-slate-800">-</p>
                        </div>
                    </div>
                </div>
                <div>
                    <p class="text-slate-500 mb-2">Applicants</p>
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="text-left px-3 py-2">Applicant</th>
                                    <th class="text-left px-3 py-2">Applied Position</th>
                                    <th class="text-left px-3 py-2">Date Submitted</th>
                                    <th class="text-left px-3 py-2">Initial Screening</th>
                                    <th class="text-left px-3 py-2">Recommendation Score</th>
                                    <th class="text-left px-3 py-2">Basis</th>
                                    <th class="text-left px-3 py-2">Action</th>
                                </tr>
                            </thead>
                            <tbody id="recruitmentViewApplicantsBody" class="divide-y"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 bg-white flex justify-end">
                <button type="button" id="recruitmentViewModalCancel" data-modal-close="recruitmentPostingViewModal" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Close</button>
            </div>
        </div>
    </div>
</div>

<div id="recruitmentApplicantProfileModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="recruitmentApplicantProfileModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Applicant Profile</h3>
                <button type="button" data-modal-close="recruitmentApplicantProfileModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-start gap-4">
                        <div class="h-16 w-16 rounded-full border border-slate-200 bg-white overflow-hidden shrink-0 flex items-center justify-center">
                            <img id="recruitmentApplicantProfilePhoto" src="" alt="Applicant profile photo" class="hidden h-full w-full object-cover">
                            <span id="recruitmentApplicantProfilePhotoFallback" class="material-symbols-outlined text-slate-400">person</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs uppercase text-slate-500 tracking-wide">Applicant Profile Preview</p>
                            <p id="recruitmentApplicantProfileName" class="text-base font-semibold text-slate-800 mt-1">-</p>
                            <p id="recruitmentApplicantProfileMeta" class="text-sm text-slate-600 mt-1">-</p>
                            <p id="recruitmentApplicantProfileContact" class="text-xs text-slate-500 mt-1">-</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Professional Snapshot</p>
                    <ul class="mt-2 space-y-1.5 text-slate-700">
                        <li><span class="font-medium text-slate-800">Eligibility:</span> <span id="recruitmentApplicantEligibility">-</span></li>
                        <li><span class="font-medium text-slate-800">Education:</span> <span id="recruitmentApplicantEducation">-</span></li>
                        <li><span class="font-medium text-slate-800">Training:</span> <span id="recruitmentApplicantTraining">-</span></li>
                        <li><span class="font-medium text-slate-800">Experience:</span> <span id="recruitmentApplicantExperience">-</span></li>
                    </ul>
                </div>

                <div class="md:col-span-2 rounded-lg border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Work Experience</p>
                    <ul id="recruitmentApplicantWorkExperience" class="mt-2 list-disc pl-5 text-slate-700 space-y-1"></ul>
                </div>

                <div class="md:col-span-2 rounded-lg border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Profile Links</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <a id="recruitmentApplicantResumeLink" href="#" target="_blank" rel="noopener noreferrer" class="hidden px-3 py-1.5 text-xs rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">View Resume/CV</a>
                        <a id="recruitmentApplicantPortfolioLink" href="#" target="_blank" rel="noopener noreferrer" class="hidden px-3 py-1.5 text-xs rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">View Portfolio</a>
                        <p id="recruitmentApplicantLinksEmpty" class="text-xs text-slate-500">No external profile links submitted.</p>
                    </div>
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
                            <tbody id="recruitmentApplicantDocumentsBody" class="divide-y divide-slate-100">
                                <tr><td class="px-3 py-3 text-slate-500" colspan="5">No document selected.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-white flex justify-end">
                <button type="button" data-modal-close="recruitmentApplicantProfileModal" class="px-4 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Close</button>
            </div>
        </div>
    </div>
</div>

<div id="recruitmentCreateJobModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="recruitmentCreateJobModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-6xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Create Job Posting</h3>
                <button type="button" data-modal-close="recruitmentCreateJobModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="recruitment.php" method="POST" class="flex-1 min-h-0 flex flex-col">
                <input type="hidden" name="form_action" value="create_job_posting">
                <div class="flex-1 min-h-0 overflow-y-auto p-6 space-y-6 text-sm">
                    <section class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-semibold text-slate-800">Job Details</h4>
                        <p class="text-xs text-slate-500 mt-1">Define the position metadata and application period.</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Posting Title</label>
                                <input name="title" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            </div>
                            <div>
                                <label class="text-slate-600">Plantilla Number</label>
                                <input name="plantilla_item_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                                <p class="text-xs text-slate-500 mt-1">Must be unique across all job postings.</p>
                            </div>
                            <div>
                                <label class="text-slate-600">Division</label>
                                <select name="office_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                                    <option value="">Select division</option>
                                    <?php foreach ($officeOptions as $office): ?>
                                        <option value="<?= htmlspecialchars((string)($office['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($office['office_name'] ?? 'Office'), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-slate-600">Employment Type</label>
                                <select id="recruitmentCreateEmploymentType" name="employment_type" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                                    <option value="">Select employment type</option>
                                    <option value="permanent">Plantilla / Permanent</option>
                                    <option value="contractual">Contractual</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Position Source</label>
                                <select id="recruitmentCreatePositionMode" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                                    <option value="predefined">Select from predefined positions</option>
                                    <option value="new">Type a new position</option>
                                </select>
                                <p class="text-xs text-slate-500 mt-1">Predefined positions auto-fill qualification criteria based on existing configuration.</p>
                            </div>
                            <div id="recruitmentCreatePredefinedPositionWrap">
                                <label class="text-slate-600">Position (Available)</label>
                                <select id="recruitmentCreatePositionId" name="position_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                                    <option value="">Select position</option>
                                    <?php foreach ($availablePositionOptions as $position): ?>
                                        <?php
                                        $classification = strtolower((string)($position['employment_classification'] ?? ''));
                                        $positionEmploymentType = in_array($classification, ['regular', 'coterminous'], true) ? 'permanent' : 'contractual';
                                        ?>
                                        <option value="<?= htmlspecialchars((string)($position['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employment-type="<?= htmlspecialchars($positionEmploymentType, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($position['position_title'] ?? 'Position'), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div id="recruitmentCreateNewPositionWrap" class="hidden">
                                <label class="text-slate-600">New Position Title</label>
                                <input id="recruitmentCreateNewPositionTitle" name="new_position_title" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="e.g., Senior Agriculturist">
                                <p class="text-xs text-slate-500 mt-1">The new position will be added automatically and reused in future postings.</p>
                            </div>
                            <div>
                                <label class="text-slate-600">Open Date</label>
                                <input name="open_date" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            </div>
                            <div>
                                <label class="text-slate-600">Close Date</label>
                                <input name="close_date" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            </div>
                            <div>
                                <label class="text-slate-600">Initial Status</label>
                                <select name="posting_status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-semibold text-slate-800">Qualification Criteria</h4>
                        <p class="text-xs text-slate-500 mt-1">Applicants are scored against these criteria to compute recommendation results and identify missing qualifications.</p>
                        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                            <p><span class="font-semibold text-slate-700">How scoring works:</span> Each criterion contributes to the recommendation score. Applicants who miss one or more criteria can still be scored, but gaps are flagged during screening.</p>
                        </div>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="md:col-span-4">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700 mt-1" title="If checked, applicants must have either CSC or PRC eligibility.">
                                    <input type="hidden" name="criteria_eligibility_required" value="0">
                                    <input id="recruitmentCreateCriteriaEligibilityRequired" type="checkbox" name="criteria_eligibility_required" value="1" checked>
                                    CSC/PRC Eligibility Required
                                </label>
                                <p class="text-xs text-slate-500 mt-1">If unchecked, eligibility is excluded from required criteria for this posting.</p>
                            </div>
                            <div>
                                <label class="text-slate-600" title="Minimum educational attainment required.">Minimum Education Level</label>
                                <select id="recruitmentCreateCriteriaEducationLevel" name="criteria_education_level" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                                    <option value="elementary">Elementary</option>
                                    <option value="secondary">Secondary</option>
                                    <option value="vocational">Vocational/Trade Course</option>
                                    <option value="college" selected>College</option>
                                    <option value="graduate">Graduate Studies</option>
                                </select>
                                <p class="text-xs text-slate-500 mt-1">The system compares against the applicant's highest educational attainment.</p>
                            </div>
                            <div>
                                <label class="text-slate-600" title="Minimum number of training hours completed.">Minimum Training (Hours)</label>
                                <input id="recruitmentCreateCriteriaTrainingHours" name="criteria_training_hours" type="number" min="0" step="0.5" value="4" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                                <p class="text-xs text-slate-500 mt-1">Total relevant training hours expected from the applicant.</p>
                            </div>
                            <div>
                                <label class="text-slate-600" title="Minimum number of relevant work years.">Minimum Experience (Years)</label>
                                <input id="recruitmentCreateCriteriaExperienceYears" name="criteria_experience_years" type="number" min="0" step="0.5" value="1" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                                <p class="text-xs text-slate-500 mt-1">Relevant professional experience required for initial recommendation.</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-semibold text-slate-800">Description and Responsibilities</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Description</label>
                                <textarea name="description" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></textarea>
                            </div>
                            <div>
                                <label class="text-slate-600">Qualifications</label>
                                <textarea name="qualifications" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></textarea>
                            </div>
                            <div>
                                <label class="text-slate-600">Responsibilities</label>
                                <textarea name="responsibilities" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-semibold text-slate-800">Required Documents</h4>
                        <p class="text-xs text-slate-500 mt-1">Select the submission checklist shown to applicants. Missing checked documents are flagged during application review.</p>
                        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                            <p><span class="font-semibold text-slate-700">Tip:</span> Keep this checklist aligned with the position requirements to avoid incomplete submissions.</p>
                        </div>
                        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="required_documents[]" value="application_letter" checked> Application Letter</label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="required_documents[]" value="updated_resume_cv" checked> Updated Resume/CV</label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="required_documents[]" value="personal_data_sheet" checked> Personal Data Sheet</label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="required_documents[]" value="valid_government_id" checked> Valid Government ID</label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="required_documents[]" value="transcript_of_records" checked> Transcript of Records</label>
                        </div>
                    </section>
                </div>
                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-modal-close="recruitmentCreateJobModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Create Job</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="recruitmentEditJobModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="recruitmentEditJobModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-6xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Edit Job Posting</h3>
                <button type="button" data-modal-close="recruitmentEditJobModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="recruitment.php" method="POST" class="flex-1 min-h-0 flex flex-col">
                <input type="hidden" name="form_action" value="edit_job_posting">
                <input type="hidden" name="posting_id" id="recruitmentEditPostingId" value="">
                <div class="flex-1 min-h-0 overflow-y-auto p-6 space-y-6 text-sm">
                    <section class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-semibold text-slate-800">Job Details</h4>
                        <p class="text-xs text-slate-500 mt-1">Update posting details and active dates.</p>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Posting Title</label>
                                <input id="recruitmentEditTitle" name="title" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            </div>
                            <div>
                                <label class="text-slate-600">Plantilla Number</label>
                                <input id="recruitmentEditPlantillaItemNo" name="plantilla_item_no" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                                <p class="text-xs text-slate-500 mt-1">Must remain unique across all postings.</p>
                            </div>
                            <div>
                                <label class="text-slate-600">Division</label>
                                <select id="recruitmentEditOfficeId" name="office_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                                    <option value="">Select division</option>
                                    <?php foreach ($officeOptions as $office): ?>
                                        <option value="<?= htmlspecialchars((string)($office['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($office['office_name'] ?? 'Office'), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-slate-600">Employment Type</label>
                                <select id="recruitmentEditEmploymentType" name="employment_type" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                                    <option value="permanent">Plantilla / Permanent</option>
                                    <option value="contractual">Contractual</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Position Source</label>
                                <select id="recruitmentEditPositionMode" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                                    <option value="predefined">Select from predefined positions</option>
                                    <option value="new">Type a new position</option>
                                </select>
                                <p class="text-xs text-slate-500 mt-1">Predefined positions can reuse saved criteria, while new positions are added for future use.</p>
                            </div>
                            <div id="recruitmentEditPredefinedPositionWrap">
                                <label class="text-slate-600">Position (Available)</label>
                                <select id="recruitmentEditPositionId" name="position_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                                    <option value="">Select position</option>
                                    <?php foreach ($positionOptions as $position): ?>
                                        <?php
                                        $classification = strtolower((string)($position['employment_classification'] ?? ''));
                                        $positionEmploymentType = in_array($classification, ['regular', 'coterminous'], true) ? 'permanent' : 'contractual';
                                        ?>
                                        <option value="<?= htmlspecialchars((string)($position['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-employment-type="<?= htmlspecialchars($positionEmploymentType, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($position['position_title'] ?? 'Position'), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div id="recruitmentEditNewPositionWrap" class="hidden">
                                <label class="text-slate-600">New Position Title</label>
                                <input id="recruitmentEditNewPositionTitle" name="new_position_title" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="e.g., Senior Agriculturist">
                                <p class="text-xs text-slate-500 mt-1">If entered, a new position record is created automatically.</p>
                            </div>
                            <div>
                                <label class="text-slate-600">Open Date</label>
                                <input id="recruitmentEditOpenDate" name="open_date" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            </div>
                            <div>
                                <label class="text-slate-600">Close Date</label>
                                <input id="recruitmentEditCloseDate" name="close_date" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            </div>
                            <div>
                                <label class="text-slate-600">Status</label>
                                <select id="recruitmentEditStatus" name="posting_status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                    <option value="closed">Closed</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-semibold text-slate-800">Qualification Criteria</h4>
                        <p class="text-xs text-slate-500 mt-1">Applicants are scored against these criteria to compute recommendation results and identify missing qualifications.</p>
                        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                            <p><span class="font-semibold text-slate-700">How scoring works:</span> Each criterion contributes to the recommendation score. Applicants who miss one or more criteria can still be scored, but gaps are flagged during screening.</p>
                        </div>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="md:col-span-4">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700 mt-1" title="If checked, applicants must have either CSC or PRC eligibility.">
                                    <input type="hidden" name="criteria_eligibility_required" value="0">
                                    <input id="recruitmentEditCriteriaEligibilityRequired" type="checkbox" name="criteria_eligibility_required" value="1">
                                    CSC/PRC Eligibility Required
                                </label>
                                <p class="text-xs text-slate-500 mt-1">If unchecked, eligibility is excluded from required criteria for this posting.</p>
                            </div>
                            <div>
                                <label class="text-slate-600" title="Minimum educational attainment required.">Minimum Education Level</label>
                                <select id="recruitmentEditCriteriaEducationLevel" name="criteria_education_level" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                                    <option value="elementary">Elementary</option>
                                    <option value="secondary">Secondary</option>
                                    <option value="vocational">Vocational/Trade Course</option>
                                    <option value="college">College</option>
                                    <option value="graduate">Graduate Studies</option>
                                </select>
                                <p class="text-xs text-slate-500 mt-1">The system compares against the applicant's highest educational attainment.</p>
                            </div>
                            <div>
                                <label class="text-slate-600" title="Minimum number of training hours completed.">Minimum Training (Hours)</label>
                                <input id="recruitmentEditCriteriaTrainingHours" name="criteria_training_hours" type="number" min="0" step="0.5" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                                <p class="text-xs text-slate-500 mt-1">Total relevant training hours expected from the applicant.</p>
                            </div>
                            <div>
                                <label class="text-slate-600" title="Minimum number of relevant work years.">Minimum Experience (Years)</label>
                                <input id="recruitmentEditCriteriaExperienceYears" name="criteria_experience_years" type="number" min="0" step="0.5" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                                <p class="text-xs text-slate-500 mt-1">Relevant professional experience required for initial recommendation.</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-semibold text-slate-800">Description and Responsibilities</h4>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="text-slate-600">Description</label>
                                <textarea id="recruitmentEditDescription" name="description" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></textarea>
                            </div>
                            <div>
                                <label class="text-slate-600">Qualifications</label>
                                <textarea id="recruitmentEditQualifications" name="qualifications" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></textarea>
                            </div>
                            <div>
                                <label class="text-slate-600">Responsibilities</label>
                                <textarea id="recruitmentEditResponsibilities" name="responsibilities" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-semibold text-slate-800">Required Documents</h4>
                        <p class="text-xs text-slate-500 mt-1">Select the submission checklist shown to applicants. Missing checked documents are flagged during application review.</p>
                        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                            <p><span class="font-semibold text-slate-700">Tip:</span> Keep this checklist aligned with the position requirements to avoid incomplete submissions.</p>
                        </div>
                        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="required_documents[]" value="application_letter" checked> Application Letter</label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="required_documents[]" value="updated_resume_cv" checked> Updated Resume/CV</label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="required_documents[]" value="personal_data_sheet" checked> Personal Data Sheet</label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="required_documents[]" value="valid_government_id" checked> Valid Government ID</label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="required_documents[]" value="transcript_of_records" checked> Transcript of Records</label>
                        </div>
                    </section>
                </div>
                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-modal-close="recruitmentEditJobModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Applicant Email Automation Templates</h2>
        <p class="text-sm text-slate-500 mt-1">Customize outgoing email templates for submitted, passed, failed, and next-stage updates. Placeholders: <code>{applicant_name}</code>, <code>{job_title}</code>, <code>{application_ref_no}</code>, <code>{remarks}</code>.</p>
    </header>
    <form action="recruitment.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <input type="hidden" name="form_action" value="save_recruitment_email_templates">

        <div>
            <label class="text-slate-600">Submitted Subject</label>
            <input name="submitted_subject" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)($recruitmentEmailTemplates['submitted']['subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
            <label class="text-slate-600">Passed Subject</label>
            <input name="passed_subject" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)($recruitmentEmailTemplates['passed']['subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Submitted Body</label>
            <textarea name="submitted_body" rows="4" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"><?= htmlspecialchars((string)($recruitmentEmailTemplates['submitted']['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Passed Body</label>
            <textarea name="passed_body" rows="4" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"><?= htmlspecialchars((string)($recruitmentEmailTemplates['passed']['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div>
            <label class="text-slate-600">Failed Subject</label>
            <input name="failed_subject" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)($recruitmentEmailTemplates['failed']['subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
            <label class="text-slate-600">Next Stage Subject</label>
            <input name="next_stage_subject" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?= htmlspecialchars((string)($recruitmentEmailTemplates['next_stage']['subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Failed Body</label>
            <textarea name="failed_body" rows="4" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"><?= htmlspecialchars((string)($recruitmentEmailTemplates['failed']['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Next Stage Body</label>
            <textarea name="next_stage_body" rows="4" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"><?= htmlspecialchars((string)($recruitmentEmailTemplates['next_stage']['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="md:col-span-2 flex justify-end gap-3">
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Email Templates</button>
        </div>
    </form>
</section>

<div id="recruitmentArchiveJobModal" data-modal class="fixed inset-0 z-50 hidden overflow-y-auto" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="recruitmentArchiveJobModal"></div>
    <div class="relative min-h-full flex items-start sm:items-center justify-center p-4">
        <div class="w-full max-w-lg bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Archive Job Posting</h3>
                <button type="button" data-modal-close="recruitmentArchiveJobModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="recruitment.php" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm">
                <input type="hidden" name="form_action" value="archive_job_posting">
                <input type="hidden" name="posting_id" id="recruitmentArchivePostingId" value="">
                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-xs uppercase text-slate-500">Job Posting</p>
                    <p id="recruitmentArchivePostingTitle" class="font-medium text-slate-800 mt-1">-</p>
                </div>
                <p class="text-xs text-slate-500">Archived jobs are removed from active recruitment flows but remain in historical records.</p>
                <div class="flex justify-end gap-3 mt-2"><button type="button" data-modal-close="recruitmentArchiveJobModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button><button type="submit" class="px-5 py-2 rounded-md bg-rose-600 text-white hover:bg-rose-700">Archive Job</button></div>
            </form>
        </div>
    </div>
</div>

<script id="recruitmentPostingViewData" type="application/json"><?= (string)json_encode($postingViewById, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script id="recruitmentCreatePositionCriteriaData" type="application/json"><?= (string)json_encode([
    'defaults' => $recruitmentCreateCriteriaDefaults,
    'positions' => $recruitmentPositionCriteriaById,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
