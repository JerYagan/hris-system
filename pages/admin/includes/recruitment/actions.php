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

if (!function_exists('recruitmentSavePositionCriteria')) {
    function recruitmentSavePositionCriteria(
        string $supabaseUrl,
        array $headers,
        string $adminUserId,
        string $positionId,
        string $eligibilityOption,
        float $educationYears,
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
        $positionOverrides[$normalizedPositionId] = [
            'eligibility' => recruitmentResolveEligibilityOption($eligibilityOption),
            'minimum_education_years' => max(0, $educationYears),
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
    'pds' => 'PDS',
    'wes' => 'WES',
    'eligibility_csc_prc' => 'Eligibility (CSC/PRC)',
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
    $description = cleanText($_POST['description'] ?? null) ?? '';
    $qualifications = cleanText($_POST['qualifications'] ?? null);
    $responsibilities = cleanText($_POST['responsibilities'] ?? null);
    $employmentType = strtolower((string)(cleanText($_POST['employment_type'] ?? null) ?? ''));
    $plantillaItemNo = trim((string)(cleanText($_POST['plantilla_item_no'] ?? null) ?? ''));
    $requiredDocumentsRaw = $_POST['required_documents'] ?? [];
    $criteriaEligibility = recruitmentResolveEligibilityOption((string)(cleanText($_POST['criteria_eligibility'] ?? null) ?? 'csc_prc'));
    $criteriaEducationYears = (float)(cleanText($_POST['criteria_education_years'] ?? null) ?? 2);
    $criteriaTrainingHours = (float)(cleanText($_POST['criteria_training_hours'] ?? null) ?? 4);
    $criteriaExperienceYears = (float)(cleanText($_POST['criteria_experience_years'] ?? null) ?? 1);
    $openDate = cleanText($_POST['open_date'] ?? null) ?? '';
    $closeDate = cleanText($_POST['close_date'] ?? null) ?? '';
    $postingStatus = strtolower((string)(cleanText($_POST['posting_status'] ?? null) ?? 'draft'));

    if ($title === '' || $description === '' || $officeId === '' || $positionId === '' || $openDate === '' || $closeDate === '' || $plantillaItemNo === '') {
        redirectWithState('error', 'Title, office, position, plantilla number, description, open date, and close date are required.');
    }

    if (!recruitmentIsValidUuid($officeId) || !recruitmentIsValidUuid($positionId)) {
        redirectWithState('error', 'Selected office or position is invalid.');
    }

    if (!in_array($employmentType, ['permanent', 'contractual'], true)) {
        redirectWithState('error', 'Please select a valid employment type.');
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

    if ($criteriaEducationYears < 0 || $criteriaTrainingHours < 0 || $criteriaExperienceYears < 0) {
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
        $criteriaEducationYears,
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
    $description = cleanText($_POST['description'] ?? null) ?? '';
    $qualifications = cleanText($_POST['qualifications'] ?? null);
    $responsibilities = cleanText($_POST['responsibilities'] ?? null);
    $employmentType = strtolower((string)(cleanText($_POST['employment_type'] ?? null) ?? ''));
    $plantillaItemNo = trim((string)(cleanText($_POST['plantilla_item_no'] ?? null) ?? ''));
    $criteriaEligibility = recruitmentResolveEligibilityOption((string)(cleanText($_POST['criteria_eligibility'] ?? null) ?? 'csc_prc'));
    $criteriaEducationYears = (float)(cleanText($_POST['criteria_education_years'] ?? null) ?? 2);
    $criteriaTrainingHours = (float)(cleanText($_POST['criteria_training_hours'] ?? null) ?? 4);
    $criteriaExperienceYears = (float)(cleanText($_POST['criteria_experience_years'] ?? null) ?? 1);
    $openDate = cleanText($_POST['open_date'] ?? null) ?? '';
    $closeDate = cleanText($_POST['close_date'] ?? null) ?? '';
    $postingStatus = strtolower((string)(cleanText($_POST['posting_status'] ?? null) ?? 'draft'));

    if (!recruitmentIsValidUuid($postingId)) {
        redirectWithState('error', 'Invalid job posting selected.');
    }

    if ($title === '' || $description === '' || $officeId === '' || $positionId === '' || $openDate === '' || $closeDate === '' || $plantillaItemNo === '') {
        redirectWithState('error', 'Title, office, position, plantilla number, description, open date, and close date are required.');
    }

    if (!recruitmentIsValidUuid($officeId) || !recruitmentIsValidUuid($positionId)) {
        redirectWithState('error', 'Selected office or position is invalid.');
    }

    if (!in_array($employmentType, ['permanent', 'contractual'], true)) {
        redirectWithState('error', 'Please select a valid employment type.');
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

    if ($criteriaEducationYears < 0 || $criteriaTrainingHours < 0 || $criteriaExperienceYears < 0) {
        redirectWithState('error', 'Qualification criteria values cannot be negative.');
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
        $criteriaEducationYears,
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
