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

$loadUnreadCount = static function () use ($supabaseUrl, $headers, $applicantUserId): int {
    $response = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/notifications?select=id'
        . '&recipient_user_id=eq.' . rawurlencode($applicantUserId)
        . '&is_read=eq.false&limit=200',
        $headers
    );

    if (!isSuccessful($response)) {
        return 0;
    }

    return count((array)($response['data'] ?? []));
};

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    $respondNotificationAction(false, 'Invalid request token. Please refresh and try again.', []);
}

if ($applicantUserId === '') {
    $respondNotificationAction(false, 'Applicant session is missing. Please login again.', []);
}

if (!isValidUuid($applicantUserId)) {
    $respondNotificationAction(false, 'Invalid applicant session context. Please login again.', []);
}

$action = cleanText($_POST['action'] ?? null);
if ($action === null) {
    $respondNotificationAction(false, 'Invalid notifications action.', []);
}

$readAt = date('c');

if ($action === 'mark_read') {
    $notificationId = cleanText($_POST['notification_id'] ?? null);
    if ($notificationId === null || !isValidUuid($notificationId)) {
        $respondNotificationAction(false, 'Notification reference is missing.', []);
    }

    $markReadResponse = apiRequest(
        'PATCH',
        $supabaseUrl
        . '/rest/v1/notifications?id=eq.' . rawurlencode($notificationId)
        . '&recipient_user_id=eq.' . rawurlencode($applicantUserId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'is_read' => true,
            'read_at' => $readAt,
        ]
    );

    if (!isSuccessful($markReadResponse)) {
        $respondNotificationAction(false, 'Failed to mark notification as read.', []);
    }

    unset($_SESSION['applicant_topnav_cache']);
    $respondNotificationAction(true, 'Notification marked as read.', [
        'notification_id' => (string)$notificationId,
        'unread_count' => $loadUnreadCount(),
    ]);
}

if ($action === 'mark_all_read') {
    $markAllResponse = apiRequest(
        'PATCH',
        $supabaseUrl
        . '/rest/v1/notifications?recipient_user_id=eq.' . rawurlencode($applicantUserId)
        . '&is_read=eq.false',
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'is_read' => true,
            'read_at' => $readAt,
        ]
    );

    if (!isSuccessful($markAllResponse)) {
        $respondNotificationAction(false, 'Failed to mark all notifications as read.', []);
    }

    unset($_SESSION['applicant_topnav_cache']);
    $respondNotificationAction(true, 'All notifications are marked as read.', [
        'unread_count' => 0,
    ]);
}

$respondNotificationAction(false, 'Unsupported notifications action.', []);
