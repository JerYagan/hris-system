<?php

if (!function_exists('recruitmentServiceIsValidUuid')) {
    function recruitmentServiceIsValidUuid(string $value): bool
    {
        return (bool)preg_match('/^[a-f0-9-]{36}$/i', $value);
    }
}

if (!function_exists('recruitmentServiceReadSettingValue')) {
    function recruitmentServiceReadSettingValue(string $supabaseUrl, array $headers, string $key): mixed
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

if (!function_exists('recruitmentServiceUpsertSettingValue')) {
    function recruitmentServiceUpsertSettingValue(string $supabaseUrl, array $headers, string $key, mixed $value, string $actorUserId = ''): bool
    {
        $payload = [[
            'setting_key' => $key,
            'setting_value' => ['value' => $value],
            'updated_by' => $actorUserId !== '' ? $actorUserId : null,
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
                'updated_by' => $actorUserId !== '' ? $actorUserId : null,
                'updated_at' => gmdate('c'),
            ]
        );

        return isSuccessful($patchResponse);
    }
}

if (!function_exists('recruitmentServiceNormalizeEligibilityRequirement')) {
    function recruitmentServiceNormalizeEligibilityRequirement(string $rawValue): string
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

if (!function_exists('recruitmentServiceResolveEligibilityOption')) {
    function recruitmentServiceResolveEligibilityOption(string $rawValue): string
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

if (!function_exists('recruitmentServiceEligibilityOptionToRequirement')) {
    function recruitmentServiceEligibilityOptionToRequirement(string $option): string
    {
        return match (recruitmentServiceResolveEligibilityOption($option)) {
            'none' => 'none',
            'csc' => 'csc',
            'prc' => 'prc',
            default => 'csc, prc',
        };
    }
}

if (!function_exists('recruitmentServiceFormatEligibilityRequirement')) {
    function recruitmentServiceFormatEligibilityRequirement(string $value): string
    {
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
    }
}

if (!function_exists('recruitmentServiceNormalizeEducationLevel')) {
    function recruitmentServiceNormalizeEducationLevel(string $rawValue): string
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

if (!function_exists('recruitmentServiceEducationLevelToYears')) {
    function recruitmentServiceEducationLevelToYears(string $educationLevel): float
    {
        return match (recruitmentServiceNormalizeEducationLevel($educationLevel)) {
            'graduate' => 6.0,
            'college' => 4.0,
            'vocational' => 2.0,
            'secondary' => 1.0,
            default => 0.0,
        };
    }
}

if (!function_exists('recruitmentServiceEducationYearsToLevel')) {
    function recruitmentServiceEducationYearsToLevel(float $educationYears): string
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

if (!function_exists('recruitmentServiceEducationLevelRank')) {
    function recruitmentServiceEducationLevelRank(string $value): int
    {
        return match (recruitmentServiceNormalizeEducationLevel($value)) {
            'graduate' => 5,
            'college' => 4,
            'vocational' => 3,
            'secondary' => 2,
            default => 1,
        };
    }
}

if (!function_exists('recruitmentServiceEducationLevelLabel')) {
    function recruitmentServiceEducationLevelLabel(string $value): string
    {
        return match (recruitmentServiceNormalizeEducationLevel($value)) {
            'graduate' => 'Graduate Studies',
            'college' => 'College',
            'vocational' => 'Vocational/Trade Course',
            'secondary' => 'Secondary',
            default => 'Elementary',
        };
    }
}

if (!function_exists('recruitmentServiceDefaultEmailTemplates')) {
    function recruitmentServiceDefaultEmailTemplates(): array
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
                'body' => 'Hello {applicant_name},<br><br>Your application for <strong>{job_title}</strong> has moved to the next stage.<br>Reference: <strong>{application_ref_no}</strong><br><br>Remarks: {remarks}<br><br>Please monitor your account and email for schedule details.',
            ],
        ];
    }
}

if (!function_exists('recruitmentServiceMergeEmailTemplates')) {
    function recruitmentServiceMergeEmailTemplates(array $configured): array
    {
        $templates = recruitmentServiceDefaultEmailTemplates();

        foreach (['submitted', 'passed', 'failed', 'next_stage'] as $templateKey) {
            $templateRow = is_array($configured[$templateKey] ?? null)
                ? (array)$configured[$templateKey]
                : [];
            $subject = trim((string)($templateRow['subject'] ?? ''));
            $body = trim((string)($templateRow['body'] ?? ''));
            if ($subject !== '') {
                $templates[$templateKey]['subject'] = $subject;
            }
            if ($body !== '') {
                $templates[$templateKey]['body'] = $body;
            }
        }

        return $templates;
    }
}

if (!function_exists('recruitmentServiceNormalizeEligibilityConfig')) {
    function recruitmentServiceNormalizeEligibilityConfig(array $raw, string $fallbackPolicyDefault): array
    {
        $config = [
            'policy_default' => trim($fallbackPolicyDefault) !== '' ? trim($fallbackPolicyDefault) : 'career service sub professional',
            'position_overrides' => [],
        ];

        $policyDefault = trim((string)($raw['policy_default'] ?? ''));
        if ($policyDefault !== '') {
            $config['policy_default'] = $policyDefault;
        }

        $positionOverrides = is_array($raw['position_overrides'] ?? null)
            ? (array)$raw['position_overrides']
            : [];

        foreach ($positionOverrides as $positionKey => $positionEligibility) {
            $normalizedKey = strtolower(trim((string)$positionKey));
            $normalizedValue = trim((string)$positionEligibility);
            if ($normalizedKey === '' || $normalizedValue === '') {
                continue;
            }
            $config['position_overrides'][$normalizedKey] = $normalizedValue;
        }

        return $config;
    }
}

if (!function_exists('recruitmentServiceNormalizePositionCriteriaConfig')) {
    function recruitmentServiceNormalizePositionCriteriaConfig(
        array $raw,
        float $requiredEducationYears,
        float $requiredTrainingHours,
        float $requiredExperienceYears
    ): array {
        $config = ['position_overrides' => []];
        $rawOverrides = is_array($raw['position_overrides'] ?? null)
            ? (array)$raw['position_overrides']
            : [];

        foreach ($rawOverrides as $positionKey => $criteriaRow) {
            $normalizedPositionKey = strtolower(trim((string)$positionKey));
            if ($normalizedPositionKey === '' || !is_array($criteriaRow)) {
                continue;
            }

            $eligibilityOption = recruitmentServiceResolveEligibilityOption((string)($criteriaRow['eligibility'] ?? 'csc_prc'));
            $minimumEducationYears = max(0, (float)($criteriaRow['minimum_education_years'] ?? $requiredEducationYears));
            $minimumEducationLevel = recruitmentServiceNormalizeEducationLevel(
                (string)($criteriaRow['minimum_education_level'] ?? recruitmentServiceEducationYearsToLevel($minimumEducationYears))
            );

            $config['position_overrides'][$normalizedPositionKey] = [
                'eligibility' => $eligibilityOption,
                'minimum_education_level' => $minimumEducationLevel,
                'minimum_education_years' => $minimumEducationYears,
                'minimum_training_hours' => max(0, (float)($criteriaRow['minimum_training_hours'] ?? $requiredTrainingHours)),
                'minimum_experience_years' => max(0, (float)($criteriaRow['minimum_experience_years'] ?? $requiredExperienceYears)),
            ];
        }

        return $config;
    }
}

if (!function_exists('recruitmentServiceResolvePostingCriteria')) {
    function recruitmentServiceResolvePostingCriteria(
        string $positionId,
        array $positionCriteriaConfig,
        array $eligibilityConfig,
        string $requiredEligibility,
        string $requiredEducationLevel,
        float $requiredEducationYears,
        float $requiredTrainingHours,
        float $requiredExperienceYears
    ): array {
        $normalizedPositionId = strtolower(trim($positionId));
        $legacyEligibilityRequirement = trim((string)($eligibilityConfig['position_overrides'][$normalizedPositionId] ?? ''));
        $legacyPolicyDefault = trim((string)($eligibilityConfig['policy_default'] ?? $requiredEligibility));
        $legacyEffective = $legacyEligibilityRequirement !== '' ? $legacyEligibilityRequirement : $legacyPolicyDefault;

        $override = is_array($positionCriteriaConfig['position_overrides'][$normalizedPositionId] ?? null)
            ? (array)$positionCriteriaConfig['position_overrides'][$normalizedPositionId]
            : [];

        $eligibilityOption = isset($override['eligibility'])
            ? recruitmentServiceResolveEligibilityOption((string)$override['eligibility'])
            : recruitmentServiceResolveEligibilityOption($legacyEffective);

        $resolvedEducationYears = isset($override['minimum_education_years'])
            ? max(0, (float)$override['minimum_education_years'])
            : $requiredEducationYears;
        $resolvedEducationLevel = isset($override['minimum_education_level'])
            ? recruitmentServiceNormalizeEducationLevel((string)$override['minimum_education_level'])
            : $requiredEducationLevel;

        return [
            'eligibility_scope' => isset($override['eligibility']) ? 'position' : ($legacyEligibilityRequirement !== '' ? 'position' : 'policy'),
            'eligibility_option' => $eligibilityOption,
            'eligibility_requirement' => recruitmentServiceEligibilityOptionToRequirement($eligibilityOption),
            'minimum_education_level' => $resolvedEducationLevel,
            'minimum_education_years' => $resolvedEducationYears,
            'minimum_training_hours' => isset($override['minimum_training_hours'])
                ? max(0, (float)$override['minimum_training_hours'])
                : $requiredTrainingHours,
            'minimum_experience_years' => isset($override['minimum_experience_years'])
                ? max(0, (float)$override['minimum_experience_years'])
                : $requiredExperienceYears,
        ];
    }
}

if (!function_exists('recruitmentServiceSaveEligibilityRequirement')) {
    function recruitmentServiceSaveEligibilityRequirement(
        string $supabaseUrl,
        array $headers,
        string $actorUserId,
        string $positionId,
        string $scope,
        string $requirement
    ): void {
        $stored = recruitmentServiceReadSettingValue($supabaseUrl, $headers, 'recruitment.eligibility_requirements');
        $settings = is_array($stored) ? $stored : [];
        $config = recruitmentServiceNormalizeEligibilityConfig($settings, 'career service sub professional');

        $normalizedPositionId = strtolower(trim($positionId));
        if ($scope === 'position' && $normalizedPositionId !== '') {
            $config['position_overrides'][$normalizedPositionId] = $requirement;
        } elseif ($scope === 'policy') {
            $config['policy_default'] = $requirement;
            if ($normalizedPositionId !== '') {
                unset($config['position_overrides'][$normalizedPositionId]);
            }
        }

        $payload = [
            'policy_default' => $config['policy_default'],
            'position_overrides' => $config['position_overrides'],
            'updated_at' => gmdate('c'),
        ];

        recruitmentServiceUpsertSettingValue($supabaseUrl, $headers, 'recruitment.eligibility_requirements', $payload, $actorUserId);
    }
}

if (!function_exists('recruitmentServiceSavePositionCriteria')) {
    function recruitmentServiceSavePositionCriteria(
        string $supabaseUrl,
        array $headers,
        string $actorUserId,
        string $positionId,
        string $eligibilityOption,
        string $educationLevel,
        float $trainingHours,
        float $experienceYears
    ): void {
        if (!recruitmentServiceIsValidUuid($positionId)) {
            return;
        }

        $stored = recruitmentServiceReadSettingValue($supabaseUrl, $headers, 'recruitment.position_criteria');
        $settings = is_array($stored) ? $stored : [];
        $config = recruitmentServiceNormalizePositionCriteriaConfig($settings, 4, 4, 1);

        $normalizedPositionId = strtolower(trim($positionId));
        $normalizedEducationLevel = recruitmentServiceNormalizeEducationLevel($educationLevel);
        $config['position_overrides'][$normalizedPositionId] = [
            'eligibility' => recruitmentServiceResolveEligibilityOption($eligibilityOption),
            'minimum_education_level' => $normalizedEducationLevel,
            'minimum_education_years' => recruitmentServiceEducationLevelToYears($normalizedEducationLevel),
            'minimum_training_hours' => max(0, $trainingHours),
            'minimum_experience_years' => max(0, $experienceYears),
            'updated_at' => gmdate('c'),
        ];

        $payload = [
            'position_overrides' => $config['position_overrides'],
            'updated_at' => gmdate('c'),
        ];

        recruitmentServiceUpsertSettingValue($supabaseUrl, $headers, 'recruitment.position_criteria', $payload, $actorUserId);
    }
}

if (!function_exists('recruitmentServiceNotify')) {
    function recruitmentServiceNotify(array $headers, string $supabaseUrl, string $recipientUserId, string $title, string $body, string $linkUrl = '/hris-system/pages/staff/recruitment.php'): void
    {
        if (!recruitmentServiceIsValidUuid($recipientUserId)) {
            return;
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $recipientUserId,
                'category' => 'recruitment',
                'title' => $title,
                'body' => $body,
                'link_url' => $linkUrl,
            ]]
        );
    }
}

