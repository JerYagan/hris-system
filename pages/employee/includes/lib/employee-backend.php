<?php

require_once __DIR__ . '/../auth-guard.php';
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/supabase.php';

if (!function_exists('employeeBackendContext')) {
    function employeeBackendContext(): array
    {
        $supabase = employeeSupabaseBootstrap();

        return [
            'supabase_url' => (string)($supabase['url'] ?? ''),
            'service_role_key' => (string)($supabase['service_role_key'] ?? ''),
            'headers' => (array)($supabase['headers'] ?? []),
            'employee_user_id' => (string)($_SESSION['user']['id'] ?? ''),
        ];
    }
}

if (!function_exists('resolveEmployeeIdentityContext')) {
    function resolveEmployeeIdentityContext(string $supabaseUrl, array $headers, string $employeeUserId): array
    {
        $context = [
            'is_valid' => false,
            'error' => 'Employee session context is invalid. Please login again.',
            'user_id' => $employeeUserId,
            'role_assignment_id' => null,
            'employee_role_assigned_at' => null,
            'person_id' => null,
            'first_name' => null,
            'surname' => null,
            'display_name' => null,
            'employment_id' => null,
            'office_id' => null,
            'office_name' => null,
            'position_id' => null,
            'position_title' => null,
            'employment_type' => null,
            'employment_status' => null,
        ];

        if (!isValidUuid($employeeUserId) || $supabaseUrl === '' || empty($headers)) {
            return $context;
        }

        $roleResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_role_assignments?select=id,is_primary,assigned_at,roles!inner(role_key)'
            . '&user_id=eq.' . rawurlencode($employeeUserId)
            . '&roles.role_key=eq.employee'
            . '&order=is_primary.desc&limit=1',
            $headers
        );

        if (!isSuccessful($roleResponse) || empty((array)($roleResponse['data'] ?? []))) {
            $context['error'] = 'Employee role assignment is missing. Please contact HR/admin.';
            return $context;
        }

        $roleRow = (array)$roleResponse['data'][0];
        $roleAssignmentId = cleanText($roleRow['id'] ?? null);
        if ($roleAssignmentId === null || !isValidUuid($roleAssignmentId)) {
            $context['error'] = 'Employee role assignment could not be resolved.';
            return $context;
        }
        $context['role_assignment_id'] = $roleAssignmentId;
        $context['employee_role_assigned_at'] = cleanText($roleRow['assigned_at'] ?? null);

        $personResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/people?select=id,first_name,surname'
            . '&user_id=eq.' . rawurlencode($employeeUserId)
            . '&limit=1',
            $headers
        );

        if (!isSuccessful($personResponse) || empty((array)($personResponse['data'] ?? []))) {
            $context['error'] = 'Employee person profile is missing. Please contact HR/admin.';
            return $context;
        }

        $personRow = (array)$personResponse['data'][0];
        $personId = cleanText($personRow['id'] ?? null);
        if ($personId === null || !isValidUuid($personId)) {
            $context['error'] = 'Employee person profile could not be resolved.';
            return $context;
        }
        $firstName = cleanText($personRow['first_name'] ?? null);
        $surname = cleanText($personRow['surname'] ?? null);
        $context['person_id'] = $personId;
        $context['first_name'] = $firstName;
        $context['surname'] = $surname;
        $context['display_name'] = cleanText(trim((string)$firstName . ' ' . (string)$surname));

        $employmentResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/employment_records?select=id,employment_status,employment_type,office_id,position_id,office:offices(id,office_name),position:job_positions(id,position_title,employment_classification)'
            . '&person_id=eq.' . rawurlencode($personId)
            . '&is_current=eq.true'
            . '&limit=1',
            $headers
        );

        if (!isSuccessful($employmentResponse) || empty((array)($employmentResponse['data'] ?? []))) {
            $context['error'] = 'No active employment record found. Please contact HR/admin.';
            return $context;
        }

        $employmentRow = (array)$employmentResponse['data'][0];
        $employmentId = cleanText($employmentRow['id'] ?? null);
        if ($employmentId === null || !isValidUuid($employmentId)) {
            $context['error'] = 'Active employment record could not be resolved.';
            return $context;
        }

        $context['employment_id'] = $employmentId;
        $rawEmploymentStatus = cleanText($employmentRow['employment_status'] ?? null);
        $employmentType = strtolower(trim((string)(cleanText($employmentRow['employment_type'] ?? null) ?? '')));
        $positionClassification = strtolower(trim((string)(cleanText($employmentRow['position']['employment_classification'] ?? null) ?? '')));
        $context['employment_type'] = $employmentType !== '' ? $employmentType : null;
        $context['employment_status'] = in_array($employmentType, ['permanent', 'contractual'], true)
            ? ($employmentType === 'contractual' ? 'contractual' : $rawEmploymentStatus)
            : (in_array($positionClassification, ['contractual', 'casual', 'job_order', 'job order'], true)
                ? $positionClassification
                : $rawEmploymentStatus);
        $context['office_id'] = cleanText($employmentRow['office_id'] ?? null);
        $context['position_id'] = cleanText($employmentRow['position_id'] ?? null);
        $context['office_name'] = cleanText($employmentRow['office']['office_name'] ?? null);
        $context['position_title'] = cleanText($employmentRow['position']['position_title'] ?? null);
        $context['is_valid'] = true;
        $context['error'] = null;

        return $context;
    }
}

