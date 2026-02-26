<?php

require_once dirname(__DIR__) . '/notifications/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (!function_exists('recruitmentIsValidUuid')) {
    function recruitmentIsValidUuid(string $value): bool
    {
        return (bool)preg_match('/^[a-f0-9-]{36}$/i', $value);
    }
}

$action = (string)($_POST['form_action'] ?? '');

if (!function_exists('recruitmentReadSettingValue')) {
    function recruitmentReadSettingValue(string $supabaseUrl, array $headers, string $key): mixed
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
        if (is_array($raw) && array_key_exists('value', $raw)) {
            return $raw['value'];
        }

        return $raw;
    }
}

if (!function_exists('recruitmentUpsertSettingValue')) {
    function recruitmentUpsertSettingValue(string $supabaseUrl, array $headers, string $key, mixed $value, string $adminUserId = ''): bool
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

        return isSuccessful($patchResponse);
    }
}

if (!function_exists('recruitmentSaveEligibilityRequirement')) {
    function recruitmentSaveEligibilityRequirement(
        string $supabaseUrl,
        array $headers,
        string $adminUserId,
        string $positionId,
        string $scope,
        string $requirement
    ): void {
        $stored = recruitmentReadSettingValue($supabaseUrl, $headers, 'recruitment.eligibility_requirements');
        $settings = is_array($stored) ? $stored : [];

        $policyDefault = trim((string)($settings['policy_default'] ?? 'career service sub professional'));
        $positionOverrides = is_array($settings['position_overrides'] ?? null) ? $settings['position_overrides'] : [];

        $normalizedPositionId = strtolower(trim($positionId));
        if ($scope === 'position' && $normalizedPositionId !== '') {
            $positionOverrides[$normalizedPositionId] = $requirement;
        } elseif ($scope === 'policy') {
            $policyDefault = $requirement;
            if ($normalizedPositionId !== '') {
                unset($positionOverrides[$normalizedPositionId]);
            }
        }

        $payload = [
            'policy_default' => $policyDefault,
            'position_overrides' => $positionOverrides,
            'updated_at' => gmdate('c'),
        ];

        recruitmentUpsertSettingValue($supabaseUrl, $headers, 'recruitment.eligibility_requirements', $payload, $adminUserId);
    }
}

if (!function_exists('recruitmentNormalizeEligibilityRequirement')) {
    function recruitmentNormalizeEligibilityRequirement(string $rawValue): string
    {
        $value = trim($rawValue);
        if ($value === '') {
            return '';
        }

        $normalized = str_replace(['/', '|', ';'], ',', $value);
        $parts = preg_split('/\s*,\s*/', $normalized) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $token = trim((string)$part);
            if ($token === '') {
                continue;
            }
            $tokens[strtolower($token)] = $token;
        }

        return implode(', ', array_values($tokens));
    }
}

if (!function_exists('recruitmentResolveEligibilityOption')) {
    function recruitmentResolveEligibilityOption(string $rawValue): string
    {
        $key = strtolower(trim($rawValue));
        return match ($key) {
            'none', 'not_applicable', 'not applicable', 'n/a', 'na' => 'none',
            'csc', 'career service', 'career service sub professional' => 'csc',
            'prc' => 'prc',
            'csc_prc', 'csc,prc', 'csc, prc', 'csc/prc' => 'csc_prc',
            default => 'csc_prc',
        };
    }
}

if (!function_exists('recruitmentEligibilityOptionToRequirement')) {
    function recruitmentEligibilityOptionToRequirement(string $option): string
    {
        return match (recruitmentResolveEligibilityOption($option)) {
            'none' => 'none',
            'csc' => 'csc',
            'prc' => 'prc',
            default => 'csc, prc',
        };
    }
}

if (!function_exists('recruitmentNormalizeEducationLevel')) {
    function recruitmentNormalizeEducationLevel(string $rawValue): string
    {
        $key = strtolower(trim($rawValue));

        return match ($key) {
            'elementary' => 'elementary',
            'secondary', 'highschool', 'high_school', 'high-school' => 'secondary',
            'vocational', 'trade', 'trade_course', 'vocational_trade_course', 'vocational/trade course', 'tvet' => 'vocational',
            'college', 'bachelor', 'bachelors', "bachelor's", "bachelor's degree" => 'college',
            'graduate', 'graduate_studies', 'masters', 'masteral', 'doctorate', 'phd' => 'graduate',
            default => 'college',
        };
    }
}

