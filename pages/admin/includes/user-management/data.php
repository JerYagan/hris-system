<?php

$userManagementPartial = strtolower(trim((string)($userManagementPartial ?? '')));
$userManagementDataStage = strtolower(trim((string)($userManagementDataStage ?? 'queue')));

$employmentClassificationOptions = userManagementEmploymentClassificationPolicy();
$assignableRolePolicy = array_flip(userManagementAssignableRolePolicy());
$activeAdminLimit = function_exists('userManagementMaxActiveAdmins') ? userManagementMaxActiveAdmins() : 3;

$shouldLoadQueue = $userManagementDataStage === 'queue';
$shouldLoadReference = $userManagementDataStage === 'reference';
$shouldLoadModals = $userManagementDataStage === 'modals';

$userManagementMetaCacheKey = 'admin_user_management_meta_cache_v3';
$userManagementMetaCacheTtl = 300;
$userManagementMetaCache = $_SESSION[$userManagementMetaCacheKey] ?? null;
$userManagementMetaCacheValid = is_array($userManagementMetaCache)
    && isset($userManagementMetaCache['cached_at'])
    && ((time() - (int)($userManagementMetaCache['cached_at'] ?? 0)) < $userManagementMetaCacheTtl);

$rolesResponse = null;
$officesResponse = null;
$officesDirectoryResponse = null;
$positionsResponse = null;

if (!$userManagementMetaCacheValid) {
    $rolesResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/roles?select=id,role_key,role_name&order=role_name.asc',
        $headers
    );

    $officesResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/offices?select=id,office_name,office_code,is_active&is_active=eq.true&order=office_name.asc',
        $headers
    );

    $officesDirectoryResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/offices?select=id,office_name,office_code,is_active&is_active=eq.true&order=office_name.asc',
        $headers
    );

    $positionsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/job_positions?select=id,position_title,is_active&order=position_title.asc&limit=2000',
        $headers
    );
}

$roles = [];
$offices = [];
$officesDirectory = [];
$positions = [];

if ($userManagementMetaCacheValid) {
    $roles = (array)($userManagementMetaCache['roles'] ?? []);
    $offices = (array)($userManagementMetaCache['offices'] ?? []);
    $officesDirectory = (array)($userManagementMetaCache['offices_directory'] ?? []);
    $positions = (array)($userManagementMetaCache['positions'] ?? []);
} else {
    $roles = isSuccessful($rolesResponse) ? (array)($rolesResponse['data'] ?? []) : [];
    $offices = isSuccessful($officesResponse) ? (array)($officesResponse['data'] ?? []) : [];
    $officesDirectory = isSuccessful($officesDirectoryResponse) ? (array)($officesDirectoryResponse['data'] ?? []) : [];
    $positions = isSuccessful($positionsResponse) ? (array)($positionsResponse['data'] ?? []) : [];

    if (isSuccessful($rolesResponse) && isSuccessful($officesResponse) && isSuccessful($officesDirectoryResponse) && isSuccessful($positionsResponse)) {
        $_SESSION[$userManagementMetaCacheKey] = [
            'cached_at' => time(),
            'roles' => $roles,
            'offices' => $offices,
            'offices_directory' => $officesDirectory,
            'positions' => $positions,
        ];
    }
}

$filteredRoles = [];
foreach ($roles as $role) {
    $roleKey = strtolower(trim((string)($role['role_key'] ?? '')));
    if ($roleKey === '' || !isset($assignableRolePolicy[$roleKey])) {
        continue;
    }
    $filteredRoles[] = $role;
}
$roles = $filteredRoles;

$normalizeUserManagementPerson = static function (mixed $personValue): array {
    if (!is_array($personValue)) {
        return [];
    }

    $keys = array_keys($personValue);
    $isList = $keys === range(0, count($keys) - 1);
    if ($isList) {
        return isset($personValue[0]) && is_array($personValue[0]) ? $personValue[0] : [];
    }

    return $personValue;
};

$normalizeUserRows = static function (array $rows) use ($normalizeUserManagementPerson): array {
    foreach ($rows as $index => $userRow) {
        if (!is_array($userRow)) {
            continue;
        }

        $userRow['people'] = $normalizeUserManagementPerson($userRow['people'] ?? []);
        $rows[$index] = $userRow;
    }

    return $rows;
};

