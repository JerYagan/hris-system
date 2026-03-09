<?php
require_once __DIR__ . '/includes/support/bootstrap.php';
require_once __DIR__ . '/includes/support/actions.php';
require_once __DIR__ . '/includes/support/data.php';

$pageTitle   = 'Support | DA HRIS';
$activePage  = 'support.php';
$breadcrumbs = ['Support'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/employee/support/index.js';

ob_start();

$escape = static function (mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$formatDateTime = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '-';
    }

    $ts = strtotime($value);
    return $ts === false ? '-' : date('M j, Y g:i A', $ts);
};

$buildQuery = static function (array $params): string {
    return http_build_query(array_filter($params, static fn($v) => $v !== null && $v !== ''));
};
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold">Support Center</h1>
  <p class="text-sm text-gray-500">Submit support requests and track your ticket updates.</p>
</div>

<div id="supportPageFeedback" class="hidden" data-state="<?= $escape((string)($state ?? '')) ?>" data-message="<?= $escape((string)($message ?? '')) ?>"></div>

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

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
  <div class="bg-white rounded-xl shadow p-4 border">
    <p class="text-xs text-gray-500">Total Tickets</p>
    <p class="text-2xl font-bold mt-1"><?= $escape((string)($supportSummary['total_inquiries'] ?? 0)) ?></p>
  </div>
  <div class="bg-white rounded-xl shadow p-4 border">
    <p class="text-xs text-gray-500">Open Tickets</p>
    <p class="text-2xl font-bold mt-1 text-amber-700"><?= $escape((string)($supportSummary['open_tickets'] ?? 0)) ?></p>
  </div>
  <div class="bg-white rounded-xl shadow p-4 border">
    <p class="text-xs text-gray-500">Last 30 Days</p>
    <p class="text-2xl font-bold mt-1"><?= $escape((string)($supportSummary['recent_30_days'] ?? 0)) ?></p>
  </div>
</div>

<section class="bg-white rounded-xl shadow p-6 mb-6">
  <h2 class="text-lg font-semibold mb-4">Submit Profile Change Support Request</h2>

  <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4" id="supportInquiryForm">
    <input type="hidden" name="csrf_token" value="<?= $escape(ensureCsrfToken()) ?>">
    <input type="hidden" name="action" value="submit_support_inquiry">

    <div>
      <label for="supportCategory" class="block text-xs font-semibold text-gray-600 mb-1">Category</label>
      <select id="supportCategory" name="inquiry_category" class="w-full border rounded-lg px-3 py-2 text-sm" required>
        <option value="profile_change" selected>Profile Change</option>
        <option value="technical_issues">Technical Issues</option>
        <option value="account_management">Account Management</option>
        <option value="payroll_benefits">Payroll and Benefits</option>
        <option value="training_development">Training and Development</option>
        <option value="documents_records">Documents and Records</option>
        <option value="timekeeping_attendance">Timekeeping and Attendance</option>
        <option value="other">Other</option>
      </select>
    </div>

    <div class="md:col-span-2">
      <label for="supportSubject" class="block text-xs font-semibold text-gray-600 mb-1">Subject</label>
      <input id="supportSubject" type="text" name="subject" maxlength="150" class="w-full border rounded-lg px-3 py-2 text-sm" required placeholder="Short summary of your concern">
    </div>

    <div class="md:col-span-2">
      <label for="supportMessage" class="block text-xs font-semibold text-gray-600 mb-1">Message</label>
      <textarea id="supportMessage" name="message" rows="5" maxlength="3000" class="w-full border rounded-lg px-3 py-2 text-sm" required placeholder="Provide details so HR/Admin can assist you faster."></textarea>
      <div id="supportMessageCounter" class="mt-1 text-xs text-gray-500">0 / 3000</div>
    </div>

    <div class="md:col-span-2">
      <label class="block text-xs font-semibold text-gray-600 mb-1">Supporting Attachment</label>
      <input id="supportAttachment" type="file" name="support_attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="hidden" required>

      <button type="button" id="supportAttachmentTrigger" class="inline-flex items-center gap-2 rounded-md border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
        <span class="material-symbols-outlined text-[18px]">attach_file</span>
        Select Attachment
      </button>

      <p id="supportAttachmentFilename" class="mt-2 text-xs text-slate-500">No file selected.</p>
      <p class="mt-1 text-xs text-gray-500">Required. Accepted: PDF, JPG, PNG, DOC, DOCX (max 5MB).</p>

      <div id="supportAttachmentPreview" class="mt-3 hidden rounded-lg border border-slate-200 bg-slate-50 p-3">
        <div class="flex items-start gap-3">
          <img id="supportAttachmentPreviewImage" src="" alt="Attachment preview" class="hidden h-16 w-16 rounded border border-slate-200 object-cover">
          <div class="min-w-0 flex-1">
            <p id="supportAttachmentPreviewName" class="truncate text-sm font-medium text-slate-800"></p>
            <p id="supportAttachmentPreviewMeta" class="text-xs text-slate-600"></p>
          </div>
        </div>
      </div>
    </div>

    <div class="md:col-span-2 flex justify-end">
      <button type="submit" class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm font-semibold">Submit Ticket</button>
    </div>
  </form>
</section>

