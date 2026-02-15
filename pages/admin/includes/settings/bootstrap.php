<?php

require_once __DIR__ . '/../lib/admin-backend.php';
require_once __DIR__ . '/../notifications/email.php';

$backend = adminBackendContext();
$supabaseUrl = (string)($backend['supabase_url'] ?? '');
$serviceRoleKey = (string)($backend['service_role_key'] ?? '');
$headers = (array)($backend['headers'] ?? []);
$adminUserId = (string)($backend['admin_user_id'] ?? '');
$adminEmail = (string)($backend['admin_email'] ?? 'admin@da.gov.ph');

if ($supabaseUrl === '' || $serviceRoleKey === '') {
    redirectWithState('error', 'Supabase credentials are missing. Check your .env file.');
}

$settingsCatalog = [
    'backup_scope' => ['type' => 'text', 'default' => 'Full System Backup'],
    'backup_schedule' => ['type' => 'text', 'default' => 'Daily'],
    'restore_file' => ['type' => 'text', 'default' => ''],
    'password_min_length' => ['type' => 'number', 'default' => '10'],
    'login_lockout_threshold' => ['type' => 'number', 'default' => '5'],
    'session_timeout_minutes' => ['type' => 'number', 'default' => '30'],
    'two_factor_mode' => ['type' => 'text', 'default' => 'Enabled for Admin'],
    'alerts_enabled' => ['type' => 'boolean', 'default' => '1'],
    'email_notifications_enabled' => ['type' => 'boolean', 'default' => '1'],
    'critical_alert_recipient' => ['type' => 'text', 'default' => 'Admin Only'],
    'reminder_frequency' => ['type' => 'text', 'default' => 'Real-time'],
    'smtp_host' => ['type' => 'text', 'default' => ''],
    'smtp_port' => ['type' => 'number', 'default' => '587'],
    'smtp_username' => ['type' => 'text', 'default' => ''],
    'smtp_password' => ['type' => 'text', 'default' => ''],
    'smtp_encryption' => ['type' => 'text', 'default' => 'tls'],
    'smtp_auth' => ['type' => 'boolean', 'default' => '1'],
    'smtp_from_email' => ['type' => 'text', 'default' => ''],
    'smtp_from_name' => ['type' => 'text', 'default' => 'DA HRIS'],
];

function settingsApiUrl(string $pathWithQuery = ''): string
{
    global $supabaseUrl;
    return rtrim($supabaseUrl, '/') . '/rest/v1/' . ltrim($pathWithQuery, '/');
}

function settingsReady(): bool
{
    global $supabaseUrl, $serviceRoleKey;
    return $supabaseUrl !== '' && $serviceRoleKey !== '';
}
