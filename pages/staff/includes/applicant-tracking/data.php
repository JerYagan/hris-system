<?php

$trackingRows = [];
$screeningQueueRows = [];
$hiredApplicantRows = [];
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

$applicationsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/applications?select=id,application_ref_no,application_status,submitted_at,updated_at,job_posting_id,job:job_postings(title,qualifications),applicant:applicant_profiles(full_name,email,user_id,mobile_no,current_address,resume_url,portfolio_url)'
    . '&order=updated_at.desc&limit=2000',
    $headers
);
$appendDataError('Applications', $applicationsResponse);
$applicationRows = isSuccessful($applicationsResponse) ? (array)($applicationsResponse['data'] ?? []) : [];

$applicationIds = [];
foreach ($applicationRows as $row) {
    $applicationId = cleanText($row['id'] ?? null) ?? '';
    if ($applicationId === '' || !isValidUuid($applicationId)) {
        continue;
    }
    $applicationIds[] = $applicationId;
}

$latestInterviewByApplication = [];
$interviewCountByApplication = [];
$latestFeedbackByApplication = [];
if (!empty($applicationIds)) {
    $interviewResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/application_interviews?select=application_id,scheduled_at,interview_stage,result,remarks,score,interviewer:user_accounts(email)'
        . '&application_id=in.' . rawurlencode('(' . implode(',', $applicationIds) . ')')
        . '&order=scheduled_at.desc&limit=4000',
        $headers
    );
    $appendDataError('Application interviews', $interviewResponse);
    $interviewRows = isSuccessful($interviewResponse) ? (array)($interviewResponse['data'] ?? []) : [];

    foreach ($interviewRows as $interview) {
        $applicationId = cleanText($interview['application_id'] ?? null) ?? '';
        if ($applicationId === '' || !isValidUuid($applicationId)) {
            continue;
        }

        $interviewCountByApplication[$applicationId] = (int)($interviewCountByApplication[$applicationId] ?? 0) + 1;
        if (isset($latestInterviewByApplication[$applicationId])) {
            continue;
        }

        $latestInterviewByApplication[$applicationId] = [
            'scheduled_at' => cleanText($interview['scheduled_at'] ?? null) ?? '',
            'interview_stage' => cleanText($interview['interview_stage'] ?? null) ?? '',
            'result' => cleanText($interview['result'] ?? null) ?? '',
            'remarks' => cleanText($interview['remarks'] ?? null) ?? '',
            'score' => cleanText($interview['score'] ?? null) ?? '',
            'interviewer_email' => cleanText($interview['interviewer']['email'] ?? null) ?? '',
        ];
    }

    $feedbackResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/application_feedback?select=application_id,decision,feedback_text,provided_at,provider:user_accounts(email)'
        . '&application_id=in.' . rawurlencode('(' . implode(',', $applicationIds) . ')')
        . '&order=provided_at.desc&limit=4000',
        $headers
    );
    $appendDataError('Application feedback', $feedbackResponse);
    $feedbackRows = isSuccessful($feedbackResponse) ? (array)($feedbackResponse['data'] ?? []) : [];

    foreach ($feedbackRows as $feedback) {
        $applicationId = cleanText($feedback['application_id'] ?? null) ?? '';
        if ($applicationId === '' || !isValidUuid($applicationId) || isset($latestFeedbackByApplication[$applicationId])) {
            continue;
        }

        $latestFeedbackByApplication[$applicationId] = [
            'decision' => cleanText($feedback['decision'] ?? null) ?? '',
            'feedback_text' => cleanText($feedback['feedback_text'] ?? null) ?? '',
            'provided_at' => cleanText($feedback['provided_at'] ?? null) ?? '',
            'provider_email' => cleanText($feedback['provider']['email'] ?? null) ?? '',
        ];
    }

    $documentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/application_documents?select=application_id,document_type'
        . '&application_id=in.' . rawurlencode('(' . implode(',', $applicationIds) . ')')
        . '&limit=6000',
        $headers
    );
    $appendDataError('Application documents', $documentResponse);
    $documentRows = isSuccessful($documentResponse) ? (array)($documentResponse['data'] ?? []) : [];

    $documentTypesByApplication = [];
    foreach ($documentRows as $documentRow) {
        $applicationId = cleanText($documentRow['application_id'] ?? null) ?? '';
        if ($applicationId === '' || !isValidUuid($applicationId)) {
            continue;
        }

        $documentType = strtolower((string)(cleanText($documentRow['document_type'] ?? null) ?? ''));
        if ($documentType === '') {
            continue;
        }

        if (!isset($documentTypesByApplication[$applicationId])) {
            $documentTypesByApplication[$applicationId] = [];
        }
        $documentTypesByApplication[$applicationId][$documentType] = true;
    }
} else {
    $documentTypesByApplication = [];
}

$employmentResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/employment_records?select=id,person:people!employment_records_person_id_fkey(user_id),is_current'
    . '&is_current=eq.true&limit=5000',
    $headers
);
$appendDataError('Employment records', $employmentResponse);
$employmentRows = isSuccessful($employmentResponse) ? (array)($employmentResponse['data'] ?? []) : [];

