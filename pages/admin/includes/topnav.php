<?php
$breadcrumbs = $breadcrumbs ?? ['Dashboard'];

$topnavSupabaseUrl = isset($supabaseUrl) ? (string)$supabaseUrl : '';
$topnavHeaders = isset($headers) && is_array($headers) ? $headers : [];
$topnavAdminUserId = isset($adminUserId) ? (string)$adminUserId : '';

if (($topnavSupabaseUrl === '' || empty($topnavHeaders) || $topnavAdminUserId === '') && function_exists('adminBackendContext')) {
    $topnavBackend = adminBackendContext();
    $topnavSupabaseUrl = (string)($topnavBackend['supabase_url'] ?? $topnavSupabaseUrl);
    $topnavHeaders = (array)($topnavBackend['headers'] ?? $topnavHeaders);
    $topnavAdminUserId = (string)($topnavBackend['admin_user_id'] ?? $topnavAdminUserId);
}

$topnavUnreadCount = 0;
$topnavUnreadNotifications = [];
$adminTopnavCsrfToken = function_exists('ensureCsrfToken') ? ensureCsrfToken() : '';
$adminTopnavAnonKey = trim((string)($_ENV['SUPABASE_ANON_KEY'] ?? $_SERVER['SUPABASE_ANON_KEY'] ?? ''));
$adminTopnavAccessToken = trim((string)($_SESSION['supabase']['access_token'] ?? ''));
$topnavDisplayName = 'Admin User';
$topnavDisplayRole = 'Administrator';
$topnavDisplayInitials = 'AD';
$topnavProfilePhotoUrl = null;

$adminTopnavCacheTtlSeconds = 45;
$adminTopnavCache = (array)($_SESSION['admin_topnav_cache'] ?? []);
$adminTopnavCacheUserId = (string)($adminTopnavCache['user_id'] ?? '');
$adminTopnavCacheTimestamp = (int)($adminTopnavCache['cached_at'] ?? 0);
$adminTopnavCacheIsFresh = $adminTopnavCacheUserId !== ''
    && $adminTopnavCacheUserId === $topnavAdminUserId
    && $adminTopnavCacheTimestamp > 0
    && (time() - $adminTopnavCacheTimestamp) <= $adminTopnavCacheTtlSeconds;

