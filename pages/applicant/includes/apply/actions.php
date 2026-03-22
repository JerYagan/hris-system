<?php

require_once dirname(__DIR__, 3) . '/admin/includes/notifications/email.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

if (!function_exists('applicantLoadRecruitmentEmailTemplates')) {
    function applicantLoadRecruitmentEmailTemplates(string $supabaseUrl, array $headers): array
    {
        $defaults = [
            'submitted' => [
                'subject' => 'Application Submitted: {application_ref_no}',
                'body' => 'Hello {applicant_name},<br><br>Your application for <strong>{job_title}</strong> has been submitted successfully.<br>Reference: <strong>{application_ref_no}</strong><br><br>Remarks: {remarks}<br><br>Thank you.',
            ],
            'passed' => ['subject' => '', 'body' => ''],
            'failed' => ['subject' => '', 'body' => ''],
            'next_stage' => ['subject' => '', 'body' => ''],
        ];

        $response = apiRequest(
            'GET',
            rtrim($supabaseUrl, '/') . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('recruitment.email_templates') . '&limit=1',
            $headers
        );

        if (!isSuccessful($response)) {
            return $defaults;
        }

        $raw = $response['data'][0]['setting_value'] ?? null;
        $value = is_array($raw) && array_key_exists('value', $raw) ? $raw['value'] : $raw;
        if (!is_array($value)) {
            return $defaults;
        }

        foreach (['submitted', 'passed', 'failed', 'next_stage'] as $key) {
            $row = is_array($value[$key] ?? null) ? (array)$value[$key] : [];
            $subject = trim((string)($row['subject'] ?? ''));
            $body = trim((string)($row['body'] ?? ''));
            if ($subject !== '') {
                $defaults[$key]['subject'] = $subject;
            }
            if ($body !== '') {
                $defaults[$key]['body'] = $body;
            }
        }

        return $defaults;
    }
}

if (!function_exists('applicantTemplateRender')) {
    function applicantTemplateRender(string $template, array $replacements): string
    {
        $rendered = $template;
        foreach ($replacements as $key => $value) {
            $rendered = str_replace('{' . $key . '}', (string)$value, $rendered);
        }

        return $rendered;
    }
}

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.', 'apply.php');
}

if ($applicantUserId === '') {
    redirectWithState('error', 'Applicant session is missing. Please login again.', 'apply.php');
}

if (!isValidUuid($applicantUserId)) {
    redirectWithState('error', 'Invalid applicant session context. Please login again.', 'apply.php');
}

$jobId = cleanText($_POST['job_id'] ?? null);
$consent = cleanText($_POST['consent_declaration'] ?? null);

$educationAttainment = cleanText($_POST['education_attainment'] ?? null);
$courseStrand = cleanText($_POST['course_strand'] ?? null);
$schoolInstitution = cleanText($_POST['school_institution'] ?? null);
$recentPosition = cleanText($_POST['recent_position'] ?? null);
$companyOrganization = cleanText($_POST['company_organization'] ?? null);
$yearsExperienceRaw = cleanText($_POST['years_experience'] ?? null);
$certificationsTrainings = cleanText($_POST['certifications_trainings'] ?? null);
$trainingHoursCompletedRaw = cleanText($_POST['training_hours_completed'] ?? null);

$educationLevelEntries = (array)($_POST['education_level_entry'] ?? []);
$educationSchoolEntries = (array)($_POST['education_school_name_entry'] ?? []);
$educationCourseEntries = (array)($_POST['education_course_degree_entry'] ?? []);
$educationYearEntries = (array)($_POST['education_year_graduated_entry'] ?? []);

$workPositionEntries = (array)($_POST['work_position_title_entry'] ?? []);
$workCompanyEntries = (array)($_POST['work_company_name_entry'] ?? []);
$workStartEntries = (array)($_POST['work_start_date_entry'] ?? []);
$workEndEntries = (array)($_POST['work_end_date_entry'] ?? []);
$workResponsibilityEntries = (array)($_POST['work_responsibilities_entry'] ?? []);

$profileQualificationSnapshot = [
    'education_years_estimate' => 0.0,
    'experience_years_estimate' => 0.0,
    'education_entries_count' => 0,
    'work_entries_count' => 0,
];

$eligibilityDocumentInputKey = 'eligibility_document';
$trainingProofDocumentInputKey = 'training_certificate_proof';

$isValidEligibilityFilename = static function (?string $fileName): bool {
    $name = strtolower(trim((string)$fileName));
    if ($name === '') {
        return false;
    }

    return str_contains($name, 'csc')
        || str_contains($name, 'prc')
        || str_contains($name, 'eligibility');
};

$isValidTrainingProofFilename = static function (?string $fileName): bool {
    $name = strtolower(trim((string)$fileName));
    if ($name === '') {
        return false;
    }

    return str_contains($name, 'training')
        || str_contains($name, 'seminar')
        || str_contains($name, 'workshop')
        || str_contains($name, 'certificate')
        || str_contains($name, 'cert');
};

