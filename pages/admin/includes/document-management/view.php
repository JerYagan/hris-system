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

$accountTypePill = static function (string $accountType): array {
    $key = strtolower(trim($accountType));
    if ($key === 'staff') {
        return ['Staff', 'bg-indigo-100 text-indigo-700'];
    }
    if ($key === 'employee') {
        return ['Employee', 'bg-emerald-100 text-emerald-700'];
    }
    if ($key === 'applicant') {
        return ['Applicant', 'bg-amber-100 text-amber-700'];
    }
    return [ucfirst($key !== '' ? $key : 'Unknown'), 'bg-slate-100 text-slate-700'];
};

$documentTypeMeta = static function (string $extension): array {
    $ext = strtolower(trim($extension));

    if ($ext === 'pdf') {
        return ['PDF', 'picture_as_pdf', 'bg-rose-100 text-rose-700'];
    }

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'], true)) {
        return ['Image', 'image', 'bg-blue-100 text-blue-700'];
    }

    if (in_array($ext, ['doc', 'docx'], true)) {
        return ['Word', 'description', 'bg-indigo-100 text-indigo-700'];
    }

    if (in_array($ext, ['xls', 'xlsx', 'csv'], true)) {
        return ['Sheet', 'table_chart', 'bg-emerald-100 text-emerald-700'];
    }

    if (in_array($ext, ['ppt', 'pptx'], true)) {
        return ['Slides', 'slideshow', 'bg-amber-100 text-amber-700'];
    }

    if (in_array($ext, ['mp4', 'webm', 'ogg', 'mp3', 'wav'], true)) {
        return ['Media', 'movie', 'bg-violet-100 text-violet-700'];
    }

    if (in_array($ext, ['txt', 'json', 'xml', 'md', 'log', 'sql', 'php', 'js', 'ts', 'html', 'css'], true)) {
        return ['Text', 'article', 'bg-slate-100 text-slate-700'];
    }

    return ['File', 'draft', 'bg-slate-100 text-slate-700'];
};

