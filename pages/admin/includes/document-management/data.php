<?php

$documentManagementPartial = strtolower(trim((string)($documentManagementPartial ?? '')));
$documentManagementDataStage = strtolower(trim((string)($documentManagementDataStage ?? 'queue')));
$documentManagementSelectedDocumentId = trim((string)($documentManagementSelectedDocumentId ?? ''));

$shouldLoadRegistry = in_array($documentManagementDataStage, ['queue', 'review-workflows'], true);
$shouldLoadArchived = $documentManagementDataStage === 'archives';
$shouldLoadReviewWorkflows = $documentManagementDataStage === 'review-workflows';
$shouldLoadRequests = $documentManagementDataStage === 'requests';
$shouldLoadModalSupport = $documentManagementDataStage === 'modals';
$shouldLoadAudit = $documentManagementDataStage === 'audit';

$emptyResponse = [
    'status' => 200,
    'data' => [],
    'raw' => '',
];

$documents = [];
$reviews = [];
$documentCategoryOptionsRaw = [];
$documentOwnerOptions = [];
$roleAssignments = [];
$documentRegistryRows = [];
$archivedDocumentRows = [];
$pendingStaffApprovalRows = [];
$pendingStaffReviewRows = [];
$uploaderSummaryRows = [];
$documentRequestRows = [];
$fullAuditTrailRows = [];
$auditTrailActionOptions = [];
$selectedDocumentAuditTrail = [];
$dataLoadError = null;
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

$resolveRequestAuditActionLabel = static function (string $actionName, array $payload): string {
    $status = strtolower(trim((string)($payload['status'] ?? '')));

    return match (strtolower(trim($actionName))) {
        'submit_document_request' => 'Submitted Request',
        'fulfill_document_request' => 'Fulfilled Request',
        default => match ($status) {
            'fulfilled' => 'Fulfilled Request',
            'submitted' => 'Submitted Request',
            default => ucwords(str_replace('_', ' ', trim($actionName))),
        },
    };
};

$resolveRequestAuditNotes = static function (array $payload): string {
    foreach ([
        $payload['fulfilled_notes'] ?? null,
        $payload['notes'] ?? null,
        $payload['other_purpose'] ?? null,
        $payload['custom_request_label'] ?? null,
    ] as $candidate) {
        $value = trim((string)$candidate);
        if ($value !== '') {
            return $value;
        }
    }

    return '';
};

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

$buildStoragePublicUrl = static function (string $baseUrl, string $bucket, string $path): string {
    $base = rtrim(trim($baseUrl), '/');
    $bucketName = strtolower(trim($bucket, '/'));
    $objectPath = trim(str_replace('\\', '/', $path), '/');

    if ($objectPath === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $objectPath) === 1 || str_starts_with($objectPath, '/')) {
        return $objectPath;
    }

    $localResolved = resolveStorageFilePath(dirname(__DIR__, 4) . '/storage/document', $objectPath);
    if (is_array($localResolved) && !empty($localResolved['relative_path'])) {
        return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', explode('/', (string)$localResolved['relative_path'])));
    }

    if (str_contains($objectPath, '/storage/v1/object/public/')) {
        return $objectPath;
    }

    if (str_starts_with($objectPath, 'storage/v1/object/')) {
        $tail = substr($objectPath, strlen('storage/v1/object/'));
        $tail = ltrim((string)$tail, '/');
        if (str_starts_with($tail, 'public/')) {
            $tail = substr($tail, strlen('public/'));
        }
        $parts = explode('/', (string)$tail, 2);
        $bucketName = strtolower(trim((string)($parts[0] ?? $bucketName)));
        $objectPath = trim((string)($parts[1] ?? ''));
    }

    if ($objectPath === '') {
        return '';
    }

    if (in_array(strtolower($bucketName), ['local_documents', 'local', 'filesystem'], true)) {
        $segments = array_values(array_filter(explode('/', preg_replace('#^document/#', '', $objectPath)), static fn(string $segment): bool => $segment !== ''));
        return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', $segments));
    }

    if ($base === '' || $bucketName === '') {
        return '';
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

$documentsResponse = $emptyResponse;
$reviewsResponse = $emptyResponse;
$categoriesResponse = $emptyResponse;
$ownersResponse = $emptyResponse;
$roleAssignmentsResponse = $emptyResponse;
$requestLogsResponse = $emptyResponse;
$auditLogsResponse = $emptyResponse;

$shouldLoadCategories = in_array($documentManagementDataStage, ['queue', 'review-workflows', 'archives', 'modals'], true);
$shouldLoadOwners = $shouldLoadModalSupport;
$shouldLoadRoleAssignments = $shouldLoadRegistry || $shouldLoadArchived || $shouldLoadModalSupport;

if ($shouldLoadRegistry) {
    $documentLimit = $documentManagementDataStage === 'queue' ? 25 : 5000;
    $documentsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/documents?select=id,title,description,document_status,current_version_no,storage_bucket,storage_path,created_at,updated_at,uploaded_by,owner_person_id,category:document_categories(category_name),owner:people(first_name,surname,user_id,user:user_accounts(email)),uploader:user_accounts(id,email)&order=updated_at.desc&limit=' . $documentLimit,
        $headers
    );
}

