<?php
require_once __DIR__ . '/includes/recruitment/bootstrap.php';

$pageTitle = 'Applicant Profile | Admin';
$activePage = 'applicants.php';
$breadcrumbs = ['Recruitment', 'Applicant Profile'];

$applicationId = cleanText($_GET['application_id'] ?? null) ?? '';
$source = strtolower(trim((string)(cleanText($_GET['source'] ?? null) ?? 'admin-applicants')));

$buildBackLink = static function (string $sourceKey): string {
    return match ($sourceKey) {
        'admin-recruitment' => 'recruitment.php',
        'admin-applicant-tracking' => 'applicant-tracking.php',
        default => 'applicants.php',
    };
};

$errorMessage = null;
$applicationRow = [];
$documents = [];
$statusHistory = [];
$interviews = [];
$feedbackRow = [];
$profilePhotoUrl = '';
$qualificationSnapshot = [];
$personId = '';

$resolveDocumentUrl = static function (?string $rawUrl) use ($supabaseUrl): string {
    $value = trim((string)$rawUrl);
    if ($value === '') {
        return '';
    }

    $localDocumentRoot = __DIR__ . '/../../storage/document';
    $resolveLocal = static function (string $rawPath) use ($localDocumentRoot): string {
        $normalized = str_replace('\\', '/', trim($rawPath));
        $normalized = preg_replace('#^https?://[^/]+/storage/v1/object/(?:public/)?[^/]+/#i', '', $normalized);
        $normalized = preg_replace('#^/?storage/v1/object/(?:public/)?[^/]+/#i', '', $normalized);
        $normalized = preg_replace('#^document/#i', '', ltrim((string)$normalized, '/'));
        $normalized = preg_replace('#^storage/document/#i', '', ltrim((string)$normalized, '/'));

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
        $localResolvedAbsolute = $resolveLocal($value);
        if ($localResolvedAbsolute !== '') {
            return $localResolvedAbsolute;
        }
        return $value;
    }

    $value = ltrim($value, './');

    if (str_starts_with($value, 'storage/v1/object/public/')) {
        return rtrim((string)$supabaseUrl, '/') . '/' . $value;
    }

    if (str_starts_with($value, '/storage/v1/object/public/')) {
        return rtrim((string)$supabaseUrl, '/') . '/' . ltrim($value, '/');
    }

    if (str_starts_with($value, 'document/')) {
        $segments = array_values(array_filter(explode('/', preg_replace('#^document/#i', '', $value)), static fn(string $segment): bool => $segment !== ''));
        return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', $segments));
    }

    if (str_starts_with($value, 'storage/document/')) {
        $relative = preg_replace('#^storage/document/#i', '', $value);
        $segments = array_values(array_filter(explode('/', (string)$relative), static fn(string $segment): bool => $segment !== ''));
        return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', $segments));
    }

    $segments = array_values(array_filter(explode('/', $value), static fn(string $segment): bool => $segment !== ''));
    return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', $segments));
};

$statusPill = static function (string $status): array {
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

$readEvaluationCriteria = static function (string $supabaseUrl, array $headers): array {
    $defaults = [
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
    ];

    $response = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('evaluation.rule_based.criteria') . '&limit=1',
        $headers
    );

    if (!isSuccessful($response) || empty($response['data'][0]['setting_value'])) {
        return $defaults;
    }

    $raw = $response['data'][0]['setting_value'] ?? null;
    $stored = is_array($raw) && array_key_exists('value', $raw) ? $raw['value'] : $raw;
    if (!is_array($stored)) {
        return $defaults;
    }

    $weights = is_array($stored['weights'] ?? null) ? (array)$stored['weights'] : [];

    return [
        'eligibility' => strtolower(trim((string)($stored['eligibility'] ?? $defaults['eligibility']))),
        'minimum_education_years' => (float)($stored['minimum_education_years'] ?? $defaults['minimum_education_years']),
        'minimum_training_hours' => (float)($stored['minimum_training_hours'] ?? $defaults['minimum_training_hours']),
        'minimum_experience_years' => (float)($stored['minimum_experience_years'] ?? $defaults['minimum_experience_years']),
        'threshold' => (float)($stored['threshold'] ?? $defaults['threshold']),
        'weights' => [
            'eligibility' => (float)($weights['eligibility'] ?? $defaults['weights']['eligibility']),
            'education' => (float)($weights['education'] ?? $defaults['weights']['education']),
            'training' => (float)($weights['training'] ?? $defaults['weights']['training']),
            'experience' => (float)($weights['experience'] ?? $defaults['weights']['experience']),
        ],
    ];
};

