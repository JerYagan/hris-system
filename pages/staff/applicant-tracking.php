<?php
require_once __DIR__ . '/includes/applicant-tracking/bootstrap.php';
require_once __DIR__ . '/includes/applicant-tracking/actions.php';
require_once __DIR__ . '/includes/applicant-tracking/data.php';

$pageTitle = 'Applicant Tracking | Staff';
$activePage = 'recruitment.php';
$breadcrumbs = ['Recruitment', 'Applicant Tracking'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Applicant Tracking</h1>
    <p class="text-sm text-gray-500">Track applicant progress and apply scheduling/status updates with consistent admin workflow patterns.</p>
</div>

<div id="trackingFlashState" class="hidden" data-state="<?= htmlspecialchars((string)($state ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-message="<?= htmlspecialchars((string)($message ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>

<?php if ($dataLoadError): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Application Progress</h2>
        <p class="text-sm text-gray-500 mt-1">Monitor applicant progress using current status only, with interview results and recruiter feedback visible.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="trackingSearchInput" class="text-sm text-gray-600">Search Applicants</label>
            <input id="trackingSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search applicant, posting, email, status">
        </div>
        <div>
            <label for="trackingStatusFilter" class="text-sm text-gray-600">Status Filter</label>
            <select id="trackingStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="Submitted">Submitted</option>
                <option value="Screening">Screening</option>
                <option value="Shortlisted">Shortlisted</option>
                <option value="Interview">Interview</option>
                <option value="Offer">Offer</option>
                <option value="Hired">Hired</option>
                <option value="Rejected">Rejected</option>
                <option value="Withdrawn">Withdrawn</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="trackingTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Posting</th>
                    <th class="text-left px-4 py-3">Submitted</th>
                    <th class="text-left px-4 py-3">Latest Interview</th>
                    <th class="text-left px-4 py-3">Interview & Feedback</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($trackingRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="7">No application records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($trackingRows as $row): ?>
                        <tr data-tracking-row data-tracking-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-tracking-status="<?= htmlspecialchars((string)($row['status_filter'] ?? strtolower((string)($row['status_label'] ?? 'submitted'))), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['applicant_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['applicant_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['posting_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['submitted_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['interview_meta'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <p class="text-xs text-slate-700 leading-5"><?= htmlspecialchars((string)($row['feedback_meta'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        data-track-schedule
                                        data-application-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-applicant-name="<?= htmlspecialchars((string)($row['applicant_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">event</span>Schedule
                                    </button>

                                    <button
                                        type="button"
                                        data-track-status-update
                                        data-application-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-applicant-name="<?= htmlspecialchars((string)($row['applicant_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status-label="<?= htmlspecialchars((string)($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-emerald-300 bg-emerald-50/50 text-emerald-700 hover:bg-emerald-50 shadow-sm"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">sync_alt</span>Update Status
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="trackingFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="7">No records match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="trackingPaginationInfo" class="text-xs text-slate-500">Page 1 of 1</p>
        <div class="flex items-center gap-2">
            <button type="button" id="trackingPrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <button type="button" id="trackingNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Screening & Next Steps Queue</h2>
        <p class="text-sm text-gray-500 mt-1">Applicants that passed initial application and are waiting for next recruitment steps.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="queueSearchInput" class="text-sm text-gray-600">Search Applicants</label>
            <input id="queueSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search applicant, posting, email, stage, status">
        </div>
        <div>
            <label for="queueStatusFilter" class="text-sm text-gray-600">Status Filter</label>
            <select id="queueStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Queue Statuses</option>
                <option value="Screening">Screening</option>
                <option value="Shortlisted">Shortlisted</option>
                <option value="Interview">Interview</option>
                <option value="Offer">Offer</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="queueTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Posting</th>
                    <th class="text-left px-4 py-3">Submitted</th>
                    <th class="text-left px-4 py-3">Latest Interview</th>
                    <th class="text-left px-4 py-3">Interview & Feedback</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Current Stage</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($screeningQueueRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="8">No applicants currently in screening and next-step queue.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($screeningQueueRows as $row): ?>
                        <tr data-queue-row data-queue-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-queue-status="<?= htmlspecialchars((string)($row['status_filter'] ?? strtolower((string)($row['status_label'] ?? 'submitted'))), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['applicant_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['applicant_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['posting_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['submitted_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['interview_meta'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><p class="text-xs text-slate-700 leading-5"><?= htmlspecialchars((string)($row['feedback_meta'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700"><?= htmlspecialchars((string)($row['current_stage_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        data-track-schedule
                                        data-application-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-applicant-name="<?= htmlspecialchars((string)($row['applicant_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">event</span>Schedule
                                    </button>
                                    <button
                                        type="button"
                                        data-track-status-update
                                        data-application-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-applicant-name="<?= htmlspecialchars((string)($row['applicant_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status-label="<?= htmlspecialchars((string)($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-emerald-300 bg-emerald-50/50 text-emerald-700 hover:bg-emerald-50 shadow-sm"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">sync_alt</span>Update Status
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="queueFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="8">No queue records match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="queuePaginationInfo" class="text-xs text-slate-500">Page 1 of 1</p>
        <div class="flex items-center gap-2">
            <button type="button" id="queuePrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <button type="button" id="queueNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Hired Applicants</h2>
        <p class="text-sm text-gray-500 mt-1">Convert hired applicants to employee records with one action.</p>
    </header>

    <div class="px-6 pt-4 pb-3">
        <label for="hiredSearchInput" class="text-sm text-gray-600">Search Hired Applicants</label>
        <input id="hiredSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search hired applicant, posting, email, feedback">
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="hiredTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Posting</th>
                    <th class="text-left px-4 py-3">Hired Date</th>
                    <th class="text-left px-4 py-3">Interview & Feedback</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($hiredApplicantRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="6">No hired applicants found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($hiredApplicantRows as $row): ?>
                        <tr data-hired-row data-hired-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['applicant_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['applicant_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['posting_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['updated_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><p class="text-xs text-slate-700 leading-5"><?= htmlspecialchars((string)($row['feedback_meta'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Hired'), ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <?php if (!empty($row['can_add_employee'])): ?>
                                    <form method="POST" action="applicant-tracking.php" data-add-employee-form class="inline-block">
                                        <input type="hidden" name="form_action" value="add_hired_applicant_as_employee">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="application_id" value="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-green-300 bg-green-50 text-green-700 hover:bg-green-100 shadow-sm" data-applicant-name="<?= htmlspecialchars((string)($row['applicant_name'] ?? 'Applicant'), ENT_QUOTES, 'UTF-8') ?>">
                                            <span class="material-symbols-outlined text-[15px]">badge</span>Add as Employee
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-[11px] rounded-full bg-slate-100 text-slate-600">Already Employee</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="hiredFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="6">No hired applicants match your search criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="px-6 pb-4 flex items-center justify-between gap-3">
        <p id="hiredPaginationInfo" class="text-xs text-slate-500">Page 1 of 1</p>
        <div class="flex items-center gap-2">
            <button type="button" id="hiredPrevPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</button>
            <button type="button" id="hiredNextPage" class="px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</button>
        </div>
    </div>
</section>

<div id="scheduleInterviewModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="scheduleInterviewModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Schedule Interview</h3>
                <button type="button" data-modal-close="scheduleInterviewModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form id="scheduleInterviewForm" action="applicant-tracking.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="schedule_interview">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="scheduleApplicationId" name="application_id" value="">
                <div class="md:col-span-2">
                    <label class="text-slate-600">Applicant</label>
                    <input id="scheduleApplicantName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Interview Stage</label>
                    <select name="interview_stage" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="hr">HR Interview</option>
                        <option value="technical">Technical Interview</option>
                        <option value="final">Final Interview</option>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Interview Mode</label>
                    <select name="interview_mode" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="onsite">Onsite</option>
                        <option value="online">Online</option>
                        <option value="phone">Phone</option>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Interview Date</label>
                    <input type="date" name="interview_date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                </div>
                <div>
                    <label class="text-slate-600">Interview Time</label>
                    <input type="time" name="interview_time" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Scheduling Notes</label>
                    <textarea name="schedule_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add panel details, instructions, or reschedule reason."></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="scheduleInterviewModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" id="scheduleInterviewSubmit" class="px-5 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="updateStatusModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="updateStatusModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Update Application Status</h3>
                <button type="button" data-modal-close="updateStatusModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form id="updateStatusForm" action="applicant-tracking.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="update_status">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="statusApplicationId" name="application_id" value="">
                <div class="md:col-span-2">
                    <label class="text-slate-600">Applicant</label>
                    <input id="statusApplicantName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Current Status</label>
                    <input id="statusCurrentStatus" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">New Status</label>
                    <select id="statusNewStatus" name="new_status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="submitted">Submitted</option>
                        <option value="screening">Screening</option>
                        <option value="shortlisted">Shortlisted</option>
                        <option value="interview">Interview</option>
                        <option value="offer">Offer</option>
                        <option value="hired">Hired</option>
                        <option value="rejected">Rejected</option>
                        <option value="withdrawn">Withdrawn</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Remarks</label>
                    <textarea name="status_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add decision rationale or update details."></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="updateStatusModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" id="updateStatusSubmit" class="px-5 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/hris-system/assets/js/staff/applicant-tracking/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
