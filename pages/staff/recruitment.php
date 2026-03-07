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
    <p class="text-sm text-gray-500">Read-only view of office-scoped job postings and applicant pipeline details.</p>
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
        <span class="inline-flex items-center gap-1.5 px-3 py-2 text-xs rounded-full bg-slate-100 text-slate-600">Staff read-only access</span>
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
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($activeRecruitmentRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="7">No active job postings found in your scope.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activeRecruitmentRows as $row): ?>
                        <tr data-recruitment-row data-recruitment-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-recruitment-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['position_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($row['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                <button
                                    type="button"
                                    data-open-posting-view-modal
                                    data-posting-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    class="mt-2 px-3 py-1.5 text-xs rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-100"
                                >
                                    View
                                </button>
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
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="recruitmentFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="7">No active postings match your search/filter criteria.</td>
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

            <div class="flex-1 min-h-0 flex flex-col" id="staffApplicantDecisionForm">
                <input type="hidden" id="staffDecisionApplicationId" value="">
                <div class="flex-1 min-h-0 overflow-y-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase text-slate-500 tracking-wide">Selected Applicant</p>
                        <p id="staffApplicantProfileName" class="text-base font-semibold text-slate-800 mt-1">-</p>
                        <p id="staffApplicantProfileMeta" class="text-sm text-slate-600 mt-1">-</p>
                        <div id="staffApplicantEmployeeAction" class="mt-3"></div>
                    </div>
                    <div class="md:col-span-2 rounded-lg border border-slate-200 bg-blue-50 px-4 py-3 text-slate-700">
                        Staff access in this module is read-only. Use this modal to review the applicant profile and submitted documents only.
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
                    <button type="button" data-staff-applicant-modal-close="staffApplicantDecisionModal" class="px-4 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script id="recruitmentPostingViewData" type="application/json"><?= (string)json_encode($postingViewById, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<script src="../../assets/js/staff/recruitment/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