$allowedUploadPolicyByDocumentType = [
    'resume' => [
        'mime' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed',
        ],
        'ext' => ['pdf', 'doc', 'docx'],
    ],
    'pds' => [
        'mime' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed',
        ],
        'ext' => ['pdf', 'doc', 'docx'],
    ],
    'transcript' => [
        'mime' => ['application/pdf', 'image/jpeg', 'image/png'],
        'ext' => ['pdf', 'jpg', 'jpeg', 'png'],
    ],
    'certificate' => [
        'mime' => ['application/pdf', 'image/jpeg', 'image/png'],
        'ext' => ['pdf', 'jpg', 'jpeg', 'png'],
    ],
    'id' => [
        'mime' => ['application/pdf', 'image/jpeg', 'image/png'],
        'ext' => ['pdf', 'jpg', 'jpeg', 'png'],
    ],
    'other' => [
        'mime' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed',
            'image/jpeg',
            'image/png',
        ],
        'ext' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
    ],
];

$maxFileSizeBytes = 5 * 1024 * 1024;

$requiredUploads = [];
$existingReusableDocumentsByType = [];
$existingReusableEligibilityDocument = null;
$existingReusableTrainingDocument = null;

$returnPath = 'apply.php' . ($jobId !== null ? ('?job_id=' . urlencode($jobId)) : '');

if ($jobId === null) {
    redirectWithState('error', 'Missing job reference. Please choose a posting first.', 'job-list.php');
}

if (!isValidUuid($jobId)) {
    redirectWithState('error', 'Invalid job reference. Please choose a posting again.', 'job-list.php');
}

if ($consent !== '1') {
    redirectWithState('error', 'You must confirm the declaration before submitting.', $returnPath);
}

$yearsExperience = null;
if ($yearsExperienceRaw !== null) {
    if (!is_numeric($yearsExperienceRaw) || (float)$yearsExperienceRaw < 0) {
        redirectWithState('error', 'Years of experience must be a valid non-negative number.', $returnPath);
    }
    $yearsExperience = (float)$yearsExperienceRaw;
}

$trainingHoursCompleted = 0.0;
if ($trainingHoursCompletedRaw !== null && trim((string)$trainingHoursCompletedRaw) !== '') {
    if (!is_numeric($trainingHoursCompletedRaw) || (float)$trainingHoursCompletedRaw < 0) {
        redirectWithState('error', 'Training hours must be a valid non-negative number.', $returnPath);
    }

    $trainingHoursCompleted = (float)$trainingHoursCompletedRaw;
}

$applicantProfileResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/applicant_profiles?select=id,full_name,email&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
    $headers
);

if (!isSuccessful($applicantProfileResponse) || empty((array)($applicantProfileResponse['data'] ?? []))) {
    redirectWithState('error', 'Applicant profile is missing. Please update your profile first.', 'profile.php?edit=true');
}

$applicantProfileId = (string)($applicantProfileResponse['data'][0]['id'] ?? '');
if (!isValidUuid($applicantProfileId)) {
    redirectWithState('error', 'Applicant profile context could not be resolved.', 'profile.php?edit=true');
}

$syncTrainingHoursResponse = apiRequest(
    'PATCH',
    $supabaseUrl . '/rest/v1/applicant_profiles?id=eq.' . rawurlencode($applicantProfileId),
    array_merge($headers, ['Prefer: return=minimal']),
    [
        'training_hours_completed' => $trainingHoursCompleted,
    ]
);

if (!isSuccessful($syncTrainingHoursResponse)) {
    redirectWithState('error', 'Failed to sync training hours to applicant profile.', $returnPath);
}

$educationLevelToYears = static function (?string $value): float {
    $key = strtolower(trim((string)$value));
    return match ($key) {
        'graduate' => 6.0,
        'college' => 4.0,
        'vocational' => 2.0,
        'secondary', 'elementary' => 0.0,
        default => 0.0,
    };
};

$parseDate = static function (?string $value): ?DateTimeImmutable {
    $candidate = trim((string)$value);
    if ($candidate === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $candidate);
    if ($date instanceof DateTimeImmutable) {
        return $date;
    }

    try {
        return new DateTimeImmutable($candidate);
    } catch (Throwable) {
        return null;
    }
};

