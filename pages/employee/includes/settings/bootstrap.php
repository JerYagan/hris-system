<?php

require_once __DIR__ . '/../lib/employee-backend.php';

$backend = employeeBackendContext();
$supabaseUrl = (string)($backend['supabase_url'] ?? '');
$serviceRoleKey = (string)($backend['service_role_key'] ?? '');
$headers = (array)($backend['headers'] ?? []);
$employeeUserId = (string)($backend['employee_user_id'] ?? '');

if ($supabaseUrl === '' || $serviceRoleKey === '') {
    redirectWithState('error', 'Supabase credentials are missing. Check your .env file.', 'settings.php');
}

$employeeContext = resolveEmployeeIdentityContext($supabaseUrl, $headers, $employeeUserId);
$employeeContextResolved = (bool)($employeeContext['is_valid'] ?? false);
$employeeContextError = cleanText($employeeContext['error'] ?? null);

if (!$employeeContextResolved) {
    renderEmployeeContextErrorAndExit($employeeContextError ?: 'Your account is missing a valid employee context.');
}
$employeePersonId = cleanText($employeeContext['person_id'] ?? null);
$employeeEmploymentId = cleanText($employeeContext['employment_id'] ?? null);
$employeeOfficeId = cleanText($employeeContext['office_id'] ?? null);
$employeeOfficeName = cleanText($employeeContext['office_name'] ?? null);
$employeePositionId = cleanText($employeeContext['position_id'] ?? null);
$employeePositionTitle = cleanText($employeeContext['position_title'] ?? null);
$employeeEmploymentStatus = cleanText($employeeContext['employment_status'] ?? null);
