<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

if (!(bool)($employeeContextResolved ?? false)) {
    redirectWithState('error', (string)($employeeContextError ?? 'Employee context could not be resolved.'), 'document-management.php');
}

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.', 'document-management.php');
}

$action = strtolower((string)cleanText($_POST['action'] ?? ''));
if (!in_array($action, ['upload_document', 'upload_new_version', 'archive_document'], true)) {
    redirectWithState('error', 'Unsupported document management action.', 'document-management.php');
}

$validateAndStoreUpload = static function (array $upload, string $personId, string $suffix) {
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
    $normalizedRelativePath = normalizeRelativeStoragePath($relativePath);
    if ($normalizedRelativePath === null) {
        return ['ok' => false, 'message' => 'Unable to generate a secure storage path for upload.'];
    }

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

    $checksum = (string)(hash_file('sha256', $targetPath) ?: '');

    return [
        'ok' => true,
        'original_name' => $originalName,
        'size_bytes' => $sizeBytes,
        'mime_type' => $mimeType,
        'checksum' => $checksum,
        'storage_path' => $normalizedRelativePath,
    ];
};

if ($action === 'upload_document') {
    $categoryId = cleanText($_POST['category_id'] ?? null) ?? '';
    $title = cleanText($_POST['title'] ?? null) ?? '';
    $description = cleanText($_POST['description'] ?? null);
    $upload = $_FILES['document_file'] ?? null;

    if (!isValidUuid($categoryId) || $title === '') {
        redirectWithState('error', 'Category and title are required.', 'document-management.php');
    }

    if (!is_array($upload)) {
        redirectWithState('error', 'Please choose a file to upload.', 'document-management.php');
    }

    $categoryResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/document_categories?select=id&id=eq.' . rawurlencode($categoryId) . '&limit=1',
        $headers
    );

    if (!isSuccessful($categoryResponse) || empty((array)($categoryResponse['data'] ?? []))) {
        redirectWithState('error', 'Selected document category is invalid.', 'document-management.php');
    }

    $saved = $validateAndStoreUpload($upload, (string)$employeePersonId, 'new');
    if (!(bool)($saved['ok'] ?? false)) {
        redirectWithState('error', (string)($saved['message'] ?? 'Upload failed.'), 'document-management.php');
    }

    $documentInsertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/documents',
        array_merge($headers, ['Prefer: return=representation']),
        [[
            'owner_person_id' => $employeePersonId,
            'category_id' => $categoryId,
            'title' => $title,
            'description' => $description,
            'storage_bucket' => 'local_documents',
            'storage_path' => (string)$saved['storage_path'],
            'current_version_no' => 1,
            'document_status' => 'submitted',
            'uploaded_by' => $employeeUserId,
        ]]
    );

    if (!isSuccessful($documentInsertResponse) || empty((array)($documentInsertResponse['data'] ?? []))) {
        $failedFile = resolveStorageFilePath(dirname(__DIR__, 4) . '/storage/document', (string)$saved['storage_path']);
        if ($failedFile !== null) {
            @unlink((string)$failedFile['absolute_path']);
        }
        redirectWithState('error', 'Failed to save document metadata.', 'document-management.php');
    }

    $document = (array)$documentInsertResponse['data'][0];
    $documentId = (string)($document['id'] ?? '');

    $versionInsertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/document_versions',
        $headers,
        [[
            'document_id' => $documentId,
            'version_no' => 1,
            'file_name' => (string)$saved['original_name'],
            'mime_type' => (string)$saved['mime_type'],
            'size_bytes' => (int)$saved['size_bytes'],
            'checksum_sha256' => (string)$saved['checksum'],
            'storage_path' => (string)$saved['storage_path'],
            'uploaded_by' => $employeeUserId,
        ]]
    );

    if (!isSuccessful($versionInsertResponse)) {
        redirectWithState('error', 'Document saved but version metadata failed.', 'document-management.php');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        $headers,
        [[
            'actor_user_id' => $employeeUserId,
            'module_name' => 'employee',
            'entity_name' => 'documents',
            'entity_id' => $documentId,
            'action_name' => 'upload_document',
            'old_data' => null,
            'new_data' => [
                'title' => $title,
                'storage_path' => (string)$saved['storage_path'],
                'size_bytes' => (int)$saved['size_bytes'],
            ],
        ]]
    );

    redirectWithState('success', 'Document uploaded successfully.', 'document-management.php');
}

