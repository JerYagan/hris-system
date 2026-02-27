<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);
$dataLoadError = null;
$csrfToken = ensureCsrfToken();

$requiredDocumentCategories = [
    'Violation',
    'Memorandum Receipt',
    'GSIS instead SSS',
    'Copy of SALN',
    'Service record',
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

$normalizeCategoryName = static function (string $value): string {
    return strtolower(trim(preg_replace('/\s+/', ' ', $value)));
};

$toCategoryKey = static function (string $label): string {
    $key = strtolower((string)preg_replace('/[^a-z0-9]+/i', '_', $label));
    $key = trim($key, '_');
    return 'employee_201_' . $key;
};

$requiredCategoryAliasMap = [
    'violation' => 'Violation',
    'memorandum receipt' => 'Memorandum Receipt',
    'gsis instead sss' => 'GSIS instead SSS',
    'gsis' => 'GSIS instead SSS',
    'copy of saln' => 'Copy of SALN',
    'service record' => 'Service record',
    'coe' => 'COE',
    'certificate of employment' => 'COE',
    'pds' => 'PDS',
    'personal data sheet' => 'PDS',
    'sss' => 'SSS',
    'pagibig' => 'Pagibig',
    'pag-ibig' => 'Pagibig',
    'philhealth' => 'Philhealth',
    'nbi' => 'NBI',
    'medical' => 'Medical',
    'drug test' => 'Drug Test',
    'drugtest' => 'Drug Test',
    'others' => 'Others',
    'other' => 'Others',
];

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
    $categoriesByRequiredLabel = [];

    $mapCategories = static function (array $rows) use (&$categoriesByRequiredLabel, $normalizeCategoryName, $requiredCategoryAliasMap): void {
        foreach ($rows as $categoryRaw) {
            $category = (array)$categoryRaw;
            $categoryId = (string)($category['id'] ?? '');
            $categoryName = (string)($category['category_name'] ?? '');
            if ($categoryId === '' || $categoryName === '') {
                continue;
            }

            $normalized = $normalizeCategoryName($categoryName);
            $mappedLabel = $requiredCategoryAliasMap[$normalized] ?? null;
            if ($mappedLabel === null || isset($categoriesByRequiredLabel[$mappedLabel])) {
                continue;
            }

            $categoriesByRequiredLabel[$mappedLabel] = [
                'id' => $categoryId,
                'category_name' => $mappedLabel,
            ];
        }
    };

    $mapCategories((array)($categoryResponse['data'] ?? []));

    $missingRequiredLabels = [];
    foreach ($requiredDocumentCategories as $requiredLabel) {
        if (!isset($categoriesByRequiredLabel[$requiredLabel])) {
            $missingRequiredLabels[] = $requiredLabel;
        }
    }

    if (!empty($missingRequiredLabels)) {
        foreach ($missingRequiredLabels as $missingLabel) {
            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/document_categories',
                array_merge($headers, ['Prefer: return=representation']),
                [[
                    'category_key' => $toCategoryKey($missingLabel),
                    'category_name' => $missingLabel,
                    'requires_approval' => true,
                ]]
            );
        }

        $refreshCategoryResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/document_categories?select=id,category_name&order=category_name.asc&limit=300',
            $headers
        );

        if (isSuccessful($refreshCategoryResponse)) {
            $mapCategories((array)($refreshCategoryResponse['data'] ?? []));
        }
    }

    foreach ($requiredDocumentCategories as $requiredLabel) {
        if (!isset($categoriesByRequiredLabel[$requiredLabel])) {
            continue;
        }

        $documentCategories[] = $categoriesByRequiredLabel[$requiredLabel];
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
    $rawCategoryName = (string)($categoryRow['category_name'] ?? '');
    $normalizedCategory = $normalizeCategoryName($rawCategoryName);
    $mappedCategory = $requiredCategoryAliasMap[$normalizedCategory] ?? null;

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
        'category_name' => $mappedCategory ?? 'Others',
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
