<?php

require_once __DIR__ . '/../../../shared/lib/notification-domain.php';

$requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

$isAsyncRequest = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
    || isset($_POST['async'])
    || isset($_GET['async']);

$respondNotificationAction = static function (bool $ok, string $message, array $payload = []) use ($isAsyncRequest): never {
    if ($isAsyncRequest) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode(array_merge([
            'ok' => $ok,
            'message' => $message,
        ], $payload), JSON_UNESCAPED_UNICODE);
        exit;
    }

    redirectWithState($ok ? 'success' : 'error', $message);
};

$loadNotificationSnapshot = static function () use ($supabaseUrl, $headers, $staffUserId): array {
    $delegate = 'notificationServiceLoadSnapshot';
    return $delegate($supabaseUrl, $headers, $staffUserId, [
        'preview_limit' => 8,
        'unread_limit' => 500,
    ]);
};

$loadUnreadCount = static function () use ($loadNotificationSnapshot): int {
    return (int)($loadNotificationSnapshot()['unread_count'] ?? 0);
};

$loadTopnavItems = static function () use ($loadNotificationSnapshot): array {
    return (array)($loadNotificationSnapshot()['items'] ?? []);
};

if ($requestMethod === 'GET') {
    $action = cleanText($_GET['action'] ?? null) ?? '';
    if ($action === 'topnav_snapshot') {
        $respondNotificationAction(true, 'Notification snapshot loaded.', [
            'unread_count' => $loadUnreadCount(),
            'items' => $loadTopnavItems(),
        ]);
    }

    return;
}

if ($requestMethod !== 'POST') {
    return;
}

requireStaffPostWithCsrf($_POST['csrf_token'] ?? null);

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if ($action === 'mark_notification_read') {
    $notificationId = cleanText($_POST['notification_id'] ?? null) ?? '';
    if (!isValidUuid($notificationId)) {
        $respondNotificationAction(false, 'Invalid notification selected.', []);
    }

    $notificationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/notifications?select=id,is_read,title,recipient_user_id'
        . '&id=eq.' . rawurlencode($notificationId)
        . '&recipient_user_id=eq.' . rawurlencode($staffUserId)
        . '&limit=1',
        $headers
    );

    $notificationRow = isSuccessful($notificationResponse) ? ($notificationResponse['data'][0] ?? null) : null;
    if (!is_array($notificationRow)) {
        $respondNotificationAction(false, 'Notification record not found.', []);
    }

    if ((bool)($notificationRow['is_read'] ?? false) === true) {
        unset($_SESSION['staff_topnav_cache']);
        $respondNotificationAction(true, 'Notification already marked as read.', [
            'notification_id' => $notificationId,
            'unread_count' => $loadUnreadCount(),
        ]);
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/notifications?id=eq.' . rawurlencode($notificationId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'is_read' => true,
            'read_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        $respondNotificationAction(false, 'Failed to mark notification as read.', []);
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'notifications',
            'entity_name' => 'notifications',
            'entity_id' => $notificationId,
            'action_name' => 'mark_read',
            'old_data' => ['is_read' => false],
            'new_data' => ['is_read' => true],
            'ip_address' => clientIp(),
        ]]
    );

    unset($_SESSION['staff_topnav_cache']);
    $respondNotificationAction(true, 'Notification marked as read.', [
        'notification_id' => $notificationId,
        'unread_count' => $loadUnreadCount(),
    ]);
}

if ($action === 'mark_all_notifications_read') {
    $unreadResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/notifications?select=id,is_read'
        . '&recipient_user_id=eq.' . rawurlencode($staffUserId)
        . '&is_read=eq.false'
        . '&limit=5000',
        $headers
    );

    if (!isSuccessful($unreadResponse)) {
        $respondNotificationAction(false, 'Failed to load unread notifications.', []);
    }

    $ids = [];
    foreach ((array)($unreadResponse['data'] ?? []) as $row) {
        $id = cleanText($row['id'] ?? null) ?? '';
        if (!isValidUuid($id)) {
            continue;
        }

        $ids[] = $id;
    }

    if (empty($ids)) {
        unset($_SESSION['staff_topnav_cache']);
        $respondNotificationAction(true, 'No unread notifications found.', [
            'affected_count' => 0,
            'unread_count' => 0,
        ]);
    }

    $inFilter = sanitizeUuidListForInFilter($ids);
    if ($inFilter === '') {
        $respondNotificationAction(false, 'Failed to resolve unread notifications for update.', []);
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/notifications?id=in.(' . rawurlencode($inFilter) . ')',
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'is_read' => true,
            'read_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        $respondNotificationAction(false, 'Failed to mark all notifications as read.', []);
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'notifications',
            'entity_name' => 'notifications',
            'entity_id' => null,
            'action_name' => 'mark_all_read',
            'old_data' => ['unread_count' => count($ids)],
            'new_data' => ['marked_read_count' => count($ids)],
            'ip_address' => clientIp(),
        ]]
    );

    unset($_SESSION['staff_topnav_cache']);
    $respondNotificationAction(true, 'Marked ' . count($ids) . ' notification(s) as read.', [
        'affected_count' => count($ids),
        'unread_count' => 0,
    ]);
}

logStaffSecurityEvent(
    $supabaseUrl,
    $headers,
    $staffUserId,
    'notifications',
    'unknown_action_attempt',
    [
        'form_action' => $action,
    ]
);

$respondNotificationAction(false, 'Unknown notifications action.', []);
