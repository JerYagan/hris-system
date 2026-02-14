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
    function settingsCastValue(string $value, string $type): array
    {
        $trimmed = trim($value);

        if ($type === 'number') {
            $numberValue = is_numeric($trimmed) ? (string)((int)$trimmed) : '0';
            return ['value_text' => $numberValue, 'value_json' => null];
        }

        if ($type === 'boolean') {
            $normalized = in_array(strtolower($trimmed), ['1', 'true', 'enabled', 'yes'], true) ? '1' : '0';
            return ['value_text' => $normalized, 'value_json' => null];
        }

        return ['value_text' => $trimmed, 'value_json' => null];
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
];

if (!isset($actionConfig[$action])) {
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
    $casted = settingsCastValue($incoming, $type);

    $rows[] = [
        'setting_key' => $key,
        'value_text' => $casted['value_text'],
        'value_json' => $casted['value_json'],
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
    redirectWithState('error', 'Failed to save settings.');
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
