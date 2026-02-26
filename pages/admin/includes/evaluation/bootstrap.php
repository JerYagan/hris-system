<?php

require_once __DIR__ . '/../lib/admin-backend.php';

$backend = adminBackendContext();
$supabaseUrl = (string)($backend['supabase_url'] ?? '');
$serviceRoleKey = (string)($backend['service_role_key'] ?? '');
$headers = (array)($backend['headers'] ?? []);
$adminUserId = (string)($backend['admin_user_id'] ?? '');

if ($supabaseUrl === '' || $serviceRoleKey === '') {
    redirectWithState('error', 'Supabase credentials are missing. Check your .env file.');
}

if (!function_exists('evaluationDefaultCriteria')) {
    function evaluationDefaultCriteria(): array
    {
        return [
            'eligibility' => 'career service sub professional',
            'minimum_education_years' => 2,
            'minimum_training_hours' => 4,
            'minimum_experience_years' => 1,
            'threshold' => 75,
            'weights' => [
                'eligibility' => 25,
                'education' => 25,
                'training' => 25,
                'experience' => 25,
            ],
            'rule_notes' => '',
        ];
    }
}

if (!function_exists('evaluationNumeric')) {
    function evaluationNumeric(mixed $value, float $default = 0.0, float $min = 0.0, float $max = 1000.0): float
    {
        if (!is_numeric($value)) {
            return max($min, min($max, $default));
        }

        $number = (float)$value;
        return max($min, min($max, $number));
    }
}

if (!function_exists('evaluationParseStoredValue')) {
    function evaluationParseStoredValue(mixed $stored): mixed
    {
        if (is_array($stored) && array_key_exists('value', $stored)) {
            return $stored['value'];
        }

        return $stored;
    }
}

if (!function_exists('evaluationReadSetting')) {
    function evaluationReadSetting(string $supabaseUrl, array $headers, string $key): mixed
    {
        $response = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode($key) . '&limit=1',
            $headers
        );

        if (!isSuccessful($response)) {
            return null;
        }

        $raw = $response['data'][0]['setting_value'] ?? null;
        return evaluationParseStoredValue($raw);
    }
}

if (!function_exists('evaluationLoadCriteria')) {
    function evaluationLoadCriteria(string $supabaseUrl, array $headers): array
    {
        $defaults = evaluationDefaultCriteria();
        $stored = evaluationReadSetting($supabaseUrl, $headers, 'evaluation.rule_based.criteria');

        if (!is_array($stored)) {
            return $defaults;
        }

        $weights = is_array($stored['weights'] ?? null) ? (array)$stored['weights'] : [];

        return [
            'eligibility' => strtolower(trim((string)($stored['eligibility'] ?? $defaults['eligibility']))),
            'minimum_education_years' => evaluationNumeric($stored['minimum_education_years'] ?? null, (float)$defaults['minimum_education_years'], 0, 20),
            'minimum_training_hours' => evaluationNumeric($stored['minimum_training_hours'] ?? null, (float)$defaults['minimum_training_hours'], 0, 1000),
            'minimum_experience_years' => evaluationNumeric($stored['minimum_experience_years'] ?? null, (float)$defaults['minimum_experience_years'], 0, 60),
            'threshold' => evaluationNumeric($stored['threshold'] ?? null, (float)$defaults['threshold'], 0, 100),
            'weights' => [
                'eligibility' => evaluationNumeric($weights['eligibility'] ?? null, (float)$defaults['weights']['eligibility'], 0, 100),
                'education' => evaluationNumeric($weights['education'] ?? null, (float)$defaults['weights']['education'], 0, 100),
                'training' => evaluationNumeric($weights['training'] ?? null, (float)$defaults['weights']['training'], 0, 100),
                'experience' => evaluationNumeric($weights['experience'] ?? null, (float)$defaults['weights']['experience'], 0, 100),
            ],
            'rule_notes' => trim((string)($stored['rule_notes'] ?? $defaults['rule_notes'])),
        ];
    }
}

if (!function_exists('evaluationNormalizeEligibilityOption')) {
    function evaluationNormalizeEligibilityOption(string $value): string
    {
        $key = strtolower(trim($value));
        return match ($key) {
            'none', 'not_applicable', 'not applicable', 'n/a', 'na' => 'none',
            'csc', 'career service', 'career service sub professional' => 'csc',
            'prc' => 'prc',
            'csc_prc', 'csc,prc', 'csc, prc', 'csc/prc' => 'csc_prc',
            default => 'csc_prc',
        };
    }
}

