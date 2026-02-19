<?php

$documentRows = [];
$documentCategoryOptions = [];
$selectedDocumentStatus = strtolower((string)(cleanText($_GET['status'] ?? null) ?? ''));
$allowedStatusFilters = ['', 'draft', 'submitted', 'approved', 'rejected', 'archived'];
if (!in_array($selectedDocumentStatus, $allowedStatusFilters, true)) {
    $selectedDocumentStatus = '';
}

$dataLoadError = null;

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


$categoryResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/document_categories?select=id,category_name&order=category_name.asc&limit=300',
    $headers
);
$appendDataError('Document categories', $categoryResponse);
$documentCategoryOptions = isSuccessful($categoryResponse) ? (array)($categoryResponse['data'] ?? []) : [];

$personScopeIds = [];
$isAdminDocumentScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
if (!$isAdminDocumentScope && isValidUuid((string)$staffOfficeId)) {
    $officePeopleResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=person_id'
        . '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
        . '&is_current=eq.true&limit=5000',
        $headers
    );
    $appendDataError('Office people scope', $officePeopleResponse);

    $officePeopleRows = isSuccessful($officePeopleResponse) ? (array)($officePeopleResponse['data'] ?? []) : [];
    foreach ($officePeopleRows as $row) {
        $personId = cleanText($row['person_id'] ?? null);
        if ($personId === null || !isValidUuid($personId)) {
            continue;
        }

        $personScopeIds[$personId] = true;
    }
}

$documentsEndpoint = $supabaseUrl
    . '/rest/v1/documents?select=id,title,description,document_status,owner_person_id,updated_at,created_at,category:document_categories(category_name),owner:people(first_name,surname,user_id),uploader:user_accounts(email)'
    . '&order=updated_at.desc&limit=1000';

if (!$isAdminDocumentScope) {
    $personIdList = array_keys($personScopeIds);
    if (empty($personIdList)) {
        $documentsEndpoint .= '&id=is.null';
    } else {
        $documentsEndpoint .= '&owner_person_id=in.' . rawurlencode('(' . implode(',', $personIdList) . ')');
    }
}

$documentsResponse = apiRequest('GET', $documentsEndpoint, $headers);
$appendDataError('Documents', $documentsResponse);
$documents = isSuccessful($documentsResponse) ? (array)($documentsResponse['data'] ?? []) : [];

$reviewsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/document_reviews?select=document_id,review_status,reviewed_at,review_notes,reviewer:user_accounts(email)'
    . '&order=created_at.desc&limit=2000',
    $headers
);
$appendDataError('Document reviews', $reviewsResponse);
$reviews = isSuccessful($reviewsResponse) ? (array)($reviewsResponse['data'] ?? []) : [];

$latestReviewByDocument = [];
foreach ($reviews as $review) {
    $documentId = cleanText($review['document_id'] ?? null) ?? '';
    if ($documentId === '' || isset($latestReviewByDocument[$documentId])) {
        continue;
    }

    $latestReviewByDocument[$documentId] = [
        'status' => cleanText($review['review_status'] ?? null) ?? '',
        'reviewed_at' => cleanText($review['reviewed_at'] ?? null) ?? '',
        'reviewer_email' => cleanText($review['reviewer']['email'] ?? null) ?? '',
        'review_notes' => cleanText($review['review_notes'] ?? null) ?? '',
    ];
}

$statusBadge = static function (string $status): array {
    $statusKey = strtolower(trim($status));

    return match ($statusKey) {
        'submitted' => ['Submitted', 'bg-amber-100 text-amber-800'],
        'approved' => ['Approved', 'bg-emerald-100 text-emerald-800'],
        'rejected' => ['Rejected', 'bg-rose-100 text-rose-800'],
        'archived' => ['Archived', 'bg-slate-200 text-slate-700'],
        default => ['Draft', 'bg-blue-100 text-blue-800'],
    };
};