$deleteWorkExperiences = static function (string $personId) use ($supabaseUrl, $headers): array {
    $workLookupResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/person_work_experiences?select=id&person_id=eq.' . rawurlencode($personId) . '&limit=500',
        $headers
    );

    if (!isSuccessful($workLookupResponse)) {
        return $workLookupResponse;
    }

    $workIds = [];
    foreach ((array)($workLookupResponse['data'] ?? []) as $workRow) {
        $workId = cleanText($workRow['id'] ?? null) ?? '';
        if (isValidUuid($workId)) {
            $workIds[] = $workId;
        }
    }

    if ($workIds === []) {
        return ['status' => 204, 'data' => []];
    }

    return apiRequest(
        'DELETE',
        $supabaseUrl
        . '/rest/v1/person_work_experiences?id=in.' . rawurlencode('(' . implode(',', $workIds) . ')')
        . '&person_id=eq.' . rawurlencode($personId),
        array_merge($headers, ['Prefer: return=minimal'])
    );
};

$personResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/people?select=id&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
    $headers
);

$personId = isSuccessful($personResponse)
    ? cleanText($personResponse['data'][0]['id'] ?? null)
    : null;

if ($personId === null || !isValidUuid($personId)) {
    redirectWithState('error', 'Please complete your profile first before applying. Education and work experience records are required for automatic evaluation.', 'profile.php?edit=true');
}

$allowedEducationLevels = ['elementary', 'secondary', 'vocational', 'college', 'graduate'];
$educationRows = [];
$educationCount = max(
    count($educationLevelEntries),
    count($educationSchoolEntries),
    count($educationCourseEntries),
    count($educationYearEntries)
);

for ($index = 0; $index < $educationCount; $index++) {
    $level = strtolower((string)(cleanText($educationLevelEntries[$index] ?? null) ?? ''));
    $school = cleanText($educationSchoolEntries[$index] ?? null);
    $course = cleanText($educationCourseEntries[$index] ?? null);
    $yearGraduated = cleanText($educationYearEntries[$index] ?? null);

    if ($level === '' && $school === null && $course === null && $yearGraduated === null) {
        continue;
    }

    if (!in_array($level, $allowedEducationLevels, true)) {
        redirectWithState('error', 'Please select a valid education level for each education entry.', $returnPath);
    }

    if (in_array($level, ['elementary', 'secondary'], true)) {
        $course = null;
    }

    $educationRows[] = [
        'person_id' => $personId,
        'education_level' => $level,
        'school_name' => $school,
        'course_degree' => $course,
        'year_graduated' => $yearGraduated,
        'sequence_no' => count($educationRows) + 1,
    ];
}

$workRows = [];
$workCount = max(
    count($workPositionEntries),
    count($workCompanyEntries),
    count($workStartEntries),
    count($workEndEntries),
    count($workResponsibilityEntries)
);

for ($index = 0; $index < $workCount; $index++) {
    $positionTitle = cleanText($workPositionEntries[$index] ?? null);
    $companyName = cleanText($workCompanyEntries[$index] ?? null);
    $startDate = cleanText($workStartEntries[$index] ?? null);
    $endDate = cleanText($workEndEntries[$index] ?? null);
    $responsibilities = cleanText($workResponsibilityEntries[$index] ?? null);

    if ($positionTitle === null && $companyName === null && $startDate === null && $responsibilities === null) {
        continue;
    }

    if ($startDate === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        redirectWithState('error', 'Each work experience entry requires a valid start date.', $returnPath);
    }

    if ($endDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        redirectWithState('error', 'Work experience end date must be a valid date.', $returnPath);
    }

    if ($endDate !== null && $endDate < $startDate) {
        redirectWithState('error', 'Work experience end date cannot be earlier than start date.', $returnPath);
    }

    $workRows[] = [
        'person_id' => $personId,
        'inclusive_date_from' => $startDate,
        'inclusive_date_to' => $endDate,
        'position_title' => $positionTitle,
        'office_company' => $companyName,
        'achievements' => $responsibilities,
        'sequence_no' => count($workRows) + 1,
    ];
}

if (empty($educationRows)) {
    redirectWithState('error', 'Please complete your Education Background before applying so the system can auto-evaluate your qualifications.', $returnPath);
}

$educationDeleteResponse = apiRequest(
    'DELETE',
    $supabaseUrl . '/rest/v1/person_educations?person_id=eq.' . rawurlencode($personId),
    $headers
);

if (!isSuccessful($educationDeleteResponse)) {
    redirectWithState('error', 'Failed to refresh education entries from apply form.', $returnPath);
}

$educationInsertResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/person_educations',
    array_merge($headers, ['Prefer: return=minimal']),
    $educationRows
);

if (!isSuccessful($educationInsertResponse)) {
    redirectWithState('error', 'Failed to save education entries from apply form.', $returnPath);
}

$workDeleteResponse = $deleteWorkExperiences($personId);

if (!isSuccessful($workDeleteResponse)) {
    redirectWithState('error', 'Failed to refresh work experience entries from apply form.', $returnPath);
}

if (!empty($workRows)) {
    $workInsertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/person_work_experiences',
        array_merge($headers, ['Prefer: return=minimal']),
        $workRows
    );

    if (!isSuccessful($workInsertResponse)) {
        redirectWithState('error', 'Failed to save work experience entries from apply form.', $returnPath);
    }
}

