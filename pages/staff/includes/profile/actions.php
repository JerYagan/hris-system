<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

if ($action !== 'update_staff_profile') {
    redirectWithState('error', 'Unknown profile action.');
}

$firstName = trim((string)(cleanText($_POST['first_name'] ?? null) ?? ''));
$middleName = trim((string)(cleanText($_POST['middle_name'] ?? null) ?? ''));
$surname = trim((string)(cleanText($_POST['surname'] ?? null) ?? ''));
$nameExtension = trim((string)(cleanText($_POST['name_extension'] ?? null) ?? ''));
$personalEmail = strtolower(trim((string)(cleanText($_POST['personal_email'] ?? null) ?? '')));
$mobileNo = trim((string)(cleanText($_POST['mobile_no'] ?? null) ?? ''));
$username = trim((string)(cleanText($_POST['username'] ?? null) ?? ''));

if ($firstName === '' || $surname === '') {
    redirectWithState('error', 'First name and surname are required.');
}

if ($personalEmail !== '' && !filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
    redirectWithState('error', 'Please provide a valid personal email.');
}

if ($mobileNo !== '' && !preg_match('/^\+?[0-9][0-9\s-]{6,19}$/', $mobileNo)) {
    redirectWithState('error', 'Please provide a valid contact number.');
}

$accountLookup = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_accounts?select=id,username,mobile_no&id=eq.' . rawurlencode($staffUserId) . '&limit=1',
    $headers
);
$accountRow = isSuccessful($accountLookup) ? ($accountLookup['data'][0] ?? null) : null;
if (!is_array($accountRow)) {
    redirectWithState('error', 'Account record not found.');
}

$personLookup = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/people?select=id,first_name,middle_name,surname,name_extension,personal_email,mobile_no&user_id=eq.' . rawurlencode($staffUserId) . '&limit=1',
    $headers
);
$personRow = isSuccessful($personLookup) ? ($personLookup['data'][0] ?? null) : null;

$personPayload = [
    'first_name' => $firstName,
    'middle_name' => $middleName !== '' ? $middleName : null,
    'surname' => $surname,
    'name_extension' => $nameExtension !== '' ? $nameExtension : null,
    'personal_email' => $personalEmail !== '' ? $personalEmail : null,
    'mobile_no' => $mobileNo !== '' ? $mobileNo : null,
    'updated_at' => gmdate('c'),
];

$personId = null;
if (is_array($personRow) && isValidUuid((string)($personRow['id'] ?? ''))) {
    $personId = (string)$personRow['id'];
    $personPatch = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/people?id=eq.' . rawurlencode($personId),
        array_merge($headers, ['Prefer: return=minimal']),
        $personPayload
    );

    if (!isSuccessful($personPatch)) {
        redirectWithState('error', 'Failed to update personal profile details.');
    }
} else {
    $insertPayload = [
        'user_id' => $staffUserId,
        'first_name' => $firstName,
        'middle_name' => $middleName !== '' ? $middleName : null,
        'surname' => $surname,
        'name_extension' => $nameExtension !== '' ? $nameExtension : null,
        'personal_email' => $personalEmail !== '' ? $personalEmail : null,
        'mobile_no' => $mobileNo !== '' ? $mobileNo : null,
        'agency_employee_no' => 'AUTO-' . strtoupper(substr(str_replace('-', '', $staffUserId), 0, 8)),
        'citizenship' => 'Filipino',
    ];

    $personInsert = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/people',
        array_merge($headers, ['Prefer: return=representation']),
        [$insertPayload]
    );

    if (!isSuccessful($personInsert)) {
        redirectWithState('error', 'Failed to initialize profile details.');
    }

    $personId = cleanText($personInsert['data'][0]['id'] ?? null);
}

$accountPatch = apiRequest(
    'PATCH',
    $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . rawurlencode($staffUserId),
    array_merge($headers, ['Prefer: return=minimal']),
    [
        'username' => $username !== '' ? $username : null,
        'mobile_no' => $mobileNo !== '' ? $mobileNo : null,
        'updated_at' => gmdate('c'),
    ]
);

if (!isSuccessful($accountPatch)) {
    redirectWithState('error', 'Profile was updated, but failed to save account preferences.');
}

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $staffUserId,
        'module_name' => 'profile',
        'entity_name' => 'user_accounts',
        'entity_id' => $staffUserId,
        'action_name' => 'update_staff_profile',
        'old_data' => [
            'username' => cleanText($accountRow['username'] ?? null),
            'mobile_no' => cleanText($accountRow['mobile_no'] ?? null),
            'person_first_name' => cleanText($personRow['first_name'] ?? null),
            'person_middle_name' => cleanText($personRow['middle_name'] ?? null),
            'person_surname' => cleanText($personRow['surname'] ?? null),
            'person_name_extension' => cleanText($personRow['name_extension'] ?? null),
            'person_personal_email' => cleanText($personRow['personal_email'] ?? null),
            'person_mobile_no' => cleanText($personRow['mobile_no'] ?? null),
        ],
        'new_data' => [
            'username' => $username,
            'mobile_no' => $mobileNo,
            'person_id' => $personId,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'surname' => $surname,
            'name_extension' => $nameExtension,
            'personal_email' => $personalEmail,
        ],
        'ip_address' => clientIp(),
    ]]
);

redirectWithState('success', 'Profile details updated successfully.');