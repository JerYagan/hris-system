<?php
$docStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'approved' => ['Approved', 'bg-emerald-100 text-emerald-800'],
        'rejected' => ['Rejected', 'bg-rose-100 text-rose-800'],
        'submitted' => ['Submitted', 'bg-amber-100 text-amber-800'],
        'needs_revision', 'need_revision', 'need revision', 'needs revision' => ['Needs Revision', 'bg-orange-100 text-orange-800'],
        'archived' => ['Archived', 'bg-slate-200 text-slate-700'],
        default => ['Draft', 'bg-blue-100 text-blue-800'],
    };
};

$accountTypePill = static function (string $accountType): array {
    $key = strtolower(trim($accountType));
    return match ($key) {
        'employee' => ['Employee', 'bg-emerald-100 text-emerald-700'],
        'applicant' => ['Applicant', 'bg-amber-100 text-amber-700'],
        default => ['Unknown', 'bg-slate-100 text-slate-700'],
    };
};

$documentTypeMeta = static function (string $path): array {
    $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
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
    return ['File', 'draft', 'bg-slate-100 text-slate-700'];
};

$renderAccountTabs = static function (string $group): void {
    ?>
    <div class="inline-flex items-center gap-1 rounded-xl border border-slate-200 p-1 bg-slate-50" data-account-tabs="<?= htmlspecialchars($group, ENT_QUOTES, 'UTF-8') ?>">
        <button type="button" data-account-tab="all" class="px-3 py-1.5 text-xs rounded-lg bg-white text-slate-700 border border-slate-200">All</button>
        <button type="button" data-account-tab="employee" class="px-3 py-1.5 text-xs rounded-lg text-slate-600">Employee</button>
        <button type="button" data-account-tab="applicant" class="px-3 py-1.5 text-xs rounded-lg text-slate-600">Applicant</button>
    </div>
    <?php
};

$ownerSearchDataset = [];
foreach ($documentOwnerOptions as $owner) {
    $ownerId = trim((string)($owner['id'] ?? ''));
    $firstName = trim((string)($owner['first_name'] ?? ''));
    $surname = trim((string)($owner['surname'] ?? ''));
    $ownerName = trim($surname . ', ' . $firstName, ', ');
    if ($ownerId === '' || $ownerName === '') {
        continue;
    }

    $email = trim((string)($owner['user']['email'] ?? ''));
    $photoUrl = trim((string)($owner['profile_photo_url'] ?? ''));

    $ownerSearchDataset[] = [
        'id' => $ownerId,
        'name' => $ownerName,
        'email' => $email,
        'photo_url' => $photoUrl,
    ];
}
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

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm flex gap-2">
        <span class="material-symbols-outlined text-base">error</span>
        <span><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Document Management</h2>
            <p class="text-sm text-slate-500 mt-1">Upload files to local storage with required file type, owner, and date details.</p>
        </div>
        <button type="button" data-open-upload-modal class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">
            <span class="material-symbols-outlined text-[18px]">upload_file</span>
            Upload Document to Local Storage
        </button>
    </header>
</section>

