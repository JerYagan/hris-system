<?php

$documentsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/documents?select=id,title,description,document_status,current_version_no,storage_bucket,storage_path,created_at,updated_at,uploaded_by,owner_person_id,category:document_categories(category_name),owner:people(first_name,surname,user_id),uploader:user_accounts(id,email)&order=updated_at.desc&limit=2000',
    $headers
);

$reviewsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/document_reviews?select=document_id,review_status,reviewed_at,review_notes,created_at,reviewer_user_id,reviewer:user_accounts(username,email)&order=created_at.desc&limit=4000',
    $headers
);

$categoriesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/document_categories?select=id,category_name&order=category_name.asc&limit=500',
    $headers
);

$ownersResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,surname,profile_photo_url,user:user_accounts(email)&order=surname.asc,first_name.asc&limit=3000',
    $headers
);

$roleAssignmentsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_role_assignments?select=user_id,role:roles(role_key,role_name)&is_primary=eq.true&expires_at=is.null&limit=5000',
    $headers
);

$documents = isSuccessful($documentsResponse) ? (array)($documentsResponse['data'] ?? []) : [];
$reviews = isSuccessful($reviewsResponse) ? (array)($reviewsResponse['data'] ?? []) : [];
$documentCategoryOptionsRaw = isSuccessful($categoriesResponse) ? (array)($categoriesResponse['data'] ?? []) : [];
$documentOwnerOptions = isSuccessful($ownersResponse) ? (array)($ownersResponse['data'] ?? []) : [];
$roleAssignments = isSuccessful($roleAssignmentsResponse) ? (array)($roleAssignmentsResponse['data'] ?? []) : [];

$dataLoadError = null;
$documentRequestRows = [];
$documentAuditTrailById = [];
$invalidCategoryNames = ['haugafia'];
$appendDataError = static function (string $label, array $response) use (&$dataLoadError): void {
    if (isSuccessful($response)) {
        return;
    }

    $piece = $label . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $piece .= ' ' . $raw;
    }

    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $piece) : $piece;
};

$appendDataError('Document', $documentsResponse);
$appendDataError('Review', $reviewsResponse);
$appendDataError('Category', $categoriesResponse);
$appendDataError('Owner', $ownersResponse);
$appendDataError('Role assignment', $roleAssignmentsResponse);

usort($documentOwnerOptions, static function (array $left, array $right): int {
    $leftName = trim((string)($left['surname'] ?? '') . ' ' . (string)($left['first_name'] ?? ''));
    $rightName = trim((string)($right['surname'] ?? '') . ' ' . (string)($right['first_name'] ?? ''));
    return strcmp($leftName, $rightName);
});

$roleKeyByUserId = [];
foreach ($roleAssignments as $assignment) {
    $userId = strtolower(trim((string)($assignment['user_id'] ?? '')));
    $roleKey = strtolower(trim((string)($assignment['role']['role_key'] ?? '')));
    if ($userId !== '' && $roleKey !== '' && !isset($roleKeyByUserId[$userId])) {
        $roleKeyByUserId[$userId] = $roleKey;
    }
}

$resolveProfilePhotoUrl = static function (string $rawPath): string {
    $normalized = trim($rawPath);
    if ($normalized === '') {
        return '';
    }

    if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://') || str_starts_with($normalized, '/')) {
        return $normalized;
    }

    return '/hris-system/storage/document/' . ltrim($normalized, '/');
};

$filteredOwnerOptions = [];
foreach ($documentOwnerOptions as $owner) {
    $ownerId = trim((string)($owner['id'] ?? ''));
    $ownerUserId = strtolower(trim((string)($owner['user_id'] ?? ($owner['user']['id'] ?? ''))));
    $ownerRoleKey = $ownerUserId !== '' ? strtolower(trim((string)($roleKeyByUserId[$ownerUserId] ?? ''))) : '';

    if ($ownerId === '' || !in_array($ownerRoleKey, ['employee', 'staff'], true)) {
        continue;
    }

    $owner['role_key'] = $ownerRoleKey;
    $owner['resolved_profile_photo_url'] = $resolveProfilePhotoUrl((string)($owner['profile_photo_url'] ?? ''));
    $filteredOwnerOptions[] = $owner;
}

