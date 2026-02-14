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

$documents = isSuccessful($documentsResponse) ? $documentsResponse['data'] : [];
$reviews = isSuccessful($reviewsResponse) ? $reviewsResponse['data'] : [];

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