if (!function_exists('recruitmentEducationLevelToYears')) {
    function recruitmentEducationLevelToYears(string $educationLevel): float
    {
        return match (recruitmentNormalizeEducationLevel($educationLevel)) {
            'graduate' => 6.0,
            'college' => 4.0,
            'vocational' => 2.0,
            'secondary' => 1.0,
            default => 0.0,
        };
    }
}

if (!function_exists('recruitmentEducationYearsToLevel')) {
    function recruitmentEducationYearsToLevel(float $educationYears): string
    {
        if ($educationYears >= 6) {
            return 'graduate';
        }
        if ($educationYears >= 4) {
            return 'college';
        }
        if ($educationYears >= 2) {
            return 'vocational';
        }
        if ($educationYears >= 1) {
            return 'secondary';
        }

        return 'elementary';
    }
}

if (!function_exists('recruitmentSavePositionCriteria')) {
    function recruitmentSavePositionCriteria(
        string $supabaseUrl,
        array $headers,
        string $adminUserId,
        string $positionId,
        string $eligibilityOption,
        string $educationLevel,
        float $trainingHours,
        float $experienceYears
    ): void {
        if (!recruitmentIsValidUuid($positionId)) {
            return;
        }

        $stored = recruitmentReadSettingValue($supabaseUrl, $headers, 'recruitment.position_criteria');
        $settings = is_array($stored) ? $stored : [];
        $positionOverrides = is_array($settings['position_overrides'] ?? null) ? (array)$settings['position_overrides'] : [];

        $normalizedPositionId = strtolower(trim($positionId));
        $normalizedEducationLevel = recruitmentNormalizeEducationLevel($educationLevel);
        $positionOverrides[$normalizedPositionId] = [
            'eligibility' => recruitmentResolveEligibilityOption($eligibilityOption),
            'minimum_education_level' => $normalizedEducationLevel,
            'minimum_education_years' => recruitmentEducationLevelToYears($normalizedEducationLevel),
            'minimum_training_hours' => max(0, $trainingHours),
            'minimum_experience_years' => max(0, $experienceYears),
            'updated_at' => gmdate('c'),
        ];

        $payload = [
            'position_overrides' => $positionOverrides,
            'updated_at' => gmdate('c'),
        ];

        recruitmentUpsertSettingValue($supabaseUrl, $headers, 'recruitment.position_criteria', $payload, $adminUserId);
    }
}

if (!function_exists('recruitmentDefaultEmailTemplates')) {
    function recruitmentDefaultEmailTemplates(): array
    {
        return [
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
                'body' => 'Hello {applicant_name},<br><br>Your application for <strong>{job_title}</strong> has moved to the next stage.<br>Reference: <strong>{application_ref_no}</strong><br><br>Remarks: {remarks}<br><br>Please monitor your account and email for final review schedule and office signing instructions.',
            ],
        ];
    }
}