$documentOwnerOptions = $filteredOwnerOptions;

$canonicalCategories = [
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

$normalizeCategoryName = static function (string $value) use ($canonicalCategories): string {
    $label = trim((string)preg_replace('/\s+/', ' ', $value));
    $key = strtolower($label);
    if ($key === '') {
        return 'Others';
    }
    return $canonicalCategories[$key] ?? $label;
};

$sanitizeCategoryName = static function (?string $value) use ($invalidCategoryNames): ?string {
    $label = trim((string)$value);
    if ($label === '') {
        return null;
    }

    if (in_array(strtolower($label), $invalidCategoryNames, true)) {
        return null;
    }

    return $label;
};

$buildActorLabel = static function (array $actor): string {
    $username = trim((string)($actor['username'] ?? ''));
    if ($username !== '') {
        return $username;
    }

    $email = trim((string)($actor['email'] ?? ''));
    return $email !== '' ? $email : 'System';
};

$resolveAuditActionLabel = static function (string $actionName, array $payload): string {
    $statusContext = (array)($payload['status_context'] ?? []);
    $reviewStatus = strtolower(trim((string)($payload['review_status'] ?? ($statusContext['review_status'] ?? ''))));
    $statusTo = strtolower(trim((string)($payload['status_to'] ?? ($payload['status'] ?? ''))));

    return match (strtolower(trim($actionName))) {
        'upload_document', 'upload_document_file' => 'Created',
        'upload_document_version' => 'Updated',
        'recommend_document' => $reviewStatus !== ''
            ? 'Reviewed: Recommend ' . ucwords(str_replace('_', ' ', $reviewStatus))
            : 'Reviewed',
        'review_document' => match ($reviewStatus !== '' ? $reviewStatus : $statusTo) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'needs_revision', 'need_revision' => 'Needs Revision',
            default => 'Reviewed',
        },
        'archive_document' => 'Archived',
        'restore_document' => 'Restored',
        default => ucwords(str_replace('_', ' ', trim($actionName))),
    };
};

$resolveAuditNotes = static function (array $payload): string {
    $statusContext = (array)($payload['status_context'] ?? []);
    foreach ([
        $payload['review_notes'] ?? null,
        $statusContext['review_notes'] ?? null,
        $payload['archive_reason'] ?? null,
        $payload['status_reason'] ?? null,
        $payload['reason'] ?? null,
    ] as $candidate) {
        $value = trim((string)$candidate);
        if ($value !== '') {
            return $value;
        }
    }

    return '';
};

$orderedCategoryDisplay = [
    'Violation',
    'Memorandum Receipt',
    'GSIS',
    'Copy of SALN',
    'Service Record',
    'COE',
    'PDS',
    'SSS',
    'Pagibig',
    'Philhealth',
    'NBI',
    'Medical',
    'Drug Test',
    'Others',
];

$documentCategoryFilterOptions = $orderedCategoryDisplay;

$categoryIdByCanonical = [];
$customCategoryOptions = [];
foreach ($documentCategoryOptionsRaw as $category) {
    $categoryId = trim((string)($category['id'] ?? ''));
    $rawName = $sanitizeCategoryName((string)($category['category_name'] ?? ''));
    if ($categoryId === '' || $rawName === null) {
        continue;
    }

    $canonicalName = $normalizeCategoryName($rawName);
    if (!isset($categoryIdByCanonical[$canonicalName])) {
        $categoryIdByCanonical[$canonicalName] = $categoryId;
    }

    if (!in_array($rawName, $orderedCategoryDisplay, true)) {
        $customCategoryOptions[strtolower($rawName)] = [
            'id' => $categoryId,
            'category_name' => $rawName,
        ];
    }
}

