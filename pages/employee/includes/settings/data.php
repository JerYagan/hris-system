<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);
$dataLoadError = null;

$employeeDisplayName = 'Employee';
$employeeEmail = '';
$employeeUsername = '';
$employeeMobileNo = '';
$employeePersonalEmail = '';
$employeeLastLoginAt = '';
$mustChangePassword = false;

$settingsPreferences = [
    'timezone' => 'Asia/Manila',
    'date_format' => 'M j, Y',
    'theme' => 'system',
];

$notificationPreferences = [
    'system_alerts' => true,
    'hr_announcements' => true,
    'evaluation_updates' => true,
];

if (!(bool)($employeeContextResolved ?? false)) {
    $dataLoadError = (string)($employeeContextError ?? 'Employee context could not be resolved.');
    return;
}

$userAccountResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/user_accounts?select=id,email,username,mobile_no,last_login_at,must_change_password'
    . '&id=eq.' . rawurlencode((string)$employeeUserId)
    . '&limit=1',
    $headers
);

if (!isSuccessful($userAccountResponse) || empty((array)($userAccountResponse['data'] ?? []))) {
    $dataLoadError = 'Unable to load account settings right now.';
    return;
}

$userAccount = (array)$userAccountResponse['data'][0];
$employeeEmail = (string)($userAccount['email'] ?? '');
$employeeUsername = (string)($userAccount['username'] ?? '');
$employeeMobileNo = (string)($userAccount['mobile_no'] ?? '');
$employeeLastLoginAt = (string)($userAccount['last_login_at'] ?? '');
$mustChangePassword = (bool)($userAccount['must_change_password'] ?? false);

$personResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/people?select=id,first_name,surname,personal_email,mobile_no'
    . '&id=eq.' . rawurlencode((string)$employeePersonId)
    . '&limit=1',
    $headers
);

if (isSuccessful($personResponse) && !empty((array)($personResponse['data'] ?? []))) {
    $person = (array)$personResponse['data'][0];
    $employeeDisplayName = trim((string)($person['first_name'] ?? '') . ' ' . (string)($person['surname'] ?? ''));
    if ($employeeDisplayName === '') {
        $employeeDisplayName = 'Employee';
    }

    $employeePersonalEmail = (string)($person['personal_email'] ?? '');
    if ($employeePersonalEmail === '') {
        $employeePersonalEmail = $employeeEmail;
    }

    $personMobile = (string)($person['mobile_no'] ?? '');
    if ($personMobile !== '') {
        $employeeMobileNo = $personMobile;
    }
}

$settingsKey = 'employee_settings_' . $employeeUserId;
$settingsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/system_settings?select=setting_value,updated_at'
    . '&setting_key=eq.' . rawurlencode($settingsKey)
    . '&limit=1',
    $headers
);

if (isSuccessful($settingsResponse) && !empty((array)($settingsResponse['data'] ?? []))) {
    $settingsRow = (array)$settingsResponse['data'][0];
    $settingValue = (array)($settingsRow['setting_value'] ?? []);
    $prefs = (array)($settingValue['preferences'] ?? []);
    $notif = (array)($settingValue['notifications'] ?? []);

    $settingsPreferences['timezone'] = (string)($prefs['timezone'] ?? $settingsPreferences['timezone']);
    $settingsPreferences['date_format'] = (string)($prefs['date_format'] ?? $settingsPreferences['date_format']);
    $settingsPreferences['theme'] = (string)($prefs['theme'] ?? $settingsPreferences['theme']);

    $notificationPreferences['system_alerts'] = (bool)($notif['system_alerts'] ?? $notificationPreferences['system_alerts']);
    $notificationPreferences['hr_announcements'] = (bool)($notif['hr_announcements'] ?? $notificationPreferences['hr_announcements']);
    $notificationPreferences['evaluation_updates'] = (bool)($notif['evaluation_updates'] ?? $notificationPreferences['evaluation_updates']);
}