if ($shouldLoadArchived) {
    $documentsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/documents?select=id,title,description,document_status,current_version_no,storage_bucket,storage_path,created_at,updated_at,uploaded_by,owner_person_id,category:document_categories(category_name),owner:people(first_name,surname,user_id,user:user_accounts(email)),uploader:user_accounts(id,email)&document_status=eq.archived&order=updated_at.desc&limit=2000',
        $headers
    );
}

if ($shouldLoadReviewWorkflows) {
    $reviewsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/document_reviews?select=document_id,review_status,reviewed_at,review_notes,created_at,reviewer_user_id,reviewer:user_accounts(username,email)&order=created_at.desc&limit=4000',
        $headers
    );
}

if ($shouldLoadCategories) {
    $categoriesResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/document_categories?select=id,category_name&order=category_name.asc&limit=500',
        $headers
    );
}

if ($shouldLoadOwners) {
    $ownersResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,surname,profile_photo_url,user:user_accounts(email)&order=surname.asc,first_name.asc&limit=3000',
        $headers
    );
}

if ($shouldLoadRoleAssignments) {
    $roleAssignmentsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_role_assignments?select=user_id,role:roles(role_key,role_name)&is_primary=eq.true&expires_at=is.null&limit=5000',
        $headers
    );
}

if ($shouldLoadRequests) {
    $requestLogsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=entity_id,action_name,new_data,created_at,actor:user_accounts(username,email)'
        . '&module_name=eq.document_management'
        . '&entity_name=eq.document_requests'
        . '&order=created_at.asc&limit=2000',
        $headers
    );
}

if ($shouldLoadAudit && $documentManagementSelectedDocumentId !== '') {
    $auditLogsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=entity_id,action_name,new_data,created_at,actor:user_accounts(username,email)'
        . '&entity_name=eq.documents'
        . '&entity_id=eq.' . rawurlencode($documentManagementSelectedDocumentId)
        . '&action_name=in.' . rawurlencode('(upload_document,upload_document_file,upload_document_version,recommend_document,review_document,archive_document,restore_document)')
        . '&order=created_at.desc&limit=200',
        $headers
    );
}

if ($shouldLoadAudit && $documentManagementSelectedDocumentId === '') {
    $auditLogsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=entity_id,entity_name,action_name,new_data,created_at,actor:user_accounts(username,email)'
        . '&module_name=eq.document_management'
        . '&entity_name=in.' . rawurlencode('(documents,document_requests)')
        . '&order=created_at.desc&limit=4000',
        $headers
    );
}

$appendDataError('Document', $documentsResponse);
$appendDataError('Review', $reviewsResponse);
$appendDataError('Category', $categoriesResponse);
$appendDataError('Owner', $ownersResponse);
$appendDataError('Role assignment', $roleAssignmentsResponse);
$appendDataError('Document requests', $requestLogsResponse);
$appendDataError('Document audit logs', $auditLogsResponse);