if ($action === 'save_recruitment_email_templates') {
    $defaults = recruitmentDefaultEmailTemplates();
    $submittedSubject = trim((string)(cleanText($_POST['submitted_subject'] ?? null) ?? $defaults['submitted']['subject']));
    $submittedBody = trim((string)(cleanText($_POST['submitted_body'] ?? null) ?? $defaults['submitted']['body']));
    $passedSubject = trim((string)(cleanText($_POST['passed_subject'] ?? null) ?? $defaults['passed']['subject']));
    $passedBody = trim((string)(cleanText($_POST['passed_body'] ?? null) ?? $defaults['passed']['body']));
    $failedSubject = trim((string)(cleanText($_POST['failed_subject'] ?? null) ?? $defaults['failed']['subject']));
    $failedBody = trim((string)(cleanText($_POST['failed_body'] ?? null) ?? $defaults['failed']['body']));
    $nextStageSubject = trim((string)(cleanText($_POST['next_stage_subject'] ?? null) ?? $defaults['next_stage']['subject']));
    $nextStageBody = trim((string)(cleanText($_POST['next_stage_body'] ?? null) ?? $defaults['next_stage']['body']));

    $payload = [
        'submitted' => ['subject' => $submittedSubject, 'body' => $submittedBody],
        'passed' => ['subject' => $passedSubject, 'body' => $passedBody],
        'failed' => ['subject' => $failedSubject, 'body' => $failedBody],
        'next_stage' => ['subject' => $nextStageSubject, 'body' => $nextStageBody],
        'updated_at' => gmdate('c'),
    ];

    $saved = recruitmentUpsertSettingValue($supabaseUrl, $headers, 'recruitment.email_templates', $payload, $adminUserId);
    if (!$saved) {
        redirectWithState('error', 'Failed to save recruitment email templates.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'recruitment',
            'entity_name' => 'system_settings',
            'entity_id' => null,
            'action_name' => 'save_recruitment_email_templates',
            'old_data' => null,
            'new_data' => ['templates' => ['submitted', 'passed', 'failed', 'next_stage']],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Recruitment email templates saved successfully.');
}

$allowedRequirementKeys = [
    'application_letter' => 'Application Letter',
    'updated_resume_cv' => 'Updated Resume/CV',
    'personal_data_sheet' => 'Personal Data Sheet',
    'valid_government_id' => 'Valid Government ID',
    'transcript_of_records' => 'Transcript of Records',
];

$resolveEmploymentType = static function (string $positionId) use ($supabaseUrl, $headers): ?string {
    if (!recruitmentIsValidUuid($positionId)) {
        return null;
    }

    $positionResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/job_positions?select=id,employment_classification&id=eq.' . rawurlencode($positionId)
        . '&limit=1',
        $headers
    );

    if (!isSuccessful($positionResponse) || !is_array($positionResponse['data'][0] ?? null)) {
        return null;
    }

    $classification = strtolower((string)(cleanText($positionResponse['data'][0]['employment_classification'] ?? null) ?? ''));
    return in_array($classification, ['regular', 'coterminous'], true)
        ? 'permanent'
        : 'contractual';
};

$resolveEmploymentClassification = static function (string $employmentType): ?string {
    return match (strtolower(trim($employmentType))) {
        'permanent' => 'regular',
        'contractual' => 'contractual',
        default => null,
    };
};

$findExistingPositionId = static function (string $positionTitle, string $employmentClassification) use ($supabaseUrl, $headers): ?string {
    $normalizedTitle = strtolower(trim($positionTitle));
    if ($normalizedTitle === '' || $employmentClassification === '') {
        return null;
    }

    $response = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/job_positions?select=id,position_title,employment_classification'
        . '&employment_classification=eq.' . rawurlencode($employmentClassification)
        . '&limit=1000',
        $headers
    );

    if (!isSuccessful($response)) {
        return null;
    }

    foreach ((array)($response['data'] ?? []) as $row) {
        $title = strtolower(trim((string)($row['position_title'] ?? '')));
        if ($title === $normalizedTitle) {
            $id = trim((string)($row['id'] ?? ''));
            return recruitmentIsValidUuid($id) ? $id : null;
        }
    }

    return null;
};

$createJobPosition = static function (string $positionTitle, string $employmentClassification) use ($supabaseUrl, $headers): ?string {
    $normalizedTitle = trim($positionTitle);
    if ($normalizedTitle === '' || $employmentClassification === '') {
        return null;
    }

    $prefixSource = strtoupper(preg_replace('/[^A-Z0-9]+/', ' ', strtoupper($normalizedTitle)) ?? '');
    $tokens = array_values(array_filter(explode(' ', $prefixSource), static fn(string $token): bool => $token !== ''));
    $prefix = '';
    foreach ($tokens as $token) {
        $prefix .= substr($token, 0, 1);
        if (strlen($prefix) >= 4) {
            break;
        }
    }
    if ($prefix === '') {
        $prefix = 'POS';
    }

    try {
        $suffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    } catch (Throwable) {
        $suffix = strtoupper(substr(md5($normalizedTitle . microtime(true)), 0, 6));
    }

    $positionCode = $prefix . '-' . $suffix;

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/job_positions',
        array_merge($headers, ['Prefer: return=representation']),
        [[
            'position_code' => $positionCode,
            'position_title' => $normalizedTitle,
            'employment_classification' => $employmentClassification,
            'is_active' => true,
        ]]
    );

    if (!isSuccessful($insertResponse)) {
        return null;
    }

    $createdId = trim((string)($insertResponse['data'][0]['id'] ?? ''));
    return recruitmentIsValidUuid($createdId) ? $createdId : null;
};