$profileQualificationSnapshot['education_entries_count'] = count($educationRows);
$profileQualificationSnapshot['work_entries_count'] = count($workRows);

$highestEducationYears = 0.0;
foreach ($educationRows as $educationRow) {
    $years = $educationLevelToYears((string)($educationRow['education_level'] ?? ''));
    if ($years > $highestEducationYears) {
        $highestEducationYears = $years;
    }
}
$profileQualificationSnapshot['education_years_estimate'] = $highestEducationYears;

$totalExperienceDays = 0;
$todayDate = new DateTimeImmutable('today');
foreach ($workRows as $workRow) {
    $fromDate = $parseDate((string)($workRow['inclusive_date_from'] ?? ''));
    if (!$fromDate instanceof DateTimeImmutable) {
        continue;
    }

    $toDate = $parseDate((string)($workRow['inclusive_date_to'] ?? ''));
    if (!$toDate instanceof DateTimeImmutable) {
        $toDate = $todayDate;
    }

    if ($toDate < $fromDate) {
        continue;
    }

    $totalExperienceDays += (int)$fromDate->diff($toDate)->days + 1;
}

$profileQualificationSnapshot['experience_years_estimate'] = round($totalExperienceDays / 365, 2);

if ($educationAttainment === null) {
    $educationAttainment = match (true) {
        $highestEducationYears >= 6.0 => 'Graduate Studies',
        $highestEducationYears >= 4.0 => 'College',
        $highestEducationYears >= 2.0 => 'Vocational/Trade Course',
        default => 'Secondary',
    };
}

$existingDocumentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/application_documents?select=id,document_type,file_url,file_name,mime_type,file_size_bytes,uploaded_at,application:applications!inner(applicant_profile_id)'
    . '&application.applicant_profile_id=eq.' . rawurlencode($applicantProfileId)
    . '&order=uploaded_at.desc&limit=1000',
    $headers
);

if (isSuccessful($existingDocumentsResponse)) {
    foreach ((array)($existingDocumentsResponse['data'] ?? []) as $documentRow) {
        $documentType = strtolower((string)($documentRow['document_type'] ?? ''));
        $fileName = (string)($documentRow['file_name'] ?? 'document');

        if ($existingReusableEligibilityDocument === null
            && in_array($documentType, ['certificate', 'id', 'other'], true)
            && $isValidEligibilityFilename($fileName)
        ) {
            $existingReusableEligibilityDocument = [
                'file_url' => (string)($documentRow['file_url'] ?? ''),
                'file_name' => $fileName,
                'mime_type' => (string)($documentRow['mime_type'] ?? 'application/octet-stream'),
                'file_size_bytes' => (int)($documentRow['file_size_bytes'] ?? 0),
            ];
        }

        if ($existingReusableTrainingDocument === null
            && in_array($documentType, ['certificate', 'other'], true)
            && $isValidTrainingProofFilename($fileName)
        ) {
            $existingReusableTrainingDocument = [
                'file_url' => (string)($documentRow['file_url'] ?? ''),
                'file_name' => $fileName,
                'mime_type' => (string)($documentRow['mime_type'] ?? 'application/octet-stream'),
                'file_size_bytes' => (int)($documentRow['file_size_bytes'] ?? 0),
            ];
        }

        if ($documentType === '' || array_key_exists($documentType, $existingReusableDocumentsByType)) {
            continue;
        }

        $existingReusableDocumentsByType[$documentType] = [
            'file_url' => (string)($documentRow['file_url'] ?? ''),
            'file_name' => $fileName,
            'mime_type' => (string)($documentRow['mime_type'] ?? 'application/octet-stream'),
            'file_size_bytes' => (int)($documentRow['file_size_bytes'] ?? 0),
        ];
    }
}

$today = date('Y-m-d');
$jobResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/job_postings?select=id,title,position_id,open_date,close_date,posting_status,required_documents'
    . '&id=eq.' . rawurlencode($jobId)
    . '&posting_status=eq.published'
    . '&open_date=lte.' . $today
    . '&close_date=gte.' . $today
    . '&limit=1',
    $headers
);

if (!isSuccessful($jobResponse)) {
    $jobResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/job_postings?select=id,title,position_id,open_date,close_date,posting_status'
        . '&id=eq.' . rawurlencode($jobId)
        . '&posting_status=eq.published'
        . '&open_date=lte.' . $today
        . '&close_date=gte.' . $today
        . '&limit=1',
        $headers
    );
}

if (!isSuccessful($jobResponse) || empty((array)($jobResponse['data'] ?? []))) {
    redirectWithState('error', 'This job posting is not open for applications.', 'job-list.php');
}

