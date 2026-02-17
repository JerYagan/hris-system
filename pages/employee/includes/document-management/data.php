<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);
$dataLoadError = null;
$csrfToken = ensureCsrfToken();

$documentCategories = [];
$employeeDocuments = [];
$documentVersionsById = [];
$documentReviewsById = [];

if (!(bool)($employeeContextResolved ?? false)) {
    $dataLoadError = (string)($employeeContextError ?? 'Employee context could not be resolved.');
    return;
}

$categoryResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/document_categories?select=id,category_name&order=category_name.asc&limit=200',
    $headers
);

if (isSuccessful($categoryResponse)) {
    foreach ((array)($categoryResponse['data'] ?? []) as $categoryRaw) {
        $category = (array)$categoryRaw;
        $categoryId = (string)($category['id'] ?? '');
        $categoryName = (string)($category['category_name'] ?? '');
        if ($categoryId === '' || $categoryName === '') {
            continue;
        }

        $documentCategories[] = [
            'id' => $categoryId,
            'category_name' => $categoryName,
        ];
    }
} else {
    $dataLoadError = 'Unable to load document categories.';
}

$documentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/documents?select=id,title,description,document_status,current_version_no,storage_bucket,storage_path,created_at,updated_at,category:document_categories(category_name)'
    . '&owner_person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=updated_at.desc&limit=200',
    $headers
);

if (!isSuccessful($documentsResponse)) {
    $docError = 'Unable to load your documents right now.';
    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $docError) : $docError;
    return;
}

$documentIds = [];
foreach ((array)($documentsResponse['data'] ?? []) as $documentRaw) {
    $document = (array)$documentRaw;
    $documentId = (string)($document['id'] ?? '');
    if ($documentId === '') {
        continue;
    }

    $documentIds[] = $documentId;
    $categoryRow = (array)($document['category'] ?? []);

    $employeeDocuments[] = [
        'id' => $documentId,
        'title' => (string)($document['title'] ?? 'Untitled Document'),
        'description' => (string)($document['description'] ?? ''),
        'document_status' => strtolower((string)($document['document_status'] ?? 'draft')),
        'current_version_no' => (int)($document['current_version_no'] ?? 1),
        'storage_bucket' => (string)($document['storage_bucket'] ?? ''),
        'storage_path' => (string)($document['storage_path'] ?? ''),
        'created_at' => (string)($document['created_at'] ?? ''),
        'updated_at' => (string)($document['updated_at'] ?? ''),
        'category_name' => (string)($categoryRow['category_name'] ?? 'Uncategorized'),
    ];
}

if (empty($documentIds)) {
    return;
}

$inClause = implode(',', array_map(static fn(string $id): string => rawurlencode($id), $documentIds));

$versionsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/document_versions?select=id,document_id,version_no,file_name,mime_type,size_bytes,uploaded_at'
    . '&document_id=in.(' . $inClause . ')'
    . '&order=version_no.desc,uploaded_at.desc&limit=1000',
    $headers
);

if (isSuccessful($versionsResponse)) {
    foreach ((array)($versionsResponse['data'] ?? []) as $versionRaw) {
        $version = (array)$versionRaw;
        $documentId = (string)($version['document_id'] ?? '');
        if ($documentId === '') {
            continue;
        }

        if (!isset($documentVersionsById[$documentId])) {
            $documentVersionsById[$documentId] = [];
        }

        $documentVersionsById[$documentId][] = [
            'id' => (string)($version['id'] ?? ''),
            'version_no' => (int)($version['version_no'] ?? 1),
            'file_name' => (string)($version['file_name'] ?? ''),
            'mime_type' => (string)($version['mime_type'] ?? ''),
            'size_bytes' => (int)($version['size_bytes'] ?? 0),
            'uploaded_at' => (string)($version['uploaded_at'] ?? ''),
        ];
    }
}

$reviewsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/document_reviews?select=id,document_id,review_status,review_notes,reviewed_at,reviewer:user_accounts(email)'
    . '&document_id=in.(' . $inClause . ')'
    . '&order=reviewed_at.desc,created_at.desc&limit=1000',
    $headers
);

if (isSuccessful($reviewsResponse)) {
    foreach ((array)($reviewsResponse['data'] ?? []) as $reviewRaw) {
        $review = (array)$reviewRaw;
        $documentId = (string)($review['document_id'] ?? '');
        if ($documentId === '') {
            continue;
        }

        if (!isset($documentReviewsById[$documentId])) {
            $documentReviewsById[$documentId] = [];
        }

        $reviewer = (array)($review['reviewer'] ?? []);

        $documentReviewsById[$documentId][] = [
            'id' => (string)($review['id'] ?? ''),
            'review_status' => strtolower((string)($review['review_status'] ?? 'pending')),
            'review_notes' => (string)($review['review_notes'] ?? ''),
            'reviewed_at' => (string)($review['reviewed_at'] ?? ''),
            'reviewer_email' => (string)($reviewer['email'] ?? ''),
        ];
    }
}