$matchEligibility = static function (string $required, string $actual): bool {
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
        if ($actualKey === $token || str_contains($actualKey, $token) || str_contains($token, $actualKey)) {
            return true;
        }
    }

    return false;
};

$documentLabel = static function (string $type): string {
    return match (strtolower(trim($type))) {
        'pds' => 'PDS',
        'transcript' => 'Transcript of Records',
        'certificate' => 'Certificate',
        'resume' => 'Updated Resume/CV',
        'id' => 'Valid Government ID',
        default => ucwords(str_replace('_', ' ', trim($type) !== '' ? $type : 'other document')),
    };
};

$educationLevelLabel = static function (?string $level): string {
    return match (strtolower(trim((string)$level))) {
        'graduate' => 'Graduate Studies',
        'college' => 'College',
        'vocational' => 'Vocational/Trade Course',
        'secondary' => 'Secondary',
        'elementary' => 'Elementary',
        default => 'Not provided',
    };
};

$normalizeEducationLevel = static function (?string $level): string {
    $key = strtolower(trim((string)$level));

    return match ($key) {
        'elementary' => 'elementary',
        'secondary', 'highschool', 'high_school', 'high-school' => 'secondary',
        'vocational', 'trade', 'trade_course', 'vocational_trade_course', 'vocational/trade course', 'tvet' => 'vocational',
        'college', 'bachelor', 'bachelors', "bachelor's", "bachelor's degree" => 'college',
        'graduate', 'graduate_studies', 'masters', 'masteral', 'doctorate', 'phd' => 'graduate',
        default => 'college',
    };
};

$educationLevelRank = static function (?string $level) use ($normalizeEducationLevel): int {
    return match ($normalizeEducationLevel($level)) {
        'graduate' => 5,
        'college' => 4,
        'vocational' => 3,
        'secondary' => 2,
        default => 1,
    };
};

$educationYearsToLevel = static function (float $years): string {
    if ($years >= 6) {
        return 'graduate';
    }
    if ($years >= 4) {
        return 'college';
    }
    if ($years >= 2) {
        return 'vocational';
    }
    if ($years >= 1) {
        return 'secondary';
    }

    return 'elementary';
};

$educationLevelYears = static function (?string $level): float {
    return match (strtolower(trim((string)$level))) {
        'graduate' => 6.0,
        'college' => 4.0,
        'vocational' => 2.0,
        default => 0.0,
    };
};

$calculateExperienceYears = static function (array $rows): float {
    $totalDays = 0;
    $today = new DateTimeImmutable('today');

    foreach ($rows as $row) {
        $from = trim((string)($row['inclusive_date_from'] ?? ''));
        if ($from === '') {
            continue;
        }

        try {
            $fromDate = new DateTimeImmutable($from);
        } catch (Throwable) {
            continue;
        }

        $toRaw = trim((string)($row['inclusive_date_to'] ?? ''));
        if ($toRaw !== '') {
            try {
                $toDate = new DateTimeImmutable($toRaw);
            } catch (Throwable) {
                $toDate = $today;
            }
        } else {
            $toDate = $today;
        }

        if ($toDate < $fromDate) {
            continue;
        }

        $totalDays += (int)$fromDate->diff($toDate)->days + 1;
    }

    return round($totalDays / 365, 2);
};

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

