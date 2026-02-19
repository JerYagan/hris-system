<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
$resolvedStaffOfficeId = cleanText($staffOfficeId ?? null) ?? '';

$isPersonInScope = static function (string $personId) use ($isAdminScope, $resolvedStaffOfficeId, $supabaseUrl, $headers): bool {
    if (!isValidUuid($personId)) {
        return false;
    }

    if ($isAdminScope) {
        return true;
    }

    if (!isValidUuid($resolvedStaffOfficeId)) {
        return false;
    }

    $scopeResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=id'
        . '&person_id=eq.' . rawurlencode($personId)
        . '&office_id=eq.' . rawurlencode($resolvedStaffOfficeId)
        . '&is_current=eq.true'
        . '&limit=1',
        $headers
    );

    return isSuccessful($scopeResponse) && !empty($scopeResponse['data'][0]);
};

$notifyRequester = static function (string $recipientUserId, string $title, string $body) use ($supabaseUrl, $headers): void {
    if (!isValidUuid($recipientUserId)) {
        return;
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/notifications',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'recipient_user_id' => $recipientUserId,
            'category' => 'timekeeping',
            'title' => $title,
            'body' => $body,
            'link_url' => '/hris-system/pages/employee/timekeeping.php',
        ]]
    );
};

$writeActivityLog = static function (string $entityName, string $entityId, string $actionName, array $oldData, array $newData) use ($supabaseUrl, $headers, $staffUserId): void {
    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'timekeeping',
            'entity_name' => $entityName,
            'entity_id' => $entityId,
            'action_name' => $actionName,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => clientIp(),
        ]]
    );
};

if ($action === 'review_leave_request') {
    $requestId = cleanText($_POST['request_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $notes = cleanText($_POST['notes'] ?? null);

    if (!isValidUuid($requestId)) {
        redirectWithState('error', 'Invalid leave request selected.');
    }

    if (!in_array($decision, ['approved', 'rejected', 'cancelled'], true)) {
        redirectWithState('error', 'Invalid leave decision selected.');
    }

    $requestResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/leave_requests?select=id,person_id,status,person:people(user_id)&id=eq.' . rawurlencode($requestId) . '&limit=1',
        $headers
    );

    $requestRow = isSuccessful($requestResponse) ? ($requestResponse['data'][0] ?? null) : null;
    if (!is_array($requestRow)) {
        redirectWithState('error', 'Leave request not found.');
    }

    $personId = cleanText($requestRow['person_id'] ?? null) ?? '';
    $recipientUserId = cleanText($requestRow['person']['user_id'] ?? null) ?? '';

    if (!$isPersonInScope($personId)) {
        redirectWithState('error', 'Leave request is outside your office scope.');
    }

    $oldStatus = strtolower((string)(cleanText($requestRow['status'] ?? null) ?? 'pending'));
    if (!canTransitionStatus('leave_requests', $oldStatus, $decision)) {
        redirectWithState('error', 'Invalid leave request transition from ' . $oldStatus . ' to ' . $decision . '.');
    }

    $updateResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/leave_requests?id=eq.' . rawurlencode($requestId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'status' => $decision,
            'reviewed_by' => $staffUserId,
            'reviewed_at' => gmdate('c'),
            'review_notes' => $notes,
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($updateResponse)) {
        redirectWithState('error', 'Failed to update leave request status.');
    }

    $notifyRequester(
        $recipientUserId,
        'Leave Request Updated',
        'Your leave request was marked as ' . str_replace('_', ' ', $decision) . '.'
    );

    $writeActivityLog(
        'leave_requests',
        $requestId,
        'review_leave_request',
        ['status' => $oldStatus],
        ['status' => $decision, 'notes' => $notes]
    );

    redirectWithState('success', 'Leave request updated successfully.');
}

