<?php

$registrationRows = [];
$registrationViewById = [];
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

$postingResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/job_postings?select=id,title&limit=2000',
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
        . '/rest/v1/applications?select=id,application_ref_no,application_status,submitted_at,updated_at,job_posting_id,applicant:applicant_profiles(user_id,full_name,email,mobile_no,current_address)'
        . '&job_posting_id=in.' . rawurlencode('(' . implode(',', $postingIds) . ')')
        . '&order=submitted_at.desc&limit=2000',
        $headers
    );
    $appendDataError('Applications queue', $applicationResponse);
    $applicationRows = isSuccessful($applicationResponse) ? (array)($applicationResponse['data'] ?? []) : [];
}

$applicationIds = [];
$applicantUserIds = [];
foreach ($applicationRows as $row) {
    $id = cleanText($row['id'] ?? null);
    if ($id === null || !isValidUuid($id)) {
        continue;
    }
    $applicationIds[] = $id;

    $applicantUserId = cleanText($row['applicant']['user_id'] ?? null) ?? '';
    if (isValidUuid($applicantUserId)) {
        $applicantUserIds[strtolower($applicantUserId)] = $applicantUserId;
    }
}



$basisByApplication = [];
$documentsByApplication = [];
if (!empty($applicationIds)) {
    $statusHistoryResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/application_status_history?select=application_id,notes,created_at'
        . '&application_id=in.' . rawurlencode('(' . implode(',', $applicationIds) . ')')
        . '&order=created_at.desc&limit=10000',
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

    $documentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/application_documents?select=application_id,document_type,file_url,file_name,mime_type,uploaded_at'
        . '&application_id=in.' . rawurlencode('(' . implode(',', $applicationIds) . ')')
        . '&order=uploaded_at.desc&limit=10000',
        $headers
    );
    $appendDataError('Application documents', $documentResponse);
    $documentRows = isSuccessful($documentResponse) ? (array)($documentResponse['data'] ?? []) : [];

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

    foreach ($documentRows as $document) {
        $applicationId = cleanText($document['application_id'] ?? null);
        if ($applicationId === null || !isValidUuid($applicationId)) {
            continue;
        }

        if (!isset($documentsByApplication[$applicationId])) {
            $documentsByApplication[$applicationId] = [];
        }

        $fileUrl = trim((string)(cleanText($document['file_url'] ?? null) ?? ''));
        $documentsByApplication[$applicationId][] = [
            'document_type' => cleanText($document['document_type'] ?? null) ?? 'other',
            'document_label' => $documentTypeLabel((string)(cleanText($document['document_type'] ?? null) ?? 'other')),
            'file_name' => cleanText($document['file_name'] ?? null) ?? 'document',
            'file_url' => $fileUrl,
            'mime_type' => cleanText($document['mime_type'] ?? null) ?? '',
            'uploaded_label' => formatDateTimeForPhilippines(cleanText($document['uploaded_at'] ?? null), 'M d, Y'),
            'is_available' => $fileUrl !== '',
        ];
    }
}

$screeningPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match (true) {
        in_array($key, ['shortlisted', 'offer', 'hired'], true) => ['Verified', 'bg-emerald-100 text-emerald-800'],
        in_array($key, ['submitted', 'screening', 'interview'], true) => ['For Review', 'bg-blue-100 text-blue-800'],
        in_array($key, ['rejected', 'withdrawn'], true) => ['Disqualified', 'bg-rose-100 text-rose-800'],
        default => ['For Review', 'bg-slate-100 text-slate-700'],
    };
};

foreach ($applicationRows as $application) {
    $applicationId = cleanText($application['id'] ?? null) ?? '';
    if (!isValidUuid($applicationId)) {
        continue;
    }

    $postingId = cleanText($application['job_posting_id'] ?? null) ?? '';
    $statusRaw = strtolower((string)(cleanText($application['application_status'] ?? null) ?? 'submitted'));
    [$screeningLabel, $screeningClass] = $screeningPill($statusRaw);

    $applicantName = cleanText($application['applicant']['full_name'] ?? null) ?? 'Applicant';
    $applicantEmail = cleanText($application['applicant']['email'] ?? null) ?? '-';
    $applicantUserId = cleanText($application['applicant']['user_id'] ?? null) ?? '';
    if (!isValidUuid($applicantUserId)) {
        continue;
    }
    $applicantMobile = cleanText($application['applicant']['mobile_no'] ?? null) ?? '-';
    $applicantAddress = cleanText($application['applicant']['current_address'] ?? null) ?? '-';
    $submittedLabel = formatDateTimeForPhilippines(cleanText($application['submitted_at'] ?? null), 'M d, Y');
    $postingTitle = $postingTitleById[$postingId] ?? 'Job Posting';
    $basis = (string)($basisByApplication[$applicationId] ?? 'Application submitted by applicant.');
    $documents = (array)($documentsByApplication[$applicationId] ?? []);

    $registrationRows[] = [
        'id' => $applicationId,
        'application_ref_no' => cleanText($application['application_ref_no'] ?? null) ?? '-',
        'applicant_name' => $applicantName,
        'applicant_email' => $applicantEmail,
        'posting_title' => $postingTitle,
        'status_raw' => $statusRaw,
        'status_label' => $screeningLabel,
        'status_class' => $screeningClass,
        'submitted_label' => $submittedLabel,
        'basis' => $basis,
        'search_text' => strtolower(trim($applicantName . ' ' . $applicantEmail . ' ' . $postingTitle . ' ' . $screeningLabel . ' ' . $basis)),
    ];

    $registrationViewById[$applicationId] = [
        'application_id' => $applicationId,
        'application_ref_no' => cleanText($application['application_ref_no'] ?? null) ?? '-',
        'applicant_name' => $applicantName,
        'applicant_email' => $applicantEmail,
        'applicant_mobile' => $applicantMobile,
        'applicant_address' => $applicantAddress,
        'posting_title' => $postingTitle,
        'submitted_label' => $submittedLabel,
        'screening_label' => $screeningLabel,
        'basis' => $basis,
        'documents' => $documents,
    ];
}