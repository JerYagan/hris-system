<?php

require_once __DIR__ . '/../auth-guard.php';
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/supabase.php';

if (!function_exists('staffBackendContext')) {
    function staffBackendContext(): array
    {
        $supabase = staffSupabaseBootstrap();

        return [
            'supabase_url' => (string)($supabase['url'] ?? ''),
            'service_role_key' => (string)($supabase['service_role_key'] ?? ''),
            'headers' => (array)($supabase['headers'] ?? []),
            'staff_user_id' => (string)($_SESSION['user']['id'] ?? ''),
        ];
    }
}

if (!function_exists('staffActorRoleAllowlist')) {
    function staffActorRoleAllowlist(): array
    {
        if (function_exists('staffAllowedActorRoles')) {
            return staffAllowedActorRoles();
        }

        return ['staff', 'hr_officer', 'supervisor', 'admin'];
    }
}

if (!function_exists('userHasActiveRoleAssignment')) {
    function userHasActiveRoleAssignment(string $supabaseUrl, array $headers, string $userId, string $roleKey): bool
    {
        if (!isValidUuid($userId) || $supabaseUrl === '' || empty($headers)) {
            return false;
        }

        $normalizedRoleKey = strtolower(trim($roleKey));
        if ($normalizedRoleKey === '') {
            return false;
        }

        $response = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_role_assignments?select=id,roles!inner(role_key)'
            . '&user_id=eq.' . rawurlencode($userId)
            . '&roles.role_key=eq.' . rawurlencode($normalizedRoleKey)
            . '&or=(expires_at.is.null,expires_at.gt.' . rawurlencode(gmdate('c')) . ')'
            . '&limit=1',
            $headers
        );

        return isSuccessful($response) && !empty((array)($response['data'] ?? []));
    }
}

