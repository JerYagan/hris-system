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
            'person_id' => null,
            'employment_id' => null,
            'office_id' => null,
            'office_name' => null,
            'position_id' => null,
            'position_title' => null,
            'employment_status' => null,
        ];

        if (!isValidUuid($employeeUserId) || $supabaseUrl === '' || empty($headers)) {
            return $context;
        }

        $roleResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_role_assignments?select=id,is_primary,roles!inner(role_key)'
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
        $context['employment_status'] = cleanText($employmentRow['employment_status'] ?? null);
        $context['office_id'] = cleanText($employmentRow['office_id'] ?? null);
        $context['position_id'] = cleanText($employmentRow['position_id'] ?? null);
        $context['office_name'] = cleanText($employmentRow['office']['office_name'] ?? null);
        $context['position_title'] = cleanText($employmentRow['position']['position_title'] ?? null);
        $context['is_valid'] = true;
        $context['error'] = null;

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
