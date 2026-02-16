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
            'education_requirement' => 'related_bachelor',
            'minimum_experience_years' => 2,
            'minimum_exam_score' => 75,
            'minimum_interview_rating' => 3.5,
            'rule_notes' => '',
        ];
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

        $educationRequirement = strtolower(trim((string)($stored['education_requirement'] ?? $defaults['education_requirement'])));
        if (!in_array($educationRequirement, ['related_bachelor', 'any_bachelor', 'masters_preferred'], true)) {
            $educationRequirement = $defaults['education_requirement'];
        }

        $minimumExperienceYears = (int)($stored['minimum_experience_years'] ?? $defaults['minimum_experience_years']);
        $minimumExperienceYears = max(0, min(30, $minimumExperienceYears));

        $minimumExamScore = (float)($stored['minimum_exam_score'] ?? $defaults['minimum_exam_score']);
        $minimumExamScore = max(0, min(100, $minimumExamScore));

        $minimumInterviewRating = (float)($stored['minimum_interview_rating'] ?? $defaults['minimum_interview_rating']);
        $minimumInterviewRating = max(1, min(5, $minimumInterviewRating));

        return [
            'education_requirement' => $educationRequirement,
            'minimum_experience_years' => $minimumExperienceYears,
            'minimum_exam_score' => $minimumExamScore,
            'minimum_interview_rating' => $minimumInterviewRating,
            'rule_notes' => trim((string)($stored['rule_notes'] ?? $defaults['rule_notes'])),
        ];
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

if (!function_exists('evaluationNormalizeScore')) {
    function evaluationNormalizeScore(mixed $score): array
    {
        if ($score === null || $score === '') {
            return ['percent' => null, 'rating' => null];
        }

        if (!is_numeric($score)) {
            return ['percent' => null, 'rating' => null];
        }

        $value = (float)$score;
        if ($value <= 0) {
            return ['percent' => null, 'rating' => null];
        }

        if ($value <= 5) {
            $rating = max(1, min(5, $value));
            return [
                'percent' => max(0, min(100, $rating * 20)),
                'rating' => $rating,
            ];
        }

        $percent = max(0, min(100, $value));
        return [
            'percent' => $percent,
            'rating' => max(1, min(5, $percent / 20)),
        ];
    }
}

if (!function_exists('evaluationAverage')) {
    function evaluationAverage(array $values): ?float
    {
        if (empty($values)) {
            return null;
        }

        $sum = 0.0;
        $count = 0;
        foreach ($values as $value) {
            if (!is_numeric($value)) {
                continue;
            }
            $sum += (float)$value;
            $count++;
        }

        if ($count === 0) {
            return null;
        }

        return $sum / $count;
    }
}

if (!function_exists('evaluationBuildDataset')) {
    function evaluationBuildDataset(string $supabaseUrl, array $headers): array
    {
        $applicationsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/applications?select=id,application_ref_no,application_status,submitted_at,applicant_profile_id,job_posting_id,applicant:applicant_profiles(full_name,email,resume_url,portfolio_url),job:job_postings(title)&order=submitted_at.desc&limit=500',
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

        return [
            'applications' => isSuccessful($applicationsResponse) ? (array)($applicationsResponse['data'] ?? []) : [],
            'interviews' => isSuccessful($interviewsResponse) ? (array)($interviewsResponse['data'] ?? []) : [],
            'documents' => isSuccessful($documentsResponse) ? (array)($documentsResponse['data'] ?? []) : [],
            'errors' => $errors,
        ];
    }
}