$resolveProfilePhotoUrl = static function (?string $rawPath): ?string {
    $path = trim((string)$rawPath);
    if ($path === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    if (str_starts_with($path, '/')) {
        return $path;
    }

    $normalized = str_replace('\\', '/', ltrim($path, '/'));
    if (str_starts_with($normalized, 'storage/document/')) {
        $normalized = substr($normalized, strlen('storage/document/'));
    }

    $segments = array_values(array_filter(explode('/', $normalized), static fn(string $segment): bool => $segment !== ''));
    if (empty($segments)) {
        return null;
    }

    $encoded = implode('/', array_map('rawurlencode', $segments));
    return systemAppPath('/storage/document/' . $encoded);
};

if ($adminTopnavCacheIsFresh) {
    $cachedDisplayName = trim((string)($adminTopnavCache['display_name'] ?? ''));
    if ($cachedDisplayName !== '') {
        $topnavDisplayName = $cachedDisplayName;
    }

    $cachedDisplayRole = trim((string)($adminTopnavCache['display_role'] ?? ''));
    if ($cachedDisplayRole !== '') {
        $topnavDisplayRole = $cachedDisplayRole;
    }

    $topnavUnreadCount = max(0, (int)($adminTopnavCache['unread_count'] ?? 0));
    $topnavUnreadNotifications = (array)($adminTopnavCache['unread_notifications'] ?? []);
    $topnavProfilePhotoUrl = $resolveProfilePhotoUrl((string)($adminTopnavCache['profile_photo_url'] ?? ''));
}

if (!$adminTopnavCacheIsFresh && $topnavSupabaseUrl !== '' && !empty($topnavHeaders) && $topnavAdminUserId !== '' && function_exists('apiRequest') && function_exists('isSuccessful')) {
    $topnavProfileResponse = apiRequest(
        'GET',
        rtrim($topnavSupabaseUrl, '/') . '/rest/v1/user_accounts?select=id,email,people(first_name,surname,profile_photo_url)&id=eq.' . rawurlencode($topnavAdminUserId) . '&limit=1',
        $topnavHeaders
    );

    if (isSuccessful($topnavProfileResponse)) {
        $profileRow = (array)($topnavProfileResponse['data'][0] ?? []);
        $peopleRow = (array)($profileRow['people'][0] ?? $profileRow['people'] ?? []);
        $firstName = trim((string)($peopleRow['first_name'] ?? ''));
        $surname = trim((string)($peopleRow['surname'] ?? ''));
        $topnavProfilePhotoUrl = $resolveProfilePhotoUrl((string)($peopleRow['profile_photo_url'] ?? ''));
        $email = trim((string)($profileRow['email'] ?? ''));

        $candidateName = trim($firstName . ' ' . $surname);
        if ($candidateName !== '') {
            $topnavDisplayName = $candidateName;
        } elseif ($email !== '') {
            $topnavDisplayName = $email;
        }
    }

    $topnavRoleResponse = apiRequest(
        'GET',
        rtrim($topnavSupabaseUrl, '/') . '/rest/v1/user_role_assignments?select=role:roles(role_name,role_key)&user_id=eq.' . rawurlencode($topnavAdminUserId) . '&is_primary=eq.true&expires_at=is.null&limit=1',
        $topnavHeaders
    );

    if (isSuccessful($topnavRoleResponse)) {
        $roleRow = (array)($topnavRoleResponse['data'][0]['role'] ?? []);
        $roleName = trim((string)($roleRow['role_name'] ?? ''));
        if ($roleName !== '') {
            $topnavDisplayRole = $roleName;
        }
    }

    $topnavUnreadResponse = apiRequest(
        'GET',
        rtrim($topnavSupabaseUrl, '/') . '/rest/v1/notifications?select=id&recipient_user_id=eq.' . rawurlencode($topnavAdminUserId) . '&is_read=eq.false&category=neq.announcement&limit=5000',
        $topnavHeaders
    );

    if (isSuccessful($topnavUnreadResponse)) {
        $topnavUnreadCount = count((array)($topnavUnreadResponse['data'] ?? []));
    }

    $topnavUnreadPreviewResponse = apiRequest(
        'GET',
        rtrim($topnavSupabaseUrl, '/') . '/rest/v1/notifications?select=id,title,body,created_at,link_url&recipient_user_id=eq.' . rawurlencode($topnavAdminUserId) . '&is_read=eq.false&category=neq.announcement&order=created_at.desc&limit=5',
        $topnavHeaders
    );

    if (isSuccessful($topnavUnreadPreviewResponse)) {
        $topnavUnreadNotifications = (array)($topnavUnreadPreviewResponse['data'] ?? []);
    }

    $_SESSION['admin_topnav_cache'] = [
        'user_id' => $topnavAdminUserId,
        'display_name' => $topnavDisplayName,
        'display_role' => $topnavDisplayRole,
        'profile_photo_url' => (string)($topnavProfilePhotoUrl ?? ''),
        'unread_count' => $topnavUnreadCount,
        'unread_notifications' => $topnavUnreadNotifications,
        'cached_at' => time(),
    ];
}

if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    $sessionName = trim((string)($_SESSION['user']['full_name'] ?? $_SESSION['user']['name'] ?? ''));
    if ($sessionName !== '' && $topnavDisplayName === 'Admin User') {
        $topnavDisplayName = $sessionName;
    }

    $sessionRole = trim((string)($_SESSION['user']['role_name'] ?? $_SESSION['user']['role_key'] ?? $_SESSION['user']['role'] ?? ''));
    if ($sessionRole !== '' && $topnavDisplayRole === 'Administrator') {
        $topnavDisplayRole = ucwords(str_replace('_', ' ', strtolower($sessionRole)));
    }
}