if (!function_exists('recruitmentServiceSplitName')) {
    function recruitmentServiceSplitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $parts = array_values(array_filter($parts, static fn ($part): bool => $part !== ''));
        if (count($parts) === 0) {
            return ['first_name' => 'Applicant', 'surname' => 'User'];
        }
        if (count($parts) === 1) {
            return ['first_name' => $parts[0], 'surname' => 'User'];
        }

        return [
            'first_name' => $parts[0],
            'surname' => $parts[count($parts) - 1],
        ];
    }
}

if (!function_exists('recruitmentServiceLoadEmployeeIdPrefix')) {
    function recruitmentServiceLoadEmployeeIdPrefix(string $supabaseUrl, array $headers): string
    {
        $defaultPrefix = 'DA-EMP-';
        $stored = recruitmentServiceReadSettingValue($supabaseUrl, $headers, 'employee_id_prefix');
        $storedValue = is_scalar($stored) ? trim((string)$stored) : '';

        $prefix = strtoupper((string)preg_replace('/[^A-Z0-9-]+/i', '-', $storedValue));
        $prefix = preg_replace('/-+/', '-', $prefix ?? '') ?: '';
        $prefix = trim($prefix);
        if ($prefix === '') {
            $prefix = $defaultPrefix;
        }
        if (!str_ends_with($prefix, '-')) {
            $prefix .= '-';
        }

        return $prefix;
    }
}