if (!function_exists('evaluationRunRuleEngine')) {
    function evaluationRunRuleEngine(array $dataset, array $criteria): array
    {
        $applications = (array)($dataset['applications'] ?? []);
        $interviews = (array)($dataset['interviews'] ?? []);
        $documents = (array)($dataset['documents'] ?? []);

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

        $rows = [];
        $summary = [
            'shortlist' => 0,
            'manual_review' => 0,
            'not_recommended' => 0,
            'total' => 0,
        ];

        $educationRequirement = strtolower((string)($criteria['education_requirement'] ?? 'related_bachelor'));
        $minimumExperienceYears = (int)($criteria['minimum_experience_years'] ?? 2);
        $minimumExamScore = (float)($criteria['minimum_exam_score'] ?? 75);
        $minimumInterviewRating = (float)($criteria['minimum_interview_rating'] ?? 3.5);

        foreach ($applications as $application) {
            $applicationId = (string)($application['id'] ?? '');
            if ($applicationId === '') {
                continue;
            }

            $applicant = (array)($application['applicant'] ?? []);
            $job = (array)($application['job'] ?? []);
            $applicationDocs = $documentsByApplication[$applicationId] ?? [];
            $applicationInterviews = $interviewsByApplication[$applicationId] ?? [];

            $hasResume = !empty($applicant['resume_url']) || isset($applicationDocs['resume']);
            $hasPortfolio = !empty($applicant['portfolio_url']);
            $hasTranscript = isset($applicationDocs['transcript']);
            $hasCertificate = isset($applicationDocs['certificate']);
            $hasPds = isset($applicationDocs['pds']);

            $hasEducationEvidence = $hasTranscript || $hasCertificate || $hasPds;
            if ($educationRequirement === 'related_bachelor') {
                $educationMeets = $hasEducationEvidence;
            } elseif ($educationRequirement === 'any_bachelor') {
                $educationMeets = $hasEducationEvidence || $hasResume;
            } else {
                $educationMeets = $hasEducationEvidence || $hasResume;
            }

            $estimatedExperienceYears = 0;
            if ($hasResume) {
                $estimatedExperienceYears += 1;
            }
            if ($hasPortfolio) {
                $estimatedExperienceYears += 1;
            }
            if ($hasCertificate || $hasTranscript) {
                $estimatedExperienceYears += 1;
            }

            $experienceMeets = $estimatedExperienceYears >= $minimumExperienceYears;

            $technicalPercentScores = [];
            $technicalRatingScores = [];
            $interviewPercentScores = [];
            $interviewRatingScores = [];

            foreach ($applicationInterviews as $interview) {
                $stage = strtolower((string)($interview['interview_stage'] ?? ''));
                $normalized = evaluationNormalizeScore($interview['score'] ?? null);
                $percent = $normalized['percent'];
                $rating = $normalized['rating'];
                if ($percent === null || $rating === null) {
                    continue;
                }

                if ($stage === 'technical') {
                    $technicalPercentScores[] = $percent;
                    $technicalRatingScores[] = $rating;
                }

                $interviewPercentScores[] = $percent;
                $interviewRatingScores[] = $rating;
            }

            $examScore = evaluationAverage($technicalPercentScores);
            if ($examScore === null) {
                $examScore = evaluationAverage($interviewPercentScores);
            }

            $interviewRating = evaluationAverage($interviewRatingScores);
            if (!empty($technicalRatingScores) && $interviewRating === null) {
                $interviewRating = evaluationAverage($technicalRatingScores);
            }

            $examMeets = $examScore !== null && $examScore >= $minimumExamScore;
            $interviewMeets = $interviewRating !== null && $interviewRating >= $minimumInterviewRating;

            $criteriaFlags = [$educationMeets, $experienceMeets, $examMeets, $interviewMeets];
            $failedCriteria = 0;
            foreach ($criteriaFlags as $flag) {
                if (!$flag) {
                    $failedCriteria++;
                }
            }

            $ruleResult = 'Fail';
            $recommendation = 'Not Recommended';
            if ($failedCriteria === 0) {
                $ruleResult = 'Pass';
                $recommendation = 'Shortlist';
                $summary['shortlist']++;
            } elseif ($failedCriteria === 1) {
                $ruleResult = 'Conditional';
                $recommendation = 'Manual Review';
                $summary['manual_review']++;
            } else {
                $summary['not_recommended']++;
            }

            $summary['total']++;

            $rows[] = [
                'application_id' => $applicationId,
                'application_ref_no' => (string)($application['application_ref_no'] ?? '-'),
                'application_status' => strtolower((string)($application['application_status'] ?? 'submitted')),
                'applicant_name' => (string)($applicant['full_name'] ?? 'Unknown Applicant'),
                'applicant_email' => (string)($applicant['email'] ?? '-'),
                'job_title' => (string)($job['title'] ?? 'Unassigned Position'),
                'education_text' => $educationMeets ? 'Meets' : 'Missing evidence',
                'education_meets' => $educationMeets,
                'experience_years' => $estimatedExperienceYears,
                'experience_meets' => $experienceMeets,
                'exam_score' => $examScore,
                'exam_meets' => $examMeets,
                'interview_rating' => $interviewRating,
                'interview_meets' => $interviewMeets,
                'rule_result' => $ruleResult,
                'recommendation' => $recommendation,
                'submitted_at' => (string)($application['submitted_at'] ?? ''),
            ];
        }

        usort($rows, static function (array $left, array $right): int {
            $priority = ['pass' => 1, 'conditional' => 2, 'fail' => 3];
            $leftPriority = $priority[strtolower((string)($left['rule_result'] ?? 'fail'))] ?? 4;
            $rightPriority = $priority[strtolower((string)($right['rule_result'] ?? 'fail'))] ?? 4;
            if ($leftPriority !== $rightPriority) {
                return $leftPriority <=> $rightPriority;
            }
            return strcmp((string)($left['applicant_name'] ?? ''), (string)($right['applicant_name'] ?? ''));
        });

        return [
            'rows' => $rows,
            'summary' => $summary,
        ];
    }
}
