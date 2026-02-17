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

$priorityClass = static function (string $value): string {
    return match (strtolower(trim($value))) {
        'high' => 'bg-red-100 text-red-800',
        'low' => 'bg-blue-100 text-blue-800',
        default => 'bg-gray-200 text-gray-700',
    };
};

$buildQuery = static function (array $params): string {
    return http_build_query(array_filter($params, static fn($v) => $v !== null && $v !== ''));
};
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold">Support Center</h1>
  <p class="text-sm text-gray-500">Submit support inquiries and track your recent requests.</p>
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

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
  <div class="bg-white rounded-xl shadow p-4 border">
    <p class="text-xs text-gray-500">Total Inquiries</p>
    <p class="text-2xl font-bold mt-1"><?= $escape((string)($supportSummary['total_inquiries'] ?? 0)) ?></p>
  </div>
  <div class="bg-white rounded-xl shadow p-4 border">
    <p class="text-xs text-gray-500">High Priority</p>
    <p class="text-2xl font-bold mt-1 text-red-700"><?= $escape((string)($supportSummary['high_priority'] ?? 0)) ?></p>
  </div>
  <div class="bg-white rounded-xl shadow p-4 border">
    <p class="text-xs text-gray-500">Last 30 Days</p>
    <p class="text-2xl font-bold mt-1"><?= $escape((string)($supportSummary['recent_30_days'] ?? 0)) ?></p>
  </div>
</div>

<section class="bg-white rounded-xl shadow p-6 mb-6">
  <h2 class="text-lg font-semibold mb-4">Submit Support Inquiry</h2>

  <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4" id="supportInquiryForm">
    <input type="hidden" name="csrf_token" value="<?= $escape(ensureCsrfToken()) ?>">
    <input type="hidden" name="action" value="submit_support_inquiry">

    <div>
      <label for="supportCategory" class="block text-xs font-semibold text-gray-600 mb-1">Category</label>
      <select id="supportCategory" name="inquiry_category" class="w-full border rounded-lg px-3 py-2 text-sm" required>
        <option value="">Select category</option>
        <option value="general">General</option>
        <option value="account">Account</option>
        <option value="payroll">Payroll</option>
        <option value="timekeeping">Timekeeping</option>
        <option value="documents">Documents</option>
        <option value="technical">Technical</option>
      </select>
    </div>

    <div>
      <label for="supportPriority" class="block text-xs font-semibold text-gray-600 mb-1">Priority</label>
      <select id="supportPriority" name="priority" class="w-full border rounded-lg px-3 py-2 text-sm" required>
        <option value="normal">Normal</option>
        <option value="low">Low</option>
        <option value="high">High</option>
      </select>
    </div>

    <div class="md:col-span-2">
      <label for="supportSubject" class="block text-xs font-semibold text-gray-600 mb-1">Subject</label>
      <input id="supportSubject" type="text" name="subject" maxlength="150" class="w-full border rounded-lg px-3 py-2 text-sm" required placeholder="Short summary of your concern">
    </div>

    <div class="md:col-span-2">
      <label for="supportMessage" class="block text-xs font-semibold text-gray-600 mb-1">Message</label>
      <textarea id="supportMessage" name="message" rows="5" maxlength="3000" class="w-full border rounded-lg px-3 py-2 text-sm" required placeholder="Provide details so HR/IT can assist you faster."></textarea>
      <div id="supportMessageCounter" class="mt-1 text-xs text-gray-500">0 / 3000</div>
    </div>

    <div class="md:col-span-2 flex justify-end">
      <button type="submit" class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm font-semibold">Submit Inquiry</button>
    </div>
  </form>
</section>

<section class="bg-white rounded-xl shadow p-6">
  <h2 class="text-lg font-semibold mb-4">Recent Inquiry History</h2>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="border-b text-gray-500">
        <tr>
          <th class="text-left py-3">Date</th>
          <th class="text-left py-3">Category</th>
          <th class="text-left py-3">Subject</th>
          <th class="text-left py-3">Priority</th>
          <th class="text-left py-3">Status</th>
          <th class="text-left py-3">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($supportInquiries)): ?>
          <tr><td class="py-3 text-gray-500" colspan="6">No support inquiry history yet.</td></tr>
        <?php else: ?>
          <?php foreach ($supportInquiries as $inquiry): ?>
            <tr class="border-b">
              <td class="py-3"><?= $escape($formatDateTime((string)($inquiry['created_at'] ?? ''))) ?></td>
              <td class="py-3"><?= $escape(ucwords((string)($inquiry['category'] ?? 'general'))) ?></td>
              <td class="py-3"><?= $escape((string)($inquiry['subject'] ?? 'Support Inquiry')) ?></td>
              <td class="py-3"><span class="px-2 py-1 rounded-full text-xs <?= $escape($priorityClass((string)($inquiry['priority'] ?? 'normal'))) ?>"><?= $escape(ucfirst((string)($inquiry['priority'] ?? 'normal'))) ?></span></td>
              <td class="py-3"><span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800"><?= $escape(ucfirst((string)($inquiry['status'] ?? 'submitted'))) ?></span></td>
              <td class="py-3">
                <button type="button" class="border px-3 py-1 rounded text-xs" data-open-support-detail data-detail-subject="<?= $escape((string)($inquiry['subject'] ?? 'Support Inquiry')) ?>" data-detail-body="<?= $escape((string)($inquiry['message'] ?? '')) ?>">View</button>
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
        <a class="border px-3 py-1 rounded" href="?<?= $escape($buildQuery(['page' => $supportPagination['previous_page'] ?? 1])) ?>">Previous</a>
      <?php endif; ?>
      <?php if (!empty($supportPagination['has_next'])): ?>
        <a class="border px-3 py-1 rounded" href="?<?= $escape($buildQuery(['page' => $supportPagination['next_page'] ?? 2])) ?>">Next</a>
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
    </div>
    <div class="px-6 py-4 border-t flex justify-end">
      <button type="button" data-close-support-detail class="border px-4 py-2 rounded-lg text-sm">Close</button>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include './includes/layout.php';
