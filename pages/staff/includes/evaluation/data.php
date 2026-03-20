<?php

$ruleEvaluationSummary = [
    'qualified' => 0,
    'not_qualified' => 0,
    'total' => 0,
];
$ruleEvaluationRows = [];
$ruleEvaluationPositionTitles = [];
$ruleEvaluationPagination = [
    'page' => max(1, (int)($_GET['rule_page'] ?? 1)),
    'has_prev' => false,
    'has_next' => false,
    'prev_page' => 1,
    'next_page' => 2,
    'label' => 'Page 1',
];
$ruleEvaluationFilters = [
    'search' => trim((string)($_GET['rule_search'] ?? '')),
    'status' => strtolower(trim((string)($_GET['rule_status'] ?? ''))),
    'position' => trim((string)($_GET['position_title'] ?? '')),
];
$ruleEvaluationDetailPayload = null;
$dataLoadError = null;
$evaluationDataStage = trim((string)($evaluationDataStage ?? 'summary'));
$ruleEvaluationPageSize = 10;
$ruleEvaluationDetailApplicationId = trim((string)($ruleEvaluationDetailApplicationId ?? ($_GET['application_id'] ?? '')));

$supabaseUrl = isset($supabaseUrl) ? (string)$supabaseUrl : '';
$headers = isset($headers) && is_array($headers) ? $headers : [];
$staffOfficeId = $staffOfficeId ?? null;
$staffRoleKey = $staffRoleKey ?? null;

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

$isAdminScope = strtolower((string)$staffRoleKey) === 'admin';

$toScalarText = static function (mixed $value, string $fallback = ''): string {
    if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
        return trim((string)$value);
    }

    return $fallback;
};

$extractStructuredInputs = static function (string $feedbackText): array {
    if ($feedbackText === '') {
        return [];
    }

    $decoded = json_decode($feedbackText, true);
    if (!is_array($decoded)) {
        return [];
    }

    return [
        'eligibility' => cleanText($decoded['eligibility'] ?? $decoded['eligibility_type'] ?? null),
        'education_years' => $decoded['education_years'] ?? $decoded['years_in_college'] ?? null,
        'training_hours' => $decoded['training_hours'] ?? $decoded['hours_of_training'] ?? null,
        'experience_years' => $decoded['experience_years'] ?? $decoded['years_of_experience'] ?? null,
    ];
};

$estimateProfileFromSignals = static function (array $application, array $documentTypes, array $interviews): array {
    $applicationStatus = strtolower((string)(cleanText($application['application_status'] ?? null) ?? 'submitted'));
    $applicant = is_array($application['applicant'] ?? null) ? (array)$application['applicant'] : [];

    $hasEligibilityDoc = isset($documentTypes['eligibility']) || isset($documentTypes['license']) || isset($documentTypes['id']);
    $eligibility = $hasEligibilityDoc ? 'career service sub professional' : 'n/a';

    $educationYears = 0.0;
    if (isset($documentTypes['transcript']) || isset($documentTypes['pds'])) {
        $educationYears = 2.0;
    }

    $trainingHours = 0.0;
    if (isset($documentTypes['certificate'])) {
        $trainingHours += 4.0;
    }
    if ((cleanText($applicant['portfolio_url'] ?? null) ?? '') !== '') {
        $trainingHours += 2.0;
    }

    $experienceYears = 0.0;
    if ((cleanText($applicant['resume_url'] ?? null) ?? '') !== '') {
        $experienceYears += 1.0;
    }
    if (in_array($applicationStatus, ['screening', 'shortlisted', 'interview', 'offer', 'hired'], true)) {
        $experienceYears += 0.5;
    }
    if (in_array($applicationStatus, ['offer', 'hired'], true)) {
        $experienceYears += 0.5;
    }
    foreach ($interviews as $interview) {
        $result = strtolower((string)(cleanText($interview['result'] ?? null) ?? ''));
        if (in_array($result, ['pass', 'passed', 'recommended', 'completed'], true)) {
            $experienceYears += 0.25;
            break;
        }
    }

    return [
        'eligibility' => $eligibility,
        'education_years' => $educationYears,
        'training_hours' => $trainingHours,
        'experience_years' => $experienceYears,
    ];
};