$documentCategoryOptions = [];
foreach ($orderedCategoryDisplay as $categoryName) {
    if (!isset($categoryIdByCanonical[$categoryName])) {
        continue;
    }

    $documentCategoryOptions[] = [
        'id' => $categoryIdByCanonical[$categoryName],
        'category_name' => $categoryName,
    ];
}

foreach ($customCategoryOptions as $customCategory) {
    $documentCategoryOptions[] = $customCategory;
}

$documentCategoryFilterOptions = array_values(array_unique(array_merge(
    $orderedCategoryDisplay,
    array_map(static fn(array $category): string => (string)($category['category_name'] ?? ''), array_values($customCategoryOptions))
)));

if (empty($documentCategoryOptions)) {
    $seen = [];
    foreach ($documentCategoryOptionsRaw as $category) {
        $categoryId = trim((string)($category['id'] ?? ''));
        $rawName = $sanitizeCategoryName((string)($category['category_name'] ?? ''));
        if ($categoryId === '' || $rawName === null) {
            continue;
        }

        $canonicalName = $normalizeCategoryName($rawName);
        if (isset($seen[$canonicalName])) {
            continue;
        }

        $seen[$canonicalName] = true;
        $documentCategoryOptions[] = [
            'id' => $categoryId,
            'category_name' => $canonicalName,
        ];
    }
}

$buildStoragePublicUrl = static function (string $baseUrl, string $bucket, string $path): string {
    $base = rtrim(trim($baseUrl), '/');
    $bucketName = trim($bucket, '/');
    $objectPath = trim($path, '/');

    if ($base === '' || $bucketName === '' || $objectPath === '') {
        return '';
    }

    if (in_array(strtolower($bucketName), ['local_documents', 'local', 'filesystem'], true)) {
        $segments = array_values(array_filter(explode('/', preg_replace('#^document/#', '', $objectPath)), static fn(string $segment): bool => $segment !== ''));
        return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', $segments));
    }

    $segments = array_values(array_filter(explode('/', $objectPath), static fn(string $segment): bool => $segment !== ''));
    return $base . '/storage/v1/object/public/' . rawurlencode($bucketName) . '/' . implode('/', array_map('rawurlencode', $segments));
};

$statusLabel = static function (string $status): string {
    $key = strtolower(trim($status));
    return match ($key) {
        'needs_revision', 'need_revision', 'need revision', 'needs revision' => 'Needs Revision',
        'submitted' => 'Submitted',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'archived' => 'Archived',
        default => 'Draft',
    };
};

$mapAccountType = static function (string $roleKey): string {
    return match (strtolower(trim($roleKey))) {
        'employee' => 'employee',
        'applicant' => 'applicant',
        'staff' => 'staff',
        'admin' => 'admin',
        default => 'unknown',
    };
};

$reviewsByDocument = [];
foreach ($reviews as $review) {
    $documentId = trim((string)($review['document_id'] ?? ''));
    if ($documentId === '') {
        continue;
    }

    $reviewerUserId = strtolower(trim((string)($review['reviewer_user_id'] ?? '')));
    $reviewerRole = $reviewerUserId !== '' ? (string)($roleKeyByUserId[$reviewerUserId] ?? '') : '';
    $reviewsByDocument[$documentId][] = [
        'status' => strtolower(trim((string)($review['review_status'] ?? ''))),
        'reviewed_at' => trim((string)($review['reviewed_at'] ?? '')),
        'created_at' => trim((string)($review['created_at'] ?? '')),
        'notes' => trim((string)($review['review_notes'] ?? '')),
        'reviewer_label' => trim((string)($review['reviewer']['username'] ?? '')) !== ''
            ? trim((string)($review['reviewer']['username'] ?? ''))
            : trim((string)($review['reviewer']['email'] ?? '')),
        'reviewer_email' => trim((string)($review['reviewer']['email'] ?? '')),
        'reviewer_role' => $reviewerRole,
    ];
}

$documentRegistryRows = [];
$archivedDocumentRows = [];
$pendingStaffApprovalRows = [];
$pendingStaffReviewRows = [];
$uploaderSummaryMap = [];
$documentIds = [];

