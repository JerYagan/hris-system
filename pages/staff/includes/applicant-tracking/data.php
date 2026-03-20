<?php

$trackingPostingRows = [];
$trackingApplicantRows = [];
$trackingDetailPayload = null;
$dataLoadError = null;

$trackingDataStage = trim((string)($trackingDataStage ?? 'shell'));
$trackingPostingPageSize = 8;
$trackingPostingPage = max(1, (int)($_GET['tracking_postings_page'] ?? 1));
$trackingPostingPagination = [
    'page' => $trackingPostingPage,
    'has_prev' => $trackingPostingPage > 1,
    'has_next' => false,
    'prev_page' => max(1, $trackingPostingPage - 1),
    'next_page' => $trackingPostingPage + 1,
    'label' => 'Page ' . $trackingPostingPage,
];

$trackingApplicantPageSize = 10;
$trackingApplicantPage = max(1, (int)($_GET['tracking_page'] ?? 1));
$trackingApplicantFilters = [
    'posting_id' => trim((string)($_GET['posting_id'] ?? '')),
    'search' => trim((string)($_GET['search'] ?? '')),
    'status' => strtolower(trim((string)($_GET['status'] ?? ''))),
];
$trackingApplicantPagination = [
    'page' => $trackingApplicantPage,
    'has_prev' => $trackingApplicantPage > 1,
    'has_next' => false,
    'prev_page' => max(1, $trackingApplicantPage - 1),
    'next_page' => $trackingApplicantPage + 1,
    'label' => 'Page ' . $trackingApplicantPage,
];
$staffOfficeId = $staffOfficeId ?? null;
$staffRoleKey = $staffRoleKey ?? null;
$trackingSelectedPostingId = $trackingApplicantFilters['posting_id'];
$trackingSelectedPostingTitle = 'All Postings';
$trackingSelectedApplicationId = trim((string)($trackingSelectedApplicationId ?? ($_GET['application_id'] ?? '')));

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

$trackingStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'hired', 'offer', 'shortlisted' => [ucfirst($key), 'bg-emerald-100 text-emerald-800'],
        'submitted', 'screening', 'interview' => [ucfirst($key), 'bg-amber-100 text-amber-800'],
        'rejected', 'withdrawn' => [ucfirst($key), 'bg-rose-100 text-rose-800'],
        default => [ucfirst($key !== '' ? $key : 'submitted'), 'bg-slate-100 text-slate-700'],
    };
};

$trackingStageLabel = static function (string $status): string {
    return match (strtolower(trim($status))) {
        'submitted' => 'Applied',
        'screening' => 'Verified',
        'shortlisted' => 'Evaluation',
        'interview' => 'Interview',
        'offer' => 'For Approval',
        'hired' => 'Hired',
        'rejected' => 'Rejected',
        'withdrawn' => 'Withdrawn',
        default => 'Applied',
    };
};

$feedbackDecisionLabel = static function (string $decision): string {
    return match (strtolower(trim($decision))) {
        'for_next_step' => 'For Next Step',
        'on_hold' => 'On Hold',
        'rejected' => 'Rejected',
        'hired' => 'Hired',
        default => '-',
    };
};

$documentTypeLabel = static function (string $type): string {
    return match (strtolower(trim($type))) {
        'pds' => 'PDS',
        'transcript' => 'Transcript of Records',
        'certificate' => 'Eligibility (CSC/PRC)',
        'resume' => 'Resume/CV',
        'id' => 'ID',
        default => 'Other Document',
    };
};