$loadScopedApplications = static function () use ($supabaseUrl, $headers, $appendDataError, $isAdminScope, $staffOfficeId, $extractStructuredInputs, $estimateProfileFromSignals, $toScalarText): array {
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $applicationEndpoint = $supabaseUrl
        . '/rest/v1/applications?select=id,application_ref_no,application_status,submitted_at,job_posting_id,job:job_postings(id,title,office_id,position:job_positions(position_title)),applicant:applicant_profiles(full_name,email,user_id,resume_url,portfolio_url)'
        . '&application_status=not.eq.hired'
        . '&order=submitted_at.desc&limit=2000';

    if (!$isAdminScope && isValidUuid((string)$staffOfficeId)) {
        $applicationEndpoint .= '&job.office_id=eq.' . rawurlencode((string)$staffOfficeId);
    }

    $applicationsResponse = apiRequest('GET', $applicationEndpoint, $headers);
    $appendDataError('Rule-based applications', $applicationsResponse);
    $applicationRows = isSuccessful($applicationsResponse) ? (array)($applicationsResponse['data'] ?? []) : [];

    $applicationIds = [];
    foreach ($applicationRows as $applicationRow) {
        $applicationId = cleanText($applicationRow['id'] ?? null) ?? '';
        if (isValidUuid($applicationId)) {
            $applicationIds[] = $applicationId;
        }
    }

    $interviewsByApplicationId = [];
    $documentsByApplicationId = [];
    $feedbackByApplicationId = [];
    $latestInterviewByApplicationId = [];
    $latestFeedbackByApplicationId = [];

    if (!empty($applicationIds)) {
        $applicationIdFilter = rawurlencode('(' . implode(',', $applicationIds) . ')');

        $interviewsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/application_interviews?select=application_id,score,result,interview_stage,scheduled_at,remarks,interviewer:user_accounts(email)'
            . '&application_id=in.' . $applicationIdFilter
            . '&order=scheduled_at.desc&limit=4000',
            $headers
        );
        $appendDataError('Rule-based interviews', $interviewsResponse);
        $interviewRows = isSuccessful($interviewsResponse) ? (array)($interviewsResponse['data'] ?? []) : [];
        foreach ($interviewRows as $interviewRow) {
            $applicationId = cleanText($interviewRow['application_id'] ?? null) ?? '';
            if (!isValidUuid($applicationId)) {
                continue;
            }

            if (!isset($interviewsByApplicationId[$applicationId])) {
                $interviewsByApplicationId[$applicationId] = [];
            }

            $interviewsByApplicationId[$applicationId][] = $interviewRow;
            if (!isset($latestInterviewByApplicationId[$applicationId])) {
                $latestInterviewByApplicationId[$applicationId] = $interviewRow;
            }
        }

        $documentsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/application_documents?select=application_id,document_type'
            . '&application_id=in.' . $applicationIdFilter
            . '&limit=6000',
            $headers
        );
        $appendDataError('Rule-based documents', $documentsResponse);
        $documentRows = isSuccessful($documentsResponse) ? (array)($documentsResponse['data'] ?? []) : [];
        foreach ($documentRows as $documentRow) {
            $applicationId = cleanText($documentRow['application_id'] ?? null) ?? '';
            if (!isValidUuid($applicationId)) {
                continue;
            }

            $documentType = strtolower((string)(cleanText($documentRow['document_type'] ?? null) ?? ''));
            if ($documentType === '') {
                continue;
            }

            if (!isset($documentsByApplicationId[$applicationId])) {
                $documentsByApplicationId[$applicationId] = [];
            }

            $documentsByApplicationId[$applicationId][$documentType] = true;
        }

        $feedbackResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/application_feedback?select=application_id,decision,feedback_text,provided_at,provider:user_accounts(email)'
            . '&application_id=in.' . $applicationIdFilter
            . '&limit=2000',
            $headers
        );
        $appendDataError('Rule-based feedback', $feedbackResponse);
        $feedbackRows = isSuccessful($feedbackResponse) ? (array)($feedbackResponse['data'] ?? []) : [];
        foreach ($feedbackRows as $feedbackRow) {
            $applicationId = cleanText($feedbackRow['application_id'] ?? null) ?? '';
            if (!isValidUuid($applicationId)) {
                continue;
            }

            $feedbackByApplicationId[$applicationId] = cleanText($feedbackRow['feedback_text'] ?? null) ?? '';
            if (!isset($latestFeedbackByApplicationId[$applicationId])) {
                $latestFeedbackByApplicationId[$applicationId] = $feedbackRow;
            }
        }
    }

    $rows = [];
    $positionTitles = [];
    $summary = [
        'qualified' => 0,
        'not_qualified' => 0,
        'total' => 0,
    ];

    foreach ($applicationRows as $applicationRow) {
        $applicationId = cleanText($applicationRow['id'] ?? null) ?? '';
        if (!isValidUuid($applicationId)) {
            continue;
        }

        $statusRaw = strtolower((string)(cleanText($applicationRow['application_status'] ?? null) ?? 'submitted'));
        if ($statusRaw === 'hired') {
            continue;
        }

        $job = is_array($applicationRow['job'] ?? null) ? (array)$applicationRow['job'] : [];
        $jobPosition = is_array($job['position'] ?? null) ? (array)$job['position'] : [];
        $positionTitle = cleanText($jobPosition['position_title'] ?? null)
            ?? cleanText($job['title'] ?? null)
            ?? 'Unassigned Position';
        $positionKey = staffApplicantEvaluationNormalizePositionKey($positionTitle);
        $criteria = staffApplicantEvaluationResolveCriteria($supabaseUrl, $headers, $positionTitle);

        $applicant = is_array($applicationRow['applicant'] ?? null) ? (array)$applicationRow['applicant'] : [];
        $structuredInputs = $extractStructuredInputs((string)($feedbackByApplicationId[$applicationId] ?? ''));
        $signalInputs = $estimateProfileFromSignals(
            $applicationRow,
            (array)($documentsByApplicationId[$applicationId] ?? []),
            (array)($interviewsByApplicationId[$applicationId] ?? [])
        );

        $profileInput = [
            'eligibility' => $structuredInputs['eligibility'] ?? $signalInputs['eligibility'] ?? 'n/a',
            'education_years' => $structuredInputs['education_years'] ?? $signalInputs['education_years'] ?? 0,
            'training_hours' => $structuredInputs['training_hours'] ?? $signalInputs['training_hours'] ?? 0,
            'experience_years' => $structuredInputs['experience_years'] ?? $signalInputs['experience_years'] ?? 0,
        ];

        $result = staffApplicantEvaluationCompute($profileInput, $criteria);
        $resultCriteria = (array)($result['criteria'] ?? []);
        $resultProfile = (array)($result['profile'] ?? []);
        $criteriaMet = (array)($result['criteria_met'] ?? []);
        $scores = (array)($result['scores'] ?? []);
        $qualified = !empty($result['qualified']);

        if ($qualified) {
            $summary['qualified']++;
        } else {
            $summary['not_qualified']++;
        }
        $summary['total']++;

        $latestInterview = (array)($latestInterviewByApplicationId[$applicationId] ?? []);
        $latestFeedback = (array)($latestFeedbackByApplicationId[$applicationId] ?? []);
        $latestInterviewResult = strtolower((string)(cleanText($latestInterview['result'] ?? null) ?? 'pending'));
        if (!in_array($latestInterviewResult, ['pass', 'fail', 'pending'], true)) {
            $latestInterviewResult = 'pending';
        }

        $row = [
            'application_id' => $applicationId,
            'application_ref_no' => cleanText($applicationRow['application_ref_no'] ?? null) ?? '-',
            'applicant_name' => cleanText($applicant['full_name'] ?? null) ?? 'Unknown Applicant',
            'applicant_email' => cleanText($applicant['email'] ?? null) ?? '-',
            'position_title' => $positionTitle,
            'position_key' => $positionKey,
            'application_status' => $statusRaw,
            'application_status_label' => ucwords(str_replace('_', ' ', $statusRaw)),
            'submitted_at_label' => formatDateTimeForPhilippines(cleanText($applicationRow['submitted_at'] ?? null), 'M d, Y'),
            'status_label' => (string)($result['status'] ?? 'Not Qualified'),
            'status_class' => (string)($result['status_class'] ?? 'bg-rose-100 text-rose-800'),
            'qualified' => $qualified,
            'total_score' => (int)($result['total_score'] ?? 0),
            'threshold' => (int)($result['threshold'] ?? 75),
            'search_text' => strtolower(trim((cleanText($applicant['full_name'] ?? null) ?? '') . ' ' . $positionTitle . ' ' . (cleanText($applicant['email'] ?? null) ?? '') . ' ' . (string)($result['status'] ?? ''))),
            'detail' => [
                'application_ref_no' => cleanText($applicationRow['application_ref_no'] ?? null) ?? '-',
                'applicant_name' => cleanText($applicant['full_name'] ?? null) ?? 'Unknown Applicant',
                'applicant_email' => cleanText($applicant['email'] ?? null) ?? '-',
                'position_title' => $positionTitle,
                'application_status_label' => ucwords(str_replace('_', ' ', $statusRaw)),
                'submitted_at_label' => formatDateTimeForPhilippines(cleanText($applicationRow['submitted_at'] ?? null), 'M d, Y'),
                'status_label' => (string)($result['status'] ?? 'Not Qualified'),
                'status_class' => (string)($result['status_class'] ?? 'bg-rose-100 text-rose-800'),
                'total_score' => (int)($result['total_score'] ?? 0),
                'threshold' => (int)($result['threshold'] ?? 75),
                'criteria' => [
                    'eligibility' => $toScalarText($resultCriteria['eligibility'] ?? null, 'n/a'),
                    'education_years' => (float)($resultCriteria['minimum_education_years'] ?? 2),
                    'training_hours' => (float)($resultCriteria['minimum_training_hours'] ?? 4),
                    'experience_years' => (float)($resultCriteria['minimum_experience_years'] ?? 1),
                ],
                'profile' => [
                    'eligibility' => $toScalarText($resultProfile['eligibility'] ?? null, 'n/a'),
                    'education_years' => (float)($resultProfile['education_years'] ?? 0),
                    'training_hours' => (float)($resultProfile['training_hours'] ?? 0),
                    'experience_years' => (float)($resultProfile['experience_years'] ?? 0),
                ],
                'scores' => [
                    'eligibility' => (int)($scores['eligibility'] ?? 0),
                    'education' => (int)($scores['education'] ?? 0),
                    'training' => (int)($scores['training'] ?? 0),
                    'experience' => (int)($scores['experience'] ?? 0),
                ],
                'criteria_met' => [
                    'eligibility' => !empty($criteriaMet['eligibility']),
                    'education' => !empty($criteriaMet['education']),
                    'training' => !empty($criteriaMet['training']),
                    'experience' => !empty($criteriaMet['experience']),
                ],
                'failed_criteria' => (array)($result['failed_criteria'] ?? []),
                'latest_interview' => [
                    'result' => $latestInterviewResult,
                    'result_label' => ucwords(str_replace('_', ' ', $latestInterviewResult)),
                    'score' => trim((string)($latestInterview['score'] ?? '')),
                    'stage' => cleanText($latestInterview['interview_stage'] ?? null) ?? '-',
                    'scheduled_label' => formatDateTimeForPhilippines(cleanText($latestInterview['scheduled_at'] ?? null), 'M d, Y h:i A'),
                    'remarks' => cleanText($latestInterview['remarks'] ?? null) ?? '-',
                    'interviewer_email' => cleanText($latestInterview['interviewer']['email'] ?? null) ?? '-',
                ],
                'latest_feedback' => [
                    'decision' => strtolower((string)(cleanText($latestFeedback['decision'] ?? null) ?? '')),
                    'decision_label' => trim((string)(cleanText($latestFeedback['decision'] ?? null) ?? '')) !== '' ? ucwords(str_replace('_', ' ', strtolower((string)$latestFeedback['decision']))) : '-',
                    'feedback_text' => cleanText($latestFeedback['feedback_text'] ?? null) ?? '-',
                    'provided_label' => formatDateTimeForPhilippines(cleanText($latestFeedback['provided_at'] ?? null), 'M d, Y'),
                    'provider_email' => cleanText($latestFeedback['provider']['email'] ?? null) ?? '-',
                ],
                'evidence_signals' => [
                    'resume_available' => (cleanText($applicant['resume_url'] ?? null) ?? '') !== '',
                    'portfolio_available' => (cleanText($applicant['portfolio_url'] ?? null) ?? '') !== '',
                    'documents' => array_values(array_keys((array)($documentsByApplicationId[$applicationId] ?? []))),
                ],
            ],
        ];

        $rows[] = $row;
        $positionTitles[$positionTitle] = true;
    }

    usort($rows, static function (array $left, array $right): int {
        if (($left['qualified'] ?? false) !== ($right['qualified'] ?? false)) {
            return !empty($left['qualified']) ? -1 : 1;
        }

        $leftScore = (int)($left['total_score'] ?? 0);
        $rightScore = (int)($right['total_score'] ?? 0);
        if ($leftScore !== $rightScore) {
            return $rightScore <=> $leftScore;
        }

        return strcmp((string)($left['applicant_name'] ?? ''), (string)($right['applicant_name'] ?? ''));
    });

    $cache = [
        'rows' => $rows,
        'summary' => $summary,
        'position_titles' => array_values(array_keys($positionTitles)),
    ];
    sort($cache['position_titles'], SORT_NATURAL | SORT_FLAG_CASE);

    return $cache;
};

