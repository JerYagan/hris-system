<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

requireStaffPostWithCsrf($_POST['csrf_token'] ?? null);

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if ($action === 'mark_notification_read') {
    $notificationId = cleanText($_POST['notification_id'] ?? null) ?? '';
    if (!isValidUuid($notificationId)) {
        redirectWithState('error', 'Invalid notification selected.');
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
        redirectWithState('error', 'Notification record not found.');
    }

    if ((bool)($notificationRow['is_read'] ?? false) === true) {
        redirectWithState('success', 'Notification already marked as read.');
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
        redirectWithState('error', 'Failed to mark notification as read.');
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

    redirectWithState('success', 'Notification marked as read.');
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
        redirectWithState('error', 'Failed to load unread notifications.');
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
        redirectWithState('success', 'No unread notifications found.');
    }

    $inFilter = sanitizeUuidListForInFilter($ids);
    if ($inFilter === '') {
        redirectWithState('error', 'Failed to resolve unread notifications for update.');
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
        redirectWithState('error', 'Failed to mark all notifications as read.');
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

    redirectWithState('success', 'Marked ' . count($ids) . ' notification(s) as read.');
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

redirectWithState('error', 'Unknown notifications action.');
