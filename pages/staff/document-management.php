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
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Document Management</h1>
            <p class="text-sm text-gray-500">Review active documents, submit recommendations to admin for final approval/rejection, manage upload categories, and track HR document requests.</p>
        </div>
        <button type="button" id="openCategoryCreateModal" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 whitespace-nowrap">
            <span class="material-symbols-outlined text-[18px]">create_new_folder</span>
            Create Upload Category
        </button>
    </div>
</div>

<?php if ($state && $message): ?>
    <?php if ($state === 'success'): ?>
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php else: ?>
        <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($dataLoadError): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-3">
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Active Documents</p>
        <p class="text-2xl font-bold text-emerald-800 mt-1"><?= htmlspecialchars((string)($activeDocumentCount ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="rounded-xl border border-slate-300 bg-slate-100 px-4 py-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Archived Documents</p>
        <p class="text-2xl font-bold text-slate-700 mt-1"><?= htmlspecialchars((string)($archivedDocumentCount ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Employee Document Registry</h2>
        <p class="text-sm text-gray-500 mt-1">Active records only. Staff submits recommendation to admin; admin has final approve/reject authority.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="md:col-span-2">
            <label for="documentSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="documentSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by document title, employee, category, or status">
        </div>
        <div>
            <label for="documentCategoryFilter" class="text-sm text-gray-600">Category</label>
            <select id="documentCategoryFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Categories</option>
                <?php foreach ($documentCategoryOptions as $type): ?>
                    <option value="<?= htmlspecialchars(strtolower($type), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="documentStatusFilter" class="text-sm text-gray-600">All Statuses</label>
            <select id="documentStatusFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="submitted" <?= $selectedDocumentStatus === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                <option value="approved" <?= $selectedDocumentStatus === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $selectedDocumentStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
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
                        <td class="px-4 py-3 text-gray-500" colspan="7">No active document records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($documentRows as $row): ?>
                        <tr
                            data-doc-row
                            data-doc-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-status="<?= htmlspecialchars((string)($row['status_raw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-doc-category="<?= htmlspecialchars(strtolower((string)($row['category_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['title'] ?? 'Document'), ENT_QUOTES, 'UTF-8') ?></p>
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
                                <div class="flex items-center gap-2">
                                    <?php $rowStatusRaw = strtolower((string)($row['status_raw'] ?? '')); ?>
                                    <?php if (!empty($row['can_recommend'])): ?>
                                        <button
                                            type="button"
                                            data-open-review-modal
                                            data-document-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-document-title="<?= htmlspecialchars((string)($row['title'] ?? 'Document'), ENT_QUOTES, 'UTF-8') ?>"
                                            data-current-status="<?= htmlspecialchars((string)($row['status_label'] ?? 'Draft'), ENT_QUOTES, 'UTF-8') ?>"
                                            data-previous-recommendation="<?= htmlspecialchars((string)($row['previous_recommendation'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-previous-notes="<?= htmlspecialchars((string)($row['previous_recommendation_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-document-view-url="<?= htmlspecialchars((string)($row['preview_url'] ?? $row['view_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-document-download-url="<?= htmlspecialchars((string)($row['download_url'] ?? $row['view_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100"
                                        >
                                            <span class="material-symbols-outlined text-[14px]">fact_check</span>
                                            Review
                                        </button>
                                    <?php endif; ?>

                                    <button
                                        type="button"
                                        data-open-audit-modal
                                        data-document-title="<?= htmlspecialchars((string)($row['title'] ?? 'Document'), ENT_QUOTES, 'UTF-8') ?>"
                                        data-audit-trail='<?= htmlspecialchars((string)json_encode((array)($row['audit_trail'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"
                                    >
                                        <span class="material-symbols-outlined text-[14px]">history</span>
                                        Audit
                                    </button>

                                    <button
                                        type="button"
                                        data-open-archive-modal
                                        data-document-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-document-title="<?= htmlspecialchars((string)($row['title'] ?? 'Document'), ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status="<?= htmlspecialchars((string)($row['status_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"
                                    >
                                        <span class="material-symbols-outlined text-[14px]">inventory_2</span>
                                        Archive
                                    </button>
                                </div>
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

    <div class="px-6 pb-5 flex items-center justify-between gap-3 border-t" data-pagination-controls="staffDocumentTable">
        <p class="text-xs text-gray-500" data-pagination-label>Showing 0 of 0</p>
        <div class="flex items-center gap-2">
            <button type="button" class="px-3 py-1.5 text-xs border rounded-md text-gray-700 hover:bg-gray-50" data-pagination-prev>Previous</button>
            <span class="text-xs text-gray-600" data-pagination-page>Page 1</span>
            <button type="button" class="px-3 py-1.5 text-xs border rounded-md text-gray-700 hover:bg-gray-50" data-pagination-next>Next</button>
        </div>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Pending Staff Review</h2>
        <p class="text-sm text-gray-500 mt-1">Queue for records still in staff recommendation stage before admin final review.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
            <label for="pendingStaffReviewSearch" class="text-sm text-gray-600">Search Requests</label>
            <input id="pendingStaffReviewSearch" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by document title, employee, category, or status">
        </div>
        <div>
            <label for="pendingStaffReviewCategory" class="text-sm text-gray-600">Category</label>
            <select id="pendingStaffReviewCategory" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Categories</option>
                <?php foreach ($documentCategoryOptions as $type): ?>
                    <option value="<?= htmlspecialchars(strtolower($type), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="pendingStaffReviewTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Document</th>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Submitted</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($pendingStaffReviewRows)): ?>
                    <tr data-pending-empty-static>
                        <td class="px-4 py-3 text-gray-500" colspan="6">No pending records for staff review.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pendingStaffReviewRows as $row): ?>
                        <tr
                            data-pending-row
                            data-pending-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-pending-category="<?= htmlspecialchars(strtolower((string)($row['category_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($row['title'] ?? 'Document'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['owner_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['category_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['submitted_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)($row['status_label'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <?php if (!empty($row['can_recommend'])): ?>
                                    <button
                                        type="button"
                                        data-open-review-modal
                                        data-document-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-document-title="<?= htmlspecialchars((string)($row['title'] ?? 'Document'), ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status="<?= htmlspecialchars((string)($row['status_label'] ?? 'Draft'), ENT_QUOTES, 'UTF-8') ?>"
                                        data-previous-recommendation="<?= htmlspecialchars((string)($row['previous_recommendation'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-previous-notes="<?= htmlspecialchars((string)($row['previous_recommendation_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-document-view-url="<?= htmlspecialchars((string)($row['preview_url'] ?? $row['view_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-document-download-url="<?= htmlspecialchars((string)($row['download_url'] ?? $row['view_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100"
                                    >
                                        <span class="material-symbols-outlined text-[14px]">fact_check</span>
                                        Review
                                    </button>
                                <?php else: ?>
                                    <span class="text-xs text-gray-500">Locked</span>
                                <?php endif; ?>
                                <button
                                    type="button"
                                    data-open-audit-modal
                                    data-document-title="<?= htmlspecialchars((string)($row['title'] ?? 'Document'), ENT_QUOTES, 'UTF-8') ?>"
                                    data-audit-trail='<?= htmlspecialchars((string)json_encode((array)($row['audit_trail'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"
                                >
                                    <span class="material-symbols-outlined text-[14px]">history</span>
                                    Audit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="pendingStaffReviewFilterEmpty" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="6">No records match your current search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="px-6 pb-5 flex items-center justify-between gap-3 border-t" data-pagination-controls="pendingStaffReviewTable">
        <p class="text-xs text-gray-500" data-pagination-label>Showing 0 of 0</p>
        <div class="flex items-center gap-2">
            <button type="button" class="px-3 py-1.5 text-xs border rounded-md text-gray-700 hover:bg-gray-50" data-pagination-prev>Previous</button>
            <span class="text-xs text-gray-600" data-pagination-page>Page 1</span>
            <button type="button" class="px-3 py-1.5 text-xs border rounded-md text-gray-700 hover:bg-gray-50" data-pagination-next>Next</button>
        </div>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Archived Documents</h2>
        <p class="text-sm text-gray-500 mt-1">Archived records are kept for retention and reference only. Viewing and downloading are disabled.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-5 gap-3">
        <div class="md:col-span-2">
            <label for="archivedDocumentSearchInput" class="text-sm text-gray-600">Search Requests</label>
            <input id="archivedDocumentSearchInput" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by document title, employee, or category">
        </div>
        <div>
            <label for="archivedDocumentCategoryFilter" class="text-sm text-gray-600">Category</label>
            <select id="archivedDocumentCategoryFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                <option value="">All Categories</option>
                <?php foreach ($documentCategoryOptions as $type): ?>
                    <option value="<?= htmlspecialchars(strtolower($type), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="archivedDocumentDateFrom" class="text-sm text-gray-600">Archived From</label>
            <input id="archivedDocumentDateFrom" type="date" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
        </div>
        <div>
            <label for="archivedDocumentDateTo" class="text-sm text-gray-600">Archived To</label>
            <input id="archivedDocumentDateTo" type="date" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="archivedDocumentTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Document</th>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Archived Status</th>
                    <th class="text-left px-4 py-3">Last Review</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($archivedDocumentRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="6">No archived documents found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($archivedDocumentRows as $row): ?>
                        <tr class="bg-slate-50 text-slate-500" data-archived-row data-archived-search="<?= htmlspecialchars((string)($row['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-archived-category="<?= htmlspecialchars(strtolower((string)($row['category_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" data-archived-date="<?= htmlspecialchars((string)($row['archived_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-600"><?= htmlspecialchars((string)($row['title'] ?? 'Document'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-slate-500 mt-1">Archived <?= htmlspecialchars((string)($row['archived_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['owner_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['category_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full bg-slate-300 text-slate-700">Archived</span>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['last_review'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <form method="POST" action="document-management.php" class="inline-flex">
                                    <input type="hidden" name="form_action" value="restore_document">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="document_id" value="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="restore_reason" value="" data-restore-reason>
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-md border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
                                        <span class="material-symbols-outlined text-[14px]">restore</span>
                                        Restore
                                    </button>
                                </form>
                                <button
                                    type="button"
                                    data-open-audit-modal
                                    data-document-title="<?= htmlspecialchars((string)($row['title'] ?? 'Document'), ENT_QUOTES, 'UTF-8') ?>"
                                    data-audit-trail='<?= htmlspecialchars((string)json_encode((array)($row['audit_trail'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'
                                    class="ml-2 inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"
                                >
                                    <span class="material-symbols-outlined text-[14px]">history</span>
                                    Audit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="archivedDocumentFilterEmpty" class="hidden">
                    <td class="px-4 py-3 text-gray-500" colspan="6">No archived records match your current search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="px-6 pb-5 flex items-center justify-between gap-3 border-t" data-pagination-controls="archivedDocumentTable">
        <p class="text-xs text-gray-500" data-pagination-label>Showing 0 of 0</p>
        <div class="flex items-center gap-2">
            <button type="button" class="px-3 py-1.5 text-xs border rounded-md text-gray-700 hover:bg-gray-50" data-pagination-prev>Previous</button>
            <span class="text-xs text-gray-600" data-pagination-page>Page 1</span>
            <button type="button" class="px-3 py-1.5 text-xs border rounded-md text-gray-700 hover:bg-gray-50" data-pagination-next>Next</button>
        </div>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">HR Document Requests</h2>
        <p class="text-sm text-gray-500 mt-1">Employee-submitted HR document requests with selected purpose and submission time.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Request</th>
                    <th class="text-left px-4 py-3">Requested By</th>
                    <th class="text-left px-4 py-3">Purpose</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Submitted</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($documentRequestRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="5">No HR document requests submitted yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($documentRequestRows as $requestRow): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($requestRow['request_type_label'] ?? 'HR Document Request'), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php if (trim((string)($requestRow['custom_request_label'] ?? '')) !== ''): ?>
                                    <p class="text-xs text-gray-500 mt-1">Custom: <?= htmlspecialchars((string)($requestRow['custom_request_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                                <?php if (trim((string)($requestRow['notes'] ?? '')) !== ''): ?>
                                    <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($requestRow['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($requestRow['requester_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <?= htmlspecialchars((string)($requestRow['purpose_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                <?php if (trim((string)($requestRow['other_purpose'] ?? '')) !== ''): ?>
                                    <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)($requestRow['other_purpose'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3"><span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800"><?= htmlspecialchars((string)($requestRow['status_label'] ?? 'Submitted'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($requestRow['submitted_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Document Uploaders</h2>
        <p class="text-sm text-gray-500 mt-1">View employee and applicant uploader activity, then open each account's compiled document list.</p>
    </header>

    <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3 border-b">
        <div class="md:col-span-2">
            <label for="uploaderTableSearch" class="text-sm text-gray-600">Search Uploaders</label>
            <input id="uploaderTableSearch" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by uploader name or email">
        </div>
        <div>
            <label class="text-sm text-gray-600">Account Type</label>
            <div class="mt-1 inline-flex rounded-md border overflow-hidden">
                <button type="button" class="px-3 py-2 text-xs bg-green-700 text-white" data-uploader-tab="all">All</button>
                <button type="button" class="px-3 py-2 text-xs bg-white text-gray-700 border-l" data-uploader-tab="employee">Employee</button>
                <button type="button" class="px-3 py-2 text-xs bg-white text-gray-700 border-l" data-uploader-tab="applicant">Applicant</button>
            </div>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="staffDocumentUploadersTable" class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Uploader</th>
                    <th class="text-left px-4 py-3">Account Type</th>
                    <th class="text-left px-4 py-3">Total Uploads</th>
                    <th class="text-left px-4 py-3">Last Upload</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($uploaderSummaryRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="5">No uploader activity found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($uploaderSummaryRows as $uploader): ?>
                        <?php
                        $displayName = cleanText($uploader['display_name'] ?? null) ?? 'Unknown User';
                        $accountType = strtolower((string)(cleanText($uploader['account_type'] ?? null) ?? 'unknown'));
                        $email = cleanText($uploader['email'] ?? null) ?? '-';
                        $totalUploads = (int)($uploader['total_uploads'] ?? 0);
                        $lastUploadLabel = cleanText($uploader['last_uploaded_label'] ?? null) ?? '-';
                        $documentsJson = json_encode((array)($uploader['documents'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                        $accountTypeClass = match ($accountType) {
                            'employee' => 'bg-emerald-100 text-emerald-700',
                            'applicant' => 'bg-amber-100 text-amber-700',
                            default => 'bg-slate-100 text-slate-700',
                        };
                        $accountTypeLabel = ucfirst($accountType);
                        ?>
                        <tr data-uploader-row data-uploader-type="<?= htmlspecialchars($accountType, ENT_QUOTES, 'UTF-8') ?>" data-uploader-search="<?= htmlspecialchars((string)($uploader['search_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center justify-center min-w-[90px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($accountTypeClass, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($accountTypeLabel, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars((string)$totalUploads, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($lastUploadLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-open-uploader-modal
                                    data-uploader-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                    data-uploader-type="<?= htmlspecialchars($accountTypeLabel, ENT_QUOTES, 'UTF-8') ?>"
                                    data-uploader-documents='<?= htmlspecialchars((string)$documentsJson, ENT_QUOTES, 'UTF-8') ?>'
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
                                >
                                    <span class="material-symbols-outlined text-[15px]">folder_open</span>
                                    View Documents
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="px-6 pb-5 flex items-center justify-between gap-3 border-t" data-pagination-controls="staffDocumentUploadersTable">
        <p class="text-xs text-gray-500" data-pagination-label>Showing 0 of 0</p>
        <div class="flex items-center gap-2">
            <button type="button" class="px-3 py-1.5 text-xs border rounded-md text-gray-700 hover:bg-gray-50" data-pagination-prev>Previous</button>
            <span class="text-xs text-gray-600" data-pagination-page>Page 1</span>
            <button type="button" class="px-3 py-1.5 text-xs border rounded-md text-gray-700 hover:bg-gray-50" data-pagination-next>Next</button>
        </div>
    </div>
</section>

<div id="documentReviewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Recommend Document Decision</h3>
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
                <button
                    type="button"
                    id="reviewViewDocumentButton"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"
                >
                    <span class="material-symbols-outlined text-[15px]">visibility</span>
                    View Document
                </button>
            </div>

            <div>
                <label for="reviewStatusSelect" class="text-sm text-gray-600">Recommendation</label>
                <select id="reviewStatusSelect" name="review_status" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" required>
                    <option value="">Select recommendation</option>
                    <option value="approved">Recommend Approval</option>
                    <option value="rejected">Recommend Rejection</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Final approval/rejection is handled by admin.</p>
            </div>

            <div>
                <label for="reviewNotesInput" class="text-sm text-gray-600">Review Notes</label>
                <textarea id="reviewNotesInput" name="review_notes" rows="3" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Recommendation notes for admin and submitter."></textarea>
            </div>

            <div class="flex items-center justify-between pt-2">
                <button type="button" id="documentReviewModalCancel" class="px-4 py-2 border rounded-md text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="documentReviewSubmit" class="px-4 py-2 rounded-md bg-green-700 text-white text-sm hover:bg-green-800">Send Recommendation</button>
            </div>
        </form>
    </div>
</div>

<div id="documentArchiveModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Archive Document</h3>
            <button type="button" id="documentArchiveModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close archive modal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="documentArchiveForm" method="POST" action="document-management.php" class="px-6 py-4 space-y-4">
            <input type="hidden" name="form_action" value="archive_document">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="document_id" id="archiveDocumentId" value="">

            <div>
                <label class="text-sm text-gray-600">Document</label>
                <p id="archiveDocumentTitle" class="mt-1 text-sm font-medium text-gray-800">-</p>
            </div>

            <div>
                <label class="text-sm text-gray-600">Current Status</label>
                <p id="archiveDocumentCurrentStatus" class="mt-1 text-sm text-gray-700">-</p>
            </div>

            <div>
                <label for="archiveReasonInput" class="text-sm text-gray-600">Archive Notes</label>
                <textarea id="archiveReasonInput" name="archive_reason" rows="3" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Optional retention/archive notes."></textarea>
            </div>

            <div class="flex items-center justify-between pt-2">
                <button type="button" id="documentArchiveModalCancel" class="px-4 py-2 border rounded-md text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="documentArchiveSubmit" class="px-4 py-2 rounded-md bg-slate-800 text-white text-sm hover:bg-slate-900">Confirm Archive</button>
            </div>
        </form>
    </div>
</div>

<div id="documentAuditModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-3xl rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Document Audit Trail</h3>
                <p id="documentAuditTitle" class="text-sm text-gray-500 mt-1">-</p>
            </div>
            <button type="button" id="documentAuditModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close audit modal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="px-6 py-4 max-h-[65vh] overflow-y-auto">
            <div id="documentAuditBody" class="space-y-3 text-sm">
                <p class="text-gray-500">No audit trail entries available.</p>
            </div>
        </div>
        <div class="px-6 py-4 border-t flex justify-end">
            <button type="button" id="documentAuditModalCancel" class="px-4 py-2 border rounded-md text-sm text-gray-700 hover:bg-gray-50">Close</button>
        </div>
    </div>
</div>

<div id="documentCategoryModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-xl bg-white border shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Create Upload Category</h3>
            <button type="button" id="documentCategoryModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close category modal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="document-management.php" class="px-6 py-4 space-y-4">
            <input type="hidden" name="form_action" value="create_document_category">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div>
                <label for="staffDocumentCategoryName" class="text-sm text-gray-600">Category Name</label>
                <input id="staffDocumentCategoryName" name="category_name" type="text" maxlength="80" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Examples: Certification, Compliance Letter" required>
                <p class="text-xs text-gray-500 mt-1">Invalid placeholder values such as haugafia are blocked.</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" id="documentCategoryModalCancel" class="px-4 py-2 border rounded-md text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-md bg-green-700 text-white text-sm hover:bg-green-800">Create Category</button>
            </div>
        </form>
    </div>
</div>

<div id="uploaderDocumentsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-6xl max-h-[90vh] rounded-xl bg-white border shadow-lg overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <div>
                <h3 id="uploaderDocumentsTitle" class="text-lg font-semibold text-gray-800">Uploaded Documents</h3>
                <p id="uploaderDocumentsMeta" class="text-xs text-gray-500 mt-1">-</p>
            </div>
            <button type="button" id="uploaderDocumentsModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close uploader documents modal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="px-6 pt-4 pb-3 grid grid-cols-1 md:grid-cols-3 gap-3 border-b">
            <div class="md:col-span-2">
                <label for="uploaderModalSearch" class="text-sm text-gray-600">Search Requests</label>
                <input id="uploaderModalSearch" type="search" class="w-full mt-1 border rounded-md px-3 py-2 text-sm" placeholder="Search by document, category, source, or status">
            </div>
            <div>
                <label for="uploaderModalCategoryFilter" class="text-sm text-gray-600">Category</label>
                <select id="uploaderModalCategoryFilter" class="w-full mt-1 border rounded-md px-3 py-2 text-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($document201Types as $type): ?>
                        <option value="<?= htmlspecialchars(strtolower($type), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="p-6 overflow-auto flex-1 min-h-0">
            <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-4 py-3">Type</th>
                        <th class="text-left px-4 py-3">Document</th>
                        <th class="text-left px-4 py-3">Category</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Last Upload</th>
                        <th class="text-left px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody id="uploaderDocumentsBody" class="divide-y">
                    <tr>
                        <td class="px-4 py-3 text-gray-500" colspan="6">No documents found for this uploader.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t flex justify-end">
            <button type="button" id="uploaderDocumentsModalCancel" class="px-4 py-2 rounded-md bg-green-700 text-white text-sm hover:bg-green-800">Close</button>
        </div>
    </div>
</div>

<div id="documentPreviewModal" class="fixed inset-0 z-60 hidden items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-6xl max-h-[92vh] rounded-xl bg-white border shadow-lg overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b gap-4">
            <div class="min-w-0">
                <h3 id="documentPreviewTitle" class="text-lg font-semibold text-gray-800 truncate">Document Preview</h3>
                <p class="text-xs text-gray-500 mt-1">Preview supported files without leaving the page.</p>
            </div>
            <button type="button" id="documentPreviewModalClose" class="text-gray-500 hover:text-gray-700" aria-label="Close preview modal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="px-6 py-3 border-b flex items-center justify-between gap-3">
            <p id="documentPreviewStatusText" class="text-xs text-gray-500">Loading preview…</p>
            <a id="documentPreviewDownloadButton" href="#" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100">
                <span class="material-symbols-outlined text-[15px]">download</span>
                Download File
            </a>
        </div>
        <div class="flex-1 min-h-0 bg-slate-50">
            <iframe id="documentPreviewFrame" class="w-full h-full border-0 bg-white" title="Document Preview"></iframe>
        </div>
        <div class="px-6 py-4 border-t flex justify-end">
            <button type="button" id="documentPreviewModalCancel" class="px-4 py-2 rounded-md bg-slate-800 text-white text-sm hover:bg-slate-900">Close</button>
        </div>
    </div>
</div>

<script src="../../assets/js/staff/document-management/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
