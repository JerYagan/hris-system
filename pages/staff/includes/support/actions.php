<?php

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    return;
}

$redirectSupport = static function (string $state, string $message, array $extra = []): never {
    $params = array_merge(['state' => $state, 'message' => $message], $extra);
    header('Location: support.php?' . http_build_query($params));
    exit;
};

requireStaffPostWithCsrf($_POST['csrf_token'] ?? null);

$action = strtolower((string)cleanText($_POST['action'] ?? null));
if ($action !== 'update_forwarded_ticket') {
    $redirectSupport('error', 'Unsupported support action.', []);
}

$ticketId = (string)(cleanText($_POST['ticket_id'] ?? null) ?? '');
$requesterUserId = (string)(cleanText($_POST['requester_user_id'] ?? null) ?? '');
$requesterRole = strtolower((string)(cleanText($_POST['requester_role'] ?? null) ?? ''));
$staffUpdateType = strtolower((string)(cleanText($_POST['staff_update_type'] ?? null) ?? 'progress_update'));
$staffNotes = trim((string)(cleanText($_POST['staff_notes'] ?? null) ?? ''));
$notifyRequester = isset($_POST['notify_requester']) && (string)$_POST['notify_requester'] === '1';

$allowedRequesterRoles = ['employee', 'applicant'];
$allowedUpdateTypes = ['progress_update', 'needs_more_info', 'recommend_resolution', 'recommend_rejection', 'escalated_to_admin'];

if (!isValidUuid($ticketId) || !isValidUuid($requesterUserId) || !in_array($requesterRole, $allowedRequesterRoles, true) || !in_array($staffUpdateType, $allowedUpdateTypes, true)) {
    $redirectSupport('error', 'Invalid support ticket payload.', []);
}

if (mb_strlen($staffNotes) < 5) {
    $redirectSupport('error', 'Please provide a staff update note with at least 5 characters.', ['ticket_id' => $ticketId]);
}

$ticketHistoryResponse = apiRequest(
    'GET',
    rtrim($supabaseUrl, '/')
    . '/rest/v1/activity_logs?select=id,entity_id,action_name,new_data,created_at,module_name,entity_name'
    . '&module_name=eq.support&entity_name=eq.tickets'
    . '&entity_id=eq.' . rawurlencode($ticketId)
    . '&order=created_at.asc&limit=500',
    $headers
);

if (!isSuccessful($ticketHistoryResponse)) {
    $redirectSupport('error', 'Unable to validate ticket assignment right now.', ['ticket_id' => $ticketId]);
}

$resolvedStatus = 'submitted';
$resolvedForwardTo = '';
$resolvedRequesterUserId = '';
$resolvedRequesterRole = '';

foreach ((array)($ticketHistoryResponse['data'] ?? []) as $logRowRaw) {
    $logRow = (array)$logRowRaw;
    $payload = (array)($logRow['new_data'] ?? []);
    $actionName = strtolower((string)($logRow['action_name'] ?? ''));

    if ($actionName === 'submit_ticket') {
        $resolvedRequesterUserId = (string)($payload['requester_user_id'] ?? $resolvedRequesterUserId);
        $resolvedRequesterRole = strtolower((string)($payload['requester_role'] ?? $resolvedRequesterRole));
        $resolvedStatus = (string)($payload['status'] ?? $resolvedStatus);
        continue;
    }

    if ($actionName === 'admin_ticket_update') {
        $resolvedStatus = (string)($payload['status'] ?? $resolvedStatus);
        $resolvedForwardTo = (string)($payload['forward_to_user_id'] ?? $resolvedForwardTo);
    }
}

if ($resolvedRequesterUserId !== '' && $resolvedRequesterUserId !== $requesterUserId) {
    $redirectSupport('error', 'Requester context mismatch for this support ticket.', ['ticket_id' => $ticketId]);
}

if ($resolvedRequesterRole !== '' && $resolvedRequesterRole !== $requesterRole) {
    $redirectSupport('error', 'Requester role mismatch for this support ticket.', ['ticket_id' => $ticketId]);
}

