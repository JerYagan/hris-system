<?php

require_once __DIR__ . '/email.php';

if (!function_exists('smtpConfigIsReady')) {
    function smtpConfigIsReady(array $smtpConfig, string $fromEmail): bool
    {
        return false;
    }
}

if (!function_exists('smtpSendTransactionalEmail')) {
    function smtpSendTransactionalEmail(array $smtpConfig, string $fromEmail, string $fromName, string $toEmail, string $toName, string $subject, string $htmlContent): array
    {
        return [
            'status' => 500,
            'data' => [],
            'raw' => 'SMTP helper not loaded.',
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    redirectWithState($ok ? 'success' : 'error', $message);
};

$loadUnreadCount = static function () use ($supabaseUrl, $headers, $adminUserId): int {
    $response = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/notifications?select=id'
        . '&recipient_user_id=eq.' . rawurlencode($adminUserId)
        . '&is_read=eq.false'
        . '&category=neq.announcement'
        . '&limit=500',
        $headers
    );

    if (!isSuccessful($response)) {
        return 0;
    }

    return count((array)($response['data'] ?? []));
};

if (!function_exists('isValidUuid')) {
    function isValidUuid(string $value): bool
    {
        return (bool)preg_match('/^[a-f0-9-]{36}$/i', $value);
    }
}

if (!function_exists('formatInFilterList')) {
    function formatInFilterList(array $ids): string
    {
        $valid = [];
        foreach ($ids as $id) {
            $candidate = strtolower(trim((string)$id));
            if (!isValidUuid($candidate)) {
                continue;
            }
            $valid[] = $candidate;
        }

        return implode(',', array_values(array_unique($valid)));
    }
}

$action = (string)($_POST['form_action'] ?? '');

if ($action === 'mark_notification_read') {
    $notificationId = cleanText($_POST['notification_id'] ?? null) ?? '';
    if (!isValidUuid($notificationId)) {
        $respondNotificationAction(false, 'Invalid notification selected.', []);
    }

    $notificationResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/notifications?select=id,is_read,title,recipient_user_id&id=eq.' . $notificationId . '&recipient_user_id=eq.' . $adminUserId . '&limit=1',
        $headers
    );

    $notificationRow = $notificationResponse['data'][0] ?? null;
    if (!is_array($notificationRow)) {
        $respondNotificationAction(false, 'Notification record not found.', []);
    }

    if ((bool)($notificationRow['is_read'] ?? false) === true) {
        $respondNotificationAction(true, 'Notification already marked as read.', [
            'notification_id' => $notificationId,
            'unread_count' => $loadUnreadCount(),
        ]);
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/notifications?id=eq.' . $notificationId,
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
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'notifications',
            'entity_name' => 'notifications',
            'entity_id' => $notificationId,
            'action_name' => 'mark_read',
            'old_data' => ['is_read' => false],
            'new_data' => ['is_read' => true],
            'ip_address' => clientIp(),
        ]]
    );

    $respondNotificationAction(true, 'Notification marked as read.', [
        'notification_id' => $notificationId,
        'unread_count' => $loadUnreadCount(),
    ]);
}

if ($action === 'mark_all_notifications_read') {
    $unreadResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/notifications?select=id,is_read&recipient_user_id=eq.' . $adminUserId . '&is_read=eq.false&limit=5000',
        $headers
    );

    if (!isSuccessful($unreadResponse)) {
        $respondNotificationAction(false, 'Failed to load unread notifications.', []);
    }

    $ids = [];
    foreach ((array)$unreadResponse['data'] as $row) {
        $id = (string)($row['id'] ?? '');
        if (!isValidUuid($id)) {
            continue;
        }
        $ids[] = $id;
    }

    if (empty($ids)) {
        $respondNotificationAction(true, 'No unread notifications found.', [
            'affected_count' => 0,
            'unread_count' => 0,
        ]);
    }

    $inFilter = formatInFilterList($ids);
    if ($inFilter === '') {
        $respondNotificationAction(false, 'Unable to mark notifications as read.', []);
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/notifications?id=in.(' . $inFilter . ')',
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
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'notifications',
            'entity_name' => 'notifications',
            'entity_id' => null,
            'action_name' => 'mark_all_read',
            'old_data' => ['unread_count' => count($ids)],
            'new_data' => ['marked_read_count' => count($ids)],
            'ip_address' => clientIp(),
        ]]
    );

    $respondNotificationAction(true, 'Marked ' . count($ids) . ' notification(s) as read.', [
        'affected_count' => count($ids),
        'unread_count' => 0,
    ]);
}

if ($action === 'send_test_notification_email') {
    $recipientEmail = strtolower((string)(cleanText($_POST['recipient_email'] ?? null) ?? ''));
    if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        redirectWithState('error', 'Enter a valid recipient email for test delivery.');
    }

    if (!smtpConfigIsReady($smtpConfig, $mailFrom)) {
        redirectWithState('error', 'SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD, and MAIL_FROM are required for SMTP email sending.');
    }

    $subject = 'DA HRIS Notification Test';
    $html = '<p>Hello,</p><p>This is a test notification email from DA HRIS admin notifications.</p><p>If you received this message, SMTP integration is working.</p>';

    $emailResponse = smtpSendTransactionalEmail(
        $smtpConfig,
        $mailFrom,
        $mailFromName,
        $recipientEmail,
        $recipientEmail,
        $subject,
        $html
    );

    if (!isSuccessful($emailResponse)) {
        $details = trim((string)($emailResponse['raw'] ?? ''));
        $message = 'SMTP send failed (HTTP ' . (int)($emailResponse['status'] ?? 0) . ').';
        if ($details !== '') {
            $message .= ' ' . $details;
        }
        redirectWithState('error', $message);
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'notifications',
            'entity_name' => 'email_delivery',
            'entity_id' => null,
            'action_name' => 'send_test_email',
            'old_data' => null,
            'new_data' => [
                'provider' => $notificationEmailProvider,
                'recipient_email' => $recipientEmail,
                'status_code' => (int)($emailResponse['status'] ?? 0),
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Test email sent via SMTP to ' . $recipientEmail . '.');
}

$respondNotificationAction(false, 'Unknown notification action.', []);