$reviewLabel = static function (?array $review): string {
    if (!is_array($review)) {
        return '-';
    }

    $status = strtolower((string)($review['status'] ?? ''));
    if ($status === '') {
        return '-';
    }

    $formatted = ucwords(str_replace('_', ' ', $status));
    $reviewedAt = cleanText($review['reviewed_at'] ?? null);
    if ($reviewedAt === null || $reviewedAt === '') {
        return $formatted;
    }

    return $formatted . ' · ' . formatDateTimeForPhilippines($reviewedAt, 'M d, Y');
};

$detect201Type = static function (array $document): ?string {
    $dictionary = [
        'PDS' => ['pds', 'personal data sheet'],
        'SSS' => ['sss'],
        'Pagibig' => ['pagibig', 'pag-ibig'],
        'Philhealth' => ['philhealth'],
        'NBI' => ['nbi'],
        'Mayors Permits' => ['mayor', 'permit'],
        'Medical' => ['medical'],
        'Drug Test' => ['drug test', 'drugtest'],
        'Health Card' => ['health card', 'healthcard'],
        'Cedula' => ['cedula', 'community tax certificate'],
        'Resume/ CV' => ['resume', 'cv', 'curriculum vitae'],
    ];

    $haystack = strtolower(trim(
        (string)($document['title'] ?? '')
        . ' '
        . (string)($document['category_name'] ?? '')
        . ' '
        . (string)($document['description'] ?? '')
    ));

    foreach ($dictionary as $label => $keywords) {
        foreach ($keywords as $keyword) {
            if (str_contains($haystack, strtolower($keyword))) {
                return $label;
            }
        }
    }

    return null;
};

$document201Types = ['PDS', 'SSS', 'Pagibig', 'Philhealth', 'NBI', 'Mayors Permits', 'Medical', 'Drug Test', 'Health Card', 'Cedula', 'Resume/ CV'];

foreach ($documents as $document) {
    $documentId = cleanText($document['id'] ?? null) ?? '';
    if (!isValidUuid($documentId)) {
        continue;
    }

    $rawStatus = strtolower((string)(cleanText($document['document_status'] ?? null) ?? 'draft'));
    if ($selectedDocumentStatus !== '' && $rawStatus !== $selectedDocumentStatus) {
        continue;
    }

    $title = cleanText($document['title'] ?? null) ?? 'Untitled Document';
    $ownerFirst = cleanText($document['owner']['first_name'] ?? null) ?? '';
    $ownerLast = cleanText($document['owner']['surname'] ?? null) ?? '';
    $ownerUserId = cleanText($document['owner']['user_id'] ?? null) ?? '';
    $ownerName = trim($ownerFirst . ' ' . $ownerLast);
    if ($ownerName === '') {
        $ownerName = 'Unknown Owner';
    }

    $categoryName = cleanText($document['category']['category_name'] ?? null) ?? 'Uncategorized';
    $description = cleanText($document['description'] ?? null) ?? '';
    [$statusLabel, $statusClass] = $statusBadge($rawStatus);

    $document201Type = $detect201Type([
        'title' => $title,
        'category_name' => $categoryName,
        'description' => $description,
    ]);
    $is201File = $document201Type !== null;

    $latestReview = $latestReviewByDocument[$documentId] ?? null;
    $latestReviewLabel = $reviewLabel($latestReview);

    $submittedAt = cleanText($document['created_at'] ?? null);
    $updatedAt = cleanText($document['updated_at'] ?? null) ?? $submittedAt;

    $documentRows[] = [
        'id' => $documentId,
        'title' => $title,
        'owner_name' => $ownerName,
        'category_name' => $categoryName,
        'status_raw' => $rawStatus,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'description' => $description,
        'submitted_label' => formatDateTimeForPhilippines($submittedAt, 'M d, Y'),
        'updated_label' => formatDateTimeForPhilippines($updatedAt, 'M d, Y'),
        'last_review' => $latestReviewLabel,
        'uploader_email' => cleanText($document['uploader']['email'] ?? null) ?? '-',
        'is_201_file' => $is201File,
        'document_201_type' => $document201Type,
        'search_text' => strtolower(trim($title . ' ' . $ownerName . ' ' . $categoryName . ' ' . $statusLabel . ' ' . $description . ' ' . ($document201Type ?? ''))),
    ];
}