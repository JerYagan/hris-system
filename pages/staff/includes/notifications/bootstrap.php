<?php

require_once __DIR__ . '/../lib/staff-backend.php';

$bootstrap = staffModuleBootstrapContext();
$supabaseUrl = (string)($bootstrap['supabase_url'] ?? '');
$serviceRoleKey = (string)($bootstrap['service_role_key'] ?? '');
$headers = (array)($bootstrap['headers'] ?? []);
$staffUserId = (string)($bootstrap['staff_user_id'] ?? '');
$staffContext = (array)($bootstrap['staff_context'] ?? []);
$staffContextIsValid = (bool)($staffContext['is_valid'] ?? false);
$staffContextError = cleanText($staffContext['error'] ?? null);
$csrfToken = (string)($bootstrap['csrf_token'] ?? '');

if ($supabaseUrl === '' || $serviceRoleKey === '' || empty($headers)) {
	renderStaffContextErrorAndExit('Supabase credentials are missing. Check your .env file.');
}

if (!$staffContextIsValid) {
	renderStaffContextErrorAndExit($staffContextError ?: 'Your account is missing a valid staff context.');
}

$staffRoleAssignmentId = cleanText($staffContext['role_assignment_id'] ?? null);
$staffRoleKey = cleanText($staffContext['role_key'] ?? null);
$staffRoleName = cleanText($staffContext['role_name'] ?? null);
$staffPersonId = cleanText($staffContext['person_id'] ?? null);
$staffEmploymentId = cleanText($staffContext['employment_id'] ?? null);
$staffOfficeId = cleanText($staffContext['office_id'] ?? null);
$staffOfficeName = cleanText($staffContext['office_name'] ?? null);
$staffPositionId = cleanText($staffContext['position_id'] ?? null);
$staffPositionTitle = cleanText($staffContext['position_title'] ?? null);
$staffEmploymentStatus = cleanText($staffContext['employment_status'] ?? null);
