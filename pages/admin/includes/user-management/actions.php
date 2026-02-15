<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (!function_exists('userManagementIsValidUuid')) {
    function userManagementIsValidUuid(string $value): bool
    {
        return (bool)preg_match('/^[a-f0-9-]{36}$/i', $value);
    }
}

if (!function_exists('userManagementIsValidCode')) {
    function userManagementIsValidCode(string $value): bool
    {
        return (bool)preg_match('/^[A-Z0-9][A-Z0-9\-_]{1,19}$/', strtoupper($value));
    }
}

$action = (string)($_POST['form_action'] ?? '');

if ($action === 'account') {
    $accountAction = strtolower((string)($_POST['account_action'] ?? 'add'));
    $email = strtolower((string)(cleanText($_POST['email'] ?? null) ?? ''));
    $fullName = (string)(cleanText($_POST['full_name'] ?? null) ?? '');
    $officeId = cleanText($_POST['office_id'] ?? null);
    $notes = cleanText($_POST['account_notes'] ?? null);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithState('error', 'Valid email is required.');
    }

    if ($accountAction === 'archive') {
        $targetResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/user_accounts?select=id,email&email=eq.' . encodeFilter($email) . '&limit=1',
            $headers
        );

        $targetUserId = (string)($targetResponse['data'][0]['id'] ?? '');
        if ($targetUserId === '') {
            redirectWithState('error', 'No account found for the provided email.');
        }

        $archiveResponse = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . $targetUserId,
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'account_status' => 'archived',
                'lockout_until' => gmdate('c'),
            ]
        );

        if (!isSuccessful($archiveResponse)) {
            redirectWithState('error', 'Failed to archive account.');
        }

        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/user_role_assignments?user_id=eq.' . $targetUserId . '&expires_at=is.null',
            array_merge($headers, ['Prefer: return=minimal']),
            ['expires_at' => gmdate('c'), 'is_primary' => false]
        );

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $adminUserId,
                'module_name' => 'user_management',
                'entity_name' => 'user_accounts',
                'entity_id' => $targetUserId,
                'action_name' => 'archive_account',
                'old_data' => null,
                'new_data' => [
                    'email' => $email,
                    'notes' => $notes,
                ],
                'ip_address' => clientIp(),
            ]]
        );

        redirectWithState('success', 'User account archived successfully.');
    }

    if ($fullName === '') {
        redirectWithState('error', 'Full name is required for new account.');
    }

    [$firstName, $surname] = splitFullName($fullName);
    $tempPassword = 'Temp#' . substr(bin2hex(random_bytes(6)), 0, 10);

    $createAuth = apiRequest(
        'POST',
        $supabaseUrl . '/auth/v1/admin/users',
        $headers,
        [
            'email' => $email,
            'password' => $tempPassword,
            'email_confirm' => true,
            'user_metadata' => [
                'full_name' => $fullName,
                'created_by_admin' => $adminUserId,
            ],
        ]
    );

    if (!isSuccessful($createAuth)) {
        $raw = strtolower((string)$createAuth['raw']);
        if (str_contains($raw, 'already') || str_contains($raw, 'exists')) {
            redirectWithState('error', 'Email already exists in authentication.');
        }
        redirectWithState('error', 'Failed to create authentication user.');
    }

    $newUserId = (string)($createAuth['data']['id'] ?? '');
    if ($newUserId === '') {
        redirectWithState('error', 'Invalid auth response when creating account.');
    }

    $accountResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/user_accounts',
        array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
        [[
            'id' => $newUserId,
            'email' => $email,
            'account_status' => 'active',
            'email_verified_at' => gmdate('c'),
            'must_change_password' => true,
        ]]
    );

    if (!isSuccessful($accountResponse)) {
        redirectWithState('error', 'Failed to create user account record.');
    }

    $personResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/people',
        array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
        [[
            'user_id' => $newUserId,
            'first_name' => $firstName,
            'surname' => $surname,
            'personal_email' => $email,
        ]]
    );

    if (!isSuccessful($personResponse)) {
        redirectWithState('error', 'Account created but people profile creation failed.');
    }

    $employeeRole = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/roles?select=id&role_key=eq.employee&limit=1',
        $headers
    );

    $employeeRoleId = (string)($employeeRole['data'][0]['id'] ?? '');
    if ($employeeRoleId !== '') {
        $assignmentPayload = [
            'user_id' => $newUserId,
            'role_id' => $employeeRoleId,
            'is_primary' => true,
            'assigned_by' => $adminUserId !== '' ? $adminUserId : null,
            'assigned_at' => gmdate('c'),
        ];

        if ($officeId) {
            $assignmentPayload['office_id'] = $officeId;
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/user_role_assignments',
            array_merge($headers, ['Prefer: return=minimal']),
            [$assignmentPayload]
        );
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'user_management',
            'entity_name' => 'user_accounts',
            'entity_id' => $newUserId,
            'action_name' => 'create_account',
            'old_data' => null,
            'new_data' => [
                'email' => $email,
                'role_key' => 'employee',
                'office_id' => $officeId,
                'notes' => $notes,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'User account created. Temporary password is generated server-side.');
}

