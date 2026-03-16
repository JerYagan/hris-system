<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if (!in_array($action, ['review_document', 'archive_document', 'restore_document', 'create_document_category'], true)) {
    redirectWithState('error', 'Unknown document action request.');
}

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh the page and try again.');
}

$reviewStatus = strtolower((string)(cleanText($_POST['review_status'] ?? null) ?? ''));
$reviewNotes = cleanText($_POST['review_notes'] ?? null);
$archiveReason = cleanText($_POST['archive_reason'] ?? null);
$restoreReason = cleanText($_POST['restore_reason'] ?? null);

if ($action === 'create_document_category') {
    $categoryName = trim((string)(cleanText($_POST['category_name'] ?? null) ?? ''));
    if ($categoryName === '') {
        redirectWithState('error', 'Category name is required.');
    }

    if (mb_strlen($categoryName) > 80) {
        redirectWithState('error', 'Category name must be 80 characters or less.');
    }

    if (strtolower($categoryName) === 'haugafia') {
        redirectWithState('error', 'That category label is not allowed.');
    }

    if (preg_match('/^[A-Za-z0-9][A-Za-z0-9()\/,&\-\s]{1,79}$/', $categoryName) !== 1) {
        redirectWithState('error', 'Use letters, numbers, spaces, and basic punctuation only for category names.');
    }

    $categoryKey = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '_', $categoryName), '_'));
    if ($categoryKey === '') {
        redirectWithState('error', 'Unable to generate a valid category key.');
    }

    $existingResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/document_categories?select=id,category_name&category_key=eq.' . rawurlencode($categoryKey) . '&limit=1',
        $headers
    );

    if (isSuccessful($existingResponse) && !empty((array)($existingResponse['data'] ?? []))) {
        redirectWithState('success', 'Document category already exists.');
    }

    $createResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/document_categories',
        array_merge($headers, ['Prefer: return=representation']),
        [[
            'category_key' => $categoryKey,
            'category_name' => $categoryName,
            'requires_approval' => true,
        ]]
    );

    if (!isSuccessful($createResponse)) {
        redirectWithState('error', 'Failed to create document category.');
    }

    $createdCategoryId = trim((string)($createResponse['data'][0]['id'] ?? ''));
    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'document_management',
            'entity_name' => 'document_categories',
            'entity_id' => $createdCategoryId !== '' ? $createdCategoryId : null,
            'action_name' => 'create_document_category',
            'old_data' => null,
            'new_data' => [
                'category_name' => $categoryName,
                'category_key' => $categoryKey,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Document category created successfully.');
}

$documentId = cleanText($_POST['document_id'] ?? null) ?? '';
if (!isValidUuid($documentId)) {
    redirectWithState('error', 'Invalid document identifier.');
}

if ($action === 'review_document' && !in_array($reviewStatus, ['approved', 'rejected'], true)) {
    redirectWithState('error', 'Invalid review status selected.');
}

if ($action === 'review_document' && trim((string)$reviewNotes) === '') {
    redirectWithState('error', 'Recommendation notes are required for status-changing actions.');
}

if ($action === 'archive_document' && trim((string)$archiveReason) === '') {
    redirectWithState('error', 'Archive reason is required for status-changing actions.');
}

if ($action === 'restore_document' && trim((string)$restoreReason) === '') {
    redirectWithState('error', 'Restore reason is required for status-changing actions.');
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
    if ($action === 'review_document') {
        redirectWithState('error', 'Archived documents can no longer be reviewed.');
    }
}

if ($action === 'review_document' && in_array($currentStatus, ['approved', 'rejected'], true)) {
    redirectWithState('error', 'Finalized documents can no longer receive staff recommendations.');
}

if ($action === 'review_document') {
    $latestStaffRecommendationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/document_reviews?select=id,review_status,created_at'
        . '&document_id=eq.' . rawurlencode($documentId)
        . '&reviewer_user_id=eq.' . rawurlencode($staffUserId)
        . '&review_status=in.(approved,rejected)'
        . '&order=created_at.desc'
        . '&limit=1',
        $headers
    );

    $latestStaffRecommendation = isSuccessful($latestStaffRecommendationResponse)
        ? ($latestStaffRecommendationResponse['data'][0] ?? null)
        : null;

    if (is_array($latestStaffRecommendation)) {
        $latestStaffRecommendationAt = (string)(cleanText($latestStaffRecommendation['created_at'] ?? null) ?? '');

        $returnedByAdminResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/document_reviews?select=id,created_at'
            . '&document_id=eq.' . rawurlencode($documentId)
            . '&review_status=eq.needs_revision'
            . '&order=created_at.desc'
            . '&limit=1',
            $headers
        );

        $returnedByAdmin = isSuccessful($returnedByAdminResponse)
            ? ($returnedByAdminResponse['data'][0] ?? null)
            : null;

        $returnedAt = is_array($returnedByAdmin)
            ? (string)(cleanText($returnedByAdmin['created_at'] ?? null) ?? '')
            : '';

        $latestStaffTs = strtotime($latestStaffRecommendationAt) ?: 0;
        $returnedTs = strtotime($returnedAt) ?: 0;

        if ($returnedTs <= $latestStaffTs) {
            redirectWithState('error', 'Recommendation is locked after submit-to-admin and can only be updated when returned by admin.');
        }
    }
}

if ($action === 'archive_document') {
    if ($currentStatus === 'archived') {
        redirectWithState('success', 'Document is already archived.');
    }

    $archiveResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/documents?id=eq.' . rawurlencode($documentId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'document_status' => 'archived',
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($archiveResponse)) {
        redirectWithState('error', 'Failed to archive document.');
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
            'action_name' => 'archive_document',
            'old_data' => ['document_status' => $currentStatus],
            'new_data' => ['document_status' => 'archived', 'archive_reason' => $archiveReason],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Document "' . $documentTitle . '" has been archived.');
}

if ($action === 'restore_document') {
    if ($currentStatus !== 'archived') {
        redirectWithState('error', 'Only archived documents can be restored.');
    }

    $restoreResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/documents?id=eq.' . rawurlencode($documentId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'document_status' => 'submitted',
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($restoreResponse)) {
        redirectWithState('error', 'Failed to restore document.');
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
            'action_name' => 'restore_document',
            'old_data' => ['document_status' => $currentStatus],
            'new_data' => ['document_status' => 'submitted'],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Document "' . $documentTitle . '" has been restored to submitted status.');
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
    redirectWithState('error', 'Failed to save recommendation review log.');
}

if (isValidUuid($uploaderUserId)) {
    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/notifications',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'recipient_user_id' => $uploaderUserId,
            'category' => 'documents',
            'title' => 'Document Recommendation Submitted',
            'body' => 'A staff recommendation was submitted for "' . $documentTitle . '". Final decision will come from admin.',
            'link_url' => '/hris-system/pages/employee/document-management.php',
        ]]
    );
}

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
            'category' => 'documents',
            'title' => 'Staff Document Recommendation',
            'body' => 'Recommendation for "' . $documentTitle . '": ' . str_replace('_', ' ', $reviewStatus) . '. Review and finalize the document decision.',
            'link_url' => '/hris-system/pages/admin/document-management.php',
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
        'action_name' => 'recommend_document',
        'old_data' => ['document_status' => $currentStatus],
        'new_data' => [
            'document_status' => $currentStatus,
            'review_status' => $reviewStatus,
            'review_notes' => $reviewNotes,
            'recommendation_only' => true,
        ],
        'ip_address' => clientIp(),
    ]]
);

redirectWithState('success', 'Recommendation for "' . $documentTitle . '" was sent to admin for final decision.');