if (!function_exists('evaluationEligibilityOptionToRequirement')) {
    function evaluationEligibilityOptionToRequirement(string $option): string
    {
        return match (evaluationNormalizeEligibilityOption($option)) {
            'none' => 'none',
            'csc' => 'csc',
            'prc' => 'prc',
            default => 'csc, prc',
        };
    }
}

if (!function_exists('evaluationRequirementToEligibilityOption')) {
    function evaluationRequirementToEligibilityOption(string $requirement): string
    {
        return evaluationNormalizeEligibilityOption($requirement);
    }
}

if (!function_exists('evaluationLoadPositionCriteriaConfig')) {
    function evaluationLoadPositionCriteriaConfig(string $supabaseUrl, array $headers, array $globalCriteria): array
    {
        $stored = evaluationReadSetting($supabaseUrl, $headers, 'recruitment.position_criteria');
        if (!is_array($stored)) {
            return ['position_overrides' => []];
        }

        $rawOverrides = is_array($stored['position_overrides'] ?? null)
            ? (array)$stored['position_overrides']
            : [];

        $normalizedOverrides = [];
        foreach ($rawOverrides as $positionKey => $criteriaRow) {
            $normalizedPositionId = strtolower(trim((string)$positionKey));
            if ($normalizedPositionId === '' || !is_array($criteriaRow)) {
                continue;
            }

            $normalizedOverrides[$normalizedPositionId] = [
                'eligibility' => evaluationNormalizeEligibilityOption((string)($criteriaRow['eligibility'] ?? 'csc_prc')),
                'minimum_education_years' => evaluationNumeric(
                    $criteriaRow['minimum_education_years'] ?? null,
                    (float)($globalCriteria['minimum_education_years'] ?? 2),
                    0,
                    20
                ),
                'minimum_training_hours' => evaluationNumeric(
                    $criteriaRow['minimum_training_hours'] ?? null,
                    (float)($globalCriteria['minimum_training_hours'] ?? 4),
                    0,
                    1000
                ),
                'minimum_experience_years' => evaluationNumeric(
                    $criteriaRow['minimum_experience_years'] ?? null,
                    (float)($globalCriteria['minimum_experience_years'] ?? 1),
                    0,
                    60
                ),
            ];
        }

        return ['position_overrides' => $normalizedOverrides];
    }
}

if (!function_exists('evaluationResolvePostingCriteria')) {
    function evaluationResolvePostingCriteria(string $positionId, array $globalCriteria, array $positionCriteriaConfig): array
    {
        $normalizedPositionId = strtolower(trim($positionId));
        $override = is_array($positionCriteriaConfig['position_overrides'][$normalizedPositionId] ?? null)
            ? (array)$positionCriteriaConfig['position_overrides'][$normalizedPositionId]
            : [];

        $globalEligibilityRequirement = strtolower(trim((string)($globalCriteria['eligibility'] ?? 'career service sub professional')));
        $eligibilityOption = isset($override['eligibility'])
            ? evaluationNormalizeEligibilityOption((string)$override['eligibility'])
            : evaluationRequirementToEligibilityOption($globalEligibilityRequirement);

        return [
            'eligibility_option' => $eligibilityOption,
            'eligibility_requirement' => evaluationEligibilityOptionToRequirement($eligibilityOption),
            'minimum_education_years' => isset($override['minimum_education_years'])
                ? evaluationNumeric($override['minimum_education_years'], (float)($globalCriteria['minimum_education_years'] ?? 2), 0, 20)
                : (float)($globalCriteria['minimum_education_years'] ?? 2),
            'minimum_training_hours' => isset($override['minimum_training_hours'])
                ? evaluationNumeric($override['minimum_training_hours'], (float)($globalCriteria['minimum_training_hours'] ?? 4), 0, 1000)
                : (float)($globalCriteria['minimum_training_hours'] ?? 4),
            'minimum_experience_years' => isset($override['minimum_experience_years'])
                ? evaluationNumeric($override['minimum_experience_years'], (float)($globalCriteria['minimum_experience_years'] ?? 1), 0, 60)
                : (float)($globalCriteria['minimum_experience_years'] ?? 1),
        ];
    }
}

if (!function_exists('evaluationLoadPositionOptions')) {
    function evaluationLoadPositionOptions(string $supabaseUrl, array $headers): array
    {
        $response = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/job_positions?select=id,position_title&is_active=eq.true&order=position_title.asc&limit=1000',
            $headers
        );

        return isSuccessful($response) ? (array)($response['data'] ?? []) : [];
    }
}