$isDuplicatePlantillaNumber = static function (string $plantillaItemNo, string $excludePostingId = '') use ($supabaseUrl, $headers): ?bool {
    $plantillaValue = trim($plantillaItemNo);
    if ($plantillaValue === '') {
        return false;
    }

    $url = $supabaseUrl
        . '/rest/v1/job_postings?select=id&plantilla_item_no=eq.' . rawurlencode($plantillaValue)
        . '&limit=1';

    if ($excludePostingId !== '' && recruitmentIsValidUuid($excludePostingId)) {
        $url .= '&id=neq.' . rawurlencode($excludePostingId);
    }

    $response = apiRequest('GET', $url, $headers);
    if (!isSuccessful($response)) {
        return null;
    }

    return !empty((array)($response['data'] ?? []));
};

if ($action === 'create_job_posting') {
    $title = cleanText($_POST['title'] ?? null) ?? '';
    $officeId = cleanText($_POST['office_id'] ?? null) ?? '';
    $positionId = cleanText($_POST['position_id'] ?? null) ?? '';
    $newPositionTitle = trim((string)(cleanText($_POST['new_position_title'] ?? null) ?? ''));
    $description = cleanText($_POST['description'] ?? null) ?? '';
    $qualifications = cleanText($_POST['qualifications'] ?? null);
    $responsibilities = cleanText($_POST['responsibilities'] ?? null);
    $employmentType = strtolower((string)(cleanText($_POST['employment_type'] ?? null) ?? ''));
    $plantillaItemNo = trim((string)(cleanText($_POST['plantilla_item_no'] ?? null) ?? ''));
    $requiredDocumentsRaw = $_POST['required_documents'] ?? [];
    $criteriaEligibilityRequiredRaw = (string)(cleanText($_POST['criteria_eligibility_required'] ?? null) ?? '0');
    $criteriaEligibility = $criteriaEligibilityRequiredRaw === '1' ? 'csc_prc' : 'none';
    $criteriaEducationLevelRaw = (string)(cleanText($_POST['criteria_education_level'] ?? null) ?? '');
    $criteriaEducationYearsLegacy = (float)(cleanText($_POST['criteria_education_years'] ?? null) ?? 4);
    $criteriaEducationLevel = $criteriaEducationLevelRaw !== ''
        ? recruitmentNormalizeEducationLevel($criteriaEducationLevelRaw)
        : recruitmentEducationYearsToLevel($criteriaEducationYearsLegacy);
    $criteriaTrainingHours = (float)(cleanText($_POST['criteria_training_hours'] ?? null) ?? 4);
    $criteriaExperienceYears = (float)(cleanText($_POST['criteria_experience_years'] ?? null) ?? 1);
    $openDate = cleanText($_POST['open_date'] ?? null) ?? '';
    $closeDate = cleanText($_POST['close_date'] ?? null) ?? '';
    $postingStatus = strtolower((string)(cleanText($_POST['posting_status'] ?? null) ?? 'draft'));

    if ($title === '' || $description === '' || $officeId === '' || $openDate === '' || $closeDate === '' || $plantillaItemNo === '') {
        redirectWithState('error', 'Title, office, plantilla number, description, open date, and close date are required.');
    }

    if (!recruitmentIsValidUuid($officeId)) {
        redirectWithState('error', 'Selected office is invalid.');
    }

    if (!in_array($employmentType, ['permanent', 'contractual'], true)) {
        redirectWithState('error', 'Please select a valid employment type.');
    }

    if ($positionId === '' && $newPositionTitle === '') {
        redirectWithState('error', 'Select a predefined position or enter a new position title.');
    }

    if ($newPositionTitle !== '') {
        $employmentClassification = $resolveEmploymentClassification($employmentType);
        if ($employmentClassification === null) {
            redirectWithState('error', 'Unable to resolve employment classification for the selected employment type.');
        }

        $existingPositionId = $findExistingPositionId($newPositionTitle, $employmentClassification);
        if ($existingPositionId !== null) {
            $positionId = $existingPositionId;
        } else {
            $createdPositionId = $createJobPosition($newPositionTitle, $employmentClassification);
            if ($createdPositionId === null) {
                redirectWithState('error', 'Failed to create the new position. Please try again.');
            }
            $positionId = $createdPositionId;
        }
    }

    if (!recruitmentIsValidUuid($positionId)) {
        redirectWithState('error', 'Selected position is invalid.');
    }

    $positionEmploymentType = $resolveEmploymentType($positionId);
    if ($positionEmploymentType === null) {
        redirectWithState('error', 'Selected position was not found.');
    }
    if ($positionEmploymentType !== $employmentType) {
        redirectWithState('error', 'Selected position employment type does not match your chosen employment type.');
    }

    $requiredDocumentKeys = [];
    if (is_array($requiredDocumentsRaw)) {
        foreach ($requiredDocumentsRaw as $requirementKey) {
            $key = strtolower(trim((string)$requirementKey));
            if ($key === '' || !isset($allowedRequirementKeys[$key])) {
                continue;
            }
            $requiredDocumentKeys[$key] = $allowedRequirementKeys[$key];
        }
    }
    if (empty($requiredDocumentKeys)) {
        $requiredDocumentKeys = $allowedRequirementKeys;
    }

    if (!in_array($postingStatus, ['draft', 'published', 'closed'], true)) {
        $postingStatus = 'draft';
    }

    if ($criteriaTrainingHours < 0 || $criteriaExperienceYears < 0) {
        redirectWithState('error', 'Qualification criteria values cannot be negative.');
    }

    $eligibilityRequirement = recruitmentNormalizeEligibilityRequirement(
        recruitmentEligibilityOptionToRequirement($criteriaEligibility)
    );

    if (strtotime($closeDate) < strtotime($openDate)) {
        redirectWithState('error', 'Close date must be on or after open date.');
    }

    $plantillaExists = $isDuplicatePlantillaNumber($plantillaItemNo);
    if ($plantillaExists === null) {
        redirectWithState('error', 'Unable to validate Plantilla Number uniqueness. Please try again.');
    }
    if ($plantillaExists) {
        redirectWithState('error', 'Plantilla Number already exists. Please use a unique value.');
    }

    $insertPayload = [[
        'office_id' => $officeId,
        'position_id' => $positionId,
        'title' => $title,
        'plantilla_item_no' => $plantillaItemNo,
        'description' => $description,
        'qualifications' => $qualifications,
        'responsibilities' => $responsibilities,
        'required_documents' => array_values($requiredDocumentKeys),
        'posting_status' => $postingStatus,
        'open_date' => $openDate,
        'close_date' => $closeDate,
        'published_by' => $adminUserId !== '' ? $adminUserId : null,
    ]];

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/job_postings',
        array_merge($headers, ['Prefer: return=representation']),
        $insertPayload
    );

    if (!isSuccessful($insertResponse)) {
        redirectWithState('error', 'Failed to create job posting.');
    }

    $createdPostingId = (string)($insertResponse['data'][0]['id'] ?? '');

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'recruitment',
            'entity_name' => 'job_postings',
            'entity_id' => $createdPostingId !== '' ? $createdPostingId : null,
            'action_name' => 'create_job_posting',
            'old_data' => null,
            'new_data' => $insertPayload[0],
            'ip_address' => clientIp(),
        ]]
    );

    recruitmentSaveEligibilityRequirement(
        $supabaseUrl,
        $headers,
        $adminUserId,
        $positionId,
        'position',
        $eligibilityRequirement
    );

    recruitmentSavePositionCriteria(
        $supabaseUrl,
        $headers,
        $adminUserId,
        $positionId,
        $criteriaEligibility,
        $criteriaEducationLevel,
        $criteriaTrainingHours,
        $criteriaExperienceYears
    );

    redirectWithState('success', 'Job posting created successfully.');
}