$hasCurrentEmploymentByUserId = [];
foreach ($employmentRows as $employmentRow) {
    $userId = strtolower((string)(cleanText($employmentRow['person']['user_id'] ?? null) ?? ''));
    if (!isValidUuid($userId)) {
        continue;
    }

    $hasCurrentEmploymentByUserId[$userId] = true;
}

$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'hired', 'offer', 'shortlisted' => [ucfirst($key), 'bg-emerald-100 text-emerald-800'],
        'submitted', 'screening', 'interview' => [ucfirst($key), 'bg-amber-100 text-amber-800'],
        'rejected', 'withdrawn' => [ucfirst($key), 'bg-rose-100 text-rose-800'],
        default => [ucfirst($key !== '' ? $key : 'submitted'), 'bg-slate-100 text-slate-700'],
    };
};

$statusStageLabel = static function (string $status): string {
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

$estimateEvaluationProfile = static function (
    string $statusRaw,
    string $postingQualifications,
    string $resumeUrl,
    string $portfolioUrl,
    array $documentTypes,
    int $interviewCount,
    string $interviewResultRaw
): array {
    $eligibility = 'n/a';
    if (in_array('eligibility', $documentTypes, true) || in_array('license', $documentTypes, true)) {
        $eligibility = 'civil service';
    }

    $educationYears = 0.0;
    if (
        in_array('transcript', $documentTypes, true)
        || in_array('diploma', $documentTypes, true)
        || in_array('tor', $documentTypes, true)
        || trim($postingQualifications) !== ''
    ) {
        $educationYears = 2.0;
    }

    $trainingHours = 0.0;
    if (in_array('certificate', $documentTypes, true)) {
        $trainingHours += 8.0;
    }
    if (in_array('training', $documentTypes, true)) {
        $trainingHours += 8.0;
    }
    if ($portfolioUrl !== '') {
        $trainingHours += 4.0;
    }

    $experienceYears = match (strtolower($statusRaw)) {
        'hired' => 3.0,
        'offer' => 2.0,
        'interview' => 1.5,
        'shortlisted' => 1.0,
        'screening' => 0.5,
        default => 0.0,
    };
    if ($resumeUrl !== '') {
        $experienceYears += 0.5;
    }
    if ($interviewCount > 0) {
        $experienceYears += min(1.0, $interviewCount * 0.25);
    }
    if (in_array($interviewResultRaw, ['passed', 'recommended', 'completed'], true)) {
        $experienceYears += 0.5;
    }

    return [
        'eligibility' => $eligibility,
        'education_years' => $educationYears,
        'training_hours' => $trainingHours,
        'experience_years' => $experienceYears,
    ];
};

foreach ($applicationRows as $application) {
    $applicationId = cleanText($application['id'] ?? null) ?? '';
    if (!isValidUuid($applicationId)) {
        continue;
    }

    $statusRaw = strtolower((string)(cleanText($application['application_status'] ?? null) ?? 'submitted'));
    [$statusLabel, $statusClass] = $statusPill($statusRaw);

    $jobRow = is_array($application['job'] ?? null) ? (array)$application['job'] : [];
    $postingTitle = cleanText($jobRow['title'] ?? null) ?? 'Job Posting';
    $postingQualifications = cleanText($jobRow['qualifications'] ?? null) ?? '';

    $applicantRow = is_array($application['applicant'] ?? null) ? (array)$application['applicant'] : [];
    $applicantName = cleanText($applicantRow['full_name'] ?? null) ?? 'Applicant';
    $applicantEmail = cleanText($applicantRow['email'] ?? null) ?? '-';
    $applicantUserId = cleanText($applicantRow['user_id'] ?? null) ?? '';
    $resumeUrl = cleanText($applicantRow['resume_url'] ?? null) ?? '';
    $portfolioUrl = cleanText($applicantRow['portfolio_url'] ?? null) ?? '';

    $latestInterview = $latestInterviewByApplication[$applicationId] ?? null;
    $interviewMeta = '-';
    $interviewResultRaw = '';
    $interviewFeedbackMeta = '-';
    if (is_array($latestInterview)) {
        $stage = ucwords(str_replace('_', ' ', strtolower((string)($latestInterview['interview_stage'] ?? ''))));
        $when = formatDateTimeForPhilippines(cleanText($latestInterview['scheduled_at'] ?? null), 'M d, Y');
        $interviewMeta = trim($stage . ($when !== '-' ? ' • ' . $when : ''));
        $interviewResultRaw = strtolower((string)($latestInterview['result'] ?? ''));

        $resultLabel = $interviewResultRaw !== '' ? ucwords(str_replace('_', ' ', $interviewResultRaw)) : 'Pending';
        $remarks = trim((string)($latestInterview['remarks'] ?? ''));
        $interviewerEmail = trim((string)($latestInterview['interviewer_email'] ?? ''));
        $interviewFeedbackMeta = 'Result: ' . $resultLabel;
        if ($remarks !== '') {
            $interviewFeedbackMeta .= ' • ' . $remarks;
        }
        if ($interviewerEmail !== '') {
            $interviewFeedbackMeta .= ' • By: ' . $interviewerEmail;
        }
    }

    $latestFeedback = $latestFeedbackByApplication[$applicationId] ?? null;
    $feedbackSummary = '-';
    if (is_array($latestFeedback)) {
        $decisionLabel = $feedbackDecisionLabel((string)($latestFeedback['decision'] ?? ''));
        $feedbackText = trim((string)($latestFeedback['feedback_text'] ?? ''));
        $feedbackAt = formatDateTimeForPhilippines(cleanText($latestFeedback['provided_at'] ?? null), 'M d, Y');
        $providerEmail = trim((string)($latestFeedback['provider_email'] ?? ''));

        $feedbackSummary = $decisionLabel;
        if ($feedbackText !== '') {
            $feedbackSummary .= ' • ' . $feedbackText;
        }
        if ($feedbackAt !== '-') {
            $feedbackSummary .= ' • ' . $feedbackAt;
        }
        if ($providerEmail !== '') {
            $feedbackSummary .= ' • By: ' . $providerEmail;
        }
    }

    if ($feedbackSummary === '-' && $interviewFeedbackMeta !== '-') {
        $feedbackSummary = $interviewFeedbackMeta;
    }

    $documentTypes = array_keys((array)($documentTypesByApplication[$applicationId] ?? []));
    $interviewCount = (int)($interviewCountByApplication[$applicationId] ?? 0);

    $positionCriteria = staffApplicantEvaluationResolveCriteria($supabaseUrl, $headers, $postingTitle);
    $applicantProfileInput = $estimateEvaluationProfile(
        $statusRaw,
        $postingQualifications,
        $resumeUrl,
        $portfolioUrl,
        $documentTypes,
        $interviewCount,
        $interviewResultRaw
    );
    $evaluationResult = staffApplicantEvaluationCompute($applicantProfileInput, $positionCriteria);

    $evaluationScore = (int)($evaluationResult['total_score'] ?? 0);
    $evaluationLabel = (string)($evaluationResult['status'] ?? 'Not Qualified');
    $evaluationClass = (string)($evaluationResult['status_class'] ?? 'bg-rose-100 text-rose-800');

    $breakdownScores = (array)($evaluationResult['scores'] ?? []);
    $qualificationScore = (int)(($breakdownScores['eligibility'] ?? 0) + ($breakdownScores['education'] ?? 0));
    $experienceScore = (int)($breakdownScores['experience'] ?? 0);
    $skillsScore = (int)($breakdownScores['training'] ?? 0);

    $isAlreadyEmployee = isValidUuid($applicantUserId) && !empty($hasCurrentEmploymentByUserId[strtolower($applicantUserId)]);
    $stageLabel = $statusStageLabel($statusRaw);

    $rowPayload = [
        'id' => $applicationId,
        'application_ref_no' => cleanText($application['application_ref_no'] ?? null) ?? '-',
        'applicant_name' => $applicantName,
        'applicant_email' => $applicantEmail,
        'posting_title' => $postingTitle,
        'status_raw' => $statusRaw,
        'status_filter' => strtolower($statusLabel),
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'submitted_label' => formatDateTimeForPhilippines(cleanText($application['submitted_at'] ?? null), 'M d, Y'),
        'updated_label' => formatDateTimeForPhilippines(cleanText($application['updated_at'] ?? null), 'M d, Y'),
        'interview_meta' => $interviewMeta,
        'feedback_meta' => $feedbackSummary,
        'interview_feedback_meta' => $interviewFeedbackMeta,
        'current_stage_label' => $stageLabel,
        'evaluation_score' => $evaluationScore,
        'evaluation_label' => $evaluationLabel,
        'evaluation_class' => $evaluationClass,
        'qualification_score' => $qualificationScore,
        'experience_score' => $experienceScore,
        'skills_score' => $skillsScore,
        'evaluation_threshold' => (int)($evaluationResult['threshold'] ?? 75),
        'evaluation_failed_criteria' => (array)($evaluationResult['failed_criteria'] ?? []),
        'can_add_employee' => $statusRaw === 'hired' && !$isAlreadyEmployee,
        'already_employee' => $isAlreadyEmployee,
        'search_text' => strtolower(trim($applicantName . ' ' . $postingTitle . ' ' . $statusLabel . ' ' . $applicantEmail . ' ' . $evaluationLabel . ' ' . $feedbackSummary . ' ' . $stageLabel)),
    ];

    $trackingRows[] = $rowPayload;

    if (in_array($statusRaw, ['screening', 'shortlisted', 'interview', 'offer'], true)) {
        $screeningQueueRows[] = $rowPayload;
    }

    if ($statusRaw === 'hired') {
        $hiredApplicantRows[] = $rowPayload;
    }
}