if ($action === 'role') {
    $userId = cleanText($_POST['role_user_id'] ?? null) ?? '';
    $roleId = cleanText($_POST['role_id'] ?? null) ?? '';
    $effectivityDate = cleanText($_POST['effectivity_date'] ?? null);

    if ($userId === '' || $roleId === '') {
        redirectWithState('error', 'User and role are required for assignment.');
    }

    $assignedAt = $effectivityDate ? ($effectivityDate . 'T00:00:00Z') : gmdate('c');

    $existingRole = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_role_assignments?select=id,user_id,role_id&user_id=eq.' . $userId . '&role_id=eq.' . $roleId . '&limit=1',
        $headers
    );

    apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/user_role_assignments?user_id=eq.' . $userId . '&is_primary=eq.true',
        array_merge($headers, ['Prefer: return=minimal']),
        ['is_primary' => false]
    );

    $existingRoleId = (string)($existingRole['data'][0]['id'] ?? '');
    if ($existingRoleId !== '') {
        $setRole = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/user_role_assignments?id=eq.' . $existingRoleId,
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'is_primary' => true,
                'assigned_by' => $adminUserId !== '' ? $adminUserId : null,
                'assigned_at' => $assignedAt,
                'expires_at' => null,
            ]
        );

        if (!isSuccessful($setRole)) {
            redirectWithState('error', 'Failed to update existing role assignment.');
        }
    } else {
        $createRole = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/user_role_assignments',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'user_id' => $userId,
                'role_id' => $roleId,
                'is_primary' => true,
                'assigned_by' => $adminUserId !== '' ? $adminUserId : null,
                'assigned_at' => $assignedAt,
            ]]
        );

        if (!isSuccessful($createRole)) {
            redirectWithState('error', 'Failed to create role assignment.');
        }
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'user_management',
            'entity_name' => 'user_role_assignments',
            'entity_id' => null,
            'action_name' => 'assign_role',
            'old_data' => null,
            'new_data' => [
                'target_user_id' => $userId,
                'role_id' => $roleId,
                'assigned_at' => $assignedAt,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Role assignment updated.');
}

