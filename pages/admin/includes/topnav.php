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
$topnavDisplayName = 'Admin User';
$topnavDisplayRole = 'Administrator';
$topnavDisplayInitials = 'AD';
if ($topnavSupabaseUrl !== '' && !empty($topnavHeaders) && $topnavAdminUserId !== '' && function_exists('apiRequest') && function_exists('isSuccessful')) {
    $topnavProfileResponse = apiRequest(
        'GET',
        rtrim($topnavSupabaseUrl, '/') . '/rest/v1/user_accounts?select=id,email,people(first_name,surname)&id=eq.' . $topnavAdminUserId . '&limit=1',
        $topnavHeaders
    );

    if (isSuccessful($topnavProfileResponse)) {
        $profileRow = (array)($topnavProfileResponse['data'][0] ?? []);
        $peopleRow = (array)($profileRow['people'][0] ?? $profileRow['people'] ?? []);
        $firstName = trim((string)($peopleRow['first_name'] ?? ''));
        $surname = trim((string)($peopleRow['surname'] ?? ''));
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
        rtrim($topnavSupabaseUrl, '/') . '/rest/v1/user_role_assignments?select=role:roles(role_name,role_key)&user_id=eq.' . $topnavAdminUserId . '&is_primary=eq.true&expires_at=is.null&limit=1',
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
        rtrim($topnavSupabaseUrl, '/') . '/rest/v1/notifications?select=id&recipient_user_id=eq.' . $topnavAdminUserId . '&is_read=eq.false&limit=5000',
        $topnavHeaders
    );

    if (isSuccessful($topnavUnreadResponse)) {
        $topnavUnreadCount = count((array)($topnavUnreadResponse['data'] ?? []));
    }

    $topnavUnreadPreviewResponse = apiRequest(
        'GET',
        rtrim($topnavSupabaseUrl, '/') . '/rest/v1/notifications?select=id,title,body,created_at,link_url&recipient_user_id=eq.' . $topnavAdminUserId . '&is_read=eq.false&order=created_at.desc&limit=5',
        $topnavHeaders
    );

    if (isSuccessful($topnavUnreadPreviewResponse)) {
        $topnavUnreadNotifications = (array)($topnavUnreadPreviewResponse['data'] ?? []);
    }
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

        <div class="relative" id="notificationDropdown">
            <button id="notificationToggle" type="button" class="admin-top-chip w-9 h-9 inline-flex items-center justify-center text-slate-300 hover:text-emerald-300" aria-label="Open notifications">
                <span class="material-symbols-outlined">notifications</span>
                <?php if ($topnavUnreadCount > 0): ?>
                    <span class="absolute -top-1 -right-1 min-w-4 h-4 bg-emerald-500 text-slate-900 text-[10px] rounded-full px-1 font-semibold leading-4 text-center"><?= htmlspecialchars((string)min($topnavUnreadCount, 99), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </button>

            <div id="notificationMenu" class="absolute right-0 mt-2 w-[340px] hidden z-50 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/70 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Unread Notifications</p>
                        <p class="text-xs text-slate-500"><?= htmlspecialchars((string)$topnavUnreadCount, ENT_QUOTES, 'UTF-8') ?> unread</p>
                    </div>
                    <a href="notifications.php" class="text-xs font-medium text-emerald-700 hover:underline">Open All</a>
                </div>

                <div class="max-h-[340px] overflow-y-auto">
                    <?php if (empty($topnavUnreadNotifications)): ?>
                        <div class="px-4 py-6 text-sm text-slate-500 text-center">No unread notifications.</div>
                    <?php else: ?>
                        <?php foreach ($topnavUnreadNotifications as $item): ?>
                            <?php
                            $itemTitle = (string)($item['title'] ?? 'Notification');
                            $itemBody = (string)($item['body'] ?? '');
                            $itemLink = trim((string)($item['link_url'] ?? ''));
                            $itemCreatedAtRaw = (string)($item['created_at'] ?? '');
                            $itemCreatedAt = $itemCreatedAtRaw !== '' ? date('M d, Y h:i A', strtotime($itemCreatedAtRaw)) : '-';
                            ?>
                            <a href="<?= htmlspecialchars($itemLink !== '' ? $itemLink : 'notifications.php', ENT_QUOTES, 'UTF-8') ?>" class="block px-4 py-3 border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($itemTitle, ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-slate-600 mt-1 line-clamp-2"><?= htmlspecialchars($itemBody !== '' ? $itemBody : 'No description available.', ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-[11px] text-slate-400 mt-1"><?= htmlspecialchars($itemCreatedAt, ENT_QUOTES, 'UTF-8') ?></p>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="relative" id="profileDropdown">
            <button id="profileToggle" class="admin-top-chip flex items-center gap-2 px-2 py-1.5 focus:outline-none">
                <div class="w-8 h-8 rounded-lg bg-emerald-500 text-slate-900 flex items-center justify-center text-xs font-semibold">
                    <?= htmlspecialchars($topnavDisplayInitials, ENT_QUOTES, 'UTF-8') ?>
                </div>

                <div class="leading-tight hidden md:block text-left">
                    <p class="text-sm font-medium text-white"><?= htmlspecialchars($topnavDisplayName, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-slate-300"><?= htmlspecialchars($topnavDisplayRole, ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <span class="material-symbols-outlined text-slate-300 text-sm">expand_more</span>
            </button>

            <div id="profileMenu" class="absolute right-0 mt-2 w-64 hidden z-50 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/70">
                    <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($topnavDisplayName, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-slate-500"><?= htmlspecialchars($topnavDisplayRole, ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="p-2 space-y-1">
                <a href="profile.php" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg hover:bg-slate-100 text-slate-700 whitespace-nowrap">
                    <span class="material-symbols-outlined text-[18px] text-slate-500">person</span>
                    <span>My Profile</span>
                </a>

                <a href="settings.php" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg hover:bg-slate-100 text-slate-700 whitespace-nowrap">
                    <span class="material-symbols-outlined text-[18px] text-slate-500">settings</span>
                    <span>Settings</span>
                </a>

                <a href="create-announcement.php" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg hover:bg-slate-100 text-slate-700 whitespace-nowrap">
                    <span class="material-symbols-outlined text-[18px] text-slate-500">campaign</span>
                    <span>Create Announcement</span>
                </a>
                </div>

                <div class="border-t border-slate-100"></div>

                <div class="p-2">
                <a href="/hris-system/pages/auth/logout.php" class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg text-rose-600 hover:bg-rose-50 whitespace-nowrap">
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                    <span>Logout</span>
                </a>
                </div>
            </div>
        </div>
    </div>
</header>
