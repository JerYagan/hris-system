<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = (string)($_POST['form_action'] ?? '');

if ($action === 'review_time_adjustment') {
    $requestId = cleanText($_POST['request_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $notes = cleanText($_POST['notes'] ?? null);

    if ($requestId === '' || $decision === '') {
        redirectWithState('error', 'Adjustment request and decision are required.');
    }

    if (!in_array($decision, ['approved', 'rejected', 'needs_revision'], true)) {
        redirectWithState('error', 'Invalid adjustment decision selected.');
    }

    $requestResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/time_adjustment_requests?select=id,status,person_id,attendance_log_id,requested_time_in,requested_time_out,person:people(user_id)&id=eq.' . $requestId . '&limit=1',
        $headers
    );

    $requestRow = $requestResponse['data'][0] ?? null;
    if (!is_array($requestRow)) {
        redirectWithState('error', 'Time adjustment request not found.');
    }

    $oldStatus = strtolower((string)($requestRow['status'] ?? 'pending'));

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/time_adjustment_requests?id=eq.' . $requestId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'status' => $decision,
            'reviewed_by' => $adminUserId !== '' ? $adminUserId : null,
            'reviewed_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update adjustment request.');
    }

    $attendanceLogId = (string)($requestRow['attendance_log_id'] ?? '');
    $requestedTimeIn = cleanText($requestRow['requested_time_in'] ?? null);
    $requestedTimeOut = cleanText($requestRow['requested_time_out'] ?? null);

    if ($decision === 'approved' && $attendanceLogId !== '' && ($requestedTimeIn || $requestedTimeOut)) {
        $attendancePatch = [];
        if ($requestedTimeIn) {
            $attendancePatch['time_in'] = $requestedTimeIn;
        }
        if ($requestedTimeOut) {
            $attendancePatch['time_out'] = $requestedTimeOut;
        }

        if (!empty($attendancePatch)) {
            apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/attendance_logs?id=eq.' . $attendanceLogId,
                array_merge($headers, ['Prefer: return=minimal']),
                $attendancePatch
            );
        }
    }

    $recipientUserId = (string)($requestRow['person']['user_id'] ?? '');
    if ($recipientUserId !== '') {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $recipientUserId,
                'category' => 'timekeeping',
                'title' => 'Time Adjustment Request Updated',
                'body' => 'Your time adjustment request was marked as ' . str_replace('_', ' ', $decision) . '.',
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
            'module_name' => 'timekeeping',
            'entity_name' => 'time_adjustment_requests',
            'entity_id' => $requestId,
            'action_name' => 'review_adjustment',
            'old_data' => ['status' => $oldStatus],
            'new_data' => ['status' => $decision, 'notes' => $notes],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Time adjustment request updated successfully.');
}

if ($action === 'review_leave_request') {
    $leaveRequestId = cleanText($_POST['leave_request_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $notes = cleanText($_POST['notes'] ?? null);

    if ($leaveRequestId === '' || $decision === '') {
        redirectWithState('error', 'Leave request and decision are required.');
    }

    if (!in_array($decision, ['approved', 'rejected'], true)) {
        redirectWithState('error', 'Invalid leave decision selected.');
    }

    $leaveResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/leave_requests?select=id,status,person_id,person:people(user_id)&id=eq.' . $leaveRequestId . '&limit=1',
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
            'module_name' => 'timekeeping',
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

redirectWithState('error', 'Unknown timekeeping action.');
