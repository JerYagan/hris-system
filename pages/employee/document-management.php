<?php
/**
 * Employee Document Management
 * DA-ATI HRIS
 */

require_once __DIR__ . '/includes/document-management/bootstrap.php';
require_once __DIR__ . '/includes/document-management/actions.php';
require_once __DIR__ . '/includes/document-management/data.php';

$pageTitle = 'Document Management | DA HRIS';
$activePage = 'document-management.php';
$breadcrumbs = ['Document Management'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/employee/document-management/index.js';

ob_start();

$escape = static function (mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$formatDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '-';
    }

    $ts = strtotime($value);
    return $ts === false ? '-' : date('M j, Y g:i A', $ts);
};

$formatBytes = static function (int $bytes): string {
    if ($bytes <= 0) {
        return '-';
    }

    $units = ['B', 'KB', 'MB', 'GB'];
    $pow = (int)floor(log($bytes, 1024));
    $pow = max(0, min($pow, count($units) - 1));
    $size = $bytes / (1024 ** $pow);

    return number_format($size, $pow === 0 ? 0 : 2) . ' ' . $units[$pow];
};

$statusMeta = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'approved' => ['Approved', 'bg-green-50 text-green-700'],
        'submitted' => ['Submitted', 'bg-amber-50 text-amber-700'],
        'rejected' => ['Rejected', 'bg-red-50 text-red-700'],
      'needs_revision' => ['Needs Revision', 'bg-orange-50 text-orange-700'],
        'archived' => ['Archived', 'bg-slate-100 text-slate-600'],
        default => ['Draft', 'bg-blue-50 text-blue-700'],
    };
};

$fileTypeMeta = static function (string $extension): array {
    $ext = strtolower(trim($extension));

    if ($ext === 'pdf') {
        return ['picture_as_pdf', 'text-red-600', 'PDF'];
    }

    if (in_array($ext, ['doc', 'docx'], true)) {
        return ['description', 'text-blue-600', 'Word'];
    }

    if (in_array($ext, ['xls', 'xlsx', 'csv'], true)) {
        return ['table_chart', 'text-green-600', 'Excel'];
    }

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        return ['image', 'text-purple-600', 'Image'];
    }

    return ['insert_drive_file', 'text-gray-500', strtoupper($ext !== '' ? $ext : 'FILE')];
};

$document201Types = [
  'Violation',
  'Memorandum Receipt',
  'GSIS',
  'Copy of SALN',
  'Service record',
  'COE',
  'PDS',
  'SSS',
  'Pagibig',
  'Philhealth',
  'NBI',
  'Medical',
  'Drug Test',
  'Others',
];
$documentCategoryFilters = array_values(array_unique(array_merge(
  $document201Types,
  ['Resume/CV', 'Transcript of Records', 'Valid Government ID', 'Eligibility (CSC/PRC)', 'Application Letter']
)));
$document201Lookup = array_fill_keys(array_map('strtolower', $document201Types), true);
$activeDocumentCount = 0;
$archivedDocumentCount = 0;
foreach ($employeeDocuments as $documentCountRow) {
  $statusKey = strtolower((string)($documentCountRow['document_status'] ?? 'draft'));
  if ($statusKey === 'archived') {
    $archivedDocumentCount++;
    continue;
  }
  $activeDocumentCount++;
}
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold">Document Management</h1>
  <p class="text-sm text-gray-500">Track your own 201 documents, manage version history, and monitor review/archive status.</p>
</div>

<?php if (!empty($message)): ?>
  <?php $alertClass = ($state ?? '') === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'; ?>
  <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $escape($alertClass) ?>" aria-live="polite">
    <?= $escape($message) ?>
  </div>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
  <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" aria-live="polite">
    <?= $escape($dataLoadError) ?>
  </div>
<?php endif; ?>

