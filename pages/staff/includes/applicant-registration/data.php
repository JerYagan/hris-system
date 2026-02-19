<?php

$registrationRows = [];
$dataLoadError = null;

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

$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
$postingScopeFilter = (!$isAdminScope && isValidUuid((string)$staffOfficeId))
    ? '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
    : '';

$postingResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/job_postings?select=id,title' . $postingScopeFilter . '&limit=1000',
    $headers
);
$appendDataError('Job postings scope', $postingResponse);
$postingRows = isSuccessful($postingResponse) ? (array)($postingResponse['data'] ?? []) : [];

$postingIds = [];
$postingTitleById = [];
foreach ($postingRows as $posting) {
    $postingId = cleanText($posting['id'] ?? null);
    if ($postingId === null || !isValidUuid($postingId)) {
        continue;
    }

    $postingIds[] = $postingId;
    $postingTitleById[$postingId] = cleanText($posting['title'] ?? null) ?? 'Job Posting';
}

$applicationRows = [];
if (!empty($postingIds)) {
    $applicationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/applications?select=id,application_ref_no,application_status,submitted_at,updated_at,job_posting_id,applicant:applicant_profiles(full_name,email)'
        . '&job_posting_id=in.' . rawurlencode('(' . implode(',', $postingIds) . ')')
        . '&application_status=in.' . rawurlencode('(submitted,screening,shortlisted,interview,offer)')
        . '&order=submitted_at.desc&limit=2000',
        $headers
    );
    $appendDataError('Applications queue', $applicationResponse);
    $applicationRows = isSuccessful($applicationResponse) ? (array)($applicationResponse['data'] ?? []) : [];
}

$applicationIds = [];
foreach ($applicationRows as $row) {
    $id = cleanText($row['id'] ?? null);
    if ($id === null || !isValidUuid($id)) {
        continue;
    }
    $applicationIds[] = $id;
}

$documentCountByApplication = [];
if (!empty($applicationIds)) {
    $documentResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/application_documents?select=application_id,document_type&id=not.is.null&application_id=in.' . rawurlencode('(' . implode(',', $applicationIds) . ')') . '&limit=5000',
        $headers
    );
    $appendDataError('Application documents', $documentResponse);
    $documentRows = isSuccessful($documentResponse) ? (array)($documentResponse['data'] ?? []) : [];

    foreach ($documentRows as $document) {
        $applicationId = cleanText($document['application_id'] ?? null);
        if ($applicationId === null || !isValidUuid($applicationId)) {
            continue;
        }

        if (!isset($documentCountByApplication[$applicationId])) {
            $documentCountByApplication[$applicationId] = 0;
        }
        $documentCountByApplication[$applicationId]++;
    }
}

$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'screening' => ['Screening', 'bg-blue-100 text-blue-800'],
        'shortlisted' => ['Shortlisted', 'bg-indigo-100 text-indigo-800'],
        'interview' => ['Interview', 'bg-amber-100 text-amber-800'],
        'offer' => ['Offer', 'bg-purple-100 text-purple-800'],
        default => ['Submitted', 'bg-yellow-100 text-yellow-800'],
    };
};

foreach ($applicationRows as $application) {
    $applicationId = cleanText($application['id'] ?? null) ?? '';
    if (!isValidUuid($applicationId)) {
        continue;
    }

    $postingId = cleanText($application['job_posting_id'] ?? null) ?? '';
    $statusRaw = strtolower((string)(cleanText($application['application_status'] ?? null) ?? 'submitted'));
    [$statusLabel, $statusClass] = $statusPill($statusRaw);

    $applicantName = cleanText($application['applicant']['full_name'] ?? null) ?? 'Applicant';
    $applicantEmail = cleanText($application['applicant']['email'] ?? null) ?? '-';

    $registrationRows[] = [
        'id' => $applicationId,
        'application_ref_no' => cleanText($application['application_ref_no'] ?? null) ?? '-',
        'applicant_name' => $applicantName,
        'applicant_email' => $applicantEmail,
        'posting_title' => $postingTitleById[$postingId] ?? 'Job Posting',
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'submitted_label' => formatDateTimeForPhilippines(cleanText($application['submitted_at'] ?? null), 'M d, Y'),
        'document_count' => (int)($documentCountByApplication[$applicationId] ?? 0),
        'search_text' => strtolower(trim($applicantName . ' ' . $applicantEmail . ' ' . ($postingTitleById[$postingId] ?? '') . ' ' . $statusLabel)),
    ];
}