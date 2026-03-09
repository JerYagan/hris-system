<?php

$activeRecruitmentRows = [];
$archivedRecruitmentRows = [];
$officeOptions = [];
$positionOptions = [];
$postingViewById = [];
$applicationDeadlineRows = [];
$archivedFilledPositionRows = [];
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

$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
$scopeFilter = (!$isAdminScope && isValidUuid((string)$staffOfficeId))
    ? '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
    : '';
$todayDate = gmdate('Y-m-d');
$today = strtotime($todayDate);

$postingResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/job_postings?select=id,title,office_id,position_id,plantilla_item_no,description,qualifications,responsibilities,required_documents,posting_status,open_date,close_date,updated_at,office:offices(office_name),position:job_positions(position_title,employment_classification)'
    . '&order=updated_at.desc&limit=500',
    $headers
);
$appendDataError('Job postings', $postingResponse);
$postingRows = isSuccessful($postingResponse) ? (array)($postingResponse['data'] ?? []) : [];

$officeResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/offices?select=id,office_name&is_active=eq.true&order=office_name.asc&limit=500',
    $headers
);
$appendDataError('Divisions', $officeResponse);
$officeOptions = isSuccessful($officeResponse) ? (array)($officeResponse['data'] ?? []) : [];

$positionResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/job_positions?select=id,position_title,employment_classification&is_active=eq.true&order=position_title.asc&limit=1000',
    $headers
);
$appendDataError('Job positions', $positionResponse);
$positionOptions = isSuccessful($positionResponse) ? (array)($positionResponse['data'] ?? []) : [];

$postingIds = [];
foreach ($postingRows as $posting) {
    $postingId = cleanText($posting['id'] ?? null);
    if ($postingId === null || !isValidUuid($postingId)) {
        continue;
    }
    $postingIds[] = $postingId;
}

$applicationCountByPosting = [];
$applicationsByPosting = [];
$basisByApplicationId = [];
$documentsByApplicationId = [];
if (!empty($postingIds)) {
    $applicationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/applications?select=id,job_posting_id,application_status,updated_at,submitted_at,applicant:applicant_profiles(full_name,email,user_id)'
        . '&job_posting_id=in.' . rawurlencode('(' . implode(',', $postingIds) . ')')
        . '&limit=5000',
        $headers
    );
    $appendDataError('Applications', $applicationResponse);
    $applicationRows = isSuccessful($applicationResponse) ? (array)($applicationResponse['data'] ?? []) : [];
    $applicationIds = [];

    foreach ($applicationRows as $application) {
        $applicationId = cleanText($application['id'] ?? null);
        if ($applicationId !== null && isValidUuid($applicationId)) {
            $applicationIds[] = $applicationId;
        }

        $postingId = cleanText($application['job_posting_id'] ?? null);
        if ($postingId === null || !isValidUuid($postingId)) {
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

        if (!isset($applicationsByPosting[$postingId])) {
            $applicationsByPosting[$postingId] = [];
        }
        $applicationsByPosting[$postingId][] = (array)$application;
    }

    if (!empty($applicationIds)) {
        $statusHistoryResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/application_status_history?select=application_id,notes,created_at'
            . '&application_id=in.' . rawurlencode('(' . implode(',', $applicationIds) . ')')
            . '&order=created_at.desc&limit=10000',
            $headers
        );
        $appendDataError('Application status history', $statusHistoryResponse);
        $statusHistoryRows = isSuccessful($statusHistoryResponse) ? (array)($statusHistoryResponse['data'] ?? []) : [];

        foreach ($statusHistoryRows as $statusHistoryRow) {
            $historyApplicationId = cleanText($statusHistoryRow['application_id'] ?? null) ?? '';
            if (!isValidUuid($historyApplicationId) || isset($basisByApplicationId[$historyApplicationId])) {
                continue;
            }

            $notes = trim((string)($statusHistoryRow['notes'] ?? ''));
            if ($notes === '') {
                $basisByApplicationId[$historyApplicationId] = '-';
                continue;
            }

            $basisParts = explode('|', $notes, 2);
            $basisValue = trim((string)($basisParts[0] ?? ''));
            $basisByApplicationId[$historyApplicationId] = $basisValue !== '' ? $basisValue : '-';
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

        foreach ($documentRows as $documentRow) {
            $documentApplicationId = cleanText($documentRow['application_id'] ?? null) ?? '';
            if (!isValidUuid($documentApplicationId)) {
                continue;
            }

            if (!isset($documentsByApplicationId[$documentApplicationId])) {
                $documentsByApplicationId[$documentApplicationId] = [];
            }

            $resolvedFileUrl = $resolveRecruitmentDocumentUrl(cleanText($documentRow['file_url'] ?? null) ?? '');

            $documentsByApplicationId[$documentApplicationId][] = [
                'document_type' => cleanText($documentRow['document_type'] ?? null) ?? 'other',
                'document_label' => $documentTypeLabel((string)(cleanText($documentRow['document_type'] ?? null) ?? 'other')),
                'file_name' => cleanText($documentRow['file_name'] ?? null) ?? 'document',
                'file_url' => $resolvedFileUrl,
                'mime_type' => cleanText($documentRow['mime_type'] ?? null) ?? '',
                'uploaded_label' => formatDateTimeForPhilippines(cleanText($documentRow['uploaded_at'] ?? null), 'M d, Y'),
                'is_available' => trim((string)$resolvedFileUrl) !== '',
            ];
        }
    }
}

