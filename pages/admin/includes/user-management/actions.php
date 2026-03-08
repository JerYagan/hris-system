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

if (!function_exists('userManagementAdminRoleId')) {
    function userManagementAdminRoleId(string $supabaseUrl, array $headers): string
    {
        $response = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/roles?select=id&role_key=eq.admin&limit=1',
            $headers
        );

        return (string)($response['data'][0]['id'] ?? '');
    }
}

if (!function_exists('userManagementPrimaryRoleKey')) {
    function userManagementPrimaryRoleKey(string $userId, string $supabaseUrl, array $headers): string
    {
        if (!userManagementIsValidUuid($userId)) {
            return '';
        }

        $primaryRoleResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_role_assignments?select=role:roles!inner(role_key)'
            . '&user_id=eq.' . rawurlencode($userId)
            . '&is_primary=eq.true'
            . '&expires_at=is.null'
            . '&limit=1',
            $headers
        );

        $primaryRoleKey = strtolower(trim((string)($primaryRoleResponse['data'][0]['role']['role_key'] ?? '')));
        if ($primaryRoleKey !== '') {
            return $primaryRoleKey;
        }

        $fallbackRoleResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_role_assignments?select=role:roles!inner(role_key)'
            . '&user_id=eq.' . rawurlencode($userId)
            . '&expires_at=is.null'
            . '&limit=1',
            $headers
        );

        return strtolower(trim((string)($fallbackRoleResponse['data'][0]['role']['role_key'] ?? '')));
    }
}

if (!function_exists('userManagementActiveAdminUserIds')) {
    function userManagementActiveAdminUserIds(string $supabaseUrl, array $headers): array
    {
        $adminRoleId = userManagementAdminRoleId($supabaseUrl, $headers);
        if ($adminRoleId === '') {
            return [];
        }

        $adminAssignmentsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/user_role_assignments?select=user_id&role_id=eq.' . $adminRoleId . '&expires_at=is.null&limit=2000',
            $headers
        );

        if (!isSuccessful($adminAssignmentsResponse)) {
            return [];
        }

        $adminUserIds = [];
        foreach ((array)($adminAssignmentsResponse['data'] ?? []) as $assignment) {
            $assignedUserId = strtolower(trim((string)($assignment['user_id'] ?? '')));
            if ($assignedUserId !== '') {
                $adminUserIds[$assignedUserId] = true;
            }
        }

        return $adminUserIds;
    }
}

if (!function_exists('userManagementUserHasActiveAdminRole')) {
    function userManagementUserHasActiveAdminRole(string $targetUserId, string $supabaseUrl, array $headers): bool
    {
        if (!userManagementIsValidUuid($targetUserId)) {
            return false;
        }

        $adminUserIds = userManagementActiveAdminUserIds($supabaseUrl, $headers);
        return isset($adminUserIds[strtolower(trim($targetUserId))]);
    }
}

if (!function_exists('userManagementCanAssignAdminRole')) {
    function userManagementCanAssignAdminRole(string $targetUserId, string $supabaseUrl, array $headers): bool
    {
        if (!userManagementIsValidUuid($targetUserId)) {
            return false;
        }

        $adminUserIds = userManagementActiveAdminUserIds($supabaseUrl, $headers);
        $targetKey = strtolower(trim($targetUserId));

        if (isset($adminUserIds[$targetKey])) {
            return true;
        }

        return count($adminUserIds) < 2;
    }
}