$jobRow = (array)($jobResponse['data'][0] ?? []);
$requiredDocumentsConfig = normalizeRequiredDocumentConfig($jobRow['required_documents'] ?? null);

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

$resolveCriteriaByPosition = static function (string $positionId) use ($supabaseUrl, $headers, $normalizeEligibilityOption): array {
    $default = [
        'eligibility_option' => 'none',
        'minimum_education_years' => 2,
        'minimum_training_hours' => 4,
        'minimum_experience_years' => 1,
    ];

    if ($positionId === '' || !isValidUuid($positionId)) {
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
        'minimum_education_years' => max(0, (float)($row['minimum_education_years'] ?? $default['minimum_education_years'])),
        'minimum_training_hours' => max(0, (float)($row['minimum_training_hours'] ?? $default['minimum_training_hours'])),
        'minimum_experience_years' => max(0, (float)($row['minimum_experience_years'] ?? $default['minimum_experience_years'])),
    ];
};

$positionId = cleanText($jobRow['position_id'] ?? null) ?? '';
$resolvedCriteria = $resolveCriteriaByPosition($positionId);
$experienceRequirement = (float)($resolvedCriteria['minimum_experience_years'] ?? 0);

if ($experienceRequirement > 0 && empty($workRows)) {
    redirectWithState('error', 'Please complete your Work Experience before applying because this posting requires prior experience.', $returnPath);
}