$buildStoragePublicUrl = static function (string $baseUrl, string $bucket, string $path): string {
    $base = rtrim(trim($baseUrl), '/');
    $bucketName = trim($bucket, '/');
    $objectPath = trim($path, '/');

    if ($base === '' || $bucketName === '' || $objectPath === '') {
        return '';
    }

    if (in_array(strtolower($bucketName), ['local_documents', 'local', 'filesystem'], true)) {
        $segments = array_values(array_filter(explode('/', preg_replace('#^document/#', '', $objectPath)), static fn(string $segment): bool => $segment !== ''));
        $encodedPath = implode('/', array_map('rawurlencode', $segments));
        return '/hris-system/storage/document/' . $encodedPath;
    }

    $segments = array_values(array_filter(explode('/', $objectPath), static fn(string $segment): bool => $segment !== ''));
    $encodedPath = implode('/', array_map('rawurlencode', $segments));

    return $base . '/storage/v1/object/public/' . rawurlencode($bucketName) . '/' . $encodedPath;
};
?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Upload Document to Local Storage</h2>
        <p class="text-sm text-slate-500 mt-1">Stores the actual file in <span class="font-medium text-slate-700">hris-system/storage/document/</span> and inserts metadata into the document tables.</p>
    </header>

    <form action="document-management.php" method="POST" enctype="multipart/form-data" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <input type="hidden" name="form_action" value="upload_document_file">

        <div>
            <label class="text-slate-600">Owner</label>
            <select name="owner_person_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                <option value="">Select owner</option>
                <?php foreach ($documentOwnerOptions as $owner): ?>
                    <?php
                    $ownerId = (string)($owner['id'] ?? '');
                    $ownerName = trim((string)($owner['surname'] ?? '') . ', ' . (string)($owner['first_name'] ?? ''));
                    if ($ownerId === '' || $ownerName === ',') {
                        continue;
                    }
                    ?>
                    <option value="<?= htmlspecialchars($ownerId, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="text-slate-600">Category</label>
            <select name="category_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                <option value="">Select category</option>
                <?php foreach ($documentCategoryOptions as $category): ?>
                    <?php
                    $categoryId = (string)($category['id'] ?? '');
                    $categoryName = (string)($category['category_name'] ?? '');
                    if ($categoryId === '' || $categoryName === '') {
                        continue;
                    }
                    ?>
                    <option value="<?= htmlspecialchars($categoryId, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="text-slate-600">Title</label>
            <input type="text" name="title" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="e.g. PDS - Personal Data Sheet" required>
        </div>

        <div>
            <label class="text-slate-600">Document File</label>
            <input type="file" name="document_file" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-white" required>
        </div>

        <div class="md:col-span-2">
            <label class="text-slate-600">Description</label>
            <textarea name="description" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Optional notes for this document."></textarea>
        </div>

        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="reset" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Upload & Save</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Document Uploaders</h2>
        <p class="text-sm text-slate-500 mt-1">View uploader accounts and open a full list of documents submitted by each user.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table id="documentUploadersTable" data-simple-table="true" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Uploader</th>
                    <th class="text-left px-4 py-3">Account Type</th>
                    <th class="text-left px-4 py-3">Total Uploads</th>
                    <th class="text-left px-4 py-3">Last Upload</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($uploaderSummaryRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="5">No uploader activity found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($uploaderSummaryRows as $uploader): ?>
                        <?php
                        $uploaderEmail = (string)($uploader['email'] ?? 'Unknown Email');
                        $uploaderType = (string)($uploader['account_type'] ?? 'unknown');
                        [$uploaderTypeLabel, $uploaderTypeClass] = $accountTypePill($uploaderType);
                        $totalUploads = (int)($uploader['total_uploads'] ?? 0);
                        $lastUpload = (string)($uploader['last_uploaded_label'] ?? '-');
                        $documentsJson = json_encode((array)($uploader['documents'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        ?>
                        <tr>
                            <td class="px-4 py-3 text-slate-800 font-medium"><?= htmlspecialchars($uploaderEmail, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[95px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($uploaderTypeClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($uploaderTypeLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars((string)$totalUploads, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($lastUpload, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-doc-uploader-open
                                    data-uploader-email="<?= htmlspecialchars($uploaderEmail, ENT_QUOTES, 'UTF-8') ?>"
                                    data-uploader-type="<?= htmlspecialchars($uploaderTypeLabel, ENT_QUOTES, 'UTF-8') ?>"
                                    data-uploader-documents='<?= htmlspecialchars((string)$documentsJson, ENT_QUOTES, 'UTF-8') ?>'
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"
                                >
                                    <span class="material-symbols-outlined text-[15px]">folder_open</span>View Documents
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

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
                        $documentUrl = $buildStoragePublicUrl((string)$supabaseUrl, $bucket, $path);
                        $extension = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
                        [$typeLabel, $typeIcon, $typeClass] = $documentTypeMeta($extension);
                        ?>
                        <tr class="align-top hover:bg-slate-100 transition-colors" data-doc-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-doc-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-5 py-4">
                                <div class="font-medium text-slate-800 leading-6"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500 leading-5">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full <?= htmlspecialchars($typeClass, ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="material-symbols-outlined text-[13px]"><?= htmlspecialchars($typeIcon, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span>Version <?= htmlspecialchars((string)$versionNo, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span>•</span>
                                    <span><?= htmlspecialchars($storageLabel !== '' ? $storageLabel : '-', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td class="px-5 py-4 leading-6"><?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-5 py-4 leading-6"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-5 py-4"><span class="inline-flex items-center justify-center min-w-[95px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-5 py-4 leading-6"><?= htmlspecialchars($lastReview, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-5 py-4 leading-6"><?= htmlspecialchars($updatedLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-5 py-4">
                                <div class="relative inline-block text-left" data-doc-action-scope>
                                    <button type="button" data-doc-action-menu-toggle class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm">
                                        <span class="material-symbols-outlined text-[15px]">more_horiz</span>Actions
                                    </button>
                                    <div data-doc-action-menu class="hidden origin-top-right absolute right-0 mt-2 w-44 rounded-xl border border-slate-200 shadow-lg bg-white z-20 p-2 space-y-1">
                                            <button
                                                type="button"
                                                data-doc-view
                                                data-document-id="<?= htmlspecialchars($documentId, ENT_QUOTES, 'UTF-8') ?>"
                                                data-document-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
                                                data-document-bucket="<?= htmlspecialchars($bucket, ENT_QUOTES, 'UTF-8') ?>"
                                                data-document-path="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>"
                                                data-document-url="<?= htmlspecialchars($documentUrl, ENT_QUOTES, 'UTF-8') ?>"
                                                data-document-extension="<?= htmlspecialchars($extension, ENT_QUOTES, 'UTF-8') ?>"
                                                class="w-full inline-flex items-center gap-1.5 px-2.5 py-2 text-xs rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                                            >
                                                <span class="material-symbols-outlined text-[15px]">visibility</span>View
                                            </button>
                                            <button
                                                type="button"
                                                data-doc-review
                                                data-document-id="<?= htmlspecialchars($documentId, ENT_QUOTES, 'UTF-8') ?>"
                                                data-document-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
                                                data-current-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>"
                                                class="w-full inline-flex items-center gap-1.5 px-2.5 py-2 text-xs rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
                                            >
                                                <span class="material-symbols-outlined text-[15px]">fact_check</span>Review
                                            </button>
                                            <button
                                                type="button"
                                                data-doc-archive
                                                data-document-id="<?= htmlspecialchars($documentId, ENT_QUOTES, 'UTF-8') ?>"
                                                data-document-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
                                                data-current-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>"
                                                class="w-full inline-flex items-center gap-1.5 px-2.5 py-2 text-xs rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100"
                                            >
                                                <span class="material-symbols-outlined text-[15px]">inventory_2</span>Archive
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

<div id="documentViewerModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="documentViewerModal"></div>
    <div class="relative w-full h-full flex items-center justify-center p-3 md:p-6">
        <div class="w-[min(96vw,1200px)] h-[92vh] bg-white rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-4">
                <div>
                    <h3 id="documentViewerTitle" class="text-lg font-semibold text-slate-800">Document Viewer</h3>
                    <p id="documentViewerMeta" class="text-xs text-slate-500 mt-1">-</p>
                </div>
                <button type="button" data-modal-close="documentViewerModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <div id="documentViewerContent" class="flex-1 p-3 md:p-4 bg-slate-50 overflow-auto flex items-center justify-center text-sm text-slate-500">
                Select a document to preview.
            </div>

            <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                <a id="documentViewerOpenLink" href="#" target="_blank" rel="noopener noreferrer" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Open in New Tab</a>
                <button type="button" data-modal-close="documentViewerModal" class="px-4 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Close</button>
            </div>
        </div>
    </div>
</div>

<div id="uploaderDocumentsModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="uploaderDocumentsModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-5xl bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-4">
                <div>
                    <h3 id="uploaderDocumentsTitle" class="text-lg font-semibold text-slate-800">Uploaded Documents</h3>
                    <p id="uploaderDocumentsMeta" class="text-xs text-slate-500 mt-1">-</p>
                </div>
                <button type="button" data-modal-close="uploaderDocumentsModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <div class="p-6 overflow-x-auto max-h-[65vh]">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600 sticky top-0">
                        <tr>
                            <th class="text-left px-4 py-3">Document</th>
                            <th class="text-left px-4 py-3">Category</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-left px-4 py-3">Updated</th>
                            <th class="text-left px-4 py-3">Storage</th>
                        </tr>
                    </thead>
                    <tbody id="uploaderDocumentsBody" class="divide-y divide-slate-100">
                        <tr>
                            <td class="px-4 py-3 text-slate-500" colspan="5">No documents found for this uploader.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 flex justify-end">
                <button type="button" data-modal-close="uploaderDocumentsModal" class="px-4 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Close</button>
            </div>
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