$resolveCriteriaByPosition = static function (string $positionId) use ($supabaseUrl, $headers, $normalizeEligibilityOption, $normalizeEducationLevel, $educationYearsToLevel): array {
    $default = [
        'eligibility_option' => 'none',
        'minimum_education_level' => 'college',
        'minimum_education_years' => 2,
        'minimum_training_hours' => 4,
        'minimum_experience_years' => 1,
    ];

    if ($positionId === '' || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $positionId)) {
        return $default;
    }

    $criteriaResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('evaluation.rule_based.criteria') . '&limit=1',
        $headers
    );

    if (isSuccessful($criteriaResponse)) {
        $raw = $criteriaResponse['data'][0]['setting_value'] ?? null;
        $value = is_array($raw) && array_key_exists('value', $raw) ? $raw['value'] : $raw;
        if (is_array($value)) {
            $default['eligibility_option'] = $normalizeEligibilityOption((string)($value['eligibility'] ?? 'none'));
            $default['minimum_education_level'] = $normalizeEducationLevel((string)($value['minimum_education_level'] ?? $educationYearsToLevel((float)($value['minimum_education_years'] ?? 2))));
            $default['minimum_education_years'] = max(0, (float)($value['minimum_education_years'] ?? 2));
            $default['minimum_training_hours'] = max(0, (float)($value['minimum_training_hours'] ?? 4));
            $default['minimum_experience_years'] = max(0, (float)($value['minimum_experience_years'] ?? 1));
        }
    }

    $positionCriteriaResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('recruitment.position_criteria') . '&limit=1',
        $headers
    );

    if (!isSuccessful($positionCriteriaResponse)) {
        return $default;
    }

    $raw = $positionCriteriaResponse['data'][0]['setting_value'] ?? null;
    $value = is_array($raw) && array_key_exists('value', $raw) ? $raw['value'] : $raw;
    if (!is_array($value)) {
        return $default;
    }

    $overrides = is_array($value['position_overrides'] ?? null) ? (array)$value['position_overrides'] : [];
    $positionKey = strtolower(trim($positionId));
    $row = is_array($overrides[$positionKey] ?? null) ? (array)$overrides[$positionKey] : [];
    if (empty($row)) {
        return $default;
    }

    return [
        'eligibility_option' => $normalizeEligibilityOption((string)($row['eligibility'] ?? $default['eligibility_option'])),
        'minimum_education_level' => $normalizeEducationLevel((string)($row['minimum_education_level'] ?? $educationYearsToLevel((float)($row['minimum_education_years'] ?? $default['minimum_education_years'])))),
        'minimum_education_years' => max(0, (float)($row['minimum_education_years'] ?? $default['minimum_education_years'])),
        'minimum_training_hours' => max(0, (float)($row['minimum_training_hours'] ?? $default['minimum_training_hours'])),
        'minimum_experience_years' => max(0, (float)($row['minimum_experience_years'] ?? $default['minimum_experience_years'])),
    ];
};

$inferDocumentLabel = static function (string $type, string $fileName) use ($documentLabel): string {
    $normalizedType = strtolower(trim($type));
    $name = strtolower(trim($fileName));

    if ($normalizedType === 'resume') {
        return 'Updated Resume/CV';
    }

    if ($normalizedType === 'pds') {
        return 'Personal Data Sheet';
    }

    if ($normalizedType === 'transcript') {
        return 'Transcript of Records';
    }

    if ($normalizedType === 'id') {
        return 'Valid Government ID';
    }

    if (str_contains($name, 'application') && str_contains($name, 'letter')) {
        return 'Application Letter';
    }

    if (str_contains($name, 'resume') || preg_match('/\bcv\b/', $name) === 1) {
        return 'Updated Resume/CV';
    }

    if (str_contains($name, 'pds') || str_contains($name, 'personal_data_sheet') || str_contains($name, 'personal data sheet')) {
        return 'Personal Data Sheet';
    }

    if (str_contains($name, 'transcript') || preg_match('/\btor\b/', $name) === 1 || str_contains($name, 'diploma')) {
        return 'Transcript of Records';
    }

    if (str_contains($name, 'csc') || str_contains($name, 'prc') || str_contains($name, 'eligibility')) {
        return 'CSC/PRC Eligibility Document';
    }

    if (str_contains($name, 'training') || str_contains($name, 'seminar') || str_contains($name, 'workshop') || str_contains($name, 'certificate') || str_contains($name, 'cert')) {
        return 'Training Certificate/Proof';
    }

    if ($normalizedType === 'certificate') {
        return 'Certificate';
    }

    if ($normalizedType === 'other') {
        return 'Other Supporting Document';
    }

    return $documentLabel($normalizedType);
};

$isUuid = static function (?string $value): bool {
    $candidate = strtolower(trim((string)$value));
    return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $candidate);
};