if ($action === 'edit_job_posting') {
    $postingId = cleanText($_POST['posting_id'] ?? null) ?? '';
    $title = cleanText($_POST['title'] ?? null) ?? '';
    $officeId = cleanText($_POST['office_id'] ?? null) ?? '';
    $positionId = cleanText($_POST['position_id'] ?? null) ?? '';
    $newPositionTitle = trim((string)(cleanText($_POST['new_position_title'] ?? null) ?? ''));
    $description = cleanText($_POST['description'] ?? null) ?? '';
    $qualifications = cleanText($_POST['qualifications'] ?? null);
    $responsibilities = cleanText($_POST['responsibilities'] ?? null);
    $employmentType = strtolower((string)(cleanText($_POST['employment_type'] ?? null) ?? ''));
    $plantillaItemNo = trim((string)(cleanText($_POST['plantilla_item_no'] ?? null) ?? ''));
    $requiredDocumentsRaw = $_POST['required_documents'] ?? [];
    $criteriaEligibilityRequiredRaw = (string)(cleanText($_POST['criteria_eligibility_required'] ?? null) ?? '0');
    $criteriaEligibility = $criteriaEligibilityRequiredRaw === '1' ? 'csc_prc' : 'none';
    $criteriaEducationLevelRaw = (string)(cleanText($_POST['criteria_education_level'] ?? null) ?? '');
    $criteriaEducationYearsLegacy = (float)(cleanText($_POST['criteria_education_years'] ?? null) ?? 4);
    $criteriaEducationLevel = $criteriaEducationLevelRaw !== ''
        ? recruitmentNormalizeEducationLevel($criteriaEducationLevelRaw)
        : recruitmentEducationYearsToLevel($criteriaEducationYearsLegacy);
    $criteriaTrainingHours = (float)(cleanText($_POST['criteria_training_hours'] ?? null) ?? 4);
    $criteriaExperienceYears = (float)(cleanText($_POST['criteria_experience_years'] ?? null) ?? 1);
    $openDate = cleanText($_POST['open_date'] ?? null) ?? '';
    $closeDate = cleanText($_POST['close_date'] ?? null) ?? '';
    $postingStatus = strtolower((string)(cleanText($_POST['posting_status'] ?? null) ?? 'draft'));

    if (!recruitmentIsValidUuid($postingId)) {
        redirectWithState('error', 'Invalid job posting selected.');
    }

    if ($title === '' || $description === '' || $officeId === '' || $openDate === '' || $closeDate === '' || $plantillaItemNo === '') {
        redirectWithState('error', 'Title, office, plantilla number, description, open date, and close date are required.');
    }

    if (!recruitmentIsValidUuid($officeId)) {
        redirectWithState('error', 'Selected office is invalid.');
    }

    if (!in_array($employmentType, ['permanent', 'contractual'], true)) {
        redirectWithState('error', 'Please select a valid employment type.');
    }

    if ($positionId === '' && $newPositionTitle === '') {
        redirectWithState('error', 'Select a predefined position or enter a new position title.');
    }

    if ($newPositionTitle !== '') {
        $employmentClassification = $resolveEmploymentClassification($employmentType);
        if ($employmentClassification === null) {
            redirectWithState('error', 'Unable to resolve employment classification for the selected employment type.');
        }

        $existingPositionId = $findExistingPositionId($newPositionTitle, $employmentClassification);
        if ($existingPositionId !== null) {
            $positionId = $existingPositionId;
        } else {
            $createdPositionId = $createJobPosition($newPositionTitle, $employmentClassification);
            if ($createdPositionId === null) {
                redirectWithState('error', 'Failed to create the new position. Please try again.');
            }
            $positionId = $createdPositionId;
        }
    }

    if (!recruitmentIsValidUuid($positionId)) {
        redirectWithState('error', 'Selected position is invalid.');
    }

    $positionEmploymentType = $resolveEmploymentType($positionId);
    if ($positionEmploymentType === null) {
        redirectWithState('error', 'Selected position was not found.');
    }
    if ($positionEmploymentType !== $employmentType) {
        redirectWithState('error', 'Selected position employment type does not match your chosen employment type.');
    }

    if (!in_array($postingStatus, ['draft', 'published', 'closed', 'archived'], true)) {
        $postingStatus = 'draft';
    }

    if ($criteriaTrainingHours < 0 || $criteriaExperienceYears < 0) {
        redirectWithState('error', 'Qualification criteria values cannot be negative.');
    }

    $requiredDocumentKeys = [];
    if (is_array($requiredDocumentsRaw)) {
        foreach ($requiredDocumentsRaw as $requirementKey) {
            $key = strtolower(trim((string)$requirementKey));
            if ($key === '' || !isset($allowedRequirementKeys[$key])) {
                continue;
            }
            $requiredDocumentKeys[$key] = $allowedRequirementKeys[$key];
        }
    }
    if (empty($requiredDocumentKeys)) {
        $requiredDocumentKeys = $allowedRequirementKeys;
    }

    $eligibilityRequirement = recruitmentNormalizeEligibilityRequirement(
        recruitmentEligibilityOptionToRequirement($criteriaEligibility)
    );

    if (strtotime($closeDate) < strtotime($openDate)) {
        redirectWithState('error', 'Close date must be on or after open date.');
    }

    $plantillaExists = $isDuplicatePlantillaNumber($plantillaItemNo, $postingId);
    if ($plantillaExists === null) {
        redirectWithState('error', 'Unable to validate Plantilla Number uniqueness. Please try again.');
    }
    if ($plantillaExists) {
        redirectWithState('error', 'Plantilla Number already exists. Please use a unique value.');
    }

    $postingResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/job_postings?select=id,title,office_id,position_id,plantilla_item_no,description,qualifications,responsibilities,posting_status,open_date,close_date&id=eq.' . $postingId . '&limit=1',
        $headers
    );

    $postingRow = $postingResponse['data'][0] ?? null;
    if (!is_array($postingRow)) {
        redirectWithState('error', 'Job posting record not found.');
    }

    $patchPayload = [
        'title' => $title,
        'office_id' => $officeId,
        'position_id' => $positionId,
        'plantilla_item_no' => $plantillaItemNo,
        'description' => $description,
        'qualifications' => $qualifications,
        'responsibilities' => $responsibilities,
        'required_documents' => array_values($requiredDocumentKeys),
        'posting_status' => $postingStatus,
        'open_date' => $openDate,
        'close_date' => $closeDate,
        'updated_at' => gmdate('c'),
    ];

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/job_postings?id=eq.' . $postingId,
        array_merge($headers, ['Prefer: return=minimal']),
        $patchPayload
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update job posting.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'recruitment',
            'entity_name' => 'job_postings',
            'entity_id' => $postingId,
            'action_name' => 'edit_job_posting',
            'old_data' => [
                'title' => (string)($postingRow['title'] ?? ''),
                'office_id' => (string)($postingRow['office_id'] ?? ''),
                'position_id' => (string)($postingRow['position_id'] ?? ''),
                'plantilla_item_no' => (string)($postingRow['plantilla_item_no'] ?? ''),
                'description' => (string)($postingRow['description'] ?? ''),
                'posting_status' => (string)($postingRow['posting_status'] ?? ''),
                'open_date' => (string)($postingRow['open_date'] ?? ''),
                'close_date' => (string)($postingRow['close_date'] ?? ''),
            ],
            'new_data' => $patchPayload,
            'ip_address' => clientIp(),
        ]]
    );

    recruitmentSaveEligibilityRequirement(
        $supabaseUrl,
        $headers,
        $adminUserId,
        $positionId,
        'position',
        $eligibilityRequirement
    );

    recruitmentSavePositionCriteria(
        $supabaseUrl,
        $headers,
        $adminUserId,
        $positionId,
        $criteriaEligibility,
        $criteriaEducationLevel,
        $criteriaTrainingHours,
        $criteriaExperienceYears
    );

    redirectWithState('success', 'Job posting updated successfully.');
}

if ($action === 'archive_job_posting') {
    $postingId = cleanText($_POST['posting_id'] ?? null) ?? '';

    if (!recruitmentIsValidUuid($postingId)) {
        redirectWithState('error', 'Invalid job posting selected.');
    }

    $postingResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/job_postings?select=id,title,posting_status&id=eq.' . $postingId . '&limit=1',
        $headers
    );

    $postingRow = $postingResponse['data'][0] ?? null;
    if (!is_array($postingRow)) {
        redirectWithState('error', 'Job posting record not found.');
    }

    if (strtolower((string)($postingRow['posting_status'] ?? '')) === 'archived') {
        redirectWithState('success', 'Job posting is already archived.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/job_postings?id=eq.' . $postingId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'posting_status' => 'archived',
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to archive job posting.');
    }

    logStatusTransition(
        $supabaseUrl,
        $headers,
        $adminUserId,
        'recruitment',
        'job_postings',
        $postingId,
        'archive_job_posting',
        (string)($postingRow['posting_status'] ?? ''),
        'archived'
    );

    redirectWithState('success', 'Job posting archived successfully.');
}

if ($action !== '') {
    redirectWithState('error', 'Unknown recruitment action.');
}
