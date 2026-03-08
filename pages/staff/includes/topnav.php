<?php
$breadcrumbs = $breadcrumbs ?? ['Dashboard'];
$staffTopnavRealtimeContext = function_exists('staffBackendContext') ? (array)staffBackendContext() : [];
$staffTopnavSupabaseUrl = (string)($staffTopnavRealtimeContext['supabase_url'] ?? '');
$staffTopnavUserId = (string)($staffTopnavRealtimeContext['staff_user_id'] ?? ($_SESSION['user']['id'] ?? ''));
$staffTopnavAnonKey = trim((string)($_ENV['SUPABASE_ANON_KEY'] ?? $_SERVER['SUPABASE_ANON_KEY'] ?? ''));
$staffTopnavAccessToken = trim((string)($_SESSION['supabase']['access_token'] ?? ''));
?>

<header id="topnav" class="sticky top-0 z-30 border-b bg-white/95 backdrop-blur transition-transform duration-300 ease-in-out">
    <div class="px-6 h-16 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <button id="sidebarToggle" class="text-gray-600 hover:text-gray-900 focus:outline-none mt-1" aria-label="Toggle sidebar">
                <span class="material-icons">menu</span>
            </button>

            <div class="flex items-center gap-3 min-w-0">
                <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-2.5 py-1.5 shadow-sm">
                    <img src="<?= htmlspecialchars(systemAppPath('/assets/images/Bagong_Pilipinas_logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Bagong Pilipinas" class="h-8 w-auto object-contain" loading="lazy">
                    <img src="<?= htmlspecialchars(systemAppPath('/assets/images/DA_logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Department of Agriculture" class="h-8 w-8 object-contain" loading="lazy">
                </div>
                <div class="leading-tight min-w-0">
                    <div class="text-[10px] font-semibold uppercase tracking-[0.24em] text-gray-500">Bagong Pilipinas</div>
                    <div class="font-semibold truncate">Human Resource Information System</div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div
                class="relative"
                data-topnav-notifications
                data-endpoint="notifications.php"
                data-action-field="form_action"
                data-mark-read-action="mark_notification_read"
                data-id-field="notification_id"
                data-csrf-field="csrf_token"
                data-csrf-token="<?= htmlspecialchars((string)($staffTopnavCsrfToken ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                data-snapshot-action="topnav_snapshot"
                data-supabase-url="<?= htmlspecialchars($staffTopnavSupabaseUrl, ENT_QUOTES, 'UTF-8') ?>"
                data-supabase-anon-key="<?= htmlspecialchars($staffTopnavAnonKey, ENT_QUOTES, 'UTF-8') ?>"
                data-realtime-access-token="<?= htmlspecialchars($staffTopnavAccessToken, ENT_QUOTES, 'UTF-8') ?>"
                data-user-id="<?= htmlspecialchars($staffTopnavUserId, ENT_QUOTES, 'UTF-8') ?>"
                data-role-label="Staff"
            >
                <button type="button" data-topnav-notification-trigger class="relative rounded-md p-1 text-gray-600 transition hover:bg-gray-100 hover:text-daGreen" aria-label="Notifications">
                    <span class="material-icons">notifications</span>
                    <?php if (($staffUnreadNotificationCount ?? 0) > 0): ?>
                        <span data-topnav-unread-badge data-unread-count="<?= (int)($staffUnreadNotificationCount ?? 0) ?>" class="absolute -right-1 -top-1 inline-flex min-h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white">
                            <?= htmlspecialchars((string)($staffUnreadNotificationBadge ?? '0'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php else: ?>
                        <span data-topnav-unread-badge data-unread-count="0" class="absolute -right-1 -top-1 hidden min-h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white">0</span>
                    <?php endif; ?>
                </button>

                <div data-topnav-list-modal class="absolute right-0 top-full z-[90] mt-2 hidden w-[min(90vw,42rem)] rounded-2xl border border-slate-200 bg-white shadow-2xl">
                        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                            <div>
                                <p class="text-base font-semibold text-slate-800">Notifications</p>
                                <p class="text-xs text-slate-500"><span data-topnav-unread-text><?= (int)($staffUnreadNotificationCount ?? 0) ?></span> unread</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="notifications.php" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-50">Open All</a>
                                <button type="button" data-topnav-close="list" class="rounded-md border border-slate-200 px-2 py-1 text-slate-600 hover:bg-slate-50" aria-label="Close notifications">
                                    <span class="material-icons text-base">close</span>
                                </button>
                            </div>
                        </div>
                        <div class="max-h-[60vh] overflow-y-auto p-3" data-topnav-items>
                            <?php if (empty($staffTopnavNotificationsPreview)): ?>
                                <div class="rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No notifications available.</div>
                            <?php else: ?>
                                <?php foreach ((array)$staffTopnavNotificationsPreview as $item): ?>
                                    <?php
                                    $itemId = trim((string)($item['id'] ?? ''));
                                    if ($itemId === '') {
                                        continue;
                                    }
                                    $itemTitle = trim((string)($item['title'] ?? 'Notification'));
                                    $itemBody = trim((string)($item['body'] ?? ''));
                                    $itemLink = trim((string)($item['link_url'] ?? ''));
                                    $itemCategory = trim((string)($item['category'] ?? 'general'));
                                    $itemCreatedAtRaw = trim((string)($item['created_at'] ?? ''));
                                    $itemCreatedAtLabel = $itemCreatedAtRaw !== '' ? date('M d, Y h:i A', strtotime($itemCreatedAtRaw)) : '-';
                                    $itemIsRead = (bool)($item['is_read'] ?? false);
                                    ?>
                                    <button
                                        type="button"
                                        data-topnav-item
                                        data-notification-id="<?= htmlspecialchars($itemId, ENT_QUOTES, 'UTF-8') ?>"
                                        data-notification-title="<?= htmlspecialchars($itemTitle, ENT_QUOTES, 'UTF-8') ?>"
                                        data-notification-body="<?= htmlspecialchars($itemBody !== '' ? $itemBody : 'No details available.', ENT_QUOTES, 'UTF-8') ?>"
                                        data-notification-link="<?= htmlspecialchars($itemLink, ENT_QUOTES, 'UTF-8') ?>"
                                        data-notification-category="<?= htmlspecialchars($itemCategory, ENT_QUOTES, 'UTF-8') ?>"
                                        data-notification-created="<?= htmlspecialchars($itemCreatedAtLabel, ENT_QUOTES, 'UTF-8') ?>"
                                        data-notification-read="<?= $itemIsRead ? '1' : '0' ?>"
                                        class="mb-2 block w-full rounded-xl border px-4 py-3 text-left transition hover:bg-slate-50 <?= $itemIsRead ? 'border-slate-200 bg-white' : 'border-amber-200 bg-amber-50/60' ?>"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($itemTitle, ENT_QUOTES, 'UTF-8') ?></p>
                                            <span data-topnav-item-status class="inline-flex rounded-full px-2 py-0.5 text-[11px] <?= $itemIsRead ? 'bg-slate-100 text-slate-600' : 'bg-amber-100 text-amber-700' ?>"><?= $itemIsRead ? 'Read' : 'Unread' ?></span>
                                        </div>
                                        <p class="mt-1 line-clamp-2 text-xs text-slate-600"><?= htmlspecialchars($itemBody !== '' ? $itemBody : 'No details available.', ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="mt-2 text-[11px] text-slate-400"><?= htmlspecialchars($itemCreatedAtLabel, ENT_QUOTES, 'UTF-8') ?></p>
                                    </button>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                </div>

                <div data-topnav-detail-modal class="absolute right-0 top-full z-[95] mt-2 hidden w-[min(90vw,36rem)] rounded-2xl border border-slate-200 bg-white shadow-2xl">
                        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                            <p class="text-base font-semibold text-slate-800">Notification Details</p>
                            <button type="button" data-topnav-close="detail" class="rounded-md border border-slate-200 px-2 py-1 text-slate-600 hover:bg-slate-50" aria-label="Close notification details">
                                <span class="material-icons text-base">close</span>
                            </button>
                        </div>
                        <div class="space-y-3 px-5 py-4 text-sm">
                            <div class="flex items-center gap-2">
                                <span data-topnav-detail-status class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">Read</span>
                                <span data-topnav-detail-created class="text-xs text-slate-500">-</span>
                            </div>
                            <h3 data-topnav-detail-title class="text-base font-semibold text-slate-800">Notification</h3>
                            <p data-topnav-detail-body class="text-slate-600">No details available.</p>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                <p class="font-medium text-slate-700">Related Link</p>
                                <a data-topnav-detail-link href="#" class="mt-1 inline-flex items-center gap-1 text-emerald-700 hover:underline" target="_self" rel="noopener">Open related record</a>
                                <p data-topnav-detail-link-empty class="mt-1 text-slate-500">No related link available.</p>
                            </div>
                        </div>
                </div>
            </div>

            <span class="hidden sm:block h-6 w-px bg-gray-200"></span>

            <div class="relative" id="profileDropdown">
                <button id="profileToggle" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-gray-700 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none">
                    <?php if (!empty($staffTopnavPhotoUrl)): ?>
                        <img src="<?= htmlspecialchars((string)$staffTopnavPhotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Profile" class="h-8 w-8 rounded-full object-cover border">
                    <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-daGreen text-white flex items-center justify-center text-xs font-semibold">
                            <?= htmlspecialchars((string)($staffTopnavInitials ?? 'ST'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <div class="leading-tight hidden md:block text-left">
                        <p class="text-sm font-medium"><?= htmlspecialchars((string)($staffTopnavDisplayName ?? 'Staff User'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars((string)($staffTopnavRoleLabel ?? 'Staff'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <span class="material-icons text-gray-500 text-sm">expand_more</span>
                </button>

                <div id="profileMenu" class="absolute right-0 z-50 mt-2 hidden w-72 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                    <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                        <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars((string)($staffTopnavDisplayName ?? 'Staff User'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs text-slate-500"><?= htmlspecialchars((string)($staffTopnavRoleLabel ?? 'Staff'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <div class="p-2">
                        <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Account</p>
                        <div class="space-y-1">
                            <a href="profile.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-100">
                                <span class="material-icons text-[18px] text-slate-500">person</span>
                                <span>My Profile</span>
                            </a>
                            <a href="profile.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-100">
                                <span class="material-icons text-[18px] text-slate-500">password</span>
                                <span>Change Password</span>
                            </a>
                        </div>
                    </div>

                    <div class="mx-2 border-t border-slate-100"></div>

                    <div class="p-2">
                        <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tools</p>
                        <div class="space-y-1">
                            <a href="reports.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-100">
                                <span class="material-icons text-[18px] text-slate-500">assessment</span>
                                <span>Reports and Analytics</span>
                            </a>
                            <a href="support.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-100">
                                <span class="material-icons text-[18px] text-slate-500">support_agent</span>
                                <span>Support</span>
                            </a>
                        </div>
                    </div>

                    <div class="mx-2 border-t border-slate-100"></div>

                    <div class="p-2">
                        <a href="<?= htmlspecialchars(systemAppPath('/pages/auth/logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50">
                            <span class="material-icons text-[18px]">logout</span>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script src="<?= htmlspecialchars(systemAppPath('/assets/js/shared/topnav-notifications.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
