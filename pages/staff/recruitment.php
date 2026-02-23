<?php
require_once __DIR__ . '/includes/recruitment/bootstrap.php';
require_once __DIR__ . '/includes/recruitment/actions.php';
require_once __DIR__ . '/includes/recruitment/data.php';

$pageTitle = 'Recruitment | Staff';
$activePage = 'recruitment.php';
$breadcrumbs = ['Recruitment'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Recruitment</h1>
    <p class="text-sm text-gray-500">Manage office-scoped job postings and monitor applicant pipeline endorsements.</p>
</div>

<div id="recruitmentFlashState" class="hidden" data-state="<?= htmlspecialchars((string)$state, ENT_QUOTES, 'UTF-8') ?>" data-message="<?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>"></div>

<?php if ($state && $message): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($dataLoadError): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Recruitment Modules</h2>
            <p class="text-sm text-gray-500 mt-1">Open detailed flows for applicant registration and tracking.</p>
        </div>
        <div class="flex gap-2">
            <a href="applicant-registration.php" class="px-4 py-2 rounded-md bg-green-700 text-white text-sm hover:bg-green-800">Applicant Registration</a>
            <a href="applicant-tracking.php" class="px-4 py-2 rounded-md border text-sm text-gray-700 hover:bg-gray-50">Applicant Tracking</a>
        </div>
    </header>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Job Listings</h2>
            <p class="text-sm text-gray-500 mt-1">Active job listings aligned with admin-side posting patterns.</p>
        </div>
        <button type="button" id="openCreateJobPostingModal" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100">
            <span class="material-symbols-outlined text-[14px]">add</span>
            Create Job Posting
        </button>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="recruitmentSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="recruitmentSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by title, office, position, or status">
        </div>
        <div>
            <label for="recruitmentStatusFilter" class="text-sm text-gray-600">All Statuses</label>
            <select id="recruitmentStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="published">Open</option>
                <option value="closed">Closed</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="recruitmentTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Office</th>
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
                        <td class="px-4 py-3 text-gray-500" colspan="8">No active job postings found in your scope.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activeRecruitmentRows as $row): ?>
                        <tr data-recruitment-row data-recruitment-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-recruitment-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
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
                                <button
                                    type="button"
                                    data-open-posting-view-modal
                                    data-posting-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    class="px-3 py-1.5 text-xs rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-100"
                                >
                                    View
                                </button>
                                <button
                                    type="button"
                                    data-open-posting-status-modal
                                    data-posting-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-posting-title="<?= htmlspecialchars((string)($row['position_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status-label="<?= htmlspecialchars((string)($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    class="px-3 py-1.5 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100"
                                >
                                    Update Status
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="recruitmentFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="8">No active postings match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-slate-50 border border-slate-300 rounded-xl mb-6">
    <header class="px-6 py-4 border-b border-slate-300">
        <h2 class="text-lg font-semibold text-slate-800">Archived Job Postings</h2>
        <p class="text-sm text-slate-600 mt-1">Archived postings are separated from active listings and can be restored with Unarchive.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-100 text-slate-700">
                <tr>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Office</th>
                    <th class="text-left px-4 py-3">Employment Type</th>
                    <th class="text-left px-4 py-3">Archived Deadline</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($archivedRecruitmentRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="6">No archived job postings found.</td>
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
                            <td class="px-4 py-3">
                                <form method="POST" action="recruitment.php" class="inline-block recruitment-unarchive-form" data-position-title="<?= htmlspecialchars((string)($row['position_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="form_action" value="unarchive_job_posting">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="posting_id" value="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-100">Unarchive</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">View Application Deadlines</h2>
        <p class="text-sm text-gray-500 mt-1">Track active job postings and prioritize upcoming application deadlines.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Job Posting</th>
                    <th class="text-left px-4 py-3">Office</th>
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

<div id="postingStatusModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Review Posting Status</h3>
            <button type="button" id="postingStatusModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="postingStatusForm" method="POST" action="recruitment.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="update_posting_status">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="posting_id" id="postingStatusPostingId" value="">

            <div>
                <label class="text-gray-600">Position</label>
                <p id="postingStatusTitle" class="mt-1 text-sm font-medium text-gray-800">-</p>
            </div>
            <div>
                <label class="text-gray-600">Current Status</label>
                <p id="postingStatusCurrent" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label for="postingStatusNew" class="text-gray-600">Decision</label>
                <select id="postingStatusNew" name="new_status" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select status</option>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="closed">Closed</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
            <div>
                <label for="postingStatusNotes" class="text-gray-600">Notes</label>
                <textarea id="postingStatusNotes" name="status_notes" rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Add posting status notes."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="postingStatusModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="postingStatusSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Decision</button>
            </div>
        </form>
    </div>
</div>

<div id="createJobPostingModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-3xl rounded-xl bg-white border shadow-lg max-h-[calc(100vh-2rem)] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Create Job Posting</h3>
            <button type="button" id="createJobPostingModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal"><span class="material-symbols-outlined">close</span></button>
        </div>

        <form id="createJobPostingForm" method="POST" action="recruitment.php" class="flex-1 flex flex-col min-h-0">
            <input type="hidden" name="form_action" value="create_job_posting">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div class="flex-1 overflow-y-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="md:col-span-2">
                    <label for="createJobPostingTitle" class="text-gray-600">Position</label>
                    <input id="createJobPostingTitle" name="title" type="text" class="w-full mt-1 border rounded-md px-3 py-2" required>
                </div>
                <div>
                    <label for="createJobPostingOffice" class="text-gray-600">Office</label>
                    <select id="createJobPostingOffice" name="office_id" class="w-full mt-1 border rounded-md px-3 py-2" required>
                        <option value="">Select office</option>
                        <?php foreach ($officeOptions as $office): ?>
                            <option value="<?= htmlspecialchars((string)($office['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($office['office_name'] ?? 'Office'), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="createJobPostingPosition" class="text-gray-600">Position</label>
                    <select id="createJobPostingPosition" name="position_id" class="w-full mt-1 border rounded-md px-3 py-2" required>
                        <option value="">Select position</option>
                        <?php foreach ($positionOptions as $position): ?>
                            <?php
                            $classification = strtolower((string)(cleanText($position['employment_classification'] ?? null) ?? ''));
                            $positionEmploymentType = in_array($classification, ['regular', 'coterminous'], true) ? 'permanent' : 'contractual';
                            ?>
                            <option
                                value="<?= htmlspecialchars((string)($position['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-employment-type="<?= htmlspecialchars($positionEmploymentType, ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <?= htmlspecialchars((string)($position['position_title'] ?? 'Position'), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="createJobPostingEmploymentType" class="text-gray-600">Employment Type</label>
                    <select id="createJobPostingEmploymentType" name="employment_type" class="w-full mt-1 border rounded-md px-3 py-2" required>
                        <option value="">Select employment type</option>
                        <option value="permanent">Permanent</option>
                        <option value="contractual">Contractual</option>
                    </select>
                </div>
                <div>
                    <label for="createJobPostingOpenDate" class="text-gray-600">Open Date</label>
                    <input id="createJobPostingOpenDate" name="open_date" type="date" class="w-full mt-1 border rounded-md px-3 py-2" required>
                </div>
                <div>
                    <label for="createJobPostingCloseDate" class="text-gray-600">Close Date</label>
                    <input id="createJobPostingCloseDate" name="close_date" type="date" class="w-full mt-1 border rounded-md px-3 py-2" required>
                </div>
                <div>
                    <label for="createJobPostingStatus" class="text-gray-600">Initial Status</label>
                    <select id="createJobPostingStatus" name="posting_status" class="w-full mt-1 border rounded-md px-3 py-2">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label for="createJobPostingDescription" class="text-gray-600">Description</label>
                    <textarea id="createJobPostingDescription" name="description" rows="3" class="w-full mt-1 border rounded-md px-3 py-2" required></textarea>
                </div>
                <div>
                    <label for="createJobPostingQualifications" class="text-gray-600">Qualifications</label>
                    <textarea id="createJobPostingQualifications" name="qualifications" rows="3" class="w-full mt-1 border rounded-md px-3 py-2"></textarea>
                </div>
                <div>
                    <label for="createJobPostingResponsibilities" class="text-gray-600">Responsibilities</label>
                    <textarea id="createJobPostingResponsibilities" name="responsibilities" rows="3" class="w-full mt-1 border rounded-md px-3 py-2"></textarea>
                </div>
                <div class="md:col-span-2">
                    <p class="text-gray-600">Required Documents</p>
                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="required_documents[]" value="pds" checked> PDS</label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="required_documents[]" value="wes" checked> WES</label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="required_documents[]" value="eligibility_csc_prc" checked> Eligibility (CSC/PRC)</label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="required_documents[]" value="transcript_of_records" checked> Transcript of Records</label>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t bg-white flex justify-end gap-3">
                <button type="button" id="createJobPostingModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="createJobPostingSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800 disabled:opacity-60 disabled:cursor-not-allowed">Create Job Posting</button>
            </div>
        </form>
    </div>
</div>

<div id="postingViewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4" data-csrf-token="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <div class="w-full max-w-5xl rounded-xl bg-white border shadow-lg max-h-[calc(100vh-2rem)] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">View Position</h3>
            <button type="button" id="postingViewModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6 space-y-5 text-sm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-500">Position</p>
                    <p id="postingViewPosition" class="font-medium text-gray-800">-</p>
                </div>
                <div>
                    <p class="text-gray-500">Office</p>
                    <p id="postingViewOffice" class="font-medium text-gray-800">-</p>
                </div>
                <div>
                    <p class="text-gray-500">Employment Type</p>
                    <p id="postingViewEmploymentType" class="font-medium text-gray-800">-</p>
                </div>
                <div>
                    <p class="text-gray-500">Status</p>
                    <p id="postingViewStatus" class="font-medium text-gray-800">-</p>
                </div>
                <div>
                    <p class="text-gray-500">Open Date</p>
                    <p id="postingViewOpenDate" class="font-medium text-gray-800">-</p>
                </div>
                <div>
                    <p class="text-gray-500">Deadline</p>
                    <p id="postingViewCloseDate" class="font-medium text-gray-800">-</p>
                </div>
            </div>
            <div>
                <p class="text-gray-500">Description</p>
                <p id="postingViewDescription" class="mt-1 text-gray-800 whitespace-pre-line">-</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-500">Qualifications</p>
                    <p id="postingViewQualifications" class="mt-1 text-gray-800 whitespace-pre-line">-</p>
                </div>
                <div>
                    <p class="text-gray-500">Responsibilities</p>
                    <p id="postingViewResponsibilities" class="mt-1 text-gray-800 whitespace-pre-line">-</p>
                </div>
            </div>
            <div>
                <p class="text-gray-500">Application Requirements</p>
                <ul id="postingViewRequirements" class="mt-2 list-disc pl-6 text-gray-800 space-y-1"></ul>
            </div>
            <div>
                <p class="text-gray-500 mb-2">Applicants</p>
                <div class="overflow-x-auto border rounded-lg">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="text-left px-3 py-2">Applicant</th>
                                <th class="text-left px-3 py-2">Applied Position</th>
                                <th class="text-left px-3 py-2">Date Submitted</th>
                                <th class="text-left px-3 py-2">Initial Screening</th>
                                <th class="text-left px-3 py-2">Basis</th>
                                <th class="text-left px-3 py-2">Action</th>
                            </tr>
                        </thead>
                        <tbody id="postingViewApplicantsBody" class="divide-y"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t bg-white flex justify-end">
            <button type="button" id="postingViewModalCancel" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Close</button>
        </div>
    </div>
</div>

<div id="staffApplicantDecisionModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-staff-applicant-modal-close="staffApplicantDecisionModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Applicant Profile</h3>
                <button type="button" data-staff-applicant-modal-close="staffApplicantDecisionModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <form action="recruitment.php" method="post" class="flex-1 min-h-0 flex flex-col" id="staffApplicantDecisionForm">
                <input type="hidden" name="form_action" value="save_applicant_decision">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="staffDecisionApplicationId" name="application_id" value="" required>
                <div class="flex-1 min-h-0 overflow-y-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase text-slate-500 tracking-wide">Selected Applicant</p>
                        <p id="staffApplicantProfileName" class="text-base font-semibold text-slate-800 mt-1">-</p>
                        <p id="staffApplicantProfileMeta" class="text-sm text-slate-600 mt-1">-</p>
                        <div id="staffApplicantEmployeeAction" class="mt-3"></div>
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
                    <div class="md:col-span-2">
                        <p class="text-slate-600">Submitted Documents</p>
                        <div class="mt-2 overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="text-left px-3 py-2">Document Type</th>
                                        <th class="text-left px-3 py-2">File Name</th>
                                        <th class="text-left px-3 py-2">Uploaded</th>
                                        <th class="text-left px-3 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="staffApplicantDocumentsBody" class="divide-y divide-slate-100">
                                    <tr><td class="px-3 py-3 text-slate-500" colspan="4">No document selected.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-staff-applicant-modal-close="staffApplicantDecisionModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Decision</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script id="recruitmentPostingViewData" type="application/json"><?= (string)json_encode($postingViewById, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<script src="../../assets/js/staff/recruitment/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