if (!function_exists('recruitmentServiceGenerateEmployeeId')) {
    function recruitmentServiceGenerateEmployeeId(string $supabaseUrl, array $headers): string
    {
        $prefix = recruitmentServiceLoadEmployeeIdPrefix($supabaseUrl, $headers);
        $response = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/people?select=agency_employee_no&agency_employee_no=ilike.' . rawurlencode($prefix . '%') . '&limit=5000',
            $headers
        );

        $maxSequence = 0;
        $usedCodes = [];
        if (isSuccessful($response)) {
            foreach ((array)($response['data'] ?? []) as $row) {
                $code = trim((string)($row['agency_employee_no'] ?? ''));
                if ($code === '' || stripos($code, $prefix) !== 0) {
                    continue;
                }

                $usedCodes[strtolower($code)] = true;
                $suffix = substr($code, strlen($prefix));
                if ($suffix !== '' && ctype_digit($suffix)) {
                    $maxSequence = max($maxSequence, (int)$suffix);
                }
            }
        }

        $sequence = max(1, $maxSequence + 1);
        for ($attempt = 0; $attempt < 10000; $attempt++, $sequence++) {
            $candidate = $prefix . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);
            if (!isset($usedCodes[strtolower($candidate)])) {
                return $candidate;
            }
        }

        return $prefix . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }
}

