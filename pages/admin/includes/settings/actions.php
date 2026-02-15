<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (!settingsReady()) {
    redirectWithState('error', 'Supabase configuration is missing.');
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';
if ($action === '') {
    return;
}

if (!function_exists('settingsCastValue')) {
    function settingsCastValue(string $value, string $type): string
    {
        $trimmed = trim($value);

        if ($type === 'number') {
            $numberValue = is_numeric($trimmed) ? (string)((int)$trimmed) : '0';
            return $numberValue;
        }

        if ($type === 'boolean') {
            $normalized = in_array(strtolower($trimmed), ['1', 'true', 'enabled', 'yes'], true) ? '1' : '0';
            return $normalized;
        }

        return $trimmed;
    }
}

if (!function_exists('settingsReadStoredValue')) {
    function settingsReadStoredValue(mixed $stored): string
    {
        if (is_array($stored) && array_key_exists('value', $stored)) {
            $value = $stored['value'];
            if (is_bool($value)) {
                return $value ? '1' : '0';
            }
            return cleanText((string)$value) ?? '';
        }

        if (is_bool($stored)) {
            return $stored ? '1' : '0';
        }

        if (is_scalar($stored)) {
            return cleanText((string)$stored) ?? '';
        }

        return '';
    }
}

$actionConfig = [
    'save_backup_settings' => [
        'keys' => ['backup_scope', 'backup_schedule', 'restore_file'],
        'event' => 'Updated backup settings',
        'message' => 'Backup settings saved successfully.',
    ],
    'save_security_settings' => [
        'keys' => ['password_min_length', 'login_lockout_threshold', 'session_timeout_minutes', 'two_factor_mode'],
        'event' => 'Updated security settings',
        'message' => 'Security settings saved successfully.',
    ],
    'save_notification_settings' => [
        'keys' => ['alerts_enabled', 'email_notifications_enabled', 'critical_alert_recipient', 'reminder_frequency'],
        'event' => 'Updated notification settings',
        'message' => 'Notification settings saved successfully.',
    ],
    'save_smtp_settings' => [
        'keys' => ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'smtp_auth', 'smtp_from_email', 'smtp_from_name'],
        'event' => 'Updated SMTP settings',
        'message' => 'SMTP settings saved successfully.',
    ],
];

if (!isset($actionConfig[$action])) {
    if ($action === 'send_smtp_test_email') {
        $recipientEmail = strtolower((string)(cleanText($_POST['smtp_test_recipient_email'] ?? null) ?? ''));
        if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            redirectWithState('error', 'Enter a valid recipient email for SMTP test delivery.');
        }

        $smtpHost = trim((string)(cleanText($_POST['smtp_host'] ?? null) ?? ''));
        $smtpPortRaw = trim((string)(cleanText($_POST['smtp_port'] ?? null) ?? '587'));
        $smtpUsername = trim((string)(cleanText($_POST['smtp_username'] ?? null) ?? ''));
        $smtpPassword = (string)($_POST['smtp_password'] ?? '');
        $smtpEncryption = strtolower((string)(cleanText($_POST['smtp_encryption'] ?? null) ?? 'tls'));
        $smtpAuth = ((string)(cleanText($_POST['smtp_auth'] ?? null) ?? '1')) === '0' ? '0' : '1';
        $smtpFromEmail = trim((string)(cleanText($_POST['smtp_from_email'] ?? null) ?? ''));
        $smtpFromName = trim((string)(cleanText($_POST['smtp_from_name'] ?? null) ?? 'DA HRIS'));

        if ($smtpPassword === '') {
            $existingPasswordResponse = apiRequest(
                'GET',
                settingsApiUrl('system_settings?select=setting_value&setting_key=eq.smtp_password&limit=1'),
                $headers
            );
            if (isSuccessful($existingPasswordResponse)) {
                $smtpPassword = settingsReadStoredValue($existingPasswordResponse['data'][0]['setting_value'] ?? null);
            }
        }

        $smtpConfig = [
            'host' => $smtpHost,
            'port' => is_numeric($smtpPortRaw) ? (int)$smtpPortRaw : 587,
            'username' => $smtpUsername,
            'password' => $smtpPassword,
            'encryption' => $smtpEncryption,
            'auth' => $smtpAuth,
        ];

        if (!smtpConfigIsReady($smtpConfig, $smtpFromEmail)) {
            redirectWithState('error', 'SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD, and MAIL_FROM are required for SMTP email sending.');
        }

        $subject = 'DA HRIS SMTP Test';
        $html = '<p>Hello,</p><p>This is an SMTP test email from DA HRIS Settings.</p><p>If you received this, SMTP configuration is working.</p>';

        $emailResponse = smtpSendTransactionalEmail(
            $smtpConfig,
            $smtpFromEmail,
            $smtpFromName,
            $recipientEmail,
            $recipientEmail,
            $subject,
            $html
        );

        if (!isSuccessful($emailResponse)) {
            $details = trim((string)($emailResponse['raw'] ?? ''));
            $message = 'SMTP send failed (HTTP ' . (int)($emailResponse['status'] ?? 0) . ').';
            if ($details !== '') {
                $message .= ' ' . $details;
            }
            redirectWithState('error', $message);
        }

        apiRequest(
            'POST',
            settingsApiUrl('activity_logs'),
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
                'module_name' => 'settings',
                'entity_name' => 'email_delivery',
                'entity_id' => null,
                'action_name' => 'send_smtp_test_email',
                'old_data' => null,
                'new_data' => ['recipient_email' => $recipientEmail, 'status_code' => (int)($emailResponse['status'] ?? 0)],
                'ip_address' => clientIp(),
            ]]
        );

        redirectWithState('success', 'SMTP test email sent to ' . $recipientEmail . '.');
    }

    redirectWithState('error', 'Unsupported settings action.');
}

