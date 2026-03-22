<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = (string)($_POST['form_action'] ?? '');

$buildActorLabel = static function (array $actor): string {
    $username = trim((string)($actor['username'] ?? ''));
    if ($username !== '') {
        return $username;
    }

    $email = trim((string)($actor['email'] ?? ''));
    return $email !== '' ? $email : 'System';
};

$storeUploadedDocument = static function (array $upload, string $personId, string $suffix): array {
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'];
    $allowedMime = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        'application/csv',
    ];

    if ((int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'Please select a valid file.'];
    }

    $tmpPath = (string)($upload['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return ['ok' => false, 'message' => 'Uploaded file was not found.'];
    }

    $originalName = (string)($upload['name'] ?? 'document');
    $sizeBytes = (int)($upload['size'] ?? 0);
    if ($sizeBytes <= 0 || $sizeBytes > 10 * 1024 * 1024) {
        return ['ok' => false, 'message' => 'File must be between 1 byte and 10 MB.'];
    }

    $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
        return ['ok' => false, 'message' => 'File extension is not supported.'];
    }

    $year = gmdate('Y');
    $month = gmdate('m');
    $safePersonId = preg_replace('/[^a-zA-Z0-9\-_]/', '', $personId);
    $hash = substr((string)sha1($tmpPath . '|' . $originalName . '|' . (string)$sizeBytes . '|' . microtime(true)), 0, 20);
    $fileName = gmdate('YmdHis') . '-' . $suffix . '-' . $hash . '.' . $extension;

    $relativeDir = $safePersonId . '/' . $year . '/' . $month;
    $relativePath = $relativeDir . '/' . $fileName;
    $storageRoot = dirname(__DIR__, 4) . '/storage/document';
    $targetDir = $storageRoot . '/' . $relativeDir;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        return ['ok' => false, 'message' => 'Unable to prepare document storage directory.'];
    }

    $targetPath = $targetDir . '/' . $fileName;
    if (!move_uploaded_file($tmpPath, $targetPath)) {
        return ['ok' => false, 'message' => 'Failed to save uploaded file.'];
    }

    $mimeType = (string)(mime_content_type($targetPath) ?: 'application/octet-stream');
    if (!in_array($mimeType, $allowedMime, true)) {
        @unlink($targetPath);
        return ['ok' => false, 'message' => 'Uploaded file MIME type is not allowed.'];
    }

    return [
        'ok' => true,
        'original_name' => $originalName,
        'size_bytes' => $sizeBytes,
        'mime_type' => $mimeType,
        'checksum' => (string)(hash_file('sha256', $targetPath) ?: ''),
        'storage_path' => $relativePath,
    ];
};

$resolveCategoryId = static function (string $categoryName) use ($supabaseUrl, $headers): string {
    $label = trim($categoryName);
    if ($label === '') {
        return '';
    }

    $categoryKey = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '_', $label), '_'));
    if ($categoryKey === '') {
        return '';
    }

    $existingResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/document_categories?select=id,category_name'
        . '&or=(category_key.eq.' . rawurlencode($categoryKey) . ',category_name.eq.' . rawurlencode($label) . ')'
        . '&limit=1',
        $headers
    );

    if (isSuccessful($existingResponse)) {
        $existingId = trim((string)($existingResponse['data'][0]['id'] ?? ''));
        if ($existingId !== '') {
            return $existingId;
        }
    }

    $createResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/document_categories',
        array_merge($headers, ['Prefer: return=representation']),
        [[
            'category_key' => $categoryKey,
            'category_name' => $label,
            'requires_approval' => true,
        ]]
    );

    return trim((string)($createResponse['data'][0]['id'] ?? ''));
};

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