if (!function_exists('recruitmentServiceResolveEmploymentType')) {
    function recruitmentServiceResolveEmploymentType(string $supabaseUrl, array $headers, string $positionId): ?string
    {
        if (!recruitmentServiceIsValidUuid($positionId)) {
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
    }
}

if (!function_exists('recruitmentServiceResolveEmploymentClassification')) {
    function recruitmentServiceResolveEmploymentClassification(string $employmentType): ?string
    {
        return match (strtolower(trim($employmentType))) {
            'permanent' => 'regular',
            'contractual' => 'contractual',
            default => null,
        };
    }
}

if (!function_exists('recruitmentServiceFindExistingPositionId')) {
    function recruitmentServiceFindExistingPositionId(string $supabaseUrl, array $headers, string $positionTitle, string $employmentClassification): ?string
    {
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
                return recruitmentServiceIsValidUuid($id) ? $id : null;
            }
        }

        return null;
    }
}

if (!function_exists('recruitmentServiceCreateJobPosition')) {
    function recruitmentServiceCreateJobPosition(string $supabaseUrl, array $headers, string $positionTitle, string $employmentClassification): ?string
    {
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
        return recruitmentServiceIsValidUuid($createdId) ? $createdId : null;
    }
}

if (!function_exists('recruitmentServiceHasDuplicatePlantillaNumber')) {
    function recruitmentServiceHasDuplicatePlantillaNumber(string $supabaseUrl, array $headers, string $plantillaItemNo, string $excludePostingId = ''): ?bool
    {
        $plantillaValue = trim($plantillaItemNo);
        if ($plantillaValue === '') {
            return false;
        }

        $url = $supabaseUrl
            . '/rest/v1/job_postings?select=id&plantilla_item_no=eq.' . rawurlencode($plantillaValue)
            . '&limit=1';

        if ($excludePostingId !== '' && recruitmentServiceIsValidUuid($excludePostingId)) {
            $url .= '&id=neq.' . rawurlencode($excludePostingId);
        }

        $response = apiRequest('GET', $url, $headers);
        if (!isSuccessful($response)) {
            return null;
        }

        return !empty((array)($response['data'] ?? []));
    }
}

