<?php
$statusPill = static function (bool $isRead): array {
    if ($isRead) {
        return ['Read', 'bg-emerald-100 text-emerald-800'];
    }
    return ['Unread', 'bg-amber-100 text-amber-800'];
};
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <div class="flex flex-wrap items-center gap-3 mt-1">
            <h1 class="text-2xl font-bold">Notifications</h1>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-slate-800 text-slate-200 border border-slate-600">Email Provider: <?= htmlspecialchars($notificationEmailProviderLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <p class="text-sm text-slate-300 mt-2">Monitor admin notifications and manage read state. Future critical emails for this module default to <?= htmlspecialchars($notificationEmailProviderLabel, ENT_QUOTES, 'UTF-8') ?>.</p>
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
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Notifications</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$totalNotifications, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Visible to current admin account</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase tracking-wide text-amber-700">Unread</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$unreadNotifications, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Needs attention</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Read</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$readNotifications, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Already reviewed</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-blue-50">
            <p class="text-xs uppercase tracking-wide text-blue-700">Top Category</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $topCategory)), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)$topCategoryCount, ENT_QUOTES, 'UTF-8') ?> notification<?= $topCategoryCount === 1 ? '' : 's' ?></p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Email Notification Settings</h2>
        <p class="text-sm text-slate-500 mt-1">Provider is set to Brevo. Use this test action to confirm API key and sender configuration.</p>
    </header>
    <form action="notifications.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <input type="hidden" name="form_action" value="send_test_notification_email">
        <div class="md:col-span-2">
            <label class="text-slate-600">Test Recipient Email</label>
            <input type="email" name="recipient_email" value="<?= htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="you@example.com" required>
        </div>
        <div>
            <label class="text-slate-600">Configured Sender</label>
            <input type="text" value="<?= htmlspecialchars($mailFrom !== '' ? $mailFrom : 'Not configured', ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Send Test Email</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Admin Notification Feed</h2>
            <p class="text-sm text-slate-500 mt-1">Use search and status filters to find and resolve notification items quickly.</p>
        </div>
        <form action="notifications.php" method="POST">
            <input type="hidden" name="form_action" value="mark_all_notifications_read">
            <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Mark All as Read</button>
        </form>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Notifications</label>
            <input id="adminNotificationsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by title, message, or category">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="adminNotificationsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="Unread">Unread</option>
                <option value="Read">Read</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="adminNotificationsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Title</th>
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Message</th>
                    <th class="text-left px-4 py-3">Created</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($notifications)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="6">No notifications found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <?php
                        $notificationId = (string)($notification['id'] ?? '');
                        $title = (string)($notification['title'] ?? 'Untitled Notification');
                        $category = (string)($notification['category'] ?? 'general');
                        $body = (string)($notification['body'] ?? '');
                        $linkUrl = cleanText($notification['link_url'] ?? null);
                        $isRead = (bool)($notification['is_read'] ?? false);
                        $createdAt = (string)($notification['created_at'] ?? '');
                        $createdLabel = $createdAt !== '' ? date('M d, Y h:i A', strtotime($createdAt)) : '-';
                        [$statusLabel, $statusClass] = $statusPill($isRead);
                        $searchText = strtolower(trim($title . ' ' . $body . ' ' . $category . ' ' . $statusLabel));
                        ?>
                        <tr data-notif-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-notif-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', strtolower($category))), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($body, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[85px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <?php if (!$isRead): ?>
                                        <form action="notifications.php" method="POST">
                                            <input type="hidden" name="form_action" value="mark_notification_read">
                                            <input type="hidden" name="notification_id" value="<?= htmlspecialchars($notificationId, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="px-2.5 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Mark Read</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($linkUrl): ?>
                                        <a href="<?= htmlspecialchars($linkUrl, ENT_QUOTES, 'UTF-8') ?>" class="px-2.5 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Open</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
