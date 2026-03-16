<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = (string)($_POST['form_action'] ?? '');

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
        $supabaseUrl . '/rest/v1/document_categories?select=id&category_key=eq.' . rawurlencode($categoryKey) . '&limit=1',
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
            'actor_user_id' => $adminUserId,
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

if ($action === 'upload_document_file') {
    $ownerPersonId = cleanText($_POST['owner_person_id'] ?? null) ?? '';
    $categoryInput = cleanText($_POST['category_name'] ?? null) ?? '';
    $uploadDateInput = cleanText($_POST['upload_date'] ?? null) ?? '';
    $title = cleanText($_POST['title'] ?? null) ?? '';
    $description = cleanText($_POST['description'] ?? null);
    $upload = $_FILES['document_file'] ?? null;

    if ($ownerPersonId === '' || $categoryInput === '' || $uploadDateInput === '' || $title === '') {
        redirectWithState('error', 'Owner, file type, upload date, and title are required.');
    }

    $uploadDate = DateTimeImmutable::createFromFormat('Y-m-d', $uploadDateInput);
    if (!$uploadDate || $uploadDate->format('Y-m-d') !== $uploadDateInput) {
        redirectWithState('error', 'Invalid upload date format.');
    }
    $uploadTimestamp = $uploadDate->setTime(12, 0, 0)->format('Y-m-d\TH:i:sP');

    if (!preg_match('/^[0-9a-fA-F-]{36}$/', $ownerPersonId)) {
        redirectWithState('error', 'Invalid owner identifier.');
    }

    $canonicalCategoryMap = [
        'violation' => 'Violation',
        'memorandum receipt' => 'Memorandum Receipt',
        'memorandum' => 'Memorandum Receipt',
        'gsis' => 'GSIS',
        'gsis instead sss' => 'GSIS',
        'copy of saln' => 'Copy of SALN',
        'saln' => 'Copy of SALN',
        'service record' => 'Service Record',
        'coe' => 'COE',
        'pds' => 'PDS',
        'sss' => 'SSS',
        'pagibig' => 'Pagibig',
        'pag-ibig' => 'Pagibig',
        'philhealth' => 'Philhealth',
        'nbi' => 'NBI',
        'medical' => 'Medical',
        'drug test' => 'Drug Test',
        'others' => 'Others',
        'other' => 'Others',
    ];

    $normalizeCategory = static function (string $value): string {
        return strtolower(trim((string)preg_replace('/\s+/', ' ', $value)));
    };

    $categoryKeyInput = $normalizeCategory($categoryInput);
    $categoryName = $canonicalCategoryMap[$categoryKeyInput] ?? '';
    if ($categoryName === '') {
        redirectWithState('error', 'Please select a valid 201 file category.');
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
        $supabaseUrl . '/rest/v1/document_categories?select=id,category_name&limit=500',
        $headers
    );

    $categoryId = '';
    $matchedCategoryName = '';
    if (isSuccessful($categoryResponse)) {
        foreach ((array)($categoryResponse['data'] ?? []) as $row) {
            $rowId = trim((string)($row['id'] ?? ''));
            $rowName = trim((string)($row['category_name'] ?? ''));
            if ($rowId === '' || $rowName === '') {
                continue;
            }

            if ($normalizeCategory($rowName) === $categoryKeyInput) {
                $categoryId = $rowId;
                $matchedCategoryName = $rowName;
                break;
            }

            $rowCanonical = $canonicalCategoryMap[$normalizeCategory($rowName)] ?? '';
            if ($categoryName !== '' && $rowCanonical === $categoryName) {
                $categoryId = $rowId;
                $matchedCategoryName = $rowName;
                break;
            }
        }
    }

    if ($categoryId === '' && $categoryName !== '') {
        $categoryKey = strtolower(trim((string)preg_replace('/[^a-z0-9]+/', '_', $categoryName), '_'));
        $insertCategoryResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/document_categories',
            array_merge($headers, ['Prefer: return=representation']),
            [[
                'category_key' => $categoryKey,
                'category_name' => $categoryName,
                'requires_approval' => true,
            ]]
        );

        if (isSuccessful($insertCategoryResponse)) {
            $categoryId = trim((string)($insertCategoryResponse['data'][0]['id'] ?? ''));
        }

        if ($categoryId === '') {
            $categoryByKeyResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/document_categories?select=id&category_key=eq.' . rawurlencode($categoryKey) . '&limit=1',
                $headers
            );
            if (isSuccessful($categoryByKeyResponse)) {
                $categoryId = trim((string)($categoryByKeyResponse['data'][0]['id'] ?? ''));
            }
        }
    }

    if ($categoryId === '') {
        redirectWithState('error', 'Unable to resolve category for upload.');
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
            'created_at' => $uploadTimestamp,
            'updated_at' => $uploadTimestamp,
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
            'uploaded_at' => $uploadTimestamp,
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

    if (in_array($reviewStatus, ['rejected', 'needs_revision'], true) && trim((string)$reviewNotes) === '') {
        redirectWithState('error', 'Review notes are required for rejected or needs revision decisions.');
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

    if (in_array($currentStatus, ['approved', 'rejected'], true)) {
        redirectWithState('error', 'Finalized documents cannot be reviewed again.');
    }

    $mappedStatus = match ($reviewStatus) {
        'approved' => 'approved',
        'rejected' => 'rejected',
        'needs_revision' => 'needs_revision',
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

    $persistedStatus = $mappedStatus;
    $patchRaw = strtolower((string)($patchResponse['raw'] ?? ''));
    $enumValueRejected = str_contains($patchRaw, 'invalid input value for enum')
        || str_contains($patchRaw, 'doc_status_enum')
        || str_contains($patchRaw, 'needs_revision');

    if (!isSuccessful($patchResponse) && $reviewStatus === 'needs_revision' && $enumValueRejected) {
        $fallbackStatus = 'submitted';
        $fallbackPatchResponse = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/documents?id=eq.' . $documentId,
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'document_status' => $fallbackStatus,
                'updated_at' => gmdate('c'),
            ]
        );

        if (isSuccessful($fallbackPatchResponse)) {
            $patchResponse = $fallbackPatchResponse;
            $persistedStatus = $fallbackStatus;
        }
    }

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
        $notificationBody = 'Your document "' . ((string)($documentRow['title'] ?? 'Document')) . '" was marked as ' . str_replace('_', ' ', $reviewStatus) . '.';
        if ($reviewStatus === 'rejected' || $reviewStatus === 'needs_revision') {
            $reviewNotesText = trim((string)$reviewNotes);
            if ($reviewNotesText !== '') {
                $notificationBody .= ' Notes: ' . $reviewNotesText;
            }
            $notificationBody .= ' Please revise and resubmit in your document management page.';
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $uploadedBy,
                'category' => 'documents',
                'title' => 'Document Review Updated',
                'body' => $notificationBody,
                'link_url' => '/hris-system/pages/employee/document-management.php',
            ]]
        );
    }

    logStatusTransition(
        $supabaseUrl,
        $headers,
        $adminUserId,
        'document_management',
        'documents',
        $documentId,
        'review_document',
        $currentStatus,
        $persistedStatus,
        $reviewNotes,
        [
            'review_status' => $reviewStatus,
        ]
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

    logStatusTransition(
        $supabaseUrl,
        $headers,
        $adminUserId,
        'document_management',
        'documents',
        $documentId,
        'archive_document',
        $currentStatus,
        'archived',
        $archiveReason
    );

    redirectWithState('success', 'Document archived successfully.');
}

if ($action === 'restore_document') {
    $documentId = cleanText($_POST['document_id'] ?? null) ?? '';

    if ($documentId === '') {
        redirectWithState('error', 'Document is required for restore.');
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
    if ($currentStatus !== 'archived') {
        redirectWithState('error', 'Only archived documents can be restored.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/documents?id=eq.' . $documentId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'document_status' => 'submitted',
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to restore document.');
    }

    logStatusTransition(
        $supabaseUrl,
        $headers,
        $adminUserId,
        'document_management',
        'documents',
        $documentId,
        'restore_document',
        'archived',
        'submitted',
        null
    );

    redirectWithState('success', 'Document restored to submitted status.');
}

redirectWithState('error', 'Unknown document management action.');
