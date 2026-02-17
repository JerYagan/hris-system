<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

$isAsyncRequest = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
    || isset($_POST['async']);

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

    redirectWithState($ok ? 'success' : 'error', $message, 'notifications.php');
};

$loadUnreadCount = static function () use ($supabaseUrl, $headers, $employeeUserId): int {
    $response = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/notifications?select=id'
        . '&recipient_user_id=eq.' . rawurlencode((string)$employeeUserId)
        . '&is_read=eq.false'
        . '&limit=500',
        $headers
    );

    if (!isSuccessful($response)) {
        return 0;
    }

    return count((array)($response['data'] ?? []));
};

if (!(bool)($employeeContextResolved ?? false)) {
    $respondNotificationAction(false, (string)($employeeContextError ?? 'Employee context could not be resolved.'));
}

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    $respondNotificationAction(false, 'Invalid request token. Please refresh and try again.');
}

$action = strtolower((string)cleanText($_POST['action'] ?? ''));

if (!in_array($action, ['mark_notification_read', 'mark_all_notifications_read'], true)) {
    $respondNotificationAction(false, 'Unsupported notification action.');
}

if ($action === 'mark_notification_read') {
    $notificationId = cleanText($_POST['notification_id'] ?? null);
    if (!isValidUuid($notificationId)) {
        $respondNotificationAction(false, 'Invalid notification id.');
    }

    $ownedRowResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/notifications?select=id,is_read'
        . '&id=eq.' . rawurlencode((string)$notificationId)
        . '&recipient_user_id=eq.' . rawurlencode((string)$employeeUserId)
        . '&limit=1',
        $headers
    );

    if (!isSuccessful($ownedRowResponse) || empty((array)($ownedRowResponse['data'] ?? []))) {
        $respondNotificationAction(false, 'Notification not found for your account.');
    }

    $notification = (array)$ownedRowResponse['data'][0];
    if ((bool)($notification['is_read'] ?? false)) {
        unset($_SESSION['employee_topnav_cache']);
        $respondNotificationAction(true, 'Notification is already marked as read.', [
            'notification_id' => (string)$notificationId,
            'unread_count' => $loadUnreadCount(),
        ]);
    }

    $updateResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/notifications?id=eq.' . rawurlencode((string)$notificationId),
        $headers,
        [
            'is_read' => true,
            'read_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($updateResponse)) {
        $respondNotificationAction(false, 'Unable to mark notification as read.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        $headers,
        [[
            'actor_user_id' => $employeeUserId,
            'module_name' => 'employee',
            'entity_name' => 'notifications',
            'entity_id' => $notificationId,
            'action_name' => 'mark_notification_read',
            'new_data' => [
                'is_read' => true,
            ],
        ]]
    );

    unset($_SESSION['employee_topnav_cache']);
    $respondNotificationAction(true, 'Notification marked as read.', [
        'notification_id' => (string)$notificationId,
        'unread_count' => $loadUnreadCount(),
    ]);
}

$unreadResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/notifications?select=id'
    . '&recipient_user_id=eq.' . rawurlencode((string)$employeeUserId)
    . '&is_read=eq.false'
    . '&limit=500',
    $headers
);

if (!isSuccessful($unreadResponse)) {
    $respondNotificationAction(false, 'Unable to load unread notifications.');
}

$unreadRows = (array)($unreadResponse['data'] ?? []);
if (empty($unreadRows)) {
    unset($_SESSION['employee_topnav_cache']);
    $respondNotificationAction(true, 'No unread notifications found.', [
        'affected_count' => 0,
        'unread_count' => 0,
    ]);
}

$updateResponse = apiRequest(
    'PATCH',
    $supabaseUrl
    . '/rest/v1/notifications?recipient_user_id=eq.' . rawurlencode((string)$employeeUserId)
    . '&is_read=eq.false',
    $headers,
    [
        'is_read' => true,
        'read_at' => gmdate('c'),
    ]
);

if (!isSuccessful($updateResponse)) {
    $respondNotificationAction(false, 'Unable to mark all notifications as read.');
}

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    $headers,
    [[
        'actor_user_id' => $employeeUserId,
        'module_name' => 'employee',
        'entity_name' => 'notifications',
        'action_name' => 'mark_all_notifications_read',
        'new_data' => [
            'affected_count' => count($unreadRows),
            'is_read' => true,
        ],
    ]]
);

unset($_SESSION['employee_topnav_cache']);
$respondNotificationAction(true, 'All notifications marked as read.', [
    'affected_count' => count($unreadRows),
    'unread_count' => 0,
]);
