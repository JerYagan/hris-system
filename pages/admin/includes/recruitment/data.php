<?php

$activeRecruitmentRows = [];
$archivedRecruitmentRows = [];
$applicationDeadlineRows = [];
$postingViewById = [];
$eligibilityConfig = [
    'policy_default' => 'career service sub professional',
    'position_overrides' => [],
];
$positionCriteriaConfig = [
    'position_overrides' => [],
];
$recruitmentEmailTemplates = [
    'submitted' => [
        'subject' => 'Application Submitted: {application_ref_no}',
        'body' => 'Hello {applicant_name},<br><br>Your application for <strong>{job_title}</strong> has been submitted successfully.<br>Reference: <strong>{application_ref_no}</strong><br><br>Remarks: {remarks}<br><br>Thank you.',
    ],
    'passed' => [
        'subject' => 'Application Update: Passed Initial Screening',
        'body' => 'Hello {applicant_name},<br><br>Good news. You passed initial screening for <strong>{job_title}</strong>.<br>Reference: <strong>{application_ref_no}</strong><br><br>Remarks: {remarks}<br><br>Please wait for next instructions.',
    ],
    'failed' => [
        'subject' => 'Application Update: Not Qualified',
        'body' => 'Hello {applicant_name},<br><br>Thank you for applying to <strong>{job_title}</strong>.<br>Reference: <strong>{application_ref_no}</strong><br><br>Result: Not Qualified.<br>Remarks: {remarks}<br><br>We appreciate your interest.',
    ],
    'next_stage' => [
        'subject' => 'Application Update: Next Stage',
        'body' => 'Hello {applicant_name},<br><br>Your application for <strong>{job_title}</strong> has moved to the next stage.<br>Reference: <strong>{application_ref_no}</strong><br><br>Remarks: {remarks}<br><br>Please monitor your account and email for schedule details.',
    ],
];

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

$postingsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/job_postings?select=id,title,office_id,position_id,plantilla_item_no,description,qualifications,responsibilities,required_documents,posting_status,open_date,close_date,updated_at,office:offices(office_name),position:job_positions(position_title,employment_classification)&order=updated_at.desc&limit=500',
    $headers
);

$officesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/offices?select=id,office_name&is_active=eq.true&order=office_name.asc&limit=500',
    $headers
);

$positionsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/job_positions?select=id,position_title,employment_classification&is_active=eq.true&order=position_title.asc&limit=1000',
    $headers
);

$applicationsCountResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/applications?select=job_posting_id',
    array_merge($headers, ['Prefer: count=exact'])
);

$postings = isSuccessful($postingsResponse) ? (array)($postingsResponse['data'] ?? []) : [];
$officeOptions = isSuccessful($officesResponse) ? (array)($officesResponse['data'] ?? []) : [];
$positionOptions = isSuccessful($positionsResponse) ? (array)($positionsResponse['data'] ?? []) : [];

$applicationCountsByPosting = [];
foreach ((array)($applicationsCountResponse['data'] ?? []) as $appRow) {
    $postingId = (string)($appRow['job_posting_id'] ?? '');
    if ($postingId === '') {
        continue;
    }

    $applicationCountsByPosting[$postingId] = (int)($applicationCountsByPosting[$postingId] ?? 0) + 1;
}

$postingIds = [];
foreach ($postings as $posting) {
    $postingId = (string)($posting['id'] ?? '');
    if ($postingId === '') {
        continue;
    }
    $postingIds[] = $postingId;
}

