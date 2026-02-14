<?php
$docStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    if ($key === 'approved') {
        return ['Approved', 'bg-emerald-100 text-emerald-800'];
    }
    if ($key === 'rejected') {
        return ['Rejected', 'bg-rose-100 text-rose-800'];
    }
    if ($key === 'submitted') {
        return ['Submitted', 'bg-amber-100 text-amber-800'];
    }
    if ($key === 'archived') {
        return ['Archived', 'bg-slate-200 text-slate-700'];
    }

    return ['Draft', 'bg-blue-100 text-blue-800'];
};

$reviewStatusLabel = static function (string $status): string {
    $key = trim($status);
    if ($key === '') {
        return '-';
    }
    return ucwords(str_replace('_', ' ', $key));
};
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Document Management</h1>
        <p class="text-sm text-slate-300 mt-2">Review uploaded records, maintain lifecycle status, and archive completed documents using modal actions.</p>
    </div>
</div>

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

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm flex gap-2">
        <span class="material-symbols-outlined text-base">error</span>
        <span><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Document Registry</h2>
            <p class="text-sm text-slate-500 mt-1">Search and filter uploaded records, then apply review or archive updates through modals.</p>
        </div>
        <a href="report-analytics.php" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Open Reports</a>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Documents</label>
            <input id="documentManagementSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by title, owner, category, or uploader email">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="documentManagementStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="Draft">Draft</option>
                <option value="Submitted">Submitted</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
                <option value="Archived">Archived</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="documentManagementTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-5 py-3.5">Document</th>
                    <th class="text-left px-5 py-3.5">Owner</th>
                    <th class="text-left px-5 py-3.5">Category</th>
                    <th class="text-left px-5 py-3.5">Status</th>
                    <th class="text-left px-5 py-3.5">Last Review</th>
                    <th class="text-left px-5 py-3.5">Updated</th>
                    <th class="text-left px-5 py-3.5">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($documents)): ?>
                    <tr>
                        <td class="px-5 py-4 text-slate-500" colspan="7">No document records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($documents as $document): ?>
                        <?php
                        $documentId = (string)($document['id'] ?? '');
                        $title = (string)($document['title'] ?? '-');
                        $bucket = (string)($document['storage_bucket'] ?? '');
                        $path = (string)($document['storage_path'] ?? '');
                        $versionNo = (int)($document['current_version_no'] ?? 1);
                        $category = (string)($document['category']['category_name'] ?? 'Uncategorized');
                        $ownerFirst = trim((string)($document['owner']['first_name'] ?? ''));
                        $ownerLast = trim((string)($document['owner']['surname'] ?? ''));
                        $ownerName = trim($ownerFirst . ' ' . $ownerLast);
                        if ($ownerName === '') {
                            $ownerName = 'Unknown Owner';
                        }

                        $uploaderEmail = (string)($document['uploader']['email'] ?? '-');
                        $statusRaw = (string)($document['document_status'] ?? 'draft');
                        [$statusLabel, $statusClass] = $docStatusPill($statusRaw);

                        $review = $latestReviewByDocument[$documentId] ?? null;
                        $lastReview = '-';
                        if (is_array($review)) {
                            $reviewStatus = $reviewStatusLabel((string)($review['status'] ?? ''));
                            $reviewedAt = (string)($review['reviewed_at'] ?? '');
                            if ($reviewedAt !== '') {
                                $lastReview = $reviewStatus;
                            } else {
                                $lastReview = $reviewStatus;
                            }
                        }

                        $updatedAt = (string)($document['updated_at'] ?? $document['created_at'] ?? '');
                        $updatedLabel = $updatedAt !== '' ? date('M d, Y', strtotime($updatedAt)) : '-';
                        $searchText = strtolower(trim($title . ' ' . $ownerName . ' ' . $category . ' ' . $uploaderEmail . ' ' . $statusLabel));
                        $storageLabel = trim(($bucket !== '' ? $bucket . '/' : '') . $path);
                        ?>
                        <tr class="align-top hover:bg-slate-50/60" data-doc-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-doc-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-5 py-4">
                                <div class="font-medium text-slate-800 leading-6"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-xs text-slate-500 leading-5 mt-1">Version <?= htmlspecialchars((string)$versionNo, ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars($storageLabel !== '' ? $storageLabel : '-', ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="px-5 py-4 leading-6"><?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-5 py-4 leading-6"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-5 py-4"><span class="inline-flex items-center justify-center min-w-[95px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-5 py-4 leading-6"><?= htmlspecialchars($lastReview, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-5 py-4 leading-6"><?= htmlspecialchars($updatedLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        data-doc-review
                                        data-document-id="<?= htmlspecialchars($documentId, ENT_QUOTES, 'UTF-8') ?>"
                                        data-document-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>"
                                        class="px-2.5 py-1.5 text-xs rounded-md border border-emerald-300 text-emerald-700 hover:bg-emerald-50"
                                    >
                                        Review
                                    </button>
                                    <button
                                        type="button"
                                        data-doc-archive
                                        data-document-id="<?= htmlspecialchars($documentId, ENT_QUOTES, 'UTF-8') ?>"
                                        data-document-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>"
                                        class="px-2.5 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50"
                                    >
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

<div id="reviewDocumentModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="reviewDocumentModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Review Document</h3>
                <button type="button" data-modal-close="reviewDocumentModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="document-management.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="review_document">
                <input type="hidden" id="reviewDocumentId" name="document_id" value="">
                <div class="md:col-span-2">
                    <label class="text-slate-600">Document</label>
                    <input id="reviewDocumentTitle" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Current Status</label>
                    <input id="reviewCurrentStatus" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Review Decision</label>
                    <select name="review_status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="approved">Approve</option>
                        <option value="rejected">Reject</option>
                        <option value="needs_revision">Needs Revision</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Review Notes</label>
                    <textarea name="review_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add remarks, missing requirements, or guidance."></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="reviewDocumentModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="archiveDocumentModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="archiveDocumentModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Archive Document</h3>
                <button type="button" data-modal-close="archiveDocumentModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="document-management.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="archive_document">
                <input type="hidden" id="archiveDocumentId" name="document_id" value="">
                <div class="md:col-span-2">
                    <label class="text-slate-600">Document</label>
                    <input id="archiveDocumentTitle" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Current Status</label>
                    <input id="archiveCurrentStatus" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Archive Reason</label>
                    <textarea name="archive_reason" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add retention reference, duplicate handling reason, or completion notes."></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="archiveDocumentModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Confirm Archive</button>
                </div>
            </form>
        </div>
    </div>
</div>
