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
        'approved' => ['Approved', 'bg-approved text-green-800'],
        'submitted' => ['Submitted', 'bg-pending text-yellow-800'],
        'rejected' => ['Rejected', 'bg-rejected text-red-800'],
        'archived' => ['Archived', 'bg-gray-200 text-gray-700'],
        default => ['Draft', 'bg-blue-100 text-blue-700'],
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

$detect201Type = static function (array $document): ?string {
    $dictionary = [
      'PDS' => ['pds', 'personal data sheet'],
      'SSS' => ['sss'],
      'Pagibig' => ['pagibig', 'pag-ibig'],
      'Philhealth' => ['philhealth'],
      'NBI' => ['nbi'],
      'Mayors Permits' => ['mayor', 'permit'],
      'Medical' => ['medical'],
      'Drug Test' => ['drug test', 'drugtest'],
      'Health Card' => ['health card', 'healthcard'],
      'Cedula' => ['cedula', 'community tax certificate'],
      'Resume/ CV' => ['resume', 'cv', 'curriculum vitae'],
    ];

    $haystack = strtolower(trim(
      (string)($document['title'] ?? '')
      . ' '
      . (string)($document['category_name'] ?? '')
      . ' '
      . (string)($document['description'] ?? '')
    ));

    foreach ($dictionary as $label => $keywords) {
      foreach ($keywords as $keyword) {
        if (str_contains($haystack, strtolower($keyword))) {
          return $label;
        }
      }
    }

    return null;
};

$document201Types = ['PDS', 'SSS', 'Pagibig', 'Philhealth', 'NBI', 'Mayors Permits', 'Medical', 'Drug Test', 'Health Card', 'Cedula', 'Resume/ CV'];
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold">Document Management</h1>
  <p class="text-sm text-gray-500">Upload, version, and track your employee documents.</p>
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

<div class="bg-white rounded-xl shadow p-6 mb-6 flex flex-col md:flex-row gap-4 md:items-end">
  <div class="w-full md:flex-1 md:min-w-[460px]">
    <label class="text-sm text-gray-600">Search</label>
    <input id="documentSearchInput" type="search" placeholder="Search title, category, status" class="w-full mt-1 px-3 py-2 bg-gray-100 rounded-lg text-sm">
  </div>

  <div class="w-full md:w-auto md:ml-auto flex flex-col sm:flex-row gap-3 sm:items-end">
    <div class="w-full sm:w-56">
      <label class="text-sm text-gray-600">Category</label>
      <select id="documentCategoryFilter" class="w-full mt-1 px-3 py-2 bg-gray-100 rounded-lg text-sm">
        <option value="">All Categories</option>
        <?php foreach ($documentCategories as $category): ?>
          <option value="<?= $escape(strtolower((string)($category['category_name'] ?? ''))) ?>"><?= $escape($category['category_name'] ?? '') ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="w-full sm:w-44">
      <label class="text-sm text-gray-600">Status</label>
      <select id="documentStatusFilter" class="w-full mt-1 px-3 py-2 bg-gray-100 rounded-lg text-sm">
        <option value="">All Status</option>
        <option value="draft">Draft</option>
        <option value="submitted">Submitted</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
        <option value="archived">Archived</option>
      </select>
    </div>

    <div class="w-full sm:w-52">
      <label class="text-sm text-gray-600">Document Group</label>
      <select id="documentGroupFilter" class="w-full mt-1 px-3 py-2 bg-gray-100 rounded-lg text-sm">
        <option value="all">All Documents</option>
        <option value="201">201 Files Only</option>
      </select>
    </div>

    <div class="w-full sm:w-52">
      <label class="text-sm text-gray-600">201 File Type</label>
      <select id="document201TypeFilter" class="w-full mt-1 px-3 py-2 bg-gray-100 rounded-lg text-sm">
        <option value="">All 201 Types</option>
        <?php foreach ($document201Types as $type): ?>
          <option value="<?= $escape(strtolower($type)) ?>"><?= $escape($type) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <button data-open-upload class="bg-daGreen text-white px-5 py-2 rounded-lg text-sm font-medium hover:opacity-90 h-[42px] inline-flex items-center gap-1.5"><span class="material-icons text-sm">upload_file</span>Upload Document</button>
  </div>
</div>

<p class="text-xs text-gray-500 mb-6">Tip: Use the <strong>Document Group</strong> filter to show only 201 File documents (official personnel records such as PDS, SSS, Pagibig, Philhealth, NBI, permits, and other employment essentials).</p>