$topnavInitialSource = preg_replace('/\s+/', ' ', trim((string)$topnavDisplayName));
if ($topnavInitialSource !== '') {
    $parts = array_values(array_filter(explode(' ', $topnavInitialSource), static fn(string $part): bool => $part !== ''));
    if (!empty($parts)) {
        $first = strtoupper(substr($parts[0], 0, 1));
        $second = count($parts) > 1 ? strtoupper(substr($parts[count($parts) - 1], 0, 1)) : strtoupper(substr($parts[0], 1, 1));
        $topnavDisplayInitials = trim($first . ($second !== '' ? $second : ''));
        if ($topnavDisplayInitials === '') {
            $topnavDisplayInitials = 'AD';
        }
    }
}
?>

<header id="topnav" class="h-16 admin-topbar sticky top-0 z-20 flex items-center justify-between px-5 transition-transform duration-300 ease-in-out bg-slate-900">
    <div class="flex items-center gap-3">
        <button id="sidebarToggle" class="admin-top-chip w-8 h-8 inline-flex items-center justify-center text-slate-300 hover:text-white focus:outline-none" aria-label="Toggle sidebar">
            <span class="material-symbols-outlined">menu</span>
        </button>

        <div class="hidden md:flex items-center gap-3 rounded-xl bg-white/5 px-3 py-1.5">
            <div class="flex items-center gap-2">
                <img src="<?= htmlspecialchars(systemAppPath('/assets/images/DA_logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Department of Agriculture" class="h-8 w-8 object-contain" loading="lazy">
                <img src="<?= htmlspecialchars(systemAppPath('/assets/images/Bagong_Pilipinas_logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Bagong Pilipinas" class="h-8 w-auto object-contain" loading="lazy">
            </div>
            <div class="leading-tight">
                <span class="block text-[10px] uppercase tracking-[0.24em] text-slate-300">Bagong Pilipinas</span>
                <span class="block text-xs font-semibold text-slate-100">DA-ATI HRIS</span>
            </div>
        </div>

        <nav aria-label="Breadcrumb" class="leading-tight">
            <ol class="flex items-center gap-1.5 text-sm text-slate-400">
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <li class="flex items-center gap-1.5">
                        <?php if ($index > 0): ?>
                            <span class="material-symbols-outlined text-[13px] text-slate-500">chevron_right</span>
                        <?php endif; ?>
                        <span class="<?= $index === count($breadcrumbs) - 1 ? 'text-slate-100 font-semibold' : '' ?>">
                            <?= htmlspecialchars($crumb, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>
    </div>

    <div class="flex items-center gap-3">
        <div class="relative hidden lg:block" id="topnavSearchWrapper">
            <span class="material-symbols-outlined absolute left-3 top-2 text-slate-400 text-sm">search</span>
            <input id="topnavGlobalSearch" type="text" placeholder="Search records, accounts, documents..." class="admin-top-search pl-9 pr-3 py-1.5 w-72 text-sm placeholder:text-slate-500 focus:outline-none" autocomplete="off">
            <div id="topnavSearchResults" class="absolute right-0 mt-2 w-[420px] hidden z-50 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/70">
                    <p class="text-sm font-semibold text-slate-800">Search Results</p>
                    <p id="topnavSearchMeta" class="text-xs text-slate-500">Type to search</p>
                </div>
                <div id="topnavSearchResultsBody" class="max-h-[360px] overflow-y-auto">
                    <div class="px-4 py-6 text-sm text-slate-500 text-center">Start typing to find records in the current module.</div>
                </div>
            </div>
        </div>

        <div
            class="relative"
            data-topnav-notifications
            data-endpoint="notifications.php"
            data-action-field="form_action"
            data-mark-read-action="mark_notification_read"
            data-id-field="notification_id"
            data-csrf-field="csrf_token"
            data-csrf-token="<?= htmlspecialchars((string)$adminTopnavCsrfToken, ENT_QUOTES, 'UTF-8') ?>"
            data-snapshot-action="topnav_snapshot"
            data-supabase-url="<?= htmlspecialchars((string)$topnavSupabaseUrl, ENT_QUOTES, 'UTF-8') ?>"
            data-supabase-anon-key="<?= htmlspecialchars((string)$adminTopnavAnonKey, ENT_QUOTES, 'UTF-8') ?>"
            data-realtime-access-token="<?= htmlspecialchars((string)$adminTopnavAccessToken, ENT_QUOTES, 'UTF-8') ?>"
            data-user-id="<?= htmlspecialchars((string)$topnavAdminUserId, ENT_QUOTES, 'UTF-8') ?>"
            data-role-label="Administrator"
        >
            <button type="button" data-topnav-notification-trigger class="admin-top-chip relative w-9 h-9 inline-flex items-center justify-center text-slate-300 hover:text-emerald-300" aria-label="Open notifications">
                <span class="material-symbols-outlined">notifications</span>
                <?php if ($topnavUnreadCount > 0): ?>
                    <span data-topnav-unread-badge data-unread-count="<?= (int)$topnavUnreadCount ?>" class="absolute -top-1 -right-1 min-w-4 h-4 bg-emerald-500 text-slate-900 text-[10px] rounded-full px-1 font-semibold leading-4 text-center"><?= htmlspecialchars((string)min($topnavUnreadCount, 99), ENT_QUOTES, 'UTF-8') ?></span>
                <?php else: ?>
                    <span data-topnav-unread-badge data-unread-count="0" class="absolute -top-1 -right-1 hidden min-w-4 h-4 bg-emerald-500 text-slate-900 text-[10px] rounded-full px-1 font-semibold leading-4 text-center">0</span>
                <?php endif; ?>
            </button>

            <div data-topnav-list-modal class="absolute right-0 top-full z-[90] mt-2 hidden w-[min(90vw,42rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-5 py-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Unread Notifications</p>
                            <p class="text-xs text-slate-500"><span data-topnav-unread-text><?= (int)$topnavUnreadCount ?></span> unread</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="notifications.php" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-white">Open All</a>
                            <button type="button" data-topnav-close="list" class="rounded-md border border-slate-200 px-2 py-1 text-slate-600 hover:bg-white" aria-label="Close notifications">
                                <span class="material-symbols-outlined text-base">close</span>
                            </button>
                        </div>
                    </div>
                    <div class="max-h-[60vh] overflow-y-auto p-3" data-topnav-items>
                        <?php if (empty($topnavUnreadNotifications)): ?>
                            <div class="rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No unread notifications.</div>
                        <?php else: ?>
                            <?php foreach ($topnavUnreadNotifications as $item): ?>
                                <?php
                                $itemId = trim((string)($item['id'] ?? ''));
                                if ($itemId === '') {
                                    continue;
                                }
                                $itemTitle = (string)($item['title'] ?? 'Notification');
                                $itemBody = (string)($item['body'] ?? '');
                                $itemLink = trim((string)($item['link_url'] ?? ''));
                                $itemCreatedAtRaw = (string)($item['created_at'] ?? '');
                                $itemCreatedAt = $itemCreatedAtRaw !== '' ? date('M d, Y h:i A', strtotime($itemCreatedAtRaw)) : '-';
                                ?>
                                <button
                                    type="button"
                                    data-topnav-item
                                    data-notification-id="<?= htmlspecialchars($itemId, ENT_QUOTES, 'UTF-8') ?>"
                                    data-notification-title="<?= htmlspecialchars($itemTitle, ENT_QUOTES, 'UTF-8') ?>"
                                    data-notification-body="<?= htmlspecialchars($itemBody !== '' ? $itemBody : 'No description available.', ENT_QUOTES, 'UTF-8') ?>"
                                    data-notification-link="<?= htmlspecialchars($itemLink, ENT_QUOTES, 'UTF-8') ?>"
                                    data-notification-category="general"
                                    data-notification-created="<?= htmlspecialchars($itemCreatedAt, ENT_QUOTES, 'UTF-8') ?>"
                                    data-notification-read="0"
                                    class="mb-2 block w-full rounded-xl border border-amber-200 bg-amber-50/60 px-4 py-3 text-left transition hover:bg-slate-50"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($itemTitle, ENT_QUOTES, 'UTF-8') ?></p>
                                        <span data-topnav-item-status class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] text-amber-700">Unread</span>
                                    </div>
                                    <p class="mt-1 line-clamp-2 text-xs text-slate-600"><?= htmlspecialchars($itemBody !== '' ? $itemBody : 'No description available.', ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="mt-2 text-[11px] text-slate-400"><?= htmlspecialchars($itemCreatedAt, ENT_QUOTES, 'UTF-8') ?></p>
                                </button>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
            </div>

            <div data-topnav-detail-modal class="absolute right-0 top-full z-[95] mt-2 hidden w-[min(90vw,36rem)] rounded-2xl border border-slate-200 bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <p class="text-base font-semibold text-slate-800">Notification Details</p>
                        <button type="button" data-topnav-close="detail" class="rounded-md border border-slate-200 px-2 py-1 text-slate-600 hover:bg-slate-50" aria-label="Close notification details">
                            <span class="material-symbols-outlined text-base">close</span>
                        </button>
                    </div>
                    <div class="space-y-3 px-5 py-4 text-sm">
                        <div class="flex items-center gap-2">
                            <span data-topnav-detail-status class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">Unread</span>
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

        <div class="relative" id="profileDropdown">
            <button id="profileToggle" class="admin-top-chip flex items-center gap-2 px-2 py-1.5 focus:outline-none">
                <?php if (!empty($topnavProfilePhotoUrl)): ?>
                    <img src="<?= htmlspecialchars((string)$topnavProfilePhotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Admin profile" class="h-8 w-8 object-cover">
                <?php else: ?>
                    <div class="w-8 h-8 rounded-lg bg-emerald-500 text-slate-900 flex items-center justify-center text-xs font-semibold">
                        <?= htmlspecialchars($topnavDisplayInitials, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <div class="leading-tight hidden md:block text-left">
                    <p class="text-sm font-medium text-white"><?= htmlspecialchars($topnavDisplayName, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-slate-300"><?= htmlspecialchars($topnavDisplayRole, ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <span class="material-symbols-outlined text-slate-300 text-sm">expand_more</span>
            </button>

            <div id="profileMenu" class="absolute right-0 z-50 mt-2 hidden w-72 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($topnavDisplayName, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-slate-500"><?= htmlspecialchars($topnavDisplayRole, ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="p-2">
                    <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Account</p>
                    <div class="space-y-1">
                        <a href="profile.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-100 whitespace-nowrap">
                            <span class="material-symbols-outlined text-[18px] text-slate-500">person</span>
                            <span>My Profile</span>
                        </a>
                        <a href="profile.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-100 whitespace-nowrap">
                            <span class="material-symbols-outlined text-[18px] text-slate-500">password</span>
                            <span>Change Password</span>
                        </a>
                    </div>
                </div>

                <div class="mx-2 border-t border-slate-100"></div>

                <div class="p-2">
                    <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tools</p>
                    <div class="space-y-1">
                        <a href="settings.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-100 whitespace-nowrap">
                            <span class="material-symbols-outlined text-[18px] text-slate-500">settings</span>
                            <span>Settings</span>
                        </a>
                        <a href="support.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-100 whitespace-nowrap">
                            <span class="material-symbols-outlined text-[18px] text-slate-500">support_agent</span>
                            <span>Support</span>
                        </a>
                        <a href="create-announcement.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-100 whitespace-nowrap">
                            <span class="material-symbols-outlined text-[18px] text-slate-500">campaign</span>
                            <span>Create Announcement</span>
                        </a>
                    </div>
                </div>

                <div class="mx-2 border-t border-slate-100"></div>

                <div class="p-2">
                    <a href="<?= htmlspecialchars(systemAppPath('/pages/auth/logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50 whitespace-nowrap">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script src="<?= htmlspecialchars(systemAppPath('/assets/js/shared/topnav-notifications.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