$educationYears = (float)($profileQualificationSnapshot['education_years_estimate'] ?? 0);
$experienceYears = (float)($profileQualificationSnapshot['experience_years_estimate'] ?? 0);
if ($yearsExperience !== null) {
    $experienceYears = max($experienceYears, (float)$yearsExperience);
}
$hasFreshTrainingProofUpload = isset($_FILES[$trainingProofDocumentInputKey])
    && (int)($_FILES[$trainingProofDocumentInputKey]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

$trainingHours = $trainingHoursCompleted;
$trainingProofAvailable = $hasFreshTrainingProofUpload || is_array($existingReusableTrainingDocument);

$missingCriteria = [];
if ($educationYears < (float)$resolvedCriteria['minimum_education_years']) {
    $missingCriteria[] = 'Education';
}
if ($trainingHours < (float)$resolvedCriteria['minimum_training_hours']
    || ((float)$resolvedCriteria['minimum_training_hours'] > 0 && !$trainingProofAvailable)
) {
    $missingCriteria[] = 'Training';
}
if ($experienceYears < (float)$resolvedCriteria['minimum_experience_years']) {
    $missingCriteria[] = 'Experience';
}

if (!empty($missingCriteria)) {
    redirectWithState('error', 'Missing criteria: ' . implode(', ', $missingCriteria) . '. Your application may be marked as Not Qualified during screening.', $returnPath);
}

if ((string)($resolvedCriteria['eligibility_option'] ?? 'none') !== 'none') {
    $hasFreshEligibilityUpload = isset($_FILES[$eligibilityDocumentInputKey])
        && (int)($_FILES[$eligibilityDocumentInputKey]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

    if ($hasFreshEligibilityUpload) {
        $eligibilityFileName = (string)($_FILES[$eligibilityDocumentInputKey]['name'] ?? '');
        if (!$isValidEligibilityFilename($eligibilityFileName)) {
            redirectWithState('error', 'Eligibility upload must clearly indicate CSC or PRC eligibility in the filename.', $returnPath);
        }
    }

    $hasEligibilityHint = $hasFreshEligibilityUpload || is_array($existingReusableEligibilityDocument);

    if (!$hasEligibilityHint) {
        redirectWithState('error', 'Eligibility appears required for this position. Please ensure your upload includes valid CSC/PRC supporting proof to avoid a Not Qualified result.', $returnPath);
    }
}

if ((float)$resolvedCriteria['minimum_training_hours'] > 0) {
    $requiredUploads[$trainingProofDocumentInputKey] = [
        'document_type' => 'certificate',
        'label' => 'Training Certificate/Proof',
    ];
}

foreach ($requiredDocumentsConfig as $documentConfig) {
    if (!((bool)($documentConfig['required'] ?? true))) {
        continue;
    }

    $inputName = (string)($documentConfig['key'] ?? '');
    if ($inputName === '') {
        continue;
    }

    $requiredUploads[$inputName] = [
        'document_type' => (string)($documentConfig['document_type'] ?? 'other'),
        'label' => (string)($documentConfig['label'] ?? 'Required document'),
    ];
}

if (empty($requiredUploads)) {
    foreach (defaultRequiredDocumentConfig() as $documentConfig) {
        if (!((bool)($documentConfig['required'] ?? true))) {
            continue;
        }

        $inputName = (string)($documentConfig['key'] ?? '');
        if ($inputName === '') {
            continue;
        }

        $requiredUploads[$inputName] = [
            'document_type' => (string)($documentConfig['document_type'] ?? 'other'),
            'label' => (string)($documentConfig['label'] ?? 'Required document'),
        ];
    }
}

if ((string)($resolvedCriteria['eligibility_option'] ?? 'none') !== 'none') {
    $requiredUploads[$eligibilityDocumentInputKey] = [
        'document_type' => 'certificate',
        'label' => 'CSC/PRC Eligibility Document',
    ];
}

$duplicateCheckResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/applications?select=id'
    . '&applicant_profile_id=eq.' . rawurlencode($applicantProfileId)
    . '&job_posting_id=eq.' . rawurlencode($jobId)
    . '&limit=1',
    $headers
);

if (isSuccessful($duplicateCheckResponse) && !empty((array)($duplicateCheckResponse['data'] ?? []))) {
    redirectWithState('error', 'You already submitted an application for this posting.', 'job-view.php?job_id=' . urlencode($jobId));
}

foreach ($requiredUploads as $inputName => $documentConfig) {
    $documentType = strtolower(trim((string)($documentConfig['document_type'] ?? 'other')));
    $hasFreshUpload = isset($_FILES[$inputName]) && (int)($_FILES[$inputName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

    if ($inputName === $eligibilityDocumentInputKey) {
        if (!$hasFreshUpload && is_array($existingReusableEligibilityDocument)) {
            continue;
        }

        if (!$hasFreshUpload) {
            redirectWithState('error', 'Please upload: CSC/PRC Eligibility Document.', $returnPath);
        }
    }

    if ($inputName !== $trainingProofDocumentInputKey
        && !$hasFreshUpload
        && array_key_exists($documentType, $existingReusableDocumentsByType)
    ) {
        continue;
    }

    if (!$hasFreshUpload) {
        $missingLabel = (string)($documentConfig['label'] ?? 'a required document');
        redirectWithState('error', 'Please upload: ' . $missingLabel . '.', $returnPath);
    }

    $fileSize = (int)($_FILES[$inputName]['size'] ?? 0);
    if ($fileSize <= 0 || $fileSize > $maxFileSizeBytes) {
        redirectWithState('error', 'One or more files exceed the 5MB upload limit.', $returnPath);
    }

    $documentPolicy = (array)($allowedUploadPolicyByDocumentType[$documentType] ?? $allowedUploadPolicyByDocumentType['other']);
    $allowedMimeTypes = array_map(static fn(string $value): string => strtolower($value), (array)($documentPolicy['mime'] ?? []));
    $allowedExtensions = array_map(static fn(string $value): string => strtolower($value), (array)($documentPolicy['ext'] ?? []));

    $originalName = (string)($_FILES[$inputName]['name'] ?? '');
    $fileExtension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
    if ($fileExtension === '' || !in_array($fileExtension, $allowedExtensions, true)) {
        $label = (string)($documentConfig['label'] ?? 'document');
        redirectWithState('error', 'Invalid file extension for ' . $label . '. Allowed extensions: ' . strtoupper(implode(', ', $allowedExtensions)) . '.', $returnPath);
    }

    $tmpFilePath = (string)($_FILES[$inputName]['tmp_name'] ?? '');
    $detectedMimeType = strtolower((string)(detectUploadedMimeType($tmpFilePath) ?? ''));
    if ($detectedMimeType === '' || !in_array($detectedMimeType, $allowedMimeTypes, true)) {
        $label = (string)($documentConfig['label'] ?? 'document');
        redirectWithState('error', 'Invalid file type for ' . $label . '. Allowed types are based on the document policy.', $returnPath);
    }

    if (in_array($detectedMimeType, ['image/jpeg', 'image/png'], true) && @getimagesize($tmpFilePath) === false) {
        redirectWithState('error', 'One or more uploaded image files are corrupted or invalid.', $returnPath);
    }

    if ($detectedMimeType === 'application/pdf') {
        $headerChunk = @file_get_contents($tmpFilePath, false, null, 0, 4);
        if (!is_string($headerChunk) || $headerChunk !== '%PDF') {
            redirectWithState('error', 'Invalid PDF file detected. Please upload a valid PDF document.', $returnPath);
        }
    }

    if ($inputName === $eligibilityDocumentInputKey && !$isValidEligibilityFilename($originalName)) {
        redirectWithState('error', 'Eligibility upload must clearly indicate CSC or PRC eligibility in the filename.', $returnPath);
    }

}

$applicationRefNo = 'APP-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 6));

$applicationCreatePayload = [[
    'applicant_profile_id' => $applicantProfileId,
    'job_posting_id' => $jobId,
    'application_ref_no' => $applicationRefNo,
    'application_status' => 'submitted',
]];

$applicationCreateResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/applications',
    array_merge($headers, ['Prefer: return=representation']),
    $applicationCreatePayload
);

if (!isSuccessful($applicationCreateResponse)) {
    redirectWithState('error', 'Failed to create application record.', $returnPath);
}

$applicationId = (string)($applicationCreateResponse['data'][0]['id'] ?? '');
if (!isValidUuid($applicationId)) {
    redirectWithState('error', 'Application was created but could not be resolved.', $returnPath);
}

$historyCreateResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/application_status_history',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'application_id' => $applicationId,
        'old_status' => null,
        'new_status' => 'submitted',
        'changed_by' => null,
        'notes' => 'Application submitted by applicant.',
    ]]
);