<div id="adminUploadDocumentModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="adminUploadDocumentModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl max-h-[90vh] bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Upload Document to Local Storage</h3>
                    <p class="text-sm text-slate-500 mt-1">Select file type, owner, and upload date before saving.</p>
                </div>
                <button type="button" data-modal-close="adminUploadDocumentModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <form action="document-management.php" method="POST" enctype="multipart/form-data" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm overflow-y-auto" id="adminUploadDocumentForm">
                <input type="hidden" name="form_action" value="upload_document_file">
                <script type="application/json" id="uploadOwnerDataset"><?= json_encode($ownerSearchDataset, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

                <div>
                    <label class="text-slate-600">Owner</label>
                    <div class="relative mt-1">
                        <input type="search" id="uploadOwnerSearch" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="Search owner by name or email" autocomplete="off" required>
                        <input type="hidden" name="owner_person_id" id="uploadOwnerPersonId" value="" required>
                        <div id="uploadOwnerResults" class="hidden absolute z-30 mt-1 w-full rounded-xl border border-slate-200 bg-white shadow-lg max-h-72 overflow-y-auto"></div>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Search by name or email. Results include profile photo, name, and email.</p>
                </div>

                <div>
                    <label class="text-slate-600">File Type (201 Category)</label>
                    <select name="category_name" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="">Select file type</option>
                        <?php foreach ($documentCategoryFilterOptions as $categoryName): ?>
                            <option value="<?= htmlspecialchars((string)$categoryName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$categoryName, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="text-slate-600">Upload Date</label>
                    <input name="upload_date" data-upload-document-date type="text" placeholder="Select upload date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" required>
                </div>

                <div>
                    <label class="text-slate-600">Title</label>
                    <input type="text" name="title" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                </div>

                <div class="md:col-span-2">
                    <label class="text-slate-600">Document File</label>
                    <input id="adminDocumentFileInput" type="file" name="document_file" class="sr-only" required>
                    <div id="adminDocumentDropzone" class="mt-1 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-4 text-center cursor-pointer hover:bg-slate-100 transition-colors" tabindex="0" role="button" aria-label="Select document file">
                        <span class="material-symbols-outlined text-slate-500 text-[28px]">upload_file</span>
                        <p class="text-sm font-medium text-slate-700 mt-1">Drag and drop a file here or click to browse</p>
                        <p class="text-xs text-slate-500 mt-1">Supported: PDF, image, Word, Excel, text files</p>
                        <p id="adminDocumentFileName" class="text-xs text-slate-600 mt-2">No file selected</p>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="text-slate-600">Description</label>
                    <textarea name="description" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></textarea>
                </div>

                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="adminUploadDocumentModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Upload & Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6" data-managed-table="registry">
    <header class="px-6 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Document Registry</h2>
            <p class="text-sm text-slate-500 mt-1">All employee and applicant documents with final admin review actions.</p>
        </div>
        <?php $renderAccountTabs('registry'); ?>
    </header>

    <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-6 gap-3">
        <input data-table-search type="search" class="md:col-span-2 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search title, owner, category, uploader">
        <select data-table-status class="border border-slate-300 rounded-md px-3 py-2 text-sm">
            <option value="">All Status</option>
            <option value="Draft">Draft</option>
            <option value="Submitted">Submitted</option>
            <option value="Needs Revision">Needs Revision</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
        </select>
        <select data-table-category class="border border-slate-300 rounded-md px-3 py-2 text-sm">
            <option value="">All Categories</option>
            <?php foreach ($documentCategoryFilterOptions as $categoryName): ?>
                <option value="<?= htmlspecialchars((string)$categoryName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$categoryName, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <input data-table-date-from type="text" placeholder="From date" class="border border-slate-300 rounded-md px-3 py-2 text-sm">
        <input data-table-date-to type="text" placeholder="To date" class="border border-slate-300 rounded-md px-3 py-2 text-sm">
    </div>

    <div class="px-6 pb-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Document</th>
                    <th class="text-left px-4 py-3">Owner</th>
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Updated</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($documentRegistryRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="7">No active document records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($documentRegistryRows as $row): ?>
                        <?php
                        [$statusText, $statusClass] = $docStatusPill((string)($row['status_raw'] ?? 'draft'));
                        [$accountLabel, $accountClass] = $accountTypePill((string)($row['account_type'] ?? 'unknown'));
                        [$typeLabel, $typeIcon, $typeClass] = $documentTypeMeta((string)($row['storage_path'] ?? ''));
                        $search = strtolower(trim((string)($row['title'] ?? '') . ' ' . (string)($row['owner_name'] ?? '') . ' ' . (string)($row['category'] ?? '') . ' ' . $accountLabel));
                        ?>
                        <tr
                            data-table-row
                            data-search="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                            data-status="<?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>"
                            data-category="<?= htmlspecialchars((string)($row['category'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-account="<?= htmlspecialchars((string)($row['account_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-date="<?= htmlspecialchars((string)($row['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <td class="px-4 py-3"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs <?= htmlspecialchars($typeClass, ENT_QUOTES, 'UTF-8') ?>"><span class="material-symbols-outlined text-[13px]"><?= htmlspecialchars($typeIcon, ENT_QUOTES, 'UTF-8') ?></span><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800"><?= htmlspecialchars((string)($row['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-xs text-slate-500 mt-1">Version <?= htmlspecialchars((string)($row['version_no'] ?? 1), ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['owner_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['category'] ?? 'Others'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[95px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <span class="text-slate-700"><?= htmlspecialchars((string)($row['updated_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                <div class="mt-1"><span class="inline-flex items-center justify-center min-w-[80px] px-2 py-0.5 text-xs rounded-full <?= htmlspecialchars($accountClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8') ?></span></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="relative inline-flex" data-doc-action-wrap>
                                    <button type="button" data-doc-action-toggle class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">
                                        <span class="material-symbols-outlined text-[15px]">more_horiz</span>Actions
                                    </button>
                                    <div data-doc-action-menu class="hidden absolute right-0 top-full z-20 mt-1 w-44 rounded-lg border border-slate-200 bg-white shadow-lg p-1.5">
                                        <a
                                            href="<?= htmlspecialchars((string)($row['document_url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="w-full inline-flex items-center gap-1.5 px-2.5 py-2 text-xs rounded-md text-slate-700 hover:bg-slate-50"
                                        ><span class="material-symbols-outlined text-[15px]">open_in_new</span>View</a>
                                        <button
                                            type="button"
                                            data-doc-review
                                            data-document-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-document-title="<?= htmlspecialchars((string)($row['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                            data-current-status="<?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>"
                                            class="w-full inline-flex items-center gap-1.5 px-2.5 py-2 text-xs rounded-md text-emerald-700 hover:bg-emerald-50"
                                        ><span class="material-symbols-outlined text-[15px]">fact_check</span>Review</button>
                                        <button
                                            type="button"
                                            data-doc-archive
                                            data-document-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-document-title="<?= htmlspecialchars((string)($row['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                            data-current-status="<?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>"
                                            class="w-full inline-flex items-center gap-1.5 px-2.5 py-2 text-xs rounded-md text-rose-700 hover:bg-rose-50"
                                        ><span class="material-symbols-outlined text-[15px]">inventory_2</span>Archive</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="mt-4 flex items-center justify-between gap-3">
            <p data-table-meta class="text-xs text-slate-500">Showing 0 to 0 of 0 entries</p>
            <div class="inline-flex items-center gap-2">
                <button type="button" data-page-prev class="px-3 py-1.5 text-xs border border-slate-300 rounded-md text-slate-700">Prev</button>
                <span data-page-info class="text-xs text-slate-600">Page 1 of 1</span>
                <button type="button" data-page-next class="px-3 py-1.5 text-xs border border-slate-300 rounded-md text-slate-700">Next</button>
            </div>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6" data-managed-table="uploaders">
    <header class="px-6 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Document Uploaders</h2>
            <p class="text-sm text-slate-500 mt-1">Compiled uploads per user with direct file view/download options in modal.</p>
        </div>
        <?php $renderAccountTabs('uploaders'); ?>
    </header>

    <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-5 gap-3">
        <input data-table-search type="search" class="md:col-span-2 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search uploader email">
        <select data-table-status class="border border-slate-300 rounded-md px-3 py-2 text-sm">
            <option value="">All Types</option>
            <option value="Employee">Employee</option>
            <option value="Applicant">Applicant</option>
        </select>
        <input data-table-date-from type="text" placeholder="From date" class="border border-slate-300 rounded-md px-3 py-2 text-sm">
        <input data-table-date-to type="text" placeholder="To date" class="border border-slate-300 rounded-md px-3 py-2 text-sm">
    </div>

    <div class="px-6 pb-6 overflow-x-auto">
        <table class="w-full text-sm">
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
                    <tr><td class="px-4 py-3 text-slate-500" colspan="5">No uploader activity found.</td></tr>
                <?php else: ?>
                    <?php foreach ($uploaderSummaryRows as $uploader): ?>
                        <?php
                        $uploaderType = (string)($uploader['account_type'] ?? 'unknown');
                        [$typeLabel, $typeClass] = $accountTypePill($uploaderType);
                        $email = (string)($uploader['email'] ?? 'Unknown Email');
                        $search = strtolower(trim($email . ' ' . $typeLabel));
                        $documentsJson = json_encode((array)($uploader['documents'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        ?>
                        <tr
                            data-table-row
                            data-search="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                            data-status="<?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?>"
                            data-category=""
                            data-account="<?= htmlspecialchars($uploaderType, ENT_QUOTES, 'UTF-8') ?>"
                            data-date="<?= htmlspecialchars((string)($uploader['last_uploaded_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <td class="px-4 py-3 text-slate-800 font-medium"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[95px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($typeClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars((string)($uploader['total_uploads'] ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars((string)($uploader['last_uploaded_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-doc-uploader-open
                                    data-uploader-email="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                    data-uploader-type="<?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?>"
                                    data-uploader-documents='<?= htmlspecialchars((string)$documentsJson, ENT_QUOTES, 'UTF-8') ?>'
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"
                                ><span class="material-symbols-outlined text-[15px]">folder_open</span>View Documents</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="mt-4 flex items-center justify-between gap-3">
            <p data-table-meta class="text-xs text-slate-500">Showing 0 to 0 of 0 entries</p>
            <div class="inline-flex items-center gap-2">
                <button type="button" data-page-prev class="px-3 py-1.5 text-xs border border-slate-300 rounded-md text-slate-700">Prev</button>
                <span data-page-info class="text-xs text-slate-600">Page 1 of 1</span>
                <button type="button" data-page-next class="px-3 py-1.5 text-xs border border-slate-300 rounded-md text-slate-700">Next</button>
            </div>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Pending Staff Queue</h2>
        <p class="text-sm text-slate-500 mt-1">Includes documents forwarded by staff and records still waiting for staff recommendation.</p>
    </header>

    <div class="px-6 pt-4">
        <div class="inline-flex items-center gap-1 rounded-xl border border-slate-200 p-1 bg-slate-50" data-section-toggle="pendingQueues">
            <button type="button" data-section-tab="forwarded" class="px-3 py-1.5 text-xs rounded-lg bg-white border border-slate-200 text-slate-700">Forwarded to Admin</button>
            <button type="button" data-section-tab="staff_pending" class="px-3 py-1.5 text-xs rounded-lg text-slate-600">Pending Staff Review</button>
        </div>
    </div>

    <div class="p-6" data-section-panel="forwarded">
        <div class="rounded-xl border border-slate-200" data-managed-table="forwarded">
            <div class="px-4 py-3 grid grid-cols-1 md:grid-cols-6 gap-3 border-b border-slate-200">
                <input data-table-search type="search" class="md:col-span-2 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search forwarded queue">
                <select data-table-status class="border border-slate-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Status</option>
                    <option value="Draft">Draft</option>
                    <option value="Submitted">Submitted</option>
                    <option value="Needs Revision">Needs Revision</option>
                </select>
                <select data-table-category class="border border-slate-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($documentCategoryFilterOptions as $categoryName): ?>
                        <option value="<?= htmlspecialchars((string)$categoryName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$categoryName, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <input data-table-date-from type="text" placeholder="From date" class="border border-slate-300 rounded-md px-3 py-2 text-sm">
                <input data-table-date-to type="text" placeholder="To date" class="border border-slate-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3">Document</th>
                            <th class="text-left px-4 py-3">Owner</th>
                            <th class="text-left px-4 py-3">Category</th>
                            <th class="text-left px-4 py-3">Staff Recommendation</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-left px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($pendingStaffApprovalRows)): ?>
                            <tr><td class="px-4 py-3 text-slate-500" colspan="6">No forwarded document recommendations.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pendingStaffApprovalRows as $row): ?>
                                <?php
                                [$statusText, $statusClass] = $docStatusPill((string)($row['status_raw'] ?? 'draft'));
                                $search = strtolower(trim((string)($row['title'] ?? '') . ' ' . (string)($row['owner_name'] ?? '') . ' ' . (string)($row['category'] ?? '') . ' ' . (string)($row['staff_recommendation'] ?? '')));
                                ?>
                                <tr
                                    data-table-row
                                    data-search="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                                    data-status="<?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>"
                                    data-category="<?= htmlspecialchars((string)($row['category'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-account="<?= htmlspecialchars((string)($row['account_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-date="<?= htmlspecialchars((string)($row['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-800"><?= htmlspecialchars((string)($row['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)($row['updated_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td class="px-4 py-3"><?= htmlspecialchars((string)($row['owner_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars((string)($row['category'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-4 py-3">
                                        <div class="text-slate-700 text-xs"><?= htmlspecialchars((string)($row['staff_recommendation'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if (trim((string)($row['staff_notes'] ?? '')) !== ''): ?>
                                            <div class="text-[11px] text-slate-500 mt-1"><?= htmlspecialchars((string)($row['staff_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[95px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" data-doc-review data-review-default="approved" data-document-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-document-title="<?= htmlspecialchars((string)($row['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100"><span class="material-symbols-outlined text-[15px]">check</span>Approve</button>
                                            <button type="button" data-doc-review data-review-default="rejected" data-document-id="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-document-title="<?= htmlspecialchars((string)($row['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100"><span class="material-symbols-outlined text-[15px]">close</span>Reject</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 flex items-center justify-between gap-3 border-t border-slate-200">
                <p data-table-meta class="text-xs text-slate-500">Showing 0 to 0 of 0 entries</p>
                <div class="inline-flex items-center gap-2">
                    <button type="button" data-page-prev class="px-3 py-1.5 text-xs border border-slate-300 rounded-md text-slate-700">Prev</button>
                    <span data-page-info class="text-xs text-slate-600">Page 1 of 1</span>
                    <button type="button" data-page-next class="px-3 py-1.5 text-xs border border-slate-300 rounded-md text-slate-700">Next</button>
                </div>
            </div>
        </div>
    </div>

    <div class="p-6 hidden" data-section-panel="staff_pending">
        <div class="rounded-xl border border-slate-200" data-managed-table="staffpending">
            <div class="px-4 py-3 grid grid-cols-1 md:grid-cols-6 gap-3 border-b border-slate-200">
                <input data-table-search type="search" class="md:col-span-2 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search pending staff review">
                <select data-table-status class="border border-slate-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Status</option>
                    <option value="Draft">Draft</option>
                    <option value="Submitted">Submitted</option>
                    <option value="Needs Revision">Needs Revision</option>
                </select>
                <select data-table-category class="border border-slate-300 rounded-md px-3 py-2 text-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($documentCategoryFilterOptions as $categoryName): ?>
                        <option value="<?= htmlspecialchars((string)$categoryName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$categoryName, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <input data-table-date-from type="text" placeholder="From date" class="border border-slate-300 rounded-md px-3 py-2 text-sm">
                <input data-table-date-to type="text" placeholder="To date" class="border border-slate-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3">Document</th>
                            <th class="text-left px-4 py-3">Owner</th>
                            <th class="text-left px-4 py-3">Category</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-left px-4 py-3">Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($pendingStaffReviewRows)): ?>
                            <tr><td class="px-4 py-3 text-slate-500" colspan="5">No documents are waiting for staff recommendation.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pendingStaffReviewRows as $row): ?>
                                <?php
                                [$statusText, $statusClass] = $docStatusPill((string)($row['status_raw'] ?? 'draft'));
                                $search = strtolower(trim((string)($row['title'] ?? '') . ' ' . (string)($row['owner_name'] ?? '') . ' ' . (string)($row['category'] ?? '')));
                                ?>
                                <tr
                                    data-table-row
                                    data-search="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                                    data-status="<?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>"
                                    data-category="<?= htmlspecialchars((string)($row['category'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-account="<?= htmlspecialchars((string)($row['account_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-date="<?= htmlspecialchars((string)($row['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars((string)($row['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars((string)($row['owner_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars((string)($row['category'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[95px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars((string)($row['updated_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 flex items-center justify-between gap-3 border-t border-slate-200">
                <p data-table-meta class="text-xs text-slate-500">Showing 0 to 0 of 0 entries</p>
                <div class="inline-flex items-center gap-2">
                    <button type="button" data-page-prev class="px-3 py-1.5 text-xs border border-slate-300 rounded-md text-slate-700">Prev</button>
                    <span data-page-info class="text-xs text-slate-600">Page 1 of 1</span>
                    <button type="button" data-page-next class="px-3 py-1.5 text-xs border border-slate-300 rounded-md text-slate-700">Next</button>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6" data-managed-table="archived">
    <header class="px-6 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Archived Documents</h2>
            <p class="text-sm text-slate-500 mt-1">Archived by admin or staff, with restore option for controlled reactivation.</p>
        </div>
        <?php $renderAccountTabs('archived'); ?>
    </header>

    <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-6 gap-3">
        <input data-table-search type="search" class="md:col-span-2 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search archived records">
        <select data-table-category class="border border-slate-300 rounded-md px-3 py-2 text-sm">
            <option value="">All Categories</option>
            <?php foreach ($documentCategoryFilterOptions as $categoryName): ?>
                <option value="<?= htmlspecialchars((string)$categoryName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$categoryName, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <input data-table-date-from type="text" placeholder="From date" class="border border-slate-300 rounded-md px-3 py-2 text-sm">
        <input data-table-date-to type="text" placeholder="To date" class="border border-slate-300 rounded-md px-3 py-2 text-sm">
        <div class="md:col-span-2"></div>
    </div>

    <div class="px-6 pb-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Document</th>
                    <th class="text-left px-4 py-3">Owner</th>
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Archived Status</th>
                    <th class="text-left px-4 py-3">Updated</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($archivedDocumentRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="6">No archived documents found.</td></tr>
                <?php else: ?>
                    <?php foreach ($archivedDocumentRows as $row): ?>
                        <?php
                        $search = strtolower(trim((string)($row['title'] ?? '') . ' ' . (string)($row['owner_name'] ?? '') . ' ' . (string)($row['category'] ?? '')));
                        ?>
                        <tr
                            data-table-row
                            data-search="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                            data-status="Archived"
                            data-category="<?= htmlspecialchars((string)($row['category'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-account="<?= htmlspecialchars((string)($row['account_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-date="<?= htmlspecialchars((string)($row['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-700"><?= htmlspecialchars((string)($row['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)($row['storage_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['owner_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['category'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[95px] px-2.5 py-1 text-xs rounded-full bg-slate-200 text-slate-700">Archived</span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($row['updated_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <form method="POST" action="document-management.php" class="inline-flex" data-restore-form>
                                    <input type="hidden" name="form_action" value="restore_document">
                                    <input type="hidden" name="document_id" value="<?= htmlspecialchars((string)($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-md border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
                                        <span class="material-symbols-outlined text-[14px]">restore</span>Restore
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="mt-4 flex items-center justify-between gap-3">
            <p data-table-meta class="text-xs text-slate-500">Showing 0 to 0 of 0 entries</p>
            <div class="inline-flex items-center gap-2">
                <button type="button" data-page-prev class="px-3 py-1.5 text-xs border border-slate-300 rounded-md text-slate-700">Prev</button>
                <span data-page-info class="text-xs text-slate-600">Page 1 of 1</span>
                <button type="button" data-page-next class="px-3 py-1.5 text-xs border border-slate-300 rounded-md text-slate-700">Next</button>
            </div>
        </div>
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
            <form action="document-management.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm" id="reviewDocumentForm">
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
                    <label class="text-slate-600">Final Decision</label>
                    <select id="reviewStatusSelect" name="review_status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="approved">Approve</option>
                        <option value="rejected">Reject</option>
                        <option value="needs_revision">Needs Revision</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Review Notes</label>
                    <textarea id="reviewNotesInput" name="review_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Required for rejected/needs revision decisions."></textarea>
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
            <div id="documentViewerContent" class="flex-1 p-3 md:p-4 bg-slate-50 overflow-auto flex items-center justify-center text-sm text-slate-500">Select a document to preview.</div>
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
        <div class="w-full max-w-6xl bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-4">
                <div>
                    <h3 id="uploaderDocumentsTitle" class="text-lg font-semibold text-slate-800">Uploaded Documents</h3>
                    <p id="uploaderDocumentsMeta" class="text-xs text-slate-500 mt-1">-</p>
                </div>
                <button type="button" data-modal-close="uploaderDocumentsModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <div class="p-6 overflow-x-auto max-h-[65vh]">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3">Document</th>
                            <th class="text-left px-4 py-3">Category</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-left px-4 py-3">Updated</th>
                            <th class="text-left px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="uploaderDocumentsBody" class="divide-y divide-slate-100">
                        <tr><td class="px-4 py-3 text-slate-500" colspan="5">No documents found for this uploader.</td></tr>
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
            <form action="document-management.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm" id="archiveDocumentForm">
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
                    <textarea name="archive_reason" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add retention reference, duplicate reason, or closure notes."></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="archiveDocumentModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Confirm Archive</button>
                </div>
            </form>
        </div>
    </div>
</div>
