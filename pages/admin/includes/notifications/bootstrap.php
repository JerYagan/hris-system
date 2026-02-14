<?php

require_once __DIR__ . '/../lib/admin-backend.php';
require_once __DIR__ . '/email.php';

$backend = adminBackendContext();
$supabaseUrl = (string)($backend['supabase_url'] ?? '');
$serviceRoleKey = (string)($backend['service_role_key'] ?? '');
$headers = (array)($backend['headers'] ?? []);
$adminUserId = (string)($backend['admin_user_id'] ?? '');

if ($supabaseUrl === '' || $serviceRoleKey === '') {
    redirectWithState('error', 'Supabase credentials are missing. Check your .env file.');
}

$notificationEmailProvider = 'brevo';
$notificationEmailProviderLabel = 'Brevo';

$mailApiKey = cleanText($_ENV['MAIL_API_KEY'] ?? ($_SERVER['MAIL_API_KEY'] ?? null)) ?? '';
$mailFrom = cleanText($_ENV['MAIL_FROM'] ?? ($_SERVER['MAIL_FROM'] ?? null)) ?? '';
$mailFromName = cleanText($_ENV['MAIL_FROM_NAME'] ?? ($_SERVER['MAIL_FROM_NAME'] ?? null)) ?? 'DA HRIS';