if (!$isUuid($applicationId)) {
    $errorMessage = 'Invalid application ID.';
} else {
    $applicationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/applications?select=id,application_ref_no,application_status,submitted_at,updated_at,applicant:applicant_profiles(user_id,full_name,email,mobile_no,current_address,resume_url,portfolio_url,training_hours_completed),job:job_postings(id,title,office_id,position_id,required_documents,office:offices(office_name),position:job_positions(position_title))'
        . '&id=eq.' . rawurlencode($applicationId)
        . '&limit=1',
        $headers
    );

    if (!isSuccessful($applicationResponse) || empty($applicationResponse['data'][0])) {
        $errorMessage = 'Application record was not found.';
    } else {
        $applicationRow = (array)$applicationResponse['data'][0];
        $applicant = is_array($applicationRow['applicant'] ?? null) ? (array)$applicationRow['applicant'] : [];
        $userId = trim((string)($applicant['user_id'] ?? ''));

        if ($userId !== '') {
            $peopleResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/people?select=id,profile_photo_url&user_id=eq.' . rawurlencode($userId) . '&limit=1',
                $headers
            );

            if (isSuccessful($peopleResponse) && !empty($peopleResponse['data'][0])) {
                $personId = trim((string)($peopleResponse['data'][0]['id'] ?? ''));
                $profilePhotoUrl = trim((string)($peopleResponse['data'][0]['profile_photo_url'] ?? ''));
            }
        }

        $documentsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/application_documents?select=id,document_type,file_name,file_url,mime_type,uploaded_at'
            . '&application_id=eq.' . rawurlencode($applicationId)
            . '&order=uploaded_at.desc&limit=100',
            $headers
        );

        if (isSuccessful($documentsResponse)) {
            foreach ((array)($documentsResponse['data'] ?? []) as $row) {
                $fileUrl = $resolveDocumentUrl((string)($row['file_url'] ?? ''));
                $documentUuid = trim((string)($row['id'] ?? ''));
                $proxyBase = 'applicant-document.php?document_id=' . rawurlencode($documentUuid);
                $documents[] = [
                    'id' => $documentUuid,
                    'label' => $inferDocumentLabel((string)($row['document_type'] ?? 'other'), (string)($row['file_name'] ?? '')),
                    'document_type' => strtolower(trim((string)($row['document_type'] ?? 'other'))),
                    'file_name' => (string)($row['file_name'] ?? 'document'),
                    'file_url' => $fileUrl,
                    'view_url' => $isUuid($documentUuid) ? $proxyBase : $fileUrl,
                    'download_url' => $isUuid($documentUuid) ? ($proxyBase . '&download=1') : $fileUrl,
                    'uploaded_label' => formatDateTimeForPhilippines(cleanText($row['uploaded_at'] ?? null), 'M d, Y'),
                    'has_file' => trim($fileUrl) !== '',
                ];
            }
        }

        $historyResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/application_status_history?select=old_status,new_status,notes,created_at'
            . '&application_id=eq.' . rawurlencode($applicationId)
            . '&order=created_at.desc&limit=30',
            $headers
        );
        $statusHistory = isSuccessful($historyResponse) ? (array)($historyResponse['data'] ?? []) : [];

        $interviewsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/application_interviews?select=scheduled_at,interview_stage,result,remarks,score,interviewer:user_accounts(email)'
            . '&application_id=eq.' . rawurlencode($applicationId)
            . '&order=scheduled_at.desc&limit=20',
            $headers
        );
        $interviews = isSuccessful($interviewsResponse) ? (array)($interviewsResponse['data'] ?? []) : [];

        $feedbackResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/application_feedback?select=decision,feedback_text,provided_at'
            . '&application_id=eq.' . rawurlencode($applicationId)
            . '&order=provided_at.desc&limit=1',
            $headers
        );
        $feedbackRow = isSuccessful($feedbackResponse) && !empty($feedbackResponse['data'][0])
            ? (array)$feedbackResponse['data'][0]
            : [];

        $criteria = $readEvaluationCriteria($supabaseUrl, $headers);
        $positionId = trim((string)($applicationRow['job']['position_id'] ?? ''));
        $resolvedCriteria = $resolveCriteriaByPosition($positionId);
        $feedbackText = trim((string)($feedbackRow['feedback_text'] ?? ''));
        $structured = json_decode($feedbackText, true);
        if (!is_array($structured)) {
            $structured = [];
        }

        $latestSubmissionSnapshot = [];
        $submissionLogResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/activity_logs?select=new_data,created_at'
            . '&module_name=eq.applications'
            . '&entity_name=eq.applications'
            . '&entity_id=eq.' . rawurlencode($applicationId)
            . '&action_name=eq.submit_application'
            . '&order=created_at.desc&limit=1',
            $headers
        );
        if (isSuccessful($submissionLogResponse) && !empty($submissionLogResponse['data'][0]['new_data']) && is_array($submissionLogResponse['data'][0]['new_data'])) {
            $latestSubmissionSnapshot = (array)$submissionLogResponse['data'][0]['new_data'];
        }

        $educationRows = [];
        $workRows = [];
        if ($personId !== '') {
            $educationResponse = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/person_educations?select=education_level,sequence_no'
                . '&person_id=eq.' . rawurlencode($personId)
                . '&order=sequence_no.asc&limit=200',
                $headers
            );

            if (isSuccessful($educationResponse)) {
                $educationRows = (array)($educationResponse['data'] ?? []);
            }

            $workResponse = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/person_work_experiences?select=inclusive_date_from,inclusive_date_to,sequence_no'
                . '&person_id=eq.' . rawurlencode($personId)
                . '&order=sequence_no.asc&limit=300',
                $headers
            );

            if (isSuccessful($workResponse)) {
                $workRows = (array)($workResponse['data'] ?? []);
            }
        }

        $documentTypes = [];
        $documentFileNames = [];
        foreach ($documents as $document) {
            $type = strtolower(trim((string)($document['document_type'] ?? '')));
            if ($type !== '') {
                $documentTypes[$type] = true;
            }
            $documentFileNames[] = strtolower(trim((string)($document['file_name'] ?? '')));
        }

        $hasCscDocument = false;
        $hasPrcDocument = false;
        foreach ($documentFileNames as $fileName) {
            if (str_contains($fileName, 'csc') || str_contains($fileName, 'career service') || str_contains($fileName, 'eligibility')) {
                $hasCscDocument = true;
            }
            if (str_contains($fileName, 'prc') || str_contains($fileName, 'board')) {
                $hasPrcDocument = true;
            }
        }

        $highestEducationLevel = '';
        $educationActual = 0.0;
        foreach ($educationRows as $educationRow) {
            $level = strtolower(trim((string)($educationRow['education_level'] ?? '')));
            $years = $educationLevelYears($level);
            if ($years > $educationActual) {
                $educationActual = $years;
                $highestEducationLevel = $level;
            }
        }

        $experienceActual = $calculateExperienceYears($workRows);
        if ($experienceActual <= 0 && isset($latestSubmissionSnapshot['profile_experience_years_estimate'])) {
            $experienceActual = max(0.0, (float)$latestSubmissionSnapshot['profile_experience_years_estimate']);
        }

        $trainingActual = 0.0;
        if (isset($applicant['training_hours_completed']) && is_numeric((string)$applicant['training_hours_completed'])) {
            $trainingActual = max(0.0, (float)$applicant['training_hours_completed']);
        } elseif (isset($latestSubmissionSnapshot['training_hours_completed'])) {
            $trainingActual = max(0.0, (float)$latestSubmissionSnapshot['training_hours_completed']);
        } elseif (isset($structured['training_hours'])) {
            $trainingActual = max(0.0, (float)$structured['training_hours']);
        } elseif (isset($structured['hours_of_training'])) {
            $trainingActual = max(0.0, (float)$structured['hours_of_training']);
        }

        $eligibilityActual = strtolower(trim((string)($structured['eligibility'] ?? $structured['eligibility_type'] ?? '')));
        if ($eligibilityActual === '') {
            if ($hasCscDocument && $hasPrcDocument) {
                $eligibilityActual = 'csc/prc';
            } elseif ($hasCscDocument) {
                $eligibilityActual = 'csc';
            } elseif ($hasPrcDocument) {
                $eligibilityActual = 'prc';
            } elseif (($resolvedCriteria['eligibility_option'] ?? 'none') === 'none') {
                $eligibilityActual = 'not required';
            } else {
                $eligibilityActual = 'not provided';
            }
        }

        $requiredEligibilityOption = (string)($resolvedCriteria['eligibility_option'] ?? 'none');
        $eligibilityMeets = match ($requiredEligibilityOption) {
            'none' => true,
            'csc' => $hasCscDocument,
            'prc' => $hasPrcDocument,
            default => ($hasCscDocument || $hasPrcDocument),
        };

        $educationMeets = $educationLevelRank($highestEducationLevel) >= $educationLevelRank((string)($resolvedCriteria['minimum_education_level'] ?? 'college'));
        $trainingMeets = $trainingActual >= (float)$resolvedCriteria['minimum_training_hours'];
        $experienceMeets = $experienceActual >= (float)$resolvedCriteria['minimum_experience_years'];

        $score = 0.0;
        $score += $eligibilityMeets ? (float)($criteria['weights']['eligibility'] ?? 25) : 0.0;
        $score += $educationMeets ? (float)($criteria['weights']['education'] ?? 25) : 0.0;
        $score += $trainingMeets ? (float)($criteria['weights']['training'] ?? 25) : 0.0;
        $score += $experienceMeets ? (float)($criteria['weights']['experience'] ?? 25) : 0.0;

        $requiredEligibilityLabel = match ($requiredEligibilityOption) {
            'none' => 'Not required',
            'csc' => 'CSC',
            'prc' => 'PRC',
            default => 'CSC/PRC',
        };

        $educationLevelDisplay = $educationLevelLabel($highestEducationLevel);

        $qualificationSnapshot = [
            'eligibility' => $eligibilityActual,
            'eligibility_display' => match ($eligibilityActual) {
                'csc/prc' => 'CSC/PRC Document Uploaded',
                'csc' => 'CSC Document Uploaded',
                'prc' => 'PRC Document Uploaded',
                'not required' => 'Not Required',
                'not provided' => 'Not Provided',
                default => strtoupper((string)$eligibilityActual),
            },
            'education_level_label' => $educationLevelDisplay,
            'education_years' => $educationActual,
            'training_hours' => $trainingActual,
            'experience_years' => $experienceActual,
            'required_eligibility' => $requiredEligibilityLabel,
            'required_education_level' => $educationLevelLabel((string)($resolvedCriteria['minimum_education_level'] ?? 'college')),
            'required_education_years' => (float)$resolvedCriteria['minimum_education_years'],
            'required_training_hours' => (float)$resolvedCriteria['minimum_training_hours'],
            'required_experience_years' => (float)$resolvedCriteria['minimum_experience_years'],
            'eligibility_meets' => $eligibilityMeets,
            'education_meets' => $educationMeets,
            'training_meets' => $trainingMeets,
            'experience_meets' => $experienceMeets,
            'score' => (int)round(max(0, min(100, $score))),
            'threshold' => (int)round((float)$criteria['threshold']),
        ];
    }
}