if (!function_exists('recruitmentServiceNormalizeRequiredDocuments')) {
    function recruitmentServiceNormalizeRequiredDocuments(array $requiredDocumentsRaw, array $allowedRequirementKeys): array
    {
        $requiredDocumentKeys = [];
        foreach ($requiredDocumentsRaw as $requirementKey) {
            $key = strtolower(trim((string)$requirementKey));
            if ($key === '' || !isset($allowedRequirementKeys[$key])) {
                continue;
            }
            $requiredDocumentKeys[$key] = $allowedRequirementKeys[$key];
        }

        if (empty($requiredDocumentKeys)) {
            $requiredDocumentKeys = $allowedRequirementKeys;
        }

        return array_values($requiredDocumentKeys);
    }
}

if (!function_exists('recruitmentServiceCollectJobPostingInput')) {
    function recruitmentServiceCollectJobPostingInput(array $source, array $allowedRequirementKeys): array
    {
        $criteriaEligibilityRequiredRaw = (string)(cleanText($source['criteria_eligibility_required'] ?? null) ?? '0');
        $criteriaEligibility = $criteriaEligibilityRequiredRaw === '1' ? 'csc_prc' : 'none';
        $criteriaEducationLevelRaw = (string)(cleanText($source['criteria_education_level'] ?? null) ?? '');
        $criteriaEducationYearsLegacy = (float)(cleanText($source['criteria_education_years'] ?? null) ?? 4);

        return [
            'title' => cleanText($source['title'] ?? null) ?? '',
            'office_id' => cleanText($source['office_id'] ?? null) ?? '',
            'position_id' => cleanText($source['position_id'] ?? null) ?? '',
            'new_position_title' => trim((string)(cleanText($source['new_position_title'] ?? null) ?? '')),
            'description' => cleanText($source['description'] ?? null) ?? '',
            'qualifications' => cleanText($source['qualifications'] ?? null),
            'responsibilities' => cleanText($source['responsibilities'] ?? null),
            'employment_type' => strtolower((string)(cleanText($source['employment_type'] ?? null) ?? '')),
            'plantilla_item_no' => trim((string)(cleanText($source['plantilla_item_no'] ?? null) ?? '')),
            'required_documents' => recruitmentServiceNormalizeRequiredDocuments(
                is_array($source['required_documents'] ?? null) ? $source['required_documents'] : [],
                $allowedRequirementKeys
            ),
            'criteria_eligibility' => $criteriaEligibility,
            'criteria_education_level' => $criteriaEducationLevelRaw !== ''
                ? recruitmentServiceNormalizeEducationLevel($criteriaEducationLevelRaw)
                : recruitmentServiceEducationYearsToLevel($criteriaEducationYearsLegacy),
            'criteria_training_hours' => (float)(cleanText($source['criteria_training_hours'] ?? null) ?? 4),
            'criteria_experience_years' => (float)(cleanText($source['criteria_experience_years'] ?? null) ?? 1),
            'open_date' => cleanText($source['open_date'] ?? null) ?? '',
            'close_date' => cleanText($source['close_date'] ?? null) ?? '',
            'posting_status' => strtolower((string)(cleanText($source['posting_status'] ?? null) ?? 'draft')),
        ];
    }
}

