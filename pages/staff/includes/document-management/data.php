<?php

$documentRows = [];
$archivedDocumentRows = [];
$uploaderSummaryRows = [];

$document201Types = [
    'PDS',
    'SSS',
    'Pagibig',
    'Philhealth',
    'NBI',
    'Mayors Permits',
    'Medical',
    'Drug Test',
    'Health Card',
    'Cedula',
    'Resume/CV',
];

$selectedDocumentStatus = strtolower((string)(cleanText($_GET['status'] ?? null) ?? ''));
$allowedStatusFilters = ['', 'submitted', 'approved', 'rejected'];
if (!in_array($selectedDocumentStatus, $allowedStatusFilters, true)) {
    $selectedDocumentStatus = '';
}

$activeDocumentCount = 0;
$archivedDocumentCount = 0;

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

$documentsEndpoint = $supabaseUrl
    . '/rest/v1/documents?select=id,title,description,document_status,owner_person_id,storage_bucket,storage_path,updated_at,created_at,category:document_categories(category_name),owner:people(first_name,surname,user_id),uploader:user_accounts(email)'
    . '&order=updated_at.desc&limit=2000';

$documentsResponse = apiRequest('GET', $documentsEndpoint, $headers);
$appendDataError('Documents', $documentsResponse);
$documents = isSuccessful($documentsResponse) ? (array)($documentsResponse['data'] ?? []) : [];

$reviewsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/document_reviews?select=document_id,review_status,reviewed_at,review_notes,reviewer:user_accounts(email)'
    . '&order=created_at.desc&limit=3000',
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
        'review_notes' => cleanText($review['review_notes'] ?? null) ?? '',
    ];
}

$detect201Type = static function (array $payload) use ($document201Types): ?string {
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
        'Resume/CV' => ['resume', 'cv', 'curriculum vitae'],
    ];

    $haystack = strtolower(trim(
        (string)($payload['title'] ?? '')
        . ' '
        . (string)($payload['category_name'] ?? '')
        . ' '
        . (string)($payload['description'] ?? '')
        . ' '
        . (string)($payload['file_name'] ?? '')
        . ' '
        . (string)($payload['document_type'] ?? '')
    ));

    foreach ($dictionary as $label => $keywords) {
        foreach ($keywords as $keyword) {
            if (str_contains($haystack, strtolower($keyword))) {
                return $label;
            }
        }
    }

    return in_array((string)($payload['category_name'] ?? ''), $document201Types, true)
        ? (string)$payload['category_name']
        : null;
};

$statusBadge = static function (string $status): array {
    return match (strtolower(trim($status))) {
        'submitted' => ['Submitted for Approval', 'bg-amber-100 text-amber-800'],
        'approved' => ['Approved', 'bg-emerald-100 text-emerald-800'],
        'rejected' => ['Rejected', 'bg-rose-100 text-rose-800'],
        'archived' => ['Archived', 'bg-slate-300 text-slate-700'],
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
    $reviewedAt = cleanText($review['reviewed_at'] ?? null) ?? '';

    return $reviewedAt !== ''
        ? $formatted . ' · ' . formatDateTimeForPhilippines($reviewedAt, 'M d, Y')
        : $formatted;
};

$resolveDocumentUrl = static function (?string $bucket, ?string $path) use ($supabaseUrl): string {
    $bucketValue = strtolower(trim((string)$bucket));
    $pathValue = trim((string)$path);
    $localDocumentRoot = __DIR__ . '/../../../../storage/document';
    $resolveExistingLocalPath = static function (string $rawPath) use ($localDocumentRoot): string {
        $trimmed = trim($rawPath, '/');
        if ($trimmed === '') {
            return '';
        }

        $normalized = preg_replace('#^document/#i', '', $trimmed);
        $normalized = str_replace('\\', '/', (string)$normalized);
        $segments = array_values(array_filter(explode('/', $normalized), static fn(string $segment): bool => $segment !== ''));
        if (empty($segments)) {
            return '';
        }

        $candidateRelative = implode('/', $segments);
        $candidateFile = $localDocumentRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidateRelative);
        if (is_file($candidateFile)) {
            return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', $segments));
        }

        $basename = end($segments);
        if (is_string($basename) && $basename !== '') {
            $basenameFile = $localDocumentRoot . DIRECTORY_SEPARATOR . $basename;
            if (is_file($basenameFile)) {
                return '/hris-system/storage/document/' . rawurlencode($basename);
            }
        }

        return '';
    };

    if ($pathValue === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $pathValue) === 1) {
        return $pathValue;
    }

    if (str_starts_with($pathValue, '/')) {
        return $pathValue;
    }

    $directLocal = $resolveExistingLocalPath($pathValue);
    if ($directLocal !== '') {
        return $directLocal;
    }

    if (str_contains($pathValue, '/storage/v1/object/public/')) {
        $parts = explode('/storage/v1/object/public/', $pathValue, 2);
        $storageTail = trim((string)($parts[1] ?? ''), '/');
        if ($storageTail !== '') {
            $tailSegments = array_values(array_filter(explode('/', $storageTail), static fn(string $segment): bool => $segment !== ''));
            if (count($tailSegments) >= 2) {
                $bucketValue = strtolower((string)$tailSegments[0]);
                $pathValue = implode('/', array_slice($tailSegments, 1));

                $parsedLocal = $resolveExistingLocalPath($pathValue);
                if ($parsedLocal !== '') {
                    return $parsedLocal;
                }
            }
        }
    }

    if ($bucketValue === '' || in_array($bucketValue, ['local_documents', 'local', 'filesystem'], true)) {
        $normalizedPath = preg_replace('#^document/#', '', $pathValue);
        $segments = array_values(array_filter(explode('/', (string)$normalizedPath), static fn(string $segment): bool => $segment !== ''));
        return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', $segments));
    }

    if (preg_match('#^document/#i', $pathValue) === 1) {
        $segments = array_values(array_filter(explode('/', preg_replace('#^document/#i', '', $pathValue)), static fn(string $segment): bool => $segment !== ''));
        return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', $segments));
    }

    $segments = array_values(array_filter(explode('/', $pathValue), static fn(string $segment): bool => $segment !== ''));
    return rtrim($supabaseUrl, '/') . '/storage/v1/object/public/' . rawurlencode($bucketValue) . '/' . implode('/', array_map('rawurlencode', $segments));
};