if ($action === 'review_overtime_request') {
    $requestId = cleanText($_POST['request_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $notes = cleanText($_POST['notes'] ?? null);

    if (!isValidUuid($requestId)) {
        redirectWithState('error', 'Invalid overtime request selected.');
    }

    if (!in_array($decision, ['approved', 'rejected', 'cancelled'], true)) {
        redirectWithState('error', 'Invalid overtime decision selected.');
    }

    $requestResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/overtime_requests?select=id,person_id,status,person:people(user_id)&id=eq.' . rawurlencode($requestId) . '&limit=1',
        $headers
    );

    $requestRow = isSuccessful($requestResponse) ? ($requestResponse['data'][0] ?? null) : null;
    if (!is_array($requestRow)) {
        redirectWithState('error', 'Overtime request not found.');
    }

    $personId = cleanText($requestRow['person_id'] ?? null) ?? '';
    $recipientUserId = cleanText($requestRow['person']['user_id'] ?? null) ?? '';

    if (!$isPersonInScope($personId)) {
        redirectWithState('error', 'Overtime request is outside your office scope.');
    }

    $oldStatus = strtolower((string)(cleanText($requestRow['status'] ?? null) ?? 'pending'));
    if (!canTransitionStatus('overtime_requests', $oldStatus, $decision)) {
        redirectWithState('error', 'Invalid overtime request transition from ' . $oldStatus . ' to ' . $decision . '.');
    }

    $updateResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/overtime_requests?id=eq.' . rawurlencode($requestId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'status' => $decision,
            'approved_by' => $staffUserId,
            'approved_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($updateResponse)) {
        redirectWithState('error', 'Failed to update overtime request status.');
    }

    $notifyRequester(
        $recipientUserId,
        'Overtime Request Updated',
        'Your overtime request was marked as ' . str_replace('_', ' ', $decision) . '.'
    );

    $writeActivityLog(
        'overtime_requests',
        $requestId,
        'review_overtime_request',
        ['status' => $oldStatus],
        ['status' => $decision, 'notes' => $notes]
    );

    redirectWithState('success', 'Overtime request updated successfully.');
}

if ($action === 'review_time_adjustment') {
    $requestId = cleanText($_POST['request_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $notes = cleanText($_POST['notes'] ?? null);

    if (!isValidUuid($requestId)) {
        redirectWithState('error', 'Invalid adjustment request selected.');
    }

    if (!in_array($decision, ['approved', 'rejected', 'needs_revision'], true)) {
        redirectWithState('error', 'Invalid adjustment decision selected.');
    }

    $requestResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/time_adjustment_requests?select=id,person_id,status,attendance_log_id,requested_time_in,requested_time_out,person:people(user_id)&id=eq.' . rawurlencode($requestId) . '&limit=1',
        $headers
    );

    $requestRow = isSuccessful($requestResponse) ? ($requestResponse['data'][0] ?? null) : null;
    if (!is_array($requestRow)) {
        redirectWithState('error', 'Time adjustment request not found.');
    }

    $personId = cleanText($requestRow['person_id'] ?? null) ?? '';
    $recipientUserId = cleanText($requestRow['person']['user_id'] ?? null) ?? '';

    if (!$isPersonInScope($personId)) {
        redirectWithState('error', 'Adjustment request is outside your office scope.');
    }

    $oldStatus = strtolower((string)(cleanText($requestRow['status'] ?? null) ?? 'pending'));
    if (!canTransitionStatus('time_adjustment_requests', $oldStatus, $decision)) {
        redirectWithState('error', 'Invalid adjustment transition from ' . $oldStatus . ' to ' . $decision . '.');
    }

    $updateResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/time_adjustment_requests?id=eq.' . rawurlencode($requestId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'status' => $decision,
            'reviewed_by' => $staffUserId,
            'reviewed_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($updateResponse)) {
        redirectWithState('error', 'Failed to update time adjustment status.');
    }

    $attendanceLogId = cleanText($requestRow['attendance_log_id'] ?? null) ?? '';
    $requestedTimeIn = cleanText($requestRow['requested_time_in'] ?? null);
    $requestedTimeOut = cleanText($requestRow['requested_time_out'] ?? null);

    if ($decision === 'approved' && isValidUuid($attendanceLogId) && ($requestedTimeIn || $requestedTimeOut)) {
        $attendancePayload = [];
        if ($requestedTimeIn) {
            $attendancePayload['time_in'] = $requestedTimeIn;
        }
        if ($requestedTimeOut) {
            $attendancePayload['time_out'] = $requestedTimeOut;
        }

        if (!empty($attendancePayload)) {
            apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/attendance_logs?id=eq.' . rawurlencode($attendanceLogId),
                array_merge($headers, ['Prefer: return=minimal']),
                $attendancePayload
            );
        }
    }

    $notifyRequester(
        $recipientUserId,
        'Time Adjustment Request Updated',
        'Your time adjustment request was marked as ' . str_replace('_', ' ', $decision) . '.'
    );

    $writeActivityLog(
        'time_adjustment_requests',
        $requestId,
        'review_time_adjustment',
        ['status' => $oldStatus],
        ['status' => $decision, 'notes' => $notes]
    );

    redirectWithState('success', 'Time adjustment request updated successfully.');
}

redirectWithState('error', 'Unknown timekeeping action.');
