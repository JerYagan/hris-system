<?php

$activeRecruitmentRows = [];
$archivedRecruitmentRows = [];
$applicationDeadlineRows = [];
$activeRecruitmentStatusOptions = [];
$recruitmentPostingViewPayload = null;
$recruitmentApplicantViewPayload = null;
$recruitmentSummary = [
    'active_postings' => 0,
    'published_postings' => 0,
    'archived_postings' => 0,
    'pending_applications' => 0,
    'total_applications' => 0,
    'upcoming_deadlines' => 0,
];
$recruitmentPagination = [
    'page' => 1,
    'page_size' => 10,
    'total_rows' => 0,
    'total_pages' => 1,
    'from' => 0,
    'to' => 0,
    'search' => '',
    'status' => '',
    'has_prev' => false,
    'has_next' => false,
    'prev_page' => 1,
    'next_page' => 1,
];
$dataLoadError = null;

$recruitmentDataStage = (string)($recruitmentDataStage ?? 'full');
$recruitmentLoadSummary = in_array($recruitmentDataStage, ['full', 'summary'], true);
$recruitmentLoadListings = in_array($recruitmentDataStage, ['full', 'listings'], true);
$recruitmentLoadSecondary = in_array($recruitmentDataStage, ['full', 'secondary'], true);
$recruitmentPostingId = cleanText($_GET['posting_id'] ?? null) ?? '';
$recruitmentApplicationId = cleanText($_GET['application_id'] ?? null) ?? '';
$recruitmentPageSize = 10;
$recruitmentPage = max(1, (int)($_GET['recruitment_page'] ?? 1));
$recruitmentSearch = trim((string)($_GET['recruitment_search'] ?? ''));
$recruitmentSearchNormalized = strtolower($recruitmentSearch);
$recruitmentStatusFilter = strtolower(trim((string)($_GET['recruitment_status'] ?? '')));

if (!isValidUuid($recruitmentPostingId)) {
    $recruitmentPostingId = '';
}

if (!isValidUuid($recruitmentApplicationId)) {
    $recruitmentApplicationId = '';
}

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

$resolveRecruitmentDocumentUrl = static function (?string $rawUrl) use ($supabaseUrl): string {
    $value = trim((string)$rawUrl);
    if ($value === '') {
        return '';
    }

    $localDocumentRoot = __DIR__ . '/../../../../storage/document';

    $resolveLocalPath = static function (string $rawPath) use ($localDocumentRoot): string {
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

    $localResolved = $resolveLocalPath($value);
    if ($localResolved !== '') {
        return $localResolved;
    }

    if (preg_match('#^https?://#i', $value) === 1 || str_starts_with($value, '/')) {
        return $value;
    }

    if (str_starts_with($value, 'document/')) {
        $segments = array_values(array_filter(explode('/', preg_replace('#^document/#i', '', $value)), static fn(string $segment): bool => $segment !== ''));
        return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', $segments));
    }

    return rtrim($supabaseUrl, '/') . '/' . ltrim($value, '/');
};

$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'published' => ['Open', 'bg-emerald-100 text-emerald-800'],
        'closed' => ['Closed', 'bg-amber-100 text-amber-800'],
        'archived' => ['Archived', 'bg-slate-200 text-slate-700'],
        default => ['Draft', 'bg-blue-100 text-blue-800'],
    };
};

$applicationStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'submitted' => ['Submitted', 'bg-slate-100 text-slate-700'],
        'screening' => ['Screening', 'bg-amber-100 text-amber-800'],
        'shortlisted' => ['Shortlisted', 'bg-blue-100 text-blue-800'],
        'interview' => ['Interview', 'bg-indigo-100 text-indigo-800'],
        'offer' => ['For Offer', 'bg-emerald-100 text-emerald-800'],
        'hired' => ['Hired', 'bg-green-100 text-green-800'],
        'rejected' => ['Rejected', 'bg-rose-100 text-rose-800'],
        default => ['For Review', 'bg-slate-100 text-slate-700'],
    };
};

$employmentTypeLabel = static function (?string $classification): string {
    $key = strtolower(trim((string)$classification));
    return match ($key) {
        'regular', 'coterminous' => 'Permanent',
        'contractual', 'casual', 'job_order' => 'Contractual',
        default => 'Contractual',
    };
};