foreach ($documents as $document) {
    $documentId = trim((string)($document['id'] ?? ''));
    if ($documentId === '') {
        continue;
    }

    $documentIds[] = $documentId;

    $title = trim((string)($document['title'] ?? 'Untitled Document'));
    $rawStatus = strtolower(trim((string)($document['document_status'] ?? 'draft')));
    $versionNo = (int)($document['current_version_no'] ?? 1);
    $rawCategory = trim((string)($document['category']['category_name'] ?? ''));
    $categoryName = $sanitizeCategoryName($normalizeCategoryName($rawCategory)) ?? 'Others';

    $ownerFirst = trim((string)($document['owner']['first_name'] ?? ''));
    $ownerLast = trim((string)($document['owner']['surname'] ?? ''));
    $ownerName = trim($ownerFirst . ' ' . $ownerLast);
    if ($ownerName === '') {
        $ownerName = 'Unknown Owner';
    }

    $uploadedBy = strtolower(trim((string)($document['uploaded_by'] ?? '')));
    $uploaderRoleKey = (string)($roleKeyByUserId[$uploadedBy] ?? '');
    $accountType = $mapAccountType($uploaderRoleKey);
    if (!in_array($accountType, ['employee', 'applicant'], true)) {
        $ownerUserId = strtolower(trim((string)($document['owner']['user_id'] ?? '')));
        $accountType = $mapAccountType((string)($roleKeyByUserId[$ownerUserId] ?? ''));
    }

    if (!in_array($accountType, ['employee', 'applicant'], true)) {
        $accountType = 'employee';
    }

    $createdAt = trim((string)($document['created_at'] ?? ''));
    $updatedAt = trim((string)($document['updated_at'] ?? $createdAt));
    $createdLabel = $createdAt !== '' ? date('M d, Y', strtotime($createdAt)) : '-';
    $updatedLabel = $updatedAt !== '' ? date('M d, Y', strtotime($updatedAt)) : '-';

    $bucket = trim((string)($document['storage_bucket'] ?? ''));
    $path = trim((string)($document['storage_path'] ?? ''));
    $documentUrl = $buildStoragePublicUrl((string)$supabaseUrl, $bucket, $path);
    $previewUrl = '/hris-system/pages/admin/document-preview.php?document_id=' . rawurlencode($documentId) . '&return_to=' . rawurlencode('/hris-system/pages/admin/document-management.php');

    $documentReviews = $reviewsByDocument[$documentId] ?? [];
    $latestReview = $documentReviews[0] ?? null;
    $latestStaffReview = null;
    $latestAdminReview = null;
    foreach ($documentReviews as $review) {
        $role = strtolower(trim((string)($review['reviewer_role'] ?? '')));
        if ($latestStaffReview === null && $role === 'staff') {
            $latestStaffReview = $review;
        }
        if ($latestAdminReview === null && $role === 'admin') {
            $latestAdminReview = $review;
        }
        if ($latestStaffReview !== null && $latestAdminReview !== null) {
            break;
        }
    }

    $lastReviewLabel = '-';
    if (is_array($latestReview)) {
        $lastReviewLabel = $statusLabel((string)($latestReview['status'] ?? ''));
    }

    $row = [
        'id' => $documentId,
        'title' => $title,
        'status_raw' => $rawStatus,
        'status_label' => $statusLabel($rawStatus),
        'category' => $categoryName,
        'owner_name' => $ownerName,
        'account_type' => $accountType,
        'version_no' => $versionNo,
        'created_at' => $createdAt,
        'created_label' => $createdLabel,
        'updated_at' => $updatedAt,
        'updated_label' => $updatedLabel,
        'last_review' => $lastReviewLabel,
        'latest_review_notes' => (string)($latestReview['notes'] ?? ''),
        'storage_bucket' => $bucket,
        'storage_path' => $path,
        'preview_url' => $previewUrl,
        'download_url' => $documentUrl,
        'document_url' => $documentUrl,
        'archived_at' => '',
        'archived_label' => '-',
        'audit_trail' => [],
    ];

    if ($rawStatus === 'archived') {
        $archivedDocumentRows[] = $row;
    } else {
        $documentRegistryRows[] = $row;
    }

    $isFinalState = in_array($rawStatus, ['approved', 'rejected', 'archived'], true);
    $staffReviewTs = strtotime((string)($latestStaffReview['created_at'] ?? '')) ?: 0;
    $adminReviewTs = strtotime((string)($latestAdminReview['created_at'] ?? '')) ?: 0;

    if (!$isFinalState) {
        if ($latestStaffReview !== null && $staffReviewTs >= $adminReviewTs) {
            $pendingStaffApprovalRows[] = array_merge($row, [
                'staff_recommendation' => $statusLabel((string)($latestStaffReview['status'] ?? '')),
                'staff_notes' => (string)($latestStaffReview['notes'] ?? ''),
            ]);
        }

        if ($latestStaffReview === null) {
            $pendingStaffReviewRows[] = $row;
        }
    }

    $uploaderId = $uploadedBy;
    if ($uploaderId === '') {
        continue;
    }

    $uploaderEmail = trim((string)($document['uploader']['email'] ?? ''));
    if ($uploaderEmail === '') {
        $uploaderEmail = 'Unknown Email';
    }

    if (!isset($uploaderSummaryMap[$uploaderId])) {
        $uploaderSummaryMap[$uploaderId] = [
            'user_id' => $uploaderId,
            'email' => $uploaderEmail,
            'account_type' => $accountType,
            'total_uploads' => 0,
            'last_uploaded_at' => $updatedAt,
            'last_uploaded_label' => $updatedLabel,
            'documents' => [],
        ];
    }

    $uploaderSummaryMap[$uploaderId]['total_uploads']++;
    $lastUploadedAt = (string)($uploaderSummaryMap[$uploaderId]['last_uploaded_at'] ?? '');
    if ($lastUploadedAt === '' || ($updatedAt !== '' && strtotime($updatedAt) > strtotime($lastUploadedAt))) {
        $uploaderSummaryMap[$uploaderId]['last_uploaded_at'] = $updatedAt;
        $uploaderSummaryMap[$uploaderId]['last_uploaded_label'] = $updatedLabel;
    }

    $uploaderSummaryMap[$uploaderId]['documents'][] = [
        'id' => $documentId,
        'title' => $title,
        'category' => $categoryName,
        'status' => $statusLabel($rawStatus),
        'updated' => $updatedLabel,
        'updated_at' => $updatedAt,
        'storage_bucket' => $bucket,
        'storage_path' => $path,
        'preview_url' => $previewUrl,
        'download_url' => $documentUrl,
        'url' => $documentUrl,
    ];
}

