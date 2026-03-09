<?php

$evaluationCycleRows = [];
$evaluationDecisionRows = [];
$evaluationMetrics = [
    'open_cycles' => 0,
    'pending_reviews' => 0,
    'reviewed_records' => 0,
    'approved_records' => 0,
];

$ruleEvaluationRows = [];
$ruleEvaluationSummary = [
    'qualified' => 0,
    'not_qualified' => 0,
    'total' => 0,
];
$ruleCriteriaMap = [];
$rulePositionTitles = [];
$ruleSelectedPositionTitle = '';
$ruleSelectedCriteria = staffApplicantEvaluationDefaultCriteria();
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

$personScopeMap = [];
if (!$isAdminScope && isValidUuid((string)$staffOfficeId)) {
    $scopeResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=person_id'
        . '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
        . '&is_current=eq.true'
        . '&limit=5000',
        $headers
    );

    $appendDataError('Evaluation scope', $scopeResponse);
    $scopeRows = isSuccessful($scopeResponse) ? (array)($scopeResponse['data'] ?? []) : [];

    foreach ($scopeRows as $scopeRow) {
        $personId = cleanText($scopeRow['person_id'] ?? null);
        if ($personId === null || !isValidUuid($personId)) {
            continue;
        }

        $personScopeMap[$personId] = true;
    }
}

$cyclesResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/performance_cycles?select=id,cycle_name,period_start,period_end,status,updated_at'
    . '&order=period_start.desc&limit=500',
    $headers
);
$appendDataError('Performance cycles', $cyclesResponse);
$cycleRows = isSuccessful($cyclesResponse) ? (array)($cyclesResponse['data'] ?? []) : [];

$evaluationEndpoint = $supabaseUrl
    . '/rest/v1/performance_evaluations?select=id,cycle_id,employee_person_id,evaluator_user_id,final_rating,remarks,status,updated_at,employee:employee_person_id(first_name,surname,user_id),evaluator:evaluator_user_id(email),cycle:cycle_id(cycle_name,period_start,period_end,status)'
    . '&order=updated_at.desc&limit=2000';

if (!$isAdminScope) {
    $personIds = array_keys($personScopeMap);
    if (empty($personIds)) {
        $evaluationEndpoint .= '&id=is.null';
    } else {
        $evaluationEndpoint .= '&employee_person_id=in.' . rawurlencode('(' . implode(',', $personIds) . ')');
    }
}

$evaluationsResponse = apiRequest('GET', $evaluationEndpoint, $headers);
$appendDataError('Performance evaluations', $evaluationsResponse);
$evaluationRows = isSuccessful($evaluationsResponse) ? (array)($evaluationsResponse['data'] ?? []) : [];

$cycleStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'open' => ['Open', 'bg-emerald-100 text-emerald-800'],
        'closed' => ['Closed', 'bg-slate-200 text-slate-700'],
        'archived' => ['Archived', 'bg-slate-100 text-slate-700'],
        default => ['Draft', 'bg-amber-100 text-amber-800'],
    };
};

$evaluationStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'submitted' => ['Pending', 'bg-amber-100 text-amber-800'],
        'reviewed' => ['Reviewed', 'bg-blue-100 text-blue-800'],
        'approved' => ['Approved', 'bg-emerald-100 text-emerald-800'],
        default => ['Draft', 'bg-slate-100 text-slate-700'],
    };
};

$evaluationCountByCycle = [];

$ruleCriteriaMapRaw = staffApplicantEvaluationLoadCriteriaMap($supabaseUrl, $headers);
if (is_array($ruleCriteriaMapRaw)) {
    foreach ($ruleCriteriaMapRaw as $positionTitleKey => $criteriaValue) {
        if (!is_string($positionTitleKey)) {
            continue;
        }

        $normalizedKey = staffApplicantEvaluationNormalizePositionKey($positionTitleKey);
        if ($normalizedKey === '') {
            continue;
        }

        $ruleCriteriaMap[$normalizedKey] = staffApplicantEvaluationNormalizeCriteria($criteriaValue);
    }
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
    if (!isValidUuid($applicationId)) {
        continue;
    }

    $applicationIds[] = $applicationId;
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
        . '/rest/v1/application_interviews?select=application_id,score,result,interview_stage'
        . '&application_id=in.' . $applicationIdFilter
        . '&order=created_at.desc&limit=4000',
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
            $latestInterviewByApplicationId[$applicationId] = [
                'result' => strtolower((string)(cleanText($interviewRow['result'] ?? null) ?? '')),
                'score' => cleanText($interviewRow['score'] ?? null) ?? '',
                'interview_stage' => cleanText($interviewRow['interview_stage'] ?? null) ?? '',
            ];
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
        . '/rest/v1/application_feedback?select=application_id,feedback_text'
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
            $latestFeedbackByApplicationId[$applicationId] = [
                'decision' => strtolower((string)(cleanText($feedbackRow['decision'] ?? null) ?? '')),
                'feedback_text' => cleanText($feedbackRow['feedback_text'] ?? null) ?? '',
            ];
        }
    }
}

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