$feedbackDecisionLabel = static function (string $decision): string {
    $key = strtolower(trim($decision));
    return match ($key) {
        'for_next_step' => 'For Next Step',
        'on_hold' => 'On Hold',
        'rejected' => 'Rejected',
        'hired' => 'Hired',
        default => $key !== '' ? ucwords(str_replace('_', ' ', $key)) : 'Recorded',
    };
};

$documentLabel = static function (string $documentType): string {
    $key = strtolower(trim($documentType));
    return match ($key) {
        'pds' => 'Personal Data Sheet',
        'resume', 'resume_cv', 'updated_resume_cv' => 'Resume/CV',
        'transcript', 'transcript_of_records' => 'Transcript of Records',
        'id', 'valid_government_id' => 'Valid Government ID',
        'application_letter' => 'Application Letter',
        'certificate' => 'Certificate',
        default => $key !== '' ? ucwords(str_replace('_', ' ', $key)) : 'Document',
    };
};

$normalizeRequiredDocumentLabels = static function ($rawValue): array {
    $defaultDocuments = ['Application Letter', 'Updated Resume/CV', 'Personal Data Sheet', 'Valid Government ID', 'Transcript of Records'];
    if (!is_array($rawValue) || empty($rawValue)) {
        return $defaultDocuments;
    }

    $documentMap = [
        'application_letter' => 'Application Letter',
        'updated_resume_cv' => 'Updated Resume/CV',
        'personal_data_sheet' => 'Personal Data Sheet',
        'valid_government_id' => 'Valid Government ID',
        'transcript_of_records' => 'Transcript of Records',
        'pds' => 'Personal Data Sheet',
        'wes' => 'Updated Resume/CV',
        'eligibility_csc_prc' => 'Valid Government ID',
        'transcript' => 'Transcript of Records',
        'certificate' => 'Certificate',
    ];

    $labels = [];
    foreach ($rawValue as $value) {
        $text = trim((string)$value);
        if ($text === '') {
            continue;
        }

        $normalizedKey = strtolower(str_replace([' ', '-', '/', '(', ')'], ['_', '_', '_', '', ''], $text));
        $label = $documentMap[$normalizedKey] ?? $documentMap[strtolower($text)] ?? $text;
        $labels[$label] = $label;
    }

    return !empty($labels) ? array_values($labels) : $defaultDocuments;
};

$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
$todayDate = gmdate('Y-m-d');
$today = strtotime($todayDate);

