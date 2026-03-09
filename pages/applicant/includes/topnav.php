<?php
// Applicant Top Navigation
$breadcrumbs = $breadcrumbs ?? ['Dashboard'];
$activePage = $activePage ?? basename($_SERVER['PHP_SELF']);

require_once __DIR__ . '/lib/applicant-backend.php';

$applicantFirstName = 'Applicant';
$applicantDisplayName = 'Applicant User';
$applicantTopnavPhotoUrl = null;
$applicantTopnavInitials = 'AP';
$fullNameFromSession = trim((string)($_SESSION['user']['name'] ?? ''));

$unreadNotificationCount = 0;
$topnavNotificationsPreview = [];
$applicantTopnavCsrfToken = function_exists('ensureCsrfToken') ? ensureCsrfToken() : '';
$topnavBackend = applicantBackendContext();
$topnavSupabaseUrl = (string)($topnavBackend['supabase_url'] ?? '');
$topnavApplicantUserId = (string)($topnavBackend['applicant_user_id'] ?? '');
$topnavHeaders = (array)($topnavBackend['headers'] ?? []);

$cacheTtlSeconds = 45;
$topnavCache = (array)($_SESSION['applicant_topnav_cache'] ?? []);
$profilePhotoCachePath = trim((string)($topnavCache['profile_photo_url'] ?? ''));
$cacheUserId = (string)($topnavCache['user_id'] ?? '');
$cacheTimestamp = (int)($topnavCache['cached_at'] ?? 0);
$cacheIsFresh = $cacheUserId !== ''
    && $cacheUserId === $topnavApplicantUserId
    && $cacheTimestamp > 0
    && (time() - $cacheTimestamp) <= $cacheTtlSeconds;

if ($cacheIsFresh) {
    $cachedFirstName = trim((string)($topnavCache['first_name'] ?? ''));
    if ($cachedFirstName !== '') {
        $applicantFirstName = $cachedFirstName;
    }
    $applicantDisplayName = trim((string)($topnavCache['display_name'] ?? '')) !== ''
        ? (string)$topnavCache['display_name']
        : $applicantDisplayName;
    $cachedPhotoPath = $profilePhotoCachePath;
    if ($cachedPhotoPath !== '') {
        $applicantTopnavPhotoUrl = preg_match('#^https?://#i', $cachedPhotoPath) === 1 || str_starts_with($cachedPhotoPath, '/')
            ? $cachedPhotoPath
            : systemAppPath('/storage/document/' . ltrim($cachedPhotoPath, '/'));
    }
    $unreadNotificationCount = max(0, (int)($topnavCache['unread_count'] ?? 0));
    $topnavNotificationsPreview = (array)($topnavCache['notifications_preview'] ?? []);
}