if (!empty($documentIds)) {
    $auditInClause = implode(',', array_map(static fn(string $id): string => rawurlencode($id), $documentIds));
    $auditLogsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=entity_id,action_name,new_data,created_at,actor:user_accounts(username,email)'
        . '&entity_name=eq.documents'
        . '&entity_id=in.(' . $auditInClause . ')'
        . '&action_name=in.' . rawurlencode('(upload_document,upload_document_file,upload_document_version,recommend_document,review_document,archive_document,restore_document)')
        . '&order=created_at.desc&limit=5000',
        $headers
    );
    $appendDataError('Document audit logs', $auditLogsResponse);

    if (isSuccessful($auditLogsResponse)) {
        $archivedAtByDocumentId = [];

        foreach ((array)($auditLogsResponse['data'] ?? []) as $auditLogRaw) {
            $auditLog = (array)$auditLogRaw;
            $documentId = trim((string)($auditLog['entity_id'] ?? ''));
            if ($documentId === '') {
                continue;
            }

            $payload = (array)($auditLog['new_data'] ?? []);
            $actor = (array)($auditLog['actor'] ?? []);
            $actionName = (string)($auditLog['action_name'] ?? '');

            $documentAuditTrailById[$documentId][] = [
                'action_label' => $resolveAuditActionLabel($actionName, $payload),
                'actor_label' => $buildActorLabel($actor),
                'created_at' => (string)($auditLog['created_at'] ?? ''),
                'created_label' => formatDateTimeForPhilippines((string)($auditLog['created_at'] ?? ''), 'M d, Y g:i A'),
                'notes' => $resolveAuditNotes($payload),
            ];

            if (strtolower(trim($actionName)) === 'archive_document' && !isset($archivedAtByDocumentId[$documentId])) {
                $archivedAtByDocumentId[$documentId] = (string)($auditLog['created_at'] ?? '');
            }
        }

        foreach ($documentRegistryRows as &$row) {
            $documentId = (string)($row['id'] ?? '');
            $row['audit_trail'] = (array)($documentAuditTrailById[$documentId] ?? []);
        }
        unset($row);

        foreach ($pendingStaffApprovalRows as &$row) {
            $documentId = (string)($row['id'] ?? '');
            $row['audit_trail'] = (array)($documentAuditTrailById[$documentId] ?? []);
        }
        unset($row);

        foreach ($pendingStaffReviewRows as &$row) {
            $documentId = (string)($row['id'] ?? '');
            $row['audit_trail'] = (array)($documentAuditTrailById[$documentId] ?? []);
        }
        unset($row);

        foreach ($archivedDocumentRows as &$row) {
            $documentId = (string)($row['id'] ?? '');
            $archivedAt = (string)($archivedAtByDocumentId[$documentId] ?? '');
            $row['archived_at'] = $archivedAt;
            $row['archived_label'] = $archivedAt !== ''
                ? formatDateTimeForPhilippines($archivedAt, 'M d, Y g:i A')
                : '-';
            $row['audit_trail'] = (array)($documentAuditTrailById[$documentId] ?? []);
        }
        unset($row);
    }
}

$requestLogsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/activity_logs?select=entity_id,action_name,new_data,created_at,actor:user_accounts(username,email)'
    . '&module_name=eq.document_management'
    . '&entity_name=eq.document_requests'
    . '&order=created_at.desc&limit=2000',
    $headers
);
$appendDataError('Document requests', $requestLogsResponse);

if (isSuccessful($requestLogsResponse)) {
    foreach ((array)($requestLogsResponse['data'] ?? []) as $requestLogRaw) {
        $requestLog = (array)$requestLogRaw;
        $payload = (array)($requestLog['new_data'] ?? []);
        $requestId = trim((string)($requestLog['entity_id'] ?? ($payload['request_id'] ?? '')));
        if ($requestId === '') {
            continue;
        }

        $actor = (array)($requestLog['actor'] ?? []);
        $documentRequestRows[] = [
            'id' => $requestId,
            'request_type_label' => trim((string)($payload['request_type_label'] ?? 'HR Document Request')),
            'custom_request_label' => trim((string)($payload['custom_request_label'] ?? '')),
            'purpose_label' => trim((string)($payload['purpose_label'] ?? 'Other')),
            'other_purpose' => trim((string)($payload['other_purpose'] ?? '')),
            'notes' => trim((string)($payload['notes'] ?? '')),
            'status_label' => ucwords(str_replace('_', ' ', trim((string)($payload['status'] ?? 'submitted')))),
            'requester_label' => $buildActorLabel($actor),
            'submitted_at' => (string)($requestLog['created_at'] ?? ''),
            'submitted_label' => formatDateTimeForPhilippines((string)($requestLog['created_at'] ?? ''), 'M d, Y g:i A'),
        ];
    }
}

$uploaderSummaryRows = array_values($uploaderSummaryMap);
usort($uploaderSummaryRows, static function (array $left, array $right): int {
    $leftTs = strtotime((string)($left['last_uploaded_at'] ?? '')) ?: 0;
    $rightTs = strtotime((string)($right['last_uploaded_at'] ?? '')) ?: 0;
    if ($leftTs === $rightTs) {
        return strcmp((string)($left['email'] ?? ''), (string)($right['email'] ?? ''));
    }
    return $rightTs <=> $leftTs;
});