$applyDocumentReview = static function (string $documentId, string $reviewStatus, ?string $reviewNotes) use ($supabaseUrl, $headers, $adminUserId): array {
    if ($documentId === '' || $reviewStatus === '') {
        return ['ok' => false, 'message' => 'Document and review status are required.'];
    }

    if (!in_array($reviewStatus, ['approved', 'rejected', 'needs_revision'], true)) {
        return ['ok' => false, 'message' => 'Invalid review status selected.'];
    }

    if (in_array($reviewStatus, ['rejected', 'needs_revision'], true) && trim((string)$reviewNotes) === '') {
        return ['ok' => false, 'message' => 'Review notes are required for rejected or needs revision decisions.'];
    }

    $documentResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/documents?select=id,title,document_status,uploaded_by&id=eq.' . $documentId . '&limit=1',
        $headers
    );

    $documentRow = $documentResponse['data'][0] ?? null;
    if (!is_array($documentRow)) {
        return ['ok' => false, 'message' => 'Document record not found.'];
    }

    $title = (string)($documentRow['title'] ?? 'Document');
    $currentStatus = strtolower((string)($documentRow['document_status'] ?? 'draft'));
    if ($currentStatus === 'archived') {
        return ['ok' => false, 'message' => 'Archived documents cannot be reviewed.', 'title' => $title];
    }

    if (in_array($currentStatus, ['approved', 'rejected'], true)) {
        return ['ok' => false, 'message' => 'Finalized documents cannot be reviewed again.', 'title' => $title];
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
        return ['ok' => false, 'message' => 'Failed to update document status.', 'title' => $title];
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
        $notificationBody = 'Your document "' . $title . '" was marked as ' . str_replace('_', ' ', $reviewStatus) . '.';
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

    return ['ok' => true, 'title' => $title];
};

if ($action === 'review_document') {
    $documentId = cleanText($_POST['document_id'] ?? null) ?? '';
    $reviewStatus = strtolower((string)(cleanText($_POST['review_status'] ?? null) ?? ''));
    $reviewNotes = cleanText($_POST['review_notes'] ?? null);

    $result = $applyDocumentReview($documentId, $reviewStatus, $reviewNotes);
    if (!(bool)($result['ok'] ?? false)) {
        redirectWithState('error', (string)($result['message'] ?? 'Failed to save document review.'));
    }

    redirectWithState('success', 'Document review saved successfully.');
}

if ($action === 'bulk_review_documents') {
    $documentIds = array_values(array_filter(array_map(
        static fn($value): string => cleanText($value) ?? '',
        (array)($_POST['document_ids'] ?? [])
    ), static fn(string $value): bool => $value !== ''));
    $documentIds = array_values(array_unique($documentIds));
    $reviewStatus = strtolower((string)(cleanText($_POST['review_status'] ?? null) ?? ''));
    $reviewNotes = cleanText($_POST['review_notes'] ?? null);

    if ($documentIds === []) {
        redirectWithState('error', 'Select at least one document before running a bulk review.');
    }

    if (!in_array($reviewStatus, ['approved', 'rejected'], true)) {
        redirectWithState('error', 'Bulk review supports approval or rejection only.');
    }

    if ($reviewStatus === 'rejected' && trim((string)$reviewNotes) === '') {
        redirectWithState('error', 'Review notes are required when rejecting documents in bulk.');
    }

    $successCount = 0;
    $failures = [];
    foreach ($documentIds as $documentId) {
        $result = $applyDocumentReview($documentId, $reviewStatus, $reviewNotes);
        if ((bool)($result['ok'] ?? false)) {
            $successCount++;
            continue;
        }

        $label = trim((string)($result['title'] ?? $documentId));
        $failures[] = $label . ': ' . (string)($result['message'] ?? 'Unknown error.');
    }

    if ($successCount === 0) {
        redirectWithState('error', $failures !== []
            ? 'No documents were updated. ' . implode(' ', array_slice($failures, 0, 3))
            : 'No documents were updated.');
    }

    if ($failures !== []) {
        redirectWithState('success', 'Updated ' . $successCount . ' document(s). Some items were skipped: ' . implode(' ', array_slice($failures, 0, 3)));
    }

    redirectWithState('success', 'Updated ' . $successCount . ' document(s) successfully.');
}

