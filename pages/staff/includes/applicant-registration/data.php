<?php

$registrationRows = [];
$registrationDetailPayload = null;
$dataLoadError = null;
$registrationPageSize = 10;
$registrationCurrentPage = max(1, (int)($_GET['page'] ?? 1));
$registrationHasNextPage = false;
$registrationHasPreviousPage = $registrationCurrentPage > 1;
$registrationPaginationLabel = 'Page ' . $registrationCurrentPage;
$registrationFilters = [
    'search' => trim((string)($_GET['search'] ?? '')),
    'status' => strtolower(trim((string)($_GET['status'] ?? ''))),
];
$applicantRegistrationDataStage = trim((string)($applicantRegistrationDataStage ?? 'shell'));
$registrationDetailApplicationId = trim((string)($registrationDetailApplicationId ?? ($_GET['application_id'] ?? '')));

$appendDataError = static function (string $label, array $response) use (&$dataLoadError): void {
    if (isSuccessful($response)) {
        return;
    }

    $message = $label . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $message .= ' ' . $raw;
    }

    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $message) : $message;
};

$resolveApplicantDocumentUrl = static function (?string $rawUrl) use ($supabaseUrl): string {
    $value = trim((string)$rawUrl);
    if ($value === '') {
        return '';
    }

    $localDocumentRoot = __DIR__ . '/../../../../storage/document';
    $resolveLocal = static function (string $rawPath) use ($localDocumentRoot): string {
        $normalized = str_replace('\\', '/', trim($rawPath));
        $normalized = preg_replace('#^https?://[^/]+/storage/v1/object/public/[^/]+/#i', '', $normalized);
        $normalized = preg_replace('#^storage/v1/object/public/[^/]+/#i', '', $normalized);
        $normalized = preg_replace('#^document/#i', '', ltrim((string)$normalized, '/'));

        $segments = array_values(array_filter(explode('/', (string)$normalized), static fn(string $segment): bool => $segment !== ''));
        if (empty($segments)) {
            return '';
        }

        $candidate = $localDocumentRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, implode('/', $segments));
        if (is_file($candidate)) {
            return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', $segments));
        }

        $basename = end($segments);
        if (is_string($basename) && $basename !== '') {
            $basenameCandidate = $localDocumentRoot . DIRECTORY_SEPARATOR . $basename;
            if (is_file($basenameCandidate)) {
                return '/hris-system/storage/document/' . rawurlencode($basename);
            }
        }

        return '';
    };

    $localResolved = $resolveLocal($value);
    if ($localResolved !== '') {
        return $localResolved;
    }

    if (preg_match('#^https?://#i', $value) === 1 || str_starts_with($value, '/')) {
        return $value;
    }

    if (str_starts_with($value, 'storage/v1/object/public/')) {
        return rtrim((string)$supabaseUrl, '/') . '/' . $value;
    }

    if (str_starts_with($value, 'document/')) {
        $segments = array_values(array_filter(explode('/', preg_replace('#^document/#i', '', $value)), static fn(string $segment): bool => $segment !== ''));
        return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', $segments));
    }

    return '/hris-system/storage/document/' . rawurlencode($value);
};

$documentTypeLabel = static function (string $type): string {
    return match (strtolower(trim($type))) {
        'pds' => 'PDS',
        'transcript' => 'Transcript of Records',
        'certificate' => 'Eligibility (CSC/PRC)',
        'resume' => 'WES',
        'id' => 'ID',
        default => 'Other Document',
    };
};

$screeningPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'submitted' => ['Applied', 'bg-blue-100 text-blue-800'],
        'screening' => ['Verified', 'bg-indigo-100 text-indigo-800'],
        'interview' => ['Interview', 'bg-amber-100 text-amber-800'],
        'shortlisted' => ['Evaluation', 'bg-violet-100 text-violet-800'],
        'offer' => ['For Approval', 'bg-cyan-100 text-cyan-800'],
        'hired' => ['Hired', 'bg-emerald-100 text-emerald-800'],
        'rejected', 'withdrawn' => ['Rejected', 'bg-rose-100 text-rose-800'],
        default => ['Applied', 'bg-slate-100 text-slate-700'],
    };
};

$buildApplicationBasisMap = static function (array $applicationIds) use ($supabaseUrl, $headers, $appendDataError): array {
    $basisByApplication = [];
    if (empty($applicationIds)) {
        return $basisByApplication;
    }

    $statusHistoryResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/application_status_history?select=application_id,notes,created_at'
        . '&application_id=in.' . rawurlencode('(' . implode(',', $applicationIds) . ')')
        . '&order=created_at.desc&limit=200',
        $headers
    );
    $appendDataError('Application history', $statusHistoryResponse);
    $statusHistoryRows = isSuccessful($statusHistoryResponse) ? (array)($statusHistoryResponse['data'] ?? []) : [];

    foreach ($statusHistoryRows as $historyRow) {
        $applicationId = cleanText($historyRow['application_id'] ?? null) ?? '';
        if (!isValidUuid($applicationId) || isset($basisByApplication[$applicationId])) {
            continue;
        }

        $notes = trim((string)($historyRow['notes'] ?? ''));
        if ($notes === '') {
            $basisByApplication[$applicationId] = '-';
            continue;
        }

        $parts = explode('|', $notes, 2);
        $basis = trim((string)($parts[0] ?? ''));
        $basisByApplication[$applicationId] = $basis !== '' ? $basis : '-';
    }

    return $basisByApplication;
};