$applicationsByPosting = [];
$basisByApplicationId = [];
$documentsByApplicationId = [];
$interviewsByApplicationId = [];
$feedbackTextByApplicationId = [];
$evaluationRecommendationByApplicationId = [];
$peopleByUserId = [];
if (!empty($postingIds)) {
    $postingIdList = implode(',', $postingIds);

    $postingApplicationsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/applications?select=id,job_posting_id,application_status,submitted_at,applicant:applicant_profiles(user_id,full_name,email,mobile_no,current_address,resume_url,portfolio_url)'
        . '&job_posting_id=in.' . rawurlencode('(' . $postingIdList . ')')
        . '&order=submitted_at.desc&limit=5000',
        $headers
    );

    $postingApplications = isSuccessful($postingApplicationsResponse)
        ? (array)($postingApplicationsResponse['data'] ?? [])
        : [];

    $applicationIds = [];
    $applicantUserIds = [];
    foreach ($postingApplications as $application) {
        $applicationId = (string)($application['id'] ?? '');
        $postingId = (string)($application['job_posting_id'] ?? '');
        if ($applicationId === '' || $postingId === '') {
            continue;
        }

        if (!isset($applicationsByPosting[$postingId])) {
            $applicationsByPosting[$postingId] = [];
        }
        $applicationsByPosting[$postingId][] = $application;
        $applicationIds[] = $applicationId;

        $applicantUserId = strtolower(trim((string)($application['applicant']['user_id'] ?? '')));
        if ($applicantUserId !== '') {
            $applicantUserIds[$applicantUserId] = true;
        }
    }

    if (!empty($applicantUserIds)) {
        $applicantUserIdList = implode(',', array_keys($applicantUserIds));
        $peopleResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/people?select=user_id,profile_photo_url&user_id=in.' . rawurlencode('(' . $applicantUserIdList . ')')
            . '&limit=5000',
            $headers
        );

        $peopleRows = isSuccessful($peopleResponse)
            ? (array)($peopleResponse['data'] ?? [])
            : [];

        foreach ($peopleRows as $personRow) {
            $userId = strtolower(trim((string)($personRow['user_id'] ?? '')));
            if ($userId === '') {
                continue;
            }

            $peopleByUserId[$userId] = [
                'profile_photo_url' => trim((string)($personRow['profile_photo_url'] ?? '')),
            ];
        }
    }

    if (!empty($applicationIds)) {
        $applicationIdIn = rawurlencode('(' . implode(',', $applicationIds) . ')');

        $statusHistoryResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/application_status_history?select=application_id,notes,created_at'
            . '&application_id=in.' . $applicationIdIn
            . '&order=created_at.desc&limit=10000',
            $headers
        );

        $applicationDocumentsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/application_documents?select=application_id,document_type,file_url,file_name,mime_type,uploaded_at'
            . '&application_id=in.' . $applicationIdIn
            . '&order=uploaded_at.desc&limit=10000',
            $headers
        );

        $applicationInterviewsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/application_interviews?select=application_id,result,scheduled_at'
            . '&application_id=in.' . $applicationIdIn
            . '&order=scheduled_at.desc&limit=10000',
            $headers
        );

        $applicationFeedbackResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/application_feedback?select=application_id,feedback_text,provided_at'
            . '&application_id=in.' . $applicationIdIn
            . '&order=provided_at.desc&limit=10000',
            $headers
        );

        $evaluationRecommendationSettingResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('evaluation.rule_based.recommendations')
            . '&limit=1',
            $headers
        );

        $statusHistoryRows = isSuccessful($statusHistoryResponse)
            ? (array)($statusHistoryResponse['data'] ?? [])
            : [];

        foreach ($statusHistoryRows as $historyRow) {
            $applicationId = (string)($historyRow['application_id'] ?? '');
            if ($applicationId === '' || isset($basisByApplicationId[$applicationId])) {
                continue;
            }

            $notes = trim((string)($historyRow['notes'] ?? ''));
            if ($notes === '') {
                $basisByApplicationId[$applicationId] = '-';
                continue;
            }

            $parts = explode('|', $notes, 2);
            $basis = trim((string)($parts[0] ?? ''));
            $basisByApplicationId[$applicationId] = $basis !== '' ? $basis : '-';
        }

        $applicationDocuments = isSuccessful($applicationDocumentsResponse)
            ? (array)($applicationDocumentsResponse['data'] ?? [])
            : [];

        foreach ($applicationDocuments as $documentRow) {
            $applicationId = (string)($documentRow['application_id'] ?? '');
            if ($applicationId === '') {
                continue;
            }

            if (!isset($documentsByApplicationId[$applicationId])) {
                $documentsByApplicationId[$applicationId] = [];
            }

            $documentsByApplicationId[$applicationId][] = [
                'document_type' => strtolower(trim((string)($documentRow['document_type'] ?? 'other'))),
                'file_name' => trim((string)($documentRow['file_name'] ?? 'Document')),
                'file_url' => $resolveRecruitmentDocumentUrl((string)($documentRow['file_url'] ?? '')),
                'mime_type' => trim((string)($documentRow['mime_type'] ?? '')),
                'uploaded_at' => trim((string)($documentRow['uploaded_at'] ?? '')),
            ];
        }

        $applicationInterviews = isSuccessful($applicationInterviewsResponse)
            ? (array)($applicationInterviewsResponse['data'] ?? [])
            : [];

        foreach ($applicationInterviews as $interviewRow) {
            $applicationId = (string)($interviewRow['application_id'] ?? '');
            if ($applicationId === '') {
                continue;
            }
            if (!isset($interviewsByApplicationId[$applicationId])) {
                $interviewsByApplicationId[$applicationId] = [];
            }
            $interviewsByApplicationId[$applicationId][] = $interviewRow;
        }

        $applicationFeedbackRows = isSuccessful($applicationFeedbackResponse)
            ? (array)($applicationFeedbackResponse['data'] ?? [])
            : [];

        foreach ($applicationFeedbackRows as $feedbackRow) {
            $applicationId = (string)($feedbackRow['application_id'] ?? '');
            if ($applicationId === '' || isset($feedbackTextByApplicationId[$applicationId])) {
                continue;
            }
            $feedbackTextByApplicationId[$applicationId] = trim((string)($feedbackRow['feedback_text'] ?? ''));
        }

        $settingRaw = isSuccessful($evaluationRecommendationSettingResponse)
            ? ($evaluationRecommendationSettingResponse['data'][0]['setting_value'] ?? null)
            : null;
        $settingPayload = is_array($settingRaw) && array_key_exists('value', $settingRaw)
            ? $settingRaw['value']
            : $settingRaw;

        $recommendationRows = is_array($settingPayload['recommendations'] ?? null)
            ? (array)$settingPayload['recommendations']
            : [];

        foreach ($recommendationRows as $recommendationRow) {
            $applicationId = trim((string)($recommendationRow['application_id'] ?? ''));
            if ($applicationId === '') {
                continue;
            }

            $evaluationRecommendationByApplicationId[$applicationId] = [
                'score' => (int)($recommendationRow['total_score'] ?? $recommendationRow['score'] ?? 0),
                'recommendation' => trim((string)($recommendationRow['recommendation'] ?? '')),
                'rule_result' => trim((string)($recommendationRow['rule_result'] ?? '')),
                'threshold' => (int)($recommendationRow['threshold'] ?? 75),
            ];
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
    return in_array($key, ['regular', 'coterminous'], true)
        ? 'Plantilla / Permanent'
        : 'Contractual';
};

