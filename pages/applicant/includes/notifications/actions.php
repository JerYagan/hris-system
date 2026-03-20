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

    redirectWithState($ok ? 'success' : 'error', $message, 'notifications.php');
};

$loadNotificationSnapshot = static function () use ($supabaseUrl, $headers, $applicantUserId): array {
    $delegate = 'notificationServiceLoadSnapshot';
    return $delegate($supabaseUrl, $headers, $applicantUserId, [
        'preview_limit' => 8,
        'unread_limit' => 200,
    ]);
};

$loadUnreadCount = static function () use ($loadNotificationSnapshot): int {
    return (int)($loadNotificationSnapshot()['unread_count'] ?? 0);
};

$loadTopnavItems = static function () use ($loadNotificationSnapshot): array {
    return (array)($loadNotificationSnapshot()['items'] ?? []);
};

if ($requestMethod === 'GET') {
    $action = cleanText($_GET['action'] ?? null);
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
