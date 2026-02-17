<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

if (!(bool)($employeeContextResolved ?? false)) {
    redirectWithState('error', (string)($employeeContextError ?? 'Employee context could not be resolved.'), 'settings.php');
}

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.', 'settings.php');
}

$action = strtolower((string)cleanText($_POST['action'] ?? ''));
if (!in_array($action, ['update_account_preferences', 'update_notification_preferences'], true)) {
    redirectWithState('error', 'Unsupported settings action.', 'settings.php');
}

$toNullable = static function (mixed $value, int $maxLength = 255): ?string {
    $text = cleanText($value);
    if ($text === null) {
        return null;
    }

    if (mb_strlen($text) > $maxLength) {
        $text = mb_substr($text, 0, $maxLength);
    }

    return $text;
};

$settingsKey = 'employee_settings_' . $employeeUserId;
$allowedTimezones = ['Asia/Manila', 'UTC'];
$allowedDateFormats = ['M j, Y', 'Y-m-d', 'm/d/Y'];
$allowedThemes = ['system', 'light', 'dark'];

$existingSettingsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/system_settings?select=id,setting_value'
    . '&setting_key=eq.' . rawurlencode($settingsKey)
    . '&limit=1',
    $headers
);

$existingSettings = [];
$existingSettingsId = null;

if (isSuccessful($existingSettingsResponse) && !empty((array)($existingSettingsResponse['data'] ?? []))) {
    $existing = (array)$existingSettingsResponse['data'][0];
    $existingSettings = (array)($existing['setting_value'] ?? []);
    $existingSettingsId = cleanText($existing['id'] ?? null);
}

$existingPreferences = (array)($existingSettings['preferences'] ?? []);
$existingNotifications = (array)($existingSettings['notifications'] ?? []);

$saveSettings = static function (array $newSettingValue) use ($supabaseUrl, $headers, $settingsKey, $existingSettingsId, $employeeUserId) {
    if ($existingSettingsId !== null && isValidUuid($existingSettingsId)) {
        return apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/system_settings?id=eq.' . rawurlencode($existingSettingsId),
            $headers,
            [
                'setting_value' => $newSettingValue,
                'updated_at' => date('c'),
            ]
        );
    }

    return apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/system_settings',
        $headers,
        [[
            'setting_key' => $settingsKey,
            'setting_value' => $newSettingValue,
            'updated_by' => $employeeUserId,
            'updated_at' => date('c'),
        ]]
    );
};

if ($action === 'update_account_preferences') {
    $mobileNo = $toNullable($_POST['mobile_no'] ?? null, 30);
    $personalEmail = $toNullable($_POST['personal_email'] ?? null, 120);
    $timezone = (string)($toNullable($_POST['timezone'] ?? null, 40) ?? 'Asia/Manila');
    $dateFormat = (string)($toNullable($_POST['date_format'] ?? null, 20) ?? 'M j, Y');
    $theme = (string)($toNullable($_POST['theme'] ?? null, 20) ?? 'system');

    if (!in_array($timezone, $allowedTimezones, true) || !in_array($dateFormat, $allowedDateFormats, true) || !in_array($theme, $allowedThemes, true)) {
        redirectWithState('error', 'Invalid preference selection.', 'settings.php');
    }

    if ($personalEmail !== null && !filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
        redirectWithState('error', 'Personal email address is invalid.', 'settings.php');
    }

    if ($mobileNo !== null && !preg_match('/^[0-9+\-()\s]{7,30}$/', $mobileNo)) {
        redirectWithState('error', 'Mobile number format is invalid.', 'settings.php');
    }

    $userAccountUpdateResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . rawurlencode((string)$employeeUserId),
        $headers,
        [
            'mobile_no' => $mobileNo,
        ]
    );

    if (!isSuccessful($userAccountUpdateResponse)) {
        redirectWithState('error', 'Unable to update account contact number.', 'settings.php');
    }

    $personUpdateResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/people?id=eq.' . rawurlencode((string)$employeePersonId),
        $headers,
        [
            'personal_email' => $personalEmail,
            'mobile_no' => $mobileNo,
        ]
    );

    if (!isSuccessful($personUpdateResponse)) {
        redirectWithState('error', 'Unable to update profile contact details.', 'settings.php');
    }

    $mergedSettings = [
        'preferences' => [
            'timezone' => $timezone,
            'date_format' => $dateFormat,
            'theme' => $theme,
        ],
        'notifications' => [
            'system_alerts' => (bool)($existingNotifications['system_alerts'] ?? true),
            'hr_announcements' => (bool)($existingNotifications['hr_announcements'] ?? true),
            'evaluation_updates' => (bool)($existingNotifications['evaluation_updates'] ?? true),
        ],
    ];

    $settingsSaveResponse = $saveSettings($mergedSettings);
    if (!isSuccessful($settingsSaveResponse)) {
        redirectWithState('error', 'Unable to save account preference settings.', 'settings.php');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        $headers,
        [[
            'actor_user_id' => $employeeUserId,
            'module_name' => 'employee',
            'entity_name' => 'system_settings',
            'action_name' => 'update_account_preferences',
            'new_data' => [
                'mobile_no' => $mobileNo,
                'personal_email' => $personalEmail,
                'timezone' => $timezone,
                'date_format' => $dateFormat,
                'theme' => $theme,
            ],
        ]]
    );

    redirectWithState('success', 'Account preferences updated successfully.', 'settings.php');
}

$systemAlerts = isset($_POST['notify_system_alerts']);
$hrAnnouncements = isset($_POST['notify_hr_announcements']);
$evaluationUpdates = isset($_POST['notify_evaluation_updates']);

$mergedSettings = [
    'preferences' => [
        'timezone' => (string)($existingPreferences['timezone'] ?? 'Asia/Manila'),
        'date_format' => (string)($existingPreferences['date_format'] ?? 'M j, Y'),
        'theme' => (string)($existingPreferences['theme'] ?? 'system'),
    ],
    'notifications' => [
        'system_alerts' => $systemAlerts,
        'hr_announcements' => $hrAnnouncements,
        'evaluation_updates' => $evaluationUpdates,
    ],
];

$settingsSaveResponse = $saveSettings($mergedSettings);
if (!isSuccessful($settingsSaveResponse)) {
    redirectWithState('error', 'Unable to save notification preferences.', 'settings.php');
}

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    $headers,
    [[
        'actor_user_id' => $employeeUserId,
        'module_name' => 'employee',
        'entity_name' => 'system_settings',
        'action_name' => 'update_notification_preferences',
        'new_data' => [
            'system_alerts' => $systemAlerts,
            'hr_announcements' => $hrAnnouncements,
            'evaluation_updates' => $evaluationUpdates,
        ],
    ]]
);

redirectWithState('success', 'Notification preferences updated successfully.', 'settings.php');
