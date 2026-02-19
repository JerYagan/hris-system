<?php
require_once __DIR__ . '/includes/document-management/bootstrap.php';
require_once __DIR__ . '/includes/document-management/actions.php';
require_once __DIR__ . '/includes/document-management/data.php';

$pageTitle = 'Document Management | Staff';
$activePage = 'document-management.php';
$breadcrumbs = ['Document Management'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Document Management</h1>
    <p class="text-sm text-gray-500">Review submitted employee documents with office-scoped filters and controlled status transitions.</p>
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
        <h2 class="text-lg font-semibold text-gray-800">Employee Document Registry</h2>
        <p class="text-sm text-gray-500 mt-1">View all employee documents in scope, filter 201 files, and submit review decisions through the standard modal flow.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-5 gap-3">
        <div class="md:col-span-2">
            <label for="documentSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="documentSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by document title, employee, category, or status">
        </div>
        <div>
            <label for="documentCategoryFilter" class="text-sm text-gray-600">Category</label>
            <select id="documentCategoryFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Categories</option>
                <?php foreach ($documentCategoryOptions as $category): ?>
                    <?php $categoryName = cleanText($category['category_name'] ?? null) ?? ''; ?>
                    <?php if ($categoryName === '') { continue; } ?>
                    <option value="<?= htmlspecialchars(strtolower($categoryName), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="documentStatusFilter" class="text-sm text-gray-600">All Statuses</label>
            <select id="documentStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="draft" <?= $selectedDocumentStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="submitted" <?= $selectedDocumentStatus === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                <option value="approved" <?= $selectedDocumentStatus === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $selectedDocumentStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="archived" <?= $selectedDocumentStatus === 'archived' ? 'selected' : '' ?>>Archived</option>
            </select>
        </div>
        <div>
            <label for="documentGroupFilter" class="text-sm text-gray-600">Document Group</label>
            <select id="documentGroupFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="all">All Documents</option>
                <option value="201">201 Files Only</option>
            </select>
        </div>
        <div>
            <label for="document201TypeFilter" class="text-sm text-gray-600">201 File Type</label>
            <select id="document201TypeFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All 201 Types</option>
                <?php foreach ($document201Types as $type): ?>
                    <option value="<?= htmlspecialchars(strtolower($type), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="staffDocumentTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Document</th>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Submitted</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Last Review</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($documentRows)): ?>
                    <tr data-doc-empty-static>
                        <td class="px-4 py-3 text-gray-500" colspan="7">No document records found in your current scope.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($documentRows as $row): ?>
                        <tr
                            data-doc-row
                            data-doc-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-category="<?= htmlspecialchars(strtolower((string)($row['category_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-group="<?= !empty($row['is_201_file']) ? '201' : 'other' ?>"
                            data-doc-201-type="<?= htmlspecialchars(strtolower((string)($row['document_201_type'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['title'] ?? 'Document'), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php if (!empty($row['is_201_file'])): ?>
                                    <p class="text-xs text-emerald-700 mt-1">201 File · <?= htmlspecialchars((string)($row['document_201_type'] ?? 'General'), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                                <p class="text-xs text-gray-500 mt-1">Updated <?= htmlspecialchars((string)($row['updated_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['owner_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['category_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['submitted_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)($row['status_label'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['last_review'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <?php $rowStatusRaw = strtolower((string)($row['status_raw'] ?? '')); ?>
                                <?php if ($rowStatusRaw === 'submitted' || $rowStatusRaw === 'draft'): ?>
                                    <button
                                        type="button"
                                        data-open-review-modal
                                        data-document-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-document-title="<?= htmlspecialchars((string)($row['title'] ?? 'Document'), ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status="<?= htmlspecialchars((string)($row['status_label'] ?? 'Draft'), ENT_QUOTES, 'UTF-8') ?>"
                                        class="px-3 py-1.5 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100"
                                    >
                                        Review
                                    </button>
                                <?php else: ?>
                                    <span class="text-xs text-gray-500">No review action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="documentFilterEmptyRow" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="7">No records match your current search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="px-6 pb-6 text-xs text-gray-500">Tip: Use <strong>Document Group</strong> and <strong>201 File Type</strong> to isolate employee 201 files such as PDS, SSS, Pagibig, Philhealth, and related records.</p>
</section>

<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Document Categories in Use</h2>
        <p class="text-sm text-gray-500 mt-1">Reference categories currently configured for uploads and verification.</p>
    </header>

    <div class="p-6 flex flex-wrap gap-2">
        <?php if (empty($documentCategoryOptions)): ?>
            <span class="text-sm text-gray-500">No categories found.</span>
        <?php else: ?>
            <?php foreach ($documentCategoryOptions as $category): ?>
                <?php $categoryName = cleanText($category['category_name'] ?? null) ?? ''; ?>
                <?php if ($categoryName === '') { continue; } ?>
                <span class="px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-700 border"><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<div id="documentReviewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Review Document Request</h3>
            <button type="button" id="documentReviewModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close review modal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="documentReviewForm" method="POST" action="document-management.php" class="px-6 py-4 space-y-4">
            <input type="hidden" name="form_action" value="review_document">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="document_id" id="reviewDocumentId" value="">

            <div>
                <label class="text-sm text-gray-600">Document</label>
                <p id="reviewDocumentTitle" class="mt-1 text-sm font-medium text-gray-800">-</p>
            </div>

            <div>
                <label class="text-sm text-gray-600">Current Status</label>
                <p id="reviewDocumentCurrentStatus" class="mt-1 text-sm text-gray-700">-</p>
            </div>

            <div>
                <label for="reviewStatusSelect" class="text-sm text-gray-600">Decision</label>
                <select id="reviewStatusSelect" name="review_status" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" required>
                    <option value="">Select decision</option>
                    <option value="approved">Approve</option>
                    <option value="rejected">Reject</option>
                    <option value="needs_revision">Needs Revision</option>
                </select>
            </div>

            <div>
                <label for="reviewNotesInput" class="text-sm text-gray-600">Review Notes</label>
                <textarea id="reviewNotesInput" name="review_notes" rows="3" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Optional notes for the submitter."></textarea>
            </div>

            <div class="flex items-center justify-between pt-2">
                <button type="button" id="documentReviewModalCancel" class="px-4 py-2 border rounded-md text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="documentReviewSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white text-sm hover:bg-green-800">Save Decision</button>
            </div>
        </form>
    </div>
</div>

<script src="../../assets/js/staff/document-management/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
