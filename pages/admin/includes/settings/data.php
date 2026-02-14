<?php

$settingsValues = [];
$auditLogs = [];

if (settingsReady()) {
    $settingsResponse = apiRequest(
        'GET',
        settingsApiUrl('system_settings?select=setting_key,value_text,value_json,updated_at'),
        $headers
    );

    if (isSuccessful($settingsResponse)) {
        $settingsRows = (array)($settingsResponse['data'] ?? []);
        if (is_array($settingsRows)) {
            foreach ($settingsRows as $row) {
                $settingKey = cleanText($row['setting_key'] ?? '');
                if ($settingKey === '') {
                    continue;
                }

                $settingsValues[$settingKey] = cleanText($row['value_text'] ?? '');
            }
        }
    }

    $auditResponse = apiRequest(
        'GET',
        settingsApiUrl('activity_logs?select=created_at,module_name,action_name,actor_user_id&module_name=eq.settings&order=created_at.desc&limit=25'),
        $headers
    );

    if (isSuccessful($auditResponse)) {
        $auditRows = (array)($auditResponse['data'] ?? []);
        if (is_array($auditRows)) {
            foreach ($auditRows as $row) {
                $moduleName = cleanText($row['module_name'] ?? 'settings') ?? 'settings';
                $actionName = cleanText($row['action_name'] ?? 'update') ?? 'update';

                $auditLogs[] = [
                    'created_at' => cleanText($row['created_at'] ?? ''),
                    'user' => cleanText((string)($row['actor_user_id'] ?? $adminEmail)),
                    'description' => ucwords(str_replace('_', ' ', $actionName)) . ' settings',
                    'module' => ucwords(str_replace('_', ' ', $moduleName)),
                ];
            }
        }
    }
}

function settingValue(string $key): string
{
    global $settingsValues, $settingsCatalog;

    if (isset($settingsValues[$key]) && $settingsValues[$key] !== '') {
        return (string) $settingsValues[$key];
    }

    if (isset($settingsCatalog[$key]['default'])) {
        return (string) $settingsCatalog[$key]['default'];
    }

    return '';
}

function isEnabled(string $key): bool
{
    return settingValue($key) === '1';
}
