<?php
require_once __DIR__ . '/includes/notifications/bootstrap.php';
require_once __DIR__ . '/includes/notifications/actions.php';
require_once __DIR__ . '/includes/notifications/data.php';

$csrfToken = ensureCsrfToken();

$pageTitle = 'Notifications | DA HRIS';
$activePage = 'notifications.php';
$breadcrumbs = ['Notifications'];
$retryUrl = htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? 'notifications.php'), ENT_QUOTES, 'UTF-8');

ob_start();
?>

<?php if (!empty($message)): ?>
<section class="mb-6 rounded-xl border px-4 py-3 text-sm <?= ($state ?? '') === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
    <?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>
</section>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
<section class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <span><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></span>
        <a href="<?= $retryUrl ?>" class="inline-flex items-center justify-center rounded-md border border-amber-300 bg-white px-3 py-1.5 text-xs text-amber-800 hover:bg-amber-100">Retry</a>
    </div>
</section>
<?php endif; ?>

<section class="mb-5 rounded-xl border bg-white p-4 sm:p-5">
    <div class="rounded-xl border bg-gradient-to-r from-green-50 via-white to-green-50 p-4 sm:p-5">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <span class="material-symbols-outlined rounded-xl bg-green-700 p-2 text-3xl text-white">notifications</span>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Notification Inbox</h1>
                    <p class="mt-1 text-sm text-gray-600">Stay updated with changes to your applications and recruitment announcements.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="rounded-lg border bg-white px-4 py-3 text-center">
                    <p class="font-semibold text-gray-800"><?= (int)$notificationStats['recent'] ?></p>
                    <p class="text-xs text-gray-500">Recent Updates</p>
                </div>
                <div class="rounded-lg border bg-white px-4 py-3 text-center">
                    <p class="font-semibold text-gray-800"><?= (int)$notificationStats['unread'] ?></p>
                    <p class="text-xs text-gray-500">Unread</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mb-6 rounded-xl border bg-white p-4">
    <div class="flex flex-col gap-3 text-sm sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
        <span class="font-medium text-gray-600">Filter:</span>
        <?php
        $filterMap = [
            'all' => 'All',
            'unread' => 'Unread',
            'application' => 'Application',
            'system' => 'System',
        ];
        ?>
        <?php foreach ($filterMap as $filterKey => $filterLabel): ?>
            <a href="notifications.php?filter=<?= urlencode($filterKey) ?>" class="rounded-full px-3 py-1 <?= $filter === $filterKey ? 'bg-green-100 text-green-700' : 'border text-gray-600 hover:bg-gray-50' ?>">
                <?= htmlspecialchars($filterLabel, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
        </div>

        <?php if ($notificationStats['unread'] > 0): ?>
            <form method="POST" action="notifications.php" class="w-full sm:w-auto">
                <input type="hidden" name="action" value="mark_all_read">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="w-full rounded-md border px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 sm:w-auto">Mark all as read</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<section class="rounded-xl border bg-white">
    <header class="border-b px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Recent Notifications</h2>
    </header>

    <?php if (empty($notifications)): ?>
        <div class="p-8 text-center">
            <span class="material-symbols-outlined text-4xl text-gray-400">inbox</span>
            <h3 class="mt-3 text-lg font-semibold text-gray-800"><?= $isFilterEmpty ? 'No matching notifications' : 'No notifications yet' ?></h3>
            <p class="mt-1 text-sm text-gray-600">
                <?= $isFilterEmpty ? 'Try switching to a different filter.' : 'You are all caught up. New updates will appear here.' ?>
            </p>
            <?php if ($isFilterEmpty): ?>
                <a href="notifications.php" class="mt-4 inline-flex items-center rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Clear filter</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="space-y-4 p-6">
            <?php foreach ($notifications as $notification): ?>
                <?php
                $categoryMeta = (array)($categoryStyles[$notification['category']] ?? $categoryStyles['default']);
                ?>
                <article class="rounded-xl border p-5 <?= !$notification['is_read'] ? 'border-green-200 bg-green-50' : '' ?>">
                    <div class="flex gap-3">
                        <span class="material-symbols-outlined mt-1 <?= htmlspecialchars((string)$categoryMeta['icon_color'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$categoryMeta['icon'], ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h3 class="font-medium text-gray-800"><?= htmlspecialchars((string)$notification['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <span class="text-xs text-gray-500">
                                    <?= !empty($notification['created_at']) ? htmlspecialchars(date('M j, Y · g:i A', strtotime((string)$notification['created_at'])), ENT_QUOTES, 'UTF-8') : '-' ?>
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-gray-600"><?= htmlspecialchars((string)$notification['body'], ENT_QUOTES, 'UTF-8') ?></p>

                            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs">
                                <?php if (!$notification['is_read']): ?>
                                    <span class="inline-flex items-center gap-1 font-medium text-green-700">
                                        <span class="material-symbols-outlined text-xs">mark_email_unread</span>
                                        Unread
                                    </span>
                                    <form method="POST" action="notifications.php" class="inline-flex">
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="notification_id" value="<?= htmlspecialchars((string)$notification['id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="text-green-700 hover:underline">Mark as read</button>
                                    </form>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-gray-500">
                                        <span class="material-symbols-outlined text-xs">drafts</span>
                                        Read
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($notification['link_url'])): ?>
                                    <a href="<?= htmlspecialchars((string)$notification['link_url'], ENT_QUOTES, 'UTF-8') ?>" class="text-green-700 hover:underline">Open</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="border-t bg-gray-50 px-6 py-3 text-sm text-gray-600">Showing <?= count($notifications) ?> notification(s)</div>
    <?php endif; ?>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
