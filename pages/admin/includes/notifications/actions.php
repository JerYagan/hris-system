<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

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
        redirectWithState('error', 'Invalid notification selected.');
    }

    $notificationResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/notifications?select=id,is_read,title,recipient_user_id&id=eq.' . $notificationId . '&recipient_user_id=eq.' . $adminUserId . '&limit=1',
        $headers
    );

    $notificationRow = $notificationResponse['data'][0] ?? null;
    if (!is_array($notificationRow)) {
        redirectWithState('error', 'Notification record not found.');
    }

    if ((bool)($notificationRow['is_read'] ?? false) === true) {
        redirectWithState('success', 'Notification already marked as read.');
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
        redirectWithState('error', 'Failed to mark notification as read.');
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

    redirectWithState('success', 'Notification marked as read.');
}

if ($action === 'mark_all_notifications_read') {
    $unreadResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/notifications?select=id,is_read&recipient_user_id=eq.' . $adminUserId . '&is_read=eq.false&limit=5000',
        $headers
    );

    if (!isSuccessful($unreadResponse)) {
        redirectWithState('error', 'Failed to load unread notifications.');
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
        redirectWithState('success', 'No unread notifications found.');
    }

    $inFilter = formatInFilterList($ids);
    if ($inFilter === '') {
        redirectWithState('error', 'Unable to mark notifications as read.');
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
        redirectWithState('error', 'Failed to mark all notifications as read.');
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

    redirectWithState('success', 'Marked ' . count($ids) . ' notification(s) as read.');
}

if ($action === 'send_test_notification_email') {
    $recipientEmail = strtolower((string)(cleanText($_POST['recipient_email'] ?? null) ?? ''));
    if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        redirectWithState('error', 'Enter a valid recipient email for test delivery.');
    }

    if ($mailApiKey === '' || $mailFrom === '') {
        redirectWithState('error', 'MAIL_API_KEY and MAIL_FROM are required for Brevo email sending.');
    }

    $subject = 'DA HRIS Notification Test';
    $html = '<p>Hello,</p><p>This is a test notification email from DA HRIS admin notifications.</p><p>If you received this message, Brevo integration is working.</p>';

    $emailResponse = brevoSendTransactionalEmail(
        $mailApiKey,
        $mailFrom,
        $mailFromName,
        $recipientEmail,
        $recipientEmail,
        $subject,
        $html
    );

    if (!isSuccessful($emailResponse)) {
        $details = trim((string)($emailResponse['raw'] ?? ''));
        $message = 'Brevo send failed (HTTP ' . (int)($emailResponse['status'] ?? 0) . ').';
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

    redirectWithState('success', 'Test email sent via Brevo to ' . $recipientEmail . '.');
}

redirectWithState('error', 'Unknown notification action.');