if ($evaluationDataStage === 'list' || $evaluationDataStage === 'detail') {
    $scopedEvaluationState = $loadScopedApplications();
    $ruleEvaluationSummary = (array)($scopedEvaluationState['summary'] ?? $ruleEvaluationSummary);
    $ruleEvaluationPositionTitles = (array)($scopedEvaluationState['position_titles'] ?? []);
    $allRows = (array)($scopedEvaluationState['rows'] ?? []);

    if ($evaluationDataStage === 'list') {
        $filteredRows = [];
        $normalizedSearch = strtolower($ruleEvaluationFilters['search']);
        $normalizedPosition = strtolower(trim($ruleEvaluationFilters['position']));

        foreach ($allRows as $row) {
            $matchesSearch = $normalizedSearch === '' || str_contains((string)($row['search_text'] ?? ''), $normalizedSearch);
            $matchesStatus = $ruleEvaluationFilters['status'] === '' || (($row['qualified'] ?? false) ? 'qualified' : 'not_qualified') === $ruleEvaluationFilters['status'];
            $matchesPosition = $normalizedPosition === '' || strtolower((string)($row['position_title'] ?? '')) === $normalizedPosition;

            if ($matchesSearch && $matchesStatus && $matchesPosition) {
                $filteredRows[] = $row;
            }
        }

        $offset = ($ruleEvaluationPagination['page'] - 1) * $ruleEvaluationPageSize;
        $ruleEvaluationPagination['has_prev'] = $ruleEvaluationPagination['page'] > 1;
        $ruleEvaluationPagination['has_next'] = count($filteredRows) > ($offset + $ruleEvaluationPageSize);
        $ruleEvaluationPagination['prev_page'] = max(1, $ruleEvaluationPagination['page'] - 1);
        $ruleEvaluationPagination['next_page'] = $ruleEvaluationPagination['page'] + 1;
        $ruleEvaluationPagination['label'] = empty($filteredRows) ? 'No candidates found' : 'Page ' . $ruleEvaluationPagination['page'];

        $visibleRows = array_slice($filteredRows, $offset, $ruleEvaluationPageSize);
        foreach ($visibleRows as $row) {
            unset($row['detail']);
            $ruleEvaluationRows[] = $row;
        }
    }

    if ($evaluationDataStage === 'detail') {
        foreach ($allRows as $row) {
            if (strcasecmp((string)($row['application_id'] ?? ''), $ruleEvaluationDetailApplicationId) !== 0) {
                continue;
            }

            $ruleEvaluationDetailPayload = [
                'application_id' => $row['application_id'],
                'applicant_name' => $row['detail']['applicant_name'] ?? 'Applicant',
                'applicant_email' => $row['detail']['applicant_email'] ?? '-',
                'position_title' => $row['detail']['position_title'] ?? '-',
                'application_ref_no' => $row['detail']['application_ref_no'] ?? '-',
                'application_status_label' => $row['detail']['application_status_label'] ?? 'Submitted',
                'submitted_at_label' => $row['detail']['submitted_at_label'] ?? '-',
                'status_label' => $row['detail']['status_label'] ?? 'Not Qualified',
                'status_class' => $row['detail']['status_class'] ?? 'bg-slate-100 text-slate-700',
                'total_score' => $row['detail']['total_score'] ?? 0,
                'threshold' => $row['detail']['threshold'] ?? 75,
                'criteria' => $row['detail']['criteria'] ?? [],
                'profile' => $row['detail']['profile'] ?? [],
                'scores' => $row['detail']['scores'] ?? [],
                'criteria_met' => $row['detail']['criteria_met'] ?? [],
                'failed_criteria' => $row['detail']['failed_criteria'] ?? [],
                'latest_interview' => $row['detail']['latest_interview'] ?? [],
                'latest_feedback' => $row['detail']['latest_feedback'] ?? [],
                'evidence_signals' => $row['detail']['evidence_signals'] ?? [],
            ];
            break;
        }

        if (!is_array($ruleEvaluationDetailPayload)) {
            $dataLoadError = $dataLoadError ?: 'Evaluation detail was not found.';
        }
    }
}