if ($action === 'fulfill_document_request') {
    $requestId = cleanText($_POST['request_id'] ?? null) ?? '';
    $fulfilledTitle = trim((string)(cleanText($_POST['fulfilled_document_title'] ?? null) ?? ''));
    $fulfilledNotes = trim((string)(cleanText($_POST['fulfilled_notes'] ?? null) ?? ''));
    $upload = $_FILES['fulfilled_document_file'] ?? null;

    if (!isValidUuid($requestId)) {
        redirectWithState('error', 'A valid HR request identifier is required.');
    }

    if ($fulfilledTitle === '') {
        redirectWithState('error', 'Released document title is required.');
    }

    if ($upload === null || !is_array($upload)) {
        redirectWithState('error', 'A released document file is required.');
    }

    $requestLogsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=entity_id,action_name,new_data,created_at,actor:user_accounts(username,email)'
        . '&module_name=eq.document_management'
        . '&entity_name=eq.document_requests'
        . '&entity_id=eq.' . rawurlencode($requestId)
        . '&order=created_at.asc&limit=100',
        $headers
    );

    if (!isSuccessful($requestLogsResponse) || empty((array)($requestLogsResponse['data'] ?? []))) {
        redirectWithState('error', 'HR document request was not found.');
    }

    $requestState = [];
    $submittedAt = '';
    $requesterLabel = '';
    foreach ((array)($requestLogsResponse['data'] ?? []) as $requestLogRaw) {
        $requestLog = (array)$requestLogRaw;
        $payload = (array)($requestLog['new_data'] ?? []);
        $requestState = array_merge($requestState, $payload);

        if ($submittedAt === '') {
            $submittedAt = (string)($requestLog['created_at'] ?? '');
        }

        if ($requesterLabel === '') {
            $requesterLabel = trim((string)($requestState['requester_label'] ?? ''));
            if ($requesterLabel === '') {
                $requesterLabel = $buildActorLabel((array)($requestLog['actor'] ?? []));
            }
        }
    }

    $currentStatus = strtolower(trim((string)($requestState['status'] ?? 'submitted')));
    if ($currentStatus === 'fulfilled') {
        redirectWithState('success', 'This HR document request is already fulfilled.');
    }

    $requesterPersonId = trim((string)($requestState['requester_person_id'] ?? ''));
    $requesterUserId = trim((string)($requestState['requester_user_id'] ?? ''));
    $requestType = strtolower(trim((string)($requestState['request_type'] ?? 'other_hr_document')));
    $requestTypeLabel = trim((string)($requestState['request_type_label'] ?? 'HR Document Request'));
    $customRequestLabel = trim((string)($requestState['custom_request_label'] ?? ''));

    if (!isValidUuid($requesterPersonId)) {
        redirectWithState('error', 'The request is missing a valid requester person record.');
    }

    $categoryLabel = match ($requestType) {
        'coe' => 'COE',
        'service_record' => 'Service Record',
        'certificate_of_foreign_travel' => 'Certificate of Foreign Travel',
        default => ($customRequestLabel !== '' ? $customRequestLabel : 'Other HR Document'),
    };
    $categoryId = $resolveCategoryId($categoryLabel);
    if ($categoryId === '') {
        redirectWithState('error', 'Unable to resolve a category for the fulfilled HR document.');
    }

    $storedUpload = $storeUploadedDocument($upload, $requesterPersonId, 'fulfilled-request');
    if (!(bool)($storedUpload['ok'] ?? false)) {
        redirectWithState('error', (string)($storedUpload['message'] ?? 'Unable to store the fulfilled request document.'));
    }

    $releasedAt = gmdate('c');
    $documentDescriptionParts = ['Released from HR request: ' . $requestTypeLabel];
    $originalNotes = trim((string)($requestState['notes'] ?? ''));
    if ($originalNotes !== '') {
        $documentDescriptionParts[] = 'Request notes: ' . $originalNotes;
    }
    if ($fulfilledNotes !== '') {
        $documentDescriptionParts[] = 'Fulfillment notes: ' . $fulfilledNotes;
    }

    $documentInsertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/documents',
        array_merge($headers, ['Prefer: return=representation']),
        [[
            'owner_person_id' => $requesterPersonId,
            'category_id' => $categoryId,
            'title' => $fulfilledTitle,
            'description' => implode("\n", $documentDescriptionParts),
            'storage_bucket' => 'local_documents',
            'storage_path' => (string)$storedUpload['storage_path'],
            'current_version_no' => 1,
            'document_status' => 'approved',
            'uploaded_by' => $adminUserId,
            'created_at' => $releasedAt,
            'updated_at' => $releasedAt,
        ]]
    );

    if (!isSuccessful($documentInsertResponse)) {
        redirectWithState('error', 'Released file was stored, but the fulfilled document record could not be created.');
    }

    $documentId = trim((string)($documentInsertResponse['data'][0]['id'] ?? ''));
    if ($documentId === '') {
        redirectWithState('error', 'Fulfilled document record did not return a document id.');
    }

    $versionInsertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/document_versions',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'document_id' => $documentId,
            'version_no' => 1,
            'file_name' => (string)$storedUpload['original_name'],
            'mime_type' => (string)$storedUpload['mime_type'],
            'size_bytes' => (int)($storedUpload['size_bytes'] ?? 0),
            'checksum_sha256' => (string)$storedUpload['checksum'],
            'storage_path' => (string)$storedUpload['storage_path'],
            'uploaded_by' => $adminUserId,
            'uploaded_at' => $releasedAt,
        ]]
    );

    if (!isSuccessful($versionInsertResponse)) {
        redirectWithState('error', 'Fulfilled document was created, but version metadata could not be saved.');
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
                'title' => $fulfilledTitle,
                'storage_bucket' => 'local_documents',
                'storage_path' => (string)$storedUpload['storage_path'],
                'size_bytes' => (int)($storedUpload['size_bytes'] ?? 0),
                'mime_type' => (string)$storedUpload['mime_type'],
                'source' => 'fulfilled_document_request',
                'request_id' => $requestId,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    $requestPayload = array_merge($requestState, [
        'request_id' => $requestId,
        'request_type' => $requestType,
        'request_type_label' => $requestTypeLabel,
        'custom_request_label' => $customRequestLabel,
        'purpose_key' => (string)($requestState['purpose_key'] ?? 'other'),
        'purpose_label' => (string)($requestState['purpose_label'] ?? 'Other'),
        'other_purpose' => (string)($requestState['other_purpose'] ?? ''),
        'notes' => (string)($requestState['notes'] ?? ''),
        'requester_user_id' => $requesterUserId,
        'requester_person_id' => $requesterPersonId,
        'requester_role' => (string)($requestState['requester_role'] ?? 'employee'),
        'requester_label' => $requesterLabel,
        'submitted_at' => $submittedAt,
        'submitted_by' => $requesterLabel,
        'status' => 'fulfilled',
        'fulfilled_document_id' => $documentId,
        'fulfilled_document_title' => $fulfilledTitle,
        'fulfilled_notes' => $fulfilledNotes,
        'fulfilled_at' => $releasedAt,
        'fulfilled_by_label' => 'Admin',
    ]);

    $requestUpdateResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'document_management',
            'entity_name' => 'document_requests',
            'entity_id' => $requestId,
            'action_name' => 'fulfill_document_request',
            'old_data' => ['status' => $currentStatus],
            'new_data' => $requestPayload,
            'ip_address' => clientIp(),
        ]]
    );

    if (!isSuccessful($requestUpdateResponse)) {
        redirectWithState('error', 'Fulfilled document was uploaded, but the request status could not be updated.');
    }

    if (isValidUuid($requesterUserId)) {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $requesterUserId,
                'category' => 'documents',
                'title' => 'HR document request fulfilled',
                'body' => 'Your request for ' . $requestTypeLabel . ' has been fulfilled and uploaded as "' . $fulfilledTitle . '".',
                'link_url' => '/hris-system/pages/employee/document-management.php',
            ]]
        );
    }

    redirectWithState('success', 'HR document request fulfilled and uploaded successfully.');
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