if (!function_exists('userManagementCreateEmployeeAccount')) {
    function userManagementCreateEmployeeAccount(
        string $email,
        string $fullName,
        ?string $officeId,
        string $adminUserId,
        string $supabaseUrl,
        array $headers,
        ?string &$createdUserId = null,
        ?string &$temporaryPassword = null
    ): array {
        $email = strtolower(trim($email));
        $fullName = trim($fullName);
        $officeId = trim((string)$officeId);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Valid email is required.'];
        }
        if ($fullName === '') {
            return ['ok' => false, 'message' => 'Full name is required for new account.'];
        }

        [$firstName, $surname] = splitFullName($fullName);
        $temporaryPassword = 'Temp#' . substr(bin2hex(random_bytes(6)), 0, 10);

        $createAuth = apiRequest(
            'POST',
            $supabaseUrl . '/auth/v1/admin/users',
            $headers,
            [
                'email' => $email,
                'password' => $temporaryPassword,
                'email_confirm' => true,
                'user_metadata' => [
                    'full_name' => $fullName,
                    'created_by_admin' => $adminUserId,
                ],
            ]
        );

        if (!isSuccessful($createAuth)) {
            $raw = strtolower((string)($createAuth['raw'] ?? ''));
            if (str_contains($raw, 'already') || str_contains($raw, 'exists')) {
                return ['ok' => false, 'message' => 'Email already exists in authentication.'];
            }

            return ['ok' => false, 'message' => 'Failed to create authentication user.'];
        }

        $createdUserId = (string)($createAuth['data']['id'] ?? '');
        if ($createdUserId === '') {
            return ['ok' => false, 'message' => 'Invalid auth response when creating account.'];
        }

        $accountResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/user_accounts',
            array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
            [[
                'id' => $createdUserId,
                'email' => $email,
                'account_status' => 'active',
                'email_verified_at' => gmdate('c'),
                'must_change_password' => true,
            ]]
        );

        if (!isSuccessful($accountResponse)) {
            return ['ok' => false, 'message' => 'Failed to create user account record.'];
        }

        $personResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/people',
            array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
            [[
                'user_id' => $createdUserId,
                'first_name' => $firstName,
                'surname' => $surname,
                'personal_email' => $email,
            ]]
        );

        if (!isSuccessful($personResponse)) {
            return ['ok' => false, 'message' => 'Account created but people profile creation failed.'];
        }

        $employeeRole = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/roles?select=id&role_key=eq.employee&limit=1',
            $headers
        );

        $employeeRoleId = (string)($employeeRole['data'][0]['id'] ?? '');
        if ($employeeRoleId !== '') {
            $assignmentPayload = [
                'user_id' => $createdUserId,
                'role_id' => $employeeRoleId,
                'is_primary' => true,
                'assigned_by' => $adminUserId !== '' ? $adminUserId : null,
                'assigned_at' => gmdate('c'),
            ];

            if ($officeId !== '' && userManagementIsValidUuid($officeId)) {
                $assignmentPayload['office_id'] = $officeId;
            }

            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/user_role_assignments',
                array_merge($headers, ['Prefer: return=minimal']),
                [$assignmentPayload]
            );
        }

        return ['ok' => true, 'message' => 'Account created successfully.'];
    }
}

