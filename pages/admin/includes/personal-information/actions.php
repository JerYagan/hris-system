<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (!function_exists('isValidUuid')) {
    function isValidUuid(string $value): bool
    {
        return (bool)preg_match('/^[a-f0-9-]{36}$/i', $value);
    }
}

$action = (string)($_POST['form_action'] ?? '');

if ($action === 'save_profile') {
    $profileAction = strtolower((string)(cleanText($_POST['profile_action'] ?? null) ?? 'edit'));
    $personId = cleanText($_POST['person_id'] ?? null);
    $employeeName = (string)(cleanText($_POST['employee_name'] ?? null) ?? '');
    $email = strtolower((string)(cleanText($_POST['email'] ?? null) ?? ''));
    $mobileNo = (string)(cleanText($_POST['mobile_no'] ?? null) ?? '');
    $profileNotes = cleanText($_POST['profile_notes'] ?? null);

    if (!in_array($profileAction, ['add', 'edit', 'archive'], true)) {
        redirectWithState('error', 'Invalid profile action selected.');
    }

    if ($profileAction === 'add') {
        if ($employeeName === '') {
            redirectWithState('error', 'Employee name is required to add profile.');
        }

        [$firstName, $surname] = splitFullName($employeeName);
        $insertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/people',
            array_merge($headers, ['Prefer: return=representation']),
            [[
                'first_name' => $firstName,
                'surname' => $surname,
                'personal_email' => $email !== '' ? $email : null,
                'mobile_no' => $mobileNo !== '' ? $mobileNo : null,
            ]]
        );

        if (!isSuccessful($insertResponse)) {
            redirectWithState('error', 'Failed to create employee profile.');
        }

        $newPersonId = (string)($insertResponse['data'][0]['id'] ?? '');
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
                'module_name' => 'personal_information',
                'entity_name' => 'people',
                'entity_id' => $newPersonId !== '' ? $newPersonId : null,
                'action_name' => 'add_profile',
                'old_data' => null,
                'new_data' => [
                    'employee_name' => $employeeName,
                    'email' => $email,
                    'mobile_no' => $mobileNo,
                    'notes' => $profileNotes,
                ],
                'ip_address' => clientIp(),
            ]]
        );

        redirectWithState('success', 'Employee profile created successfully.');
    }

    if (!isValidUuid((string)$personId)) {
        redirectWithState('error', 'Please select a valid employee profile.');
    }

    $personResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,surname,mobile_no,personal_email&id=eq.' . $personId . '&limit=1',
        $headers
    );

    $personRow = $personResponse['data'][0] ?? null;
    if (!is_array($personRow)) {
        redirectWithState('error', 'Employee profile not found.');
    }

    if ($profileAction === 'edit') {
        if ($employeeName === '') {
            redirectWithState('error', 'Employee name is required for profile update.');
        }

        [$firstName, $surname] = splitFullName($employeeName);
        $patchResponse = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/people?id=eq.' . $personId,
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'first_name' => $firstName,
                'surname' => $surname,
                'personal_email' => $email !== '' ? $email : null,
                'mobile_no' => $mobileNo !== '' ? $mobileNo : null,
                'updated_at' => gmdate('c'),
            ]
        );

        if (!isSuccessful($patchResponse)) {
            redirectWithState('error', 'Failed to update employee profile.');
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
                'module_name' => 'personal_information',
                'entity_name' => 'people',
                'entity_id' => $personId,
                'action_name' => 'edit_profile',
                'old_data' => [
                    'first_name' => $personRow['first_name'] ?? null,
                    'surname' => $personRow['surname'] ?? null,
                    'personal_email' => $personRow['personal_email'] ?? null,
                    'mobile_no' => $personRow['mobile_no'] ?? null,
                ],
                'new_data' => [
                    'employee_name' => $employeeName,
                    'email' => $email,
                    'mobile_no' => $mobileNo,
                    'notes' => $profileNotes,
                ],
                'ip_address' => clientIp(),
            ]]
        );

        redirectWithState('success', 'Employee profile updated successfully.');
    }

    $userId = (string)($personRow['user_id'] ?? '');
    if ($userId !== '') {
        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . $userId,
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'account_status' => 'archived',
                'lockout_until' => gmdate('c'),
            ]
        );
    }

    apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/employment_records?person_id=eq.' . $personId . '&is_current=eq.true',
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'is_current' => false,
            'employment_status' => 'resigned',
            'separation_date' => gmdate('Y-m-d'),
            'separation_reason' => $profileNotes,
            'updated_at' => gmdate('c'),
        ]
    );

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'personal_information',
            'entity_name' => 'people',
            'entity_id' => $personId,
            'action_name' => 'archive_profile',
            'old_data' => [
                'user_id' => $personRow['user_id'] ?? null,
                'first_name' => $personRow['first_name'] ?? null,
                'surname' => $personRow['surname'] ?? null,
            ],
            'new_data' => [
                'account_status' => 'archived',
                'employment_status' => 'resigned',
                'reason' => $profileNotes,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Employee profile archived successfully.');
}