$resolveFileUrl = static function (?string $url): string {
    $value = trim((string)$url);
    $localDocumentRoot = __DIR__ . '/../../../../storage/document';
    if ($value === '') {
        return '';
    }

    if (str_contains($value, '/storage/v1/object/public/')) {
        $parts = explode('/storage/v1/object/public/', $value, 2);
        $storageTail = trim((string)($parts[1] ?? ''), '/');
        $segments = array_values(array_filter(explode('/', $storageTail), static fn(string $segment): bool => $segment !== ''));
        if (count($segments) >= 2) {
            $objectPath = implode('/', array_slice($segments, 1));
            if (preg_match('#^document/#i', $objectPath) === 1) {
                $normalizedLocalPath = preg_replace('#^document/#i', '', $objectPath);
                $localSegments = array_values(array_filter(explode('/', (string)$normalizedLocalPath), static fn(string $segment): bool => $segment !== ''));
                if (!empty($localSegments)) {
                    return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', $localSegments));
                }
            }

            $objectSegments = array_values(array_filter(explode('/', str_replace('\\', '/', (string)$objectPath)), static fn(string $segment): bool => $segment !== ''));
            if (!empty($objectSegments)) {
                $candidate = $localDocumentRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, implode('/', $objectSegments));
                if (is_file($candidate)) {
                    return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', $objectSegments));
                }

                $objectBasename = end($objectSegments);
                if (is_string($objectBasename) && $objectBasename !== '') {
                    $basenameFile = $localDocumentRoot . DIRECTORY_SEPARATOR . $objectBasename;
                    if (is_file($basenameFile)) {
                        return '/hris-system/storage/document/' . rawurlencode($objectBasename);
                    }
                }
            }
        }
    }

    if (preg_match('#^https?://#i', $value) === 1 || str_starts_with($value, '/')) {
        return $value;
    }

    $normalizedValue = str_replace('\\', '/', ltrim($value, '/'));
    $segments = array_values(array_filter(explode('/', (string)$normalizedValue), static fn(string $segment): bool => $segment !== ''));
    if (!empty($segments)) {
        $candidate = $localDocumentRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, implode('/', $segments));
        if (is_file($candidate)) {
            return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', $segments));
        }

        $basename = end($segments);
        if (is_string($basename) && $basename !== '') {
            $basenameFile = $localDocumentRoot . DIRECTORY_SEPARATOR . $basename;
            if (is_file($basenameFile)) {
                return '/hris-system/storage/document/' . rawurlencode($basename);
            }
        }
    }

    return '/hris-system/storage/document/' . ltrim($value, '/');
};

$buildFileTypeMeta = static function (string $fileNameOrPath): array {
    $extension = strtolower((string)pathinfo($fileNameOrPath, PATHINFO_EXTENSION));

    if ($extension === 'pdf') {
        return ['PDF', 'picture_as_pdf', 'bg-rose-100 text-rose-700'];
    }
    if (in_array($extension, ['doc', 'docx'], true)) {
        return ['Word', 'description', 'bg-indigo-100 text-indigo-700'];
    }
    if (in_array($extension, ['xls', 'xlsx', 'csv'], true)) {
        return ['Sheet', 'table_chart', 'bg-emerald-100 text-emerald-700'];
    }
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        return ['Image', 'image', 'bg-blue-100 text-blue-700'];
    }

    return ['File', 'draft', 'bg-slate-100 text-slate-700'];
};