if (!function_exists('userManagementIsProtectedAdminTarget')) {
    function userManagementIsProtectedAdminTarget(string $targetUserId, string $adminUserId, string $supabaseUrl, array $headers): bool
    {
        if ($targetUserId === '') {
            return false;
        }

        return userManagementUserHasActiveAdminRole($targetUserId, $supabaseUrl, $headers);
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

        if (userManagementIsProtectedAdminTarget($targetUserId, $adminUserId, $supabaseUrl, $headers)) {
            redirectWithState('error', 'Protected admin account cannot be archived/disabled.');
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

    $newUserId = null;
    $tempPassword = null;
    $createAccountResult = userManagementCreateEmployeeAccount(
        $email,
        $fullName,
        $officeId,
        $adminUserId,
        $supabaseUrl,
        $headers,
        $newUserId,
        $tempPassword
    );

    if (!(bool)($createAccountResult['ok'] ?? false)) {
        redirectWithState('error', (string)($createAccountResult['message'] ?? 'Failed to create user account.'));
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

if ($action === 'onboard_new_hire') {
    $applicationId = trim((string)(cleanText($_POST['application_id'] ?? null) ?? ''));
    $email = strtolower(trim((string)(cleanText($_POST['email'] ?? null) ?? '')));
    $fullName = trim((string)(cleanText($_POST['full_name'] ?? null) ?? ''));
    $officeId = trim((string)(cleanText($_POST['office_id'] ?? null) ?? ''));
    $positionTitle = trim((string)(cleanText($_POST['position_title'] ?? null) ?? ''));
    $divisionName = trim((string)(cleanText($_POST['division_name'] ?? null) ?? ''));

    if (!userManagementIsValidUuid($applicationId)) {
        redirectWithState('error', 'Invalid new hire application reference.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $fullName === '') {
        redirectWithState('error', 'New hire email and full name are required.');
    }

    $hiredCheckResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/applications?select=id,application_status,job:job_postings(office_id,position_id,title),applicant:applicant_profiles(user_id,email,full_name,mobile_no)'
        . '&id=eq.' . rawurlencode($applicationId)
        . '&application_status=eq.hired&limit=1',
        $headers
    );

    if (!isSuccessful($hiredCheckResponse) || empty((array)($hiredCheckResponse['data'] ?? []))) {
        redirectWithState('error', 'Selected hire is no longer eligible for onboarding account creation.');
    }

    $verifiedHireRow = (array)($hiredCheckResponse['data'][0] ?? []);
    $verifiedApplicant = (array)($verifiedHireRow['applicant'] ?? []);
    $verifiedJob = (array)($verifiedHireRow['job'] ?? []);
    $verifiedEmail = strtolower(trim((string)($verifiedApplicant['email'] ?? '')));
    $verifiedFullName = trim((string)($verifiedApplicant['full_name'] ?? ''));
    $verifiedApplicantUserId = trim((string)($verifiedApplicant['user_id'] ?? ''));
    $verifiedOfficeId = trim((string)($verifiedJob['office_id'] ?? ''));
    $verifiedPositionId = trim((string)($verifiedJob['position_id'] ?? ''));
    $positionTitle = trim((string)($verifiedJob['title'] ?? $positionTitle));

    if ($verifiedEmail === '' || $verifiedEmail !== $email) {
        redirectWithState('error', 'Hire email mismatch detected. Refresh the page and try again.');
    }
    if ($verifiedFullName !== '') {
        $fullName = $verifiedFullName;
    }
    if (userManagementIsValidUuid($verifiedOfficeId)) {
        $officeId = $verifiedOfficeId;
    }

    $employeeRoleResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/roles?select=id&role_key=eq.employee&limit=1',
        $headers
    );
    $employeeRoleId = trim((string)($employeeRoleResponse['data'][0]['id'] ?? ''));
    if (!userManagementIsValidUuid($employeeRoleId)) {
        redirectWithState('error', 'Employee role configuration is missing.');
    }

    $newUserId = null;
    $temporaryPassword = null;
    $createdNewAccount = false;

    $targetUserId = '';
    if (userManagementIsValidUuid($verifiedApplicantUserId)) {
        $targetUserId = $verifiedApplicantUserId;
    } else {
        $existingAccountResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/user_accounts?select=id&email=eq.' . encodeFilter($email) . '&limit=1',
            $headers
        );

        if (isSuccessful($existingAccountResponse)) {
            $targetUserId = trim((string)($existingAccountResponse['data'][0]['id'] ?? ''));
        }
    }

    if (userManagementIsValidUuid($targetUserId)) {
        $existingEmployeeRoleResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_role_assignments?select=id&user_id=eq.' . rawurlencode($targetUserId)
            . '&role_id=eq.' . rawurlencode($employeeRoleId)
            . '&expires_at=is.null&limit=1',
            $headers
        );

        if (!empty((array)($existingEmployeeRoleResponse['data'] ?? []))) {
            redirectWithState('error', 'This new hire already has an employee account.');
        }

        $temporaryPassword = 'Temp#' . substr(bin2hex(random_bytes(6)), 0, 10);
        $authUpdateResponse = apiRequest(
            'PUT',
            $supabaseUrl . '/auth/v1/admin/users/' . rawurlencode($targetUserId),
            $headers,
            [
                'email' => $email,
                'password' => $temporaryPassword,
                'email_confirm' => true,
                'user_metadata' => [
                    'full_name' => $fullName,
                    'updated_by_admin' => $adminUserId,
                    'source' => 'new_hire_onboarding',
                ],
            ]
        );

        if (!isSuccessful($authUpdateResponse)) {
            redirectWithState('error', 'Failed to activate employee login credentials for this hire.');
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/user_accounts',
            array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
            [[
                'id' => $targetUserId,
                'email' => $email,
                'account_status' => 'active',
                'email_verified_at' => gmdate('c'),
                'must_change_password' => true,
            ]]
        );

        [$firstName, $surname] = splitFullName($fullName);

        $personResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/people?select=id&user_id=eq.' . rawurlencode($targetUserId) . '&limit=1',
            $headers
        );
        $personId = trim((string)($personResponse['data'][0]['id'] ?? ''));
        if (!userManagementIsValidUuid($personId)) {
            $personInsertResponse = apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/people',
                array_merge($headers, ['Prefer: return=representation']),
                [[
                    'user_id' => $targetUserId,
                    'first_name' => $firstName,
                    'surname' => $surname,
                    'personal_email' => $email,
                    'mobile_no' => cleanText($verifiedApplicant['mobile_no'] ?? null),
                ]]
            );

            if (!isSuccessful($personInsertResponse)) {
                redirectWithState('error', 'Failed to prepare employee profile for this hire.');
            }

            $personId = trim((string)($personInsertResponse['data'][0]['id'] ?? ''));
        }

        if (userManagementIsValidUuid($personId) && userManagementIsValidUuid($verifiedOfficeId) && userManagementIsValidUuid($verifiedPositionId)) {
            $employmentCheckResponse = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/employment_records?select=id&person_id=eq.' . rawurlencode($personId)
                . '&is_current=eq.true&limit=1',
                $headers
            );

            if (empty((array)($employmentCheckResponse['data'] ?? []))) {
                apiRequest(
                    'POST',
                    $supabaseUrl . '/rest/v1/employment_records',
                    array_merge($headers, ['Prefer: return=minimal']),
                    [[
                        'person_id' => $personId,
                        'office_id' => $verifiedOfficeId,
                        'position_id' => $verifiedPositionId,
                        'hire_date' => gmdate('Y-m-d'),
                        'employment_status' => 'active',
                        'is_current' => true,
                    ]]
                );
            }
        }

        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/user_role_assignments?user_id=eq.' . rawurlencode($targetUserId) . '&is_primary=eq.true',
            array_merge($headers, ['Prefer: return=minimal']),
            ['is_primary' => false]
        );

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/user_role_assignments',
            array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
            [[
                'user_id' => $targetUserId,
                'role_id' => $employeeRoleId,
                'office_id' => userManagementIsValidUuid($officeId) ? $officeId : null,
                'assigned_by' => $adminUserId !== '' ? $adminUserId : null,
                'assigned_at' => gmdate('c'),
                'is_primary' => true,
                'expires_at' => null,
            ]]
        );

        $newUserId = $targetUserId;
    } else {
        $createAccountResult = userManagementCreateEmployeeAccount(
            $email,
            $fullName,
            $officeId,
            $adminUserId,
            $supabaseUrl,
            $headers,
            $newUserId,
            $temporaryPassword
        );

        if (!(bool)($createAccountResult['ok'] ?? false)) {
            redirectWithState('error', (string)($createAccountResult['message'] ?? 'Failed to create new hire account.'));
        }
        $createdNewAccount = true;
    }

    $mailResultStatus = 'skipped';
    $mailError = null;
    $emailReady = smtpConfigIsReady($smtpConfig, $mailFrom);
    if ($emailReady) {
        $safeFullName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safePassword = htmlspecialchars((string)$temporaryPassword, ENT_QUOTES, 'UTF-8');
        $safePositionTitle = htmlspecialchars($positionTitle !== '' ? $positionTitle : 'Employee', ENT_QUOTES, 'UTF-8');
        $safeDivisionName = htmlspecialchars($divisionName !== '' ? $divisionName : 'Assigned Division', ENT_QUOTES, 'UTF-8');

        $welcomeHtml = '<p>Dear ' . $safeFullName . ',</p>'
            . '<p>Welcome to DA-ATI HRIS. Your employee account has been created.</p>'
            . '<p><strong>Login Credentials</strong><br>'
            . 'Email: ' . $safeEmail . '<br>'
            . 'Temporary Password: ' . $safePassword . '</p>'
            . '<p>Please sign in and change your password immediately.</p>'
            . '<p>Assigned Position: ' . $safePositionTitle . '<br>'
            . 'Division: ' . $safeDivisionName . '</p>'
            . '<p>Welcome aboard!</p>'
            . '<p>— DA-ATI HRIS Admin</p>';

        $mailResponse = smtpSendTransactionalEmail(
            $smtpConfig,
            $mailFrom,
            $mailFromName,
            $email,
            $fullName,
            'Welcome to DA-ATI HRIS - Employee Account Created',
            $welcomeHtml
        );

        if (isSuccessful($mailResponse)) {
            $mailResultStatus = 'sent';
        } else {
            $mailResultStatus = 'failed';
            $mailError = trim((string)($mailResponse['raw'] ?? 'SMTP delivery failed'));
        }
    }

    if ($adminUserId !== '') {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $adminUserId,
                'category' => 'system',
                'title' => 'New hire account created',
                'body' => 'Employee account created for ' . $fullName . ' (' . $email . '). Email delivery: ' . $mailResultStatus . '.',
                'link_url' => '/hris-system/pages/admin/user-management.php',
            ]]
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
            'action_name' => 'onboard_new_hire_account',
            'old_data' => null,
            'new_data' => [
                'application_id' => $applicationId,
                'email' => $email,
                'full_name' => $fullName,
                'position_title' => $positionTitle,
                'division_name' => $divisionName,
                'account_created' => $createdNewAccount,
                'flow' => 'recruitment_add_as_employee_onboarding',
                'mail_status' => $mailResultStatus,
                'mail_error' => $mailError,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    $successMessage = $createdNewAccount
        ? 'New hire employee account created successfully.'
        : 'New hire employee account access activated successfully.';
    if ($mailResultStatus === 'sent') {
        $successMessage .= ' Welcome email sent.';
    } elseif ($mailResultStatus === 'failed') {
        $successMessage .= ' Account created but welcome email failed to send.';
    } else {
        $successMessage .= ' Account created but welcome email skipped (SMTP not configured).';
    }

    redirectWithState('success', $successMessage);
}