if (!function_exists('userHasAnyActiveRoleAssignment')) {
    function userHasAnyActiveRoleAssignment(string $supabaseUrl, array $headers, string $userId, array $roleKeys): bool
    {
        if (!isValidUuid($userId) || $supabaseUrl === '' || empty($headers)) {
            return false;
        }

        foreach ($roleKeys as $roleKey) {
            $normalizedRoleKey = strtolower(trim((string)$roleKey));
            if ($normalizedRoleKey === '') {
                continue;
            }

            if (userHasActiveRoleAssignment($supabaseUrl, $headers, $userId, $normalizedRoleKey)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('fetchActiveRoleUserIdMap')) {
    function fetchActiveRoleUserIdMap(string $supabaseUrl, array $headers, string $roleKey, int $limit = 5000): array
    {
        $map = [];
        if ($supabaseUrl === '' || empty($headers)) {
            return $map;
        }

        $normalizedRoleKey = strtolower(trim($roleKey));
        if ($normalizedRoleKey === '') {
            return $map;
        }

        $response = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_role_assignments?select=user_id,expires_at,roles!inner(role_key)'
            . '&roles.role_key=eq.' . rawurlencode($normalizedRoleKey)
            . '&limit=' . max(1, $limit),
            $headers
        );

        if (!isSuccessful($response)) {
            return $map;
        }

        $roleRows = (array)($response['data'] ?? []);
        foreach ($roleRows as $roleRow) {
            $roleUserId = cleanText($roleRow['user_id'] ?? null) ?? '';
            if (!isValidUuid($roleUserId)) {
                continue;
            }

            $expiresAt = cleanText($roleRow['expires_at'] ?? null);
            if ($expiresAt !== null) {
                $expiresAtTs = strtotime($expiresAt);
                if ($expiresAtTs !== false && $expiresAtTs <= time()) {
                    continue;
                }
            }

            $map[$roleUserId] = true;
        }

        return $map;
    }
}

if (!function_exists('fetchValidCredentialUserIdMap')) {
    function fetchValidCredentialUserIdMap(string $supabaseUrl, array $headers, int $limit = 5000): array
    {
        $map = [];
        if ($supabaseUrl === '' || empty($headers)) {
            return $map;
        }

        $response = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_accounts?select=id,email,account_status'
            . '&email=not.is.null'
            . '&limit=' . max(1, $limit),
            $headers
        );

        if (!isSuccessful($response)) {
            return $map;
        }

        $rows = (array)($response['data'] ?? []);
        foreach ($rows as $row) {
            $userId = cleanText($row['id'] ?? null) ?? '';
            if (!isValidUuid($userId)) {
                continue;
            }

            $email = trim((string)(cleanText($row['email'] ?? null) ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $accountStatus = strtolower((string)(cleanText($row['account_status'] ?? null) ?? 'active'));
            if (in_array($accountStatus, ['disabled', 'archived', 'suspended'], true)) {
                continue;
            }

            $map[$userId] = true;
        }

        return $map;
    }
}

if (!function_exists('userHasValidAccountCredentials')) {
    function userHasValidAccountCredentials(string $supabaseUrl, array $headers, string $userId): bool
    {
        if (!isValidUuid($userId) || $supabaseUrl === '' || empty($headers)) {
            return false;
        }

        $response = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_accounts?select=id,email,account_status'
            . '&id=eq.' . rawurlencode($userId)
            . '&limit=1',
            $headers
        );

        if (!isSuccessful($response) || empty((array)($response['data'] ?? []))) {
            return false;
        }

        $row = (array)$response['data'][0];
        $email = trim((string)(cleanText($row['email'] ?? null) ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $accountStatus = strtolower((string)(cleanText($row['account_status'] ?? null) ?? 'active'));
        return !in_array($accountStatus, ['disabled', 'archived', 'suspended'], true);
    }
}

if (!function_exists('userIsCredentialedWithManagedRole')) {
    function userIsCredentialedWithManagedRole(string $supabaseUrl, array $headers, string $userId, array $roleKeys = ['employee', 'applicant']): bool
    {
        if (!userHasValidAccountCredentials($supabaseUrl, $headers, $userId)) {
            return false;
        }

        return userHasAnyActiveRoleAssignment($supabaseUrl, $headers, $userId, $roleKeys);
    }
}

if (!function_exists('resolveStaffIdentityContext')) {
    function resolveStaffIdentityContext(string $supabaseUrl, array $headers, string $staffUserId): array
    {
        $context = [
            'is_valid' => false,
            'error' => 'Staff session context is invalid. Please login again.',
            'user_id' => $staffUserId,
            'role_assignment_id' => null,
            'role_key' => null,
            'role_name' => null,
            'office_id' => null,
            'office_name' => null,
            'person_id' => null,
            'employment_id' => null,
            'employment_status' => null,
            'position_id' => null,
            'position_title' => null,
        ];

        if (!isValidUuid($staffUserId) || $supabaseUrl === '' || empty($headers)) {
            return $context;
        }

        $accountResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_accounts?select=id,account_status'
            . '&id=eq.' . rawurlencode($staffUserId)
            . '&limit=1',
            $headers
        );

        if (!isSuccessful($accountResponse) || empty((array)($accountResponse['data'] ?? []))) {
            $context['error'] = 'Staff account is missing in user accounts. Please contact HR/admin.';
            return $context;
        }

        $accountRow = (array)$accountResponse['data'][0];
        $accountStatus = strtolower((string)cleanText($accountRow['account_status'] ?? null));
        if (in_array($accountStatus, ['disabled', 'archived', 'suspended'], true)) {
            $context['error'] = 'Staff account is not active for module access.';
            return $context;
        }

        $roleResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_role_assignments?select=id,user_id,role_id,office_id,is_primary,expires_at,assigned_at,roles!inner(role_key,role_name),office:offices(id,office_name)'
            . '&user_id=eq.' . rawurlencode($staffUserId)
            . '&order=is_primary.desc,assigned_at.desc'
            . '&limit=10',
            $headers
        );

        if (!isSuccessful($roleResponse) || empty((array)($roleResponse['data'] ?? []))) {
            $context['error'] = 'Staff role assignment is missing. Please contact HR/admin.';
            return $context;
        }

        $selectedRoleRow = null;
        $roleRows = (array)($roleResponse['data'] ?? []);
        $allowedRoleKeys = staffActorRoleAllowlist();

        foreach ($roleRows as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $candidateRoleKey = strtolower((string)cleanText($candidate['roles']['role_key'] ?? null));
            if ($candidateRoleKey === '' || !in_array($candidateRoleKey, $allowedRoleKeys, true)) {
                continue;
            }

            $expiresAt = cleanText($candidate['expires_at'] ?? null);
            if ($expiresAt !== null) {
                $expiresAtTimestamp = strtotime($expiresAt);
                if ($expiresAtTimestamp !== false && $expiresAtTimestamp <= time()) {
                    continue;
                }
            }

            $selectedRoleRow = $candidate;
            break;
        }

        if (!is_array($selectedRoleRow)) {
            $context['error'] = 'No active staff role assignment is available for this account.';
            return $context;
        }

        $roleRow = $selectedRoleRow;
        $roleAssignmentId = cleanText($roleRow['id'] ?? null);
        $roleKey = strtolower((string)cleanText($roleRow['roles']['role_key'] ?? null));

        if ($roleAssignmentId === null || !isValidUuid($roleAssignmentId)) {
            $context['error'] = 'Staff role assignment could not be resolved.';
            return $context;
        }

        if ($roleKey === '' || !in_array($roleKey, $allowedRoleKeys, true)) {
            $context['error'] = 'Current account is not allowed to access staff modules.';
            return $context;
        }

        $context['role_assignment_id'] = $roleAssignmentId;
        $context['role_key'] = $roleKey;
        $context['role_name'] = cleanText($roleRow['roles']['role_name'] ?? null);
        $context['office_id'] = cleanText($roleRow['office_id'] ?? null);
        $context['office_name'] = cleanText($roleRow['office']['office_name'] ?? null);

        $personResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/people?select=id'
            . '&user_id=eq.' . rawurlencode($staffUserId)
            . '&limit=1',
            $headers
        );

        if (!isSuccessful($personResponse) || empty((array)($personResponse['data'] ?? []))) {
            $context['error'] = 'Staff person profile is missing. Please contact HR/admin.';
            return $context;
        }

        $personRow = (array)$personResponse['data'][0];
        $personId = cleanText($personRow['id'] ?? null);
        if ($personId === null || !isValidUuid($personId)) {
            $context['error'] = 'Staff person profile could not be resolved.';
            return $context;
        }

        $context['person_id'] = $personId;

        $employmentResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/employment_records?select=id,employment_status,office_id,position_id,office:offices(id,office_name),position:job_positions(id,position_title)'
            . '&person_id=eq.' . rawurlencode($personId)
            . '&is_current=eq.true'
            . '&limit=1',
            $headers
        );

        if (!isSuccessful($employmentResponse) || empty((array)($employmentResponse['data'] ?? []))) {
            $context['error'] = 'No active employment record found for this staff account.';
            return $context;
        }

        $employmentRow = (array)$employmentResponse['data'][0];
        $employmentId = cleanText($employmentRow['id'] ?? null);
        if ($employmentId === null || !isValidUuid($employmentId)) {
            $context['error'] = 'Active employment record could not be resolved.';
            return $context;
        }

        $context['employment_id'] = $employmentId;
        $context['employment_status'] = cleanText($employmentRow['employment_status'] ?? null);
        $context['position_id'] = cleanText($employmentRow['position_id'] ?? null);
        $context['position_title'] = cleanText($employmentRow['position']['position_title'] ?? null);

        if ($context['office_id'] === null) {
            $context['office_id'] = cleanText($employmentRow['office_id'] ?? null);
        }
        if ($context['office_name'] === null) {
            $context['office_name'] = cleanText($employmentRow['office']['office_name'] ?? null);
        }

        $context['is_valid'] = true;
        $context['error'] = null;

        return $context;
    }
}