$toScalarText = static function (mixed $value, string $fallback = ''): string {
    if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
        return trim((string)$value);
    }

    return $fallback;
};

$estimateProfileFromSignals = static function (
    array $application,
    array $documentTypes,
    array $interviews
): array {
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
    $ruleCriteriaMap[$positionKey] = staffApplicantEvaluationNormalizeCriteria($criteria);

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
    $criteriaMet = (array)($result['criteria_met'] ?? []);
    $scores = (array)($result['scores'] ?? []);

    $qualified = !empty($result['qualified']);
    if ($qualified) {
        $ruleEvaluationSummary['qualified']++;
    } else {
        $ruleEvaluationSummary['not_qualified']++;
    }
    $ruleEvaluationSummary['total']++;

    $applicantName = cleanText($applicant['full_name'] ?? null) ?? 'Unknown Applicant';
    $applicantEmail = cleanText($applicant['email'] ?? null) ?? '-';
    $statusLabel = ucwords(str_replace('_', ' ', $statusRaw));

    $latestInterview = (array)($latestInterviewByApplicationId[$applicationId] ?? []);
    $latestFeedback = (array)($latestFeedbackByApplicationId[$applicationId] ?? []);

    $latestInterviewResult = strtolower((string)($latestInterview['result'] ?? 'pending'));
    if (!in_array($latestInterviewResult, ['pass', 'fail', 'pending'], true)) {
        $latestInterviewResult = 'pending';
    }

    $latestInterviewScore = trim((string)($latestInterview['score'] ?? ''));
    $latestFeedbackDecision = strtolower((string)($latestFeedback['decision'] ?? ''));
    $latestFeedbackText = trim((string)($latestFeedback['feedback_text'] ?? ''));
    $canSubmitFinalEvaluation = in_array($statusRaw, ['interview', 'offer'], true);

    $ruleEvaluationRows[] = [
        'application_id' => $applicationId,
        'application_ref_no' => cleanText($applicationRow['application_ref_no'] ?? null) ?? '-',
        'applicant_name' => $applicantName,
        'applicant_email' => $applicantEmail,
        'position_title' => $positionTitle,
        'position_key' => $positionKey,
        'application_status' => $statusRaw,
        'application_status_label' => $statusLabel,
        'submitted_at_label' => formatDateTimeForPhilippines(cleanText($applicationRow['submitted_at'] ?? null), 'M d, Y'),
        'status_label' => (string)($result['status'] ?? 'Not Qualified'),
        'status_class' => (string)($result['status_class'] ?? 'bg-rose-100 text-rose-800'),
        'qualified' => $qualified,
        'total_score' => (int)($result['total_score'] ?? 0),
        'threshold' => (int)($result['threshold'] ?? 75),
        'eligibility_input' => $toScalarText(((array)($result['profile'] ?? [])['eligibility'] ?? null), 'n/a'),
        'education_years_input' => (float)((array)($result['profile'] ?? [])['education_years'] ?? 0),
        'training_hours_input' => (float)((array)($result['profile'] ?? [])['training_hours'] ?? 0),
        'experience_years_input' => (float)((array)($result['profile'] ?? [])['experience_years'] ?? 0),
        'required_eligibility' => $toScalarText(((array)($result['criteria'] ?? [])['eligibility'] ?? null), 'n/a'),
        'required_education_years' => (float)((array)($result['criteria'] ?? [])['minimum_education_years'] ?? 2),
        'required_training_hours' => (float)((array)($result['criteria'] ?? [])['minimum_training_hours'] ?? 4),
        'required_experience_years' => (float)((array)($result['criteria'] ?? [])['minimum_experience_years'] ?? 1),
        'eligibility_score' => (int)($scores['eligibility'] ?? 0),
        'education_score' => (int)($scores['education'] ?? 0),
        'training_score' => (int)($scores['training'] ?? 0),
        'experience_score' => (int)($scores['experience'] ?? 0),
        'eligibility_meets' => !empty($criteriaMet['eligibility']),
        'education_meets' => !empty($criteriaMet['education']),
        'training_meets' => !empty($criteriaMet['training']),
        'experience_meets' => !empty($criteriaMet['experience']),
        'failed_criteria' => (array)($result['failed_criteria'] ?? []),
        'latest_interview_result' => $latestInterviewResult,
        'latest_interview_score' => $latestInterviewScore,
        'latest_feedback_decision' => $latestFeedbackDecision,
        'latest_feedback_text' => $latestFeedbackText,
        'can_submit_final_evaluation' => $canSubmitFinalEvaluation,
        'search_text' => strtolower(trim($applicantName . ' ' . $positionTitle . ' ' . $applicantEmail . ' ' . (string)($result['status'] ?? ''))),
    ];
}