<section class="bg-white rounded-xl shadow p-6">
  <h2 class="text-lg font-semibold mb-4">Recent Support Tickets</h2>

  <form method="get" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
    <div>
      <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
      <input type="search" name="search" value="<?= $escape((string)($supportSearch ?? '')) ?>" placeholder="Subject, ticket id, message" class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
      <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
      <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm">
        <option value="">All Statuses</option>
        <?php foreach (['submitted', 'in_review', 'forwarded_to_staff', 'resolved', 'rejected'] as $statusOption): ?>
          <option value="<?= $escape($statusOption) ?>" <?= (string)($supportStatusFilter ?? '') === $statusOption ? 'selected' : '' ?>><?= $escape(ucwords(str_replace('_', ' ', $statusOption))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs font-semibold text-gray-600 mb-1">Category</label>
      <select name="category" class="w-full border rounded-lg px-3 py-2 text-sm">
        <option value="">All Categories</option>
        <?php foreach ((array)($supportCategoryOptions ?? []) as $categoryOption): ?>
          <option value="<?= $escape((string)$categoryOption) ?>" <?= (string)($supportCategoryFilter ?? '') === (string)$categoryOption ? 'selected' : '' ?>><?= $escape(ucwords(str_replace('_', ' ', (string)$categoryOption))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="flex gap-2 justify-end">
      <button type="submit" class="border px-3 py-2 rounded-lg text-sm font-semibold">Apply</button>
      <a href="support.php" class="border px-3 py-2 rounded-lg text-sm">Reset</a>
    </div>
  </form>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="border-b text-gray-500">
        <tr>
          <th class="text-left py-3">Date</th>
          <th class="text-left py-3">Category</th>
          <th class="text-left py-3">Subject</th>
          <th class="text-left py-3">Status</th>
          <th class="text-left py-3">Attachment</th>
          <th class="text-left py-3">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($supportInquiries)): ?>
          <tr><td class="py-3 text-gray-500" colspan="6">No support ticket history yet.</td></tr>
        <?php else: ?>
          <?php foreach ($supportInquiries as $inquiry): ?>
            <tr class="border-b">
              <td class="py-3"><?= $escape($formatDateTime((string)($inquiry['created_at'] ?? ''))) ?></td>
              <td class="py-3"><?= $escape(ucwords(str_replace('_', ' ', (string)($inquiry['category'] ?? 'general')))) ?></td>
              <td class="py-3"><?= $escape((string)($inquiry['subject'] ?? 'Support Ticket')) ?></td>
              <td class="py-3"><span class="px-2 py-1 rounded-full text-xs <?= $escape((string)($inquiry['status_class'] ?? 'bg-slate-100 text-slate-700')) ?>"><?= $escape(ucfirst(str_replace('_', ' ', (string)($inquiry['status'] ?? 'submitted')))) ?></span></td>
              <td class="py-3">
                <?php if (!empty($inquiry['attachment_path'])): ?>
                  <a class="text-xs text-daGreen hover:underline" target="_blank" rel="noopener" href="<?= $escape('/hris-system/' . ltrim((string)$inquiry['attachment_path'], '/')) ?>">View</a>
                <?php else: ?>
                  <span class="text-xs text-gray-400">-</span>
                <?php endif; ?>
              </td>
              <td class="py-3">
                <button
                  type="button"
                  class="border px-3 py-1 rounded text-xs"
                  data-open-support-detail
                  data-detail-subject="<?= $escape((string)($inquiry['subject'] ?? 'Support Ticket')) ?>"
                  data-detail-body="<?= $escape((string)($inquiry['message'] ?? '')) ?>"
                  data-detail-notes="<?= $escape((string)((string)($inquiry['resolution_notes'] ?? '') !== '' ? $inquiry['resolution_notes'] : ((string)($inquiry['admin_notes'] ?? '') !== '' ? $inquiry['admin_notes'] : ($inquiry['staff_notes'] ?? '')))) ?>"
                >
                  View
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php if (!empty($supportPagination) && (($supportPagination['has_previous'] ?? false) || ($supportPagination['has_next'] ?? false))): ?>
  <div class="mt-4 flex justify-between items-center text-sm">
    <div class="text-gray-500">Page <?= $escape((string)($supportPagination['page'] ?? 1)) ?></div>
    <div class="flex gap-2">
      <?php if (!empty($supportPagination['has_previous'])): ?>
        <a class="border px-3 py-1 rounded" href="?<?= $escape($buildQuery(['page' => $supportPagination['previous_page'] ?? 1, 'search' => $supportSearch ?? '', 'status' => $supportStatusFilter ?? '', 'category' => $supportCategoryFilter ?? ''])) ?>">Previous</a>
      <?php endif; ?>
      <?php if (!empty($supportPagination['has_next'])): ?>
        <a class="border px-3 py-1 rounded" href="?<?= $escape($buildQuery(['page' => $supportPagination['next_page'] ?? 2, 'search' => $supportSearch ?? '', 'status' => $supportStatusFilter ?? '', 'category' => $supportCategoryFilter ?? ''])) ?>">Next</a>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<div id="supportDetailModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden" aria-hidden="true">
  <div class="bg-white w-full max-w-xl rounded-xl shadow-lg max-h-[90vh] flex flex-col">
    <div class="px-6 py-4 border-b flex justify-between items-center">
      <h2 class="text-lg font-semibold" id="supportDetailTitle">Support Inquiry</h2>
      <button type="button" data-close-support-detail><span class="material-icons">close</span></button>
    </div>
    <div class="px-6 py-5 overflow-y-auto">
      <p class="text-sm text-gray-700 whitespace-pre-line" id="supportDetailBody"></p>
      <div id="supportDetailNotesWrap" class="mt-4 hidden rounded-lg border border-slate-200 bg-slate-50 p-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Support Update</p>
        <p class="mt-1 text-sm text-slate-700 whitespace-pre-line" id="supportDetailNotes"></p>
      </div>
    </div>
    <div class="px-6 py-4 border-t flex justify-end">
      <button type="button" data-close-support-detail class="border px-4 py-2 rounded-lg text-sm">Close</button>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include './includes/layout.php';
