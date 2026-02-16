<?php

$settingsValues = [];
$auditLogs = [];

if (settingsReady()) {
    $settingsResponse = apiRequest(
        'GET',
        settingsApiUrl('system_settings?select=setting_key,setting_value,updated_at'),
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

                $storedValue = $row['setting_value'] ?? null;
                $normalizedValue = '';

                if (is_array($storedValue) && array_key_exists('value', $storedValue)) {
                    $value = $storedValue['value'];
                    if (is_bool($value)) {
                        $normalizedValue = $value ? '1' : '0';
                    } elseif (is_scalar($value)) {
                        $normalizedValue = cleanText((string)$value) ?? '';
                    } elseif (is_array($value)) {
                        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $normalizedValue = $encoded !== false ? $encoded : '';
                    } else {
                        $normalizedValue = '';
                    }
                } elseif (is_bool($storedValue)) {
                    $normalizedValue = $storedValue ? '1' : '0';
                } elseif (is_scalar($storedValue)) {
                    $normalizedValue = cleanText((string)$storedValue) ?? '';
                } elseif (is_array($storedValue)) {
                    $encoded = json_encode($storedValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $normalizedValue = $encoded !== false ? $encoded : '';
                }

                $settingsValues[$settingKey] = $normalizedValue;
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
        $defaultValue = $settingsCatalog[$key]['default'];
        if (is_bool($defaultValue)) {
            return $defaultValue ? '1' : '0';
        }

        if (is_scalar($defaultValue)) {
            return (string) $defaultValue;
        }

        if (is_array($defaultValue)) {
            $encoded = json_encode($defaultValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $encoded !== false ? $encoded : '';
        }

        return '';
    }

    return '';
}

function isEnabled(string $key): bool
{
    return settingValue($key) === '1';
}