$employmentTypeKey = static function (?string $classification): string {
    $key = strtolower(trim((string)$classification));
    return in_array($key, ['regular', 'coterminous'], true)
        ? 'permanent'
        : 'contractual';
};

$applicationStagePill = static function (string $status): array {
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

$normalizeEvaluationSetting = static function (mixed $rawValue, mixed $defaultValue): mixed {
    if (is_array($rawValue) && array_key_exists('value', $rawValue)) {
        return $rawValue['value'];
    }
    return $rawValue ?? $defaultValue;
};

$criteriaSettingsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('evaluation.rule_based.criteria')
    . '&limit=1',
    $headers
);

$criteriaRaw = isSuccessful($criteriaSettingsResponse)
    ? ($criteriaSettingsResponse['data'][0]['setting_value'] ?? null)
    : null;
$criteria = (array)$normalizeEvaluationSetting($criteriaRaw, []);

$requiredEligibility = strtolower(trim((string)($criteria['eligibility'] ?? 'career service sub professional')));
$requiredEducationYears = (float)($criteria['minimum_education_years'] ?? 2);
$requiredTrainingHours = (float)($criteria['minimum_training_hours'] ?? 4);
$requiredExperienceYears = (float)($criteria['minimum_experience_years'] ?? 1);
$threshold = (float)($criteria['threshold'] ?? 75);
$weights = is_array($criteria['weights'] ?? null) ? (array)$criteria['weights'] : [];
$eligibilityWeight = (float)($weights['eligibility'] ?? 25);
$educationWeight = (float)($weights['education'] ?? 25);
$trainingWeight = (float)($weights['training'] ?? 25);
$experienceWeight = (float)($weights['experience'] ?? 25);

$eligibilityConfigResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('recruitment.eligibility_requirements')
    . '&limit=1',
    $headers
);

$eligibilityRaw = isSuccessful($eligibilityConfigResponse)
    ? ($eligibilityConfigResponse['data'][0]['setting_value'] ?? null)
    : null;

$eligibilityValue = is_array($eligibilityRaw) && array_key_exists('value', $eligibilityRaw)
    ? $eligibilityRaw['value']
    : $eligibilityRaw;

if (is_array($eligibilityValue)) {
    $policyDefault = trim((string)($eligibilityValue['policy_default'] ?? ''));
    if ($policyDefault !== '') {
        $eligibilityConfig['policy_default'] = $policyDefault;
    } else {
        $eligibilityConfig['policy_default'] = $requiredEligibility;
    }

    $positionOverrides = is_array($eligibilityValue['position_overrides'] ?? null)
        ? (array)$eligibilityValue['position_overrides']
        : [];
    $normalizedOverrides = [];
    foreach ($positionOverrides as $positionKey => $positionEligibility) {
        $normalizedKey = strtolower(trim((string)$positionKey));
        $normalizedValue = trim((string)$positionEligibility);
        if ($normalizedKey === '' || $normalizedValue === '') {
            continue;
        }
        $normalizedOverrides[$normalizedKey] = $normalizedValue;
    }
    $eligibilityConfig['position_overrides'] = $normalizedOverrides;
} else {
    $eligibilityConfig['policy_default'] = $requiredEligibility;
}

$normalizeEligibilityOption = static function (string $value): string {
    $key = strtolower(trim($value));
    return match ($key) {
        'none', 'not_applicable', 'not applicable', 'n/a', 'na' => 'none',
        'csc', 'career service', 'career service sub professional' => 'csc',
        'prc' => 'prc',
        'csc_prc', 'csc,prc', 'csc, prc', 'csc/prc' => 'csc_prc',
        default => 'csc_prc',
    };
};

$eligibilityOptionToRequirement = static function (string $option): string {
    return match ($option) {
        'none' => 'none',
        'csc' => 'csc',
        'prc' => 'prc',
        default => 'csc, prc',
    };
};

$formatEligibilityRequirement = static function (string $value): string {
    $normalized = strtolower(trim($value));
    if ($normalized === '' || in_array($normalized, ['none', 'n/a', 'na', 'not applicable', 'not_applicable'], true)) {
        return 'None (Not Required)';
    }
    if ($normalized === 'csc') {
        return 'CSC';
    }
    if ($normalized === 'prc') {
        return 'PRC';
    }

    return 'CSC or PRC';
};

$positionCriteriaResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('recruitment.position_criteria')
    . '&limit=1',
    $headers
);

$positionCriteriaRaw = isSuccessful($positionCriteriaResponse)
    ? ($positionCriteriaResponse['data'][0]['setting_value'] ?? null)
    : null;

$positionCriteriaValue = is_array($positionCriteriaRaw) && array_key_exists('value', $positionCriteriaRaw)
    ? $positionCriteriaRaw['value']
    : $positionCriteriaRaw;

if (is_array($positionCriteriaValue)) {
    $rawOverrides = is_array($positionCriteriaValue['position_overrides'] ?? null)
        ? (array)$positionCriteriaValue['position_overrides']
        : [];

    $normalizedOverrides = [];
    foreach ($rawOverrides as $positionKey => $criteriaRow) {
        $normalizedPositionKey = strtolower(trim((string)$positionKey));
        if ($normalizedPositionKey === '' || !is_array($criteriaRow)) {
            continue;
        }

        $eligibilityOption = $normalizeEligibilityOption((string)($criteriaRow['eligibility'] ?? 'csc_prc'));
        $normalizedOverrides[$normalizedPositionKey] = [
            'eligibility' => $eligibilityOption,
            'minimum_education_years' => max(0, (float)($criteriaRow['minimum_education_years'] ?? $requiredEducationYears)),
            'minimum_training_hours' => max(0, (float)($criteriaRow['minimum_training_hours'] ?? $requiredTrainingHours)),
            'minimum_experience_years' => max(0, (float)($criteriaRow['minimum_experience_years'] ?? $requiredExperienceYears)),
        ];
    }

    $positionCriteriaConfig['position_overrides'] = $normalizedOverrides;
}

$resolvePostingCriteria = static function (string $positionId) use (
    $positionCriteriaConfig,
    $eligibilityConfig,
    $requiredEligibility,
    $requiredEducationYears,
    $requiredTrainingHours,
    $requiredExperienceYears,
    $normalizeEligibilityOption,
    $eligibilityOptionToRequirement
): array {
    $normalizedPositionId = strtolower(trim($positionId));

    $legacyEligibilityRequirement = trim((string)($eligibilityConfig['position_overrides'][$normalizedPositionId] ?? ''));
    $legacyPolicyDefault = trim((string)($eligibilityConfig['policy_default'] ?? $requiredEligibility));
    $legacyEffective = $legacyEligibilityRequirement !== '' ? $legacyEligibilityRequirement : $legacyPolicyDefault;

    $override = is_array($positionCriteriaConfig['position_overrides'][$normalizedPositionId] ?? null)
        ? (array)$positionCriteriaConfig['position_overrides'][$normalizedPositionId]
        : [];

    $eligibilityOption = isset($override['eligibility'])
        ? $normalizeEligibilityOption((string)$override['eligibility'])
        : $normalizeEligibilityOption($legacyEffective);

    return [
        'eligibility_scope' => isset($override['eligibility']) ? 'position' : ($legacyEligibilityRequirement !== '' ? 'position' : 'policy'),
        'eligibility_option' => $eligibilityOption,
        'eligibility_requirement' => $eligibilityOptionToRequirement($eligibilityOption),
        'minimum_education_years' => isset($override['minimum_education_years'])
            ? max(0, (float)$override['minimum_education_years'])
            : $requiredEducationYears,
        'minimum_training_hours' => isset($override['minimum_training_hours'])
            ? max(0, (float)$override['minimum_training_hours'])
            : $requiredTrainingHours,
        'minimum_experience_years' => isset($override['minimum_experience_years'])
            ? max(0, (float)$override['minimum_experience_years'])
            : $requiredExperienceYears,
    ];
};

$emailTemplatesResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('recruitment.email_templates')
    . '&limit=1',
    $headers
);

$emailTemplatesRaw = isSuccessful($emailTemplatesResponse)
    ? ($emailTemplatesResponse['data'][0]['setting_value'] ?? null)
    : null;

$emailTemplatesValue = is_array($emailTemplatesRaw) && array_key_exists('value', $emailTemplatesRaw)
    ? $emailTemplatesRaw['value']
    : $emailTemplatesRaw;

if (is_array($emailTemplatesValue)) {
    foreach (['submitted', 'passed', 'failed', 'next_stage'] as $templateKey) {
        $templateRow = is_array($emailTemplatesValue[$templateKey] ?? null)
            ? (array)$emailTemplatesValue[$templateKey]
            : [];
        $subject = trim((string)($templateRow['subject'] ?? ''));
        $body = trim((string)($templateRow['body'] ?? ''));
        if ($subject !== '') {
            $recruitmentEmailTemplates[$templateKey]['subject'] = $subject;
        }
        if ($body !== '') {
            $recruitmentEmailTemplates[$templateKey]['body'] = $body;
        }
    }
}