<section class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-3">
  <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Active Documents</p>
    <p class="text-2xl font-bold text-emerald-800 mt-1"><?= $escape((string)$activeDocumentCount) ?></p>
  </div>
  <div class="rounded-xl border border-slate-300 bg-slate-100 px-4 py-3">
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Archived Documents</p>
    <p class="text-2xl font-bold text-slate-700 mt-1"><?= $escape((string)$archivedDocumentCount) ?></p>
  </div>
</section>

<div class="employee-doc-tabs mb-6">
  <div class="employee-doc-tabs-row" role="tablist" aria-label="Document View Tabs">
    <button type="button" data-document-view-tab="active" class="employee-doc-tab employee-doc-tab-active">Current Documents</button>
    <button type="button" data-document-view-tab="archived" class="employee-doc-tab employee-doc-tab-muted">Archived Documents</button>
  </div>

  <div id="documentStatusTabs" class="employee-doc-status-row" role="tablist" aria-label="Document Status Tabs">
    <button type="button" data-document-status-tab="all" class="employee-doc-status-pill employee-doc-status-pill-active">All</button>
    <button type="button" data-document-status-tab="submitted" class="employee-doc-status-pill employee-doc-status-pill-muted">Submitted</button>
    <button type="button" data-document-status-tab="approved" class="employee-doc-status-pill employee-doc-status-pill-muted">Approved</button>
    <button type="button" data-document-status-tab="rejected" class="employee-doc-status-pill employee-doc-status-pill-muted">Rejected</button>
  </div>
</div>

