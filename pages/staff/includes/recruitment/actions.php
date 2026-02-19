<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

if ($action !== 'update_posting_status') {
    redirectWithState('error', 'Unknown recruitment action.');
}

$postingId = cleanText($_POST['posting_id'] ?? null) ?? '';
$newStatus = strtolower((string)(cleanText($_POST['new_status'] ?? null) ?? ''));
$statusNotes = cleanText($_POST['status_notes'] ?? null);

if (!isValidUuid($postingId)) {
    redirectWithState('error', 'Invalid job posting selected.');
}

if (!in_array($newStatus, ['draft', 'published', 'closed', 'archived'], true)) {
    redirectWithState('error', 'Invalid posting status selected.');
}

$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
$scopeFilter = (!$isAdminScope && isValidUuid((string)$staffOfficeId))
    ? '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
    : '';

$postingResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/job_postings?select=id,title,posting_status,office_id&id=eq.' . rawurlencode($postingId) . $scopeFilter . '&limit=1',
    $headers
);

$postingRow = isSuccessful($postingResponse) ? ($postingResponse['data'][0] ?? null) : null;
if (!is_array($postingRow)) {
    redirectWithState('error', 'Posting not found or outside your office scope.');
}

$oldStatus = strtolower((string)(cleanText($postingRow['posting_status'] ?? null) ?? 'draft'));

$canTransitionPosting = static function (string $old, string $new): bool {
    if ($old === $new) {
        return true;
    }

    $rules = [
        'draft' => ['published', 'archived'],
        'published' => ['closed', 'archived'],
        'closed' => ['archived'],
    ];

    return isset($rules[$old]) && in_array($new, $rules[$old], true);
};

if (!$canTransitionPosting($oldStatus, $newStatus)) {
    redirectWithState('error', 'Invalid posting transition from ' . $oldStatus . ' to ' . $newStatus . '.');
}

$patchResponse = apiRequest(
    'PATCH',
    $supabaseUrl . '/rest/v1/job_postings?id=eq.' . rawurlencode($postingId),
    array_merge($headers, ['Prefer: return=minimal']),
    [
        'posting_status' => $newStatus,
        'updated_at' => gmdate('c'),
    ]
);

if (!isSuccessful($patchResponse)) {
    redirectWithState('error', 'Failed to update posting status.');
}

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $staffUserId,
        'module_name' => 'recruitment',
        'entity_name' => 'job_postings',
        'entity_id' => $postingId,
        'action_name' => 'update_posting_status',
        'old_data' => ['posting_status' => $oldStatus],
        'new_data' => ['posting_status' => $newStatus, 'notes' => $statusNotes],
        'ip_address' => clientIp(),
    ]]
);

redirectWithState('success', 'Job posting status updated to ' . $newStatus . '.');