$config = $actionConfig[$action];
$rows = [];

foreach ($config['keys'] as $key) {
    if (!array_key_exists($key, $settingsCatalog)) {
        continue;
    }

    $type = $settingsCatalog[$key]['type'];
    $default = (string) $settingsCatalog[$key]['default'];
    $incoming = cleanText($_POST[$key] ?? null) ?? $default;

    if ($action === 'save_smtp_settings' && $key === 'smtp_password' && trim($incoming) === '') {
        $existingPasswordResponse = apiRequest(
            'GET',
            settingsApiUrl('system_settings?select=setting_value&setting_key=eq.smtp_password&limit=1'),
            $headers
        );

        if (isSuccessful($existingPasswordResponse)) {
            $incoming = settingsReadStoredValue($existingPasswordResponse['data'][0]['setting_value'] ?? null);
        }
    }

    $castedValue = settingsCastValue($incoming, $type);

    $rows[] = [
        'setting_key' => $key,
        'setting_value' => ['value' => $castedValue],
        'updated_at' => date('c'),
    ];
}

if (empty($rows)) {
    redirectWithState('error', 'No settings payload to save.');
}

$settingsResponse = apiRequest(
    'POST',
    settingsApiUrl('system_settings?on_conflict=setting_key'),
    array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
    $rows
);

if (!isSuccessful($settingsResponse)) {
    $keys = array_map(static fn(array $row): string => (string)($row['setting_key'] ?? ''), $rows);
    $keys = array_values(array_filter($keys, static fn(string $key): bool => $key !== ''));

    $existing = [];
    if (!empty($keys)) {
        $existingResponse = apiRequest(
            'GET',
            settingsApiUrl('system_settings?select=setting_key&setting_key=in.(' . implode(',', $keys) . ')'),
            $headers
        );

        if (isSuccessful($existingResponse)) {
            foreach ((array)($existingResponse['data'] ?? []) as $existingRow) {
                $existingKey = cleanText($existingRow['setting_key'] ?? null) ?? '';
                if ($existingKey !== '') {
                    $existing[$existingKey] = true;
                }
            }
        }
    }

    $patchFailed = false;
    foreach ($rows as $row) {
        $settingKey = (string)($row['setting_key'] ?? '');
        if ($settingKey === '' || !isset($existing[$settingKey])) {
            continue;
        }

        $patchResponse = apiRequest(
            'PATCH',
            settingsApiUrl('system_settings?setting_key=eq.' . rawurlencode($settingKey)),
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'setting_value' => $row['setting_value'] ?? ['value' => ''],
                'updated_at' => (string)($row['updated_at'] ?? date('c')),
            ]
        );

        if (!isSuccessful($patchResponse)) {
            $patchFailed = true;
            break;
        }
    }

    if (!$patchFailed) {
        $insertRows = [];
        foreach ($rows as $row) {
            $settingKey = (string)($row['setting_key'] ?? '');
            if ($settingKey !== '' && !isset($existing[$settingKey])) {
                $insertRows[] = $row;
            }
        }

        if (!empty($insertRows)) {
            $insertResponse = apiRequest(
                'POST',
                settingsApiUrl('system_settings'),
                array_merge($headers, ['Prefer: return=minimal']),
                $insertRows
            );

            if (!isSuccessful($insertResponse)) {
                $details = trim((string)($insertResponse['raw'] ?? ''));
                $message = 'Failed to save settings.';
                if ($details !== '') {
                    $message .= ' ' . $details;
                }
                redirectWithState('error', $message);
            }
        }
    } else {
        $details = trim((string)($settingsResponse['raw'] ?? ''));
        $message = 'Failed to save settings.';
        if ($details !== '') {
            $message .= ' ' . $details;
        }
        redirectWithState('error', $message);
    }
}

$logPayload = [[
    'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
    'module_name' => 'settings',
    'entity_name' => 'system_settings',
    'entity_id' => null,
    'action_name' => 'update',
    'old_data' => null,
    'new_data' => ['action' => $action],
    'ip_address' => clientIp(),
]];

apiRequest(
    'POST',
    settingsApiUrl('activity_logs'),
    array_merge($headers, ['Prefer: return=minimal']),
    $logPayload
);

redirectWithState('success', $config['message']);