$backLink = $buildBackLink($source);
$statusRaw = (string)($applicationRow['application_status'] ?? 'submitted');
[$statusLabel, $statusClass] = $statusPill($statusRaw);
$applicant = is_array($applicationRow['applicant'] ?? null) ? (array)$applicationRow['applicant'] : [];
$job = is_array($applicationRow['job'] ?? null) ? (array)$applicationRow['job'] : [];
$jobOffice = is_array($job['office'] ?? null) ? (array)$job['office'] : [];
$jobPosition = is_array($job['position'] ?? null) ? (array)$job['position'] : [];

$feedbackText = trim((string)($feedbackRow['feedback_text'] ?? ''));
$feedbackStructured = json_decode($feedbackText, true);
if (!is_array($feedbackStructured)) {
    $feedbackStructured = [];
}

$toList = static function ($value): array {
    if (is_array($value)) {
        return array_values(array_filter(array_map(static fn($item): string => trim((string)$item), $value), static fn(string $item): bool => $item !== ''));
    }

    $text = trim((string)$value);
    if ($text === '') {
        return [];
    }

    $parts = preg_split('/\r\n|\r|\n|\||;/', $text) ?: [];
    return array_values(array_filter(array_map(static fn(string $item): string => trim($item), $parts), static fn(string $item): bool => $item !== ''));
};