$users = [];
$modalUsers = [];
$primaryRoleMap = [];
$userOfficeMap = [];
$activeAdminUserIdSet = [];
$activeAdminCount = 0;
$newHireCandidates = [];
$summaryCards = [];

if ($shouldLoadQueue || $shouldLoadModals) {
    $activeAdminAssignmentsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/user_role_assignments?select=user_id,role:roles!inner(role_key)&role.role_key=eq.admin&expires_at=is.null&limit=2000',
        $headers
    );

    $activeAdminAssignments = isSuccessful($activeAdminAssignmentsResponse) ? (array)($activeAdminAssignmentsResponse['data'] ?? []) : [];
    foreach ($activeAdminAssignments as $assignmentRow) {
        $userId = strtolower(trim((string)($assignmentRow['user_id'] ?? '')));
        if ($userId === '') {
            continue;
        }
        $activeAdminUserIdSet[$userId] = true;
    }
    $activeAdminCount = count($activeAdminUserIdSet);
}

if ($shouldLoadQueue) {
    $usersResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_accounts?select=id,email,mobile_no,account_status,created_at,people(first_name,surname,mobile_no)&order=created_at.desc&limit=25',
        $headers
    );
    $users = $normalizeUserRows(isSuccessful($usersResponse) ? (array)($usersResponse['data'] ?? []) : []);

    $primaryRolesResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_role_assignments?select=user_id,role:roles(role_name,role_key)&is_primary=eq.true&expires_at=is.null&limit=2000',
        $headers
    );
    $primaryRoles = isSuccessful($primaryRolesResponse) ? (array)($primaryRolesResponse['data'] ?? []) : [];
    foreach ($primaryRoles as $assignment) {
        $userId = (string)($assignment['user_id'] ?? '');
        if ($userId === '' || isset($primaryRoleMap[$userId])) {
            continue;
        }

        $roleName = (string)($assignment['role']['role_name'] ?? $assignment['role']['role_key'] ?? '');
        $primaryRoleMap[$userId] = $roleName !== '' ? $roleName : 'Unassigned';
    }

    $employeeRoleAssignmentsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/user_role_assignments?select=user_id,role:roles!inner(role_key)&role.role_key=eq.employee&expires_at=is.null&limit=5000',
        $headers
    );

    $employmentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=id,person:people!employment_records_person_id_fkey(user_id),is_current'
        . '&is_current=eq.true&limit=5000',
        $headers
    );

    $hiredApplicationsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/applications?select=id,application_ref_no,application_status,updated_at,job:job_postings(id,title,office_id,position:job_positions(position_title),office:offices(id,office_name)),applicant:applicant_profiles(full_name,email,user_id)'
        . '&application_status=eq.hired&order=updated_at.desc&limit=200',
        $headers
    );

    $existingUserEmailMap = [];
    foreach ($users as $userRow) {
        $email = strtolower(trim((string)($userRow['email'] ?? '')));
        if ($email !== '') {
            $existingUserEmailMap[$email] = true;
        }
    }

    $hasEmployeeRoleByUserId = [];
    foreach ((array)(isSuccessful($employeeRoleAssignmentsResponse) ? ($employeeRoleAssignmentsResponse['data'] ?? []) : []) as $assignmentRow) {
        $userId = strtolower(trim((string)($assignmentRow['user_id'] ?? '')));
        if ($userId !== '') {
            $hasEmployeeRoleByUserId[$userId] = true;
        }
    }

    $hasCurrentEmploymentByUserId = [];
    foreach ((array)(isSuccessful($employmentResponse) ? ($employmentResponse['data'] ?? []) : []) as $employmentRow) {
        $userId = strtolower(trim((string)($employmentRow['person']['user_id'] ?? '')));
        if ($userId !== '') {
            $hasCurrentEmploymentByUserId[$userId] = true;
        }
    }

    $newHireCandidatesByEmail = [];
    if (isSuccessful($hiredApplicationsResponse)) {
        foreach ((array)($hiredApplicationsResponse['data'] ?? []) as $applicationRow) {
            $application = (array)$applicationRow;
            $applicant = (array)($application['applicant'] ?? []);
            $job = (array)($application['job'] ?? []);
            $position = (array)($job['position'] ?? []);
            $office = (array)($job['office'] ?? []);

            $email = strtolower(trim((string)($applicant['email'] ?? '')));
            if ($email === '') {
                continue;
            }

            $applicantUserId = strtolower(trim((string)($applicant['user_id'] ?? '')));
            $hasEmployeeRole = $applicantUserId !== '' && isset($hasEmployeeRoleByUserId[$applicantUserId]);
            $hasCurrentEmployment = $applicantUserId !== '' && isset($hasCurrentEmploymentByUserId[$applicantUserId]);

            if ($applicantUserId !== '') {
                if (!$hasCurrentEmployment || $hasEmployeeRole) {
                    continue;
                }
            } elseif (isset($existingUserEmailMap[$email])) {
                continue;
            }

            if (isset($newHireCandidatesByEmail[$email])) {
                continue;
            }

            $fullName = trim((string)($applicant['full_name'] ?? ''));
            $positionTitle = trim((string)($position['position_title'] ?? ''));
            $divisionName = trim((string)($office['office_name'] ?? ''));
            $applicationRef = trim((string)($application['application_ref_no'] ?? ''));
            $hiredAtRaw = trim((string)($application['updated_at'] ?? ''));
            $hiredAtLabel = $hiredAtRaw !== '' ? date('M d, Y h:i A', strtotime($hiredAtRaw)) : '-';
            $officeId = trim((string)($job['office_id'] ?? ''));

            $newHireCandidatesByEmail[$email] = [
                'application_id' => (string)($application['id'] ?? ''),
                'application_ref_no' => $applicationRef,
                'full_name' => $fullName !== '' ? $fullName : $email,
                'email' => $email,
                'applicant_user_id' => $applicantUserId,
                'position_title' => $positionTitle !== '' ? $positionTitle : 'Unassigned Position',
                'division_name' => $divisionName !== '' ? $divisionName : 'Unassigned Division',
                'office_id' => $officeId,
                'hired_at' => $hiredAtLabel,
            ];
        }
    }

    $newHireCandidates = array_slice(array_values($newHireCandidatesByEmail), 0, 10);

    $summaryCards = [
        [
            'label' => 'Assignable Roles',
            'value' => (string)count($roles),
            'icon' => 'badge',
            'tone' => 'bg-sky-50 text-sky-700 border-sky-200',
        ],
        [
            'label' => 'Active Admin Users',
            'value' => (string)$activeAdminCount . ' / ' . (string)$activeAdminLimit,
            'icon' => 'shield_person',
            'tone' => 'bg-amber-50 text-amber-700 border-amber-200',
        ],
        [
            'label' => 'Configured Divisions',
            'value' => (string)count($officesDirectory),
            'icon' => 'apartment',
            'tone' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        ],
        [
            'label' => 'Configured Positions',
            'value' => (string)count($positions),
            'icon' => 'work',
            'tone' => 'bg-violet-50 text-violet-700 border-violet-200',
        ],
    ];
}

if ($shouldLoadModals) {
    $modalUsersResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_accounts?select=id,email,mobile_no,account_status,created_at,people(first_name,surname,mobile_no)&order=created_at.desc&limit=1000',
        $headers
    );
    $modalUsers = $normalizeUserRows(isSuccessful($modalUsersResponse) ? (array)($modalUsersResponse['data'] ?? []) : []);

    $primaryOfficeAssignmentsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_role_assignments?select=user_id,office_id&is_primary=eq.true&expires_at=is.null&limit=2000',
        $headers
    );

    $primaryOfficeAssignments = isSuccessful($primaryOfficeAssignmentsResponse) ? (array)($primaryOfficeAssignmentsResponse['data'] ?? []) : [];
    foreach ($primaryOfficeAssignments as $assignment) {
        $userId = (string)($assignment['user_id'] ?? '');
        if ($userId === '' || isset($userOfficeMap[$userId])) {
            continue;
        }

        $userOfficeMap[$userId] = (string)($assignment['office_id'] ?? '');
    }
}