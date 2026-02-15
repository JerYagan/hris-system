<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = (string)($_POST['form_action'] ?? '');

if ($action === 'update_profile_details') {
    $firstName = cleanText($_POST['first_name'] ?? null) ?? '';
    $middleName = cleanText($_POST['middle_name'] ?? null);
    $surname = cleanText($_POST['surname'] ?? null) ?? '';
    $nameExtension = cleanText($_POST['name_extension'] ?? null);
    $mobileNo = cleanText($_POST['mobile_no'] ?? null);
    $personalEmail = cleanText($_POST['personal_email'] ?? null);

    if ($firstName === '' || $surname === '') {
        redirectWithState('error', 'First name and surname are required.');
    }

    $personLookup = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,first_name,middle_name,surname,name_extension,mobile_no,personal_email&user_id=eq.' . $adminUserId . '&limit=1',
        $headers
    );

    $personRow = $personLookup['data'][0] ?? null;

    $newPayload = [
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'surname' => $surname,
        'name_extension' => $nameExtension,
        'mobile_no' => $mobileNo,
        'personal_email' => $personalEmail,
    ];

    if (is_array($personRow) && !empty($personRow['id'])) {
        $personId = (string)$personRow['id'];

        $patchResponse = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/people?id=eq.' . $personId,
            array_merge($headers, ['Prefer: return=minimal']),
            $newPayload
        );

        if (!isSuccessful($patchResponse)) {
            redirectWithState('error', 'Failed to update profile details.');
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $adminUserId,
                'module_name' => 'profile',
                'entity_name' => 'people',
                'entity_id' => $personId,
                'action_name' => 'update_profile_details',
                'old_data' => [
                    'first_name' => (string)($personRow['first_name'] ?? ''),
                    'middle_name' => $personRow['middle_name'] ?? null,
                    'surname' => (string)($personRow['surname'] ?? ''),
                    'name_extension' => $personRow['name_extension'] ?? null,
                    'mobile_no' => $personRow['mobile_no'] ?? null,
                    'personal_email' => $personRow['personal_email'] ?? null,
                ],
                'new_data' => $newPayload,
                'ip_address' => clientIp(),
            ]]
        );

        redirectWithState('success', 'Profile details updated successfully.');
    }

    $accountLookup = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_accounts?select=email&id=eq.' . $adminUserId . '&limit=1',
        $headers
    );

    $accountRow = $accountLookup['data'][0] ?? [];

    $insertPayload = array_merge($newPayload, [
        'user_id' => $adminUserId,
        'agency_employee_no' => 'AUTO-' . strtoupper(substr(str_replace('-', '', $adminUserId), 0, 8)),
        'citizenship' => 'Filipino',
        'telephone_no' => null,
        'date_of_birth' => null,
        'place_of_birth' => null,
        'sex_at_birth' => null,
        'civil_status' => null,
    ]);

    if (($insertPayload['personal_email'] ?? null) === null) {
        $insertPayload['personal_email'] = cleanText($accountRow['email'] ?? null);
    }

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/people',
        array_merge($headers, ['Prefer: return=representation']),
        [$insertPayload]
    );

    if (!isSuccessful($insertResponse)) {
        redirectWithState('error', 'Failed to initialize profile details.');
    }

    $createdPerson = $insertResponse['data'][0] ?? [];
    $createdPersonId = (string)($createdPerson['id'] ?? '');

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'profile',
            'entity_name' => 'people',
            'entity_id' => $createdPersonId !== '' ? $createdPersonId : null,
            'action_name' => 'create_profile_details',
            'old_data' => null,
            'new_data' => $insertPayload,
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Profile details created successfully.');
}

if ($action === 'update_account_preferences') {
    $username = cleanText($_POST['username'] ?? null);
    $mobileNo = cleanText($_POST['mobile_no'] ?? null);

    $accountLookup = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_accounts?select=id,username,mobile_no&id=eq.' . $adminUserId . '&limit=1',
        $headers
    );

    $accountRow = $accountLookup['data'][0] ?? null;
    if (!is_array($accountRow)) {
        redirectWithState('error', 'Account record not found.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . $adminUserId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'username' => $username,
            'mobile_no' => $mobileNo,
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update account preferences.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'profile',
            'entity_name' => 'user_accounts',
            'entity_id' => $adminUserId,
            'action_name' => 'update_account_preferences',
            'old_data' => [
                'username' => $accountRow['username'] ?? null,
                'mobile_no' => $accountRow['mobile_no'] ?? null,
            ],
            'new_data' => [
                'username' => $username,
                'mobile_no' => $mobileNo,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Account preferences updated successfully.');
}

if ($action !== '') {
    redirectWithState('error', 'Unknown profile action.');
}