if ($action === 'credential') {
    $userId = cleanText($_POST['credential_user_id'] ?? null) ?? '';
    $credentialAction = (string)($_POST['credential_action'] ?? '');
    $tempPassword = (string)($_POST['temporary_password'] ?? '');
    $effectiveUntil = cleanText($_POST['effective_until'] ?? null);
    $notes = cleanText($_POST['credential_notes'] ?? null);

    if ($userId === '' || $credentialAction === '') {
        redirectWithState('error', 'User and credential action are required.');
    }

    if ($credentialAction === 'reset_password') {
        if (strlen($tempPassword) < 8) {
            redirectWithState('error', 'Temporary password must be at least 8 characters.');
        }

        $resetResponse = apiRequest(
            'PUT',
            $supabaseUrl . '/auth/v1/admin/users/' . $userId,
            $headers,
            ['password' => $tempPassword]
        );

        if (!isSuccessful($resetResponse)) {
            redirectWithState('error', 'Failed to reset password.');
        }

        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . $userId,
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'must_change_password' => true,
                'failed_login_count' => 0,
                'lockout_until' => null,
                'account_status' => 'active',
            ]
        );
    } elseif ($credentialAction === 'unlock_account') {
        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . $userId,
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'failed_login_count' => 0,
                'lockout_until' => null,
                'account_status' => 'active',
            ]
        );
    } elseif ($credentialAction === 'disable_login') {
        $lockUntil = null;
        if ($effectiveUntil) {
            $lockUntil = $effectiveUntil . 'T23:59:59Z';
        }

        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . $userId,
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'account_status' => 'disabled',
                'lockout_until' => $lockUntil,
            ]
        );
    } else {
        redirectWithState('error', 'Invalid credential action selected.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'user_management',
            'entity_name' => 'user_accounts',
            'entity_id' => $userId,
            'action_name' => $credentialAction,
            'old_data' => null,
            'new_data' => [
                'effective_until' => $effectiveUntil,
                'notes' => $notes,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Credential action applied successfully.');
}

if ($action === 'add_position') {
    $positionTitle = cleanText($_POST['position_title'] ?? null) ?? '';
    $positionCode = strtoupper((string)(cleanText($_POST['position_code'] ?? null) ?? ''));
    $employmentClassification = strtolower((string)(cleanText($_POST['employment_classification'] ?? null) ?? 'regular'));
    $salaryGrade = cleanText($_POST['salary_grade'] ?? null);
    $isSupervisory = isset($_POST['is_supervisory']) && (string)$_POST['is_supervisory'] === '1';

    if ($positionTitle === '' || $positionCode === '') {
        redirectWithState('error', 'Position code and title are required.');
    }

    if (!userManagementIsValidCode($positionCode)) {
        redirectWithState('error', 'Position code must be 2-20 chars using uppercase letters, numbers, dash, or underscore.');
    }

    $allowedClassifications = ['regular', 'coterminous', 'contractual', 'casual', 'job_order'];
    if (!in_array($employmentClassification, $allowedClassifications, true)) {
        redirectWithState('error', 'Invalid employment classification selected.');
    }

    $existingPosition = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/job_positions?select=id,position_title,position_code&or=(position_title.eq.' . encodeFilter($positionTitle) . ',position_code.eq.' . encodeFilter($positionCode) . ')&limit=1',
        $headers
    );

    if (!isSuccessful($existingPosition)) {
        redirectWithState('error', 'Unable to validate existing positions.');
    }

    if (!empty($existingPosition['data'])) {
        redirectWithState('error', 'Position already exists.');
    }

    $createPosition = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/job_positions',
        array_merge($headers, ['Prefer: return=representation']),
        [[
            'position_code' => $positionCode,
            'position_title' => $positionTitle,
            'employment_classification' => $employmentClassification,
            'salary_grade' => $salaryGrade,
            'is_supervisory' => $isSupervisory,
            'is_active' => true,
        ]]
    );

    if (!isSuccessful($createPosition)) {
        redirectWithState('error', 'Failed to create position.');
    }

    $positionId = (string)($createPosition['data'][0]['id'] ?? '');

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'user_management',
            'entity_name' => 'job_positions',
            'entity_id' => $positionId !== '' ? $positionId : null,
            'action_name' => 'add_position',
            'old_data' => null,
            'new_data' => [
                'position_code' => $positionCode,
                'position_title' => $positionTitle,
                'employment_classification' => $employmentClassification,
                'salary_grade' => $salaryGrade,
                'is_supervisory' => $isSupervisory,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Position added successfully.');
}

if ($action === 'add_department') {
    $organizationId = cleanText($_POST['organization_id'] ?? null) ?? '';
    $officeName = cleanText($_POST['office_name'] ?? null) ?? '';
    $officeCode = strtoupper((string)(cleanText($_POST['office_code'] ?? null) ?? ''));
    $officeType = strtolower((string)(cleanText($_POST['office_type'] ?? null) ?? 'unit'));

    if ($officeName === '' || $officeCode === '') {
        redirectWithState('error', 'Department name and code are required.');
    }

    if (!userManagementIsValidCode($officeCode)) {
        redirectWithState('error', 'Department code must be 2-20 chars using uppercase letters, numbers, dash, or underscore.');
    }

    $allowedOfficeTypes = ['central', 'regional', 'provincial', 'division', 'unit'];
    if (!in_array($officeType, $allowedOfficeTypes, true)) {
        redirectWithState('error', 'Invalid office type selected.');
    }

    if ($organizationId === '') {
        $orgResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/organizations?select=id,is_active&is_active=eq.true&order=created_at.asc&limit=1',
            $headers
        );

        $organizationId = (string)($orgResponse['data'][0]['id'] ?? '');
    }

    if (!userManagementIsValidUuid($organizationId)) {
        redirectWithState('error', 'No active organization found. Please configure organization first.');
    }

    $existingOffice = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/offices?select=id,office_name,office_code&or=(office_name.eq.' . encodeFilter($officeName) . ',office_code.eq.' . encodeFilter($officeCode) . ')&limit=1',
        $headers
    );

    if (!isSuccessful($existingOffice)) {
        redirectWithState('error', 'Unable to validate existing departments.');
    }

    if (!empty($existingOffice['data'])) {
        redirectWithState('error', 'Department already exists.');
    }

    $departmentPayload = [
        'organization_id' => $organizationId,
        'office_code' => $officeCode,
        'office_name' => $officeName,
        'office_type' => $officeType,
        'is_active' => true,
    ];

    $createOffice = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/offices',
        array_merge($headers, ['Prefer: return=representation']),
        [$departmentPayload]
    );

    if (!isSuccessful($createOffice)) {
        redirectWithState('error', 'Failed to create department.');
    }

    $officeId = (string)($createOffice['data'][0]['id'] ?? '');

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'user_management',
            'entity_name' => 'offices',
            'entity_id' => $officeId !== '' ? $officeId : null,
            'action_name' => 'add_department',
            'old_data' => null,
            'new_data' => [
                'organization_id' => $organizationId,
                'office_name' => $officeName,
                'office_code' => $officeCode,
                'office_type' => $officeType,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Department added successfully.');
}

redirectWithState('error', 'Unknown form action.');
