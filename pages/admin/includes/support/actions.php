<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

$redirectSupport = static function (string $state, string $message, array $extra = []): never {
    $params = array_merge(['state' => $state, 'message' => $message], $extra);
    header('Location: support.php?' . http_build_query($params));
    exit;
};

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    $redirectSupport('error', 'Invalid request token. Please refresh and try again.', []);
}

$action = strtolower((string)cleanText($_POST['action'] ?? ''));
if ($action !== 'update_support_ticket') {
    $redirectSupport('error', 'Unsupported support action.', []);
}

$ticketId = (string)(cleanText($_POST['ticket_id'] ?? null) ?? '');
$requesterUserId = (string)(cleanText($_POST['requester_user_id'] ?? null) ?? '');
$requesterRole = strtolower((string)(cleanText($_POST['requester_role'] ?? null) ?? ''));
$newStatus = strtolower((string)(cleanText($_POST['ticket_status'] ?? null) ?? ''));
$adminNotes = (string)(cleanText($_POST['admin_notes'] ?? null) ?? '');
$resolutionNotes = (string)(cleanText($_POST['resolution_notes'] ?? null) ?? '');
$forwardToUserId = (string)(cleanText($_POST['forward_to_user_id'] ?? null) ?? '');

$allowedStatuses = ['submitted', 'in_review', 'forwarded_to_staff', 'resolved', 'rejected'];
if (!isValidUuid($ticketId) || !isValidUuid($requesterUserId) || !in_array($requesterRole, ['employee', 'applicant'], true) || !in_array($newStatus, $allowedStatuses, true)) {
    $redirectSupport('error', 'Invalid support ticket payload.', []);
}

if ($newStatus === 'forwarded_to_staff' && !isValidUuid($forwardToUserId)) {
    $redirectSupport('error', 'Select a valid staff user when forwarding a ticket.', ['ticket_id' => $ticketId]);
}

if (in_array($newStatus, ['resolved', 'rejected'], true) && trim($resolutionNotes) === '') {
    $redirectSupport('error', 'Resolution notes are required when resolving or rejecting a ticket.', ['ticket_id' => $ticketId]);
}

$logResponse = apiRequest(
    'POST',
    rtrim($supabaseUrl, '/') . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
        'module_name' => 'support',
        'entity_name' => 'tickets',
        'entity_id' => $ticketId,
        'action_name' => 'admin_ticket_update',
        'old_data' => null,
        'new_data' => [
            'ticket_id' => $ticketId,
            'requester_user_id' => $requesterUserId,
            'requester_role' => $requesterRole,
            'status' => $newStatus,
            'admin_notes' => $adminNotes,
            'resolution_notes' => $resolutionNotes,
            'forward_to_user_id' => $forwardToUserId !== '' ? $forwardToUserId : null,
        ],
        'ip_address' => cleanText($_SERVER['REMOTE_ADDR'] ?? null),
    ]]
);

if (!isSuccessful($logResponse)) {
    $redirectSupport('error', 'Failed to update support ticket.', ['ticket_id' => $ticketId]);
}

$requesterLink = $requesterRole === 'employee'
    ? '/hris-system/pages/employee/support.php'
    : '/hris-system/pages/applicant/support.php?tab=contact';

$requesterTitle = match ($newStatus) {
    'resolved' => 'Your support ticket was resolved',
    'rejected' => 'Your support ticket was rejected',
    'forwarded_to_staff' => 'Your support ticket was forwarded to staff',
    'in_review' => 'Your support ticket is under review',
    default => 'Your support ticket was updated',
};

$requesterBodyParts = ['Ticket ID: ' . $ticketId, 'Status: ' . strtoupper(str_replace('_', ' ', $newStatus))];
if ($resolutionNotes !== '') {
    $requesterBodyParts[] = 'Resolution: ' . mb_substr($resolutionNotes, 0, 180);
} elseif ($adminNotes !== '') {
    $requesterBodyParts[] = 'Admin note: ' . mb_substr($adminNotes, 0, 180);
}

apiRequest(
    'POST',
    rtrim($supabaseUrl, '/') . '/rest/v1/notifications',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'recipient_user_id' => $requesterUserId,
        'category' => 'support',
        'title' => $requesterTitle,
        'body' => implode(' • ', $requesterBodyParts),
        'link_url' => $requesterLink,
        'is_read' => false,
    ]]
);

if ($newStatus === 'forwarded_to_staff' && $forwardToUserId !== '') {
    apiRequest(
        'POST',
        rtrim($supabaseUrl, '/') . '/rest/v1/notifications',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'recipient_user_id' => $forwardToUserId,
            'category' => 'support',
            'title' => 'Support ticket forwarded to you',
            'body' => 'Ticket ID: ' . $ticketId . ' requires staff action.',
            'link_url' => '/hris-system/pages/staff/support.php?ticket_id=' . rawurlencode($ticketId),
            'is_read' => false,
        ]]
    );
}

$redirectSupport('success', 'Support ticket updated successfully.', ['ticket_id' => $ticketId]);