$mapRegistrationRow = static function (array $application, array $basisByApplication) use ($screeningPill): ?array {
    $applicationId = cleanText($application['id'] ?? null) ?? '';
    if (!isValidUuid($applicationId)) {
        return null;
    }

    $applicantUserId = cleanText($application['applicant']['user_id'] ?? null) ?? '';
    if (!isValidUuid($applicantUserId)) {
        return null;
    }

    $statusRaw = strtolower((string)(cleanText($application['application_status'] ?? null) ?? 'submitted'));
    [$screeningLabel, $screeningClass] = $screeningPill($statusRaw);
    $applicantName = cleanText($application['applicant']['full_name'] ?? null) ?? 'Applicant';
    $applicantEmail = cleanText($application['applicant']['email'] ?? null) ?? '-';
    $postingTitle = cleanText($application['job_posting']['title'] ?? null) ?? 'Job Posting';
    $basis = (string)($basisByApplication[$applicationId] ?? 'Application submitted by applicant.');

    return [
        'id' => $applicationId,
        'application_ref_no' => cleanText($application['application_ref_no'] ?? null) ?? '-',
        'applicant_name' => $applicantName,
        'applicant_email' => $applicantEmail,
        'posting_title' => $postingTitle,
        'status_raw' => $statusRaw === 'withdrawn' ? 'rejected' : $statusRaw,
        'status_label' => $screeningLabel,
        'status_class' => $screeningClass,
        'submitted_label' => formatDateTimeForPhilippines(cleanText($application['submitted_at'] ?? null), 'M d, Y'),
        'basis' => $basis,
        'search_text' => strtolower(trim($applicantName . ' ' . $applicantEmail . ' ' . $postingTitle . ' ' . $screeningLabel . ' ' . $basis)),
    ];
};

$applyStatusFilterToUrl = static function (string $url, string $status): string {
    if ($status === '') {
        return $url;
    }

    if ($status === 'rejected') {
        return $url . '&application_status=in.' . rawurlencode('(rejected,withdrawn)');
    }

    return $url . '&application_status=eq.' . rawurlencode($status);
};

if ($applicantRegistrationDataStage === 'list') {
    $offset = ($registrationCurrentPage - 1) * $registrationPageSize;
    $listBaseUrl = $supabaseUrl
        . '/rest/v1/applications?select=id,application_ref_no,application_status,submitted_at,job_posting:job_postings(title),applicant:applicant_profiles(user_id,full_name,email)'
        . '&order=submitted_at.desc';
    $listBaseUrl = $applyStatusFilterToUrl($listBaseUrl, $registrationFilters['status']);

    if ($registrationFilters['search'] === '') {
        $applicationResponse = apiRequest(
            'GET',
            $listBaseUrl . '&limit=' . ($registrationPageSize + 1) . '&offset=' . $offset,
            $headers
        );
        $appendDataError('Applications queue', $applicationResponse);
        $applicationRows = isSuccessful($applicationResponse) ? (array)($applicationResponse['data'] ?? []) : [];
        $registrationHasNextPage = count($applicationRows) > $registrationPageSize;
        $applicationRows = array_slice($applicationRows, 0, $registrationPageSize);
    } else {
        $applicationResponse = apiRequest(
            'GET',
            $listBaseUrl . '&limit=250',
            $headers
        );
        $appendDataError('Applications queue', $applicationResponse);
        $allApplicationRows = isSuccessful($applicationResponse) ? (array)($applicationResponse['data'] ?? []) : [];

        $normalizedSearch = strtolower(trim($registrationFilters['search']));
        $filteredApplications = [];
        foreach ($allApplicationRows as $applicationRow) {
            $statusRaw = strtolower((string)(cleanText($applicationRow['application_status'] ?? null) ?? 'submitted'));
            [$screeningLabel] = $screeningPill($statusRaw);
            $searchText = strtolower(trim(
                (string)(cleanText($applicationRow['applicant']['full_name'] ?? null) ?? '') . ' '
                . (string)(cleanText($applicationRow['applicant']['email'] ?? null) ?? '') . ' '
                . (string)(cleanText($applicationRow['job_posting']['title'] ?? null) ?? '') . ' '
                . $screeningLabel
            ));

            if ($normalizedSearch === '' || str_contains($searchText, $normalizedSearch)) {
                $filteredApplications[] = $applicationRow;
            }
        }

        $registrationHasNextPage = count($filteredApplications) > ($offset + $registrationPageSize);
        $applicationRows = array_slice($filteredApplications, $offset, $registrationPageSize);
    }

    $applicationIds = [];
    foreach ($applicationRows as $applicationRow) {
        $applicationId = cleanText($applicationRow['id'] ?? null) ?? '';
        if (isValidUuid($applicationId)) {
            $applicationIds[] = $applicationId;
        }
    }

    $basisByApplication = $buildApplicationBasisMap($applicationIds);
    foreach ($applicationRows as $applicationRow) {
        $mappedRow = $mapRegistrationRow($applicationRow, $basisByApplication);
        if (is_array($mappedRow)) {
            $registrationRows[] = $mappedRow;
        }
    }

    $registrationHasPreviousPage = $registrationCurrentPage > 1;
    $registrationPaginationLabel = empty($registrationRows)
        ? 'No results'
        : 'Page ' . $registrationCurrentPage;
}