if (in_array($recruitmentDataStage, ['full', 'summary', 'listings', 'secondary'], true)) {
    $postingResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/job_postings?select=id,title,office_id,posting_status,open_date,close_date,updated_at,office:offices(office_name),position:job_positions(position_title,employment_classification)'
        . '&order=updated_at.desc&limit=400',
        $headers
    );
    $appendDataError('Job postings', $postingResponse);
    $postingRows = isSuccessful($postingResponse) ? (array)($postingResponse['data'] ?? []) : [];

    $postingIds = [];
    $scopedPostingRows = [];
    foreach ($postingRows as $posting) {
        $postingId = cleanText($posting['id'] ?? null) ?? '';
        if (!isValidUuid($postingId)) {
            continue;
        }

        $postingOfficeId = cleanText($posting['office_id'] ?? null) ?? '';
        if (!$isAdminScope && isValidUuid((string)$staffOfficeId) && strtolower($postingOfficeId) !== strtolower((string)$staffOfficeId)) {
            continue;
        }

        $scopedPostingRows[] = (array)$posting;
        $postingIds[] = $postingId;
    }

    $applicationCountByPosting = [];
    if (($recruitmentLoadSummary || $recruitmentLoadListings) && !empty($postingIds)) {
        $applicationResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/applications?select=id,job_posting_id,application_status'
            . '&job_posting_id=in.' . rawurlencode('(' . implode(',', $postingIds) . ')')
            . '&limit=4000',
            $headers
        );
        $appendDataError('Applications', $applicationResponse);
        $applicationRows = isSuccessful($applicationResponse) ? (array)($applicationResponse['data'] ?? []) : [];

        foreach ($applicationRows as $application) {
            $postingId = cleanText($application['job_posting_id'] ?? null) ?? '';
            if (!isValidUuid($postingId)) {
                continue;
            }

            if (!isset($applicationCountByPosting[$postingId])) {
                $applicationCountByPosting[$postingId] = ['total' => 0, 'pending' => 0];
            }

            $applicationCountByPosting[$postingId]['total']++;
            $status = strtolower((string)(cleanText($application['application_status'] ?? null) ?? 'submitted'));
            if (in_array($status, ['submitted', 'screening', 'shortlisted', 'interview', 'offer'], true)) {
                $applicationCountByPosting[$postingId]['pending']++;
            }
        }
    }

    $activeRowsUnfiltered = [];
    foreach ($scopedPostingRows as $posting) {
        $postingId = cleanText($posting['id'] ?? null) ?? '';
        if (!isValidUuid($postingId)) {
            continue;
        }

        $statusRaw = strtolower((string)(cleanText($posting['posting_status'] ?? null) ?? 'draft'));
        [$statusLabel, $statusClass] = $statusPill($statusRaw);

        $title = cleanText($posting['title'] ?? null) ?? 'Untitled Posting';
        $officeName = cleanText($posting['office']['office_name'] ?? null) ?? 'Unassigned Division';
        $positionTitle = cleanText($posting['position']['position_title'] ?? null) ?? 'Unassigned Position';
        $employmentType = $employmentTypeLabel(cleanText($posting['position']['employment_classification'] ?? null));
        $counts = $applicationCountByPosting[$postingId] ?? ['total' => 0, 'pending' => 0];

        $row = [
            'id' => $postingId,
            'title' => $title,
            'office_name' => $officeName,
            'position_title' => $positionTitle,
            'employment_type' => $employmentType,
            'open_date_label' => formatDateTimeForPhilippines(cleanText($posting['open_date'] ?? null), 'M d, Y'),
            'close_date_label' => formatDateTimeForPhilippines(cleanText($posting['close_date'] ?? null), 'M d, Y'),
            'status_raw' => $statusRaw,
            'status_label' => $statusLabel,
            'status_class' => $statusClass,
            'applications_total' => (int)($counts['total'] ?? 0),
            'applications_pending' => (int)($counts['pending'] ?? 0),
            'search_text' => strtolower(trim($title . ' ' . $officeName . ' ' . $positionTitle . ' ' . $employmentType . ' ' . $statusLabel)),
        ];

        if ($statusRaw === 'archived') {
            $archivedRecruitmentRows[] = $row;
        } else {
            $activeRowsUnfiltered[] = $row;
            $activeRecruitmentStatusOptions[$statusRaw] = $statusLabel;
        }

        $closeDateRaw = cleanText($posting['close_date'] ?? null) ?? '';
        $closeDateTimestamp = $closeDateRaw !== '' ? strtotime($closeDateRaw) : false;
        if ($statusRaw === 'published' && $closeDateTimestamp !== false && $closeDateTimestamp >= $today) {
            $daysRemaining = (int)floor(($closeDateTimestamp - $today) / 86400);
            $applicationDeadlineRows[] = [
                'title' => $title,
                'position_title' => $positionTitle,
                'office_name' => $officeName,
                'close_date_label' => formatDateTimeForPhilippines($closeDateRaw, 'M d, Y'),
                'days_remaining' => $daysRemaining,
                'priority_label' => $daysRemaining <= 3 ? 'Urgent' : ($daysRemaining <= 7 ? 'Upcoming' : 'Scheduled'),
                'priority_class' => $daysRemaining <= 3
                    ? 'bg-rose-100 text-rose-800'
                    : ($daysRemaining <= 7 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'),
            ];
        }
    }

    usort($applicationDeadlineRows, static fn(array $left, array $right): int => ($left['days_remaining'] ?? 0) <=> ($right['days_remaining'] ?? 0));
    asort($activeRecruitmentStatusOptions);

    $recruitmentSummary = [
        'active_postings' => count($activeRowsUnfiltered),
        'published_postings' => count(array_filter($activeRowsUnfiltered, static fn(array $row): bool => (string)($row['status_raw'] ?? '') === 'published')),
        'archived_postings' => count($archivedRecruitmentRows),
        'pending_applications' => array_sum(array_map(static fn(array $row): int => (int)($row['applications_pending'] ?? 0), $activeRowsUnfiltered)),
        'total_applications' => array_sum(array_map(static fn(array $row): int => (int)($row['applications_total'] ?? 0), $activeRowsUnfiltered)),
        'upcoming_deadlines' => count(array_filter($applicationDeadlineRows, static fn(array $row): bool => (int)($row['days_remaining'] ?? 0) <= 7)),
    ];

    $activeRecruitmentRows = $activeRowsUnfiltered;
    if ($recruitmentSearchNormalized !== '') {
        $activeRecruitmentRows = array_values(array_filter(
            $activeRecruitmentRows,
            static fn(array $row): bool => strpos((string)($row['search_text'] ?? ''), $recruitmentSearchNormalized) !== false
        ));
    }

    if ($recruitmentStatusFilter !== '') {
        $activeRecruitmentRows = array_values(array_filter(
            $activeRecruitmentRows,
            static fn(array $row): bool => strtolower((string)($row['status_raw'] ?? '')) === $recruitmentStatusFilter
        ));
    }

    $recruitmentTotalRows = count($activeRecruitmentRows);
    $recruitmentTotalPages = max(1, (int)ceil($recruitmentTotalRows / $recruitmentPageSize));
    if ($recruitmentPage > $recruitmentTotalPages) {
        $recruitmentPage = $recruitmentTotalPages;
    }

    $recruitmentOffset = ($recruitmentPage - 1) * $recruitmentPageSize;
    $activeRecruitmentRows = array_slice($activeRecruitmentRows, $recruitmentOffset, $recruitmentPageSize);
    $recruitmentPagination = [
        'page' => $recruitmentPage,
        'page_size' => $recruitmentPageSize,
        'total_rows' => $recruitmentTotalRows,
        'total_pages' => $recruitmentTotalPages,
        'from' => $recruitmentTotalRows > 0 ? ($recruitmentOffset + 1) : 0,
        'to' => $recruitmentTotalRows > 0 ? min($recruitmentOffset + $recruitmentPageSize, $recruitmentTotalRows) : 0,
        'search' => $recruitmentSearch,
        'status' => $recruitmentStatusFilter,
        'has_prev' => $recruitmentPage > 1,
        'has_next' => $recruitmentPage < $recruitmentTotalPages,
        'prev_page' => max(1, $recruitmentPage - 1),
        'next_page' => min($recruitmentTotalPages, $recruitmentPage + 1),
    ];
}