$documents = isSuccessful($documentsResponse) ? (array)($documentsResponse['data'] ?? []) : [];
$reviews = isSuccessful($reviewsResponse) ? (array)($reviewsResponse['data'] ?? []) : [];
$documentCategoryOptionsRaw = isSuccessful($categoriesResponse) ? (array)($categoriesResponse['data'] ?? []) : [];
$documentOwnerOptions = isSuccessful($ownersResponse) ? (array)($ownersResponse['data'] ?? []) : [];
$roleAssignments = isSuccessful($roleAssignmentsResponse) ? (array)($roleAssignmentsResponse['data'] ?? []) : [];

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

if ($shouldLoadRegistry || $shouldLoadArchived) {
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

    $uploaderSummaryMap = [];
    foreach ($documents as $document) {
        $documentId = trim((string)($document['id'] ?? ''));
        if ($documentId === '') {
            continue;
        }

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
            'storage_bucket' => $bucket,
            'storage_path' => $path,
            'preview_url' => $previewUrl,
            'download_url' => $documentUrl,
            'document_url' => $documentUrl,
            'archived_at' => '',
            'archived_label' => '-',
        ];

        if ($shouldLoadArchived || $rawStatus === 'archived') {
            $archivedDocumentRows[] = $row;
        } elseif ($shouldLoadRegistry) {
            $documentRegistryRows[] = $row;
        }

        if ($shouldLoadReviewWorkflows) {
            $documentReviews = $reviewsByDocument[$documentId] ?? [];
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

            $ownerUserId = strtolower(trim((string)($document['owner']['user_id'] ?? '')));
            $ownerEmail = trim((string)($document['owner']['user']['email'] ?? ''));
            $uploaderEmail = trim((string)($document['uploader']['email'] ?? ''));
            $summaryUserId = $uploadedBy;
            $summaryEmail = $uploaderEmail;

            if (!in_array($uploaderRoleKey, ['employee', 'applicant'], true) && $ownerUserId !== '') {
                $summaryUserId = $ownerUserId;
                $summaryEmail = $ownerEmail !== '' ? $ownerEmail : $ownerName;
            }

            if ($summaryUserId !== '') {
                if ($summaryEmail === '') {
                    $summaryEmail = $ownerEmail !== '' ? $ownerEmail : ($uploaderEmail !== '' ? $uploaderEmail : 'Unknown Email');
                }

                if (!isset($uploaderSummaryMap[$summaryUserId])) {
                    $uploaderSummaryMap[$summaryUserId] = [
                        'user_id' => $summaryUserId,
                        'email' => $summaryEmail,
                        'account_type' => $accountType,
                        'total_uploads' => 0,
                        'last_uploaded_at' => $updatedAt,
                        'last_uploaded_label' => $updatedLabel,
                        'documents' => [],
                    ];
                }

                $uploaderSummaryMap[$summaryUserId]['total_uploads']++;
                $lastUploadedAt = (string)($uploaderSummaryMap[$summaryUserId]['last_uploaded_at'] ?? '');
                if ($lastUploadedAt === '' || ($updatedAt !== '' && strtotime($updatedAt) > strtotime($lastUploadedAt))) {
                    $uploaderSummaryMap[$summaryUserId]['last_uploaded_at'] = $updatedAt;
                    $uploaderSummaryMap[$summaryUserId]['last_uploaded_label'] = $updatedLabel;
                }

                $uploaderSummaryMap[$summaryUserId]['documents'][] = [
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
        }
    }

    if ($shouldLoadReviewWorkflows) {
        $uploaderSummaryRows = array_values($uploaderSummaryMap);
        usort($uploaderSummaryRows, static function (array $left, array $right): int {
            $leftTs = strtotime((string)($left['last_uploaded_at'] ?? '')) ?: 0;
            $rightTs = strtotime((string)($right['last_uploaded_at'] ?? '')) ?: 0;
            if ($leftTs === $rightTs) {
                return strcmp((string)($left['email'] ?? ''), (string)($right['email'] ?? ''));
            }
            return $rightTs <=> $leftTs;
        });
    }
}

if ($shouldLoadRequests && isSuccessful($requestLogsResponse)) {
    $requestRowsById = [];
    foreach ((array)($requestLogsResponse['data'] ?? []) as $requestLogRaw) {
        $requestLog = (array)$requestLogRaw;
        $payload = (array)($requestLog['new_data'] ?? []);
        $requestId = trim((string)($requestLog['entity_id'] ?? ($payload['request_id'] ?? '')));
        if ($requestId === '') {
            continue;
        }

        $actor = (array)($requestLog['actor'] ?? []);
        if (!isset($requestRowsById[$requestId])) {
            $requestRowsById[$requestId] = [
                'id' => $requestId,
                'request_type_label' => 'HR Document Request',
                'custom_request_label' => '',
                'purpose_label' => 'Other',
                'other_purpose' => '',
                'notes' => '',
                'status_raw' => 'submitted',
                'status_label' => 'Submitted',
                'requester_label' => $buildActorLabel($actor),
                'requester_user_id' => '',
                'requester_person_id' => '',
                'submitted_at' => (string)($requestLog['created_at'] ?? ''),
                'submitted_label' => formatDateTimeForPhilippines((string)($requestLog['created_at'] ?? ''), 'M d, Y g:i A'),
                'fulfilled_document_title' => '',
                'fulfilled_notes' => '',
                'fulfilled_at' => '',
                'fulfilled_label' => '',
            ];
        }

        $submittedAt = trim((string)($payload['submitted_at'] ?? ''));
        if ($submittedAt !== '') {
            $requestRowsById[$requestId]['submitted_at'] = $submittedAt;
            $requestRowsById[$requestId]['submitted_label'] = formatDateTimeForPhilippines($submittedAt, 'M d, Y g:i A');
        }

        $requesterLabel = trim((string)($payload['requester_label'] ?? ''));
        if ($requesterLabel !== '') {
            $requestRowsById[$requestId]['requester_label'] = $requesterLabel;
        }

        $fulfilledAt = trim((string)($payload['fulfilled_at'] ?? ''));
        $requestRowsById[$requestId] = array_merge($requestRowsById[$requestId], [
            'request_type_label' => trim((string)($payload['request_type_label'] ?? 'HR Document Request')),
            'custom_request_label' => trim((string)($payload['custom_request_label'] ?? '')),
            'purpose_label' => trim((string)($payload['purpose_label'] ?? 'Other')),
            'other_purpose' => trim((string)($payload['other_purpose'] ?? '')),
            'notes' => trim((string)($payload['notes'] ?? '')),
            'status_raw' => strtolower(trim((string)($payload['status'] ?? 'submitted'))),
            'status_label' => ucwords(str_replace('_', ' ', trim((string)($payload['status'] ?? 'submitted')))),
            'requester_user_id' => trim((string)($payload['requester_user_id'] ?? ($requestRowsById[$requestId]['requester_user_id'] ?? ''))),
            'requester_person_id' => trim((string)($payload['requester_person_id'] ?? ($requestRowsById[$requestId]['requester_person_id'] ?? ''))),
            'fulfilled_document_title' => trim((string)($payload['fulfilled_document_title'] ?? ($requestRowsById[$requestId]['fulfilled_document_title'] ?? ''))),
            'fulfilled_notes' => trim((string)($payload['fulfilled_notes'] ?? ($requestRowsById[$requestId]['fulfilled_notes'] ?? ''))),
            'fulfilled_at' => $fulfilledAt !== '' ? $fulfilledAt : (string)($requestRowsById[$requestId]['fulfilled_at'] ?? ''),
            'fulfilled_label' => $fulfilledAt !== ''
                ? formatDateTimeForPhilippines($fulfilledAt, 'M d, Y g:i A')
                : (string)($requestRowsById[$requestId]['fulfilled_label'] ?? ''),
        ]);
    }

    $documentRequestRows = array_values($requestRowsById);
    usort($documentRequestRows, static function (array $left, array $right): int {
        return strcmp((string)($right['submitted_at'] ?? ''), (string)($left['submitted_at'] ?? ''));
    });
}

if ($shouldLoadAudit && isSuccessful($auditLogsResponse)) {
    if ($documentManagementSelectedDocumentId === '') {
        $documentLabelById = [];
        $requestLabelById = [];

        foreach ((array)($auditLogsResponse['data'] ?? []) as $auditLogRaw) {
            $auditLog = (array)$auditLogRaw;
            $payload = (array)($auditLog['new_data'] ?? []);
            $entityId = trim((string)($auditLog['entity_id'] ?? ''));
            $entityName = strtolower(trim((string)($auditLog['entity_name'] ?? '')));

            if ($entityId === '') {
                continue;
            }

            if ($entityName === 'documents' && !isset($documentLabelById[$entityId])) {
                $title = trim((string)($payload['title'] ?? ''));
                if ($title !== '') {
                    $documentLabelById[$entityId] = $title;
                }
            }

            if ($entityName === 'document_requests' && !isset($requestLabelById[$entityId])) {
                $requestTypeLabel = trim((string)($payload['request_type_label'] ?? ''));
                if ($requestTypeLabel !== '') {
                    $requestLabelById[$entityId] = $requestTypeLabel;
                }
            }
        }

        foreach ((array)($auditLogsResponse['data'] ?? []) as $auditLogRaw) {
            $auditLog = (array)$auditLogRaw;
            $payload = (array)($auditLog['new_data'] ?? []);
            $actor = (array)($auditLog['actor'] ?? []);
            $entityId = trim((string)($auditLog['entity_id'] ?? ''));
            $entityName = strtolower(trim((string)($auditLog['entity_name'] ?? '')));
            $actionName = (string)($auditLog['action_name'] ?? '');
            $actorLabel = $buildActorLabel($actor);

            if ($entityId === '' || !in_array($entityName, ['documents', 'document_requests'], true)) {
                continue;
            }

            $recordType = $entityName === 'documents' ? 'Document' : 'Request';
            $recordLabel = $entityName === 'documents'
                ? (string)($documentLabelById[$entityId] ?? ('Document ' . substr($entityId, 0, 8)))
                : (string)($requestLabelById[$entityId] ?? ('Request ' . substr($entityId, 0, 8)));
            $actionLabel = $entityName === 'documents'
                ? $resolveAuditActionLabel($actionName, $payload)
                : $resolveRequestAuditActionLabel($actionName, $payload);
            $notes = $entityName === 'documents'
                ? $resolveAuditNotes($payload)
                : $resolveRequestAuditNotes($payload);
            $createdAt = (string)($auditLog['created_at'] ?? '');

            $fullAuditTrailRows[] = [
                'entity_id' => $entityId,
                'entity_name' => $entityName,
                'record_type' => $recordType,
                'record_label' => $recordLabel,
                'action_label' => $actionLabel,
                'actor_label' => $actorLabel,
                'created_at' => $createdAt,
                'created_label' => formatDateTimeForPhilippines($createdAt, 'M d, Y g:i A'),
                'notes' => $notes,
                'search_text' => strtolower(trim($recordType . ' ' . $recordLabel . ' ' . $actionLabel . ' ' . $actorLabel . ' ' . $notes)),
            ];

            if ($actionLabel !== '' && !in_array($actionLabel, $auditTrailActionOptions, true)) {
                $auditTrailActionOptions[] = $actionLabel;
            }
        }

        usort($auditTrailActionOptions, static function (string $left, string $right): int {
            return strcmp($left, $right);
        });

        return;
    }

    foreach ((array)($auditLogsResponse['data'] ?? []) as $auditLogRaw) {
        $auditLog = (array)$auditLogRaw;
        $payload = (array)($auditLog['new_data'] ?? []);
        $actor = (array)($auditLog['actor'] ?? []);
        $actionName = (string)($auditLog['action_name'] ?? '');

        $selectedDocumentAuditTrail[] = [
            'action_label' => $resolveAuditActionLabel($actionName, $payload),
            'actor_label' => $buildActorLabel($actor),
            'created_at' => (string)($auditLog['created_at'] ?? ''),
            'created_label' => formatDateTimeForPhilippines((string)($auditLog['created_at'] ?? ''), 'M d, Y g:i A'),
            'notes' => $resolveAuditNotes($payload),
        ];
    }
}