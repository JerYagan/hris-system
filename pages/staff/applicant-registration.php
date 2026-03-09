<?php
require_once __DIR__ . '/includes/applicant-registration/bootstrap.php';
require_once __DIR__ . '/includes/applicant-registration/actions.php';
require_once __DIR__ . '/includes/applicant-registration/data.php';

$pageTitle = 'Applicant Registration | Staff';
$activePage = 'recruitment.php';
$breadcrumbs = ['Recruitment', 'Applicant Registration'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Applicant Registration</h1>
    <p class="text-sm text-gray-500">Read-only applicant registration review for profile and document checking.</p>
</div>

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
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">View Registered Applicants</h2>
        <p class="text-sm text-gray-500 mt-1">Review applicants by posting, submission date, and screening status.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="registrationSearchInput" class="text-sm text-gray-600">Search Applicants</label>
            <input id="registrationSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by applicant, email, or position">
        </div>
        <div>
            <label for="registrationStatusFilter" class="text-sm text-gray-600">Screening Filter</label>
            <select id="registrationStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All</option>
                <option value="submitted">Applied</option>
                <option value="screening">Verified</option>
                <option value="interview">Interview</option>
                <option value="shortlisted">Evaluation</option>
                <option value="offer">For Approval</option>
                <option value="hired">Hired</option>
                <option value="rejected">Rejected</option>
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
                        <tr data-registration-row data-registration-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-registration-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
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
                <tr id="registrationFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="6">No records match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<div id="registrationModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-registration-modal-close="registrationModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl max-h-[calc(100vh-2rem)] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Applicant Profile</h3>
                <button type="button" data-registration-modal-close="registrationModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <div id="registrationForm" class="flex-1 min-h-0 flex flex-col">
                <input type="hidden" id="registrationApplicationId" value="">

                <div class="flex-1 min-h-0 overflow-y-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase text-slate-500 tracking-wide">Selected Applicant</p>
                        <p id="registrationApplicantName" class="text-base font-semibold text-slate-800 mt-1">-</p>
                        <p id="registrationApplicantMeta" class="text-sm text-slate-600 mt-1">-</p>
                        <p id="registrationApplicantContact" class="text-xs text-slate-500 mt-2">-</p>
                        <p id="registrationApplicationRef" class="text-xs text-slate-500 mt-1">-</p>
                    </div>

                    <div class="md:col-span-2 rounded-lg border border-slate-200 bg-blue-50 px-4 py-3 text-slate-700">
                        Staff access is read-only in applicant registration. Review the applicant profile and submitted documents only.
                    </div>

                    <div class="md:col-span-2">
                        <p class="text-slate-600">Submitted Documents</p>
                        <div class="mt-2 overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="text-left px-3 py-2">File Name</th>
                                        <th class="text-left px-3 py-2">Uploaded</th>
                                        <th class="text-left px-3 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="registrationDocumentsBody" class="divide-y divide-slate-100">
                                    <tr><td class="px-3 py-3 text-slate-500" colspan="3">No document selected.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-registration-modal-close="registrationModal" class="px-4 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script id="registrationViewData" type="application/json"><?= (string)json_encode($registrationViewById, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<script src="../../assets/js/staff/applicant-registration/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
