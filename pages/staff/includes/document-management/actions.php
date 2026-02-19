<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if ($action !== 'review_document') {
    redirectWithState('error', 'Unknown document action request.');
}

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh the page and try again.');
}

$documentId = cleanText($_POST['document_id'] ?? null) ?? '';
$reviewStatus = strtolower((string)(cleanText($_POST['review_status'] ?? null) ?? ''));
$reviewNotes = cleanText($_POST['review_notes'] ?? null);

if (!isValidUuid($documentId)) {
    redirectWithState('error', 'Invalid document identifier.');
}

if (!in_array($reviewStatus, ['approved', 'rejected', 'needs_revision'], true)) {
    redirectWithState('error', 'Invalid review status selected.');
}

$documentResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/documents?select=id,title,document_status,owner_person_id,uploaded_by&id=eq.' . rawurlencode($documentId) . '&limit=1',
    $headers
);

$documentRow = isSuccessful($documentResponse) ? ($documentResponse['data'][0] ?? null) : null;
if (!is_array($documentRow)) {
    redirectWithState('error', 'Document record not found.');
}

$documentTitle = cleanText($documentRow['title'] ?? null) ?? 'Document';
$ownerPersonId = cleanText($documentRow['owner_person_id'] ?? null) ?? '';
$currentStatus = strtolower((string)(cleanText($documentRow['document_status'] ?? null) ?? 'draft'));
$uploaderUserId = cleanText($documentRow['uploaded_by'] ?? null) ?? '';

$ownerUserId = '';
if (isValidUuid($ownerPersonId)) {
    $ownerResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=user_id&id=eq.' . rawurlencode($ownerPersonId) . '&limit=1',
        $headers
    );
    $ownerRow = isSuccessful($ownerResponse) ? ($ownerResponse['data'][0] ?? null) : null;
    $ownerUserId = is_array($ownerRow) ? (cleanText($ownerRow['user_id'] ?? null) ?? '') : '';
}

if ($currentStatus === 'archived') {
    redirectWithState('error', 'Archived documents can no longer be reviewed.');
}

if (!canTransitionStatus('documents', $currentStatus, $reviewStatus)) {
    redirectWithState('error', 'Invalid document transition from ' . $currentStatus . ' to ' . $reviewStatus . '.');
}

$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
if (!$isAdminScope) {
    if (!isValidUuid($ownerPersonId) || !isValidUuid((string)$staffOfficeId)) {
        redirectWithState('error', 'Document scope validation failed for your office context.');
    }

    $scopeResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=id'
        . '&person_id=eq.' . rawurlencode($ownerPersonId)
        . '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
        . '&is_current=eq.true&limit=1',
        $headers
    );

    $scopeRow = isSuccessful($scopeResponse) ? ($scopeResponse['data'][0] ?? null) : null;
    if (!is_array($scopeRow)) {
        redirectWithState('error', 'You are not allowed to review documents outside your office scope.');
    }
}

$mappedDocumentStatus = match ($reviewStatus) {
    'approved' => 'approved',
    'rejected' => 'rejected',
    default => 'draft',
};

$updateResponse = apiRequest(
    'PATCH',
    $supabaseUrl . '/rest/v1/documents?id=eq.' . rawurlencode($documentId),
    array_merge($headers, ['Prefer: return=minimal']),
    [
        'document_status' => $mappedDocumentStatus,
        'updated_at' => gmdate('c'),
    ]
);

if (!isSuccessful($updateResponse)) {
    redirectWithState('error', 'Failed to update document status.');
}

$reviewInsertResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/document_reviews',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'document_id' => $documentId,
        'reviewer_user_id' => $staffUserId,
        'review_status' => $reviewStatus,
        'review_notes' => $reviewNotes,
        'reviewed_at' => gmdate('c'),
    ]]
);

if (!isSuccessful($reviewInsertResponse)) {
    redirectWithState('error', 'Document status updated, but review log insert failed.');
}

if (isValidUuid($uploaderUserId)) {
    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/notifications',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'recipient_user_id' => $uploaderUserId,
            'category' => 'documents',
            'title' => 'Document Review Updated',
            'body' => 'Your document "' . $documentTitle . '" was marked as ' . str_replace('_', ' ', $reviewStatus) . '.',
            'link_url' => '/hris-system/pages/employee/document-management.php',
        ]]
    );
}

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $staffUserId,
        'module_name' => 'document_management',
        'entity_name' => 'documents',
        'entity_id' => $documentId,
        'action_name' => 'review_document',
        'old_data' => ['document_status' => $currentStatus],
        'new_data' => [
            'document_status' => $mappedDocumentStatus,
            'review_status' => $reviewStatus,
            'review_notes' => $reviewNotes,
        ],
        'ip_address' => clientIp(),
    ]]
);

redirectWithState('success', 'Document "' . $documentTitle . '" has been marked as ' . str_replace('_', ' ', $reviewStatus) . '.');