if (!function_exists('evaluationUpsertSetting')) {
    function evaluationUpsertSetting(string $supabaseUrl, array $headers, string $key, mixed $value, string $adminUserId = ''): bool
    {
        $payload = [[
            'setting_key' => $key,
            'setting_value' => ['value' => $value],
            'updated_by' => $adminUserId !== '' ? $adminUserId : null,
            'updated_at' => gmdate('c'),
        ]];

        $response = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/system_settings?on_conflict=setting_key',
            array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
            $payload
        );

        if (isSuccessful($response)) {
            return true;
        }

        $patchResponse = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/system_settings?setting_key=eq.' . rawurlencode($key),
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'setting_value' => ['value' => $value],
                'updated_by' => $adminUserId !== '' ? $adminUserId : null,
                'updated_at' => gmdate('c'),
            ]
        );

        if (isSuccessful($patchResponse)) {
            return true;
        }

        $insertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/system_settings',
            array_merge($headers, ['Prefer: return=minimal']),
            $payload
        );

        return isSuccessful($insertResponse);
    }
}

if (!function_exists('evaluationMatchEligibility')) {
    function evaluationMatchEligibility(string $required, string $actual): bool
    {
        $requiredKey = strtolower(trim($required));
        $actualKey = strtolower(trim($actual));

        if ($requiredKey === '' || in_array($requiredKey, ['n/a', 'na', 'none', 'not applicable', 'not_applicable'], true)) {
            return true;
        }

        if ($actualKey === '' || in_array($actualKey, ['n/a', 'na', 'none'], true)) {
            return false;
        }

        $requiredNormalized = str_replace(['/', '|'], ',', $requiredKey);
        $tokens = preg_split('/\s*,\s*/', $requiredNormalized) ?: [];
        $tokens = array_values(array_filter(array_map('trim', $tokens), static fn(string $token): bool => $token !== ''));
        if (empty($tokens)) {
            $tokens = [$requiredKey];
        }

        foreach ($tokens as $token) {
            if ($token === '' || in_array($token, ['n/a', 'na', 'none', 'not applicable', 'not_applicable'], true)) {
                continue;
            }

            if ($actualKey === $token || str_contains($actualKey, $token) || str_contains($token, $actualKey)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('evaluationExtractStructuredInputs')) {
    function evaluationExtractStructuredInputs(string $feedbackText): array
    {
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
    }
}

if (!function_exists('evaluationEstimateProfileFromSignals')) {
    function evaluationEstimateProfileFromSignals(array $application, array $documentTypes, array $interviews): array
    {
        $applicationStatus = strtolower((string)(cleanText($application['application_status'] ?? null) ?? 'submitted'));
        $applicant = is_array($application['applicant'] ?? null) ? (array)$application['applicant'] : [];

        $hasEligibilityDoc = isset($documentTypes['eligibility']) || isset($documentTypes['license']) || isset($documentTypes['id']) || isset($documentTypes['certificate']);
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
    }
}

if (!function_exists('evaluationBuildDataset')) {
    function evaluationBuildDataset(string $supabaseUrl, array $headers): array
    {
        $applicationsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/applications?select=id,application_ref_no,application_status,submitted_at,applicant_profile_id,job_posting_id,applicant:applicant_profiles(full_name,email,resume_url,portfolio_url),job:job_postings(title,position_id)&order=submitted_at.desc&limit=500',
            $headers
        );

        $interviewsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/application_interviews?select=id,application_id,interview_stage,score,result,scheduled_at&order=scheduled_at.desc&limit=2000',
            $headers
        );

        $documentsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/application_documents?select=id,application_id,document_type,file_name,uploaded_at&order=uploaded_at.desc&limit=4000',
            $headers
        );

        $feedbackResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/application_feedback?select=application_id,feedback_text,decision,provided_at&order=provided_at.desc&limit=2000',
            $headers
        );

        $errors = [];
        if (!isSuccessful($applicationsResponse)) {
            $errors[] = 'Failed to load applications dataset.';
        }
        if (!isSuccessful($interviewsResponse)) {
            $errors[] = 'Failed to load interview scores dataset.';
        }
        if (!isSuccessful($documentsResponse)) {
            $errors[] = 'Failed to load application documents dataset.';
        }
        if (!isSuccessful($feedbackResponse)) {
            $errors[] = 'Failed to load evaluation feedback dataset.';
        }

        return [
            'applications' => isSuccessful($applicationsResponse) ? (array)($applicationsResponse['data'] ?? []) : [],
            'interviews' => isSuccessful($interviewsResponse) ? (array)($interviewsResponse['data'] ?? []) : [],
            'documents' => isSuccessful($documentsResponse) ? (array)($documentsResponse['data'] ?? []) : [],
            'feedback' => isSuccessful($feedbackResponse) ? (array)($feedbackResponse['data'] ?? []) : [],
            'errors' => $errors,
        ];
    }
}