if (!isSuccessful($historyCreateResponse)) {
    apiRequest('DELETE', $supabaseUrl . '/rest/v1/applications?id=eq.' . rawurlencode($applicationId), $headers);
    redirectWithState('error', 'Failed to create application timeline record.', $returnPath);
}

$documentRows = [];
$timestampPrefix = date('YmdHis');

foreach ($requiredUploads as $inputName => $documentConfig) {
    $documentType = (string)($documentConfig['document_type'] ?? 'other');
    $documentTypeKey = strtolower(trim($documentType));
    $hasFreshUpload = isset($_FILES[$inputName]) && (int)($_FILES[$inputName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

    if ($inputName === $eligibilityDocumentInputKey && !$hasFreshUpload && is_array($existingReusableEligibilityDocument)) {
        $existingDocument = (array)$existingReusableEligibilityDocument;
        $documentRows[] = [
            'application_id' => $applicationId,
            'document_type' => 'certificate',
            'file_url' => (string)($existingDocument['file_url'] ?? ''),
            'file_name' => (string)($existingDocument['file_name'] ?? 'document'),
            'mime_type' => (string)($existingDocument['mime_type'] ?? 'application/octet-stream'),
            'file_size_bytes' => (int)($existingDocument['file_size_bytes'] ?? 0),
        ];
        continue;
    }

    if ($inputName === $trainingProofDocumentInputKey && !$hasFreshUpload && is_array($existingReusableTrainingDocument)) {
        $existingDocument = (array)$existingReusableTrainingDocument;
        $documentRows[] = [
            'application_id' => $applicationId,
            'document_type' => 'certificate',
            'file_url' => (string)($existingDocument['file_url'] ?? ''),
            'file_name' => (string)($existingDocument['file_name'] ?? 'document'),
            'mime_type' => (string)($existingDocument['mime_type'] ?? 'application/octet-stream'),
            'file_size_bytes' => (int)($existingDocument['file_size_bytes'] ?? 0),
        ];
        continue;
    }

    if ($inputName !== $trainingProofDocumentInputKey
        && !$hasFreshUpload
        && array_key_exists($documentTypeKey, $existingReusableDocumentsByType)
    ) {
        $existingDocument = (array)$existingReusableDocumentsByType[$documentTypeKey];
        $documentRows[] = [
            'application_id' => $applicationId,
            'document_type' => $documentType,
            'file_url' => (string)($existingDocument['file_url'] ?? ''),
            'file_name' => (string)($existingDocument['file_name'] ?? 'document'),
            'mime_type' => (string)($existingDocument['mime_type'] ?? 'application/octet-stream'),
            'file_size_bytes' => (int)($existingDocument['file_size_bytes'] ?? 0),
        ];
        continue;
    }

    $tmpPath = (string)($_FILES[$inputName]['tmp_name'] ?? '');
    $originalName = (string)($_FILES[$inputName]['name'] ?? 'document');
    $safeFileName = normalizeUploadFilename($originalName);
    $inputSegment = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($inputName)) ?? 'document';
    $inputSegment = trim($inputSegment, '-');
    if ($inputSegment === '') {
        $inputSegment = 'document';
    }

    $uniqueToken = substr(bin2hex(random_bytes(4)), 0, 8);
    $storagePath = 'applications/' . $applicantUserId . '/' . $applicationId . '/' . $documentType . '/' . $timestampPrefix . '-' . $inputSegment . '-' . $uniqueToken . '-' . $safeFileName;
    $mimeType = detectUploadedMimeType($tmpPath) ?? (string)($_FILES[$inputName]['type'] ?? 'application/octet-stream');

    $uploadResponse = uploadFileToSupabaseStorage(
        $supabaseUrl,
        $serviceRoleKey,
        'hris-applications',
        $storagePath,
        $tmpPath,
        $mimeType
    );

    if (!isSuccessful($uploadResponse)) {
        apiRequest('DELETE', $supabaseUrl . '/rest/v1/applications?id=eq.' . rawurlencode($applicationId), $headers);
        redirectWithState('error', 'Failed to upload one or more required documents.', $returnPath);
    }

    $documentRows[] = [
        'application_id' => $applicationId,
        'document_type' => $documentType,
        'file_url' => rtrim($supabaseUrl, '/') . '/storage/v1/object/hris-applications/' . $storagePath,
        'file_name' => $safeFileName,
        'mime_type' => $mimeType,
        'file_size_bytes' => (int)($_FILES[$inputName]['size'] ?? 0),
    ];
}

$documentsCreateResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/application_documents',
    array_merge($headers, ['Prefer: return=minimal']),
    $documentRows
);