$uploaderSummaryMap = [];

foreach ($documents as $document) {
    $documentId = cleanText($document['id'] ?? null) ?? '';
    if (!isValidUuid($documentId)) {
        continue;
    }

    $title = cleanText($document['title'] ?? null) ?? 'Untitled Document';
    $categoryNameRaw = cleanText($document['category']['category_name'] ?? null) ?? '';
    $description = cleanText($document['description'] ?? null) ?? '';
    $documentType201 = $detect201Type([
        'title' => $title,
        'category_name' => $categoryNameRaw,
        'description' => $description,
    ]);

    if ($documentType201 === null) {
        continue;
    }

    $statusRaw = strtolower((string)(cleanText($document['document_status'] ?? null) ?? 'draft'));
    [$statusLabel, $statusClass] = $statusBadge($statusRaw);

    $ownerFirst = cleanText($document['owner']['first_name'] ?? null) ?? '';
    $ownerLast = cleanText($document['owner']['surname'] ?? null) ?? '';
    $ownerName = trim($ownerFirst . ' ' . $ownerLast);
    if ($ownerName === '') {
        $ownerName = 'Unknown Owner';
    }

    $ownerPersonId = cleanText($document['owner_person_id'] ?? null) ?? '';

    $createdAt = cleanText($document['created_at'] ?? null) ?? '';
    $updatedAt = cleanText($document['updated_at'] ?? null) ?? $createdAt;
    $viewUrl = $statusRaw === 'archived'
        ? ''
        : $resolveDocumentUrl(cleanText($document['storage_bucket'] ?? null), cleanText($document['storage_path'] ?? null));

    $documentRow = [
        'id' => $documentId,
        'owner_person_id' => $ownerPersonId,
        'title' => $title,
        'owner_name' => $ownerName,
        'category_name' => $documentType201,
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'submitted_label' => formatDateTimeForPhilippines($createdAt, 'M d, Y'),
        'updated_label' => formatDateTimeForPhilippines($updatedAt, 'M d, Y'),
        'last_review' => $reviewLabel($latestReviewByDocument[$documentId] ?? null),
        'view_url' => $viewUrl,
        'search_text' => strtolower(trim($title . ' ' . $ownerName . ' ' . $documentType201 . ' ' . $statusLabel . ' ' . $description)),
    ];

    if ($statusRaw === 'archived') {
        $archivedDocumentRows[] = $documentRow;
        $archivedDocumentCount++;
    } else {
        if ($selectedDocumentStatus !== '' && $statusRaw !== $selectedDocumentStatus) {
            continue;
        }
        $documentRows[] = $documentRow;
        $activeDocumentCount++;
    }

    $ownerUserId = strtolower((string)(cleanText($document['owner']['user_id'] ?? null) ?? ''));
    if (!isValidUuid($ownerUserId)) {
        continue;
    }

    if (!isset($uploaderSummaryMap[$ownerUserId])) {
        $uploaderSummaryMap[$ownerUserId] = [
            'display_name' => $ownerName,
            'email' => cleanText($document['uploader']['email'] ?? null) ?? '-',
            'account_type' => 'employee',
            'total_uploads' => 0,
            'last_uploaded_at' => $updatedAt,
            'last_uploaded_label' => formatDateTimeForPhilippines($updatedAt, 'M d, Y'),
            'documents' => [],
        ];
    }

    $uploaderSummaryMap[$ownerUserId]['total_uploads']++;
    $lastTs = strtotime((string)($uploaderSummaryMap[$ownerUserId]['last_uploaded_at'] ?? '')) ?: 0;
    $currentTs = strtotime($updatedAt) ?: 0;
    if ($currentTs > $lastTs) {
        $uploaderSummaryMap[$ownerUserId]['last_uploaded_at'] = $updatedAt;
        $uploaderSummaryMap[$ownerUserId]['last_uploaded_label'] = formatDateTimeForPhilippines($updatedAt, 'M d, Y');
    }

    [$fileTypeLabel, $fileTypeIcon, $fileTypeClass] = $buildFileTypeMeta((string)(cleanText($document['storage_path'] ?? null) ?? $title));

    $uploaderSummaryMap[$ownerUserId]['documents'][] = [
        'title' => $title,
        'category' => $documentType201,
        'status' => $statusLabel,
        'updated_at_raw' => $updatedAt,
        'updated' => formatDateTimeForPhilippines($updatedAt, 'M d, Y'),
        'source' => 'Employee Document',
        'view_url' => $viewUrl,
        'download_url' => $viewUrl,
        'file_type_label' => $fileTypeLabel,
        'file_type_icon' => $fileTypeIcon,
        'file_type_class' => $fileTypeClass,
    ];
}