<div class="bg-white border rounded-xl p-6">
  <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
    <h2 class="text-lg font-semibold text-gray-800">My Document Registry</h2>

    <div class="w-full xl:w-auto xl:ml-auto flex flex-col lg:flex-row gap-3 lg:items-end">
      <div class="w-full lg:w-80">
        <label class="text-sm text-gray-700">Search</label>
        <input id="documentSearchInput" type="search" placeholder="Search title, category, status" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-md text-sm bg-white">
      </div>

      <div class="w-full lg:w-64">
        <label class="text-sm text-gray-700">Category</label>
        <select id="documentCategoryFilter" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-md text-sm bg-white">
          <option value="">All Categories</option>
          <?php foreach ($documentCategoryFilters as $categoryName): ?>
            <option value="<?= $escape(strtolower($categoryName)) ?>"><?= $escape($categoryName) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button data-open-upload class="bg-daGreen text-white px-4 py-2 rounded-md text-sm font-medium hover:opacity-90 h-[42px] inline-flex items-center justify-center gap-1.5 whitespace-nowrap"><span class="material-icons text-base">upload_file</span>Upload Document</button>
    </div>
  </div>

  <?php if (empty($employeeDocuments)): ?>
    <div id="documentTrueEmpty" class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center">
      <p class="font-medium text-gray-700">No documents yet</p>
      <p class="text-sm text-gray-500 mt-1">Upload your first document to start tracking review status and version history.</p>
      <button data-open-upload class="mt-4 bg-daGreen text-white px-4 py-2 rounded-lg text-sm">Upload Now</button>
    </div>
  <?php else: ?>
    <div class="w-full">
      <table id="employeeDocumentTable" class="w-full text-sm table-fixed">
        <thead>
          <tr class="border-b text-gray-500">
            <th class="text-left py-3 pr-2 w-[35%]">Document</th>
            <th class="text-left py-3 pr-2 w-[18%]">Category</th>
            <th class="text-left py-3 pr-2 w-[15%]">Uploaded</th>
            <th class="text-left py-3 pr-2 w-[12%]">Status</th>
            <th class="text-right py-3 w-[20%]">Actions</th>
          </tr>
        </thead>
        <tbody id="documentTableBody">
          <?php foreach ($employeeDocuments as $document): ?>
            <?php
              $documentId = (string)($document['id'] ?? '');
              $categoryName = (string)($document['category_name'] ?? 'Uncategorized');
              $status = (string)($document['document_status'] ?? 'draft');
              $isArchived = strtolower($status) === 'archived';
              $isResubmittable = in_array(strtolower($status), ['rejected', 'needs_revision'], true);
              $is201File = isset($document201Lookup[strtolower($categoryName)]);
              [$statusLabel, $statusClass] = $statusMeta($status);
              $versions = (array)($documentVersionsById[$documentId] ?? []);
              $reviews = (array)($documentReviewsById[$documentId] ?? []);
              $latestVersion = !empty($versions) ? (array)$versions[0] : [];
              $latestFileName = (string)($latestVersion['file_name'] ?? (string)($document['title'] ?? ''));
              $latestExt = strtolower((string)pathinfo($latestFileName, PATHINFO_EXTENSION));
              [$fileIcon, $fileIconClass, $fileTypeLabel] = $fileTypeMeta($latestExt);
              $detailsModalId = 'document-details-' . $documentId;
            ?>
            <tr class="border-b" data-document-row data-search="<?= $escape(strtolower((string)($document['title'] ?? '') . ' ' . strtolower($categoryName) . ' ' . strtolower($statusLabel) . ' ' . strtolower($fileTypeLabel))) ?>" data-category="<?= $escape(strtolower($categoryName)) ?>" data-status="<?= $escape(strtolower($status)) ?>" data-view="<?= $isArchived ? 'archived' : 'active' ?>">
              <td class="py-3 pr-2 align-top">
                <div class="flex items-start gap-2">
                  <span class="material-icons text-lg mt-0.5 <?= $escape($fileIconClass) ?>" title="<?= $escape($fileTypeLabel) ?>"><?= $escape($fileIcon) ?></span>
                  <div class="min-w-0">
                    <p class="font-medium break-words"><?= $escape($document['title'] ?? 'Document') ?></p>
                    <p class="text-xs text-gray-500 break-words"><?= $escape($document['description'] ?? '') ?></p>
                    <?php if ($is201File): ?>
                      <p class="text-xs text-daGreen mt-1">201 File</p>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td class="py-3 pr-2 align-top break-words"><?= $escape($categoryName) ?></td>
              <td class="py-3 pr-2 align-top break-words"><?= $escape($formatDate($document['updated_at'] ?? '')) ?></td>
              <td class="py-3 pr-2 align-top"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium <?= $escape($statusClass) ?>"><?= $escape($statusLabel) ?></span></td>
              <td class="py-3 align-top text-right">
                <div class="relative inline-flex text-left" data-action-dropdown data-admin-action-scope>
                  <button type="button" data-action-trigger aria-haspopup="menu" aria-expanded="false" class="admin-action-button">
                    <span class="admin-action-button-label">
                      <span class="material-symbols-outlined">more_horiz</span>
                      Actions
                    </span>
                    <span class="material-symbols-outlined admin-action-chevron">expand_more</span>
                  </button>

                  <div data-action-menu role="menu" class="admin-action-menu hidden w-44">
                    <a href="view-document.php?document_id=<?= $escape($documentId) ?>" target="_blank" rel="noopener" data-action-item="view" class="admin-action-item">
                      <span class="material-symbols-outlined">open_in_new</span>
                      View
                    </a>
                    <a href="download-document.php?document_id=<?= $escape($documentId) ?>" data-action-item="download" class="admin-action-item">
                      <span class="material-symbols-outlined">download</span>
                      Download
                    </a>
                    <button type="button" data-action-item="details" role="menuitem" class="admin-action-item">
                      <span class="material-symbols-outlined">info</span>
                      View Details
                    </button>
                    <?php if (!$isArchived): ?>
                      <button type="button" data-action-item="upload_version" role="menuitem" class="admin-action-item">
                        <span class="material-symbols-outlined">upload</span>
                        <?= $escape($isResubmittable ? 'Resubmit Document' : 'Upload New Version') ?>
                      </button>
                      <button type="button" data-action-item="archive" role="menuitem" class="admin-action-item admin-action-item-danger">
                        <span class="material-symbols-outlined">inventory_2</span>
                        Archive
                      </button>
                    <?php else: ?>
                      <button type="button" data-action-item="restore" role="menuitem" class="admin-action-item">
                        <span class="material-symbols-outlined">restore</span>
                        Restore
                      </button>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="hidden">
                  <a href="view-document.php?document_id=<?= $escape($documentId) ?>" target="_blank" rel="noopener" data-action-view>View</a>
                  <a href="download-document.php?document_id=<?= $escape($documentId) ?>" data-action-download>Download</a>
                  <button type="button" data-action-details data-open-details="<?= $escape($detailsModalId) ?>">Details</button>
                  <?php if (!$isArchived): ?>
                    <button type="button" data-action-upload data-open-version data-document-id="<?= $escape($documentId) ?>" data-document-title="<?= $escape($document['title'] ?? 'Document') ?>">Upload</button>
                    <form method="post" action="document-management.php" data-archive-form>
                      <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                      <input type="hidden" name="action" value="archive_document">
                      <input type="hidden" name="document_id" value="<?= $escape($documentId) ?>">
                      <input type="hidden" name="archive_reason" value="" data-archive-reason>
                      <input type="hidden" value="<?= $escape($document['title'] ?? 'Document') ?>" data-archive-title>
                      <button type="submit" data-action-archive>Archive</button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="document-management.php" data-restore-form>
                      <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                      <input type="hidden" name="action" value="restore_document">
                      <input type="hidden" name="document_id" value="<?= $escape($documentId) ?>">
                      <input type="hidden" name="restore_reason" value="" data-restore-reason>
                      <input type="hidden" value="<?= $escape($document['title'] ?? 'Document') ?>" data-restore-title>
                      <button type="submit" data-action-restore>Restore</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>

            <div id="<?= $escape($detailsModalId) ?>" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden" aria-hidden="true">
              <div class="bg-white rounded-xl shadow-lg w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b flex justify-between items-center">
                  <div>
                    <h3 class="text-lg font-semibold"><?= $escape($document['title'] ?? 'Document') ?></h3>
                    <p class="text-xs text-gray-500 mt-1"><?= $escape($categoryName) ?> · <?= $escape($fileTypeLabel) ?></p>
                  </div>
                  <button type="button" data-close-details class="text-gray-400 hover:text-gray-600"><span class="material-icons">close</span></button>
                </div>

                <div class="p-6 overflow-y-auto space-y-6 text-sm">
                  <div class="grid md:grid-cols-3 gap-4">
                    <div class="border rounded-lg p-3">
                      <p class="text-gray-500 text-xs">Status</p>
                      <p class="font-medium mt-1"><?= $escape($statusLabel) ?></p>
                    </div>
                    <div class="border rounded-lg p-3">
                      <p class="text-gray-500 text-xs">Current Version</p>
                      <p class="font-medium mt-1">v<?= $escape((string)($document['current_version_no'] ?? 1)) ?></p>
                    </div>
                    <div class="border rounded-lg p-3">
                      <p class="text-gray-500 text-xs">Last Updated</p>
                      <p class="font-medium mt-1"><?= $escape($formatDate($document['updated_at'] ?? '')) ?></p>
                    </div>
                  </div>

                  <div>
                    <h4 class="font-semibold mb-2">Version History</h4>
                    <?php if (!empty($versions)): ?>
                      <div class="space-y-2">
                        <?php foreach ($versions as $version): ?>
                          <div class="border rounded-md px-3 py-2 bg-gray-50">
                            <p class="font-medium">v<?= $escape((string)($version['version_no'] ?? 1)) ?> · <?= $escape($version['file_name'] ?? '') ?></p>
                            <p class="text-gray-500 text-xs mt-1"><?= $escape($version['mime_type'] ?? '-') ?> · <?= $escape($formatBytes((int)($version['size_bytes'] ?? 0))) ?> · <?= $escape($formatDate($version['uploaded_at'] ?? '')) ?></p>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <p class="text-gray-500">No version records available.</p>
                    <?php endif; ?>
                  </div>

                  <div>
                    <h4 class="font-semibold mb-2">Review History</h4>
                    <?php if (!empty($reviews)): ?>
                      <div class="space-y-2">
                        <?php foreach ($reviews as $review): ?>
                          <div class="border rounded-md px-3 py-2 bg-gray-50">
                            <p class="font-medium"><?= $escape(ucwords(str_replace('_', ' ', (string)($review['review_status'] ?? 'pending')))) ?></p>
                            <p class="text-gray-500 text-xs mt-1"><?= $escape($review['reviewer_email'] ?? '-') ?> · <?= $escape($formatDate($review['reviewed_at'] ?? '')) ?></p>
                            <?php if (!empty($review['review_notes'])): ?>
                              <p class="text-gray-600 text-xs mt-2"><?= $escape($review['review_notes']) ?></p>
                            <?php endif; ?>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <p class="text-gray-500">No review history yet.</p>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="px-6 py-4 border-t flex justify-between items-center gap-3">
                  <div class="flex gap-2">
                    <a href="view-document.php?document_id=<?= $escape($documentId) ?>" target="_blank" rel="noopener" class="border px-3 py-2 rounded-lg text-xs inline-flex items-center gap-1.5"><span class="material-icons text-sm">visibility</span>View</a>
                    <a href="download-document.php?document_id=<?= $escape($documentId) ?>" class="border px-3 py-2 rounded-lg text-xs inline-flex items-center gap-1.5"><span class="material-icons text-sm">download</span>Download</a>
                    <?php if (!$isArchived): ?>
                      <button type="button" data-open-version data-document-id="<?= $escape($documentId) ?>" data-document-title="<?= $escape($document['title'] ?? 'Document') ?>" class="border px-3 py-2 rounded-lg text-xs inline-flex items-center gap-1.5"><span class="material-icons text-sm">edit</span><?= $isResubmittable ? 'Resubmit Revised' : 'Upload New Version' ?></button>
                    <?php endif; ?>
                  </div>
                  <button type="button" data-close-details class="border px-3 py-2 rounded-lg text-xs">Close</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div id="documentFilterEmpty" class="hidden rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center mt-4">
      <p class="font-medium text-gray-700">No matching documents</p>
      <p class="text-sm text-gray-500 mt-1">Try changing your search text or filters.</p>
      <button type="button" data-clear-filters class="mt-4 border px-4 py-2 rounded-lg text-sm">Clear Filters</button>
    </div>

    <div class="mt-4 flex items-center justify-between gap-3 border-t pt-4" data-pagination-controls="employeeDocumentTable">
      <p class="text-xs text-gray-500" data-pagination-label>Showing 0 of 0</p>
      <div class="flex items-center gap-2">
        <button type="button" class="px-3 py-1.5 text-xs border rounded-md text-gray-700 hover:bg-gray-50" data-pagination-prev>Previous</button>
        <span class="text-xs text-gray-600" data-pagination-page>Page 1</span>
        <button type="button" class="px-3 py-1.5 text-xs border rounded-md text-gray-700 hover:bg-gray-50" data-pagination-next>Next</button>
      </div>
    </div>
  <?php endif; ?>
