<?php
$postingPill = static function (string $status): array {
    $key = strtolower(trim($status));
    if ($key === 'published') {
        return ['Open', 'bg-emerald-100 text-emerald-800'];
    }
    if ($key === 'closed') {
        return ['Closed', 'bg-amber-100 text-amber-800'];
    }
    if ($key === 'archived') {
        return ['Archived', 'bg-slate-200 text-slate-700'];
    }

    return ['Draft', 'bg-blue-100 text-blue-800'];
};

$appStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    if (in_array($key, ['hired', 'offer', 'shortlisted'], true)) {
        return ['Approved', 'bg-emerald-100 text-emerald-800'];
    }
    if (in_array($key, ['interview'], true)) {
        return ['For Interview', 'bg-blue-100 text-blue-800'];
    }
    if (in_array($key, ['submitted', 'screening'], true)) {
        return ['Pending', 'bg-amber-100 text-amber-800'];
    }

    return [ucfirst($key), 'bg-slate-100 text-slate-700'];
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
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Job Listings</h2>
            <p class="text-sm text-slate-500 mt-1">Overview of open and archived postings for all hiring departments.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" data-modal-open="recruitmentCreateJobModal" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">New Job</button>
            <a href="applicants.php" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Open Applicants</a>
            <a href="evaluation.php" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Open Evaluation</a>
        </div>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600" for="recruitmentPostingsSearch">Search Job Listings</label>
            <input id="recruitmentPostingsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by position, department, status, or remarks">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600" for="recruitmentPostingsStatusFilter">Status</label>
            <select id="recruitmentPostingsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="Open">Open</option>
                <option value="Draft">Draft</option>
                <option value="Closed">Closed</option>
                <option value="Archived">Archived</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="recruitmentPostingsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Department</th>
                    <th class="text-left px-4 py-3">Applicants</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Remarks</th>
                    <th class="text-left px-4 py-3">Last Updated</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($postings)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="7">No job postings found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($postings as $posting): ?>
                        <?php
                        $postingId = (string)($posting['id'] ?? '');
                        $title = (string)($posting['title'] ?? '-');
                        $positionTitle = (string)($posting['position']['position_title'] ?? $title);
                        $officeId = (string)($posting['office_id'] ?? '');
                        $positionId = (string)($posting['position_id'] ?? '');
                        $department = (string)($posting['office']['office_name'] ?? 'Unassigned Office');
                        $description = (string)($posting['description'] ?? '');
                        $qualifications = (string)($posting['qualifications'] ?? '');
                        $responsibilities = (string)($posting['responsibilities'] ?? '');
                        $openDate = (string)($posting['open_date'] ?? '');
                        $totalApplicants = (int)($applicationCountsByPosting[$postingId] ?? 0);
                        $postingStatusRaw = (string)($posting['posting_status'] ?? 'draft');
                        [$statusLabel, $statusClass] = $postingPill((string)($posting['posting_status'] ?? 'draft'));
                        $closeDate = (string)($posting['close_date'] ?? '');
                        $remark = $closeDate !== '' ? 'Application ends ' . date('M d, Y', strtotime($closeDate)) : 'No closing date set.';
                        $updatedAt = (string)($posting['updated_at'] ?? '');
                        $updatedLabel = $updatedAt !== '' ? date('M d, Y', strtotime($updatedAt)) : '-';
                        $rowSearch = strtolower(trim($title . ' ' . $department . ' ' . $statusLabel . ' ' . $remark . ' ' . $updatedLabel));
                        ?>
                        <tr data-recruitment-postings-search="<?= htmlspecialchars($rowSearch, ENT_QUOTES, 'UTF-8') ?>" data-recruitment-postings-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$totalApplicants, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[110px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($remark, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($updatedLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"
                                        data-recruitment-job-edit
                                        data-posting-id="<?= htmlspecialchars($postingId, ENT_QUOTES, 'UTF-8') ?>"
                                        data-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
                                        data-office-id="<?= htmlspecialchars($officeId, ENT_QUOTES, 'UTF-8') ?>"
                                        data-position-id="<?= htmlspecialchars($positionId, ENT_QUOTES, 'UTF-8') ?>"
                                        data-description="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>"
                                        data-qualifications="<?= htmlspecialchars($qualifications, ENT_QUOTES, 'UTF-8') ?>"
                                        data-responsibilities="<?= htmlspecialchars($responsibilities, ENT_QUOTES, 'UTF-8') ?>"
                                        data-open-date="<?= htmlspecialchars($openDate, ENT_QUOTES, 'UTF-8') ?>"
                                        data-close-date="<?= htmlspecialchars($closeDate, ENT_QUOTES, 'UTF-8') ?>"
                                        data-posting-status="<?= htmlspecialchars($postingStatusRaw, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">edit_square</span>
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-rose-300 bg-white text-rose-700 hover:bg-rose-50 shadow-sm"
                                        data-recruitment-job-archive
                                        data-posting-id="<?= htmlspecialchars($postingId, ENT_QUOTES, 'UTF-8') ?>"
                                        data-title="<?= htmlspecialchars($positionTitle, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">archive</span>
                                        Archive
                                    </button>
                                </div>
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
        <h2 class="text-lg font-semibold text-slate-800">Registered Applicants</h2>
        <p class="text-sm text-slate-500 mt-1">Search all registered applicants and click status boxes to view matching records.</p>
    </header>

    <div class="p-6 border-b border-slate-200 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Applicants</label>
            <input id="recruitmentApplicantsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by applicant name, position, or email">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="recruitmentApplicantsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="Approved">Approved</option>
                <option value="Pending">Pending</option>
                <option value="For Interview">For Interview</option>
            </select>
        </div>
    </div>

    <div class="px-6 pt-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="rounded-lg border border-slate-200 p-4 bg-slate-50">
                <p class="text-xs uppercase text-slate-600">Registered Applicants</p>
                <p class="text-2xl font-semibold text-slate-800 mt-2"><?= htmlspecialchars((string)$applicantStatusCounts['registered'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-slate-600 mt-1">View all submitted applications.</p>
            </div>
            <div class="rounded-lg border border-slate-200 p-4 bg-emerald-50">
                <p class="text-xs uppercase text-emerald-700">Approved</p>
                <p class="text-2xl font-semibold text-slate-800 mt-2"><?= htmlspecialchars((string)$applicantStatusCounts['approved'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-slate-600 mt-1">Applicants marked as qualified.</p>
            </div>
            <div class="rounded-lg border border-slate-200 p-4 bg-amber-50">
                <p class="text-xs uppercase text-amber-700">Pending Decisions</p>
                <p class="text-2xl font-semibold text-slate-800 mt-2"><?= htmlspecialchars((string)$applicantStatusCounts['pending'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-slate-600 mt-1">Applicants awaiting final decision.</p>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" data-app-status-chip="" class="px-3 py-1.5 text-xs rounded-full border border-slate-300 text-slate-700 hover:bg-slate-50">All</button>
            <button type="button" data-app-status-chip="Approved" class="px-3 py-1.5 text-xs rounded-full border border-emerald-300 text-emerald-700 hover:bg-emerald-50">Approved</button>
            <button type="button" data-app-status-chip="Pending" class="px-3 py-1.5 text-xs rounded-full border border-amber-300 text-amber-700 hover:bg-amber-50">Pending</button>
            <button type="button" data-app-status-chip="For Interview" class="px-3 py-1.5 text-xs rounded-full border border-blue-300 text-blue-700 hover:bg-blue-50">For Interview</button>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="recruitmentApplicantsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Position Applied</th>
                    <th class="text-left px-4 py-3">Email</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Submitted</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($applications)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="5">No application records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applications as $application): ?>
                        <?php
                        $fullName = (string)($application['applicant']['full_name'] ?? 'Unknown Applicant');
                        $email = (string)($application['applicant']['email'] ?? '-');
                        $position = (string)($application['job']['title'] ?? '-');
                        [$statusLabel, $statusClass] = $appStatusPill((string)($application['application_status'] ?? 'submitted'));
                        $submittedAt = (string)($application['submitted_at'] ?? '');
                        $submittedLabel = $submittedAt !== '' ? date('M d, Y', strtotime($submittedAt)) : '-';
                        ?>
                        <?php $rowSearch = strtolower(trim($fullName . ' ' . $position . ' ' . $email . ' ' . $statusLabel . ' ' . $submittedLabel)); ?>
                        <tr data-app-search="<?= htmlspecialchars($rowSearch, ENT_QUOTES, 'UTF-8') ?>" data-app-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[110px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($submittedLabel, ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div id="recruitmentCreateJobModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="recruitmentCreateJobModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Create Job Posting</h3>
                <button type="button" data-modal-close="recruitmentCreateJobModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="recruitment.php" method="POST" class="flex-1 min-h-0 flex flex-col">
                <input type="hidden" name="form_action" value="create_job_posting">
                <div class="flex-1 min-h-0 overflow-y-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="md:col-span-2"><label class="text-slate-600">Job Title</label><input name="title" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></div>
                    <div>
                        <label class="text-slate-600">Department</label>
                        <select name="office_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            <option value="">Select department</option>
                            <?php foreach ($officeOptions as $office): ?>
                                <option value="<?= htmlspecialchars((string)($office['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>\"><?= htmlspecialchars((string)($office['office_name'] ?? 'Office'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-slate-600">Position</label>
                        <select name="position_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            <option value="">Select position</option>
                            <?php foreach ($positionOptions as $position): ?>
                                <option value="<?= htmlspecialchars((string)($position['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>\"><?= htmlspecialchars((string)($position['position_title'] ?? 'Position'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label class="text-slate-600">Open Date</label><input name="open_date" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></div>
                    <div><label class="text-slate-600">Close Date</label><input name="close_date" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></div>
                    <div><label class="text-slate-600">Initial Status</label><select name="posting_status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"><option value="draft">Draft</option><option value="published">Published</option><option value="closed">Closed</option></select></div>
                    <div class="md:col-span-2"><label class="text-slate-600">Description</label><textarea name="description" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></textarea></div>
                    <div><label class="text-slate-600">Qualifications</label><textarea name="qualifications" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></textarea></div>
                    <div><label class="text-slate-600">Responsibilities</label><textarea name="responsibilities" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></textarea></div>
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
        <div class="w-full max-w-3xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Edit Job Posting</h3>
                <button type="button" data-modal-close="recruitmentEditJobModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="recruitment.php" method="POST" class="flex-1 min-h-0 flex flex-col">
                <input type="hidden" name="form_action" value="edit_job_posting">
                <input type="hidden" name="posting_id" id="recruitmentEditPostingId" value="">
                <div class="flex-1 min-h-0 overflow-y-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="md:col-span-2"><label class="text-slate-600">Job Title</label><input id="recruitmentEditTitle" name="title" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></div>
                    <div>
                        <label class="text-slate-600">Department</label>
                        <select id="recruitmentEditOfficeId" name="office_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            <option value="">Select department</option>
                            <?php foreach ($officeOptions as $office): ?>
                                <option value="<?= htmlspecialchars((string)($office['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>\"><?= htmlspecialchars((string)($office['office_name'] ?? 'Office'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-slate-600">Position</label>
                        <select id="recruitmentEditPositionId" name="position_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            <option value="">Select position</option>
                            <?php foreach ($positionOptions as $position): ?>
                                <option value="<?= htmlspecialchars((string)($position['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>\"><?= htmlspecialchars((string)($position['position_title'] ?? 'Position'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label class="text-slate-600">Open Date</label><input id="recruitmentEditOpenDate" name="open_date" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></div>
                    <div><label class="text-slate-600">Close Date</label><input id="recruitmentEditCloseDate" name="close_date" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></div>
                    <div><label class="text-slate-600">Status</label><select id="recruitmentEditStatus" name="posting_status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"><option value="draft">Draft</option><option value="published">Published</option><option value="closed">Closed</option><option value="archived">Archived</option></select></div>
                    <div class="md:col-span-2"><label class="text-slate-600">Description</label><textarea id="recruitmentEditDescription" name="description" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></textarea></div>
                    <div><label class="text-slate-600">Qualifications</label><textarea id="recruitmentEditQualifications" name="qualifications" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></textarea></div>
                    <div><label class="text-slate-600">Responsibilities</label><textarea id="recruitmentEditResponsibilities" name="responsibilities" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></textarea></div>
                </div>
                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-modal-close="recruitmentEditJobModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