if (!function_exists('staffModuleBootstrapContext')) {
    function staffModuleBootstrapContext(): array
    {
        $backend = staffBackendContext();

        $supabaseUrl = (string)($backend['supabase_url'] ?? '');
        $serviceRoleKey = (string)($backend['service_role_key'] ?? '');
        $headers = (array)($backend['headers'] ?? []);
        $staffUserId = (string)($backend['staff_user_id'] ?? '');

        $staffContext = [
            'is_valid' => false,
            'error' => 'Staff context bootstrap was not initialized.',
        ];

        if ($supabaseUrl !== '' && $serviceRoleKey !== '' && !empty($headers) && isValidUuid($staffUserId)) {
            $staffContext = resolveStaffIdentityContext($supabaseUrl, $headers, $staffUserId);
        } else {
            $staffContext['error'] = 'Supabase credentials or session user context are missing.';
        }

        return [
            'supabase_url' => $supabaseUrl,
            'service_role_key' => $serviceRoleKey,
            'headers' => $headers,
            'staff_user_id' => $staffUserId,
            'staff_context' => $staffContext,
            'csrf_token' => ensureCsrfToken(),
        ];
    }
}

if (!function_exists('canTransitionStatus')) {
    function canTransitionStatus(string $entity, string $oldStatus, string $newStatus): bool
    {
        $entityKey = strtolower(trim($entity));
        $oldKey = strtolower(trim($oldStatus));
        $newKey = strtolower(trim($newStatus));

        if ($entityKey === '' || $oldKey === '' || $newKey === '') {
            return false;
        }

        if ($oldKey === $newKey) {
            return true;
        }

        $rules = [
            'leave_requests' => [
                'pending' => ['approved', 'rejected', 'cancelled'],
            ],
            'time_adjustment_requests' => [
                'pending' => ['approved', 'rejected', 'needs_revision'],
            ],
            'overtime_requests' => [
                'pending' => ['approved', 'rejected', 'cancelled'],
            ],
            'applications' => [
                'submitted' => ['screening', 'rejected', 'withdrawn'],
                'screening' => ['shortlisted', 'rejected', 'withdrawn'],
                'shortlisted' => ['interview', 'rejected', 'withdrawn'],
                'interview' => ['offer', 'rejected', 'withdrawn'],
                'offer' => ['hired', 'rejected', 'withdrawn'],
            ],
            'documents' => [
                'draft' => ['submitted', 'archived'],
                'submitted' => ['approved', 'rejected', 'needs_revision', 'archived'],
                'needs_revision' => ['submitted', 'rejected', 'archived'],
            ],
            'payroll_periods' => [
                'open' => ['processing'],
                'processing' => ['posted'],
                'posted' => ['closed'],
            ],
            'payroll_runs' => [
                'draft' => ['computed', 'cancelled'],
                'computed' => ['approved', 'cancelled'],
                'approved' => ['released', 'cancelled'],
            ],
            'learning_courses' => [
                'draft' => ['published'],
                'published' => ['archived'],
            ],
            'learning_enrollments' => [
                'pending' => ['approved', 'rejected'],
            ],
            'praise_nominations' => [
                'pending' => ['approved', 'rejected', 'cancelled'],
            ],
        ];

        if (!isset($rules[$entityKey][$oldKey])) {
            return false;
        }

        return in_array($newKey, $rules[$entityKey][$oldKey], true);
    }
}