</div>

<div id="uploadModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden px-4 py-6" aria-hidden="true">
  <div class="bg-white rounded-2xl shadow-lg w-full max-w-5xl max-h-[92vh] overflow-hidden flex flex-col">
    <div class="px-6 py-5 border-b flex justify-between items-start gap-4">
      <div>
        <h2 class="text-lg font-semibold">Upload Document</h2>
        <p class="text-sm text-gray-500 mt-1">Add a new 201 file, review the auto-generated title, and confirm before submitting.</p>
      </div>
      <button type="button" data-close-upload class="text-gray-400 hover:text-gray-600"><span class="material-icons">close</span></button>
    </div>

    <form method="post" action="document-management.php" enctype="multipart/form-data" class="flex-1 min-h-0 flex flex-col" data-upload-form>
      <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
      <input type="hidden" name="action" value="upload_document">

      <div class="flex-1 min-h-0 overflow-y-auto px-6 py-5">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
          <div>
            <label class="text-sm font-medium">Document Title</label>
            <input id="uploadDocumentTitle" name="title" type="text" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm" required>
            <p class="mt-1 text-xs text-gray-500">The title auto-fills from the selected filename, but you can edit it before uploading.</p>
          </div>

          <div>
            <label class="text-sm font-medium">Category</label>
            <select id="uploadDocumentCategory" name="category_id" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm" required>
              <option value="">Select category</option>
              <?php foreach ($documentCategories as $category): ?>
                <option value="<?= $escape($category['id'] ?? '') ?>"><?= $escape($category['category_name'] ?? '') ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="lg:col-span-2">
            <label class="text-sm font-medium">Description</label>
            <textarea name="description" rows="4" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm" placeholder="Optional"></textarea>
          </div>

          <div class="lg:col-span-2">
            <label class="text-sm font-medium">Upload File (max 10 MB)</label>
            <div id="uploadDocumentDropzone" class="employee-doc-upload-dropzone mt-2" tabindex="0" role="button" aria-label="Select a document to upload">
              <input id="uploadDocumentFile" name="document_file" type="file" class="sr-only" required>
              <div class="employee-doc-upload-dropzone-copy">
                <span class="material-icons text-2xl text-daGreen">upload_file</span>
                <div>
                  <p class="font-semibold text-slate-800">Choose a file or drag it here</p>
                  <p class="text-xs text-slate-500 mt-1">PDF, image, Word, Excel, PowerPoint, TXT, and CSV files are supported.</p>
                </div>
              </div>
              <button type="button" class="employee-doc-upload-trigger">Browse Files</button>
            </div>
          </div>
        </div>

        <div id="uploadDocumentPreview" class="employee-doc-upload-preview hidden mt-4" aria-live="polite">
          <div>
            <p id="uploadDocumentPreviewName" class="font-medium text-slate-800">No file selected</p>
            <p id="uploadDocumentPreviewMeta" class="text-xs text-slate-500 mt-1">Select a file to preview it here before uploading.</p>
          </div>
          <button type="button" id="uploadDocumentClear" class="text-sm text-rose-600 hover:text-rose-700">Remove</button>
        </div>
      </div>

      <div class="px-6 py-4 border-t flex justify-end gap-3">
        <button type="button" data-close-upload class="px-4 py-2 text-sm rounded-lg border">Cancel</button>
        <button type="submit" class="px-4 py-2 text-sm rounded-lg bg-daGreen text-white">Upload</button>
      </div>
    </form>
  </div>
</div>

<div id="versionModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden" aria-hidden="true">
  <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-semibold">Upload New Version</h2>
      <button type="button" data-close-version class="text-gray-400 hover:text-gray-600"><span class="material-icons">close</span></button>
    </div>

    <form method="post" action="document-management.php" enctype="multipart/form-data" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
      <input type="hidden" name="action" value="upload_new_version">
      <input type="hidden" id="versionDocumentId" name="document_id" value="">

      <p id="versionDocumentTitle" class="text-sm text-gray-600"></p>

      <div>
        <label class="text-sm font-medium">Version File (max 10 MB)</label>
        <input name="version_file" type="file" class="w-full mt-1 text-sm" required>
      </div>

      <div class="flex justify-end gap-3 pt-4">
        <button type="button" data-close-version class="px-4 py-2 text-sm rounded-lg border">Cancel</button>
        <button type="submit" class="px-4 py-2 text-sm rounded-lg bg-daGreen text-white">Upload Version</button>
      </div>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include './includes/layout.php';