if (!function_exists('resolveEmployeeIdentityContextCached')) {
    function resolveEmployeeIdentityContextCached(string $supabaseUrl, array $headers, string $employeeUserId, int $ttlSeconds = 45): array
    {
        $cache = isset($_SESSION['employee_identity_context_cache']) && is_array($_SESSION['employee_identity_context_cache'])
            ? (array)$_SESSION['employee_identity_context_cache']
            : [];

        $cacheUserId = (string)($cache['user_id'] ?? '');
        $cachedAt = (int)($cache['cached_at'] ?? 0);
        $cachedContext = isset($cache['context']) && is_array($cache['context'])
            ? (array)$cache['context']
            : [];

        $cacheIsFresh = $cacheUserId !== ''
            && $cacheUserId === $employeeUserId
            && $cachedAt > 0
            && (time() - $cachedAt) <= max(5, $ttlSeconds)
            && !empty($cachedContext)
            && (bool)($cachedContext['is_valid'] ?? false);

        if ($cacheIsFresh) {
            return $cachedContext;
        }

        $context = resolveEmployeeIdentityContext($supabaseUrl, $headers, $employeeUserId);

        if ((bool)($context['is_valid'] ?? false)) {
            $_SESSION['employee_identity_context_cache'] = [
                'user_id' => $employeeUserId,
                'cached_at' => time(),
                'context' => $context,
            ];
        }

        return $context;
    }
}