if (!function_exists('requireStaffPostWithCsrf')) {
    function requireStaffPostWithCsrf(?string $csrfToken, string $errorMessage = 'Invalid request token. Please refresh and try again.'): void
    {
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return;
        }

        if (!isValidCsrfToken($csrfToken)) {
            redirectWithState('error', $errorMessage);
        }
    }
}

if (!function_exists('sanitizeUuidListForInFilter')) {
    function sanitizeUuidListForInFilter(array $values): string
    {
        $sanitized = [];
        foreach ($values as $value) {
            $uuid = cleanText($value) ?? '';
            if (!isValidUuid($uuid)) {
                continue;
            }

            $sanitized[$uuid] = true;
        }

        return implode(',', array_keys($sanitized));
    }
}

if (!function_exists('staffSystemSettingReadValue')) {
    function staffSystemSettingReadValue(string $supabaseUrl, array $headers, string $settingKey): mixed
    {
        if ($supabaseUrl === '' || empty($headers) || trim($settingKey) === '') {
            return null;
        }

        $response = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode($settingKey) . '&limit=1',
            $headers
        );

        if (!isSuccessful($response)) {
            return null;
        }

        $rawValue = $response['data'][0]['setting_value'] ?? null;
        if (is_array($rawValue) && array_key_exists('value', $rawValue)) {
            return $rawValue['value'];
        }

        return $rawValue;
    }
}