$applicantRoleUserIdMap = fetchActiveRoleUserIdMap($supabaseUrl, $headers, 'applicant');

$applicationDocumentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/application_documents?select=id,document_type,file_url,file_name,uploaded_at,application:applications(application_status,submitted_at,job:job_postings(office_id),applicant_profile:applicant_profiles(user_id,full_name,email))'
    . '&order=uploaded_at.desc&limit=5000',
    $headers
);
$appendDataError('Applicant documents', $applicationDocumentsResponse);

foreach ((array)($applicationDocumentsResponse['data'] ?? []) as $applicationDocument) {
    $applicantUserId = strtolower((string)(cleanText($applicationDocument['application']['applicant_profile']['user_id'] ?? null) ?? ''));
    if (!isValidUuid($applicantUserId) || !isset($applicantRoleUserIdMap[$applicantUserId])) {
        continue;
    }

    $fileName = cleanText($applicationDocument['file_name'] ?? null) ?? 'Applicant Document';
    $documentType201 = $detect201Type([
        'file_name' => $fileName,
        'document_type' => cleanText($applicationDocument['document_type'] ?? null) ?? '',
    ]);

    if ($documentType201 === null) {
        continue;
    }

    $displayName = cleanText($applicationDocument['application']['applicant_profile']['full_name'] ?? null) ?? 'Unknown Applicant';
    $email = cleanText($applicationDocument['application']['applicant_profile']['email'] ?? null) ?? '-';
    $uploadedAt = cleanText($applicationDocument['uploaded_at'] ?? null) ?? (cleanText($applicationDocument['application']['submitted_at'] ?? null) ?? '');
    $uploadedLabel = formatDateTimeForPhilippines($uploadedAt, 'M d, Y');
    $status = ucwords(str_replace('_', ' ', strtolower((string)(cleanText($applicationDocument['application']['application_status'] ?? null) ?? 'submitted'))));
    $fileUrl = $resolveFileUrl(cleanText($applicationDocument['file_url'] ?? null));
    [$fileTypeLabel, $fileTypeIcon, $fileTypeClass] = $buildFileTypeMeta($fileName);

    if (!isset($uploaderSummaryMap[$applicantUserId])) {
        $uploaderSummaryMap[$applicantUserId] = [
            'display_name' => $displayName,
            'email' => $email,
            'account_type' => 'applicant',
            'total_uploads' => 0,
            'last_uploaded_at' => $uploadedAt,
            'last_uploaded_label' => $uploadedLabel,
            'documents' => [],
        ];
    }

    $uploaderSummaryMap[$applicantUserId]['total_uploads']++;
    $lastTs = strtotime((string)($uploaderSummaryMap[$applicantUserId]['last_uploaded_at'] ?? '')) ?: 0;
    $currentTs = strtotime($uploadedAt) ?: 0;
    if ($currentTs > $lastTs) {
        $uploaderSummaryMap[$applicantUserId]['last_uploaded_at'] = $uploadedAt;
        $uploaderSummaryMap[$applicantUserId]['last_uploaded_label'] = $uploadedLabel;
    }

    $uploaderSummaryMap[$applicantUserId]['documents'][] = [
        'title' => $fileName,
        'category' => $documentType201,
        'status' => $status,
        'updated_at_raw' => $uploadedAt,
        'updated' => $uploadedLabel,
        'source' => 'Applicant Requirement',
        'view_url' => $fileUrl,
        'download_url' => $fileUrl,
        'file_type_label' => $fileTypeLabel,
        'file_type_icon' => $fileTypeIcon,
        'file_type_class' => $fileTypeClass,
    ];
}

$uploaderSummaryRows = array_values($uploaderSummaryMap);
foreach ($uploaderSummaryRows as &$uploaderRow) {
    usort($uploaderRow['documents'], static function (array $left, array $right): int {
        $leftTs = strtotime((string)($left['updated_at_raw'] ?? '')) ?: 0;
        $rightTs = strtotime((string)($right['updated_at_raw'] ?? '')) ?: 0;
        return $rightTs <=> $leftTs;
    });
}
unset($uploaderRow);

usort($uploaderSummaryRows, static function (array $left, array $right): int {
    $leftTs = strtotime((string)($left['last_uploaded_at'] ?? '')) ?: 0;
    $rightTs = strtotime((string)($right['last_uploaded_at'] ?? '')) ?: 0;
    if ($leftTs === $rightTs) {
        return strcmp((string)($left['display_name'] ?? ''), (string)($right['display_name'] ?? ''));
    }
    return $rightTs <=> $leftTs;
});