$workExperienceItems = $toList($feedbackStructured['work_experience'] ?? null);
$eligibilityDisplay = trim((string)($qualificationSnapshot['eligibility_display'] ?? $qualificationSnapshot['eligibility'] ?? $feedbackStructured['eligibility'] ?? $feedbackStructured['eligibility_type'] ?? 'n/a'));
$educationDisplay = (string)($qualificationSnapshot['education_level_label'] ?? 'Not provided');
$trainingDisplay = number_format((float)($qualificationSnapshot['training_hours'] ?? $feedbackStructured['training_hours'] ?? $feedbackStructured['hours_of_training'] ?? 0), 2);
$experienceDisplay = number_format((float)($qualificationSnapshot['experience_years'] ?? $feedbackStructured['experience_years'] ?? $feedbackStructured['years_of_experience'] ?? 0), 2);

ob_start();
?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Applicant Profile</h1>
        <p class="text-sm text-slate-500">Comprehensive applicant details, uploaded documents, and recruitment history.</p>
    </div>
    <a href="<?= htmlspecialchars($backLink, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>Back
    </a>
</div>

<?php if ($errorMessage): ?>
    <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php else: ?>
    <section class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <aside class="lg:col-span-1 border border-slate-200 rounded-xl p-4 bg-slate-50">
                <div class="flex items-center gap-3">
                    <div class="w-20 h-20 rounded-xl bg-white border border-slate-200 overflow-hidden shrink-0">
                        <?php if ($profilePhotoUrl !== ''): ?>
                            <img src="<?= htmlspecialchars($profilePhotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Applicant photo" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-slate-500 text-3xl">👤</div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800"><?= htmlspecialchars((string)($applicant['full_name'] ?? 'Applicant'), ENT_QUOTES, 'UTF-8') ?></h2>
                        <span class="inline-flex mt-1 px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>

                <dl class="mt-4 space-y-2 text-sm">
                    <div>
                        <dt class="text-slate-500">Email</dt>
                        <dd class="text-slate-700"><?= htmlspecialchars((string)($applicant['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Mobile</dt>
                        <dd class="text-slate-700"><?= htmlspecialchars((string)($applicant['mobile_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Address</dt>
                        <dd class="text-slate-700"><?= htmlspecialchars((string)($applicant['current_address'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Application Ref</dt>
                        <dd class="text-slate-700 font-medium"><?= htmlspecialchars((string)($applicationRow['application_ref_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                </dl>

            </aside>

            <div class="lg:col-span-2 space-y-4">
                <div class="rounded-xl border border-slate-200 p-4">
                    <h3 class="text-base font-semibold text-slate-800">Applied Position</h3>
                    <p class="text-sm text-slate-700 mt-1"><?= htmlspecialchars((string)($jobPosition['position_title'] ?? $job['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-sm text-slate-500 mt-1">Office: <?= htmlspecialchars((string)($jobOffice['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-sm text-slate-500">Submitted: <?= htmlspecialchars(formatDateTimeForPhilippines(cleanText($applicationRow['submitted_at'] ?? null), 'M d, Y h:i A'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <h3 class="text-base font-semibold text-slate-800">Qualification Snapshot</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3 text-sm">
                        <div class="rounded-lg border border-slate-200 p-3 bg-slate-50">
                            <p class="text-slate-500">Eligibility</p>
                            <p class="text-slate-700 font-medium mt-1"><?= htmlspecialchars($eligibilityDisplay !== '' ? $eligibilityDisplay : 'n/a', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-3 bg-slate-50">
                            <p class="text-slate-500">Education</p>
                            <p class="text-slate-700 font-medium mt-1"><?= htmlspecialchars($educationDisplay, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-3 bg-slate-50">
                            <p class="text-slate-500">Training</p>
                            <p class="text-slate-700 font-medium mt-1"><?= htmlspecialchars($trainingDisplay, ENT_QUOTES, 'UTF-8') ?> hour(s)</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-3 bg-slate-50">
                            <p class="text-slate-500">Experience</p>
                            <p class="text-slate-700 font-medium mt-1"><?= htmlspecialchars($experienceDisplay, ENT_QUOTES, 'UTF-8') ?> year(s)</p>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-slate-500">
                        <p>Source alignment: uses applicant profile records, uploaded application documents, and saved apply-submission values for training hours.</p>
                        <p class="mt-1">Recommendation Score: <span class="font-medium text-slate-700"><?= htmlspecialchars((string)($qualificationSnapshot['score'] ?? 0), ENT_QUOTES, 'UTF-8') ?>%</span> (Threshold: <?= htmlspecialchars((string)($qualificationSnapshot['threshold'] ?? 75), ENT_QUOTES, 'UTF-8') ?>%)</p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <h3 class="text-base font-semibold text-slate-800">Work Experience Highlights</h3>
                    <?php if (empty($workExperienceItems)): ?>
                        <p class="text-sm text-slate-500 mt-1">No work experience details available.</p>
                    <?php else: ?>
                        <ul class="mt-2 list-disc pl-5 text-sm text-slate-700 space-y-1">
                            <?php foreach ($workExperienceItems as $item): ?>
                                <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-3">Submitted Documents</h3>
        <div class="overflow-x-auto border border-slate-200 rounded-lg">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-3 py-2">Document Type</th>
                        <th class="text-left px-3 py-2">File Name</th>
                        <th class="text-left px-3 py-2">Uploaded</th>
                        <th class="text-left px-3 py-2">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($documents)): ?>
                        <tr><td class="px-3 py-3 text-slate-500" colspan="4">No submitted documents found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($documents as $document): ?>
                            <tr>
                                <td class="px-3 py-2 text-slate-700"><?= htmlspecialchars((string)($document['label'] ?? 'Document'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-3 py-2 text-slate-700"><?= htmlspecialchars((string)($document['file_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-3 py-2 text-slate-700"><?= htmlspecialchars((string)($document['uploaded_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-3 py-2">
                                    <?php if (!empty($document['has_file'])): ?>
                                        <div class="inline-flex items-center gap-2">
                                            <a href="<?= htmlspecialchars((string)($document['view_url'] ?? $document['file_url']), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="px-2 py-1 text-xs rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">View</a>
                                            <a href="<?= htmlspecialchars((string)($document['download_url'] ?? $document['file_url']), ENT_QUOTES, 'UTF-8') ?>" class="px-2 py-1 text-xs rounded-md border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100">Download</a>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-500">File unavailable</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <article class="bg-white border border-slate-200 rounded-2xl p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-3">Feedback & Evaluation Basis</h3>
            <p class="text-sm text-slate-600"><span class="font-medium text-slate-700">Decision:</span> <?= htmlspecialchars((string)($feedbackRow['decision'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-sm text-slate-600 mt-1"><span class="font-medium text-slate-700">Provided:</span> <?= htmlspecialchars(formatDateTimeForPhilippines(cleanText($feedbackRow['provided_at'] ?? null), 'M d, Y h:i A'), ENT_QUOTES, 'UTF-8') ?></p>
            <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700 whitespace-pre-wrap"><?= htmlspecialchars($feedbackText !== '' ? $feedbackText : 'No feedback remarks recorded.', ENT_QUOTES, 'UTF-8') ?></div>
        </article>
        <article class="bg-white border border-slate-200 rounded-2xl p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-3">Interview History</h3>
            <?php if (empty($interviews)): ?>
                <p class="text-sm text-slate-500">No interviews logged yet.</p>
            <?php else: ?>
                <ul class="space-y-2 text-sm">
                    <?php foreach ($interviews as $interview): ?>
                        <li class="rounded-md border border-slate-200 p-3">
                            <p class="font-medium text-slate-700"><?= htmlspecialchars(formatDateTimeForPhilippines(cleanText($interview['scheduled_at'] ?? null), 'M d, Y h:i A'), ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-slate-600 mt-1">Stage: <?= htmlspecialchars((string)($interview['interview_stage'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> • Result: <?= htmlspecialchars((string)($interview['result'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-slate-600 mt-1">Score: <?= htmlspecialchars((string)($interview['score'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> • Interviewer: <?= htmlspecialchars((string)($interview['interviewer']['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-slate-600 mt-1 whitespace-pre-wrap">Remarks: <?= htmlspecialchars((string)($interview['remarks'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
    </section>

    <section class="bg-white border border-slate-200 rounded-2xl p-6 mt-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-3">Application Status Timeline</h3>
        <?php if (empty($statusHistory)): ?>
            <p class="text-sm text-slate-500">No status history entries found.</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($statusHistory as $history): ?>
                    <div class="rounded-md border border-slate-200 p-3 text-sm">
                        <p class="text-slate-700 font-medium"><?= htmlspecialchars(formatDateTimeForPhilippines(cleanText($history['created_at'] ?? null), 'M d, Y h:i A'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-slate-600 mt-1">From <?= htmlspecialchars((string)($history['old_status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> to <?= htmlspecialchars((string)($history['new_status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-slate-600 mt-1 whitespace-pre-wrap"><?= htmlspecialchars((string)($history['notes'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
