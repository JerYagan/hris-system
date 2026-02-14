<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = (string)($_POST['form_action'] ?? '');

if ($action === 'review_document') {
    $documentId = cleanText($_POST['document_id'] ?? null) ?? '';
    $reviewStatus = strtolower((string)(cleanText($_POST['review_status'] ?? null) ?? ''));
    $reviewNotes = cleanText($_POST['review_notes'] ?? null);

    if ($documentId === '' || $reviewStatus === '') {
        redirectWithState('error', 'Document and review status are required.');
    }

    if (!in_array($reviewStatus, ['approved', 'rejected', 'needs_revision'], true)) {
        redirectWithState('error', 'Invalid review status selected.');
    }

    $documentResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/documents?select=id,title,document_status,uploaded_by&id=eq.' . $documentId . '&limit=1',
        $headers
    );

    $documentRow = $documentResponse['data'][0] ?? null;
    if (!is_array($documentRow)) {
        redirectWithState('error', 'Document record not found.');
    }

    $currentStatus = strtolower((string)($documentRow['document_status'] ?? 'draft'));
    if ($currentStatus === 'archived') {
        redirectWithState('error', 'Archived documents cannot be reviewed.');
    }

    $mappedStatus = match ($reviewStatus) {
        'approved' => 'approved',
        'rejected' => 'rejected',
        default => 'draft',
    };

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/documents?id=eq.' . $documentId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'document_status' => $mappedStatus,
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update document status.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/document_reviews',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'document_id' => $documentId,
            'reviewer_user_id' => $adminUserId,
            'review_status' => $reviewStatus,
            'review_notes' => $reviewNotes,
            'reviewed_at' => gmdate('c'),
        ]]
    );

    $uploadedBy = (string)($documentRow['uploaded_by'] ?? '');
    if ($uploadedBy !== '') {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $uploadedBy,
                'category' => 'documents',
                'title' => 'Document Review Updated',
                'body' => 'Your document "' . ((string)($documentRow['title'] ?? 'Document')) . '" was marked as ' . str_replace('_', ' ', $reviewStatus) . '.',
                'link_url' => '/hris-system/pages/employee/document-management.php',
            ]]
        );
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'document_management',
            'entity_name' => 'documents',
            'entity_id' => $documentId,
            'action_name' => 'review_document',
            'old_data' => ['document_status' => $currentStatus],
            'new_data' => [
                'document_status' => $mappedStatus,
                'review_status' => $reviewStatus,
                'review_notes' => $reviewNotes,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Document review saved successfully.');
}

if ($action === 'archive_document') {
    $documentId = cleanText($_POST['document_id'] ?? null) ?? '';
    $archiveReason = cleanText($_POST['archive_reason'] ?? null);

    if ($documentId === '') {
        redirectWithState('error', 'Document is required for archiving.');
    }

    $documentResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/documents?select=id,title,document_status,uploaded_by&id=eq.' . $documentId . '&limit=1',
        $headers
    );

    $documentRow = $documentResponse['data'][0] ?? null;
    if (!is_array($documentRow)) {
        redirectWithState('error', 'Document record not found.');
    }

    $currentStatus = strtolower((string)($documentRow['document_status'] ?? 'draft'));
    if ($currentStatus === 'archived') {
        redirectWithState('success', 'Document is already archived.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/documents?id=eq.' . $documentId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'document_status' => 'archived',
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to archive document.');
    }

    $uploadedBy = (string)($documentRow['uploaded_by'] ?? '');
    if ($uploadedBy !== '') {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $uploadedBy,
                'category' => 'documents',
                'title' => 'Document Archived',
                'body' => 'Your document "' . ((string)($documentRow['title'] ?? 'Document')) . '" has been archived.',
                'link_url' => '/hris-system/pages/employee/document-management.php',
            ]]
        );
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'document_management',
            'entity_name' => 'documents',
            'entity_id' => $documentId,
            'action_name' => 'archive_document',
            'old_data' => ['document_status' => $currentStatus],
            'new_data' => [
                'document_status' => 'archived',
                'reason' => $archiveReason,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Document archived successfully.');
}

redirectWithState('error', 'Unknown document management action.');
