<?php

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

$loadTopnavItems = static function () use ($supabaseUrl, $headers, $applicantUserId): array {
    $response = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/notifications?select=id,title,body,link_url,is_read,created_at,category'
        . '&recipient_user_id=eq.' . rawurlencode($applicantUserId)
        . '&order=created_at.desc&limit=8',
        $headers
    );

    if (!isSuccessful($response)) {
        return [];
    }

    return array_map(static function (array $row): array {
        $createdAt = trim((string)($row['created_at'] ?? ''));
        return [
            'id' => (string)($row['id'] ?? ''),
            'title' => (string)($row['title'] ?? 'Notification'),
            'body' => (string)($row['body'] ?? 'No details available.'),
            'link_url' => (string)($row['link_url'] ?? ''),
            'category' => (string)($row['category'] ?? 'general'),
            'is_read' => (bool)($row['is_read'] ?? false),
            'created_at' => $createdAt,
            'created_at_label' => $createdAt !== '' ? formatDateTimeForPhilippines($createdAt, 'M d, Y h:i A') . ' PST' : '-',
        ];
    }, array_values((array)($response['data'] ?? [])));
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