$applicantUserIds = [];
foreach ($applicationsByPosting as $postingApplicationRows) {
    foreach ($postingApplicationRows as $applicationRow) {
        $applicantUserId = cleanText($applicationRow['applicant']['user_id'] ?? null) ?? '';
        if (!isValidUuid($applicantUserId)) {
            continue;
        }

        $applicantUserIds[strtolower($applicantUserId)] = $applicantUserId;
    }
}

$personByUserId = [];
$hasCurrentEmploymentByUserId = [];
if (!empty($applicantUserIds)) {
    $applicantUserIdFilter = implode(',', array_values($applicantUserIds));
    $peopleResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/people?select=id,user_id'
        . '&user_id=in.' . rawurlencode('(' . $applicantUserIdFilter . ')')
        . '&limit=5000',
        $headers
    );
    $appendDataError('Applicant people lookup', $peopleResponse);
    $peopleRows = isSuccessful($peopleResponse) ? (array)($peopleResponse['data'] ?? []) : [];

    $personIds = [];
    foreach ($peopleRows as $peopleRow) {
        $userId = cleanText($peopleRow['user_id'] ?? null) ?? '';
        $personId = cleanText($peopleRow['id'] ?? null) ?? '';
        if (!isValidUuid($userId) || !isValidUuid($personId)) {
            continue;
        }

        $personByUserId[strtolower($userId)] = [
            'user_id' => $userId,
            'person_id' => $personId,
        ];
        $personIds[] = $personId;
    }

    if (!empty($personIds)) {
        $employmentResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/employment_records?select=person_id,is_current'
            . '&person_id=in.' . rawurlencode('(' . implode(',', $personIds) . ')')
            . '&is_current=eq.true&limit=5000',
            $headers
        );
        $appendDataError('Applicant employment lookup', $employmentResponse);
        $employmentRows = isSuccessful($employmentResponse) ? (array)($employmentResponse['data'] ?? []) : [];

        $personIdWithEmployment = [];
        foreach ($employmentRows as $employmentRow) {
            $personId = cleanText($employmentRow['person_id'] ?? null) ?? '';
            if (!isValidUuid($personId)) {
                continue;
            }
            $personIdWithEmployment[strtolower($personId)] = true;
        }

        foreach ($personByUserId as $userKey => $personLinkRow) {
            $personId = strtolower((string)($personLinkRow['person_id'] ?? ''));
            if ($personId !== '' && isset($personIdWithEmployment[$personId])) {
                $hasCurrentEmploymentByUserId[$userKey] = true;
            }
        }
    }
}

$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'published' => ['Open', 'bg-emerald-100 text-emerald-800'],
        'closed' => ['Closed', 'bg-amber-100 text-amber-800'],
        'archived' => ['Archived', 'bg-slate-200 text-slate-700'],
        default => ['Draft', 'bg-blue-100 text-blue-800'],
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

$applicationStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'submitted' => ['Applied', 'bg-blue-100 text-blue-800'],
        'screening' => ['Verified', 'bg-indigo-100 text-indigo-800'],
        'interview' => ['Interview', 'bg-amber-100 text-amber-800'],
        'shortlisted' => ['Evaluation', 'bg-violet-100 text-violet-800'],
        'offer' => ['For Approval', 'bg-cyan-100 text-cyan-800'],
        'hired' => ['Hired', 'bg-emerald-100 text-emerald-800'],
        'rejected' => ['Rejected', 'bg-rose-100 text-rose-800'],
        'withdrawn' => ['Rejected', 'bg-rose-100 text-rose-800'],
        default => ['Applied', 'bg-slate-100 text-slate-700'],
    };
};