if (!isSuccessful($documentsCreateResponse)) {
    apiRequest('DELETE', $supabaseUrl . '/rest/v1/applications?id=eq.' . rawurlencode($applicationId), $headers);
    redirectWithState('error', 'Application submission failed while saving documents.', $returnPath);
}

$notes = [
    'education_attainment' => $educationAttainment,
    'course_strand' => $courseStrand,
    'school_institution' => $schoolInstitution,
    'recent_position' => $recentPosition,
    'company_organization' => $companyOrganization,
    'years_experience' => $yearsExperience,
    'training_hours_completed' => $trainingHoursCompleted,
    'training_proof_provided' => $trainingProofAvailable,
    'certifications_trainings' => $certificationsTrainings,
    'profile_education_entries_count' => (int)($profileQualificationSnapshot['education_entries_count'] ?? 0),
    'profile_work_entries_count' => (int)($profileQualificationSnapshot['work_entries_count'] ?? 0),
    'profile_education_years_estimate' => (float)($profileQualificationSnapshot['education_years_estimate'] ?? 0),
    'profile_experience_years_estimate' => (float)($profileQualificationSnapshot['experience_years_estimate'] ?? 0),
];

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $applicantUserId,
        'module_name' => 'applications',
        'entity_name' => 'applications',
        'entity_id' => $applicationId,
        'action_name' => 'submit_application',
        'old_data' => null,
        'new_data' => [
            'application_ref_no' => $applicationRefNo,
            'job_posting_id' => $jobId,
            'notes' => $notes,
        ],
        'ip_address' => cleanText($_SERVER['REMOTE_ADDR'] ?? null),
    ]]
);

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/notifications',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'recipient_user_id' => $applicantUserId,
        'category' => 'application',
        'title' => 'Application submitted successfully',
        'body' => 'Reference ' . $applicationRefNo . ' has been submitted and is now under review.',
        'link_url' => 'applications.php',
        'is_read' => false,
    ]]
);

$smtpConfig = [
    'host' => '',
    'port' => 587,
    'username' => '',
    'password' => '',
    'encryption' => 'tls',
    'auth' => '1',
];
$mailFrom = '';
$mailFromName = 'DA HRIS';
$resolvedMail = resolveSmtpMailConfig($supabaseUrl, $headers, $smtpConfig, $mailFrom, $mailFromName);
$smtpResolved = (array)($resolvedMail['smtp'] ?? []);
$mailFromResolved = (string)($resolvedMail['from'] ?? '');
$mailFromNameResolved = (string)($resolvedMail['from_name'] ?? 'DA HRIS');

$applicantEmail = trim((string)($applicantProfileResponse['data'][0]['email'] ?? ''));
$applicantName = trim((string)($applicantProfileResponse['data'][0]['full_name'] ?? 'Applicant'));
$jobTitle = trim((string)($jobRow['title'] ?? 'Job Posting'));

if ($applicantEmail !== '' && filter_var($applicantEmail, FILTER_VALIDATE_EMAIL) && smtpConfigIsReady($smtpResolved, $mailFromResolved)) {
    $templates = applicantLoadRecruitmentEmailTemplates($supabaseUrl, $headers);
    $subjectTemplate = (string)($templates['submitted']['subject'] ?? 'Application Submitted: {application_ref_no}');
    $bodyTemplate = (string)($templates['submitted']['body'] ?? '');
    $replacements = [
        'applicant_name' => $applicantName,
        'job_title' => $jobTitle,
        'application_ref_no' => $applicationRefNo,
        'remarks' => 'Your application was received and is pending screening.',
    ];

    $emailResponse = smtpSendTransactionalEmail(
        $smtpResolved,
        $mailFromResolved,
        $mailFromNameResolved,
        $applicantEmail,
        $applicantName,
        applicantTemplateRender($subjectTemplate, $replacements),
        applicantTemplateRender($bodyTemplate, $replacements)
    );

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $applicantUserId,
            'module_name' => 'recruitment',
            'entity_name' => 'applications',
            'entity_id' => $applicationId,
            'action_name' => 'email_submitted_notification',
            'old_data' => null,
            'new_data' => [
                'recipient_email' => $applicantEmail,
                'status_code' => (int)($emailResponse['status'] ?? 0),
                'application_ref_no' => $applicationRefNo,
            ],
            'ip_address' => cleanText($_SERVER['REMOTE_ADDR'] ?? null),
        ]]
    );
}

redirectWithState('success', 'Application submitted successfully. Reference: ' . $applicationRefNo, 'applications.php');