if ($resolvedForwardTo !== $staffUserId || strtolower($resolvedStatus) !== 'forwarded_to_staff') {
    $redirectSupport('error', 'This ticket is not currently forwarded to your account.', ['ticket_id' => $ticketId]);
}

$logResponse = apiRequest(
    'POST',
    rtrim($supabaseUrl, '/') . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $staffUserId,
        'module_name' => 'support',
        'entity_name' => 'tickets',
        'entity_id' => $ticketId,
        'action_name' => 'staff_ticket_update',
        'old_data' => null,
        'new_data' => [
            'ticket_id' => $ticketId,
            'requester_user_id' => $requesterUserId,
            'requester_role' => $requesterRole,
            'staff_user_id' => $staffUserId,
            'staff_update_type' => $staffUpdateType,
            'staff_notes' => $staffNotes,
            'status' => 'forwarded_to_staff',
        ],
        'ip_address' => cleanText($_SERVER['REMOTE_ADDR'] ?? null),
    ]]
);

if (!isSuccessful($logResponse)) {
    $redirectSupport('error', 'Failed to save staff update for this support ticket.', ['ticket_id' => $ticketId]);
}

$adminRoleIds = [];
$rolesResponse = apiRequest(
    'GET',
    rtrim($supabaseUrl, '/') . '/rest/v1/roles?select=id,role_key&role_key=in.(admin)&limit=20',
    $headers
);

if (isSuccessful($rolesResponse)) {
    foreach ((array)($rolesResponse['data'] ?? []) as $roleRow) {
        $roleId = cleanText($roleRow['id'] ?? null);
        if ($roleId !== null) {
            $adminRoleIds[] = $roleId;
        }
    }
}

$adminUserIds = [];
if (!empty($adminRoleIds)) {
    $assignmentsResponse = apiRequest(
        'GET',
        rtrim($supabaseUrl, '/')
        . '/rest/v1/user_role_assignments?select=user_id&role_id=in.' . rawurlencode('(' . implode(',', $adminRoleIds) . ')')
        . '&expires_at=is.null&limit=300',
        $headers
    );

    if (isSuccessful($assignmentsResponse)) {
        foreach ((array)($assignmentsResponse['data'] ?? []) as $assignmentRow) {
            $userId = cleanText($assignmentRow['user_id'] ?? null);
            if ($userId !== null) {
                $adminUserIds[$userId] = true;
            }
        }
    }
}

if (!empty($adminUserIds)) {
    $adminNotificationRows = [];
    foreach (array_keys($adminUserIds) as $adminUserId) {
        $adminNotificationRows[] = [
            'recipient_user_id' => $adminUserId,
            'category' => 'support',
            'title' => 'Staff updated a forwarded support ticket',
            'body' => 'Ticket ID: ' . $ticketId . ' • ' . ucwords(str_replace('_', ' ', $staffUpdateType)) . ' • ' . mb_substr($staffNotes, 0, 160),
            'link_url' => '/hris-system/pages/admin/support.php?ticket_id=' . rawurlencode($ticketId),
            'is_read' => false,
        ];
    }

    apiRequest(
        'POST',
        rtrim($supabaseUrl, '/') . '/rest/v1/notifications',
        array_merge($headers, ['Prefer: return=minimal']),
        $adminNotificationRows
    );
}

if ($notifyRequester) {
    $requesterLink = $requesterRole === 'employee'
        ? '/hris-system/pages/employee/support.php'
        : '/hris-system/pages/applicant/support.php?tab=contact';

    apiRequest(
        'POST',
        rtrim($supabaseUrl, '/') . '/rest/v1/notifications',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'recipient_user_id' => $requesterUserId,
            'category' => 'support',
            'title' => 'Support ticket update from staff',
            'body' => 'Ticket ID: ' . $ticketId . ' • ' . mb_substr($staffNotes, 0, 180),
            'link_url' => $requesterLink,
            'is_read' => false,
        ]]
    );
}

$redirectSupport('success', 'Staff support update sent successfully.', ['ticket_id' => $ticketId]);