if ($action === 'role') {
    $userId = cleanText($_POST['role_user_id'] ?? null) ?? '';
    $roleId = cleanText($_POST['role_id'] ?? null) ?? '';
    $officeId = cleanText($_POST['role_office_id'] ?? null) ?? '';
    $effectivityDate = cleanText($_POST['effectivity_date'] ?? null);

    if ($userId === '' || $roleId === '') {
        redirectWithState('error', 'User and role are required for assignment.');
    }

    if ($officeId !== '' && !userManagementIsValidUuid($officeId)) {
        redirectWithState('error', 'Invalid division selected for role assignment.');
    }

    $selectedRoleResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/roles?select=role_key&id=eq.' . $roleId . '&limit=1',
        $headers
    );

    $selectedRoleKey = strtolower(trim((string)($selectedRoleResponse['data'][0]['role_key'] ?? '')));
    $assignableRolePolicy = array_flip(userManagementAssignableRolePolicy());
    if ($selectedRoleKey === '' || !isset($assignableRolePolicy[$selectedRoleKey])) {
        redirectWithState('error', 'Selected role is not assignable based on current privilege policy.');
    }

    if ($selectedRoleKey === 'admin' && !userManagementCanAssignAdminRole($userId, $supabaseUrl, $headers)) {
        redirectWithState('error', 'Only 2 active admin accounts are allowed. Reassign an existing admin before promoting another user.');
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
                'office_id' => $officeId !== '' ? $officeId : null,
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
                'office_id' => $officeId !== '' ? $officeId : null,
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
                'role_key' => $selectedRoleKey,
                'office_id' => $officeId !== '' ? $officeId : null,
                'assigned_at' => $assignedAt,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Role assignment updated.');
}

