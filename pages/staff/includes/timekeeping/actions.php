<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';
$actorUserId = isValidUuid((string)($staffUserId ?? '')) ? (string)$staffUserId : null;

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

$isPersonInScope = static function (string $personId) use ($supabaseUrl, $headers): bool {
    if (!isValidUuid($personId)) {
        return false;
    }

    $scopeResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=id'
        . '&person_id=eq.' . rawurlencode($personId)
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

$notifyAdmins = static function (string $title, string $body) use ($supabaseUrl, $headers): void {
    $adminUserIdMap = fetchActiveRoleUserIdMap($supabaseUrl, $headers, 'admin');
    foreach (array_keys($adminUserIdMap) as $adminUserId) {
        if (!isValidUuid((string)$adminUserId)) {
            continue;
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => (string)$adminUserId,
                'category' => 'timekeeping',
                'title' => $title,
                'body' => $body,
                'link_url' => '/hris-system/pages/admin/timekeeping.php',
            ]]
        );
    }
};

$writeActivityLog = static function (string $entityName, string $entityId, string $actionName, array $oldData, array $newData) use ($supabaseUrl, $headers, $actorUserId): void {
    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $actorUserId,
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
        redirectWithState('error', 'Leave request target is invalid or no longer active.');
    }

    $oldStatus = strtolower((string)(cleanText($requestRow['status'] ?? null) ?? 'pending'));
    if (!canTransitionStatus('leave_requests', $oldStatus, $decision)) {
        redirectWithState('error', 'Invalid leave request transition from ' . $oldStatus . ' to ' . $decision . '.');
    }

    $notifyRequester(
        $recipientUserId,
        'Leave Recommendation Submitted',
        'A staff recommendation (' . str_replace('_', ' ', $decision) . ') was submitted for your leave request. Final approval will be done by admin.'
    );

    $notifyAdmins(
        'Leave Recommendation Pending Approval',
        'A staff recommendation was submitted for a leave request: ' . str_replace('_', ' ', $decision) . '. Please review for final decision.'
    );

    $writeActivityLog(
        'leave_requests',
        $requestId,
        'recommend_leave_request',
        ['status' => $oldStatus],
        [
            'status' => $oldStatus,
            'recommended_status' => $decision,
            'notes' => $notes,
            'submitted_for_admin_approval' => true,
        ]
    );

    redirectWithState('success', 'Leave recommendation submitted to admin for final approval.');
}

if ($action === 'review_overtime_request') {
    redirectWithState('error', 'Legacy CTO overtime recommendation is disabled. Review CTO through Leave/CTO Requests only.');
}

if ($action === 'review_ob_request') {
    $requestId = cleanText($_POST['request_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $notes = cleanText($_POST['notes'] ?? null);

    if (!isValidUuid($requestId)) {
        redirectWithState('error', 'Invalid official business request selected.');
    }

    if (!in_array($decision, ['approved', 'rejected', 'cancelled'], true)) {
        redirectWithState('error', 'Invalid official business decision selected.');
    }

    $requestResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/overtime_requests?select=id,person_id,status,reason,person:people(user_id)&id=eq.' . rawurlencode($requestId) . '&limit=1',
        $headers
    );

    $requestRow = isSuccessful($requestResponse) ? ($requestResponse['data'][0] ?? null) : null;
    if (!is_array($requestRow)) {
        redirectWithState('error', 'Official business request not found.');
    }

    $reasonRaw = trim((string)($requestRow['reason'] ?? ''));
    if (preg_match('/^\[OB\]\s*/i', $reasonRaw) !== 1) {
        redirectWithState('error', 'Only official business requests can use OB review.');
    }

    $personId = cleanText($requestRow['person_id'] ?? null) ?? '';
    $recipientUserId = cleanText($requestRow['person']['user_id'] ?? null) ?? '';

    if (!$isPersonInScope($personId)) {
        redirectWithState('error', 'Official business request target is invalid or no longer active.');
    }

    $oldStatus = strtolower((string)(cleanText($requestRow['status'] ?? null) ?? 'pending'));
    if (!canTransitionStatus('overtime_requests', $oldStatus, $decision)) {
        redirectWithState('error', 'Invalid official business transition from ' . $oldStatus . ' to ' . $decision . '.');
    }

    $notifyRequester(
        $recipientUserId,
        'Official Business Recommendation Submitted',
        'A staff recommendation (' . str_replace('_', ' ', $decision) . ') was submitted for your official business request. Final approval will be done by admin.'
    );

    $notifyAdmins(
        'Official Business Recommendation Pending Approval',
        'A staff recommendation was submitted for an official business request: ' . str_replace('_', ' ', $decision) . '. Please review for final decision.'
    );

    $writeActivityLog(
        'overtime_requests',
        $requestId,
        'recommend_ob_request',
        ['status' => $oldStatus],
        [
            'status' => $oldStatus,
            'recommended_status' => $decision,
            'notes' => $notes,
            'submitted_for_admin_approval' => true,
            'request_type' => 'official_business',
        ]
    );

    redirectWithState('success', 'Official business recommendation submitted to admin for final approval.');
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
        redirectWithState('error', 'Adjustment request target is invalid or no longer active.');
    }

    $oldStatus = strtolower((string)(cleanText($requestRow['status'] ?? null) ?? 'pending'));
    if (!canTransitionStatus('time_adjustment_requests', $oldStatus, $decision)) {
        redirectWithState('error', 'Invalid adjustment transition from ' . $oldStatus . ' to ' . $decision . '.');
    }

    $notifyRequester(
        $recipientUserId,
        'Time Adjustment Recommendation Submitted',
        'A staff recommendation (' . str_replace('_', ' ', $decision) . ') was submitted for your time adjustment request. Final approval will be done by admin.'
    );

    $notifyAdmins(
        'Time Adjustment Recommendation Pending Approval',
        'A staff recommendation was submitted for a time adjustment request: ' . str_replace('_', ' ', $decision) . '. Please review for final decision.'
    );

    $writeActivityLog(
        'time_adjustment_requests',
        $requestId,
        'recommend_time_adjustment',
        ['status' => $oldStatus],
        [
            'status' => $oldStatus,
            'recommended_status' => $decision,
            'notes' => $notes,
            'submitted_for_admin_approval' => true,
        ]
    );

    redirectWithState('success', 'Time adjustment recommendation submitted to admin for final approval.');
}

redirectWithState('error', 'Unknown timekeeping action.');