if (!function_exists('recruitmentServicePrepareJobPostingPayload')) {
    function recruitmentServicePrepareJobPostingPayload(
        string $supabaseUrl,
        array $headers,
        array $input,
        string $excludePostingId = '',
        bool $allowArchivedStatus = false,
        string $actorUserId = ''
    ): array {
        $title = (string)($input['title'] ?? '');
        $officeId = (string)($input['office_id'] ?? '');
        $positionId = (string)($input['position_id'] ?? '');
        $newPositionTitle = trim((string)($input['new_position_title'] ?? ''));
        $description = (string)($input['description'] ?? '');
        $qualifications = $input['qualifications'] ?? null;
        $responsibilities = $input['responsibilities'] ?? null;
        $employmentType = strtolower((string)($input['employment_type'] ?? ''));
        $plantillaItemNo = trim((string)($input['plantilla_item_no'] ?? ''));
        $requiredDocuments = is_array($input['required_documents'] ?? null) ? array_values($input['required_documents']) : [];
        $criteriaEligibility = (string)($input['criteria_eligibility'] ?? 'none');
        $criteriaEducationLevel = (string)($input['criteria_education_level'] ?? 'college');
        $criteriaTrainingHours = (float)($input['criteria_training_hours'] ?? 4);
        $criteriaExperienceYears = (float)($input['criteria_experience_years'] ?? 1);
        $openDate = (string)($input['open_date'] ?? '');
        $closeDate = (string)($input['close_date'] ?? '');
        $postingStatus = strtolower((string)($input['posting_status'] ?? 'draft'));

        if ($title === '' || $description === '' || $officeId === '' || $openDate === '' || $closeDate === '' || $plantillaItemNo === '') {
            return ['ok' => false, 'message' => 'Title, division, plantilla number, description, open date, and close date are required.'];
        }

        if (!recruitmentServiceIsValidUuid($officeId)) {
            return ['ok' => false, 'message' => 'Selected division is invalid.'];
        }

        if (!recruitmentIsActiveOffice($supabaseUrl, $headers, $officeId)) {
            return ['ok' => false, 'message' => 'Selected division is unavailable or no longer active.'];
        }

        if (!in_array($employmentType, ['permanent', 'contractual'], true)) {
            return ['ok' => false, 'message' => 'Please select a valid employment type.'];
        }

        if ($positionId === '' && $newPositionTitle === '') {
            return ['ok' => false, 'message' => 'Select a predefined position or enter a new position title.'];
        }

        if ($newPositionTitle !== '') {
            $employmentClassification = recruitmentServiceResolveEmploymentClassification($employmentType);
            if ($employmentClassification === null) {
                return ['ok' => false, 'message' => 'Unable to resolve employment classification for the selected employment type.'];
            }

            $existingPositionId = recruitmentServiceFindExistingPositionId($supabaseUrl, $headers, $newPositionTitle, $employmentClassification);
            if ($existingPositionId !== null) {
                $positionId = $existingPositionId;
            } else {
                $createdPositionId = recruitmentServiceCreateJobPosition($supabaseUrl, $headers, $newPositionTitle, $employmentClassification);
                if ($createdPositionId === null) {
                    return ['ok' => false, 'message' => 'Failed to create the new position. Please try again.'];
                }
                $positionId = $createdPositionId;
            }
        }

        if (!recruitmentServiceIsValidUuid($positionId)) {
            return ['ok' => false, 'message' => 'Selected position is invalid.'];
        }

        $positionEmploymentType = recruitmentServiceResolveEmploymentType($supabaseUrl, $headers, $positionId);
        if ($positionEmploymentType === null) {
            return ['ok' => false, 'message' => 'Selected position was not found.'];
        }
        if ($positionEmploymentType !== $employmentType) {
            return ['ok' => false, 'message' => 'Selected position employment type does not match your chosen employment type.'];
        }

        $allowedStatuses = $allowArchivedStatus
            ? ['draft', 'published', 'closed', 'archived']
            : ['draft', 'published', 'closed'];
        if (!in_array($postingStatus, $allowedStatuses, true)) {
            $postingStatus = 'draft';
        }

        if ($criteriaTrainingHours < 0 || $criteriaExperienceYears < 0) {
            return ['ok' => false, 'message' => 'Qualification criteria values cannot be negative.'];
        }

        if (strtotime($closeDate) < strtotime($openDate)) {
            return ['ok' => false, 'message' => 'Close date must be on or after open date.'];
        }

        $plantillaExists = recruitmentServiceHasDuplicatePlantillaNumber($supabaseUrl, $headers, $plantillaItemNo, $excludePostingId);
        if ($plantillaExists === null) {
            return ['ok' => false, 'message' => 'Unable to validate Plantilla Number uniqueness. Please try again.'];
        }
        if ($plantillaExists) {
            return ['ok' => false, 'message' => 'Plantilla Number already exists. Please use a unique value.'];
        }

        $payload = [
            'office_id' => $officeId,
            'position_id' => $positionId,
            'title' => $title,
            'plantilla_item_no' => $plantillaItemNo,
            'description' => $description,
            'qualifications' => $qualifications,
            'responsibilities' => $responsibilities,
            'required_documents' => $requiredDocuments,
            'posting_status' => $postingStatus,
            'open_date' => $openDate,
            'close_date' => $closeDate,
            'published_by' => $actorUserId !== '' ? $actorUserId : null,
        ];

        return [
            'ok' => true,
            'position_id' => $positionId,
            'eligibility_requirement' => recruitmentServiceNormalizeEligibilityRequirement(
                recruitmentServiceEligibilityOptionToRequirement($criteriaEligibility)
            ),
            'criteria_eligibility' => $criteriaEligibility,
            'criteria_education_level' => $criteriaEducationLevel,
            'criteria_training_hours' => $criteriaTrainingHours,
            'criteria_experience_years' => $criteriaExperienceYears,
            'payload' => $payload,
        ];
    }
}