if (!function_exists('evaluationRunRuleEngine')) {
    function evaluationRunRuleEngine(array $dataset, array $criteria, array $positionCriteriaConfig = []): array
    {
        $applications = (array)($dataset['applications'] ?? []);
        $interviews = (array)($dataset['interviews'] ?? []);
        $documents = (array)($dataset['documents'] ?? []);
        $feedbackRows = (array)($dataset['feedback'] ?? []);

        $interviewsByApplication = [];
        foreach ($interviews as $interview) {
            $applicationId = (string)($interview['application_id'] ?? '');
            if ($applicationId === '') {
                continue;
            }
            if (!isset($interviewsByApplication[$applicationId])) {
                $interviewsByApplication[$applicationId] = [];
            }
            $interviewsByApplication[$applicationId][] = $interview;
        }

        $documentsByApplication = [];
        foreach ($documents as $document) {
            $applicationId = (string)($document['application_id'] ?? '');
            $documentType = strtolower((string)($document['document_type'] ?? ''));
            if ($applicationId === '' || $documentType === '') {
                continue;
            }
            if (!isset($documentsByApplication[$applicationId])) {
                $documentsByApplication[$applicationId] = [];
            }
            $documentsByApplication[$applicationId][$documentType] = true;
        }

        $feedbackByApplication = [];
        foreach ($feedbackRows as $feedback) {
            $applicationId = (string)($feedback['application_id'] ?? '');
            if ($applicationId === '' || isset($feedbackByApplication[$applicationId])) {
                continue;
            }

            $feedbackByApplication[$applicationId] = trim((string)($feedback['feedback_text'] ?? ''));
        }

        $rows = [];
        $summary = [
            'qualified' => 0,
            'not_qualified' => 0,
            'total' => 0,
        ];

        $threshold = (float)($criteria['threshold'] ?? 75);

        $weights = is_array($criteria['weights'] ?? null) ? (array)$criteria['weights'] : [];
        $eligibilityWeight = (float)($weights['eligibility'] ?? 25);
        $educationWeight = (float)($weights['education'] ?? 25);
        $trainingWeight = (float)($weights['training'] ?? 25);
        $experienceWeight = (float)($weights['experience'] ?? 25);

        foreach ($applications as $application) {
            $applicationId = (string)($application['id'] ?? '');
            if ($applicationId === '') {
                continue;
            }

            $applicant = (array)($application['applicant'] ?? []);
            $job = (array)($application['job'] ?? []);
            $positionId = (string)($job['position_id'] ?? '');
            $applicationDocs = $documentsByApplication[$applicationId] ?? [];
            $applicationInterviews = $interviewsByApplication[$applicationId] ?? [];
            $feedbackText = (string)($feedbackByApplication[$applicationId] ?? '');

            $postingCriteria = evaluationResolvePostingCriteria($positionId, $criteria, $positionCriteriaConfig);
            $requiredEligibility = strtolower(trim((string)($postingCriteria['eligibility_requirement'] ?? 'none')));
            $requiredEducationYears = (float)($postingCriteria['minimum_education_years'] ?? ($criteria['minimum_education_years'] ?? 2));
            $requiredTrainingHours = (float)($postingCriteria['minimum_training_hours'] ?? ($criteria['minimum_training_hours'] ?? 4));
            $requiredExperienceYears = (float)($postingCriteria['minimum_experience_years'] ?? ($criteria['minimum_experience_years'] ?? 1));
            $eligibilityRequired = !in_array($requiredEligibility, ['none', 'n/a', 'na', 'not applicable', 'not_applicable', ''], true);

            $structuredInputs = evaluationExtractStructuredInputs($feedbackText);
            $signalInputs = evaluationEstimateProfileFromSignals($application, $applicationDocs, $applicationInterviews);

            $eligibilityInput = strtolower(trim((string)($structuredInputs['eligibility'] ?? $signalInputs['eligibility'] ?? 'n/a')));
            $educationYears = evaluationNumeric($structuredInputs['education_years'] ?? null, (float)($signalInputs['education_years'] ?? 0), 0, 20);
            $trainingHours = evaluationNumeric($structuredInputs['training_hours'] ?? null, (float)($signalInputs['training_hours'] ?? 0), 0, 1000);
            $experienceYears = evaluationNumeric($structuredInputs['experience_years'] ?? null, (float)($signalInputs['experience_years'] ?? 0), 0, 60);

            $eligibilityMeets = $eligibilityRequired ? evaluationMatchEligibility($requiredEligibility, $eligibilityInput) : true;
            $educationMeets = $educationYears >= $requiredEducationYears;
            $trainingMeets = $trainingHours >= $requiredTrainingHours;
            $experienceMeets = $experienceYears >= $requiredExperienceYears;

            $eligibilityScore = $eligibilityRequired && $eligibilityMeets ? $eligibilityWeight : 0;
            $educationScore = $educationMeets ? $educationWeight : 0;
            $trainingScore = $trainingMeets ? $trainingWeight : 0;
            $experienceScore = $experienceMeets ? $experienceWeight : 0;

            $maxScore = (float)($educationWeight + $trainingWeight + $experienceWeight + ($eligibilityRequired ? $eligibilityWeight : 0));
            $earnedScore = (float)($eligibilityScore + $educationScore + $trainingScore + $experienceScore);
            $totalScore = $maxScore > 0 ? ($earnedScore / $maxScore) * 100 : 0;
            $allCriteriaMet = $eligibilityMeets && $educationMeets && $trainingMeets && $experienceMeets;

            $isQualified = $totalScore >= $threshold;
            $ruleResult = $isQualified ? 'Qualified for Evaluation' : 'Not Qualified';
            $recommendation = $isQualified ? 'Recommend Proceed' : 'Not Recommended';

            if ($isQualified) {
                $summary['qualified']++;
            } else {
                $summary['not_qualified']++;
            }

            $summary['total']++;

            $rows[] = [
                'application_id' => $applicationId,
                'application_ref_no' => (string)($application['application_ref_no'] ?? '-'),
                'application_status' => strtolower((string)($application['application_status'] ?? 'submitted')),
                'applicant_name' => (string)($applicant['full_name'] ?? 'Unknown Applicant'),
                'applicant_email' => (string)($applicant['email'] ?? '-'),
                'job_title' => (string)($job['title'] ?? 'Unassigned Position'),
                'position_id' => $positionId,
                'eligibility_input' => $eligibilityInput,
                'eligibility_required' => $eligibilityRequired,
                'eligibility_option' => (string)($postingCriteria['eligibility_option'] ?? 'none'),
                'required_eligibility' => $requiredEligibility,
                'eligibility_meets' => $eligibilityMeets,
                'education_years' => $educationYears,
                'required_education_years' => $requiredEducationYears,
                'training_hours' => $trainingHours,
                'required_training_hours' => $requiredTrainingHours,
                'experience_years' => $experienceYears,
                'required_experience_years' => $requiredExperienceYears,
                'eligibility_score' => (int)round($eligibilityScore),
                'education_score' => (int)round($educationScore),
                'training_score' => (int)round($trainingScore),
                'experience_score' => (int)round($experienceScore),
                'total_score' => (int)round($totalScore),
                'threshold' => (int)round($threshold),
                'education_meets' => $educationMeets,
                'training_meets' => $trainingMeets,
                'experience_meets' => $experienceMeets,
                'rule_result' => $ruleResult,
                'recommendation' => $recommendation,
                'submitted_at' => (string)($application['submitted_at'] ?? ''),
            ];
        }

        usort($rows, static function (array $left, array $right): int {
            $priority = ['qualified for evaluation' => 1, 'not qualified' => 2];
            $leftPriority = $priority[strtolower((string)($left['rule_result'] ?? 'not qualified'))] ?? 3;
            $rightPriority = $priority[strtolower((string)($right['rule_result'] ?? 'not qualified'))] ?? 3;
            if ($leftPriority !== $rightPriority) {
                return $leftPriority <=> $rightPriority;
            }

            $leftScore = (int)($left['total_score'] ?? 0);
            $rightScore = (int)($right['total_score'] ?? 0);
            if ($leftScore !== $rightScore) {
                return $rightScore <=> $leftScore;
            }

            return strcmp((string)($left['applicant_name'] ?? ''), (string)($right['applicant_name'] ?? ''));
        });

        return [
            'rows' => $rows,
            'summary' => $summary,
        ];
    }
}
