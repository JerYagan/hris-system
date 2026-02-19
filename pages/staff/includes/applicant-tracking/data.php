<?php

$trackingRows = [];
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
    $supabaseUrl . '/rest/v1/job_postings?select=id,title,office_id' . $postingScopeFilter . '&limit=1000',
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
        . '&order=updated_at.desc&limit=2000',
        $headers
    );
    $appendDataError('Applications', $applicationResponse);
    $applicationRows = isSuccessful($applicationResponse) ? (array)($applicationResponse['data'] ?? []) : [];
}

$applicationIds = [];
foreach ($applicationRows as $row) {
    $applicationId = cleanText($row['id'] ?? null);
    if ($applicationId === null || !isValidUuid($applicationId)) {
        continue;
    }
    $applicationIds[] = $applicationId;
}

$latestInterviewByApplication = [];
if (!empty($applicationIds)) {
    $interviewResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/application_interviews?select=application_id,scheduled_at,interview_stage,result'
        . '&application_id=in.' . rawurlencode('(' . implode(',', $applicationIds) . ')')
        . '&order=scheduled_at.desc&limit=3000',
        $headers
    );
    $appendDataError('Application interviews', $interviewResponse);
    $interviewRows = isSuccessful($interviewResponse) ? (array)($interviewResponse['data'] ?? []) : [];

    foreach ($interviewRows as $interview) {
        $applicationId = cleanText($interview['application_id'] ?? null) ?? '';
        if ($applicationId === '' || isset($latestInterviewByApplication[$applicationId])) {
            continue;
        }

        $latestInterviewByApplication[$applicationId] = [
            'scheduled_at' => cleanText($interview['scheduled_at'] ?? null) ?? '',
            'interview_stage' => cleanText($interview['interview_stage'] ?? null) ?? '',
            'result' => cleanText($interview['result'] ?? null) ?? '',
        ];
    }
}

$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'screening' => ['Screening', 'bg-blue-100 text-blue-800'],
        'shortlisted' => ['Shortlisted', 'bg-indigo-100 text-indigo-800'],
        'interview' => ['Interview', 'bg-amber-100 text-amber-800'],
        'offer' => ['Offer', 'bg-purple-100 text-purple-800'],
        'hired' => ['Hired', 'bg-emerald-100 text-emerald-800'],
        'rejected' => ['Rejected', 'bg-rose-100 text-rose-800'],
        'withdrawn' => ['Withdrawn', 'bg-slate-200 text-slate-700'],
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
    $postingTitle = $postingTitleById[$postingId] ?? 'Job Posting';

    $latestInterview = $latestInterviewByApplication[$applicationId] ?? null;
    $interviewMeta = '-';
    if (is_array($latestInterview)) {
        $stage = ucwords(str_replace('_', ' ', strtolower((string)($latestInterview['interview_stage'] ?? ''))));
        $when = formatDateTimeForPhilippines(cleanText($latestInterview['scheduled_at'] ?? null), 'M d, Y');
        $interviewMeta = trim($stage . ($when !== '-' ? ' · ' . $when : ''));
    }

    $trackingRows[] = [
        'id' => $applicationId,
        'application_ref_no' => cleanText($application['application_ref_no'] ?? null) ?? '-',
        'applicant_name' => $applicantName,
        'applicant_email' => $applicantEmail,
        'posting_title' => $postingTitle,
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'submitted_label' => formatDateTimeForPhilippines(cleanText($application['submitted_at'] ?? null), 'M d, Y'),
        'updated_label' => formatDateTimeForPhilippines(cleanText($application['updated_at'] ?? null), 'M d, Y'),
        'interview_meta' => $interviewMeta,
        'search_text' => strtolower(trim($applicantName . ' ' . $postingTitle . ' ' . $statusLabel . ' ' . $applicantEmail)),
    ];
}