$loadScopedPostingState = static function () use ($supabaseUrl, $headers, $appendDataError, $staffOfficeId, $staffRoleKey): array {
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $response = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/job_postings?select=id,title,office_id,posting_status,open_date,close_date,updated_at,office:offices(office_name),position:job_positions(position_title)'
        . '&order=updated_at.desc&limit=400',
        $headers
    );
    $appendDataError('Job postings', $response);
    $rows = isSuccessful($response) ? (array)($response['data'] ?? []) : [];

    $isAdminScope = strtolower((string)$staffRoleKey) === 'admin';
    $scopedRows = [];
    $scopedIds = [];
    $postingMap = [];

    foreach ($rows as $row) {
        $postingId = cleanText($row['id'] ?? null) ?? '';
        if (!isValidUuid($postingId)) {
            continue;
        }

        $officeId = cleanText($row['office_id'] ?? null) ?? '';
        if (!$isAdminScope && isValidUuid((string)$staffOfficeId) && strtolower($officeId) !== strtolower((string)$staffOfficeId)) {
            continue;
        }

        $status = strtolower((string)(cleanText($row['posting_status'] ?? null) ?? 'published'));
        if ($status === 'archived') {
            continue;
        }

        $normalizedRow = [
            'id' => $postingId,
            'title' => cleanText($row['title'] ?? null) ?? 'Job Posting',
            'office_name' => cleanText($row['office']['office_name'] ?? null) ?? '-',
            'position_title' => cleanText($row['position']['position_title'] ?? null) ?? 'Position',
            'posting_status' => $status,
            'open_date_label' => formatDateTimeForPhilippines(cleanText($row['open_date'] ?? null), 'M d, Y'),
            'close_date_label' => formatDateTimeForPhilippines(cleanText($row['close_date'] ?? null), 'M d, Y'),
        ];

        $scopedRows[] = $normalizedRow;
        $scopedIds[] = $postingId;
        $postingMap[strtolower($postingId)] = $normalizedRow;
    }

    $cache = [
        'rows' => $scopedRows,
        'ids' => $scopedIds,
        'map' => $postingMap,
    ];

    return $cache;
};