if (!function_exists('recruitmentServiceCreateJobPosting')) {
    function recruitmentServiceCreateJobPosting(string $supabaseUrl, array $headers, string $actorUserId, array $prepared): array
    {
        $insertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/job_postings',
            array_merge($headers, ['Prefer: return=representation']),
            [$prepared['payload']]
        );

        if (!isSuccessful($insertResponse)) {
            return ['ok' => false, 'message' => 'Failed to create job posting.'];
        }

        $createdPostingId = (string)($insertResponse['data'][0]['id'] ?? '');

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $actorUserId !== '' ? $actorUserId : null,
                'module_name' => 'recruitment',
                'entity_name' => 'job_postings',
                'entity_id' => $createdPostingId !== '' ? $createdPostingId : null,
                'action_name' => 'create_job_posting',
                'old_data' => null,
                'new_data' => $prepared['payload'],
                'ip_address' => clientIp(),
            ]]
        );

        recruitmentServiceSaveEligibilityRequirement(
            $supabaseUrl,
            $headers,
            $actorUserId,
            (string)$prepared['position_id'],
            'position',
            (string)$prepared['eligibility_requirement']
        );

        recruitmentServiceSavePositionCriteria(
            $supabaseUrl,
            $headers,
            $actorUserId,
            (string)$prepared['position_id'],
            (string)$prepared['criteria_eligibility'],
            (string)$prepared['criteria_education_level'],
            (float)$prepared['criteria_training_hours'],
            (float)$prepared['criteria_experience_years']
        );

        return ['ok' => true, 'message' => 'Job posting created successfully.', 'posting_id' => $createdPostingId];
    }
}