if (!function_exists('resolveEmployeeDashboardIdentityContext')) {
    function resolveEmployeeDashboardIdentityContext(string $supabaseUrl, array $headers, string $employeeUserId): array
    {
        $context = resolveEmployeeIdentityContext($supabaseUrl, $headers, $employeeUserId);

        if ((bool)($context['is_valid'] ?? false)) {
            return $context;
        }

        if (!isValidUuid($employeeUserId) || $supabaseUrl === '' || empty($headers)) {
            return $context;
        }

        $fallbackContext = [
            'is_valid' => false,
            'error' => 'Employee session context is invalid. Please login again.',
            'user_id' => $employeeUserId,
            'role_assignment_id' => null,
            'employee_role_assigned_at' => null,
            'person_id' => null,
            'first_name' => null,
            'surname' => null,
            'display_name' => null,
            'employment_id' => null,
            'office_id' => null,
            'office_name' => null,
            'position_id' => null,
            'position_title' => null,
            'employment_status' => null,
        ];

        $roleResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_role_assignments?select=id,is_primary,assigned_at,roles!inner(role_key)'
            . '&user_id=eq.' . rawurlencode($employeeUserId)
            . '&roles.role_key=eq.employee'
            . '&order=is_primary.desc&limit=1',
            $headers
        );

        if (!isSuccessful($roleResponse) || empty((array)($roleResponse['data'] ?? []))) {
            return $context;
        }

        $roleRow = (array)$roleResponse['data'][0];
        $roleAssignmentId = cleanText($roleRow['id'] ?? null);
        if ($roleAssignmentId === null || !isValidUuid($roleAssignmentId)) {
            return $context;
        }

        $fallbackContext['role_assignment_id'] = $roleAssignmentId;
        $fallbackContext['employee_role_assigned_at'] = cleanText($roleRow['assigned_at'] ?? null);

        $personResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/people?select=id,first_name,surname'
            . '&user_id=eq.' . rawurlencode($employeeUserId)
            . '&limit=1',
            $headers
        );

        if (!isSuccessful($personResponse) || empty((array)($personResponse['data'] ?? []))) {
            return $context;
        }

        $personRow = (array)$personResponse['data'][0];
        $personId = cleanText($personRow['id'] ?? null);
        if ($personId === null || !isValidUuid($personId)) {
            return $context;
        }

        $firstName = cleanText($personRow['first_name'] ?? null);
        $surname = cleanText($personRow['surname'] ?? null);

        $fallbackContext['person_id'] = $personId;
        $fallbackContext['first_name'] = $firstName;
        $fallbackContext['surname'] = $surname;
        $fallbackContext['display_name'] = cleanText(trim((string)$firstName . ' ' . (string)$surname));
        $fallbackContext['is_valid'] = true;
        $fallbackContext['error'] = null;

        return $fallbackContext;
    }
}

if (!function_exists('resolveEmployeeDashboardIdentityContextCached')) {
    function resolveEmployeeDashboardIdentityContextCached(string $supabaseUrl, array $headers, string $employeeUserId, int $ttlSeconds = 45): array
    {
        $cache = isset($_SESSION['employee_dashboard_identity_context_cache']) && is_array($_SESSION['employee_dashboard_identity_context_cache'])
            ? (array)$_SESSION['employee_dashboard_identity_context_cache']
            : [];

        $cacheUserId = (string)($cache['user_id'] ?? '');
        $cachedAt = (int)($cache['cached_at'] ?? 0);
        $cachedContext = isset($cache['context']) && is_array($cache['context'])
            ? (array)$cache['context']
            : [];

        $cacheIsFresh = $cacheUserId !== ''
            && $cacheUserId === $employeeUserId
            && $cachedAt > 0
            && (time() - $cachedAt) <= max(5, $ttlSeconds)
            && !empty($cachedContext)
            && (bool)($cachedContext['is_valid'] ?? false);

        if ($cacheIsFresh) {
            return $cachedContext;
        }

        $context = resolveEmployeeDashboardIdentityContext($supabaseUrl, $headers, $employeeUserId);

        if ((bool)($context['is_valid'] ?? false)) {
            $_SESSION['employee_dashboard_identity_context_cache'] = [
                'user_id' => $employeeUserId,
                'cached_at' => time(),
                'context' => $context,
            ];
        }

        return $context;
    }
}

if (!function_exists('renderEmployeeContextErrorAndExit')) {
    function renderEmployeeContextErrorAndExit(string $message): never
    {
        http_response_code(403);
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        echo '<!doctype html>';
        echo '<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>Employee Access Context Error</title>';
        echo '<style>body{font-family:Arial,sans-serif;background:#f9fafb;margin:0;padding:24px;color:#111827}.card{max-width:680px;margin:40px auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px}.title{font-size:20px;font-weight:700;margin:0 0 8px}.text{font-size:14px;line-height:1.5;margin:0 0 16px;color:#374151}.link{display:inline-block;text-decoration:none;border:1px solid #d1d5db;border-radius:8px;padding:8px 12px;color:#111827}</style>';
        echo '</head><body><section class="card"><h1 class="title">Unable to load employee context</h1><p class="text">' . $safeMessage . '</p><a class="link" href="/hris-system/pages/auth/login.php">Back to Login</a></section></body></html>';
        exit;
    }
}
