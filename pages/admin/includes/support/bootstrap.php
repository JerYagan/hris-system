<?php

require_once __DIR__ . '/../lib/admin-backend.php';

$backend = adminBackendContext();
$supabaseUrl = (string)($backend['supabase_url'] ?? '');
$serviceRoleKey = (string)($backend['service_role_key'] ?? '');
$headers = (array)($backend['headers'] ?? []);
$adminUserId = (string)($backend['admin_user_id'] ?? '');
$adminEmail = (string)($backend['admin_email'] ?? 'admin@da.gov.ph');

if ($supabaseUrl === '' || $serviceRoleKey === '') {
    redirectWithState('error', 'Supabase credentials are missing. Check your .env file.', 'support.php');
}

$supportStatusOptions = ['submitted', 'in_review', 'forwarded_to_staff', 'resolved', 'rejected'];