$applicationScreeningPill = static function (string $status): array {
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

$recommendationScoreForStatus = static function (string $status, string $fullName, string $email): int {
    $statusKey = strtolower(trim($status));
    $score = match ($statusKey) {
        'hired' => 95,
        'offer' => 85,
        'shortlisted' => 72,
        'interview' => 55,
        'screening' => 40,
        'submitted' => 30,
        'rejected', 'withdrawn' => 10,
        default => 25,
    };

    if (trim($fullName) !== '') {
        $score += 3;
    }
    if (trim($email) !== '') {
        $score += 2;
    }

    return max(0, min(100, $score));
};

$recommendationLabel = static function (int $score): array {
    if ($score >= 80) {
        return ['High', 'bg-emerald-100 text-emerald-800'];
    }
    if ($score >= 55) {
        return ['Medium', 'bg-amber-100 text-amber-800'];
    }

    return ['Low', 'bg-slate-200 text-slate-700'];
};

$isActivePublishedPosting = static function (array $posting) use ($todayDate): bool {
    $statusRaw = strtolower((string)(cleanText($posting['posting_status'] ?? null) ?? 'draft'));
    if ($statusRaw !== 'published') {
        return false;
    }

    $openDate = cleanText($posting['open_date'] ?? null) ?? '';
    $closeDate = cleanText($posting['close_date'] ?? null) ?? '';

    if ($openDate !== '' && $openDate > $todayDate) {
        return false;
    }

    if ($closeDate !== '' && $closeDate < $todayDate) {
        return false;
    }

    return true;
};

$activeRecruitmentStatusOptions = [];

foreach ($postingRows as $posting) {
    $postingId = cleanText($posting['id'] ?? null) ?? '';
    if (!isValidUuid($postingId)) {
        continue;
    }

    $postingOfficeId = cleanText($posting['office_id'] ?? null) ?? '';
    if (!$isAdminScope && isValidUuid((string)$staffOfficeId) && strtolower($postingOfficeId) !== strtolower((string)$staffOfficeId)) {
        continue;
    }

    $statusRaw = strtolower((string)(cleanText($posting['posting_status'] ?? null) ?? 'draft'));
    [$statusLabel, $statusClass] = $statusPill($statusRaw);

    $title = cleanText($posting['title'] ?? null) ?? 'Untitled Posting';
    $officeName = cleanText($posting['office']['office_name'] ?? null) ?? 'Unassigned Division';
    $positionTitle = cleanText($posting['position']['position_title'] ?? null) ?? 'Unassigned Position';
    $employmentType = $employmentTypeLabel(cleanText($posting['position']['employment_classification'] ?? null));
    $description = cleanText($posting['description'] ?? null) ?? '-';
    $qualifications = cleanText($posting['qualifications'] ?? null) ?? '-';
    $responsibilities = cleanText($posting['responsibilities'] ?? null) ?? '-';
    $requirements = [];
    $requiredDocuments = $posting['required_documents'] ?? [];
    if (is_array($requiredDocuments)) {
        foreach ($requiredDocuments as $requiredDocument) {
            $label = trim((string)$requiredDocument);
            if ($label === '') {
                continue;
            }
            $requirements[] = $label;
        }
    }
    if (empty($requirements)) {
        $requirements = [
            'PDS',
            'WES',
            'Eligibility (CSC/PRC)',
            'Transcript of Records',
        ];
    }
    $counts = $applicationCountByPosting[$postingId] ?? ['total' => 0, 'pending' => 0];

    $postingApplicantRows = [];
    foreach ((array)($applicationsByPosting[$postingId] ?? []) as $applicationRow) {
        $applicationId = cleanText($applicationRow['id'] ?? null) ?? '';
        if (!isValidUuid($applicationId)) {
            continue;
        }

        $applicationStatusRaw = strtolower((string)(cleanText($applicationRow['application_status'] ?? null) ?? 'submitted'));
        [$applicationStatusLabel, $applicationStatusClass] = $applicationStatusPill($applicationStatusRaw);
        [$screeningLabel, $screeningClass] = $applicationScreeningPill($applicationStatusRaw);
        $applicantName = cleanText($applicationRow['applicant']['full_name'] ?? null) ?? 'Applicant';
        $applicantEmail = cleanText($applicationRow['applicant']['email'] ?? null) ?? '-';
        $applicantUserId = cleanText($applicationRow['applicant']['user_id'] ?? null) ?? '';
        $submittedLabel = formatDateTimeForPhilippines(cleanText($applicationRow['submitted_at'] ?? null), 'M d, Y');

        $score = $recommendationScoreForStatus($applicationStatusRaw, $applicantName, $applicantEmail);
        [$scoreLabel, $scoreClass] = $recommendationLabel($score);
        $hasEmployment = isValidUuid($applicantUserId) && !empty($hasCurrentEmploymentByUserId[strtolower($applicantUserId)]);

        $postingApplicantRows[] = [
            'application_id' => $applicationId,
            'applicant_name' => $applicantName,
            'applicant_email' => $applicantEmail,
            'status_raw' => $applicationStatusRaw,
            'status_label' => $applicationStatusLabel,
            'status_class' => $applicationStatusClass,
            'initial_screening_label' => $screeningLabel,
            'initial_screening_class' => $screeningClass,
            'basis' => (string)($basisByApplicationId[$applicationId] ?? '-'),
            'applied_position' => $positionTitle,
            'submitted_label' => $submittedLabel,
            'score' => $score,
            'score_label' => $scoreLabel,
            'score_class' => $scoreClass,
            'documents' => (array)($documentsByApplicationId[$applicationId] ?? []),
            'can_add_employee' => $applicationStatusRaw === 'hired' && !$hasEmployment,
            'already_employee' => $hasEmployment,
        ];
    }

    usort($postingApplicantRows, static function (array $left, array $right): int {
        return ((int)($right['score'] ?? 0)) <=> ((int)($left['score'] ?? 0));
    });

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
        'description' => $description,
        'qualifications' => $qualifications,
        'responsibilities' => $responsibilities,
        'requirements' => $requirements,
        'applications_total' => (int)($counts['total'] ?? 0),
        'applications_pending' => (int)($counts['pending'] ?? 0),
        'search_text' => strtolower(trim($title . ' ' . $officeName . ' ' . $positionTitle . ' ' . $employmentType . ' ' . $statusLabel)),
    ];

    $postingViewById[$postingId] = [
        'id' => $postingId,
        'position_title' => $positionTitle,
        'posting_title' => $title,
        'office_name' => $officeName,
        'employment_type' => $employmentType,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'open_date_label' => formatDateTimeForPhilippines(cleanText($posting['open_date'] ?? null), 'M d, Y'),
        'close_date_label' => formatDateTimeForPhilippines(cleanText($posting['close_date'] ?? null), 'M d, Y'),
        'description' => $description,
        'qualifications' => $qualifications,
        'responsibilities' => $responsibilities,
        'requirements' => $requirements,
        'applicants' => $postingApplicantRows,
    ];

    if ($statusRaw === 'archived') {
        $archivedRecruitmentRows[] = $row;
    } else {
        $activeRecruitmentRows[] = $row;
        $activeRecruitmentStatusOptions[$statusRaw] = $statusLabel;
    }

    $closeDateRaw = cleanText($posting['close_date'] ?? null) ?? '';
    $closeDateTimestamp = $closeDateRaw !== '' ? strtotime($closeDateRaw) : false;
    if (
        $statusRaw === 'published'
        && $closeDateTimestamp !== false
        && $closeDateTimestamp >= $today
    ) {
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

usort($applicationDeadlineRows, static function (array $left, array $right): int {
    return ($left['days_remaining'] ?? 0) <=> ($right['days_remaining'] ?? 0);
});

asort($activeRecruitmentStatusOptions);

$hiredApplicationsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/applications?select=id,application_status,updated_at,job:job_postings(id,office_id,posting_status,title,position:job_positions(position_title),office:offices(id,office_name)),applicant:applicant_profiles(full_name)'
    . '&application_status=eq.hired&order=updated_at.desc&limit=3000',
    $headers
);
$appendDataError('Archived filled positions', $hiredApplicationsResponse);
$hiredRows = isSuccessful($hiredApplicationsResponse) ? (array)($hiredApplicationsResponse['data'] ?? []) : [];

foreach ($hiredRows as $hiredRow) {
    $jobRow = is_array($hiredRow['job'] ?? null) ? (array)$hiredRow['job'] : [];
    $jobStatus = strtolower((string)(cleanText($jobRow['posting_status'] ?? null) ?? ''));
    if (!in_array($jobStatus, ['closed', 'archived'], true)) {
        continue;
    }

    $jobOfficeId = cleanText($jobRow['office_id'] ?? null) ?? '';
    if (!$isAdminScope && isValidUuid((string)$staffOfficeId) && strtolower($jobOfficeId) !== strtolower((string)$staffOfficeId)) {
        continue;
    }

    $officeRow = is_array($jobRow['office'] ?? null) ? (array)$jobRow['office'] : [];
    $positionRow = is_array($jobRow['position'] ?? null) ? (array)$jobRow['position'] : [];
    $applicantRow = is_array($hiredRow['applicant'] ?? null) ? (array)$hiredRow['applicant'] : [];

    $archivedFilledPositionRows[] = [
        'position_title' => cleanText($positionRow['position_title'] ?? null) ?? (cleanText($jobRow['title'] ?? null) ?? 'Position'),
        'office_name' => cleanText($officeRow['office_name'] ?? null) ?? 'Unassigned Division',
        'date_filled_label' => formatDateTimeForPhilippines(cleanText($hiredRow['updated_at'] ?? null), 'M d, Y'),
        'employee_name' => cleanText($applicantRow['full_name'] ?? null) ?? 'Unknown Employee',
    ];
}