if (!$cacheIsFresh && $topnavSupabaseUrl !== '' && $topnavApplicantUserId !== '' && !empty($topnavHeaders) && function_exists('apiRequest')) {
    $peopleNameResponse = apiRequest(
        'GET',
        $topnavSupabaseUrl
        . '/rest/v1/people?select=first_name,surname,profile_photo_url&user_id=eq.' . rawurlencode($topnavApplicantUserId)
        . '&limit=1',
        $topnavHeaders
    );

    if (isSuccessful($peopleNameResponse)) {
        $peopleRow = (array)($peopleNameResponse['data'][0] ?? []);
        $peopleSurname = trim((string)($peopleRow['surname'] ?? ''));
        $peopleFirstName = trim((string)($peopleNameResponse['data'][0]['first_name'] ?? ''));
        if ($peopleFirstName !== '') {
            $applicantFirstName = $peopleFirstName;
            $applicantDisplayName = trim($peopleFirstName . ' ' . $peopleSurname);
            if ($applicantDisplayName === '') {
                $applicantDisplayName = $peopleFirstName;
            }
        }

        $profilePhotoPath = trim((string)($peopleRow['profile_photo_url'] ?? ''));
        if ($profilePhotoPath !== '') {
            $profilePhotoCachePath = $profilePhotoPath;
            $applicantTopnavPhotoUrl = preg_match('#^https?://#i', $profilePhotoPath) === 1 || str_starts_with($profilePhotoPath, '/')
                ? $profilePhotoPath
                : systemAppPath('/storage/document/' . ltrim($profilePhotoPath, '/'));
        }
    }

    if ($applicantFirstName === 'Applicant') {
        $profileNameResponse = apiRequest(
            'GET',
            $topnavSupabaseUrl
            . '/rest/v1/applicant_profiles?select=full_name&user_id=eq.' . rawurlencode($topnavApplicantUserId)
            . '&limit=1',
            $topnavHeaders
        );

        if (isSuccessful($profileNameResponse)) {
            $profileFullName = trim((string)($profileNameResponse['data'][0]['full_name'] ?? ''));
            if ($profileFullName !== '') {
                $nameParts = preg_split('/\s+/', $profileFullName) ?: [];
                $applicantFirstName = (string)($nameParts[0] ?? 'Applicant');
                $applicantDisplayName = $profileFullName;
            }
        }
    }

    if ($applicantFirstName === 'Applicant' && $fullNameFromSession !== '') {
        $nameParts = preg_split('/\s+/', $fullNameFromSession) ?: [];
        $applicantFirstName = (string)($nameParts[0] ?? 'Applicant');
        $applicantDisplayName = $fullNameFromSession;
    }

    $unreadResponse = apiRequest(
        'GET',
        $topnavSupabaseUrl
        . '/rest/v1/notifications?select=id&recipient_user_id=eq.' . rawurlencode($topnavApplicantUserId)
        . '&is_read=eq.false&limit=100',
        $topnavHeaders
    );

    if (isSuccessful($unreadResponse)) {
        $unreadNotificationCount = count((array)($unreadResponse['data'] ?? []));
    }

    $previewResponse = apiRequest(
        'GET',
        $topnavSupabaseUrl
        . '/rest/v1/notifications?select=id,title,body,link_url,is_read,created_at,category'
        . '&recipient_user_id=eq.' . rawurlencode($topnavApplicantUserId)
        . '&order=created_at.desc&limit=8',
        $topnavHeaders
    );

    if (isSuccessful($previewResponse)) {
        $topnavNotificationsPreview = array_values((array)($previewResponse['data'] ?? []));
    }

    $_SESSION['applicant_topnav_cache'] = [
        'user_id' => $topnavApplicantUserId,
        'first_name' => $applicantFirstName,
        'display_name' => $applicantDisplayName,
        'profile_photo_url' => $profilePhotoCachePath,
        'unread_count' => $unreadNotificationCount,
        'notifications_preview' => $topnavNotificationsPreview,
        'cached_at' => time(),
    ];
}

if ($applicantFirstName === 'Applicant' && $fullNameFromSession !== '') {
    $nameParts = preg_split('/\s+/', $fullNameFromSession) ?: [];
    $applicantFirstName = (string)($nameParts[0] ?? 'Applicant');
}

if (trim($applicantDisplayName) === '' && $fullNameFromSession !== '') {
    $applicantDisplayName = $fullNameFromSession;
}

if (trim($applicantDisplayName) === '') {
    $applicantDisplayName = $applicantFirstName;
}

$namePartsForInitials = preg_split('/\s+/', trim($applicantDisplayName)) ?: [];
$initials = '';
foreach (array_slice($namePartsForInitials, 0, 2) as $part) {
    if ($part !== '') {
        $initials .= strtoupper(substr($part, 0, 1));
    }
}
if ($initials !== '') {
    $applicantTopnavInitials = $initials;
}

$unreadNotificationBadge = $unreadNotificationCount > 99 ? '99+' : (string)$unreadNotificationCount;

$primaryLinks = [
    ['href' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'dashboard'],
    ['href' => 'job-list.php', 'label' => 'Jobs', 'icon' => 'list_alt'],
];

$recruitmentLinks = [
    ['href' => 'applications.php', 'label' => 'Applications', 'icon' => 'folder_shared'],
    ['href' => 'application-feedback.php', 'label' => 'Application Feedback', 'icon' => 'fact_check'],
];

$accountLinks = [
    ['href' => 'profile.php', 'label' => 'Profile', 'icon' => 'person'],
    ['href' => 'support.php', 'label' => 'Support', 'icon' => 'help'],
];