if ($action === 'upload_new_version') {
    $documentId = cleanText($_POST['document_id'] ?? null) ?? '';
    $upload = $_FILES['version_file'] ?? null;

    if (!isValidUuid($documentId)) {
        redirectWithState('error', 'Invalid document selected for version upload.', 'document-management.php');
    }

    if (!is_array($upload)) {
        redirectWithState('error', 'Please choose a version file to upload.', 'document-management.php');
    }

    $documentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/documents?select=id,title,current_version_no,owner_person_id'
        . '&id=eq.' . rawurlencode($documentId)
        . '&owner_person_id=eq.' . rawurlencode((string)$employeePersonId)
        . '&limit=1',
        $headers
    );

    if (!isSuccessful($documentResponse) || empty((array)($documentResponse['data'] ?? []))) {
        redirectWithState('error', 'Document not found or access denied.', 'document-management.php');
    }

    $document = (array)$documentResponse['data'][0];
    $nextVersionNo = ((int)($document['current_version_no'] ?? 1)) + 1;

    $saved = $validateAndStoreUpload($upload, (string)$employeePersonId, 'v' . (string)$nextVersionNo);
    if (!(bool)($saved['ok'] ?? false)) {
        redirectWithState('error', (string)($saved['message'] ?? 'Version upload failed.'), 'document-management.php');
    }

    $versionInsertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/document_versions',
        $headers,
        [[
            'document_id' => $documentId,
            'version_no' => $nextVersionNo,
            'file_name' => (string)$saved['original_name'],
            'mime_type' => (string)$saved['mime_type'],
            'size_bytes' => (int)$saved['size_bytes'],
            'checksum_sha256' => (string)$saved['checksum'],
            'storage_path' => (string)$saved['storage_path'],
            'uploaded_by' => $employeeUserId,
        ]]
    );

    if (!isSuccessful($versionInsertResponse)) {
        redirectWithState('error', 'Failed to save new version metadata.', 'document-management.php');
    }

    $documentPatchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/documents?id=eq.' . rawurlencode($documentId) . '&owner_person_id=eq.' . rawurlencode((string)$employeePersonId),
        $headers,
        [
            'storage_path' => (string)$saved['storage_path'],
            'current_version_no' => $nextVersionNo,
            'document_status' => 'submitted',
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($documentPatchResponse)) {
        redirectWithState('error', 'Failed to update document current version.', 'document-management.php');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        $headers,
        [[
            'actor_user_id' => $employeeUserId,
            'module_name' => 'employee',
            'entity_name' => 'documents',
            'entity_id' => $documentId,
            'action_name' => 'upload_document_version',
            'old_data' => ['current_version_no' => $nextVersionNo - 1],
            'new_data' => [
                'current_version_no' => $nextVersionNo,
                'storage_path' => (string)$saved['storage_path'],
                'size_bytes' => (int)$saved['size_bytes'],
            ],
        ]]
    );

    redirectWithState('success', 'New document version uploaded successfully.', 'document-management.php');
}

if ($action === 'archive_document') {
    $documentId = cleanText($_POST['document_id'] ?? null) ?? '';

    if (!isValidUuid($documentId)) {
        redirectWithState('error', 'Invalid document selected for archive.', 'document-management.php');
    }

    $documentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/documents?select=id,title,document_status,owner_person_id'
        . '&id=eq.' . rawurlencode($documentId)
        . '&owner_person_id=eq.' . rawurlencode((string)$employeePersonId)
        . '&limit=1',
        $headers
    );

    if (!isSuccessful($documentResponse) || empty((array)($documentResponse['data'] ?? []))) {
        redirectWithState('error', 'Document not found or access denied.', 'document-management.php');
    }

    $document = (array)$documentResponse['data'][0];
    $oldStatus = strtolower((string)($document['document_status'] ?? 'draft'));
    if ($oldStatus === 'archived') {
        redirectWithState('error', 'Document is already archived.', 'document-management.php');
    }

    $archiveResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/documents?id=eq.' . rawurlencode($documentId) . '&owner_person_id=eq.' . rawurlencode((string)$employeePersonId),
        $headers,
        [
            'document_status' => 'archived',
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($archiveResponse)) {
        redirectWithState('error', 'Failed to archive document.', 'document-management.php');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        $headers,
        [[
            'actor_user_id' => $employeeUserId,
            'module_name' => 'employee',
            'entity_name' => 'documents',
            'entity_id' => $documentId,
            'action_name' => 'archive_document',
            'old_data' => ['document_status' => $oldStatus],
            'new_data' => ['document_status' => 'archived'],
        ]]
    );

    redirectWithState('success', 'Document archived successfully.', 'document-management.php');
}
