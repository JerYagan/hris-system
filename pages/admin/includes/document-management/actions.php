<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = (string)($_POST['form_action'] ?? '');

if ($action === 'upload_document_file') {
    $ownerPersonId = cleanText($_POST['owner_person_id'] ?? null) ?? '';
    $categoryId = cleanText($_POST['category_id'] ?? null) ?? '';
    $title = cleanText($_POST['title'] ?? null) ?? '';
    $description = cleanText($_POST['description'] ?? null);
    $upload = $_FILES['document_file'] ?? null;

    if ($ownerPersonId === '' || $categoryId === '' || $title === '') {
        redirectWithState('error', 'Owner, category, and title are required.');
    }

    if (!preg_match('/^[0-9a-fA-F-]{36}$/', $ownerPersonId) || !preg_match('/^[0-9a-fA-F-]{36}$/', $categoryId)) {
        redirectWithState('error', 'Invalid owner or category identifier.');
    }

    if (!is_array($upload) || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        redirectWithState('error', 'Please select a valid file to upload.');
    }

    $tmpPath = (string)($upload['tmp_name'] ?? '');
    $originalName = (string)($upload['name'] ?? 'document');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        redirectWithState('error', 'Uploaded file was not found.');
    }

    $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'json', 'xml', 'md'];
    if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
        redirectWithState('error', 'File type is not supported.');
    }

    $sizeBytes = (int)($upload['size'] ?? 0);
    if ($sizeBytes <= 0) {
        redirectWithState('error', 'Uploaded file is empty.');
    }

    $ownerResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id&id=eq.' . $ownerPersonId . '&limit=1',
        $headers
    );
    $ownerRow = $ownerResponse['data'][0] ?? null;
    if (!is_array($ownerRow)) {
        redirectWithState('error', 'Selected owner does not exist.');
    }

    $categoryResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/document_categories?select=id&id=eq.' . $categoryId . '&limit=1',
        $headers
    );
    $categoryRow = $categoryResponse['data'][0] ?? null;
    if (!is_array($categoryRow)) {
        redirectWithState('error', 'Selected category does not exist.');
    }

    $safeBaseName = preg_replace('/[^a-zA-Z0-9\-_]+/', '-', (string)pathinfo($originalName, PATHINFO_FILENAME));
    $safeBaseName = trim((string)$safeBaseName, '-');
    if ($safeBaseName === '') {
        $safeBaseName = 'document';
    }

    $storedFileName = gmdate('YmdHis') . '-' . substr(bin2hex(random_bytes(6)), 0, 12) . '-' . $safeBaseName . '.' . $extension;
    $storageRoot = dirname(__DIR__, 4) . '/storage/document';
    if (!is_dir($storageRoot) && !mkdir($storageRoot, 0775, true) && !is_dir($storageRoot)) {
        redirectWithState('error', 'Unable to prepare local document storage directory.');
    }

    $targetPath = $storageRoot . '/' . $storedFileName;
    if (!move_uploaded_file($tmpPath, $targetPath)) {
        redirectWithState('error', 'Failed to save uploaded file to local storage.');
    }

    $mimeType = (string)(mime_content_type($targetPath) ?: 'application/octet-stream');
    $checksum = (string)(hash_file('sha256', $targetPath) ?: '');

    $documentInsertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/documents',
        array_merge($headers, ['Prefer: return=representation']),
        [[
            'owner_person_id' => $ownerPersonId,
            'category_id' => $categoryId,
            'title' => $title,
            'description' => $description,
            'storage_bucket' => 'local_documents',
            'storage_path' => $storedFileName,
            'current_version_no' => 1,
            'document_status' => 'submitted',
            'uploaded_by' => $adminUserId,
        ]]
    );

    if (!isSuccessful($documentInsertResponse)) {
        @unlink($targetPath);
        redirectWithState('error', 'File was saved locally but failed to insert document metadata.');
    }

    $documentRow = $documentInsertResponse['data'][0] ?? null;
    $documentId = (string)($documentRow['id'] ?? '');
    if ($documentId === '') {
        @unlink($targetPath);
        redirectWithState('error', 'Document insert succeeded but no document id was returned.');
    }

    $versionInsertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/document_versions',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'document_id' => $documentId,
            'version_no' => 1,
            'file_name' => $originalName,
            'mime_type' => $mimeType,
            'size_bytes' => $sizeBytes,
            'checksum_sha256' => $checksum,
            'storage_path' => $storedFileName,
            'uploaded_by' => $adminUserId,
        ]]
    );

    if (!isSuccessful($versionInsertResponse)) {
        redirectWithState('error', 'Document saved, but failed to create version metadata.');
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
            'action_name' => 'upload_document_file',
            'old_data' => null,
            'new_data' => [
                'title' => $title,
                'storage_bucket' => 'local_documents',
                'storage_path' => $storedFileName,
                'size_bytes' => $sizeBytes,
                'mime_type' => $mimeType,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Document uploaded to local storage and saved to database successfully.');
}

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