$allMobileLinks = array_merge($primaryLinks, $recruitmentLinks, $accountLinks);
?>

<header id="topnav" class="sticky top-0 z-40 border-b bg-white/95 backdrop-blur transition-transform duration-200">

    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-[68px] items-center justify-between gap-4">

            <!-- LEFT: BRAND + NAV -->
            <div class="flex min-w-0 items-center gap-3 lg:gap-5">
                <a href="dashboard.php" class="flex items-center gap-2.5 shrink-0">
                    <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-2.5 py-1.5 shadow-sm">
                        <img src="<?= htmlspecialchars(systemAppPath('/assets/images/Bagong_Pilipinas_logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Bagong Pilipinas" class="h-8 w-auto object-contain" loading="lazy">
                        <img src="<?= htmlspecialchars(systemAppPath('/assets/images/DA_logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Department of Agriculture" class="h-8 w-8 object-contain" loading="lazy">
                    </div>
                    <div class="hidden sm:block leading-tight">
                        <p class="text-xs text-gray-500">Applicant Portal · Bagong Pilipinas</p>
                        <p class="text-sm font-semibold text-gray-800">DA-ATI HRIS</p>
                    </div>
                </a>

                <nav class="hidden lg:flex items-center gap-1" aria-label="Primary navigation">
                    <?php foreach ($primaryLinks as $link): ?>
                        <?php $isActive = $activePage === $link['href']; ?>
                        <a href="<?= $link['href'] ?>"
                           class="inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-sm transition <?= $isActive ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-800' ?>">
                            <span class="material-symbols-outlined text-[18px]"><?= $link['icon'] ?></span>
                            <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>

                    <div class="relative">
                        <button id="applicantRecruitmentMenuBtn"
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-sm text-gray-600 transition hover:bg-gray-100 hover:text-gray-800"
                                aria-label="Open recruitment menu">
                            <span class="material-symbols-outlined text-[18px]">work_history</span>
                            Recruitment
                            <span class="material-symbols-outlined text-[18px]">expand_more</span>
                        </button>

                        <div id="applicantRecruitmentMenu" class="hidden absolute left-0 mt-2 w-64 rounded-xl border bg-white p-2 shadow-sm z-50">
                            <?php foreach ($recruitmentLinks as $link): ?>
                                <?php $isActive = $activePage === $link['href']; ?>
                                <a href="<?= $link['href'] ?>"
                                   class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm <?= $isActive ? 'bg-green-50 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-50' ?>">
                                    <span class="material-symbols-outlined text-sm"><?= $link['icon'] ?></span>
                                    <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </nav>
            </div>

            <!-- RIGHT: ACTIONS -->
            <div class="flex items-center gap-3">

                <button id="applicantMobileMenuBtn"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md border text-gray-700 hover:bg-gray-50 lg:hidden"
                        aria-label="Open navigation menu">
                    <span class="material-symbols-outlined text-[20px]">menu</span>
                </button>

                <div
                    class="relative"
                    data-topnav-notifications
                    data-endpoint="notifications.php"
                    data-action-field="action"
                    data-mark-read-action="mark_read"
                    data-id-field="notification_id"
                    data-csrf-field="csrf_token"
                    data-csrf-token="<?= htmlspecialchars((string)$applicantTopnavCsrfToken, ENT_QUOTES, 'UTF-8') ?>"
                    data-snapshot-action="topnav_snapshot"
                    data-supabase-url="<?= htmlspecialchars((string)$topnavSupabaseUrl, ENT_QUOTES, 'UTF-8') ?>"
                    data-supabase-anon-key="<?= htmlspecialchars((string)(trim((string)($_ENV['SUPABASE_ANON_KEY'] ?? $_SERVER['SUPABASE_ANON_KEY'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>"
                    data-realtime-access-token="<?= htmlspecialchars((string)($_SESSION['supabase']['access_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    data-user-id="<?= htmlspecialchars((string)$topnavApplicantUserId, ENT_QUOTES, 'UTF-8') ?>"
                    data-role-label="Applicant"
                >
                    <button type="button"
                            data-topnav-notification-trigger
                            class="relative rounded-md p-1 text-gray-600 transition hover:bg-gray-100 hover:text-green-700"
                            aria-label="Notifications">
                        <span class="material-symbols-outlined">notifications</span>
                        <?php if ($unreadNotificationCount > 0): ?>
                            <span data-topnav-unread-badge data-unread-count="<?= (int)$unreadNotificationCount ?>" class="absolute -right-1 -top-1 inline-flex min-h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white">
                                <?= htmlspecialchars($unreadNotificationBadge, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php else: ?>
                            <span data-topnav-unread-badge data-unread-count="0" class="absolute -right-1 -top-1 hidden min-h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white">0</span>
                        <?php endif; ?>
                    </button>

                    <div data-topnav-list-modal class="absolute right-0 top-full z-[90] mt-2 hidden w-[min(90vw,42rem)] rounded-2xl border border-slate-200 bg-white shadow-2xl">
                            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                                <div>
                                    <p class="text-base font-semibold text-slate-800">Notifications</p>
                                    <p class="text-xs text-slate-500"><span data-topnav-unread-text><?= (int)$unreadNotificationCount ?></span> unread</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="notifications.php" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-50">Open All</a>
                                    <button type="button" data-topnav-close="list" class="rounded-md border border-slate-200 px-2 py-1 text-slate-600 hover:bg-slate-50" aria-label="Close notifications">
                                        <span class="material-symbols-outlined text-base">close</span>
                                    </button>
                                </div>
                            </div>
                            <div class="max-h-[60vh] overflow-y-auto p-3" data-topnav-items>
                                <?php if (empty($topnavNotificationsPreview)): ?>
                                    <div class="rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No notifications available.</div>
                                <?php else: ?>
                                    <?php foreach ((array)$topnavNotificationsPreview as $item): ?>
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
                                        $itemCreatedAtLabel = $itemCreatedAtRaw !== '' ? formatDateTimeForPhilippines($itemCreatedAtRaw, 'M d, Y h:i A') . ' PST' : '-';
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
                                    <span class="material-symbols-outlined text-base">close</span>
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

                <div class="relative">
                    <button id="applicantUserMenuBtn"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-gray-700 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none">
                        <?php if (!empty($applicantTopnavPhotoUrl)): ?>
                            <img src="<?= htmlspecialchars((string)$applicantTopnavPhotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Applicant profile" class="h-8 w-8 rounded-full border object-cover">
                        <?php else: ?>
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-700 text-xs font-semibold text-white">
                                <?= htmlspecialchars((string)$applicantTopnavInitials, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                        <span class="hidden sm:block"><?= htmlspecialchars($applicantFirstName, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="material-symbols-outlined text-base">expand_more</span>
                    </button>

                    <div id="applicantUserMenu"
                         class="absolute right-0 z-50 mt-2 hidden w-72 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl text-sm">
                        <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($applicantDisplayName, ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-xs text-slate-500">Applicant</p>
                        </div>

                        <div class="p-2">
                            <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Account</p>
                            <div class="space-y-1">
                                <a href="profile.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-slate-700 transition hover:bg-slate-100">
                                    <span class="material-symbols-outlined text-[18px] text-slate-500">person</span>
                                    <span>My Profile</span>
                                </a>
                                <a href="profile.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-slate-700 transition hover:bg-slate-100">
                                    <span class="material-symbols-outlined text-[18px] text-slate-500">password</span>
                                    <span>Change Password</span>
                                </a>
                            </div>
                        </div>

                        <div class="mx-2 border-t border-slate-100"></div>

                        <div class="p-2">
                            <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tools</p>
                            <div class="space-y-1">
                                <a href="support.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-slate-700 transition hover:bg-slate-100">
                                    <span class="material-symbols-outlined text-[18px] text-slate-500">help</span>
                                    <span>Support</span>
                                </a>
                            </div>
                        </div>

                        <div class="mx-2 border-t border-slate-100"></div>

                        <div class="p-2">
                            <a href="<?= htmlspecialchars(systemAppPath('/pages/auth/logout.php'), ENT_QUOTES, 'UTF-8') ?>"
                               class="flex items-center gap-3 rounded-xl px-3 py-2 font-medium text-rose-600 transition hover:bg-rose-50">
                                <span class="material-symbols-outlined text-[18px]">logout</span>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="applicantMobileMenu" class="hidden border-t bg-white lg:hidden">
        <div class="mx-auto w-full max-w-7xl space-y-4 px-4 py-4 sm:px-6">
            <section>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Main</h3>
                <div class="space-y-1">
                    <?php foreach ($primaryLinks as $link): ?>
                        <?php $isActive = $activePage === $link['href']; ?>
                        <a href="<?= $link['href'] ?>"
                           class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm <?= $isActive ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <span class="material-symbols-outlined text-[18px]"><?= $link['icon'] ?></span>
                            <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Recruitment</h3>
                <div class="space-y-1">
                    <?php foreach ($recruitmentLinks as $link): ?>
                        <?php $isActive = $activePage === $link['href']; ?>
                        <a href="<?= $link['href'] ?>"
                           class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm <?= $isActive ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <span class="material-symbols-outlined text-[18px]"><?= $link['icon'] ?></span>
                            <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Account</h3>
                <div class="space-y-1">
                    <?php foreach ($accountLinks as $link): ?>
                        <?php $isActive = $activePage === $link['href']; ?>
                        <a href="<?= $link['href'] ?>"
                           class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm <?= $isActive ? 'bg-green-100 text-green-700 font-medium' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <span class="material-symbols-outlined text-[18px]"><?= $link['icon'] ?></span>
                            <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>
</header>

<script src="<?= htmlspecialchars(systemAppPath('/assets/js/shared/topnav-notifications.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>

<script>
    (function () {
        const mobileMenuBtn = document.getElementById('applicantMobileMenuBtn');
        const mobileMenu = document.getElementById('applicantMobileMenu');
        const menuBtn = document.getElementById('applicantUserMenuBtn');
        const menu = document.getElementById('applicantUserMenu');
        const recruitmentBtn = document.getElementById('applicantRecruitmentMenuBtn');
        const recruitmentMenu = document.getElementById('applicantRecruitmentMenu');

        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                document.dispatchEvent(new CustomEvent('hris:close-topnav-notifications', { detail: { source: 'applicant-mobile-menu' } }));
                mobileMenu.classList.toggle('hidden');
                if (!mobileMenu.classList.contains('hidden')) {
                    document.dispatchEvent(new CustomEvent('hris:sidebar-opened', { detail: { source: 'applicant-mobile-menu' } }));
                }
            });
        }

        if (menuBtn && menu) {
            menuBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                document.dispatchEvent(new CustomEvent('hris:close-topnav-notifications', { detail: { source: 'applicant-user-menu' } }));
                menu.classList.toggle('hidden');
                if (recruitmentMenu) {
                    recruitmentMenu.classList.add('hidden');
                }
                if (!menu.classList.contains('hidden')) {
                    document.dispatchEvent(new CustomEvent('hris:profile-menu-opened', { detail: { source: 'applicant-user-menu' } }));
                }
            });

            menu.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        }

        if (recruitmentBtn && recruitmentMenu) {
            recruitmentBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                document.dispatchEvent(new CustomEvent('hris:close-topnav-notifications', { detail: { source: 'applicant-recruitment-menu' } }));
                recruitmentMenu.classList.toggle('hidden');
                if (menu) {
                    menu.classList.add('hidden');
                }
                if (!recruitmentMenu.classList.contains('hidden')) {
                    document.dispatchEvent(new CustomEvent('hris:applicant-menu-opened', { detail: { source: 'applicant-recruitment-menu' } }));
                }
            });

            recruitmentMenu.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        }

        document.addEventListener('click', function () {
            if (menu) {
                menu.classList.add('hidden');
            }
            if (recruitmentMenu) {
                recruitmentMenu.classList.add('hidden');
            }
            if (mobileMenu) {
                mobileMenu.classList.add('hidden');
            }
        });

        document.addEventListener('hris:request-close-sidebar', function () {
            if (mobileMenu) {
                mobileMenu.classList.add('hidden');
            }
        });
    })();
</script>