if (!function_exists('staffSystemSettingUpsertValue')) {
    function staffSystemSettingUpsertValue(string $supabaseUrl, array $headers, string $settingKey, mixed $value, ?string $updatedByUserId = null): bool
    {
        if ($supabaseUrl === '' || empty($headers) || trim($settingKey) === '') {
            return false;
        }

        $payload = [[
            'setting_key' => $settingKey,
            'setting_value' => ['value' => $value],
            'updated_by' => isValidUuid((string)$updatedByUserId) ? $updatedByUserId : null,
            'updated_at' => gmdate('c'),
        ]];

        $upsertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/system_settings?on_conflict=setting_key',
            array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
            $payload
        );

        if (isSuccessful($upsertResponse)) {
            return true;
        }

        $patchResponse = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/system_settings?setting_key=eq.' . rawurlencode($settingKey),
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'setting_value' => ['value' => $value],
                'updated_by' => isValidUuid((string)$updatedByUserId) ? $updatedByUserId : null,
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

if (!function_exists('staffApplicantEvaluationDefaultCriteria')) {
    function staffApplicantEvaluationDefaultCriteria(): array
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
        ];
    }
}

if (!function_exists('staffApplicantEvaluationNumeric')) {
    function staffApplicantEvaluationNumeric(mixed $value, float $default = 0.0, float $min = 0.0, float $max = 1000.0): float
    {
        if (!is_numeric($value)) {
            return max($min, min($max, $default));
        }

        $number = (float)$value;
        return max($min, min($max, $number));
    }
}

if (!function_exists('staffApplicantEvaluationNormalizeCriteria')) {
    function staffApplicantEvaluationNormalizeCriteria(mixed $criteria): array
    {
        $defaults = staffApplicantEvaluationDefaultCriteria();
        if (!is_array($criteria)) {
            return $defaults;
        }

        $weights = is_array($criteria['weights'] ?? null) ? (array)$criteria['weights'] : [];

        return [
            'eligibility' => strtolower(trim((string)($criteria['eligibility'] ?? $defaults['eligibility']))),
            'minimum_education_years' => staffApplicantEvaluationNumeric($criteria['minimum_education_years'] ?? null, (float)$defaults['minimum_education_years'], 0, 20),
            'minimum_training_hours' => staffApplicantEvaluationNumeric($criteria['minimum_training_hours'] ?? null, (float)$defaults['minimum_training_hours'], 0, 1000),
            'minimum_experience_years' => staffApplicantEvaluationNumeric($criteria['minimum_experience_years'] ?? null, (float)$defaults['minimum_experience_years'], 0, 60),
            'threshold' => staffApplicantEvaluationNumeric($criteria['threshold'] ?? null, (float)$defaults['threshold'], 0, 100),
            'weights' => [
                'eligibility' => staffApplicantEvaluationNumeric($weights['eligibility'] ?? null, (float)$defaults['weights']['eligibility'], 0, 100),
                'education' => staffApplicantEvaluationNumeric($weights['education'] ?? null, (float)$defaults['weights']['education'], 0, 100),
                'training' => staffApplicantEvaluationNumeric($weights['training'] ?? null, (float)$defaults['weights']['training'], 0, 100),
                'experience' => staffApplicantEvaluationNumeric($weights['experience'] ?? null, (float)$defaults['weights']['experience'], 0, 100),
            ],
        ];
    }
}

