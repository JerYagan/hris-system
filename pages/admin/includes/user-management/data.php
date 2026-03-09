<?php

$employmentClassificationOptions = userManagementEmploymentClassificationPolicy();
$assignableRolePolicy = array_flip(userManagementAssignableRolePolicy());
$activeAdminLimit = function_exists('userManagementMaxActiveAdmins') ? userManagementMaxActiveAdmins() : 3;

$userManagementMetaCacheKey = 'admin_user_management_meta_cache_v3';
$userManagementMetaCacheTtl = 300;
$userManagementMetaCache = $_SESSION[$userManagementMetaCacheKey] ?? null;
$userManagementMetaCacheValid = is_array($userManagementMetaCache)
    && isset($userManagementMetaCache['cached_at'])
    && ((time() - (int)$userManagementMetaCache['cached_at']) < $userManagementMetaCacheTtl);

$usersResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_accounts?select=id,email,mobile_no,account_status,created_at,people(first_name,surname,mobile_no)&order=created_at.desc&limit=1000',
    $headers
);

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

$primaryRolesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_role_assignments?select=user_id,role:roles(role_name,role_key)&is_primary=eq.true&expires_at=is.null&limit=2000',
    $headers
);

$primaryOfficeAssignmentsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_role_assignments?select=user_id,office_id&is_primary=eq.true&expires_at=is.null&limit=2000',
    $headers
);

$employeeRoleAssignmentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/user_role_assignments?select=user_id,role:roles!inner(role_key)&role.role_key=eq.employee&expires_at=is.null&limit=5000',
    $headers
);

$activeAdminAssignmentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/user_role_assignments?select=user_id,role:roles!inner(role_key)&role.role_key=eq.admin&expires_at=is.null&limit=2000',
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
    . '&application_status=eq.hired&order=updated_at.desc&limit=3000',
    $headers
);

$users = isSuccessful($usersResponse) ? $usersResponse['data'] : [];

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

foreach ($users as $index => $userRow) {
    if (!is_array($userRow)) {
        continue;
    }

    $userRow['people'] = $normalizeUserManagementPerson($userRow['people'] ?? []);
    $users[$index] = $userRow;
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
    $roles = isSuccessful($rolesResponse) ? (array)$rolesResponse['data'] : [];
    $offices = isSuccessful($officesResponse) ? (array)$officesResponse['data'] : [];
    $officesDirectory = isSuccessful($officesDirectoryResponse) ? (array)$officesDirectoryResponse['data'] : [];
    $positions = isSuccessful($positionsResponse) ? (array)$positionsResponse['data'] : [];

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

$primaryRoles = isSuccessful($primaryRolesResponse) ? $primaryRolesResponse['data'] : [];
$primaryOfficeAssignments = isSuccessful($primaryOfficeAssignmentsResponse) ? $primaryOfficeAssignmentsResponse['data'] : [];
$employeeRoleAssignments = isSuccessful($employeeRoleAssignmentsResponse) ? $employeeRoleAssignmentsResponse['data'] : [];
$activeAdminAssignments = isSuccessful($activeAdminAssignmentsResponse) ? $activeAdminAssignmentsResponse['data'] : [];
$employmentRows = isSuccessful($employmentResponse) ? $employmentResponse['data'] : [];

$filteredRoles = [];
foreach ($roles as $role) {
    $roleKey = strtolower(trim((string)($role['role_key'] ?? '')));
    if ($roleKey === '' || !isset($assignableRolePolicy[$roleKey])) {
        continue;
    }
    $filteredRoles[] = $role;
}
$roles = $filteredRoles;

$existingUserEmailMap = [];
foreach ($users as $userRow) {
    $user = (array)$userRow;
    $email = strtolower(trim((string)($user['email'] ?? '')));
    if ($email !== '') {
        $existingUserEmailMap[$email] = true;
    }
}

$hasEmployeeRoleByUserId = [];
foreach ((array)$employeeRoleAssignments as $assignmentRow) {
    $userId = strtolower(trim((string)($assignmentRow['user_id'] ?? '')));
    if ($userId === '') {
        continue;
    }

    $hasEmployeeRoleByUserId[$userId] = true;
}

$activeAdminUserIdSet = [];
foreach ((array)$activeAdminAssignments as $assignmentRow) {
    $userId = strtolower(trim((string)($assignmentRow['user_id'] ?? '')));
    if ($userId === '') {
        continue;
    }

    $activeAdminUserIdSet[$userId] = true;
}

$activeAdminCount = count($activeAdminUserIdSet);

$hasCurrentEmploymentByUserId = [];
foreach ((array)$employmentRows as $employmentRow) {
    $userId = strtolower(trim((string)($employmentRow['person']['user_id'] ?? '')));
    if ($userId === '') {
        continue;
    }

    $hasCurrentEmploymentByUserId[$userId] = true;
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

$newHireCandidates = array_values($newHireCandidatesByEmail);

$primaryRoleMap = [];
foreach ($primaryRoles as $assignment) {
    $userId = (string)($assignment['user_id'] ?? '');
    if ($userId === '' || isset($primaryRoleMap[$userId])) {
        continue;
    }

    $roleName = (string)($assignment['role']['role_name'] ?? $assignment['role']['role_key'] ?? '');
    $primaryRoleMap[$userId] = $roleName !== '' ? $roleName : 'Unassigned';
}

$userOfficeMap = [];
foreach ($primaryOfficeAssignments as $assignment) {
    $userId = (string)($assignment['user_id'] ?? '');
    if ($userId === '' || isset($userOfficeMap[$userId])) {
        continue;
    }

    $officeId = (string)($assignment['office_id'] ?? '');
    $userOfficeMap[$userId] = $officeId;
}
