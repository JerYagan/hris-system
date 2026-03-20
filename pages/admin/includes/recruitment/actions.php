<?php

require_once dirname(__DIR__) . '/notifications/email.php';
require_once dirname(__DIR__, 3) . '/shared/lib/recruitment-domain.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (!function_exists('recruitmentIsValidUuid')) {
    function recruitmentIsValidUuid(string $value): bool
    {
        return recruitmentServiceIsValidUuid($value);
    }
}

$action = (string)($_POST['form_action'] ?? '');

if (!function_exists('recruitmentReadSettingValue')) {
    function recruitmentReadSettingValue(string $supabaseUrl, array $headers, string $key): mixed
    {
        return recruitmentServiceReadSettingValue($supabaseUrl, $headers, $key);
    }
}

if (!function_exists('recruitmentIsActiveOffice')) {
    function recruitmentIsActiveOffice(string $supabaseUrl, array $headers, string $officeId): bool
    {
        if (!recruitmentIsValidUuid($officeId)) {
            return false;
        }

        $response = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/offices?select=id&id=eq.' . rawurlencode($officeId) . '&is_active=eq.true&limit=1',
            $headers
        );

        return isSuccessful($response) && !empty((array)($response['data'] ?? []));
    }
}

if (!function_exists('recruitmentUpsertSettingValue')) {
    function recruitmentUpsertSettingValue(string $supabaseUrl, array $headers, string $key, mixed $value, string $adminUserId = ''): bool
    {
        return recruitmentServiceUpsertSettingValue($supabaseUrl, $headers, $key, $value, $adminUserId);
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
        recruitmentServiceSaveEligibilityRequirement($supabaseUrl, $headers, $adminUserId, $positionId, $scope, $requirement);
    }
}

if (!function_exists('recruitmentNormalizeEligibilityRequirement')) {
    function recruitmentNormalizeEligibilityRequirement(string $rawValue): string
    {
        return recruitmentServiceNormalizeEligibilityRequirement($rawValue);
    }
}

if (!function_exists('recruitmentResolveEligibilityOption')) {
    function recruitmentResolveEligibilityOption(string $rawValue): string
    {
        return recruitmentServiceResolveEligibilityOption($rawValue);
    }
}

if (!function_exists('recruitmentEligibilityOptionToRequirement')) {
    function recruitmentEligibilityOptionToRequirement(string $option): string
    {
        return recruitmentServiceEligibilityOptionToRequirement($option);
    }
}

if (!function_exists('recruitmentNormalizeEducationLevel')) {
    function recruitmentNormalizeEducationLevel(string $rawValue): string
    {
        return recruitmentServiceNormalizeEducationLevel($rawValue);
    }
}

if (!function_exists('recruitmentEducationLevelToYears')) {
    function recruitmentEducationLevelToYears(string $educationLevel): float
    {
        return recruitmentServiceEducationLevelToYears($educationLevel);
    }
}

if (!function_exists('recruitmentEducationYearsToLevel')) {
    function recruitmentEducationYearsToLevel(float $educationYears): string
    {
        return recruitmentServiceEducationYearsToLevel($educationYears);
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
        recruitmentServiceSavePositionCriteria(
            $supabaseUrl,
            $headers,
            $adminUserId,
            $positionId,
            $eligibilityOption,
            $educationLevel,
            $trainingHours,
            $experienceYears
        );
    }
}

if (!function_exists('recruitmentDefaultEmailTemplates')) {
    function recruitmentDefaultEmailTemplates(): array
    {
        return recruitmentServiceDefaultEmailTemplates();
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

if ($action === 'create_job_posting') {
    $input = recruitmentServiceCollectJobPostingInput($_POST, $allowedRequirementKeys);
    $prepared = recruitmentServicePrepareJobPostingPayload(
        $supabaseUrl,
        $headers,
        $input,
        '',
        false,
        $adminUserId
    );
    if (!(bool)($prepared['ok'] ?? false)) {
        redirectWithState('error', (string)($prepared['message'] ?? 'Unable to prepare job posting.'));
    }

    $result = recruitmentServiceCreateJobPosting($supabaseUrl, $headers, $adminUserId, $prepared);
    if (!(bool)($result['ok'] ?? false)) {
        redirectWithState('error', (string)($result['message'] ?? 'Failed to create job posting.'));
    }

    redirectWithState('success', (string)($result['message'] ?? 'Job posting created successfully.'));
}

if ($action === 'edit_job_posting') {
    $postingId = cleanText($_POST['posting_id'] ?? null) ?? '';

    if (!recruitmentIsValidUuid($postingId)) {
        redirectWithState('error', 'Invalid job posting selected.');
    }

    $input = recruitmentServiceCollectJobPostingInput($_POST, $allowedRequirementKeys);
    $prepared = recruitmentServicePrepareJobPostingPayload(
        $supabaseUrl,
        $headers,
        $input,
        $postingId,
        true,
        $adminUserId
    );
    if (!(bool)($prepared['ok'] ?? false)) {
        redirectWithState('error', (string)($prepared['message'] ?? 'Unable to prepare job posting update.'));
    }

    $result = recruitmentServiceUpdateJobPosting($supabaseUrl, $headers, $adminUserId, $postingId, $prepared);
    if (!(bool)($result['ok'] ?? false)) {
        redirectWithState('error', (string)($result['message'] ?? 'Failed to update job posting.'));
    }

    redirectWithState('success', (string)($result['message'] ?? 'Job posting updated successfully.'));
}

if ($action === 'archive_job_posting') {
    $postingId = cleanText($_POST['posting_id'] ?? null) ?? '';

    $result = recruitmentServiceArchiveJobPosting($supabaseUrl, $headers, $adminUserId, $postingId);
    $flashType = (bool)($result['ok'] ?? false) ? 'success' : 'error';
    redirectWithState($flashType, (string)($result['message'] ?? 'Unable to archive job posting.'));
}

if ($action !== '') {
    redirectWithState('error', 'Unknown recruitment action.');
}
