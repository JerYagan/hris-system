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

    logStatusTransition(
        $supabaseUrl,
        $headers,
        $adminUserId,
        'dashboard',
        'leave_requests',
        $leaveRequestId,
        'review_leave',
        $oldStatus,
        $decision,
        $notes
    );

    redirectWithState('success', 'Leave request updated successfully.');
}

if ($action === 'save_dashboard_announcement') {
    redirectWithState('error', 'Dashboard quick drafts are no longer used. Manage announcement publishing in Create Announcement.', 'create-announcement.php');
}

if ($action === 'save_dashboard_chart_schedule') {
    $attendanceChartTime = trim((string)(cleanText($_POST['attendance_chart_time'] ?? null) ?? ''));
    $recruitmentChartTime = trim((string)(cleanText($_POST['recruitment_chart_time'] ?? null) ?? ''));

    $isValidTime = static fn(string $timeValue): bool => (bool)preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $timeValue);

    if (!$isValidTime($attendanceChartTime) || !$isValidTime($recruitmentChartTime)) {
        redirectWithState('error', 'Invalid chart schedule time format.');
    }

    $upsertRows = [
        [
            'setting_key' => 'dashboard_chart_attendance_time',
            'setting_value' => ['value' => $attendanceChartTime],
            'updated_by' => $adminUserId !== '' ? $adminUserId : null,
            'updated_at' => gmdate('c'),
        ],
        [
            'setting_key' => 'dashboard_chart_recruitment_time',
            'setting_value' => ['value' => $recruitmentChartTime],
            'updated_by' => $adminUserId !== '' ? $adminUserId : null,
            'updated_at' => gmdate('c'),
        ],
    ];

    $upsertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/system_settings?on_conflict=setting_key',
        array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
        $upsertRows
    );

    if (!isSuccessful($upsertResponse)) {
        redirectWithState('error', 'Failed to save chart schedule settings.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'dashboard',
            'entity_name' => 'system_settings',
            'entity_id' => null,
            'action_name' => 'update_chart_schedule',
            'old_data' => null,
            'new_data' => [
                'dashboard_chart_attendance_time' => $attendanceChartTime,
                'dashboard_chart_recruitment_time' => $recruitmentChartTime,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Dashboard chart schedule settings saved.');
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