$matchEligibility = static function (string $required, string $actual): bool {
    $requiredRaw = strtolower(trim($required));
    $actualKey = strtolower(trim($actual));

    if ($requiredRaw === '' || in_array($requiredRaw, ['n/a', 'na', 'none', 'not applicable', 'not_applicable'], true)) {
        return true;
    }
    if ($actualKey === '' || in_array($actualKey, ['n/a', 'na', 'none'], true)) {
        return false;
    }

    $normalizedRequired = str_replace(['/', '|'], ',', $requiredRaw);
    $requiredTokens = preg_split('/\s*,\s*/', $normalizedRequired) ?: [];
    $requiredTokens = array_values(array_filter(array_map('trim', $requiredTokens), static fn(string $token): bool => $token !== ''));
    if (empty($requiredTokens)) {
        $requiredTokens = [$requiredRaw];
    }

    foreach ($requiredTokens as $token) {
        if ($token === '' || in_array($token, ['n/a', 'na', 'none', 'not applicable', 'not_applicable'], true)) {
            continue;
        }
        if ($actualKey === $token || str_contains($actualKey, $token) || str_contains($token, $actualKey)) {
            return true;
        }
    }

    return false;
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

$estimateSignalInputs = static function (array $application, array $documents, array $interviews): array {
    $applicationStatus = strtolower((string)($application['application_status'] ?? 'submitted'));
    $applicant = is_array($application['applicant'] ?? null) ? (array)$application['applicant'] : [];

    $documentTypes = [];
    foreach ($documents as $document) {
        $key = strtolower((string)($document['document_type'] ?? ''));
        if ($key !== '') {
            $documentTypes[$key] = true;
        }
    }

    $hasEligibilityDoc = isset($documentTypes['eligibility']) || isset($documentTypes['license']) || isset($documentTypes['id']) || isset($documentTypes['certificate']);
    $eligibility = $hasEligibilityDoc ? 'career service sub professional' : 'n/a';

    $educationYears = (isset($documentTypes['transcript']) || isset($documentTypes['pds'])) ? 2.0 : 0.0;
    $trainingHours = isset($documentTypes['certificate']) ? 4.0 : 0.0;
    if (trim((string)($applicant['portfolio_url'] ?? '')) !== '') {
        $trainingHours += 2.0;
    }

    $experienceYears = 0.0;
    if (trim((string)($applicant['resume_url'] ?? '')) !== '') {
        $experienceYears += 1.0;
    }
    if (in_array($applicationStatus, ['screening', 'shortlisted', 'interview', 'offer', 'hired'], true)) {
        $experienceYears += 0.5;
    }
    if (in_array($applicationStatus, ['offer', 'hired'], true)) {
        $experienceYears += 0.5;
    }
    foreach ($interviews as $interview) {
        $result = strtolower(trim((string)($interview['result'] ?? '')));
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

$evaluationScoreForApplication = static function (string $applicationId, array $application) use (
    $feedbackTextByApplicationId,
    $documentsByApplicationId,
    $interviewsByApplicationId,
    $extractStructuredInputs,
    $estimateSignalInputs,
    $matchEligibility,
    $threshold,
    $eligibilityWeight,
    $educationWeight,
    $trainingWeight,
    $experienceWeight
): array {
    $criteriaInput = is_array($application['criteria'] ?? null) ? (array)$application['criteria'] : [];
    $requiredEligibility = strtolower(trim((string)($criteriaInput['eligibility_requirement'] ?? 'none')));
    $requiredEducationYears = max(0, (float)($criteriaInput['minimum_education_years'] ?? 0));
    $requiredTrainingHours = max(0, (float)($criteriaInput['minimum_training_hours'] ?? 0));
    $requiredExperienceYears = max(0, (float)($criteriaInput['minimum_experience_years'] ?? 0));
    $eligibilityRequired = !in_array($requiredEligibility, ['none', 'n/a', 'na', 'not applicable', 'not_applicable', ''], true);

    $feedbackText = (string)($feedbackTextByApplicationId[$applicationId] ?? '');
    $structured = $extractStructuredInputs($feedbackText);
    $documents = (array)($documentsByApplicationId[$applicationId] ?? []);
    $interviews = (array)($interviewsByApplicationId[$applicationId] ?? []);
    $signals = $estimateSignalInputs($application, $documents, $interviews);

    $eligibilityInput = strtolower(trim((string)($structured['eligibility'] ?? $signals['eligibility'] ?? 'n/a')));
    $educationYears = (float)($structured['education_years'] ?? $signals['education_years'] ?? 0);
    $trainingHours = (float)($structured['training_hours'] ?? $signals['training_hours'] ?? 0);
    $experienceYears = (float)($structured['experience_years'] ?? $signals['experience_years'] ?? 0);

    $eligibilityMeets = $eligibilityRequired ? $matchEligibility($requiredEligibility, $eligibilityInput) : true;
    $educationMeets = $educationYears >= $requiredEducationYears;
    $trainingMeets = $trainingHours >= $requiredTrainingHours;
    $experienceMeets = $experienceYears >= $requiredExperienceYears;

    $earnedScore = 0.0;
    $maxScore = 0.0;

    if ($eligibilityRequired) {
        $maxScore += $eligibilityWeight;
        if ($eligibilityMeets) {
            $earnedScore += $eligibilityWeight;
        }
    }

    $maxScore += $educationWeight;
    if ($educationMeets) {
        $earnedScore += $educationWeight;
    }

    $maxScore += $trainingWeight;
    if ($trainingMeets) {
        $earnedScore += $trainingWeight;
    }

    $maxScore += $experienceWeight;
    if ($experienceMeets) {
        $earnedScore += $experienceWeight;
    }

    $score = $maxScore > 0 ? ($earnedScore / $maxScore) * 100 : 0;

    $totalScore = max(0, min(100, (int)round($score)));
    $isQualified = $totalScore >= $threshold;

    return [
        'score' => $totalScore,
        'is_qualified' => $isQualified,
    ];
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

$formatNumber = static function (float $value): string {
    if ((float)(int)$value === $value) {
        return (string)(int)$value;
    }

    return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
};

$documentStatusPill = static function (string $applicationStatus): array {
    $key = strtolower(trim($applicationStatus));
    if (in_array($key, ['shortlisted', 'offer', 'hired'], true)) {
        return ['Verified', 'bg-emerald-100 text-emerald-800'];
    }
    if (in_array($key, ['rejected', 'withdrawn'], true)) {
        return ['Rejected', 'bg-rose-100 text-rose-800'];
    }

    return ['Pending', 'bg-amber-100 text-amber-800'];
};

$normalizeRequiredDocumentLabels = static function ($rawValue): array {
    $defaultDocuments = ['PDS', 'WES', 'Eligibility (CSC/PRC)', 'Transcript of Records'];
    if (!is_array($rawValue) || empty($rawValue)) {
        return $defaultDocuments;
    }

    $documentMap = [
        'pds' => 'PDS',
        'wes' => 'WES',
        'eligibility_csc_prc' => 'Eligibility (CSC/PRC)',
        'transcript_of_records' => 'Transcript of Records',
        'transcript' => 'Transcript of Records',
        'certificate' => 'Eligibility (CSC/PRC)',
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

$activePostingPositionIds = [];
foreach ($postings as $posting) {
    $statusRaw = strtolower(trim((string)($posting['posting_status'] ?? 'draft')));
    if (!in_array($statusRaw, ['draft', 'published'], true)) {
        continue;
    }

    $positionId = (string)($posting['position_id'] ?? '');
    if ($positionId !== '') {
        $activePostingPositionIds[strtolower($positionId)] = true;
    }
}

$availablePositionOptions = [];
foreach ($positionOptions as $position) {
    $positionId = strtolower((string)($position['id'] ?? ''));
    if ($positionId === '' || isset($activePostingPositionIds[$positionId])) {
        continue;
    }
    $availablePositionOptions[] = $position;
}
if (empty($availablePositionOptions)) {
    $availablePositionOptions = $positionOptions;
}

$today = strtotime(gmdate('Y-m-d'));
foreach ($postings as $posting) {
    $postingId = (string)($posting['id'] ?? '');
    if ($postingId === '') {
        continue;
    }

    $title = (string)($posting['title'] ?? 'Untitled Posting');
    $positionTitle = (string)($posting['position']['position_title'] ?? $title);
    $officeName = (string)($posting['office']['office_name'] ?? 'Unassigned Office');
    $classification = (string)($posting['position']['employment_classification'] ?? '');
    $employmentType = $employmentTypeLabel($classification);
    $employmentTypeRaw = $employmentTypeKey($classification);
    $statusRaw = strtolower((string)($posting['posting_status'] ?? 'draft'));
    [$statusLabel, $statusClass] = $statusPill($statusRaw);
    $description = (string)($posting['description'] ?? '-');
    $qualifications = (string)($posting['qualifications'] ?? '-');
    $responsibilities = (string)($posting['responsibilities'] ?? '-');
    $openDate = (string)($posting['open_date'] ?? '');
    $closeDate = (string)($posting['close_date'] ?? '');
    $updatedAt = (string)($posting['updated_at'] ?? '');
    $plantillaItemNo = trim((string)($posting['plantilla_item_no'] ?? ''));
    $requirements = $normalizeRequiredDocumentLabels($posting['required_documents'] ?? []);
    $resolvedPostingCriteria = $resolvePostingCriteria((string)($posting['position_id'] ?? ''));
    $eligibilityScope = (string)$resolvedPostingCriteria['eligibility_scope'];
    $eligibilityOption = (string)$resolvedPostingCriteria['eligibility_option'];
    $effectiveEligibilityRequirement = (string)$resolvedPostingCriteria['eligibility_requirement'];
    $minimumEducationYears = (float)$resolvedPostingCriteria['minimum_education_years'];
    $minimumTrainingHours = (float)$resolvedPostingCriteria['minimum_training_hours'];
    $minimumExperienceYears = (float)$resolvedPostingCriteria['minimum_experience_years'];

    $postingApplicantRows = [];
    foreach ((array)($applicationsByPosting[$postingId] ?? []) as $application) {
        $applicationId = (string)($application['id'] ?? '');
        if ($applicationId === '') {
            continue;
        }

        $applicationStatusRaw = (string)($application['application_status'] ?? 'submitted');
        [$applicationStatusLabel, $applicationStatusClass] = $applicationStagePill($applicationStatusRaw);
        $submittedAt = (string)($application['submitted_at'] ?? '');
        $submittedLabel = $submittedAt !== '' ? date('M d, Y', strtotime($submittedAt)) : '-';
        $applicant = is_array($application['applicant'] ?? null) ? (array)$application['applicant'] : [];
        $applicantName = (string)($applicant['full_name'] ?? 'Applicant');
        $applicantEmail = (string)($applicant['email'] ?? '-');
        $applicantMobile = (string)($applicant['mobile_no'] ?? '-');
        $applicantAddress = (string)($applicant['current_address'] ?? '-');
        $resumeUrl = trim((string)($applicant['resume_url'] ?? ''));
        $portfolioUrl = trim((string)($applicant['portfolio_url'] ?? ''));
        $applicantUserId = strtolower(trim((string)($applicant['user_id'] ?? '')));
        $profilePhotoUrl = (string)($peopleByUserId[$applicantUserId]['profile_photo_url'] ?? '');

        $applicationWithCriteria = $application;
        $applicationWithCriteria['criteria'] = [
            'eligibility_requirement' => $effectiveEligibilityRequirement,
            'minimum_education_years' => $minimumEducationYears,
            'minimum_training_hours' => $minimumTrainingHours,
            'minimum_experience_years' => $minimumExperienceYears,
        ];

        $applicationEvaluation = $evaluationScoreForApplication($applicationId, $applicationWithCriteria);
        $score = (int)($applicationEvaluation['score'] ?? 0);
        [$scoreLabel, $scoreClass] = $recommendationLabel($score);
        [$documentStatusLabel, $documentStatusClass] = $documentStatusPill($applicationStatusRaw);

        $feedbackText = (string)($feedbackTextByApplicationId[$applicationId] ?? '');
        $structuredInputs = $extractStructuredInputs($feedbackText);
        $signalInputs = $estimateSignalInputs(
            $application,
            (array)($documentsByApplicationId[$applicationId] ?? []),
            (array)($interviewsByApplicationId[$applicationId] ?? [])
        );

        $eligibilityValue = trim((string)($structuredInputs['eligibility'] ?? $signalInputs['eligibility'] ?? 'n/a'));
        $educationYears = (float)($structuredInputs['education_years'] ?? $signalInputs['education_years'] ?? 0);
        $trainingHours = (float)($structuredInputs['training_hours'] ?? $signalInputs['training_hours'] ?? 0);
        $experienceYears = (float)($structuredInputs['experience_years'] ?? $signalInputs['experience_years'] ?? 0);

        $workExperience = [];
        if ($experienceYears > 0) {
            $workExperience[] = 'Relevant experience: ' . $formatNumber($experienceYears) . ' year(s)';
        }
        if ($resumeUrl !== '') {
            $workExperience[] = 'Resume/CV available for full work history review';
        }
        if (empty($workExperience)) {
            $workExperience[] = 'No detailed work experience entries encoded yet.';
        }

        $careerSummary = 'Application stage: ' . $applicationStatusLabel . '. Recommendation score: ' . $score . '%.';
        $basisText = trim((string)($basisByApplicationId[$applicationId] ?? ''));
        if ($basisText !== '' && $basisText !== '-') {
            $careerSummary .= ' Screening basis: ' . $basisText . '.';
        }

        $applicationDocuments = [];
        foreach ((array)($documentsByApplicationId[$applicationId] ?? []) as $document) {
            $uploadedAt = trim((string)($document['uploaded_at'] ?? ''));
            $applicationDocuments[] = [
                'document_type' => (string)($document['document_type'] ?? 'other'),
                'file_name' => (string)($document['file_name'] ?? 'Document'),
                'file_url' => (string)($document['file_url'] ?? ''),
                'uploaded_label' => $uploadedAt !== '' ? date('M d, Y', strtotime($uploadedAt)) : '-',
                'status_label' => $documentStatusLabel,
                'status_class' => $documentStatusClass,
            ];
        }

        $postingApplicantRows[] = [
            'application_id' => $applicationId,
            'applicant_name' => $applicantName,
            'applicant_email' => $applicantEmail,
            'applicant_mobile' => $applicantMobile !== '' ? $applicantMobile : '-',
            'applicant_address' => $applicantAddress !== '' ? $applicantAddress : '-',
            'profile_photo_url' => $profilePhotoUrl,
            'resume_url' => $resumeUrl,
            'portfolio_url' => $portfolioUrl,
            'applied_position' => $positionTitle,
            'submitted_label' => $submittedLabel,
            'initial_screening_label' => $applicationStatusLabel,
            'initial_screening_class' => $applicationStatusClass,
            'basis' => (string)($basisByApplicationId[$applicationId] ?? '-'),
            'career_summary' => $careerSummary,
            'eligibility' => $eligibilityValue !== '' ? $eligibilityValue : 'n/a',
            'education_years' => $formatNumber($educationYears),
            'training_hours' => $formatNumber($trainingHours),
            'experience_years' => $formatNumber($experienceYears),
            'work_experience' => $workExperience,
            'score' => $score,
            'score_label' => $scoreLabel,
            'score_class' => $scoreClass,
            'documents' => $applicationDocuments,
            'view_profile_url' => 'applicant-profile.php?application_id=' . rawurlencode($applicationId) . '&source=admin-recruitment',
        ];
    }

    usort($postingApplicantRows, static function (array $left, array $right): int {
        return ((int)($right['score'] ?? 0)) <=> ((int)($left['score'] ?? 0));
    });

    $row = [
        'id' => $postingId,
        'title' => $title,
        'position_title' => $positionTitle,
        'plantilla_item_no' => $plantillaItemNo !== '' ? $plantillaItemNo : '-',
        'plantilla_item_no_raw' => $plantillaItemNo,
        'office_id' => (string)($posting['office_id'] ?? ''),
        'position_id' => (string)($posting['position_id'] ?? ''),
        'office_name' => $officeName,
        'employment_type' => $employmentType,
        'employment_type_raw' => $employmentTypeRaw,
        'applications_total' => (int)($applicationCountsByPosting[$postingId] ?? 0),
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'description' => $description,
        'qualifications' => $qualifications,
        'responsibilities' => $responsibilities,
        'eligibility_scope' => $eligibilityScope,
        'eligibility_option' => $eligibilityOption,
        'eligibility_requirement' => $effectiveEligibilityRequirement,
        'minimum_education_years' => $minimumEducationYears,
        'minimum_training_hours' => $minimumTrainingHours,
        'minimum_experience_years' => $minimumExperienceYears,
        'requirements' => $requirements,
        'open_date' => $openDate,
        'open_date_label' => $openDate !== '' ? date('M d, Y', strtotime($openDate)) : '-',
        'close_date' => $closeDate,
        'close_date_label' => $closeDate !== '' ? date('M d, Y', strtotime($closeDate)) : '-',
        'updated_label' => $updatedAt !== '' ? date('M d, Y', strtotime($updatedAt)) : '-',
        'remarks' => $closeDate !== '' ? 'Application ends ' . date('M d, Y', strtotime($closeDate)) : 'No closing date set.',
        'search_text' => strtolower(trim($title . ' ' . $positionTitle . ' ' . $plantillaItemNo . ' ' . $officeName . ' ' . $employmentType . ' ' . $statusLabel)),
    ];

    $postingViewById[$postingId] = [
        'id' => $postingId,
        'posting_title' => $title,
        'position_title' => $positionTitle,
        'plantilla_item_no' => $plantillaItemNo !== '' ? $plantillaItemNo : '-',
        'office_name' => $officeName,
        'employment_type' => $employmentType,
        'status_label' => $statusLabel,
        'open_date_label' => $row['open_date_label'],
        'close_date_label' => $row['close_date_label'],
        'description' => $description,
        'qualifications' => $qualifications,
        'responsibilities' => $responsibilities,
        'requirements' => $requirements,
        'criteria' => [
            'eligibility' => $formatEligibilityRequirement($effectiveEligibilityRequirement),
            'education' => 'Minimum ' . $formatNumber($minimumEducationYears) . ' year(s)',
            'training' => 'Minimum ' . $formatNumber($minimumTrainingHours) . ' hour(s)',
            'experience' => 'Minimum ' . $formatNumber($minimumExperienceYears) . ' year(s)',
        ],
        'applicants' => $postingApplicantRows,
    ];

    if ($statusRaw === 'archived') {
        $archivedRecruitmentRows[] = $row;
    } else {
        $activeRecruitmentRows[] = $row;
    }

    $closeDateTimestamp = $closeDate !== '' ? strtotime($closeDate) : false;
    if ($statusRaw === 'published' && $closeDateTimestamp !== false && $closeDateTimestamp >= $today) {
        $daysRemaining = (int)floor(($closeDateTimestamp - $today) / 86400);
        $applicationDeadlineRows[] = [
            'title' => $title,
            'position_title' => $positionTitle,
            'office_name' => $officeName,
            'close_date_label' => $closeDate !== '' ? date('M d, Y', strtotime($closeDate)) : '-',
            'days_remaining' => $daysRemaining,
            'priority_label' => $daysRemaining <= 3 ? 'Urgent' : ($daysRemaining <= 7 ? 'Upcoming' : 'Scheduled'),
            'priority_class' => $daysRemaining <= 3
                ? 'bg-rose-100 text-rose-800'
                : ($daysRemaining <= 7 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'),
        ];
    }
}

usort($applicationDeadlineRows, static function (array $left, array $right): int {
    return ((int)($left['days_remaining'] ?? 0)) <=> ((int)($right['days_remaining'] ?? 0));
});