if (!function_exists('staffApplicantEvaluationNormalizePositionKey')) {
    function staffApplicantEvaluationNormalizePositionKey(string $positionTitle): string
    {
        $normalized = strtolower(trim($positionTitle));
        if ($normalized === '') {
            return '';
        }

        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return trim((string)$normalized);
    }
}

if (!function_exists('staffApplicantEvaluationLoadCriteriaMap')) {
    function staffApplicantEvaluationLoadCriteriaMap(string $supabaseUrl, array $headers): array
    {
        $candidateKeys = [
            'evaluation.applicant.criteria_by_position_title',
            'evaluation.rule_based.criteria_by_position_title',
            'evaluation.rule_based.position_criteria',
        ];

        foreach ($candidateKeys as $key) {
            $stored = staffSystemSettingReadValue($supabaseUrl, $headers, $key);
            if (!is_array($stored)) {
                continue;
            }

            return $stored;
        }

        return [];
    }
}

if (!function_exists('staffApplicantEvaluationResolveCriteria')) {
    function staffApplicantEvaluationResolveCriteria(string $supabaseUrl, array $headers, string $positionTitle): array
    {
        $defaults = staffApplicantEvaluationDefaultCriteria();
        $positionKey = staffApplicantEvaluationNormalizePositionKey($positionTitle);
        $criteriaMap = staffApplicantEvaluationLoadCriteriaMap($supabaseUrl, $headers);

        if ($positionKey !== '' && isset($criteriaMap[$positionKey])) {
            return staffApplicantEvaluationNormalizeCriteria($criteriaMap[$positionKey]);
        }

        foreach ($criteriaMap as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (staffApplicantEvaluationNormalizePositionKey($key) === $positionKey && is_array($value)) {
                return staffApplicantEvaluationNormalizeCriteria($value);
            }
        }

        $globalCandidates = [
            'evaluation.applicant.default_criteria',
            'evaluation.rule_based.default_criteria',
            'evaluation.rule_based.criteria',
        ];

        foreach ($globalCandidates as $settingKey) {
            $storedDefault = staffSystemSettingReadValue($supabaseUrl, $headers, $settingKey);
            if (is_array($storedDefault)) {
                return staffApplicantEvaluationNormalizeCriteria($storedDefault);
            }
        }

        return $defaults;
    }
}

if (!function_exists('staffApplicantEvaluationNormalizeProfile')) {
    function staffApplicantEvaluationNormalizeProfile(array $profile): array
    {
        return [
            'eligibility' => strtolower(trim((string)($profile['eligibility'] ?? 'n/a'))),
            'education_years' => staffApplicantEvaluationNumeric($profile['education_years'] ?? null, 0, 0, 20),
            'training_hours' => staffApplicantEvaluationNumeric($profile['training_hours'] ?? null, 0, 0, 1000),
            'experience_years' => staffApplicantEvaluationNumeric($profile['experience_years'] ?? null, 0, 0, 60),
        ];
    }
}