if ($recruitmentDataStage === 'posting-view' && $recruitmentPostingId !== '') {
    $postingResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/job_postings?select=id,title,office_id,description,qualifications,responsibilities,required_documents,posting_status,open_date,close_date,office:offices(office_name),position:job_positions(position_title,employment_classification)'
        . '&id=eq.' . rawurlencode($recruitmentPostingId)
        . '&limit=1',
        $headers
    );
    $appendDataError('Posting detail', $postingResponse);
    $posting = isSuccessful($postingResponse) ? (array)($postingResponse['data'][0] ?? []) : [];
    $postingOfficeId = cleanText($posting['office_id'] ?? null) ?? '';

    if (!empty($posting) && ($isAdminScope || !isValidUuid((string)$staffOfficeId) || strtolower($postingOfficeId) === strtolower((string)$staffOfficeId))) {
        $statusRaw = strtolower((string)(cleanText($posting['posting_status'] ?? null) ?? 'draft'));
        [$statusLabel] = $statusPill($statusRaw);
        $positionTitle = cleanText($posting['position']['position_title'] ?? null) ?? 'Unassigned Position';
        $officeName = cleanText($posting['office']['office_name'] ?? null) ?? 'Unassigned Division';

        $applicationsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/applications?select=id,application_status,submitted_at,applicant_profile_id,applicant:applicant_profiles(full_name,email,mobile_no,current_address,person_id)'
            . '&job_posting_id=eq.' . rawurlencode($recruitmentPostingId)
            . '&order=submitted_at.desc&limit=200',
            $headers
        );
        $appendDataError('Posting applicants', $applicationsResponse);
        $applicationRows = isSuccessful($applicationsResponse) ? (array)($applicationsResponse['data'] ?? []) : [];

        $applicants = [];
        foreach ($applicationRows as $application) {
            $applicationId = cleanText($application['id'] ?? null) ?? '';
            if (!isValidUuid($applicationId)) {
                continue;
            }

            $applicationStatusRaw = strtolower((string)(cleanText($application['application_status'] ?? null) ?? 'submitted'));
            [$applicationStatusLabel, $applicationStatusClass] = $applicationStatusPill($applicationStatusRaw);
            $submittedAt = cleanText($application['submitted_at'] ?? null) ?? '';
            $applicant = is_array($application['applicant'] ?? null) ? (array)$application['applicant'] : [];
            $personId = cleanText($applicant['person_id'] ?? null) ?? '';

            $applicants[] = [
                'application_id' => $applicationId,
                'applicant_name' => cleanText($applicant['full_name'] ?? null) ?? 'Applicant',
                'applicant_email' => cleanText($applicant['email'] ?? null) ?? '-',
                'applied_position' => $positionTitle,
                'submitted_label' => $submittedAt !== '' ? formatDateTimeForPhilippines($submittedAt, 'M d, Y') : '-',
                'initial_screening_label' => $applicationStatusLabel,
                'initial_screening_class' => $applicationStatusClass,
                'basis' => 'Open profile to load documents and review history.',
                'already_employee' => isValidUuid($personId),
            ];
        }

        $recruitmentPostingViewPayload = [
            'id' => $recruitmentPostingId,
            'posting_title' => cleanText($posting['title'] ?? null) ?? 'Untitled Posting',
            'position_title' => $positionTitle,
            'office_name' => $officeName,
            'employment_type' => $employmentTypeLabel(cleanText($posting['position']['employment_classification'] ?? null)),
            'status_label' => $statusLabel,
            'open_date_label' => formatDateTimeForPhilippines(cleanText($posting['open_date'] ?? null), 'M d, Y'),
            'close_date_label' => formatDateTimeForPhilippines(cleanText($posting['close_date'] ?? null), 'M d, Y'),
            'description' => cleanText($posting['description'] ?? null) ?? '-',
            'qualifications' => cleanText($posting['qualifications'] ?? null) ?? '-',
            'responsibilities' => cleanText($posting['responsibilities'] ?? null) ?? '-',
            'requirements' => $normalizeRequiredDocumentLabels($posting['required_documents'] ?? []),
            'applicants' => $applicants,
        ];
    }
}

