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

$supportStatusOptions = ['submitted', 'in_review', 'forwarded_to_staff', 'resolved', 'rejected'];
$staffSupportUpdateTypes = ['progress_update', 'needs_more_info', 'recommend_resolution', 'recommend_rejection', 'escalated_to_admin'];