if (!function_exists('recruitmentServiceUpdateJobPosting')) {
    function recruitmentServiceUpdateJobPosting(string $supabaseUrl, array $headers, string $actorUserId, string $postingId, array $prepared): array
    {
        if (!recruitmentServiceIsValidUuid($postingId)) {
            return ['ok' => false, 'message' => 'Invalid job posting selected.'];
        }

        $postingResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/job_postings?select=id,title,office_id,position_id,plantilla_item_no,description,qualifications,responsibilities,posting_status,open_date,close_date&id=eq.' . $postingId . '&limit=1',
            $headers
        );

        $postingRow = $postingResponse['data'][0] ?? null;
        if (!is_array($postingRow)) {
            return ['ok' => false, 'message' => 'Job posting record not found.'];
        }

        $patchPayload = (array)$prepared['payload'];
        unset($patchPayload['published_by']);
        $patchPayload['updated_at'] = gmdate('c');

        $patchResponse = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/job_postings?id=eq.' . $postingId,
            array_merge($headers, ['Prefer: return=minimal']),
            $patchPayload
        );

        if (!isSuccessful($patchResponse)) {
            return ['ok' => false, 'message' => 'Failed to update job posting.'];
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $actorUserId !== '' ? $actorUserId : null,
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

        recruitmentServiceSaveEligibilityRequirement(
            $supabaseUrl,
            $headers,
            $actorUserId,
            (string)$prepared['position_id'],
            'position',
            (string)$prepared['eligibility_requirement']
        );

        recruitmentServiceSavePositionCriteria(
            $supabaseUrl,
            $headers,
            $actorUserId,
            (string)$prepared['position_id'],
            (string)$prepared['criteria_eligibility'],
            (string)$prepared['criteria_education_level'],
            (float)$prepared['criteria_training_hours'],
            (float)$prepared['criteria_experience_years']
        );

        return ['ok' => true, 'message' => 'Job posting updated successfully.'];
    }
}

if (!function_exists('recruitmentServiceArchiveJobPosting')) {
    function recruitmentServiceArchiveJobPosting(string $supabaseUrl, array $headers, string $actorUserId, string $postingId): array
    {
        if (!recruitmentServiceIsValidUuid($postingId)) {
            return ['ok' => false, 'message' => 'Invalid job posting selected.'];
        }

        $postingResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/job_postings?select=id,title,posting_status&id=eq.' . $postingId . '&limit=1',
            $headers
        );

        $postingRow = $postingResponse['data'][0] ?? null;
        if (!is_array($postingRow)) {
            return ['ok' => false, 'message' => 'Job posting record not found.'];
        }

        if (strtolower((string)($postingRow['posting_status'] ?? '')) === 'archived') {
            return ['ok' => true, 'message' => 'Job posting is already archived.'];
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
            return ['ok' => false, 'message' => 'Failed to archive job posting.'];
        }

        logStatusTransition(
            $supabaseUrl,
            $headers,
            $actorUserId,
            'recruitment',
            'job_postings',
            $postingId,
            'archive_job_posting',
            (string)($postingRow['posting_status'] ?? ''),
            'archived'
        );

        return ['ok' => true, 'message' => 'Job posting archived successfully.'];
    }
}