$mapApplicantRow = static function (array $application) use ($trackingStatusPill, $trackingStageLabel): ?array {
    $applicationId = cleanText($application['id'] ?? null) ?? '';
    if (!isValidUuid($applicationId)) {
        return null;
    }

    $statusRaw = strtolower((string)(cleanText($application['application_status'] ?? null) ?? 'submitted'));
    [$statusLabel, $statusClass] = $trackingStatusPill($statusRaw);

    $applicantName = cleanText($application['applicant']['full_name'] ?? null) ?? 'Applicant';
    $applicantEmail = cleanText($application['applicant']['email'] ?? null) ?? '-';
    $postingTitle = cleanText($application['job_posting']['title'] ?? null) ?? 'Job Posting';
    $stageLabel = $trackingStageLabel($statusRaw);

    return [
        'id' => $applicationId,
        'application_ref_no' => cleanText($application['application_ref_no'] ?? null) ?? '-',
        'applicant_name' => $applicantName,
        'applicant_email' => $applicantEmail,
        'posting_title' => $postingTitle,
        'submitted_label' => formatDateTimeForPhilippines(cleanText($application['submitted_at'] ?? null), 'M d, Y'),
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'status_filter' => strtolower($statusLabel),
        'current_stage_label' => $stageLabel,
        'search_text' => strtolower(trim($applicantName . ' ' . $applicantEmail . ' ' . $postingTitle . ' ' . $statusLabel . ' ' . $stageLabel)),
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

if ($trackingDataStage === 'postings') {
    $scopedPostingState = $loadScopedPostingState();
    $postingRows = (array)($scopedPostingState['rows'] ?? []);
    $offset = ($trackingPostingPage - 1) * $trackingPostingPageSize;
    $visiblePostingRows = array_slice($postingRows, $offset, $trackingPostingPageSize + 1);
    $trackingPostingPagination['has_next'] = count($visiblePostingRows) > $trackingPostingPageSize;
    $visiblePostingRows = array_slice($visiblePostingRows, 0, $trackingPostingPageSize);
    $trackingPostingPagination['prev_page'] = max(1, $trackingPostingPage - 1);
    $trackingPostingPagination['next_page'] = $trackingPostingPage + 1;
    $trackingPostingPagination['label'] = empty($visiblePostingRows) ? 'No postings found' : 'Page ' . $trackingPostingPage;

    $visiblePostingIds = [];
    foreach ($visiblePostingRows as $row) {
        $postingId = cleanText($row['id'] ?? null) ?? '';
        if (isValidUuid($postingId)) {
            $visiblePostingIds[] = $postingId;
        }
    }

    $countsByPosting = [];
    if (!empty($visiblePostingIds)) {
        $applicationResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/applications?select=id,job_posting_id,application_status'
            . '&job_posting_id=in.' . rawurlencode('(' . implode(',', $visiblePostingIds) . ')')
            . '&limit=2000',
            $headers
        );
        $appendDataError('Posting application counts', $applicationResponse);
        $applicationRows = isSuccessful($applicationResponse) ? (array)($applicationResponse['data'] ?? []) : [];

        foreach ($applicationRows as $applicationRow) {
            $postingId = cleanText($applicationRow['job_posting_id'] ?? null) ?? '';
            if (!isValidUuid($postingId)) {
                continue;
            }

            if (!isset($countsByPosting[$postingId])) {
                $countsByPosting[$postingId] = ['total' => 0, 'active' => 0];
            }

            $countsByPosting[$postingId]['total'] += 1;
            $statusRaw = strtolower((string)(cleanText($applicationRow['application_status'] ?? null) ?? 'submitted'));
            if (in_array($statusRaw, ['submitted', 'screening', 'shortlisted', 'interview', 'offer'], true)) {
                $countsByPosting[$postingId]['active'] += 1;
            }
        }
    }

    foreach ($visiblePostingRows as $row) {
        $postingId = (string)($row['id'] ?? '');
        $trackingPostingRows[] = [
            'id' => $postingId,
            'title' => (string)($row['title'] ?? 'Job Posting'),
            'office_name' => (string)($row['office_name'] ?? '-'),
            'position_title' => (string)($row['position_title'] ?? 'Position'),
            'open_date_label' => (string)($row['open_date_label'] ?? '-'),
            'close_date_label' => (string)($row['close_date_label'] ?? '-'),
            'applications_total' => (int)($countsByPosting[$postingId]['total'] ?? 0),
            'applications_active' => (int)($countsByPosting[$postingId]['active'] ?? 0),
            'is_selected' => strtolower($trackingSelectedPostingId) === strtolower($postingId),
        ];
    }
}

if ($trackingDataStage === 'applicants') {
    $scopedPostingState = $loadScopedPostingState();
    $scopedPostingIds = (array)($scopedPostingState['ids'] ?? []);
    $postingMap = (array)($scopedPostingState['map'] ?? []);

    if (!empty($trackingSelectedPostingId) && !in_array($trackingSelectedPostingId, $scopedPostingIds, true)) {
        $trackingSelectedPostingId = '';
        $trackingApplicantFilters['posting_id'] = '';
    }

    if ($trackingSelectedPostingId !== '' && isset($postingMap[strtolower($trackingSelectedPostingId)])) {
        $trackingSelectedPostingTitle = (string)($postingMap[strtolower($trackingSelectedPostingId)]['title'] ?? 'Selected Posting');
    }

    if (!empty($scopedPostingIds)) {
        $offset = ($trackingApplicantPage - 1) * $trackingApplicantPageSize;
        $baseUrl = $supabaseUrl
            . '/rest/v1/applications?select=id,application_ref_no,application_status,submitted_at,job_posting_id,job_posting:job_postings(title),applicant:applicant_profiles(full_name,email)'
            . '&job_posting_id=in.' . rawurlencode('(' . implode(',', $scopedPostingIds) . ')')
            . '&order=submitted_at.desc';

        if ($trackingSelectedPostingId !== '') {
            $baseUrl .= '&job_posting_id=eq.' . rawurlencode($trackingSelectedPostingId);
        }
        $baseUrl = $applyStatusFilterToUrl($baseUrl, $trackingApplicantFilters['status']);

        if ($trackingApplicantFilters['search'] === '') {
            $applicationResponse = apiRequest(
                'GET',
                $baseUrl . '&limit=' . ($trackingApplicantPageSize + 1) . '&offset=' . $offset,
                $headers
            );
            $appendDataError('Applicant tracking queue', $applicationResponse);
            $applicationRows = isSuccessful($applicationResponse) ? (array)($applicationResponse['data'] ?? []) : [];
            $trackingApplicantPagination['has_next'] = count($applicationRows) > $trackingApplicantPageSize;
            $applicationRows = array_slice($applicationRows, 0, $trackingApplicantPageSize);
        } else {
            $applicationResponse = apiRequest(
                'GET',
                $baseUrl . '&limit=250',
                $headers
            );
            $appendDataError('Applicant tracking queue', $applicationResponse);
            $allApplicationRows = isSuccessful($applicationResponse) ? (array)($applicationResponse['data'] ?? []) : [];
            $normalizedSearch = strtolower(trim($trackingApplicantFilters['search']));
            $filteredRows = [];

            foreach ($allApplicationRows as $applicationRow) {
                $mappedRow = $mapApplicantRow($applicationRow);
                if (!is_array($mappedRow)) {
                    continue;
                }

                if ($normalizedSearch === '' || str_contains((string)($mappedRow['search_text'] ?? ''), $normalizedSearch)) {
                    $filteredRows[] = $mappedRow;
                }
            }

            $trackingApplicantPagination['has_next'] = count($filteredRows) > ($offset + $trackingApplicantPageSize);
            $trackingApplicantRows = array_slice($filteredRows, $offset, $trackingApplicantPageSize);
            $applicationRows = [];
        }

        if (!empty($applicationRows)) {
            foreach ($applicationRows as $applicationRow) {
                $mappedRow = $mapApplicantRow($applicationRow);
                if (is_array($mappedRow)) {
                    $trackingApplicantRows[] = $mappedRow;
                }
            }
        }
    }

    $trackingApplicantPagination['has_prev'] = $trackingApplicantPage > 1;
    $trackingApplicantPagination['prev_page'] = max(1, $trackingApplicantPage - 1);
    $trackingApplicantPagination['next_page'] = $trackingApplicantPage + 1;
    $trackingApplicantPagination['label'] = empty($trackingApplicantRows) ? 'No applicants found' : 'Page ' . $trackingApplicantPage;
}

if ($trackingDataStage === 'detail') {
    $scopedPostingState = $loadScopedPostingState();
    $scopedPostingIds = (array)($scopedPostingState['ids'] ?? []);

    if (!isValidUuid($trackingSelectedApplicationId)) {
        $dataLoadError = 'Invalid applicant tracking record selected.';
    } else {
        $applicationResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/applications?select=id,application_ref_no,application_status,submitted_at,updated_at,job_posting_id,job_posting:job_postings(title),applicant:applicant_profiles(user_id,full_name,email,mobile_no,current_address)'
            . '&id=eq.' . rawurlencode($trackingSelectedApplicationId)
            . '&limit=1',
            $headers
        );
        $appendDataError('Applicant tracking detail', $applicationResponse);
        $applicationRow = isSuccessful($applicationResponse) ? (array)($applicationResponse['data'][0] ?? []) : [];

        if (empty($applicationRow)) {
            $dataLoadError = $dataLoadError ?: 'Applicant tracking detail was not found.';
        } else {
            $postingId = cleanText($applicationRow['job_posting_id'] ?? null) ?? '';
            if (!isValidUuid($postingId) || !in_array($postingId, $scopedPostingIds, true)) {
                $dataLoadError = 'The selected applicant is outside your tracking scope.';
            } else {
                $documentResponse = apiRequest(
                    'GET',
                    $supabaseUrl
                    . '/rest/v1/application_documents?select=id,document_type,file_url,file_name,mime_type,uploaded_at'
                    . '&application_id=eq.' . rawurlencode($trackingSelectedApplicationId)
                    . '&order=uploaded_at.desc&limit=50',
                    $headers
                );
                $appendDataError('Application documents', $documentResponse);
                $documentRows = isSuccessful($documentResponse) ? (array)($documentResponse['data'] ?? []) : [];

                $interviewResponse = apiRequest(
                    'GET',
                    $supabaseUrl
                    . '/rest/v1/application_interviews?select=id,scheduled_at,interview_stage,result,remarks,score,interviewer:user_accounts(email)'
                    . '&application_id=eq.' . rawurlencode($trackingSelectedApplicationId)
                    . '&order=scheduled_at.desc&limit=50',
                    $headers
                );
                $appendDataError('Application interviews', $interviewResponse);
                $interviewRows = isSuccessful($interviewResponse) ? (array)($interviewResponse['data'] ?? []) : [];

                $feedbackResponse = apiRequest(
                    'GET',
                    $supabaseUrl
                    . '/rest/v1/application_feedback?select=id,decision,feedback_text,provided_at,provider:user_accounts(email)'
                    . '&application_id=eq.' . rawurlencode($trackingSelectedApplicationId)
                    . '&order=provided_at.desc&limit=50',
                    $headers
                );
                $appendDataError('Application feedback', $feedbackResponse);
                $feedbackRows = isSuccessful($feedbackResponse) ? (array)($feedbackResponse['data'] ?? []) : [];

                $documents = [];
                foreach ($documentRows as $documentRow) {
                    $documentId = cleanText($documentRow['id'] ?? null) ?? '';
                    $resolvedUrl = $resolveApplicantDocumentUrl(cleanText($documentRow['file_url'] ?? null) ?? '');
                    $previewUrl = $resolvedUrl;
                    $downloadUrl = $resolvedUrl;
                    if (isValidUuid($documentId)) {
                        $previewUrl = '/hris-system/pages/staff/document-preview.php?source=applicant&document_id=' . rawurlencode($documentId) . '&return_to=' . rawurlencode('/hris-system/pages/staff/applicant-tracking.php');
                        $downloadUrl = '/hris-system/pages/staff/applicant-document.php?document_id=' . rawurlencode($documentId) . '&download=1';
                    }

                    $documents[] = [
                        'document_label' => $documentTypeLabel((string)(cleanText($documentRow['document_type'] ?? null) ?? 'other')),
                        'file_name' => cleanText($documentRow['file_name'] ?? null) ?? 'document',
                        'preview_url' => $previewUrl,
                        'download_url' => $downloadUrl,
                        'uploaded_label' => formatDateTimeForPhilippines(cleanText($documentRow['uploaded_at'] ?? null), 'M d, Y'),
                        'is_available' => $previewUrl !== '',
                    ];
                }

                $interviews = [];
                foreach ($interviewRows as $interviewRow) {
                    $resultRaw = strtolower((string)(cleanText($interviewRow['result'] ?? null) ?? ''));
                    $interviews[] = [
                        'stage_label' => ucwords(str_replace('_', ' ', strtolower((string)(cleanText($interviewRow['interview_stage'] ?? null) ?? 'Interview')))),
                        'scheduled_label' => formatDateTimeForPhilippines(cleanText($interviewRow['scheduled_at'] ?? null), 'M d, Y h:i A'),
                        'result_label' => $resultRaw !== '' ? ucwords(str_replace('_', ' ', $resultRaw)) : 'Pending',
                        'remarks' => cleanText($interviewRow['remarks'] ?? null) ?? '-',
                        'score' => cleanText($interviewRow['score'] ?? null) ?? '-',
                        'interviewer_email' => cleanText($interviewRow['interviewer']['email'] ?? null) ?? '-',
                    ];
                }

                $feedbackEntries = [];
                foreach ($feedbackRows as $feedbackRow) {
                    $feedbackEntries[] = [
                        'decision_label' => $feedbackDecisionLabel((string)(cleanText($feedbackRow['decision'] ?? null) ?? '')),
                        'feedback_text' => cleanText($feedbackRow['feedback_text'] ?? null) ?? '-',
                        'provided_label' => formatDateTimeForPhilippines(cleanText($feedbackRow['provided_at'] ?? null), 'M d, Y'),
                        'provider_email' => cleanText($feedbackRow['provider']['email'] ?? null) ?? '-',
                    ];
                }

                $statusRaw = strtolower((string)(cleanText($applicationRow['application_status'] ?? null) ?? 'submitted'));
                [$statusLabel] = $trackingStatusPill($statusRaw);
                $trackingDetailPayload = [
                    'application_id' => $trackingSelectedApplicationId,
                    'application_ref_no' => cleanText($applicationRow['application_ref_no'] ?? null) ?? '-',
                    'applicant_name' => cleanText($applicationRow['applicant']['full_name'] ?? null) ?? 'Applicant',
                    'applicant_email' => cleanText($applicationRow['applicant']['email'] ?? null) ?? '-',
                    'applicant_mobile' => cleanText($applicationRow['applicant']['mobile_no'] ?? null) ?? '-',
                    'applicant_address' => cleanText($applicationRow['applicant']['current_address'] ?? null) ?? '-',
                    'posting_title' => cleanText($applicationRow['job_posting']['title'] ?? null) ?? 'Job Posting',
                    'submitted_label' => formatDateTimeForPhilippines(cleanText($applicationRow['submitted_at'] ?? null), 'M d, Y'),
                    'status_label' => $statusLabel,
                    'current_stage_label' => $trackingStageLabel($statusRaw),
                    'documents' => $documents,
                    'interviews' => $interviews,
                    'feedback' => $feedbackEntries,
                ];
            }
        }
    }
}