if ($action === 'assign_department_position') {
    $personId = cleanText($_POST['person_id'] ?? null) ?? '';
    $officeId = cleanText($_POST['office_id'] ?? null) ?? '';
    $positionId = cleanText($_POST['position_id'] ?? null) ?? '';

    if (!isValidUuid($personId) || !isValidUuid($officeId) || !isValidUuid($positionId)) {
        redirectWithState('error', 'Employee, department, and position are required.');
    }

    $currentEmployment = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employment_records?select=id,office_id,position_id,employment_status&person_id=eq.' . $personId . '&is_current=eq.true&limit=1',
        $headers
    );

    $currentRow = $currentEmployment['data'][0] ?? null;
    if (is_array($currentRow) && isValidUuid((string)($currentRow['id'] ?? ''))) {
        $patchResponse = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/employment_records?id=eq.' . (string)$currentRow['id'],
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'office_id' => $officeId,
                'position_id' => $positionId,
                'updated_at' => gmdate('c'),
            ]
        );

        if (!isSuccessful($patchResponse)) {
            redirectWithState('error', 'Failed to update employee assignment.');
        }
    } else {
        $createResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/employment_records',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'person_id' => $personId,
                'office_id' => $officeId,
                'position_id' => $positionId,
                'hire_date' => gmdate('Y-m-d'),
                'employment_status' => 'active',
                'is_current' => true,
            ]]
        );

        if (!isSuccessful($createResponse)) {
            redirectWithState('error', 'Failed to create employee assignment record.');
        }
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'personal_information',
            'entity_name' => 'employment_records',
            'entity_id' => null,
            'action_name' => 'assign_department_position',
            'old_data' => is_array($currentRow)
                ? [
                    'office_id' => $currentRow['office_id'] ?? null,
                    'position_id' => $currentRow['position_id'] ?? null,
                    'employment_status' => $currentRow['employment_status'] ?? null,
                ]
                : null,
            'new_data' => [
                'person_id' => $personId,
                'office_id' => $officeId,
                'position_id' => $positionId,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Department and position assignment updated.');
}

if ($action === 'update_employee_status') {
    $personId = cleanText($_POST['person_id'] ?? null) ?? '';
    $newStatus = strtolower((string)(cleanText($_POST['new_status'] ?? null) ?? 'active'));
    $statusSpecification = cleanText($_POST['status_specification'] ?? null);

    if (!isValidUuid($personId)) {
        redirectWithState('error', 'Please select a valid employee.');
    }

    if (!in_array($newStatus, ['active', 'inactive'], true)) {
        redirectWithState('error', 'Invalid status selected.');
    }

    $employmentResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employment_records?select=id,employment_status,is_current&person_id=eq.' . $personId . '&is_current=eq.true&limit=1',
        $headers
    );

    $employmentRow = $employmentResponse['data'][0] ?? null;
    if (!is_array($employmentRow)) {
        redirectWithState('error', 'No active employment record found for selected employee.');
    }

    $mappedStatus = $newStatus === 'active' ? 'active' : 'resigned';
    $patchPayload = [
        'employment_status' => $mappedStatus,
        'updated_at' => gmdate('c'),
    ];

    if ($mappedStatus !== 'active') {
        $patchPayload['separation_date'] = gmdate('Y-m-d');
        $patchPayload['separation_reason'] = $statusSpecification;
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/employment_records?id=eq.' . (string)$employmentRow['id'],
        array_merge($headers, ['Prefer: return=minimal']),
        $patchPayload
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update employee status.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'personal_information',
            'entity_name' => 'employment_records',
            'entity_id' => (string)($employmentRow['id'] ?? ''),
            'action_name' => 'update_employee_status',
            'old_data' => ['employment_status' => $employmentRow['employment_status'] ?? null],
            'new_data' => [
                'employment_status' => $mappedStatus,
                'status_specification' => $statusSpecification,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Employee status updated successfully.');
}

redirectWithState('error', 'Unknown personal information action.');