if ($applicantRegistrationDataStage === 'detail') {
    if (!isValidUuid($registrationDetailApplicationId)) {
        $dataLoadError = 'Invalid applicant registration record selected.';
    } else {
        $applicationResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/applications?select=id,application_ref_no,application_status,submitted_at,job_posting:job_postings(title),applicant:applicant_profiles(user_id,full_name,email,mobile_no,current_address)'
            . '&id=eq.' . rawurlencode($registrationDetailApplicationId)
            . '&limit=1',
            $headers
        );
        $appendDataError('Applicant registration detail', $applicationResponse);
        $applicationRow = isSuccessful($applicationResponse) ? (array)($applicationResponse['data'][0] ?? []) : [];

        if (!is_array($applicationRow) || empty($applicationRow)) {
            $dataLoadError = $dataLoadError ?: 'Applicant registration detail was not found.';
        } else {
            $basisByApplication = $buildApplicationBasisMap([$registrationDetailApplicationId]);
            $documents = [];
            $documentResponse = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/application_documents?select=id,application_id,document_type,file_url,file_name,mime_type,uploaded_at'
                . '&application_id=eq.' . rawurlencode($registrationDetailApplicationId)
                . '&order=uploaded_at.desc&limit=50',
                $headers
            );
            $appendDataError('Application documents', $documentResponse);
            $documentRows = isSuccessful($documentResponse) ? (array)($documentResponse['data'] ?? []) : [];

            foreach ($documentRows as $document) {
                $documentId = cleanText($document['id'] ?? null) ?? '';
                $fileUrl = $resolveApplicantDocumentUrl(cleanText($document['file_url'] ?? null) ?? '');
                $previewUrl = $fileUrl;
                $downloadUrl = $fileUrl;
                if (isValidUuid($documentId)) {
                    $previewUrl = '/hris-system/pages/staff/document-preview.php?source=applicant&document_id=' . rawurlencode($documentId) . '&return_to=' . rawurlencode('/hris-system/pages/staff/applicant-registration.php');
                    $downloadUrl = '/hris-system/pages/staff/applicant-document.php?document_id=' . rawurlencode($documentId) . '&download=1';
                }

                $documents[] = [
                    'id' => $documentId,
                    'document_type' => cleanText($document['document_type'] ?? null) ?? 'other',
                    'document_label' => $documentTypeLabel((string)(cleanText($document['document_type'] ?? null) ?? 'other')),
                    'file_name' => cleanText($document['file_name'] ?? null) ?? 'document',
                    'file_url' => $previewUrl,
                    'preview_url' => $previewUrl,
                    'download_url' => $downloadUrl,
                    'mime_type' => cleanText($document['mime_type'] ?? null) ?? '',
                    'uploaded_label' => formatDateTimeForPhilippines(cleanText($document['uploaded_at'] ?? null), 'M d, Y'),
                    'is_available' => $previewUrl !== '',
                ];
            }

            $statusRaw = strtolower((string)(cleanText($applicationRow['application_status'] ?? null) ?? 'submitted'));
            [$screeningLabel] = $screeningPill($statusRaw);
            $registrationDetailPayload = [
                'application_id' => $registrationDetailApplicationId,
                'application_ref_no' => cleanText($applicationRow['application_ref_no'] ?? null) ?? '-',
                'applicant_name' => cleanText($applicationRow['applicant']['full_name'] ?? null) ?? 'Applicant',
                'applicant_email' => cleanText($applicationRow['applicant']['email'] ?? null) ?? '-',
                'applicant_mobile' => cleanText($applicationRow['applicant']['mobile_no'] ?? null) ?? '-',
                'applicant_address' => cleanText($applicationRow['applicant']['current_address'] ?? null) ?? '-',
                'posting_title' => cleanText($applicationRow['job_posting']['title'] ?? null) ?? 'Job Posting',
                'submitted_label' => formatDateTimeForPhilippines(cleanText($applicationRow['submitted_at'] ?? null), 'M d, Y'),
                'screening_label' => $screeningLabel,
                'basis' => (string)($basisByApplication[$registrationDetailApplicationId] ?? 'Application submitted by applicant.'),
                'documents' => $documents,
            ];
        }
    }
}