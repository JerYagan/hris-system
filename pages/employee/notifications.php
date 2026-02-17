<?php
/**
 * Notifications
 * DA-ATI HRIS
 */

require_once __DIR__ . '/includes/notifications/bootstrap.php';
require_once __DIR__ . '/includes/notifications/actions.php';
require_once __DIR__ . '/includes/notifications/data.php';

$pageTitle   = 'Notifications | DA HRIS';
$activePage  = 'notifications.php';
$breadcrumbs = ['Notifications'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/employee/notifications/index.js';

ob_start();

$escape = static function (mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$categoryIcon = static function (string $value): string {
    $key = strtolower(trim($value));
    if (str_contains($key, 'system')) {
        return 'campaign';
    }
    if (str_contains($key, 'hr')) {
        return 'announcement';
    }
    if (str_contains($key, 'application')) {
        return 'update';
    }
    if (str_contains($key, 'learning') || str_contains($key, 'development')) {
      return 'school';
    }

    return 'notifications';
};

$buildQuery = static function (array $params): string {
    return http_build_query(array_filter($params, static fn($v) => $v !== null && $v !== ''));
};
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold">Notifications</h1>
  <p class="text-sm text-gray-500">System alerts, HR announcements, and application updates.</p>
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
    <p class="text-xs text-gray-500">Total Notifications</p>
    <p id="notificationTotalCount" class="text-2xl font-bold mt-1"><?= $escape((string)($notificationSummary['total'] ?? 0)) ?></p>
  </div>
  <div class="bg-white rounded-xl shadow p-4 border">
    <p class="text-xs text-gray-500">Unread</p>
    <p id="notificationUnreadCount" class="text-2xl font-bold mt-1 text-daGreen"><?= $escape((string)($notificationSummary['unread'] ?? 0)) ?></p>
  </div>
  <div class="bg-white rounded-xl shadow p-4 border">
    <p class="text-xs text-gray-500">Read</p>
    <p id="notificationReadCount" class="text-2xl font-bold mt-1"><?= $escape((string)($notificationSummary['read'] ?? 0)) ?></p>
  </div>
</div>

<div class="bg-white rounded-xl shadow p-4 mb-6">
  <div class="flex flex-col md:flex-row gap-3 md:items-end md:justify-between">
    <form method="get" class="flex flex-wrap gap-3 text-sm items-end" id="notificationFilterForm">
      <div>
        <label class="block text-xs text-gray-500 mb-1" for="notifCategory">Category</label>
        <select id="notifCategory" name="category" class="border rounded-lg px-3 py-2">
          <option value="all" <?= $selectedCategory === 'all' ? 'selected' : '' ?>>All Categories</option>
          <option value="system" <?= $selectedCategory === 'system' ? 'selected' : '' ?>>System Alerts</option>
          <option value="hr" <?= $selectedCategory === 'hr' ? 'selected' : '' ?>>HR Announcements</option>
          <option value="application" <?= $selectedCategory === 'application' ? 'selected' : '' ?>>Application Updates</option>
          <option value="learning_and_development" <?= $selectedCategory === 'learning_and_development' ? 'selected' : '' ?>>Learning and Development</option>
          <option value="general" <?= $selectedCategory === 'general' ? 'selected' : '' ?>>General</option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1" for="notifStatus">Status</label>
        <select id="notifStatus" name="status" class="border rounded-lg px-3 py-2">
          <option value="all" <?= $selectedStatus === 'all' ? 'selected' : '' ?>>All Status</option>
          <option value="unread" <?= $selectedStatus === 'unread' ? 'selected' : '' ?>>Unread</option>
          <option value="read" <?= $selectedStatus === 'read' ? 'selected' : '' ?>>Read</option>
        </select>
      </div>
      <button type="submit" class="bg-daGreen text-white px-4 py-2 rounded-lg inline-flex items-center gap-1.5 self-end md:self-auto shrink-0"><span class="material-icons text-sm">filter_alt</span>Apply</button>
    </form>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= $escape(ensureCsrfToken()) ?>">
      <input type="hidden" name="action" value="mark_all_notifications_read">
      <button id="markAllReadButton" type="submit" class="border px-4 py-2 rounded-lg text-sm inline-flex items-center gap-1.5" <?= ($notificationSummary['unread'] ?? 0) > 0 ? '' : 'disabled' ?>><span class="material-icons text-sm">done_all</span>Mark All Read</button>
    </form>
  </div>
</div>

<div class="bg-white rounded-xl shadow divide-y">
  <?php if (empty($notifications)): ?>
    <div class="px-6 py-8 text-sm text-gray-500">No notifications found for the selected filters.</div>
  <?php else: ?>
    <?php foreach ($notifications as $notification): ?>
      <?php
        $isRead = (bool)($notification['is_read'] ?? false);
        $category = (string)($notification['category'] ?? 'General');
      ?>
      <div class="px-6 py-4 hover:bg-gray-50 flex items-start gap-4 <?= $isRead ? '' : 'bg-green-50' ?>" data-notification-item data-notification-id="<?= $escape((string)($notification['id'] ?? '')) ?>" data-is-read="<?= $isRead ? '1' : '0' ?>">
        <span class="material-icons mt-1 text-daGreen"><?= $escape($categoryIcon($category)) ?></span>

        <button
          type="button"
          class="flex-1 text-left"
          data-open-notification
          data-notification-id="<?= $escape((string)($notification['id'] ?? '')) ?>"
          data-notification-title="<?= $escape((string)($notification['title'] ?? 'Notification')) ?>"
          data-notification-body="<?= $escape((string)($notification['body'] ?? '')) ?>"
          data-notification-category="<?= $escape(formatNotificationCategoryLabel($category)) ?>"
          data-notification-created="<?= $escape(formatDateTimeForPhilippines((string)($notification['created_at'] ?? ''))) ?>"
          data-notification-link="<?= $escape((string)($notification['link_url'] ?? '')) ?>"
          data-notification-is-read="<?= $isRead ? '1' : '0' ?>"
        >
          <p class="font-medium"><?= $escape((string)($notification['title'] ?? 'Notification')) ?></p>
          <p class="text-sm text-gray-600"><?= $escape((string)($notification['body'] ?? '')) ?></p>
          <p class="text-xs text-gray-500 mt-1"><?= $escape(formatNotificationCategoryLabel($category)) ?> · <?= $escape(formatDateTimeForPhilippines((string)($notification['created_at'] ?? ''))) ?></p>
        </button>

        <div class="flex items-center gap-2">
          <span class="w-2 h-2 bg-daGreen rounded-full <?= $isRead ? 'hidden' : '' ?>" title="Unread" data-notification-unread-dot></span>
          <form method="post" data-notification-mark-form class="<?= $isRead ? 'hidden' : '' ?>">
              <input type="hidden" name="csrf_token" value="<?= $escape(ensureCsrfToken()) ?>">
              <input type="hidden" name="action" value="mark_notification_read">
              <input type="hidden" name="notification_id" value="<?= $escape((string)($notification['id'] ?? '')) ?>">
              <button type="submit" class="border px-2 py-1 rounded text-xs inline-flex items-center gap-1"><span class="material-icons text-xs">done</span>Mark Read</button>
          </form>
          <span class="text-xs text-gray-500 <?= $isRead ? '' : 'hidden' ?>" data-notification-read-label>Read</span>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div id="notificationModal"
     class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden"
     aria-hidden="true">

  <div class="bg-white w-full max-w-xl rounded-xl shadow-lg
              max-h-[90vh] flex flex-col">

    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 class="text-lg font-semibold" id="notificationModalTitle">Notification Details</h2>
      <button type="button" data-close-notification>
        <span class="material-icons">close</span>
      </button>
    </div>

    <div class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <p class="text-xs text-gray-500" id="notificationModalMeta"></p>
      <p class="font-semibold" id="notificationModalHeading"></p>
      <p class="text-gray-700 whitespace-pre-line" id="notificationModalBody"></p>
    </div>

    <div class="px-6 py-4 border-t flex justify-between items-center shrink-0">
      <a id="notificationModalLink" href="#" target="_self" class="text-sm text-daGreen hidden">Open Related Link</a>
      <button type="button" data-close-notification class="border px-4 py-2 rounded-lg text-sm">
        Close
      </button>
    </div>

  </div>
</div>

<?php if (!empty($notificationPagination) && (($notificationPagination['has_previous'] ?? false) || ($notificationPagination['has_next'] ?? false))): ?>
  <div class="mt-4 flex justify-between items-center text-sm">
    <div class="text-gray-500">Page <?= $escape((string)($notificationPagination['page'] ?? 1)) ?></div>
    <div class="flex gap-2">
      <?php if (!empty($notificationPagination['has_previous'])): ?>
        <a class="border px-3 py-1 rounded" href="?<?= $escape($buildQuery([
            'category' => $selectedCategory,
            'status' => $selectedStatus,
            'page' => $notificationPagination['previous_page'] ?? 1,
        ])) ?>">Previous</a>
      <?php endif; ?>
      <?php if (!empty($notificationPagination['has_next'])): ?>
        <a class="border px-3 py-1 rounded" href="?<?= $escape($buildQuery([
            'category' => $selectedCategory,
            'status' => $selectedStatus,
            'page' => $notificationPagination['next_page'] ?? 2,
        ])) ?>">Next</a>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include './includes/layout.php';
