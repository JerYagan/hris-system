<?php
include __DIR__ . '/auth-guard.php';
require_once __DIR__ . '/lib/staff-backend.php';

$shellContext = systemShellContext($pageTitle ?? null, 'Staff | DA HRIS', $activePage ?? '', $breadcrumbs ?? [], ['Dashboard']);
$pageTitle = $shellContext['page_title'];
$activePage = $shellContext['active_page'];
$breadcrumbs = $shellContext['breadcrumbs'];

$staffTopnavDisplayName = 'Staff User';
$staffTopnavRoleLabel = 'Staff';
$staffUnreadNotificationCount = 0;
$staffUnreadNotificationBadge = '0';
$staffTopnavNotificationsPreview = [];
$staffTopnavCsrfToken = function_exists('ensureCsrfToken') ? ensureCsrfToken() : '';
$staffTopnavPhotoUrl = null;
$staffTopnavInitials = 'ST';

if (function_exists('staffBackendContext')) {
    $topnavBackend = staffBackendContext();
    $topnavSupabaseUrl = (string)($topnavBackend['supabase_url'] ?? '');
    $topnavStaffUserId = (string)($topnavBackend['staff_user_id'] ?? '');
    $topnavHeaders = (array)($topnavBackend['headers'] ?? []);

    $cacheTtlSeconds = 45;
    $topnavCache = (array)($_SESSION['staff_topnav_cache'] ?? []);
    $cacheIsFresh = systemTopnavCacheIsFresh($topnavCache, $topnavStaffUserId, $cacheTtlSeconds);

    if ($cacheIsFresh) {
        $staffTopnavDisplayName = (string)($topnavCache['display_name'] ?? $staffTopnavDisplayName);
        $staffTopnavRoleLabel = (string)($topnavCache['display_role'] ?? $topnavCache['role_label'] ?? $staffTopnavRoleLabel);
        $staffTopnavPhotoUrl = systemTopnavResolveProfilePhotoUrl((string)($topnavCache['profile_photo_url'] ?? ''));
        $staffUnreadNotificationCount = max(0, (int)($topnavCache['unread_count'] ?? 0));
        $staffTopnavNotificationsPreview = (array)($topnavCache['notifications_preview'] ?? []);
    }

    if (!$cacheIsFresh && $topnavSupabaseUrl !== '' && $topnavStaffUserId !== '' && !empty($topnavHeaders) && function_exists('apiRequest') && function_exists('isSuccessful')) {
        $profileData = systemTopnavFetchPeopleProfile($topnavSupabaseUrl, $topnavHeaders, $topnavStaffUserId);
        $profilePhotoPath = (string)($profileData['profile_photo_path'] ?? '');
        if ((string)($profileData['display_name'] ?? '') !== '') {
            $staffTopnavDisplayName = (string)$profileData['display_name'];
        }

        if (isset($staffRoleName) && is_string($staffRoleName) && trim($staffRoleName) !== '') {
            $staffTopnavRoleLabel = trim($staffRoleName);
        }

        $notificationSummary = systemTopnavFetchNotificationSummary($topnavSupabaseUrl, $topnavHeaders, $topnavStaffUserId);
        $staffUnreadNotificationCount = (int)($notificationSummary['unread_count'] ?? 0);
        $staffTopnavNotificationsPreview = (array)($notificationSummary['notifications_preview'] ?? []);

        $staffTopnavPhotoUrl = systemTopnavResolveProfilePhotoUrl($profilePhotoPath);
        $_SESSION['staff_topnav_cache'] = systemTopnavCachePayload(
            $topnavStaffUserId,
            $staffTopnavDisplayName,
            $staffTopnavRoleLabel,
            $profilePhotoPath,
            $staffUnreadNotificationCount,
            $staffTopnavNotificationsPreview,
            ['role_label' => $staffTopnavRoleLabel]
        );
    }
}

$staffTopnavInitials = systemTopnavBuildInitials($staffTopnavDisplayName, 'ST');

$staffUnreadNotificationBadge = $staffUnreadNotificationCount > 99
    ? '99+'
    : (string)$staffUnreadNotificationCount;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="flex min-h-screen">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <button id="sidebarBackdrop" type="button" class="fixed inset-0 z-40 hidden bg-slate-950/55" aria-label="Close sidebar overlay"></button>

    <div id="mainContent" class="flex flex-col flex-1 transition-all duration-200 ease-in-out">
        <?php include __DIR__ . '/topnav.php'; ?>

        <main class="flex-1 p-6">
            <?= $content ?? '' ?>
        </main>
    </div>
</div>

<script src="<?= htmlspecialchars(systemAppPath('/assets/js/script.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars(systemAppPath('/assets/js/alert.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php foreach (($pageScripts ?? []) as $pageScript): ?>
    <script type="module" src="<?= htmlspecialchars((string)$pageScript, ENT_QUOTES, 'UTF-8') ?>" defer></script>
<?php endforeach; ?>
<?= systemRenderQaPerfConsoleScript() ?>
</body>
</html>