<div class="bg-white rounded-xl shadow p-6">
  <h2 class="text-lg font-bold mb-6">Uploaded <span class="text-daGreen">Documents</span></h2>

  <?php if (empty($employeeDocuments)): ?>
    <div id="documentTrueEmpty" class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center">
      <p class="font-medium text-gray-700">No documents yet</p>
      <p class="text-sm text-gray-500 mt-1">Upload your first document to start tracking review status and version history.</p>
      <button data-open-upload class="mt-4 bg-daGreen text-white px-4 py-2 rounded-lg text-sm">Upload Now</button>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b text-gray-500">
            <th class="text-left py-3">Document</th>
            <th class="text-left py-3">Category</th>
            <th class="text-left py-3">Uploaded</th>
            <th class="text-left py-3">Status</th>
            <th class="text-left py-3">Actions</th>
          </tr>
        </thead>
        <tbody id="documentTableBody">
          <?php foreach ($employeeDocuments as $document): ?>
            <?php
              $documentId = (string)($document['id'] ?? '');
              $categoryName = (string)($document['category_name'] ?? 'Uncategorized');
              $document201Type = $detect201Type($document);
              $is201File = $document201Type !== null;
              $status = (string)($document['document_status'] ?? 'draft');
              [$statusLabel, $statusClass] = $statusMeta($status);
              $versions = (array)($documentVersionsById[$documentId] ?? []);
              $reviews = (array)($documentReviewsById[$documentId] ?? []);
              $latestVersion = !empty($versions) ? (array)$versions[0] : [];
              $latestFileName = (string)($latestVersion['file_name'] ?? (string)($document['title'] ?? ''));
              $latestExt = strtolower((string)pathinfo($latestFileName, PATHINFO_EXTENSION));
              [$fileIcon, $fileIconClass, $fileTypeLabel] = $fileTypeMeta($latestExt);
              $detailsModalId = 'document-details-' . $documentId;
            ?>
            <tr class="border-b" data-document-row data-search="<?= $escape(strtolower((string)($document['title'] ?? '') . ' ' . strtolower($categoryName) . ' ' . strtolower($statusLabel) . ' ' . strtolower($fileTypeLabel) . ' ' . strtolower((string)($document201Type ?? ''))) ) ?>" data-category="<?= $escape(strtolower($categoryName)) ?>" data-status="<?= $escape(strtolower($status)) ?>" data-group="<?= $is201File ? '201' : 'other' ?>" data-201-type="<?= $escape(strtolower((string)($document201Type ?? ''))) ?>">
              <td class="py-3">
                <div class="flex items-start gap-2">
                  <span class="material-icons text-lg mt-0.5 <?= $escape($fileIconClass) ?>" title="<?= $escape($fileTypeLabel) ?>"><?= $escape($fileIcon) ?></span>
                  <div>
                    <p class="font-medium"><?= $escape($document['title'] ?? 'Document') ?></p>
                    <p class="text-xs text-gray-500"><?= $escape($document['description'] ?? '') ?></p>
                    <?php if ($is201File): ?>
                      <p class="text-xs text-daGreen mt-1">201 File · <?= $escape((string)$document201Type) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td class="py-3"><?= $escape($categoryName) ?></td>
              <td class="py-3"><?= $escape($formatDate($document['updated_at'] ?? '')) ?></td>
              <td class="py-3"><span class="px-3 py-1 rounded-full <?= $escape($statusClass) ?>"><?= $escape($statusLabel) ?></span></td>
              <td class="py-3">
                <div class="flex flex-wrap gap-2">
                  <a href="view-document.php?document_id=<?= $escape($documentId) ?>" target="_blank" rel="noopener" class="border px-3 py-1 rounded-lg text-xs inline-flex items-center gap-1.5"><span class="material-icons text-sm">visibility</span>View</a>
                  <a href="download-document.php?document_id=<?= $escape($documentId) ?>" class="border px-3 py-1 rounded-lg text-xs inline-flex items-center gap-1.5"><span class="material-icons text-sm">download</span>Download</a>
                  <button type="button" data-open-details="<?= $escape($detailsModalId) ?>" class="border px-3 py-1 rounded-lg text-xs inline-flex items-center gap-1.5"><span class="material-icons text-sm">info</span>View Details</button>
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
                    <button type="button" data-open-version data-document-id="<?= $escape($documentId) ?>" data-document-title="<?= $escape($document['title'] ?? 'Document') ?>" class="border px-3 py-2 rounded-lg text-xs inline-flex items-center gap-1.5"><span class="material-icons text-sm">edit</span>Upload New Version</button>
                    <form method="post" action="document-management.php" onsubmit="return confirm('Archive this document?');">
                      <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                      <input type="hidden" name="action" value="archive_document">
                      <input type="hidden" name="document_id" value="<?= $escape($documentId) ?>">
                      <button type="submit" class="border border-red-300 text-red-700 px-3 py-2 rounded-lg text-xs inline-flex items-center gap-1.5"><span class="material-icons text-sm">delete</span>Archive</button>
                    </form>
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
  <?php endif; ?>
</div>

<div id="uploadModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden" aria-hidden="true">
  <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-semibold">Upload Document</h2>
      <button type="button" data-close-upload class="text-gray-400 hover:text-gray-600"><span class="material-icons">close</span></button>
    </div>

    <form method="post" action="document-management.php" enctype="multipart/form-data" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
      <input type="hidden" name="action" value="upload_document">

      <div>
        <label class="text-sm font-medium">Document Title</label>
        <input name="title" type="text" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm" required>
      </div>

      <div>
        <label class="text-sm font-medium">Category</label>
        <select name="category_id" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm" required>
          <option value="">Select category</option>
          <?php foreach ($documentCategories as $category): ?>
            <option value="<?= $escape($category['id'] ?? '') ?>"><?= $escape($category['category_name'] ?? '') ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="text-sm font-medium">Description</label>
        <textarea name="description" rows="3" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm" placeholder="Optional"></textarea>
      </div>

      <div>
        <label class="text-sm font-medium">Upload File (max 10 MB)</label>
        <input name="document_file" type="file" class="w-full mt-1 text-sm" required>
      </div>

      <div class="flex justify-end gap-3 pt-4">
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