usort($ruleEvaluationRows, static function (array $left, array $right): int {
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

foreach ($ruleEvaluationRows as $row) {
    $title = trim((string)($row['position_title'] ?? ''));
    if ($title === '') {
        continue;
    }

    $rulePositionTitles[$title] = true;
}

$rulePositionTitles = array_values(array_keys($rulePositionTitles));
sort($rulePositionTitles, SORT_NATURAL | SORT_FLAG_CASE);

$ruleSelectedPositionTitle = trim((string)(cleanText($_GET['position_title'] ?? null) ?? ''));
if ($ruleSelectedPositionTitle === '' && !empty($rulePositionTitles)) {
    $ruleSelectedPositionTitle = (string)$rulePositionTitles[0];
}

$ruleSelectedCriteria = staffApplicantEvaluationResolveCriteria($supabaseUrl, $headers, $ruleSelectedPositionTitle);

foreach ($evaluationRows as $evaluation) {
    $evaluationId = cleanText($evaluation['id'] ?? null) ?? '';
    if (!isValidUuid($evaluationId)) {
        continue;
    }

    $statusRaw = strtolower((string)(cleanText($evaluation['status'] ?? null) ?? 'draft'));
    [$statusLabel, $statusClass] = $evaluationStatusPill($statusRaw);

    if ($statusRaw === 'submitted') {
        $evaluationMetrics['pending_reviews']++;
    }
    if ($statusRaw === 'reviewed') {
        $evaluationMetrics['reviewed_records']++;
    }
    if ($statusRaw === 'approved') {
        $evaluationMetrics['approved_records']++;
    }

    $employeeName = trim(
        (string)(cleanText($evaluation['employee']['first_name'] ?? null) ?? '')
        . ' '
        . (string)(cleanText($evaluation['employee']['surname'] ?? null) ?? '')
    );
    if ($employeeName === '') {
        $employeeName = 'Unknown Employee';
    }

    $cycleName = cleanText($evaluation['cycle']['cycle_name'] ?? null) ?? 'Unassigned Cycle';
    $evaluatorEmail = cleanText($evaluation['evaluator']['email'] ?? null) ?? '-';

    $cycleId = cleanText($evaluation['cycle_id'] ?? null) ?? '';
    if (isValidUuid($cycleId)) {
        $evaluationCountByCycle[$cycleId] = (int)($evaluationCountByCycle[$cycleId] ?? 0) + 1;
    }

    $evaluationDecisionRows[] = [
        'id' => $evaluationId,
        'employee_name' => $employeeName,
        'cycle_name' => $cycleName,
        'evaluator_email' => $evaluatorEmail,
        'rating_label' => is_numeric($evaluation['final_rating'] ?? null) ? number_format((float)$evaluation['final_rating'], 2) . ' / 5.00' : '-',
        'remarks' => cleanText($evaluation['remarks'] ?? null) ?? '-',
        'status_raw' => $statusRaw,
        'status_filter' => strtolower($statusLabel),
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'updated_label' => formatDateTimeForPhilippines(cleanText($evaluation['updated_at'] ?? null), 'M d, Y'),
        'search_text' => strtolower(trim($employeeName . ' ' . $cycleName . ' ' . $evaluatorEmail . ' ' . $statusLabel)),
    ];
}

foreach ($cycleRows as $cycle) {
    $cycleId = cleanText($cycle['id'] ?? null) ?? '';
    if (!isValidUuid($cycleId)) {
        continue;
    }

    $statusRaw = strtolower((string)(cleanText($cycle['status'] ?? null) ?? 'draft'));
    [$statusLabel, $statusClass] = $cycleStatusPill($statusRaw);

    if ($statusRaw === 'open') {
        $evaluationMetrics['open_cycles']++;
    }

    $cycleName = cleanText($cycle['cycle_name'] ?? null) ?? 'Unnamed Cycle';

    $evaluationCycleRows[] = [
        'id' => $cycleId,
        'cycle_name' => $cycleName,
        'period_range' => formatDateTimeForPhilippines(cleanText($cycle['period_start'] ?? null), 'M d, Y')
            . ' - '
            . formatDateTimeForPhilippines(cleanText($cycle['period_end'] ?? null), 'M d, Y'),
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'evaluation_count' => (int)($evaluationCountByCycle[$cycleId] ?? 0),
    ];
}
