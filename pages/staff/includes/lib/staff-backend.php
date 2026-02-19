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

        if ($roleKey !== 'admin' && $context['office_id'] === null) {
            $context['error'] = 'Staff role assignment has no office scope. Please contact HR/admin.';
            return $context;
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
