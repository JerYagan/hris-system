<?php
include __DIR__ . '/auth-guard.php';
require_once __DIR__ . '/lib/staff-backend.php';

$pageTitle = $pageTitle ?? 'Staff | DA HRIS';
$activePage = $activePage ?? '';
$breadcrumbs = $breadcrumbs ?? ['Dashboard'];

$staffTopnavDisplayName = 'Staff User';
$staffTopnavRoleLabel = 'Staff';
$staffUnreadNotificationCount = 0;
$staffUnreadNotificationBadge = '0';
$staffTopnavNotificationsPreview = [];
$staffTopnavCsrfToken = function_exists('ensureCsrfToken') ? ensureCsrfToken() : '';
$staffTopnavPhotoUrl = null;
$staffTopnavInitials = 'ST';

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

    return '/hris-system/storage/document/' . ltrim($path, '/');
};

if (function_exists('staffBackendContext')) {
    $topnavBackend = staffBackendContext();
    $topnavSupabaseUrl = (string)($topnavBackend['supabase_url'] ?? '');
    $topnavStaffUserId = (string)($topnavBackend['staff_user_id'] ?? '');
    $topnavHeaders = (array)($topnavBackend['headers'] ?? []);

    $cacheTtlSeconds = 45;
    $topnavCache = (array)($_SESSION['staff_topnav_cache'] ?? []);
    $cacheUserId = (string)($topnavCache['user_id'] ?? '');
    $cacheTimestamp = (int)($topnavCache['cached_at'] ?? 0);
    $cacheIsFresh = $cacheUserId !== ''
        && $cacheUserId === $topnavStaffUserId
        && $cacheTimestamp > 0
        && (time() - $cacheTimestamp) <= $cacheTtlSeconds;

    if ($cacheIsFresh) {
        $staffTopnavDisplayName = (string)($topnavCache['display_name'] ?? $staffTopnavDisplayName);
        $staffTopnavRoleLabel = (string)($topnavCache['role_label'] ?? $staffTopnavRoleLabel);
        $staffTopnavPhotoUrl = $resolveProfilePhotoUrl((string)($topnavCache['profile_photo_url'] ?? ''));
        $staffUnreadNotificationCount = max(0, (int)($topnavCache['unread_count'] ?? 0));
        $staffTopnavNotificationsPreview = (array)($topnavCache['notifications_preview'] ?? []);
    }

    if (!$cacheIsFresh && $topnavSupabaseUrl !== '' && $topnavStaffUserId !== '' && !empty($topnavHeaders) && function_exists('apiRequest') && function_exists('isSuccessful')) {
        $peopleResponse = apiRequest(
            'GET',
            rtrim($topnavSupabaseUrl, '/') . '/rest/v1/people?select=first_name,surname,profile_photo_url&user_id=eq.' . rawurlencode($topnavStaffUserId) . '&limit=1',
            $topnavHeaders
        );

        $profilePhotoPath = null;
        if (isSuccessful($peopleResponse) && !empty((array)($peopleResponse['data'] ?? []))) {
            $peopleRow = (array)$peopleResponse['data'][0];
            $firstName = trim((string)($peopleRow['first_name'] ?? ''));
            $surname = trim((string)($peopleRow['surname'] ?? ''));
            $profilePhotoPath = trim((string)($peopleRow['profile_photo_url'] ?? ''));

            $fullName = trim($firstName . ' ' . $surname);
            if ($fullName !== '') {
                $staffTopnavDisplayName = $fullName;
            }
        }

        if (isset($staffRoleName) && is_string($staffRoleName) && trim($staffRoleName) !== '') {
            $staffTopnavRoleLabel = trim($staffRoleName);
        }

        $unreadResponse = apiRequest(
            'GET',
            rtrim($topnavSupabaseUrl, '/') . '/rest/v1/notifications?select=id&recipient_user_id=eq.' . rawurlencode($topnavStaffUserId) . '&is_read=eq.false&limit=200',
            $topnavHeaders
        );

        if (isSuccessful($unreadResponse)) {
            $staffUnreadNotificationCount = count((array)($unreadResponse['data'] ?? []));
        }

        $previewResponse = apiRequest(
            'GET',
            rtrim($topnavSupabaseUrl, '/')
                . '/rest/v1/notifications?select=id,title,body,link_url,is_read,created_at,category'
                . '&recipient_user_id=eq.' . rawurlencode($topnavStaffUserId)
                . '&order=created_at.desc&limit=8',
            $topnavHeaders
        );

        if (isSuccessful($previewResponse)) {
            $staffTopnavNotificationsPreview = array_values((array)($previewResponse['data'] ?? []));
        }

        $staffTopnavPhotoUrl = $resolveProfilePhotoUrl($profilePhotoPath);
        $_SESSION['staff_topnav_cache'] = [
            'user_id' => $topnavStaffUserId,
            'display_name' => $staffTopnavDisplayName,
            'role_label' => $staffTopnavRoleLabel,
            'profile_photo_url' => (string)($profilePhotoPath ?? ''),
            'unread_count' => $staffUnreadNotificationCount,
            'notifications_preview' => $staffTopnavNotificationsPreview,
            'cached_at' => time(),
        ];
    }
}

$nameParts = preg_split('/\s+/', trim($staffTopnavDisplayName)) ?: [];
$initials = '';
foreach (array_slice($nameParts, 0, 2) as $part) {
    if ($part !== '') {
        $initials .= strtoupper(substr($part, 0, 1));
    }
}
if ($initials !== '') {
    $staffTopnavInitials = $initials;
}

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

    <div id="mainContent" class="flex flex-col flex-1 transition-all duration-200 ease-in-out">
        <?php include __DIR__ . '/topnav.php'; ?>

        <main class="flex-1 p-6">
            <?= $content ?? '' ?>
        </main>
    </div>
</div>

<script src="../../assets/js/script.js"></script>
<script src="../../assets/js/alert.js"></script>
<?php foreach (($pageScripts ?? []) as $pageScript): ?>
    <script type="module" src="<?= htmlspecialchars((string)$pageScript, ENT_QUOTES, 'UTF-8') ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
