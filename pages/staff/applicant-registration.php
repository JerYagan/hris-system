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
    <p class="text-sm text-gray-500">Review incoming applications and submit verification-forwarding decisions.</p>
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
        <h2 class="text-lg font-semibold text-gray-800">Registration Queue</h2>
        <p class="text-sm text-gray-500 mt-1">Search and filter application registrations, then apply verification decisions.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="registrationSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="registrationSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search applicant, posting, email, status">
        </div>
        <div>
            <label for="registrationStatusFilter" class="text-sm text-gray-600">All Statuses</label>
            <select id="registrationStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="submitted">Submitted</option>
                <option value="screening">Screening</option>
                <option value="shortlisted">Shortlisted</option>
                <option value="interview">Interview</option>
                <option value="offer">Offer</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="registrationTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Posting</th>
                    <th class="text-left px-4 py-3">Submitted</th>
                    <th class="text-left px-4 py-3">Documents</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($registrationRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="6">No registration records found in your office scope.</td>
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
                            <td class="px-4 py-3"><?= (int)($row['document_count'] ?? 0) ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-open-registration-modal
                                    data-application-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-applicant-name="<?= htmlspecialchars((string)($row['applicant_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-posting-title="<?= htmlspecialchars((string)($row['posting_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-current-status-label="<?= htmlspecialchars((string)($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    class="px-3 py-1.5 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100"
                                >
                                    Review
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

<div id="registrationModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Review Registration Decision</h3>
            <button type="button" id="registrationModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close modal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="registrationForm" method="POST" action="applicant-registration.php" class="px-6 py-4 space-y-4 text-sm">
            <input type="hidden" name="form_action" value="registration_decision">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="application_id" id="registrationApplicationId" value="">

            <div>
                <label class="text-gray-600">Applicant</label>
                <p id="registrationApplicantName" class="mt-1 text-sm font-medium text-gray-800">-</p>
            </div>
            <div>
                <label class="text-gray-600">Posting</label>
                <p id="registrationPostingTitle" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label class="text-gray-600">Current Status</label>
                <p id="registrationCurrentStatus" class="mt-1 text-sm text-gray-700">-</p>
            </div>
            <div>
                <label for="registrationNewStatus" class="text-gray-600">Decision</label>
                <select id="registrationNewStatus" name="new_status" class="w-full mt-1 border rounded-md px-3 py-2" required>
                    <option value="">Select decision</option>
                    <option value="screening">Move to Screening</option>
                    <option value="shortlisted">Forward to Shortlisted</option>
                    <option value="rejected">Reject Application</option>
                </select>
            </div>
            <div>
                <label for="registrationDecisionNotes" class="text-gray-600">Notes</label>
                <textarea id="registrationDecisionNotes" name="decision_notes" rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Add verification notes or endorsement remarks."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="registrationModalCancel" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="registrationSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Decision</button>
            </div>
        </form>
    </div>
</div>

<script src="../../assets/js/staff/applicant-registration/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
