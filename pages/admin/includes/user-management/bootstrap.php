<?php

require_once __DIR__ . '/../lib/admin-backend.php';
require_once __DIR__ . '/../notifications/email.php';

$backend = adminBackendContext();
$supabaseUrl = (string)($backend['supabase_url'] ?? '');
$serviceRoleKey = (string)($backend['service_role_key'] ?? '');
$headers = (array)($backend['headers'] ?? []);
$adminUserId = (string)($backend['admin_user_id'] ?? '');

if ($supabaseUrl === '' || $serviceRoleKey === '') {
    redirectWithState('error', 'Supabase credentials are missing. Check your .env file.');
}

$smtpConfig = [
    'host' => cleanText($_ENV['SMTP_HOST'] ?? ($_SERVER['SMTP_HOST'] ?? null)) ?? '',
    'port' => (int)(cleanText($_ENV['SMTP_PORT'] ?? ($_SERVER['SMTP_PORT'] ?? null)) ?? '587'),
    'username' => cleanText($_ENV['SMTP_USERNAME'] ?? ($_SERVER['SMTP_USERNAME'] ?? null)) ?? '',
    'password' => (string)($_ENV['SMTP_PASSWORD'] ?? ($_SERVER['SMTP_PASSWORD'] ?? '')),
    'encryption' => strtolower((string)(cleanText($_ENV['SMTP_ENCRYPTION'] ?? ($_SERVER['SMTP_ENCRYPTION'] ?? null)) ?? 'tls')),
    'auth' => (string)(cleanText($_ENV['SMTP_AUTH'] ?? ($_SERVER['SMTP_AUTH'] ?? null)) ?? '1'),
];

$mailFrom = cleanText($_ENV['MAIL_FROM'] ?? ($_SERVER['MAIL_FROM'] ?? null)) ?? '';
$mailFromName = cleanText($_ENV['MAIL_FROM_NAME'] ?? ($_SERVER['MAIL_FROM_NAME'] ?? null)) ?? 'DA HRIS';

$resolvedMailConfig = resolveSmtpMailConfig($supabaseUrl, $headers, $smtpConfig, $mailFrom, $mailFromName);
$smtpConfig = (array)($resolvedMailConfig['smtp'] ?? $smtpConfig);
$mailFrom = (string)($resolvedMailConfig['from'] ?? $mailFrom);
$mailFromName = (string)($resolvedMailConfig['from_name'] ?? $mailFromName);

if (!function_exists('userManagementEmploymentClassificationPolicy')) {
    function userManagementEmploymentClassificationPolicy(): array
    {
        return [
            'regular' => 'Regular',
            'coterminous' => 'Coterminous',
            'contractual' => 'Contractual',
            'casual' => 'Casual',
            'job_order' => 'Job Order',
        ];
    }
}

if (!function_exists('userManagementAssignableRolePolicy')) {
    function userManagementAssignableRolePolicy(): array
    {
        return [
            'admin',
            'staff',
            'employee',
            'applicant',
        ];
    }
}

if (!function_exists('userManagementClearMetaCache')) {
    function userManagementClearMetaCache(): void
    {
        unset($_SESSION['admin_user_management_meta_cache']);
    }
}