if (!function_exists('staffApplicantEvaluationMatchEligibility')) {
    function staffApplicantEvaluationMatchEligibility(string $required, string $actual): bool
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

if (!function_exists('staffApplicantEvaluationCompute')) {
    function staffApplicantEvaluationCompute(array $applicantProfile, array $criteria): array
    {
        $normalizedCriteria = staffApplicantEvaluationNormalizeCriteria($criteria);
        $normalizedProfile = staffApplicantEvaluationNormalizeProfile($applicantProfile);

        $weights = (array)($normalizedCriteria['weights'] ?? []);
        $eligibilityWeight = (float)($weights['eligibility'] ?? 25);
        $educationWeight = (float)($weights['education'] ?? 25);
        $trainingWeight = (float)($weights['training'] ?? 25);
        $experienceWeight = (float)($weights['experience'] ?? 25);

        $eligibilityMeets = staffApplicantEvaluationMatchEligibility(
            (string)($normalizedCriteria['eligibility'] ?? 'n/a'),
            (string)($normalizedProfile['eligibility'] ?? 'n/a')
        );
        $educationMeets = (float)($normalizedProfile['education_years'] ?? 0) >= (float)($normalizedCriteria['minimum_education_years'] ?? 2);
        $trainingMeets = (float)($normalizedProfile['training_hours'] ?? 0) >= (float)($normalizedCriteria['minimum_training_hours'] ?? 8);
        $experienceMeets = (float)($normalizedProfile['experience_years'] ?? 0) >= (float)($normalizedCriteria['minimum_experience_years'] ?? 1);

        $eligibilityScore = $eligibilityMeets ? $eligibilityWeight : 0;
        $educationScore = $educationMeets ? $educationWeight : 0;
        $trainingScore = $trainingMeets ? $trainingWeight : 0;
        $experienceScore = $experienceMeets ? $experienceWeight : 0;

        $totalScore = (float)($eligibilityScore + $educationScore + $trainingScore + $experienceScore);
        $threshold = (float)($normalizedCriteria['threshold'] ?? 75);

        $allCriteriaMet = $eligibilityMeets && $educationMeets && $trainingMeets && $experienceMeets;
        $isQualified = $totalScore >= $threshold;

        $status = $isQualified ? 'Qualified for Evaluation' : 'Not Qualified';
        $statusClass = $isQualified ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800';

        $failedCriteria = [];
        if (!$eligibilityMeets) {
            $failedCriteria[] = 'eligibility';
        }
        if (!$educationMeets) {
            $failedCriteria[] = 'education';
        }
        if (!$trainingMeets) {
            $failedCriteria[] = 'training';
        }
        if (!$experienceMeets) {
            $failedCriteria[] = 'experience';
        }

        return [
            'status' => $status,
            'status_class' => $statusClass,
            'qualified' => $isQualified,
            'all_criteria_met' => $allCriteriaMet,
            'threshold' => $threshold,
            'total_score' => (int)round($totalScore),
            'scores' => [
                'eligibility' => (int)round($eligibilityScore),
                'education' => (int)round($educationScore),
                'training' => (int)round($trainingScore),
                'experience' => (int)round($experienceScore),
            ],
            'criteria_met' => [
                'eligibility' => $eligibilityMeets,
                'education' => $educationMeets,
                'training' => $trainingMeets,
                'experience' => $experienceMeets,
            ],
            'failed_criteria' => $failedCriteria,
            'criteria' => $normalizedCriteria,
            'profile' => $normalizedProfile,
        ];
    }
}

if (!function_exists('logStaffSecurityEvent')) {
    function logStaffSecurityEvent(
        string $supabaseUrl,
        array $headers,
        ?string $actorUserId,
        string $moduleName,
        string $actionName,
        array $details = []
    ): void {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => isValidUuid((string)$actorUserId) ? $actorUserId : null,
                'module_name' => $moduleName,
                'entity_name' => 'security',
                'entity_id' => null,
                'action_name' => $actionName,
                'old_data' => null,
                'new_data' => $details,
                'ip_address' => clientIp(),
            ]]
        );
    }
}

if (!function_exists('renderStaffContextErrorAndExit')) {
    function renderStaffContextErrorAndExit(string $message): never
    {
        http_response_code(403);
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        echo '<!doctype html>';
        echo '<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>Staff Access Context Error</title>';
        echo '<style>body{font-family:Arial,sans-serif;background:#f9fafb;margin:0;padding:24px;color:#111827}.card{max-width:680px;margin:40px auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px}.title{font-size:20px;font-weight:700;margin:0 0 8px}.text{font-size:14px;line-height:1.5;margin:0 0 16px;color:#374151}.link{display:inline-block;text-decoration:none;border:1px solid #d1d5db;border-radius:8px;padding:8px 12px;color:#111827}</style>';
        echo '</head><body><section class="card"><h1 class="title">Unable to load staff context</h1><p class="text">' . $safeMessage . '</p><a class="link" href="/hris-system/pages/auth/login.php">Back to Login</a></section></body></html>';
        exit;
    }
}
