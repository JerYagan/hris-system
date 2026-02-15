<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (!function_exists('dashboardIsValidUuid')) {
    function dashboardIsValidUuid(string $value): bool
    {
        return (bool)preg_match('/^[a-f0-9-]{36}$/i', $value);
    }
}

$action = (string)($_POST['form_action'] ?? '');

if ($action === 'review_leave_request_dashboard') {
    $leaveRequestId = cleanText($_POST['leave_request_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $notes = cleanText($_POST['notes'] ?? null);

    if (!dashboardIsValidUuid($leaveRequestId) || $decision === '') {
        redirectWithState('error', 'Leave request and decision are required.');
    }

    if (!in_array($decision, ['approved', 'rejected'], true)) {
        redirectWithState('error', 'Invalid leave decision selected.');
    }

    $leaveResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/leave_requests?select=id,status,person:people(user_id)&id=eq.' . $leaveRequestId . '&limit=1',
        $headers
    );

    $leaveRow = $leaveResponse['data'][0] ?? null;
    if (!is_array($leaveRow)) {
        redirectWithState('error', 'Leave request not found.');
    }

    $oldStatus = strtolower((string)($leaveRow['status'] ?? 'pending'));

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/leave_requests?id=eq.' . $leaveRequestId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'status' => $decision,
            'reviewed_by' => $adminUserId !== '' ? $adminUserId : null,
            'reviewed_at' => gmdate('c'),
            'review_notes' => $notes,
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update leave request.');
    }

    $recipientUserId = (string)($leaveRow['person']['user_id'] ?? '');
    if ($recipientUserId !== '') {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $recipientUserId,
                'category' => 'timekeeping',
                'title' => 'Leave Request Updated',
                'body' => 'Your leave request was ' . $decision . '.',
                'link_url' => '/hris-system/pages/employee/timekeeping.php',
            ]]
        );
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'dashboard',
            'entity_name' => 'leave_requests',
            'entity_id' => $leaveRequestId,
            'action_name' => 'review_leave',
            'old_data' => ['status' => $oldStatus],
            'new_data' => ['status' => $decision, 'notes' => $notes],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Leave request updated successfully.');
}

if ($action === 'save_dashboard_announcement') {
    $title = cleanText($_POST['announcement_title'] ?? null) ?? '';
    $body = cleanText($_POST['announcement_body'] ?? null) ?? '';
    $announcementState = strtolower((string)(cleanText($_POST['announcement_state'] ?? null) ?? 'draft'));

    if ($title === '' || $body === '') {
        redirectWithState('error', 'Announcement title and body are required.');
    }

    if (!in_array($announcementState, ['draft', 'queued'], true)) {
        $announcementState = 'draft';
    }

    $actionName = $announcementState === 'queued' ? 'queue_announcement' : 'save_announcement_draft';

    $logResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'dashboard',
            'entity_name' => 'announcements',
            'entity_id' => null,
            'action_name' => $actionName,
            'old_data' => null,
            'new_data' => [
                'title' => $title,
                'body' => $body,
                'state' => $announcementState,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    if (!isSuccessful($logResponse)) {
        redirectWithState('error', 'Failed to save announcement draft.');
    }

    if ($announcementState === 'queued') {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $adminUserId,
                'category' => 'system',
                'title' => 'Announcement queued',
                'body' => '"' . $title . '" has been added to the publish queue.',
                'link_url' => '/hris-system/pages/admin/dashboard.php',
            ]]
        );
    }

    $successMessage = $announcementState === 'queued'
        ? 'Announcement queued for publishing.'
        : 'Announcement draft saved successfully.';

    redirectWithState('success', $successMessage);
}

if ($action === 'mark_dashboard_notification_read') {
    $notificationId = cleanText($_POST['notification_id'] ?? null) ?? '';

    if (!dashboardIsValidUuid($notificationId)) {
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
            'module_name' => 'dashboard',
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

if ($action !== '') {
    redirectWithState('error', 'Unknown dashboard action.');
}
