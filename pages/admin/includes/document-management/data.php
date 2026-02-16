<?php

$documentsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/documents?select=id,title,document_status,current_version_no,storage_bucket,storage_path,created_at,updated_at,uploaded_by,category:document_categories(category_name),owner:people(first_name,surname),uploader:user_accounts(email)&order=updated_at.desc&limit=1000',
    $headers
);

$reviewsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/document_reviews?select=document_id,review_status,reviewed_at,review_notes,reviewer:user_accounts(email)&order=created_at.desc&limit=1000',
    $headers
);

$categoriesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/document_categories?select=id,category_name&order=category_name.asc&limit=500',
    $headers
);

$ownersResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/people?select=id,first_name,surname&order=surname.asc,first_name.asc&limit=3000',
    $headers
);

$roleAssignmentsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_role_assignments?select=user_id,role:roles(role_key,role_name)&is_primary=eq.true&expires_at=is.null&limit=5000',
    $headers
);

$documents = isSuccessful($documentsResponse) ? $documentsResponse['data'] : [];
$reviews = isSuccessful($reviewsResponse) ? $reviewsResponse['data'] : [];
$documentCategoryOptions = isSuccessful($categoriesResponse) ? (array)($categoriesResponse['data'] ?? []) : [];
$documentOwnerOptions = isSuccessful($ownersResponse) ? (array)($ownersResponse['data'] ?? []) : [];
$roleAssignments = isSuccessful($roleAssignmentsResponse) ? (array)($roleAssignmentsResponse['data'] ?? []) : [];

$dataLoadError = null;
if (!isSuccessful($documentsResponse)) {
    $dataLoadError = 'Document query failed (HTTP ' . (int)($documentsResponse['status'] ?? 0) . ').';
    $raw = trim((string)($documentsResponse['raw'] ?? ''));
    if ($raw !== '') {
        $dataLoadError .= ' ' . $raw;
    }
}

if (!isSuccessful($reviewsResponse)) {
    $reviewError = 'Review query failed (HTTP ' . (int)($reviewsResponse['status'] ?? 0) . ').';
    $raw = trim((string)($reviewsResponse['raw'] ?? ''));
    if ($raw !== '') {
        $reviewError .= ' ' . $raw;
    }
    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $reviewError) : $reviewError;
}

if (!isSuccessful($categoriesResponse)) {
    $categoryError = 'Category query failed (HTTP ' . (int)($categoriesResponse['status'] ?? 0) . ').';
    $raw = trim((string)($categoriesResponse['raw'] ?? ''));
    if ($raw !== '') {
        $categoryError .= ' ' . $raw;
    }
    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $categoryError) : $categoryError;
}

if (!isSuccessful($ownersResponse)) {
    $ownerError = 'Owner query failed (HTTP ' . (int)($ownersResponse['status'] ?? 0) . ').';
    $raw = trim((string)($ownersResponse['raw'] ?? ''));
    if ($raw !== '') {
        $ownerError .= ' ' . $raw;
    }
    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $ownerError) : $ownerError;
}

if (!isSuccessful($roleAssignmentsResponse)) {
    $roleError = 'Role assignment query failed (HTTP ' . (int)($roleAssignmentsResponse['status'] ?? 0) . ').';
    $raw = trim((string)($roleAssignmentsResponse['raw'] ?? ''));
    if ($raw !== '') {
        $roleError .= ' ' . $raw;
    }
    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $roleError) : $roleError;
}

usort($documentOwnerOptions, static function (array $left, array $right): int {
    $leftName = trim((string)($left['surname'] ?? '') . ' ' . (string)($left['first_name'] ?? ''));
    $rightName = trim((string)($right['surname'] ?? '') . ' ' . (string)($right['first_name'] ?? ''));
    return strcmp($leftName, $rightName);
});

$latestReviewByDocument = [];
foreach ($reviews as $review) {
    $documentId = (string)($review['document_id'] ?? '');
    if ($documentId === '' || isset($latestReviewByDocument[$documentId])) {
        continue;
    }

    $latestReviewByDocument[$documentId] = [
        'status' => (string)($review['review_status'] ?? ''),
        'reviewed_at' => (string)($review['reviewed_at'] ?? ''),
        'reviewer' => (string)($review['reviewer']['email'] ?? ''),
        'notes' => (string)($review['review_notes'] ?? ''),
    ];
}

$roleKeyByUserId = [];
foreach ($roleAssignments as $assignment) {
    $userId = strtolower(trim((string)($assignment['user_id'] ?? '')));
    if ($userId === '' || isset($roleKeyByUserId[$userId])) {
        continue;
    }

    $roleKey = strtolower(trim((string)($assignment['role']['role_key'] ?? '')));
    if ($roleKey !== '') {
        $roleKeyByUserId[$userId] = $roleKey;
    }
}

$uploaderSummaryMap = [];
foreach ($documents as $document) {
    $uploaderId = strtolower(trim((string)($document['uploaded_by'] ?? '')));
    if ($uploaderId === '') {
        continue;
    }

    $uploaderEmail = trim((string)($document['uploader']['email'] ?? ''));
    if ($uploaderEmail === '') {
        $uploaderEmail = 'Unknown Email';
    }

    $roleKey = $roleKeyByUserId[$uploaderId] ?? '';
    $accountType = match ($roleKey) {
        'staff' => 'staff',
        'employee' => 'employee',
        'applicant' => 'applicant',
        default => ($roleKey !== '' ? $roleKey : 'unknown'),
    };

    $documentId = (string)($document['id'] ?? '');
    $title = (string)($document['title'] ?? '-');
    $category = (string)($document['category']['category_name'] ?? 'Uncategorized');
    $status = (string)($document['document_status'] ?? 'draft');
    $updatedAt = (string)($document['updated_at'] ?? $document['created_at'] ?? '');
    $updatedLabel = $updatedAt !== '' ? date('M d, Y', strtotime($updatedAt)) : '-';
    $storageBucket = (string)($document['storage_bucket'] ?? '');
    $storagePath = (string)($document['storage_path'] ?? '');

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
        'category' => $category,
        'status' => $status,
        'updated' => $updatedLabel,
        'storage_bucket' => $storageBucket,
        'storage_path' => $storagePath,
    ];
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