if ($recruitmentDataStage === 'applicant-view' && $recruitmentApplicationId !== '') {
    $applicationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/applications?select=id,job_posting_id,application_status,submitted_at,applicant_profile_id,posting:job_postings(id,office_id,title,position:job_positions(position_title)),applicant:applicant_profiles(full_name,email,mobile_no,current_address,resume_url,portfolio_url,person_id)'
        . '&id=eq.' . rawurlencode($recruitmentApplicationId)
        . '&limit=1',
        $headers
    );
    $appendDataError('Applicant detail', $applicationResponse);
    $application = isSuccessful($applicationResponse) ? (array)($applicationResponse['data'][0] ?? []) : [];

    $posting = is_array($application['posting'] ?? null) ? (array)$application['posting'] : [];
    $postingOfficeId = cleanText($posting['office_id'] ?? null) ?? '';
    if (!empty($application) && ($isAdminScope || !isValidUuid((string)$staffOfficeId) || strtolower($postingOfficeId) === strtolower((string)$staffOfficeId))) {
        $applicant = is_array($application['applicant'] ?? null) ? (array)$application['applicant'] : [];
        $applicationStatusRaw = strtolower((string)(cleanText($application['application_status'] ?? null) ?? 'submitted'));
        [$applicationStatusLabel, $applicationStatusClass] = $applicationStatusPill($applicationStatusRaw);

        $documentsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/application_documents?select=document_type,file_url,file_name,uploaded_at'
            . '&application_id=eq.' . rawurlencode($recruitmentApplicationId)
            . '&order=uploaded_at.asc&limit=200',
            $headers
        );
        $appendDataError('Applicant documents', $documentsResponse);
        $documentRows = isSuccessful($documentsResponse) ? (array)($documentsResponse['data'] ?? []) : [];

        $feedbackResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/application_feedback?select=decision,feedback_text,provided_at'
            . '&application_id=eq.' . rawurlencode($recruitmentApplicationId)
            . '&order=provided_at.desc&limit=20',
            $headers
        );
        $appendDataError('Applicant feedback', $feedbackResponse);
        $feedbackRows = isSuccessful($feedbackResponse) ? (array)($feedbackResponse['data'] ?? []) : [];

        $interviewsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/application_interviews?select=scheduled_at,result'
            . '&application_id=eq.' . rawurlencode($recruitmentApplicationId)
            . '&order=scheduled_at.asc&limit=50',
            $headers
        );
        $appendDataError('Applicant interviews', $interviewsResponse);
        $interviewRows = isSuccessful($interviewsResponse) ? (array)($interviewsResponse['data'] ?? []) : [];

        $documents = [];
        foreach ($documentRows as $documentRow) {
            $fileUrl = $resolveRecruitmentDocumentUrl(cleanText($documentRow['file_url'] ?? null));
            $documents[] = [
                'document_label' => $documentLabel((string)($documentRow['document_type'] ?? 'document')),
                'file_name' => cleanText($documentRow['file_name'] ?? null) ?? 'Document',
                'file_url' => $fileUrl,
                'uploaded_label' => formatDateTimeForPhilippines(cleanText($documentRow['uploaded_at'] ?? null), 'M d, Y'),
                'is_available' => $fileUrl !== '',
            ];
        }

        $feedbackHistory = [];
        $basis = '-';
        foreach ($feedbackRows as $index => $feedbackRow) {
            $feedbackText = trim((string)($feedbackRow['feedback_text'] ?? ''));
            $summary = $feedbackText !== '' ? preg_replace('/\s+/', ' ', $feedbackText) : 'No remarks recorded.';
            if ($index === 0) {
                $parts = preg_split('/\R/', $summary, 2);
                $basisCandidate = trim((string)($parts[0] ?? ''));
                if ($basisCandidate !== '') {
                    $basis = $basisCandidate;
                }
            }

            $feedbackHistory[] = [
                'decision_label' => $feedbackDecisionLabel((string)($feedbackRow['decision'] ?? '')),
                'provided_label' => formatDateTimeForPhilippines(cleanText($feedbackRow['provided_at'] ?? null), 'M d, Y g:i A'),
                'summary' => $summary,
            ];
        }

        $interviewHistory = [];
        foreach ($interviewRows as $interviewRow) {
            $interviewHistory[] = [
                'scheduled_label' => formatDateTimeForPhilippines(cleanText($interviewRow['scheduled_at'] ?? null), 'M d, Y g:i A'),
                'result_label' => trim((string)($interviewRow['result'] ?? '')) !== ''
                    ? ucwords(str_replace('_', ' ', (string)$interviewRow['result']))
                    : 'Pending',
            ];
        }

        $submittedAt = cleanText($application['submitted_at'] ?? null) ?? '';
        $positionTitle = cleanText($posting['position']['position_title'] ?? null) ?? 'Unassigned Position';
        $resumeUrl = $resolveRecruitmentDocumentUrl(cleanText($applicant['resume_url'] ?? null));
        $portfolioUrl = cleanText($applicant['portfolio_url'] ?? null) ?? '';

        $recruitmentApplicantViewPayload = [
            'application_id' => $recruitmentApplicationId,
            'applicant_name' => cleanText($applicant['full_name'] ?? null) ?? 'Applicant',
            'applicant_email' => cleanText($applicant['email'] ?? null) ?? '-',
            'applicant_mobile' => cleanText($applicant['mobile_no'] ?? null) ?? '-',
            'applicant_address' => cleanText($applicant['current_address'] ?? null) ?? '-',
            'applied_position' => $positionTitle,
            'submitted_label' => $submittedAt !== '' ? formatDateTimeForPhilippines($submittedAt, 'M d, Y') : '-',
            'initial_screening_label' => $applicationStatusLabel,
            'initial_screening_class' => $applicationStatusClass,
            'basis' => $basis,
            'already_employee' => isValidUuid((string)(cleanText($applicant['person_id'] ?? null) ?? '')),
            'documents' => $documents,
            'feedback_history' => $feedbackHistory,
            'interview_history' => $interviewHistory,
            'resume_url' => $resumeUrl,
            'portfolio_url' => $portfolioUrl,
        ];
    }
}