if ($action === 'credential') {
    $userId = cleanText($_POST['credential_user_id'] ?? null) ?? '';
    $credentialAction = strtolower((string)($_POST['credential_action'] ?? ''));
    $tempPassword = (string)($_POST['temporary_password'] ?? '');
    $effectiveUntil = cleanText($_POST['effective_until'] ?? null);
    $notes = cleanText($_POST['credential_notes'] ?? null);

    if ($userId === '' || $credentialAction === '') {
        redirectWithState('error', 'User and credential action are required.');
    }

    $targetAccountResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_accounts?select=id,email,people(first_name,surname)&id=eq.' . rawurlencode($userId) . '&limit=1',
        $headers
    );

    $targetAccount = isSuccessful($targetAccountResponse) ? (array)($targetAccountResponse['data'][0] ?? []) : [];
    if (empty($targetAccount)) {
        redirectWithState('error', 'Target user account was not found.');
    }

    $targetEmail = strtolower(trim((string)($targetAccount['email'] ?? '')));
    $targetFirstName = trim((string)($targetAccount['people']['first_name'] ?? ''));
    $targetSurname = trim((string)($targetAccount['people']['surname'] ?? ''));
    $targetDisplayName = trim($targetFirstName . ' ' . $targetSurname);
    if ($targetDisplayName === '') {
        $targetDisplayName = $targetEmail !== '' ? $targetEmail : 'User';
    }

    $targetRoleKey = userManagementPrimaryRoleKey($userId, $supabaseUrl, $headers);

    $mailResultStatus = 'not_applicable';
    $mailError = null;

    if ($credentialAction === 'reset_password') {
        if (!in_array($targetRoleKey, ['employee', 'staff'], true)) {
            redirectWithState('error', 'Password reset is currently allowed for Employee and Staff accounts only.');
        }

        if (strlen($tempPassword) < 8) {
            redirectWithState('error', 'Temporary password must be at least 8 characters.');
        }

        if (!filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
            redirectWithState('error', 'Target account has no valid email for reset notification.');
        }

        $resetResponse = apiRequest(
            'PUT',
            $supabaseUrl . '/auth/v1/admin/users/' . $userId,
            $headers,
            [
                'password' => $tempPassword,
                'email_confirm' => true,
            ]
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

        if (smtpConfigIsReady($smtpConfig, $mailFrom)) {
            $safeName = htmlspecialchars($targetDisplayName, ENT_QUOTES, 'UTF-8');
            $safeEmail = htmlspecialchars($targetEmail, ENT_QUOTES, 'UTF-8');
            $safePassword = htmlspecialchars($tempPassword, ENT_QUOTES, 'UTF-8');

            $emailBody = '<p>Dear ' . $safeName . ',</p>'
                . '<p>Your DA-ATI HRIS password has been reset by an administrator.</p>'
                . '<p><strong>Temporary Login Credentials</strong><br>'
                . 'Email: ' . $safeEmail . '<br>'
                . 'Temporary Password: ' . $safePassword . '</p>'
                . '<p>Please log in and change your password immediately.</p>'
                . '<p>If you did not request this, contact HRIS support right away.</p>'
                . '<p>— DA-ATI HRIS Admin</p>';

            $mailResponse = smtpSendTransactionalEmail(
                $smtpConfig,
                $mailFrom,
                $mailFromName,
                $targetEmail,
                $targetDisplayName,
                'DA-ATI HRIS Password Reset (Temporary Password)',
                $emailBody
            );

            if (isSuccessful($mailResponse)) {
                $mailResultStatus = 'sent';
            } else {
                $mailResultStatus = 'failed';
                $mailError = trim((string)($mailResponse['raw'] ?? 'SMTP delivery failed'));
            }
        } else {
            $mailResultStatus = 'skipped';
            $mailError = 'SMTP not configured';
        }
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
        if (userManagementIsProtectedAdminTarget($userId, $adminUserId, $supabaseUrl, $headers)) {
            redirectWithState('error', 'Protected admin account cannot be archived/disabled.');
        }

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
                'target_email' => $targetEmail !== '' ? $targetEmail : null,
                'target_role_key' => $targetRoleKey !== '' ? $targetRoleKey : null,
                'effective_until' => $effectiveUntil,
                'notes' => $notes,
                'mail_status' => $mailResultStatus,
                'mail_error' => $mailError,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    if ($credentialAction === 'reset_password') {
        $successMessage = 'Password reset applied successfully.';
        if ($mailResultStatus === 'sent') {
            $successMessage .= ' Temporary password email sent.';
        } elseif ($mailResultStatus === 'failed') {
            $successMessage .= ' Password reset applied, but email failed to send.';
        } elseif ($mailResultStatus === 'skipped') {
            $successMessage .= ' Password reset applied, but email was skipped (SMTP not configured).';
        }

        redirectWithState('success', $successMessage);
    }

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

    $allowedClassifications = array_keys(userManagementEmploymentClassificationPolicy());
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
    $officeType = 'division';

    if ($officeName === '' || $officeCode === '') {
        redirectWithState('error', 'Division name and code are required.');
    }

    if (!userManagementIsValidCode($officeCode)) {
        redirectWithState('error', 'Division code must be 2-20 chars using uppercase letters, numbers, dash, or underscore.');
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
        redirectWithState('error', 'Unable to validate existing divisions.');
    }

    if (!empty($existingOffice['data'])) {
        redirectWithState('error', 'Division already exists.');
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
        redirectWithState('error', 'Failed to create division.');
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

    redirectWithState('success', 'Division added successfully.');
}

redirectWithState('error', 'Unknown form action.');
